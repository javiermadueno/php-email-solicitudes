<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListadosDuplicadosCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('listados:duplicados')
            ->setDescription('Genera solo el Excel con las solicitudes de ducplicados. Es un shortcut del comando listados:solicitudes --duplicados')
            ->addOption('to-dropbox', null, InputOption::VALUE_NONE, 'Enviar a dropbox')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $command = $this->getApplication()->find('listados:solicitudes');

        $argumentos = new ArrayInput([
           'command' => 'listados:solicitudes',
            '--duplicados' => true,
            '--to-dropbox' => $input->getOption('to-dropbox')
        ]);

        $command->run($argumentos, $output);


    }

}
