<?php
namespace Hyper\EventProcessingBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface;

class IdentityCaptureHandlingCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('event_processing:identity_capture_handling')
            ->setDescription('Run a job to store each email identified by device from identity-capture queue');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo "executing identity capture handling script.\n";
        $start = date('d-m-Y H:i:s');
        echo "start @ ".$start."\n";
        $processName = 'event_processing:identity_capture_handling --env=prod';
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
            $identityCaptureHandling = $this->getContainer()
                            ->get('hyper_event_processing.identity_capture_handling');
            $identityCaptureHandling->run();
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