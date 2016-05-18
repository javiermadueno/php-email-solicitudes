<?php

namespace AppBundle\Command;

use AppBundle\Repository\SolicitudesRespository;
use AppBundle\Util\DateUtil;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class EmailSolicitudesCommand extends ContainerAwareCommand
{
    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    protected function configure()
    {
        $this
            ->setName('email:solicitudes')
            ->setDescription('Envia un email con un resumen diario de las solicitudes')
            ->addOption('to', 't', InputOption::VALUE_OPTIONAL, 'Destinatario del email')
            ->addOption('fecha', 'f', InputOption::VALUE_OPTIONAL, 'Fecha de calculo de las solicitudes')
            ->addOption('send', null, InputOption::VALUE_NONE, 'Calcula los datos y envia el email.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->setDecorated(true);

        $this
            ->setInput($input)
            ->setOutput($output);

        if ($hoy = $input->getOption('fecha')) {
            $hoy = new \DateTimeImmutable($hoy);
        } else {
            $hoy = new \DateTimeImmutable('now');
        }

        $manana = $hoy->modify('tomorrow');
        $desde  = $this->calculaFechaAnterior($hoy);

        $this->printFechasUtilizadas($desde, $hoy, $manana);


        $solicitudesCompletadas = $this->getSolicitudesCompletadas($desde, $hoy);
        $acumulados             = $this->getAcumuladosHasta($hoy);
        $oficinas               = $this->getOcupacionOficinas($hoy, $manana);


        if ($input->getOption('send')) {

            $this
                ->getContainer()
                ->get('app.services.mail_sender')
                ->renderAndSend([
                    'destinatario' => $input->getOption('to'),
                    'solicitudes'  => $solicitudesCompletadas,
                    'oficinas'     => $oficinas,
                    'hoy'          => $hoy,
                    'desde'        => $desde,
                    'manana'       => $manana
                ]);
        }

        //$this->printHorasDisponibles($hoy, $output);

    }

    /**
     * @param InputInterface $input
     *
     * @return $this
     */
    protected function setInput(InputInterface $input)
    {
        $this->input = $input;
        return $this;
    }

    /**
     * @param OutputInterface $output
     *
     * @return $this
     */
    protected function setOutput(OutputInterface $output)
    {
        $this->output = $output;
        return $this;
    }

    /**
     * @param \DateTimeInterface $desde
     * @param \DateTimeInterface $hoy
     * @param \DateTimeInterface $manana
     */
    protected function printFechasUtilizadas(\DateTimeInterface $desde, \DateTimeInterface $hoy, \DateTimeInterface $manana)
    {
        /** @var FormatterHelper $formatter */
        $formatter = $this->getHelper('formatter');

        $fechasUtilizadas = $formatter->formatBlock([
            'Fechas utilizadas',
            sprintf('Fecha hoy: %s', $hoy->format(SolicitudesRespository::FORMATO_FECHA)),
            sprintf('Fecha anterior: %s', $desde->format(SolicitudesRespository::FORMATO_FECHA)),
            sprintf('Fecha posterior: %s', $manana->format(SolicitudesRespository::FORMATO_FECHA)),
        ], 'info', $large = true);

        $this->output->writeln($fechasUtilizadas);
    }


    /**
     * @param \DateTimeImmutable $hoy
     *
     * @return \DateTimeImmutable
     */
    protected function calculaFechaAnterior(\DateTimeImmutable $hoy = null)
    {
        if (!$hoy) {
            $hoy = new \DateTimeImmutable('now');
        }

        //Si es lunes se obtienen las solicitudes desde el viernes anterior
        if (DateUtil::dayOfWeek($hoy) == 'L') {
            $desde = $hoy->modify('last friday');
        } else {

            //Si no, se obtienen las solicitudes y citas desde el dia anterior
            $desde = $hoy->modify('yesterday');
        }

        return $desde;

    }


    /**
     * @param \DateTimeInterface $desde
     * @param \DateTimeInterface $fecha
     *
     * @return array
     */
    public function getSolicitudesCompletadas(\DateTimeInterface $desde,  \DateTimeInterface $fecha)
    {
        $solicitudes = $this
            ->getContainer()
            ->get('solicitudes_repository')
            ->findTotalSolicitudesCompletadas($desde, $fecha);

        if (count($solicitudes) > 0) {
            $table = new Table($this->output);

            $table
                ->setHeaders(array_keys(reset($solicitudes)))
                ->addRows($solicitudes)
                ->render();
        }

        return $solicitudes;
    }

    /**
     * @param \DateTimeInterface $hoy
     * @param \DateTimeInterface $manana
     *
     * @return \AppBundle\Model\Oficina[]
     */
    public function getOcupacionOficinas(\DateTimeInterface $hoy, \DateTimeInterface $manana)
    {
        $oficinas = $this
            ->getContainer()
            ->get('citas_repository')
            ->findCapacidadPorOficina($hoy, $manana);


        if (count($oficinas) > 0) {
            $table2 = new Table($this->output);
            $table2->setHeaders(array_keys(reset($oficinas)->toArray()));

            foreach ($oficinas as $id => $oficina) {
                $table2->addRow($oficina->toArray());
            }

            $table2->render();
        }

        return $oficinas;
    }

    /**
     * @param \DateTimeInterface $hoy
     *
     * @return array
     */
    public function getAcumuladosHasta(\DateTimeInterface $hoy)
    {
        $acumulados = $this
            ->getContainer()
            ->get('solicitudes_repository')
            ->findAcumuladoSolicitudesHasta($hoy);

        if (count($acumulados) > 0) {
            $table = new Table($this->output);

            $table->addRows($acumulados);

            $table->render();
        }

        return $acumulados;
    }

    /**
     * @param \DateTimeInterface $hoy
     */
    private function printHorasDisponibles(\DateTimeInterface $hoy)
    {
        $horas = $this
            ->getContainer()
            ->get('citas_repository')
            ->finHorasDisponibles($hoy);

        $table = new Table($this->output);
        //$table->setHeaders(array_keys(reset($horas)));
        $table->addRows($horas);
        $table->render();
    }

}
