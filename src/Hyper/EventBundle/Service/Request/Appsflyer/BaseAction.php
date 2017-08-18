<?php
namespace Hyper\EventBundle\Service\Request\Appsflyer;

//Container
use Symfony\Component\DependencyInjection\ContainerInterface;

// Repositories
use Hyper\Domain\Identity\IdentityRepository;
use Hyper\Domain\Application\ApplicationRepository;
use Hyper\Domain\Device\DeviceRepository;
use Hyper\Domain\Device\IOSDeviceRepository;
use Hyper\Domain\Device\AndroidDeviceRepository;
use Hyper\Domain\Action\ActionRepository;

// Entities
use Hyper\Domain\Identity\Identity;
use Hyper\Domain\Application\Application;
use Hyper\Domain\Device\Device;
use Hyper\Domain\Device\IOSDevice;
use Hyper\Domain\Device\AndroidDevice;
use Hyper\Domain\Action\Action;

/**
 * Define a set of parameter that an install request from appsflyer postback provider must have
 */
final class BaseAction
{
    /**
     * @var ContainerInterface
     */
    protected $container; 
    
    /**
     * Repositories
     */
    protected $identityRepository;
    protected $applicationRepository;
    protected $deviceRepository;
    protected $iosDeviceRepository;
    protected $androidDeviceRepository;
    protected $actionRepository;
    
    /**
     * @var array
     */
    protected $content;
    
    /**
     * @var Device
     */
    protected $device;
    
    protected $deviceId;
    
    /**
     * @var Application
     */
    protected $application;
    
    /**
     * @var Identity
     */
    protected $identity;
    
    /**
     * @var Action
     */
    protected $action;
    
    protected $behaviourId;
    protected $actionType;
    protected $providerId;
    protected $happenedAt;
    protected $s3LogFile;
    
    public function __construct(
        ContainerInterface $container,
        IdentityRepository $identityRepository,
        ApplicationRepository $applicationRepository,
        DeviceRepository $deviceRepository,
        IOSDeviceRepository $iosDeviceRepository,
        AndroidDeviceRepository $androidDeviceRepository,
        ActionRepository $actionRepository
    ) {
        $this->container = $container;
        $this->identityRepository = $identityRepository;
        $this->applicationRepository = $applicationRepository;
        $this->deviceRepository = $deviceRepository;
        $this->iosDeviceRepository = $iosDeviceRepository;
        $this->androidDeviceRepository = $androidDeviceRepository;
        $this->actionRepository = $actionRepository;
        // TODO - implement monolog,serializer,stopwatch,validator
    }
    
    public function init(array $content,$actionType,$behaviourId,$providerId,$s3LogFile = null) {
        //echo "init base action service";
        $this->content = $content;
        $this->actionType = $actionType;
        $this->behaviourId = $behaviourId;
        $this->providerId = $providerId;
        if (isset($s3LogFile)) {
            $this->s3LogFile = $s3LogFile;
        }
    }
    
    public function getDeviceByIdentifier() {
        $content = $this->content;
        $deviceId = null;
        if (
            $content['platform'] == Device::ANDROID_PLATFORM_NAME
        ){
            $identifier = array();
            $identifier['android_id'] = ( !empty($content['android_id']) )?$content['android_id']:null;
            $identifier['advertising_id'] = (!empty($content['advertising_id']) )?$content['advertising_id']:null;
            $result = $this->androidDeviceRepository->getByIdentifier($identifier);
            if (isset($result['device_id'])) {
                $deviceId = $result['device_id'];
            }
            
        } elseif (
            $content['platform'] == Device::IOS_PLATFORM_NAME  
          ){
            $identifier = $content['idfa'];
            $result = $this->iosDeviceRepository->getByIdentifier($identifier);
            if (isset($result['device_id'])) {
                $deviceId = $result['device_id'];
            }
        }
        $this->deviceId = $deviceId;
        return $this->deviceId;
    }
    
