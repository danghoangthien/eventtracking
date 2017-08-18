<?php
namespace Hyper\EventAPIBundle\Command\TrendCard;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Hyper\EventAPIBundle\Service\TrendCard\TrendCardProcessor;
use Hyper\EventBundle\Service\Cached\TrendCard\TrendCardCached;
use Hyper\EventAPIBundle\Service\TrendCard\TrendCardFactory;

class TrendCardCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName('trend_card:processor')
            ->setDescription('Processor for Trend Card');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $processor = new TrendCardProcessor(
            new TrendCardCached($this->getContainer())
            , new TrendCardFactory($this->getContainer())
            , $this->getContainer()->get('monolog.logger.trend_card_api')
        );
        $processor->handle();
    }

}