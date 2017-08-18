<?php
namespace Hyper\EventProcessingBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand,
    Symfony\Component\Console\Input\InputInterface,
    Hyper\EventProcessingBundle\Service\EventHandling\ForwardEventHandling,
    Symfony\Component\Console\Output\OutputInterface;

class ForwardEventHandingCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        //$queueName = $this->getContainer()->getParameter('amazon_sqs_ak_postback_forward');
        $this
            ->setName('event_processing:forward_event_handling')
            ->setDescription("Run a job to fetch ak-postback-forward-queue queue");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->checkExistProcess('forward_event_handling')) {
            exit;
        }
        $forwardEventHandling = new ForwardEventHandling(
            $this->getContainer()
            , $this->getContainer()->getParameter('amazon_sqs_ak_postback_forward')
            , $this->getContainer()->getParameter('amazon_sqs_queue_pre_event_handling')
            , $this->getContainer()->getParameter('amazon_s3_bucket_name')
        );
        $forwardEventHandling->handle();
    }



    private function checkExistProcess($processName, $maxProcess = 1)
    {
        $countPs = 0;
        exec("ps -fC php", $psList);
        if (!empty($psList)) {
            foreach($psList as $ps) {
                if (strpos($ps,$processName)!==false) {
                    $countPs ++;
                }
            }
        }
        if ($countPs <= $maxProcess) {
            return false;
        }
        return true;
    }
}