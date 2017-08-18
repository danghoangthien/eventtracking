<?php
namespace Hyper\EventBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TestCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('test:stuff')
            ->setDescription('test tuff');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo "executing count S3 event script.<br/>";
        $start = date('d-m-Y H:i:s');
        echo "start @ ".$start."\n";
        $testController = $this->getContainer()->get('hyper_event.test_controller');
        $testController->testBatchInsert();
        $end = date('d-m-Y H:i:s');
        echo "end @ ".$end."\n";
        return;
            
            

    }
    
}