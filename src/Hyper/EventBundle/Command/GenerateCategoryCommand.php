<?php
namespace Hyper\EventBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateCategoryCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('category:generate')
            ->setDescription('generate category from item metadata');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
            echo "generating Category.<br/>";
            echo "start @ ".date('d-m-Y H:i:s')."\n";
            //die;
            $testController = $this->getContainer()->get('hyper_event.test_controller');
            $testController->generateCategory();
            echo "end @ ".date('d-m-Y H:i:s')."\n";

    }
    
}