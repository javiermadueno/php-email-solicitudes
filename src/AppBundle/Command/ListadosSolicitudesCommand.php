<?php

namespace AppBundle\Command;


use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListadosSolicitudesCommand extends BaseCommand
{



    protected function configure()
    {
        $this
            ->setName('listados:solicitudes')
            ->setDescription('Generea archivos de Excel con los listados de solicitudes')
            ->addOption('to-dropbox', null, InputOption::VALUE_NONE, 'Envia los listados a DropBox')
            ->addOption('--duplicados', null, InputOption::VALUE_NONE, 'Genera Excel de duplicados con el nombre de archivo especificado')
            ->addOption('--solicitudes', null, InputOption::VALUE_NONE, 'Genera Excel de solicitudes del sistema con el nombre de archivo especificado')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
       $this
           ->setInput($input)
           ->setOutput($output);

        $container = $this->getContainer();

        $duplicados = $solicitudes = null;

        if($input->getOption('duplicados') || !$input->getOption('solicitudes')) {
            $duplicados = $container->get('app.listado.duplicados')->generate();
            $output->writeln("Generado el fichero " . $duplicados->getFilename());
        }

        if($input->getOption('solicitudes') || !$input->getOption('duplicados')) {
            $solicitudes = $container->get('app.listado.solicitudes')->generate();
            $output->writeln("Generado el fichero " . $solicitudes->getFilename());
        }


        if($input->getOption('to-dropbox')) {
            $dropbox = $container->get('app.dropbox.sender');

            if($duplicados instanceof \SplFileObject) {
                $dropbox->send($duplicados);
                $output->writeln('Enviado a Dropbox el fichero '. $duplicados->getFilename());
            }

            if($solicitudes instanceof \SplFileObject) {
                $dropbox->send($solicitudes);
                $output->writeln('Enviado a Dropbox el fichero ' . $solicitudes->getFilename());
            }
        }

    }


}
