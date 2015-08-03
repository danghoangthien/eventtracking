<?php
namespace Hyper\EventBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Hyper\EventBundle\Document\Person;
use Hyper\EventBundle\Document\Transaction;
use Hyper\EventBundle\Annotations\CsvMetaReader;

class CsvImportCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('csv:import')
            ->setDescription('load csv parse to JSON and store to S3')
            ->addOption(
                'file',
                null,
                InputOption::VALUE_REQUIRED,
                'CSV file path to import'
            )
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $file = $input->getOption('file');
        $contents = $this->parseCsvContent($file);
        
        /*
        if($hoursAgo && is_numeric($hoursAgo)){
            $storageController = $this->getContainer()->get('hyper_event.storage_controller');
            $storageController->pushMemcachedToMongoDB($hoursAgo);
        }else{
            echo "invalid option value";
        }
        */
        
    }
    
    protected function parseCsvContent($csvFile)
    {

        $csvMetaReader = new CsvMetaReader();
        $personCsvMongoDbIndex = $csvMetaReader->csvMongoDbIndex('\Hyper\EventBundle\Document\Person');
        $transactionCsvMongoDbIndex = $csvMetaReader->csvMongoDbIndex('\Hyper\EventBundle\Document\Transaction');
        $csvMongoDbIndex = array_merge($personCsvMongoDbIndex,$transactionCsvMongoDbIndex);
        $content = array();
        
        $storageController = $this->getContainer()->get('hyper_event.storage_controller_v4');
        
        $amazonBaseURL = $storageController->getAmazonBaseURL();
        $rootDir = $storageController->get('kernel')->getRootDir();// '/var/www/html/projects/event_tracking/app'
        $rawLogDir = $rootDir. '/../web/raw_event';
        $s3FolderMappping = $storageController->getS3FolderMapping();
        $supportProvider = $storageController->getPostBackProviders();
        $providerId = 0;
        if($providerId!==null && array_key_exists($providerId,$supportProvider)) {
            $storageController->postBackProvider = $supportProvider[$providerId];
        }
        
        if (($handle = fopen($csvFile, "r")) !== false) {
            $i = 0;
            $header = array();
            while(($row = fgetcsv($handle)) !== false) {
                if($i == 0){
                    $header = $row;
                } else {
                    $contentIndex = $i-1;
                    foreach ($header as $index => $columnName) {
                       $mongoIndex = array_search(strtolower($columnName),$csvMongoDbIndex);
                       if ($mongoIndex) {
                            $content[$contentIndex][$mongoIndex] = $row[$index];
                       }
                    }
                    $rawContent = json_encode($content[$contentIndex]);
                    $result = $storageController->storeEventS3(
                        $rawContent,
                        $content[$contentIndex],
                        $amazonBaseURL,
                        $rawLogDir,
                        $s3FolderMappping
                    );
                }
                $i++;
            }
        }
        //return $content;
    }
    
}