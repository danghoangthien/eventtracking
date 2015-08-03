<?php
namespace Hyper\EventBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PushToMongoDBCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('event:push')
            ->setDescription('Push event from memcached to mongo db then clear memcache')
            ->addOption(
                'hours-ago',
                null,
                InputOption::VALUE_REQUIRED,
                'How many hours ago that you want to retrieve memcached?'
            )
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $hoursAgo = $input->getOption('hours-ago');
        if($hoursAgo && is_numeric($hoursAgo)){
            $storageController = $this->getContainer()->get('hyper_event.storage_controller');
            $storageController->pushMemcachedToMongoDB($hoursAgo);
        }else{
            echo "invalid option value";
        }
        
    }
    
}