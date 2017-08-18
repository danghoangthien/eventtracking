<?php
namespace Hyper\EventBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

use Hyper\EventBundle\Document\Person;
use Hyper\EventBundle\Document\Transaction;
use Hyper\EventBundle\Annotations\CsvMetaReader;

class CsvUploadCommand extends ContainerAwareCommand
{
    
    protected function configure()
    {
        $this->setName('csv:upload')
            ->setDescription('Load csv parse to JSON and store to S3')
            ->addOption(
                'file',
                null,
                InputOption::VALUE_REQUIRED,
                'Csv file path to import'
            )
            ->addOption(
                'provider',
                null,
                InputOption::VALUE_REQUIRED,
                'Provider is required!'
            )
            ->addOption(
                'app_id',
                null,
                InputOption::VALUE_REQUIRED,
                'Application Id is required!'
            )
            ->addOption(
                'event_type',
                null,
                InputOption::VALUE_REQUIRED,
                'Event Type is required!'
            );
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // echo "exec \n";
        $filePath       = $input->getOption('file');
        $provider   = $input->getOption('provider');
        $appId      = $input->getOption('app_id');
        $eventType  = $input->getOption('event_type');
        // echo 'file: '.$filePath;
        $csvUploadLogId = basename($filePath, '.csv');
        // echo 'csvuploadlogid: '.$csvUploadLogId;
        $params = array('provider'=>$provider, 'app_id'=>$appId, 'event_type'=>$eventType);
        
        $totalRowUploaded = 0;
        if (($handle = fopen($filePath,"r")) !== FALSE) {
            $fields = fgetcsv($handle);
            if ($fields === FALSE) {
                // Write log here
                echo 'read file fail!';
                return;
            }
            $fields = $this->getFiledMap($provider);
            // while (($data = fgetcsv($handle)) !== FALSE && ! feof($handle)) {
            while (($data = fgetcsv($handle)) !== FALSE) {
                $content = [];
                foreach ($fields as $key=>$field) {
                    $content[$field] = $data[$key];
                }
                $filePath = $this->csvStoreS3($content, $params);
                $totalRowUploaded ++;
                // echo "File Path: $filePath \n";
            }
            fclose($handle);
            
            $csvUploadLogRepo = $this->getContainer()->get('event.csvuploadlog.repository');
            $csvUploadLog = $csvUploadLogRepo->find($csvUploadLogId);
            $endTime = time();
            $csvUploadLog->setEndTime($endTime);
            $csvUploadLog->setTotalRowUploaded($totalRowUploaded);
            
            $csvUploadLogRepo->update($csvUploadLog);
        } else {
            // Write log here
            echo 'open file fail';
            return;
        }
    }
    
    protected function csvStoreS3($content, $params)
    {
        $storageController = $this->getContainer()->get('hyper_event.storage_controller_v4');
        $amazonBaseURL = $this->getContainer()->getParameter('hyper_event.amazon_s3.base_url');
        // $rootDir = $this->getContainer()->get('kernel')->getRootDir();
        $rawLogDir = '/var/www/html/projects/event_tracking/web/raw_event';

        $rawContent = json_encode($content, true);
        $content['app_id'] = $params['app_id'];
        $content['event_type'] = $params['event_type'];
        $s3FolderMappping = $this->getS3FolderMapping();
        
        $filePath = $storageController->storeEventS3(
            $rawContent,
            $content,
            $amazonBaseURL,
            $rawLogDir,
            $s3FolderMappping
        );
        
        return $filePath;
    }
    
    protected function getS3FolderMapping()
    {
        return array(
            'com.bukalapak.android' => 'bukalapak',
            'id1003169137' => 'bukalapak',
            'com.daidigames.banting' => 'asianpoker',
            'id961876128' => 'asianpoker',
            'sg.gumi.bravefrontier' => 'bravefrontier',
            'id694609161' => 'bravefrontier',
            'sg.gumi.chainchronicleglobal' => 'chainchronicle',
            'id935189878' => 'chainchronicle',
            'sg.gumi.wakfu' => 'wakfu',
            'id942908715'   => 'wakfu',
            '_test' => '_test',
            'com.apn.mobile.browser' => 'askbrowser',
            'com.woi.liputan6.android' => 'liputan6',
            'com.google.android.apps.santatracker' => 'santatracker',
            'com.akasanet.yogrt.android' => 'yogrt',
            'id950197859' => 'yogrt',
            'id1049249612' => 'raiderquests'
        );
    }
    
    protected function getFiledMap($provider)
    {
        if ('appsflyer' == $provider) {
            return ['click_time','install_time','event_time','event_name','event_value','currency','af_prt','pid','c','af_siteid',
            'af_cpi','country_code','city','ip','wifi','language','appsflyer_device_id','customer_user_id','android_id','imei','mac','advertising_id','device_type',
            'os_version','sdk_version','app_version','operator','carrier','af_sub1','af_sub2','af_sub3','af_sub4','af_sub5','click_url'];
        }
    }
}