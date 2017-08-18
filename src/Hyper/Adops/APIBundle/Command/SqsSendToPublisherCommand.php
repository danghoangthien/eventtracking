<?php
namespace Hyper\Adops\APIBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use GuzzleHttp\Client;

use Hyper\Adops\APIBundle\Command\SqsContainerAware;
use Hyper\Adops\APIBundle\Handler\SqsContainerHandler;
use Hyper\Adops\APIBundle\Handler\SqsSendToPublisherHandler;

/**
 * Get messages from sqs "postback-queue".
 * Send data to publisher.
 * Send again to sqs "adops-report-queue" and "adops-log-queue"
 *
 * @author Carl Pham <vanca.vnn@gmail.com>
 */
class SqsSendToPublisherCommand extends SqsContainerAware
{
    protected function configure()
    {
        $this->setName('sqs:send-to-publisher')
            ->setDescription('Receive messages from SQS and send to publisher!')
            ->addOption(
                'queue-name',
                null,
                InputOption::VALUE_NONE,
                'Queue Name'
            )
            ->addOption(
                'skip-check-ip',
                null,
                InputOption::VALUE_OPTIONAL,
                'Check Ip'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $queueName = $input->getOption('queue-name');
        $skipCheckIp = $input->getOption('skip-check-ip');

        if ((bool)$skipCheckIp || (!(bool)$skipCheckIp && $this->checkIp())) {
            $sqsSendToPublisherHandler = new SqsSendToPublisherHandler($container);
            $sqsContainerHandler = new SqsContainerHandler($sqsSendToPublisherHandler);
            $messages = $sqsContainerHandler->getMessage('amazon_sqs_queue_name', 100);
            if (count($messages) > 0) {
                foreach ($messages as $message) {
                    $sqsContainerHandler->perform($message);
                }
                $output->write('Completed!');
            } else {
                $output->write('Empty queue!');
            }
        } else {
            $output->write('Not match IP!');
        }
    }

}