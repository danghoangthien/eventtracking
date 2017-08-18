<?php
namespace Hyper\EventBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateAnalyticsMetadataCommand extends ContainerAwareCommand
{
    protected $analyticsRepo;
    protected $analyticsController;
    protected $listKey = array();
    
    protected function configure()
    {
        $this
            ->setName('analytics_metadata:generate')
            ->setDescription('generate meta data metadata')
            ->addOption(
                'key',
                null,
                InputOption::VALUE_REQUIRED,
                'Key is required!'
            );   
    }
    
    protected function initParams()
    {
        $this->analyticsController = $this->getContainer()->get('analytics.controller');
        $this->analyticsRepo = $this->getContainer()
        ->get('analytics_metadata_repository');
        $this->listKey = $this->analyticsRepo->findAllKey();
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
            $this->initParams();
            echo "generating Metadata.\n";
            echo "start @ ".date('d-m-Y H:i:s')."\n";
            $key = $input->getOption('key');
            if ($key) {
                try {
                    //$key = "profiles_breakdown_by_eventplatform";
                    $this->analyticsController->generateMetadataValues($key);
                    //$analyticsController->generateMetadataRows();
                } catch (\Exception $e) {
                    echo $e->getMessage();
                }
            } else {
                if ($this->listKey) {
                    foreach ($this->listKey as $item) {
                        $cmd = $this->analyticsController->executeCommandMetadata($item['key']);
                        echo "execute command: $cmd \n";
                    }
                }
            }
            echo "end @ ".date('d-m-Y H:i:s')."\n";
    }
    
}