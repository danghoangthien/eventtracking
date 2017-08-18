<?php
namespace Hyper\EventBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Carl Pham <vanca.vnn@gmail.com>
 */
class ElasticSearchCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('elasticsearch')
            ->setDescription('Index Elastic Search')
            ->addOption(
                'action',
                null,
                InputOption::VALUE_REQUIRED,
                'Elastic Search Actions: index, delete'
            )
            ->addOption(
                'schema',
                null,
                InputOption::VALUE_REQUIRED,
                'Table name.'
            )
            ->addOption(
                'app_title_s3folder',
                null,
                InputOption::VALUE_OPTIONAL,
                'App title s3 forlder name.'
            );
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $schema = $input->getOption('schema');
        if (null == $schema) {
            $output->writeln('Please provide schema like: --schema=actions');
            exit();
        }
        $esAction = $input->getOption('action');
        if (null == $esAction) {
            $output->writeln('Please provide elastic search action like: --action=index');
            exit();
        }
        $appTitleS3Folders = $input->getOption('app_title_s3folder');
        $searchService = $this->getContainer()->get('elasticsearch_service');
        $esInstance = $searchService->init($schema);
        
        switch ($esAction){
            case 'index':
                $this->esIndex($esInstance, $appTitleS3Folders);
                break;
            case 'delete':
                $this->esDelete($esInstance, $appTitleS3Folders);
                break;
            default:
                echo 'Please provide action.';
                break;
        }
        
    }
    
    public function esIndex($esInstance, $appTitleS3Folders = null)
    {
        echo "Start index... \n";
        if (null == $appTitleS3Folders) {
            $esInstance->perform();
        } else {
            $appTitleS3Folders = explode(',', $appTitleS3Folders);
            $esInstance->perform($appTitleS3Folders);
        }
        echo "End index.\n";
    }
    
    public function esDelete($esInstance, $indexName = null)
    {
        echo "Start detele... \n";
        if (null == $indexName) {
            $esInstance->deleteBulkElasticSearch();
        } else {
            $esInstance->deleteBulkElasticSearch($indexName);
        }
        echo "Completed delete all index elastic search.\n";
    }
    
}