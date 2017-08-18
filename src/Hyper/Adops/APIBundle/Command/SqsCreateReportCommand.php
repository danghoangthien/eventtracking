<?php
namespace Hyper\Adops\APIBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Aws\Sqs\SqsClient;
use GuzzleHttp\Client;

use Hyper\Adops\APIBundle\Command\SqsContainerAware;
use Hyper\Adops\APIBundle\Handler\SqsContainerHandler;
use Hyper\Adops\APIBundle\Handler\SqsCreateReportHandler;

/**
 * Get messages from sqs "adops-report-queue" and insert to DB.
 *
 * @author Carl Pham <vanca.vnn@gmail.com>
 */
class SqsCreateReportCommand extends SqsContainerAware
{
    protected function configure()
    {
        $this->setName('sqs:create-report')
            ->setDescription('Get message from SQS and create report!')
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
        $skipCheckIp = $input->getOption('skip-check-ip');

        if ((bool)$skipCheckIp || (!(bool)$skipCheckIp && $this->checkIp())) {
            $sqsCreateReportHandler = new SqsCreateReportHandler($container);
            $sqsContainerHandler = new SqsContainerHandler($sqsCreateReportHandler);
            $messages = $sqsContainerHandler->getMessage('amazon_sqs_report_queue', 100);
            if (count($messages) > 0) {
                $sqsContainerHandler->perform($messages);
                $output->write('Completed!');
            } else {
                $output->write('Empty queue!');
            }
        } else {
            $output->write('Not match IP!');
        }
    }

}