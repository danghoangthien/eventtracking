<?php
namespace Hyper\EventBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class S3ToGlacierCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('improve:s3_to_glacier')
            ->setDescription('Tranfer raw-event-log bucket from s3 to glacier')
            ->addOption(
                'app',
                null,
                InputOption::VALUE_REQUIRED,
                'app name'
            )
            ->addOption(
                'from',
                null,
                InputOption::VALUE_REQUIRED,
                'From is required!'
            )
            ->addOption(
                'to',
                null,
                InputOption::VALUE_REQUIRED,
                'To is required!'
            )
            ->addOption(
                'delete',
                null,
                InputOption::VALUE_REQUIRED,
                'delete s3'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
            echo "executing tranfer raw-event-log s3 to glacier script\n";
            $start = date('d-m-Y H:i:s');
            echo "start @ ".$start."\n";
            $app = $input->getOption('app');
            if ($app == null) {
                $output->writeln('Please provide app like: --app=BBM,bukalapak');
                return;
            }
            $from = $input->getOption('from');

            if ($from == null) {
                $output->writeln('Please provide from like: --from=2015-12');
                return;
            }
            $to = $input->getOption('to');
            if ($to == null) {
                $output->writeln('Please provide to like: --to=2016-04');
                return;
            }
            $delete = $input->getOption('delete');
            $s3ToGlacier = $this->getContainer()->get('s3_to_glacier_service');
            $s3ToGlacier->init();
            $s3ToGlacier->process($app, $from, $to, $delete);
            $end = date('d-m-Y H:i:s');
            echo "end @ ".$end."\n";
            return;
    }

}