    public function storeDevice() {
        $content = $this->content;
        /**
         * TODO - throw exception for invalid params
         */
         
        $deviceId = null;
        $content['idfv'] = (empty($content['idfv']))?'':$content['idfv'];
        // generate platform and device id base on platform
        if ($content['platform'] == Device::ANDROID_PLATFORM_NAME) {
            $platform = Device::ANDROID_PLATFORM_CODE;
            $advertisingId = (!empty($content['advertising_id']))?$content['advertising_id']:null;
            $androidId = (!empty($content['android_id']))?$content['android_id']:null;
            $deviceId = md5($androidId.$advertisingId);
        }
        if ($content['platform'] == Device::IOS_PLATFORM_NAME) {
            $platform = Device::IOS_PLATFORM_CODE;
            $deviceId = md5($content['idfa'].$content['idfv']);
        }
        
        $device = new Device($deviceId);
        $device->setPlatform($platform);
        $clickTime = strtotime($content['click_time']);
        $device->setClickTime($clickTime);
        $installTime = strtotime($content['install_time']);
        $device->setInstallTime($installTime);
        $device->setCountryCode($content['country_code']);
        $device->setCity($content['city']);
        $device->setIp($content['ip']);
        $device->setWifi($content['wifi']);
        $device->setLanguage($content['language']);
        if(isset($content['mac'])){
            $device->setMac($content['mac']);
        }
        if(isset($content['operator'])){
             $device->setOperator($content['operator']);
        }
        $device->setDeviceOsVersion($content['os_version']);
        $this->deviceRepository->save($device);
        if (
            $content['platform'] == Device::ANDROID_PLATFORM_NAME
        ){
           $androidDevice = new AndroidDevice();
           $androidDevice->setAdvertisingId($advertisingId);
           $androidDevice->setAndroidId($androidId);
           if(empty($content['imei'])){
               $content['imei'] = '';
           }
           $androidDevice->setImei($content['imei']);
           if(empty($content['device_brand'])){
               $content['device_brand'] = '';
           }
           $androidDevice->setDeviceBrand($content['device_brand']);
           if(empty($content['device_model'])){
               $content['device_model'] = '';
           }
           $androidDevice->setDeviceModel($content['device_model']);
           $androidDevice->setDevice($device);
           $this->androidDeviceRepository->save($androidDevice);
        }
        if (
            $content['platform'] == Device::IOS_PLATFORM_NAME
        ){
           $iosDevice = new IOSDevice();
           $iosDevice->setIdfa($content['idfa']);
           $iosDevice->setIdfv($content['idfv']);
           if(empty($content['device_name'])){
               $content['device_name'] = '';
           }
           $iosDevice->setDeviceName($content['device_name']);
           if(empty($content['device_type'])){
               $content['device_type'] = '';
           }
           $iosDevice->setDeviceType($content['device_type']);
           $iosDevice->setDevice($device);
           $this->iosDeviceRepository->save($iosDevice);
        }
        $this->deviceRepository->save($device);
        $this->deviceId = $device->getId();
        return $this->deviceId;
    }
    
    public function getIdentityByIdentifier() {
        // currently not implement
    }
    
    public function storeIdentity() {
        // currently not implement
    }
    
    public function getApplicationByIdentifier() {
        $content = $this->content;
        $identifier = array();
        $identifier['app_id'] = $content['app_id'];
        $identifier['app_name'] = $content['app_name'];
        $identifier['app_version'] = $content['app_version'];
        $application = $this->applicationRepository->getByIdentifier($identifier);
        $this->application = $application;
        return $this->application;
    }
    
    public function storeApplication(){
        $content = $this->content;
        if ($content['platform'] == Device::ANDROID_PLATFORM_NAME) {
            $platform = Device::ANDROID_PLATFORM_CODE; 
        } elseif ($content['platform'] == Device::IOS_PLATFORM_NAME) {
            $platform = Device::IOS_PLATFORM_CODE; 
        }
        $applicationId = md5($content['app_id'].$content['app_name'].$content['app_version']);
        $application = new Application($applicationId);
        $application->setAppId($content['app_id']);
        if(empty($content['app_name'])) {
            if($content['app_id'] == 'id1049249612') {
                $content['app_name'] = 'Raiders Quest';
            } else {
                throw new \Exception('invalid app_name');
            }
        }
        $application->setAppName($content['app_name']);
        if(empty($content['app_version'])) {
            throw new \Exception('invalid app_version');
        }
        $application->setAppVersion($content['app_version']);
        $application->setPlatform($platform);
        $application->setId($applicationId);
        $this->applicationRepository->save($application);
        $this->application = $application;
        return $this->application;
    }
    public function getActionByIdentifier() {
        $content = $this->content;

        $application = $this->application;
        $identifier = array();
        $identifier['device_id'] = $this->deviceId;
        $identifier['application_id'] = $application->getId();
        $identifier['behaviour_id'] = $this->behaviourId;
        $identifier['happened_at'] = strtotime($content['event_time']);
        $action = $this->actionRepository->getByIdentifier($identifier);
        $this->action = $action;
        return $this->action;
    }
    
    public function storeAction(){
        $em=$this->container->get('doctrine')->getEntityManager('pgsql');
        $content = $this->content;
        $happenedAt = strtotime($content['event_time']);
        $actionId = md5($this->deviceId.$this->application->getId().$this->behaviourId.$happenedAt);
        $action = new Action($actionId);
        $action->setBehaviourId($this->behaviourId);
        $action->setActionType($this->actionType);
        $action->setProviderId($this->providerId);
        $action->setHappenedAt($happenedAt);
        $action->setApplication($this->application);
        $action->setAppId($this->application->getAppId());
        $action->setDevice($em->getReference('Hyper\Domain\Device\Device', $this->deviceId));
        if(!empty($this->s3LogFile)){
            $action->setS3LogFile($this->s3LogFile);
        }
        $this->actionRepository->save($action);
        $this->action = $action;
        return $this->action;
    }

    
}
