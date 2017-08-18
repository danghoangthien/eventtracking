<?php
namespace Hyper\EventBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RedshiftBatchProcessingControlCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('redshift:batch_control')
            ->setDescription('watch batch processing process,force it start again when it suddenly stopped');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $start = date('d-m-Y H:i:s');
        $processName = '/usr/bin/php app/console redshift:batch --env=prod'; 
        $exists= false;
        exec("ps -fC 'php'", $psList);
        if (count($psList) > 1) {
            foreach($psList as $ps) {
                echo $ps."\n";
                if (strpos($ps,$processName)!==false) {
                    //echo "-----TRUE "."\n";
                    $exists = true;
                }
            }
        }
        //print_r($psList);
        if ($exists == false) {
            echo "process suddenly stopped,started over @ ".$start."\n";
            //exec($processName." > /dev/null 2>/dev/null &");
            exec($processName." > /dev/null 2>/dev/null &");
            exec("ps -fC 'php'", $psListFinal);
            var_dump($psListFinal);
        } else {
            echo "process is still running when checking @ ".$start."\n";
        }
        return;
    }
    
}