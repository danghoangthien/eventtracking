<?php
namespace Hyper\EventProcessingBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface;

class PreEventHandingCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('event_processing:pre_event_handling')
            ->setDescription('Run a job to store Devices and Applications from event_handling queue.');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo "executing pre event handling script.\n";
        $start = date('d-m-Y H:i:s');
        echo "start @ ".$start."\n";
        $processName = 'event_processing:pre_event_handling --env=prod'; 
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
            $preEventHanding = $this->getContainer()
                            ->get('hyper_event_processing.pre_event_handling');
            $preEventHanding->run();
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