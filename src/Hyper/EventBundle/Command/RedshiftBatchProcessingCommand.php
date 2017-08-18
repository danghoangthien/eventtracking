<?php
namespace Hyper\EventBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RedshiftBatchProcessingCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('redshift:batch')
            ->setDescription('generate frm from transaction actions');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
            echo "executing batch script.<br/>";
            $start = date('d-m-Y H:i:s');
            echo "start @ ".$start."\n";
            
            //$start = date('d-m-Y H:i:s');
            $processName = 'redshift:batch --env=prod'; 
            $exists= false;
            $countPs = 0;
            exec("ps -fC 'php'", $psList);
            if (count($psList) > 1) {
                foreach($psList as $ps) {
                    echo $ps."\n";
                    if (strpos($ps,$processName)!==false) {
                        //echo "-----TRUE "."\n";
                        $exists = true;
                        $countPs += 1;
                    }
                }
            }
            
            if ($countPs <= 1) {
                $redshiftBatchProcessing = $this->getContainer()->get('redshift_batch_processing_service');
                $redshiftBatchProcessing->init();
                $redshiftBatchProcessing->processV2(true);
                echo "start @ ".$start."\n";
                echo "end @ ".date('d-m-Y H:i:s')."\n";
                echo "process suddenly stopped,started over @ ".$start."\n";
                //exec($processName." > /dev/null 2>/dev/null &");
                //exec($processName." > /dev/null 2>/dev/null &");
                exec("ps -fC 'php'", $psListFinal);
                var_dump($psListFinal);
            } else {
                echo "process is still running when checking @ ".$start."\n";
                return;
            }
            
            

    }
    
}