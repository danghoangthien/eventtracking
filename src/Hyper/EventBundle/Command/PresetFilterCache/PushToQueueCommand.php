<?php
namespace Hyper\EventBundle\Command\PresetFilterCache;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Hyper\Domain\Setting\Setting;
use Hyper\EventBundle\Service\Cached\Card\LastCardPushToQueueCached;
use Hyper\EventBundle\Service\Cached\Setting\SettingCached;

class PushToQueueCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('preset_filter_cache:push_to_queue')
            ->setDescription('Send list filter need to update the cache to sqs.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $queueName = $this->getContainer()->getParameter('amazon_sqs_queue_cache_filter');
            $filterRepo = $this->getContainer()->get('filter_repository');
            $sqsWraper = $this->getContainer()->get('hyper_event_processing.sqs_wrapper');
            $totalCount = $filterRepo->getFilterTotalCount();
            $currCount = 0;
            if (!$totalCount) {
                throw new \Exception("0 rows found.");
            }
            $this->stopHorizontalProcess();
            do {
                $result = $filterRepo->getFiterByLimit(100, $currCount);
                $sqsWraper->sendMessageBatch($queueName, $result);
                $currCount = $currCount + count($result);
                if ($currCount == $totalCount) {
                    $lastCardPushToQueueCached = new LastCardPushToQueueCached($this->getContainer());
                    $value = json_encode(end($result));
                    $lastCardPushToQueueCached->set($value);
                }
            } while($currCount < $totalCount);
        } catch(\Exception $e) {
            echo $e->getMessage();
        }

    }

    protected function stopHorizontalProcess()
    {
        $settingCached = new SettingCached($this->getContainer());
        $settingCached->hset(Setting::PRE_EVENT_HANDLING_TYPE_KEY, Setting::STATUS_STOP_VALUE);
        $settingCached->hset(Setting::EVENT_HANDLING_TYPE_KEY, Setting::STATUS_STOP_VALUE);
        $settingCached->hset(Setting::POST_EVENT_HANDLING_TYPE_KEY, Setting::STATUS_STOP_VALUE);
        $settingCached->hset(Setting::IDENTITY_CAPTURE_HANDLING_TYPE_KEY, Setting::STATUS_STOP_VALUE);
    }

}