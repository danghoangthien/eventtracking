<?php
namespace Hyper\EventBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class EsReIndexCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('es:re_index')
            ->setDescription('Re index elasticsearch')
            ->addOption(
                'app-title',
                null,
                InputOption::VALUE_REQUIRED,
                'app title'
            )
            ->addOption(
                'from',
                null,
                InputOption::VALUE_REQUIRED,
                'from date'
            )
            ->addOption(
                'to',
                null,
                InputOption::VALUE_REQUIRED,
                'to date'
            )
             ->addOption(
                'delete',
                null,
                InputOption::VALUE_REQUIRED,
                'delete index'
            );

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
            echo "executing re-index app title elasticsearch script\n";

            $appTitle = $input->getOption('app-title');
            if ($appTitle == null) {
                $output->writeln('Please provide app title like: --app-title=bukalapak');
                return;
            }
            $fromDate = $input->getOption('from');
            if ($fromDate == null) {
                $output->writeln('Please provide from date like: --from=2016-01-02');
                return;
            }
            $toDate = $input->getOption('to');
            if ($toDate == null) {
                $output->writeln('Please provide to date like: --to=2017-01-02');
                return;
            }
            $delete = $input->getOption('delete');
            if ($delete == null) {
                $output->writeln('Please provide delete like: --delete=1 or --delete=0');
                return;
            }
            $from = strtotime($fromDate);
            $to = strtotime($toDate) + 24 * 60 * 60;
            $reIndexEs = $this->getContainer()->get('re_index_es_service');
            $start = date('d-m-Y H:i:s');
            echo "start @ app-title: $appTitle, from: $from($fromDate), to: $to($toDate),-->$start ","\n";
            $reIndexEs->process($appTitle, $from, $to, $delete);
            $end = date('d-m-Y H:i:s');
            echo "end @ app-title: $appTitle, from: $from, to: $to,-->$end ","\n";
            return;
    }

}