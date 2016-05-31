<?php

namespace AppBundle\Command;

use AppBundle\Services\ExcelGenerator;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListadosSolicitudesCommand extends ContainerAwareCommand
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
            ->setName('listados:solicitudes')
            ->setDescription('...')
            ->addArgument('argument', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
       $this
           ->setInput($input)
           ->setOutput($output);

        $container = $this->getContainer();

        $dropbox = $container->get('app.dropbox.sender');

        $duplicados = $container->get('app.listado.duplicados')->generate();
        $dropbox->send($duplicados);
        $output->writeln("Generado el fichero " . $duplicados->getFilename());

        $solicitudes = $container->get('app.listado.solicitudes')->generate();
        $dropbox->send($solicitudes);
        $output->writeln("Generado el fichero " . $solicitudes->getFilename());

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

}
