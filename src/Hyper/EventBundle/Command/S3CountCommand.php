<?php
namespace Hyper\EventBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class S3CountCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('s3:count_event')
            ->setDescription('count S3 event')
            ->addOption(
                'date',
                null,
                InputOption::VALUE_REQUIRED,
                'Csv file path to import'
            )
            ->addOption(
                'app_folder',
                null,
                InputOption::VALUE_REQUIRED,
                'Provider is required!'
            )
            ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
            echo "executing count S3 event script.<br/>";
            $start = date('d-m-Y H:i:s');
            echo "start @ ".$start."\n";
            $date       = $input->getOption('date');
            $appFolder   = $input->getOption('app_folder');
            $s3CountEvent = $this->getContainer()->get('s3_count_event_service');
            $s3CountEvent->init();
            $s3CountEvent->process($date,$appFolder);
            $end = date('d-m-Y H:i:s');
            echo "end @ ".$end."\n";
            return;
            
            

    }
    
}