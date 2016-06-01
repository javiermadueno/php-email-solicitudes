<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListadosNormalesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('listados:normales')
            ->setDescription('Genera solo el Excel con las solicitudes normales. Es un shortcut del comando listados:solicitudes --solicitudes')
            ->addOption('to-dropbox', null, InputOption::VALUE_NONE, 'Enviar a dropbox')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $command = $this->getApplication()->find('listados:solicitudes');

        $argumentos = new ArrayInput([
            '--solicitudes' => true,
            '--to-dropbox' => $input->getOption('to-dropbox')
        ]);

        $command->run($argumentos, $output);
    }

}
