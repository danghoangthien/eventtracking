<?php

namespace Hyper\EventBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\HttpFoundation\File\File;
use Hyper\EventBundle\Document\Person;
use Hyper\EventBundle\Document\Transaction;
use Hyper\EventBundle\Annotations\CsvMetaReader;
use Hyper\EventBundle\Service\EventProcess;


class StorageController extends Controller
{
    /**
    * @param ContainerInterface $container
    */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    
    public function indexAction(Request $request)
    {
        //return new Response('This is postback version 2<hr/>');
        $content = array();
        $isPostWithJsonBody = $this->isPostWithJsonBody($request);
        $isPostWithCsv = $this->isPostWithCsv($request);
        if ($isPostWithJsonBody) {
            $content = $this->getValidContent($request);
            if(!empty($content)){
                $filePath = $this->storeEventS3($request);
                $this->storeEventMemCached($content);
                if(!empty($filePath)){
                    return new Response(
                        json_encode(
                            array(
                                'file_path' => $filePath
                            )
                        )
                    );
                }
            }
            
        }
        elseif ($isPostWithCsv) {
            $contents = $this->parseCSVContent($request);
            //print_r($content);//die;
            if (!empty($contents) && is_array($contents)) {
                $this->storeEventMongoDB($contents);
                return new Response(
                    json_encode(
                        array(
                            'status.'=>'success',
                            'code'=>'200',
                            'message'=> 'success',
                            'upload_tool'=>'http://ec2-52-26-255-227.us-west-2.compute.amazonaws.com/projects/tool/csv_upload.php'
                        )
                    )
                );
            }
        }
        else {
            //improve with Rest API standard later
            return new Response(
                json_encode(
                    array(
                        'error'=>'bad request',
                        'code'=>'400'
                    )
                )
            );
        }
        

        
        
        
    }
    
    protected function isPostWithJsonBody(Request $request)
    {
        $contentType = $request->headers->get('Content-Type');
        $method = $request->getMethod();
        //$logger = $this->get('logger');
        //$logger->info('result contentType'.$contentType);
        //$logger->info('result method'.$method);
        return ($contentType == 'application/json' && $method == 'POST');
    }
    
