<?php
namespace Hyper\EventBundle\Command\PresetFilterCache;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ReceiveFromQueueCommand extends ContainerAwareCommand
{
    const MAX_PROCESS = 10;
    protected function configure()
    {
        $this
            ->setName('preset_filter_cache:receive_from_queue')
            ->setDescription('Receive list filter from sqs.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $pc = $this->processCount();
            //echo "\n$pc";
            if ($pc >= self::MAX_PROCESS) {
                return;
            }
            $msgMax = self::MAX_PROCESS - $pc;
            $queueName = $this->getContainer()->getParameter('amazon_sqs_queue_cache_filter');
            $sqsWraper = $this->getContainer()->get('hyper_event_processing.sqs_wrapper');
            $listFilter = $sqsWraper->receiveMessagesBodyFromQueue($queueName, $msgMax);
            //$listFilterTotal = count($listFilter);
            //echo "\nTotal list filter: {$listFilterTotal}";
            if (empty($listFilter)) {
                throw new \Exception('No message received.');
            }
            foreach ($listFilter as $filter) {
                $this->buidCmd($filter['id']);
            }
        } catch(\Exception $e) {
            echo $e->getMessage();
        }
    }

    protected function processCount()
    {
        $processName = 'preset_filter_cache:update_cache --env=prod';
        $countPs = 0;
        exec("ps -fC 'php'", $psList);
        if (count($psList) > 1) {
            foreach($psList as $ps) {
                if (strpos($ps,$processName) !== false) {
                    $countPs += 1;
                }
            }
        }

        return $countPs;
    }

    protected function buidCmd($filterId)
    {
        $rootDir = $this->getContainer()->get('kernel')->getRootDir() . '/../';
        $cmd = "cd {$rootDir}";
        $cmd .= " && php app/console preset_filter_cache:update_cache --env=prod --preset_filter_id=$filterId";
        $cmd .= " >> app/logs/preset_filter_cache_update_cache.log 2>&1 &";
        //echo "\n$cmd";
        exec($cmd);
    }

}