<?php
namespace Hyper\EventBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateFrmCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('frm:generate')
            ->setDescription('generate frm from transaction actions');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
            echo "generating FRM.<br/>";
            $start = date('d-m-Y H:i:s');
            echo "start @ ".$start."\n";
            //die;
            $testController = $this->getContainer()->get('hyper_event.test_controller');
            $testController->generatePurchaseFrm();
            echo "start @ ".$start."\n";
            echo "end @ ".date('d-m-Y H:i:s')."\n";

    }
    
}