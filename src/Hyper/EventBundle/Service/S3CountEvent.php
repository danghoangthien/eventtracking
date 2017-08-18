<?php
namespace Hyper\EventBundle\Service;

use Hyper\Domain\Setting\Setting;

class S3CountEvent
{
    private $container;
    
    private $s3Region;
    private $s3Bucket;
    private $s3SecurityKey;
    private $s3SecuritySecret;
    
    private $s3;
    
    private $batchProcessTempFolder;
    
    public function __construct(\Symfony\Component\DependencyInjection\ContainerInterface $container){
        $this->container = $container;
        
    }
    
    public function init(){
        $this->s3Region = $this->container->getParameter('amazon_s3_region');
        $this->s3Bucket = 'raw-event-log-v2';
        //$this->s3Bucket = $this->container->getParameter('amazon_s3_bucket_name');
        $this->s3SecurityKey = $this->container->getParameter('amazon_aws_key');
        $this->s3SecuritySecret = $this->container->getParameter('amazon_aws_secret_key');
        
        $this->batchProcessTempFolder = '/var/www/html/projects/event_tracking/web/batch_processing_s3';
        $this->s3 = $this->connectToS3();
    }
    
    public function connectToS3(){
        $credentials = new \Aws\Credentials\Credentials($this->s3SecurityKey, $this->s3SecuritySecret);
        $options = [
            'region'            => $this->s3Region,
            'version'           => '2006-03-01',
            'signature_version' => 'v4',
            'credentials' => $credentials
        ];
       
        return new  \Aws\S3\S3Client(
            $options
        );
    }
    /*
    * $date : 2015/09/29
    * $appFolder : ex - liputan6
    */
    public function processLegacy($date,$appFolder) {
        
        // get hour folders in date
        //"liputan6\/2016\/02\/22\/00\/41\/appsflyer_com.woi.liputan6.android_in-app-event_1447110598_56c9e8b700f94.gz"
        /*
        $result = $this->s3->getObject(
                        array(
                            'Bucket' => $this->s3Bucket,
                            'Key' => 'liputan6/2016/02/22/00/41/appsflyer_com.woi.liputan6.android_in-app-event_1447110598_56c9e8b700f94.gz', 
                            //'SaveAs' => $tmpFile
                        )
                    );
        print_r($result);die;
        */
        $dateBasedPath = $appFolder.'/'.$date.'/';
        $hourPathList = $this->getS3folderList($dateBasedPath);
        //print_r($hourPathList);
        $breakDownEvent = array();
        //$i = 0;
        foreach ($hourPathList as $hourPath) {
            $s3LogPerMinutePathList = $this->getS3folderList($hourPath);
            foreach ($s3LogPerMinutePathList as $s3LogPerMinutePath) {
                $localS3LogPerMinutePath = $this->batchProcessTempFolder.'/'.$s3LogPerMinutePath;
                if (!file_exists($localS3LogPerMinutePath)) {
                    echo $localS3LogPerMinutePath;
                    mkdir($localS3LogPerMinutePath, 0755, true);
                }
                $eventLogGzObjectList = $this->s3->getIterator('ListObjects', array(
                    'Bucket'    => $this->s3Bucket,
                    'Prefix'    => $s3LogPerMinutePath,
                    'Delimiter' => '/',
                ));
                //print_r($eventLogGzObjectList);
                //begin transaction
                
                foreach ($eventLogGzObjectList as $eventLogGzObject) {
                    $tmpFile = $this->batchProcessTempFolder.'/'.$eventLogGzObject['Key'];
                    $filePart = explode('_',$eventLogGzObject['Key']);
                    $eventType = $filePart[2];
                    if ($eventType == 'install') {
                        if(!array_key_exists('install',$breakDownEvent)){
                            $breakDownEvent['install'] = 1;
                        } else {
                            $breakDownEvent['install']+= 1;
                        }
                    } elseif ($eventType =='in-app-event') {
                        $result = $this->s3->getObject(
                        array(
                            'Bucket' => $this->s3Bucket,
                            'Key' => $eventLogGzObject['Key'], 
                            'SaveAs' => $tmpFile
                        )
                        );
                        $logContent = $this->gzToArray($tmpFile);
                        unlink($tmpFile);
                        $eventName = $logContent['event_name'];
                        if(!array_key_exists($eventName,$breakDownEvent)){
                            $breakDownEvent[$eventName] = 1;
                        } else {
                            $breakDownEvent[$eventName]+= 1;
                        }
                        
                    }
                    
                    
                    //var_dump($result);die;
                    //count
                  //$i++;
                }
                //commit transaction
                
            }
            
        }
    
        echo "finish processing"."\n";
        print_r($breakDownEvent);
        return;
        
    }
    
