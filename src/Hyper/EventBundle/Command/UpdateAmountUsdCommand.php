<?php
namespace Hyper\EventBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use \Aws\Common\Exception\TransferException;

class UpdateAmountUsdCommand extends ContainerAwareCommand
{
    protected $output;

    protected function configure()
    {
        $this
            ->setName('update_amount_usd:update')
            ->setDescription('Update amount usd for past event.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        try {
            $queueName = $this->getContainer()->getParameter('amazon_sqs_queue_inappevent_config');
            $sqsWraper = $this->getContainer()->get('hyper_event_processing.sqs_wrapper');
            $listIaeConfig = $sqsWraper->receiveMessagesBodyFromQueue($queueName, 1);
            // $listIaeConfig[] = [
            //     'event_name' => 'purchase demo'
            //     , 'app_id' => 'com.mefashionita.android'
            // ];
            if (empty($listIaeConfig)) {
                throw new \Exception('No message received.');
            }
            $actionRepo = $this->getContainer()->get('action_repository');
            foreach ($listIaeConfig as $iaeConfig) {
                $listAction = $actionRepo->updateAmountUSD(
                    $iaeConfig['event_name']
                    , $iaeConfig['app_id']
                );
                // $listAction = [
                //     [
                //         'id' => '2cb8b58da3a7c22f8cd21ce1c311bf7b'
                //         , 'amount_usd' => '20'
                //     ]
                //     , [
                //         'id' => '2931e9c463578b7c502d0ea12352c488'
                //         , 'amount_usd' => '30'
                //     ]
                // ];
                if (!empty($listAction)) {
                    $esIndex = $actionRepo->getAppTitleS3FolderByAppId($iaeConfig['app_id']);
                    $this->getContainer()->get('es_action_repository')->updateAmountUSD($esIndex, $iaeConfig['app_id'], $listAction);
                }
            }
        } catch (\Exception $e) {
            $this->logger($e->getCode() . ':' . $e->getMessage());
        }
    }

    protected function logger($message, $type = null)
    {
        //$this->output->writeln(date("Y-m-d H:i:s: ") . $message);
    }

}