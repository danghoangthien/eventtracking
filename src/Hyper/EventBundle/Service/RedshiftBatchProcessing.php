<?php
namespace Hyper\EventBundle\Service;

use Hyper\Domain\Setting\Setting;

class RedshiftBatchProcessing
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
        $this->s3Bucket = $this->container->getParameter('amazon_s3_bucket_name');
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
    
    public function process() {
        $now = strtotime('-1 hour');
        $hourBasedPath = date('Y/m/d/H',$now);
        
        //$localFolder = '/var/www/html/projects/event_tracking/web/batch_processing_s3';
        //fetch from app list,date time
        //$prefix = 'bukalapak/2015/09/29/10/';
        $appNameMapping = array_unique($this->getLocalS3FolderMapping());
        //print_r($appNameMapping);
        /*
        $appNameMapping = array(
            'id1049249612' => 'raiderquests'
        );
        */
        $redshift = $this->container->get('redshift_service');
        $i = 0;
        $commitPoint = 300;
        $em=$this->container->get('doctrine')->getManager('pgsql');
        $em->getConnection()->beginTransaction(); // suspend auto-commit
        foreach ($appNameMapping as $appName) {
            //$s3LogPerHourPath = 'liputan6/2015/11/19/06/';
            $s3LogPerHourPath = $appName.'/'.$hourBasedPath.'/';
            $s3LogPerMinutePathList = $this->getS3folderList($s3LogPerHourPath);
            echo $s3LogPerHourPath;echo "\n";
            //var_dump($s3LogPerMinutePathList);echo "\n";
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
                    $this->s3->getObject(
                        array(
                            'Bucket' => $this->s3Bucket,
                            'Key' => $eventLogGzObject['Key'], 
                            'SaveAs' => $tmpFile
                        )
                    );
                    $logContent = $this->gzToArray($tmpFile);
                    unlink($tmpFile);
                    $metaData = array();
                    $metaData['s3_log_file'] = $eventLogGzObject['Key'];
                    //echo $metaData['s3_log_file']."\n";
                    try {
                        // currently just support Appsflyer
                        $providerId = 1;
                        $redshift->storeLogEventToRedshift($providerId,$logContent,$metaData);
                        echo "$i:::".$eventLogGzObject['Key']." ,\n";
                        if ($i%$commitPoint == 0 && $i!=0) {
                            $em->flush();
                            $em->getConnection()->commit();
                            echo "processed $i::: \n at ".date('d-m-Y H:i:s');
                            echo "begin new batch \n";
                            $em->getConnection()->beginTransaction();
                         }
                         $i++;
                    } catch (\Exception $ex) {
                        $this->log($ex);
                        echo "---".$ex->getMessage()."\n";
                        echo "while processing ".$eventLogGzObject['Key']."\n";
                        
                    }
                  
                }
                //commit transaction
                
            }
            
        }
        $em->flush();
        echo "final commit"."\n";
        $em->getConnection()->commit();
        echo "finish processing";
        return;
        
    }
    
    public function processV2($startOver = false) {
        if ($startOver == true) {
            $now = strtotime('-1 hour');
            $hourBasedPath = date('Y/m/d/H',$now);
            $next = strtotime('now');
            $this->setNextBatchPoint($next);
        } else {
            $current = $this->getNextBatchPoint();
           if($current  >strtotime('now')) {
                echo "----finished all process----"."\n";
               return;
           }
            //echo 'current '.$current."\n";
            $hourBasedPath = date('Y/m/d/H',$current);
            $next = $current + 3600;
            $this->setNextBatchPoint($next);
            //die;
        }
        
        
        //$localFolder = '/var/www/html/projects/event_tracking/web/batch_processing_s3';
        //fetch from app list,date time
        //$prefix = 'bukalapak/2015/09/29/10/';
        $appNameMapping = array_unique($this->getLocalS3FolderMapping());
        //print_r($appNameMapping);
        /*
        $appNameMapping = array(
            'id1049249612' => 'raiderquests'
        );
        */
        $redshift = $this->container->get('redshift_service');
        $i = 0;
        $commitPoint = 300;
        $em=$this->container->get('doctrine')->getManager('pgsql');
        $em->getConnection()->beginTransaction(); // suspend auto-commit
        foreach ($appNameMapping as $appName) {
            //$s3LogPerHourPath = 'liputan6/2015/11/19/06/';
            $s3LogPerHourPath = $appName.'/'.$hourBasedPath.'/';
            $s3LogPerMinutePathList = $this->getS3folderList($s3LogPerHourPath);
            echo $s3LogPerHourPath;echo "\n";
            //var_dump($s3LogPerMinutePathList);echo "\n";
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
                    $this->s3->getObject(
                        array(
                            'Bucket' => $this->s3Bucket,
                            'Key' => $eventLogGzObject['Key'], 
                            'SaveAs' => $tmpFile
                        )
                    );
                    $logContent = $this->gzToArray($tmpFile);
                    unlink($tmpFile);
                    $metaData = array();
                    $metaData['s3_log_file'] = $eventLogGzObject['Key'];
                    //echo $metaData['s3_log_file']."\n";
                    try {
                        // currently just support Appsflyer
                        $providerId = 1;
                        $redshift->storeLogEventToRedshift($providerId,$logContent,$metaData);
                        //echo "$i".$eventLogGzObject['Key']." ,\n";
                        if ($i%$commitPoint == 0 && $i!=0) {
                            $em->flush();
                            $em->getConnection()->commit();
                            echo "processed $i \n at ".date('d-m-Y H:i:s');
                            echo "begin new batch \n";
                            $em->getConnection()->beginTransaction();
                         }
                         $i++;
                    } catch (\Exception $ex) {
                        $this->log($ex);
                        echo "---".$ex->getMessage()."\n";
                        echo "while processing ".$eventLogGzObject['Key']."\n";
                        
                    }
                    unset($eventLogGzObject);
                }
                unset($eventLogGzObjectList);
                //commit transaction
                
            }
            unset($s3LogPerMinutePathList);
            
        }
        $em->flush();
        echo "final commit"."\n";
        $em->getConnection()->commit();
        // TODO handle next 1000 file from pending log
        
        echo "finish processing"."\n";
        
        echo "doing next process"."\n";
        $this->processV2(false);
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
    
    public function getNextBatchPoint() {
        $settingRepo = $this->container->get('setting_repository');
        $setting = $settingRepo->findOneByKey('next_processing_point');
        $current = null;
        //\Doctrine\Common\Util\Debug::dump($setting);
        if ($setting instanceof Setting) {
            $current = $setting->getValue();
        }
        unset($settingRepo);
        unset($setting);
        return $current;
        
    }
    
    public function setNextBatchPoint($point) {
        $settingRepo = $this->container->get('setting_repository');
        $setting = $settingRepo->findOneByKey('next_processing_point');
        if ($setting instanceof Setting) {
            $setting->setValue($point);
        } else {
            $setting = new Setting();
            $key = 'next_processing_point';
            $setting->setKey($key);
            $setting->setValue($point);
        }
        $settingRepo->save($setting);
        $settingRepo->completeTransaction();
        unset($settingRepo);
        unset($setting);
    }
    
}