    protected function isPostWithCsv(Request $request)
    {
        $method = $request->getMethod();
        $csv = $request->files->get('csv');
        return (!empty($csv) && $method == 'POST');
        return false;
    }
    protected function parseCsvContent(Request $request)
    {
        $csv = $request->files->get('csv');
        $csvMetaReader = new CsvMetaReader();
        $personCsvMongoDbIndex = $csvMetaReader->csvMongoDbIndex('\Hyper\EventBundle\Document\Person');
        $transactionCsvMongoDbIndex = $csvMetaReader->csvMongoDbIndex('\Hyper\EventBundle\Document\Transaction');
        $csvMongoDbIndex = array_merge($personCsvMongoDbIndex,$transactionCsvMongoDbIndex);
        $content = array();
        if (($handle = fopen($csv->getRealPath(), "r")) !== false) {
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
                }
                $i++;
            }
        }
        return $content;
    }
    
    protected function isPurchaseEvent($content)
    {
        return (!empty($content['event_name']) && strpos($content['event_name'],'purchase')!==false);
    }
    
    protected function getValidContent(Request $request,$returnType ='array')
    {
        $rawJsonContent = $request->getContent();
        if ($returnType == 'json') {
            return $rawJsonContent;
        } else {
            $content = json_decode($rawJsonContent,true);
            //$logger = $this->get('logger');
            //$logger->info('result content '.$content);
            //$logger->info('result rawJsonContent '.$rawJsonContent);
            if(is_array($content) && !empty($content)){
                //$logger->info('result valid content ');
                return $content;
            }else{
                return null;
            }
        }
        
    }
    
    /*
    * return null|instance of Person
    */
    protected function getPersonDocumentByDeviceId($content)
    {
        $person = null;
        $platform = strtolower($content['platform']);
        // is Android
        if ($platform == 'android') {
            $searchConditions =array();
            if (!empty($content['android_id'])) {
                $searchConditions['android_id'] = $content['android_id'];
            }
            if (!empty($content['imei'])) {
                $searchConditions['imei'] = $content['imei'];
            }
            $person = $this->get('doctrine_mongodb')
            ->getRepository('HyperEventBundle:Person')
            ->findOneBy($searchConditions);
        }
        elseif ($platform == 'ios') {
            $searchConditions =array();
            if (!empty($content['idfa'])) {
                $searchConditions['idfa'] = $content['idfa'];
            }
            if (!empty($content['idfv'])) {
                $searchConditions['idfv'] = $content['idfv'];
            }
            $person = $this->get('doctrine_mongodb')
            ->getRepository('HyperEventBundle:Person')
            ->findOneBy($searchConditions);
        }
        
        return $person;

    }
    
    /*
    * return null|instance of Transaction
    */
    protected function getPersonTransactionDocumentByTime($hypid,$eventTime)
    {
        $transaction = null;
        $transaction = $this->get('doctrine_mongodb')
            ->getRepository('HyperEventBundle:Transaction')
            ->findOneBy(
                array(
                    'hypid'=>$hypid,
                    'event_time'=>$eventTime
                )
            );
        return $transaction;
    }
    /*
    * Store many event to Document
    */
    protected function storeEventMongoDB(array $contents)
    {
        foreach ($contents as $content) {
            if ($this->isPurchaseEvent($content)) {
                //store to mongo
                $person = $this->getPersonDocumentByDeviceId($content);
                /*
                echo "person:";
                var_dump($person);
                echo "<hr/>";
                continue;
                */
                if (!$person instanceof Person) {
                    //store new person
                    $person = $this->storePersonDocument($content);
                }
                $transaction = $this->getPersonTransactionDocumentByTime($person->getHypid(),$content['event_time']);
                if (!$transaction instanceof Transaction) {
                    //store transaction with hypid from person
                    $transaction = $this->storeTransactionDocument($person,$content);
                }
            }
        }
    }
        
    protected function storePersonDocument($content)
    {
        $person = new Person();
        $uniqueId = uniqid('person_');
        $person->setHypid($uniqueId);
        $person->setAppId($content['app_id']);
        $person->setPlatform($content['platform']);
        $person->setClickTime(
            !empty($content['click_time'])?$content['click_time']:null
        );
        $person->setInstallTime(
            !empty($content['install_time'])?$content['install_time']:null
        );
        $person->setCountryCode(
            !empty($content['country_code'])?$content['country_code']:null
        );
        $person->setCity(
            !empty($content['city'])?$content['city']:null
        );
        $person->setIp(
            !empty($content['ip'])?$content['ip']:null
        );
        $person->setWifi(
            !empty($content['wifi'])?$content['wifi']:null
        );
        $person->setLanguage(
            !empty($content['language'])?$content['language']:null
        );
        $person->setOperator(
            !empty($content['operator'])?$content['operator']:null
        );
        $person->setAdvertisingId(
            !empty($content['advertising_id'])?$content['advertising_id']:null
        );
        $person->setAndroidId(
            !empty($content['android_id'])?$content['android_id']:null
        );
        $person->setImei(
            !empty($content['imei'])?$content['imei']:null
        );
        $person->setIdfa(
            !empty($content['idfa'])?$content['idfa']:null
        );
        $person->setIdfv(
            !empty($content['idfv'])?$content['idfv']:null
        );
        $person->setMac(
            !empty($content['mac'])?$content['mac']:null
        );
        $person->setDeviceBrand(
            !empty($content['device_brand'])?$content['device_brand']:null
        );
        $person->setDeviceModel(
            !empty($content['device_model'])?$content['device_model']:null
        );
        $person->setDeviceName(
            !empty($content['device_name'])?$content['device_name']:null
        );
        $person->setDeviceType(
            !empty($content['device_type'])?$content['device_type']:null
        );
        $person->setOsVersion(
            !empty($content['os_version'])?$content['os_version']:null
        );
        $person->setAppVersion(
            !empty($content['app_version'])?$content['app_version']:null
        );
        $person->setPersonName(
             !empty($content['person_name'])?$content['person_name']:null
        );
        $person->setPersonEmail(
             !empty($content['person_email'])?$content['person_email']:null
        );
        $person->setFacebookId(
             !empty($content['facebook_id'])?$content['facebook_id']:null
        );
        
        $dm = $this->get('doctrine_mongodb')->getManager();
        $dm->persist($person);
        $dm->flush();
        return $person;
        
    }
    
    protected function storeTransactionDocument(Person $person,$content)
    {
        $transaction = new Transaction();
        $uniqueId = uniqid('trans_');
        $transaction->setHytid($uniqueId);
        $hypid = $person->getHypid();
        $transaction->setHypid($hypid);
        $transaction->setEventTime($content['event_time']);
        $transaction->setEventName($content['event_name']);
        $transaction->setEventType($content['event_type']);
        $transaction->setEventValue($content['event_value']);
        $transaction->setcurrency($content['currency']);
        $transaction->setProductName(
            !empty($content['product_name'])?$content['product_name']:null
        );
        $transaction->setProductCategory(
            !empty($content['product_category'])?$content['product_category']:null
        );
        $dm = $this->get('doctrine_mongodb')->getManager();
        $dm->persist($transaction);
        $dm->flush();
        return $transaction;
    }
    
    /**
     * @return Hyper\EventBundle\Upload\EventLogUploader
     */
    protected function getEventLogUploader()
    {
        return $this->get('hyper_event.event_log_uploader');
    }
    
    protected function storeEventS3(Request $request){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $amazonBaseURL = $this->container->getParameter('hyper_event.amazon_s3.base_url');
        $rootDir = $this->get('kernel')->getRootDir();// '/var/www/html/projects/event_tracking/app'
        $rawLogDir = $rootDir. '/../web/raw_event';
         
        $fs = new Filesystem();
        $rawContent = $this->getValidContent($request,'json');
        $content = $this->getValidContent($request,'array');
        $appId=$content['app_id'];
        $path = $rawLogDir.'/'.'log_'.$appId.'_'.time();
        $pathTxT = $path.'txt';
        $pathJson = $path.'.json';
        $pathGz = $path.'.gz';
        $fs->dumpFile($pathJson,$rawContent);
        
        $file = new File($pathJson);
       
        
        $filePathName = $file->getPathname();
        $gzFilePathName = $pathGz;
        file_put_contents($gzFilePathName, gzencode( file_get_contents($filePathName),9));
        chmod($gzFilePathName, 0664);
        $gzFile = new File($gzFilePathName);
        
        $eventUploader = $this->getEventLogUploader();
        $fileName = $eventUploader->uploadFromLocal($gzFile);
        if (!empty($fileName)) {
            $filePath = $amazonBaseURL.'/'.$fileName;
        
            //echo $fileName."<hr/>";
            $fs->remove($pathGz);
            $fs->remove($pathJson);
            return $filePath;
        } else {
            return null;
        }
        
    }
    
    protected function storeEventMemCached($content)
    {
        $appId = $content['app_id'];
        $singleEventKey = 'hyperevent_'.$appId.'_'.time();
        $this->get('memcache.default')->set($singleEventKey, $content, 0 , 0);
        //also store key list
        $appEventMetaKey = 'app_events';
        $keyList = $this->get('memcache.default')->get($appEventMetaKey);
        if (empty($keyList)) {
           $keyList = array();
        }
        $keyList[]=$singleEventKey;
        $this->get('memcache.default')->set($appEventMetaKey, $keyList, 0 , 0);
    }
    
    public function pushMemcachedToMongoDB($hoursAgo='-1'){
        //print_r();die;
         $appEventMetaKey = 'app_events';
        $appEventKeys = $this->get('memcache.default')->get('app_events');
        $hoursAgo = strtotime('-'.$hoursAgo.' hours');
        $currentTimeStamp = time();
        $appEventValues =array();
        $unsetKeys = array();
        foreach ($appEventKeys as $key) {
            $keyParts = explode('_',$key);
            $eventTimeStamp = end($keyParts);
            if( $hoursAgo<=$eventTimeStamp && $eventTimeStamp<=$currentTimeStamp){
                $appEventValues[] = $this->get('memcache.default')->get($key);
                $unsetKeys[] = $key;
            }
        }
        $this->storeEventMongoDB($appEventValues);
        foreach ($unsetKeys as $unsetKey){
            $this->get('memcache.default')->delete($unsetKey);
            unset($appEventKeys[$unsetKey]);
             $this->get('memcache.default')->set($appEventMetaKey, $appEventKeys, 0 , 0);
        }
    }
    
    public function test()
    {
        echo "hello moto";
    }
    
     public function testMemcachedAction(Request $request)
    {
        //echo "working on storing and getting memcached";
        //$this->get('memcache.default')->delete('app_events');
        /*
        $giaosu = array(
            0 => array('name'=>'Thien Dang','dob'=>'14-03-1983')    
        );
        $this->get('memcache.default')->set('giaosu', $giaosu, 0 , 0);
        */
        $eventProcessingService = $this->get('hyper_event.event_process');
        die;
        $appEventKeys = $this->get('memcache.default')->get('app_events');
        $appEventValues =array();
        if(!empty($appEventKeys)){
            foreach ($appEventKeys as $key) {
                $appEventValues[] = $this->get('memcache.default')->get($key);
            }
            var_dump($appEventValues);
            return  new Response(
                json_encode(
                    array(
                        'app event keys'=>$appEventKeys,
                        'values'=>$appEventValues
                    )
                )
            );
        }
        else{
            return  new Response('no content');
        }
        
    }
}
