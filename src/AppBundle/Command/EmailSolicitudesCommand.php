<?php

namespace AppBundle\Command;

use AppBundle\Util\DateUtil;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class EmailSolicitudesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('email:solicitudes')
            ->setDescription('Envia un email con un resumen diario de las solicitudes')
            ->addOption('to', 't', InputOption::VALUE_OPTIONAL, 'Destinatario del email')
            ->addOption('fecha','f', InputOption::VALUE_OPTIONAL, 'Fecha de calculo de las solicitudes')
            ->addOption('send', null, InputOption::VALUE_NONE, 'Calcula los datos y envia el email.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $output->setDecorated(false);

        if($fecha = $input->getOption('fecha')) {
            $fecha = new \DateTime($fecha);
        } else {
            $fecha = new \DateTime('now');
        }

        $manana = clone $fecha;

        $manana->modify('tomorrow');


        $desde = $this->calculaFechaAnterior($fecha);


        $solicitudesCompletadas = $this->getSolicitudesCompletadas($desde, $fecha, $output);
        $oficinas               = $this->getOcupacionOficinas($desde, $fecha, $output);


        if ($input->getOption('send')) {

            if(!$destinatario = $input->getOption('to')){
                throw new InvalidOptionException('No se ha especificado destinatario');
            }

            $this
                ->getContainer()
                ->get('app.services.mail_sender')
                ->renderAndSend([
                    'destinatario' => $destinatario,
                    'solicitudes' => $solicitudesCompletadas,
                    'oficinas' => $oficinas,
                    'hoy' => $fecha,
                    'desde' => $desde,
                    'manana' => $manana
                ]);
        }

    }


    protected function calculaFechaAnterior(\DateTime $hoy = null)
    {
        if(!$hoy) {
            $hoy = new \DateTime('now');
        }

        $desde =  clone $hoy;

        //Si es lunes se obtienen las solicitudes desde el viernes anterior
        if(DateUtil::dayOfWeek($hoy) == 'L') {
            $desde->modify('last friday');
        } else {

            //Si no, se obtienen las solicitudes y citas desde el dia anterior
            $desde->modify('yesterday');
        }

        return $desde;
    }


    /**
     * @param \DateTime       $desde
     * @param \DateTime       $fecha
     * @param OutputInterface $output
     *
     * @return array
     */
    public function getSolicitudesCompletadas(\DateTime $desde, \DateTime $fecha, OutputInterface $output)
    {
        $solicitudes = $this
            ->getContainer()
            ->get('solicitudes_repository')
            ->findTotalSolicitudesCompletadas($desde, $fecha);

        if (count($solicitudes) > 0) {
            $table = new Table($output);

            $table
                ->setHeaders(array_keys(reset($solicitudes)))
                ->addRows($solicitudes)
                ->render();
        }

        return $solicitudes;
    }

    /**
     * @param \DateTime       $desde
     * @param \DateTime       $fecha
     * @param OutputInterface $output
     *
     * @return \AppBundle\Model\Oficina[]
     */
    public function getOcupacionOficinas(\DateTime $desde, \DateTime $fecha, OutputInterface $output)
    {
        $oficinas = $this
            ->getContainer()
            ->get('citas_repository')
            ->findCapacidadPorOficina($desde, $fecha);


        if (count($oficinas) > 0) {
            $table2 = new Table($output);
            $table2->setHeaders(array_keys(reset($oficinas)->toArray()));

            foreach ($oficinas as $id => $oficina) {
                $table2->addRow($oficina->toArray());
            }

            $table2->render();
        }

        return $oficinas;
    }

}