    /**
    * Count app event by S3 file metadata
    * $date : 2015/09/29
    * $appFolder : ex - liputan6
    **/
    public function process($date,$appFolder) {
        
        // get hour folders in date
        //"liputan6\/2016\/02\/22\/00\/41\/appsflyer_com.woi.liputan6.android_in-app-event_1447110598_56c9e8b700f94.gz"
        /*
        $result = $this->s3->getObject(
                        array(
                            'Bucket' => $this->s3Bucket,
                            'Key' => 'liputan6/2016/02/22/00/41/appsflyer_com.woi.liputan6.android_in-app-event_1447110598_56c9e8b700f94.gz', 
                            //'SaveAs' => $tmpFile
                        )
                    );
        print_r($result);die;
        */
        $dateBasedPath = $appFolder.'/'.$date.'/';
        $hourPathList = $this->getS3folderList($dateBasedPath);
        //print_r($hourPathList);
        $breakDownEvent = array();
        //$i = 0;
        foreach ($hourPathList as $hourPath) {
            $s3LogPerMinutePathList = $this->getS3folderList($hourPath);
            foreach ($s3LogPerMinutePathList as $s3LogPerMinutePath) {
                $localS3LogPerMinutePath = $this->batchProcessTempFolder.'/'.$s3LogPerMinutePath;
                echo "Processing:: $localS3LogPerMinutePath"."\n";
                /*
                if (!file_exists($localS3LogPerMinutePath)) {
                    echo $localS3LogPerMinutePath;
                    mkdir($localS3LogPerMinutePath, 0755, true);
                }
                */
                $eventLogGzObjectList = $this->s3->getIterator('ListObjects', array(
                    'Bucket'    => $this->s3Bucket,
                    'Prefix'    => $s3LogPerMinutePath,
                    'Delimiter' => '/',
                ));
                //print_r($eventLogGzObjectList);
                //begin transaction
                
                foreach ($eventLogGzObjectList as $eventLogGzObject) {
                    //$tmpFile = $this->batchProcessTempFolder.'/'.$eventLogGzObject['Key'];
                    $filePart = explode('_',$eventLogGzObject['Key']);
                    $eventType = $filePart[2];
                    if ($eventType == 'install') {
                        if (!array_key_exists('install',$breakDownEvent)) {
                            $breakDownEvent['install'] = 1;
                        } else {
                            $breakDownEvent['install']+= 1;
                        }
                    } elseif ($eventType =='in-app-event') {
                        $result = $this->s3->headObject(
                            array(
                                'Bucket' => $this->s3Bucket,
                                'Key' => $eventLogGzObject['Key'], 
                                //'SaveAs' => $tmpFile
                            )
                        );
                        //$logContent = $this->gzToArray($tmpFile);
                        //unlink($tmpFile);
                        //print_r($result);
                        $metadata = $result['Metadata'];
                        if(!isset($metadata['x-amz-meta-event_name'])){
                            echo "invalid metadata in {$eventLogGzObject['Key']} could not count this event"."\n";
                        }
                        $eventName = $metadata['x-amz-meta-event_name'];
                        if (!array_key_exists($eventName,$breakDownEvent)) {
                            $breakDownEvent[$eventName] = 1;
                        } else {
                            $breakDownEvent[$eventName]+= 1;
                        }
                        
                    }
                    
                    
                    //var_dump($result);die;
                    //count
                  //$i++;
                }
                //echo "\n";
                //print_r($breakDownEvent);
                //echo "\n";
                //commit transaction
                
            }
            
        }
    
        echo "finish processing"."\n";
        print_r($breakDownEvent);
        return;
        
    }
    
    public function getS3folderList($prefix){
        $s3FolderList = array();
        //list folder
        $objects = $this->s3->listObjects(
            array(
                    'Bucket' => $this->s3Bucket,
                    'Prefix'    => $prefix,
                    'Delimiter' => '/'
            )        
        );
        $objectAsArray = $objects->toArray();
        //echo "<pre>";
        //var_dump( $objectAsArray );die;
        if(isset($objectAsArray['CommonPrefixes'])){
            $FolderByPrefixes = $objectAsArray['CommonPrefixes'];
            
            foreach($FolderByPrefixes as $folderByPrefix) {
                $s3FolderList[] = $folderByPrefix['Prefix'];
                unset($folderByPrefix);
            }
            unset($FolderByPrefixes);
        }
        unset($objects);
        unset($objectAsArray);
        return $s3FolderList;
    }
    
    
    private function getLocalS3FolderMapping() {
        $storageController = $this->container->get('hyper_event.storage_controller_v4');
        return $storageController->getS3FolderMapping();
    }
    
    private function gzToArray($file) {
        // try catch
        $zh = gzopen($file,'r') or die("can't open: $php_errormsg");
        $content = '';
        while ($line = gzgets($zh,1024)) {
            $content .= $line;
        }
        gzclose($zh) or die("can't close: $php_errormsg");
        unset($zh);
        return json_decode($content,true);
    }
    
    private function log(\Exception $ex){

    }

}
