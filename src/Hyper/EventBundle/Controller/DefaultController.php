<?php

namespace Hyper\EventBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\HttpFoundation\File\File;
use Hyper\EventBundle\Document\Person;
use Hyper\EventBundle\Document\Transaction;

class DefaultController extends Controller
{
    public function indexAction(Request $request)
    {
       //echo "start to work with postback"."<hr/>";
        $isPostWithJsonBody = $this->isPostWithJsonBody($request);
        if ($isPostWithJsonBody) {
            $content= $this->getValidContent($request);
            if($content) {
                if($this->isPurchaseEvent($content)) {
                    //store to mongo
                    $person = $this->getPersonDocumentByDeviceId($content);
                    //var_dump($content);
                    if(!$person instanceof Person){
                        //store new person
                        $person = $this->storePersonDocument($content);
                    }
                    //store transaction with hypid from person
                    $transaction = $this->storeTransactionDocument($person,$content);
                }
                // store to S3
                $fileName = $this->storeEventS3($request);
                return new Response(
                    json_encode(
                        array(
                            'file_name' => $fileName,
                            'person' => $person,
                            'transaction' => $transaction
                        )
                    )
                );
            } else {
                return  new Response(json_encode(array('content'=>$content)));
            }
            
        } else {
            return new Response(json_encode(array('isPostWithJsonBody'=>$isPostWithJsonBody)));
        }
        
        
    }
    
    protected function isPostWithJsonBody(Request $request){
        $contentType = $request->headers->get('Content-Type');
        $method = $request->getMethod();
        $logger = $this->get('logger');
        $logger->info('result contentType'.$contentType);
        $logger->info('result method'.$method);
        return ($contentType == 'application/json' && $method == 'POST');
    }
    
    protected function isPurchaseEvent($content){
        return (!empty($content['event_name']) && $content['event_name']=="purchase");
    }
    
    protected function getValidContent(Request $request){
        $rawJsonContent = $request->getContent();
        $content = json_decode($rawJsonContent,true);
        $logger = $this->get('logger');
        //$logger->info('result content '.$content);
        $logger->info('result rawJsonContent '.$rawJsonContent);
        if(is_array($content) && !empty($content)){
            $logger->info('result valid content ');
            return $content;
        }else{
            return null;
        }
    }
    
    /*
    * $deviceId appsflyer_device_id
    * return null|array
    */
    protected function getPersonDocumentByDeviceId($content){
        $product = null;
        if(!empty($content['android_id']) && !empty($content['imei'])){
            $product = $this->get('doctrine_mongodb')
            ->getRepository('HyperEventBundle:Person')
            ->findOneBy(
                array(
                    'android_id'=>$content['android_id'],
                    'imei'=>$content['imei'],
                )
            );
        }
        if(!empty($content['idfa']) && !empty($content['idfv'])){
            $product = $this->get('doctrine_mongodb')
            ->getRepository('HyperEventBundle:Person')
            ->findOneBy(
                array(
                    'idfa'=>$content['idfa'],
                    'idfv'=>$content['idfv']
                )
            );
        }
        
        return $product;

    }

    protected function storePersonDocument($content){
        $person = new Person();
        $uniqueId = uniqid('person_');
        $person->setHypid($uniqueId);
        $person->setAppId($content['app_id']);
        $person->setPlatform($content['platform']);
        $person->setClickTime($content['click_time']);
        $person->setInstallTime($content['install_time']);
        $person->setCountryCode($content['country_code']);
        $person->setCity($content['city']);
        $person->setIp($content['ip']);
        $person->setWifi($content['wifi']);
        $person->setLanguage($content['language']);
        $person->setOperator($content['operator']);
        $person->setCustomerUserId($content['customer_user_id']);
        $person->setAdvertisingId(
            !empty($content['advertising_id'])?$content['advertising_id']:''
        );
        $person->setAndroidId(
            !empty($content['android_id'])?$content['android_id']:''
        );
        $person->setImei($content['imei']);
        $person->setIdfa(
            !empty($content['idfa'])?$content['idfa']:''
        );
        $person->setIdfv(
            !empty($content['idfv'])?$content['idfa']:''
        );
        $person->setMac(
            !empty($content['mac'])?$content['mac']:''
        );
        $person->setDeviceBrand(
            !empty($content['device_brand'])?$content['device_brand']:''
        );
        $person->setDeviceModel(
            !empty($content['device_model'])?$content['device_model']:''
        );
        $person->setDeviceName(
            !empty($content['device_name'])?$content['device_name']:''
        );
        $person->setDeviceType(
             !empty($content['device_type'])?$content['device_type']:''
        );
        $person->setOsVersion($content['os_version']);
        $person->setAppVersion($content['app_version']);
        
        $dm = $this->get('doctrine_mongodb')->getManager();
        $dm->persist($person);
        $dm->flush();
        return $person;
        
    }
    
    protected function storeTransactionDocument(Person $person,$content){
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
        $dm = $this->get('doctrine_mongodb')->getManager();
        $dm->persist($transaction);
        $dm->flush();
        return $transaction;
        
    }
    
    /**
     * @return Acme\StorageBundle\Uploader\PhotoUploader
     */
    protected function getEventLogUploader()
    {
        return $this->get('hyper_event.event_log_uploader');
    }
    
    protected function storeEventS3(Request $request){
        $amazonBaseURL = $this->container->getParameter('hyper_event.amazon_s3.base_url');
        $rootDir = $this->get('kernel')->getRootDir();// '/var/www/html/projects/event_tracking/app'
        $rawLogDir = $rootDir. '/../web/raw_event';
         
        $fs = new Filesystem();
        $content = $request->getContent();
        
        $path = $rawLogDir.'/'.'log_'.'an_app_id'.'_@_'.time();
        $pathTxT = $path.'txt';
        $pathJson = $path.'json';
        $pathGz = $path.'gz';
        $fs->dumpFile($pathJson,$content);
        
        $file = new File($pathJson);
       
        
        $filePathName = $file->getPathname();
        $gzFilePathName = $pathGz;
        file_put_contents($gzFilePathName, gzencode( file_get_contents($filePathName),9));
        $gzFile = new File($gzFilePathName);
        
        $eventUploader = $this->getEventLogUploader();
        $fileName = $amazonBaseURL.'/'.$eventUploader->uploadFromLocal($gzFile);
        
        //echo $fileName."<hr/><br/>";
        $fs->remove($pathJson);
        return $fileName;
    }
    
     public function storeDocumentAction(Request $request)
    {
        echo "working on storing document";
        $sth = array('empty_string'=>'');
        $person = new Person();
        $person->setAppId('qwerty_gs');
        $person->setAdvertisingId($sth['empty_string']);
        $dm = $this->get('doctrine_mongodb')->getManager();
        $dm->persist($person);
        $dm->flush();
        return new Response('Created product id '.$person->getId());
        //die;
    }
}
