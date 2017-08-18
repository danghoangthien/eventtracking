<?php
namespace Hyper\EventBundle\Service\Request\Abstraction;

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


abstract class AAction
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
        // TODO - implement monolog,serializer,stopwatch,validator
        
        $this->identityRepository = $identityRepository;
        $this->applicationRepository = $applicationRepository;
        $this->deviceRepository = $deviceRepository;
        $this->iosDeviceRepository = $iosDeviceRepository;
        $this->androidDeviceRepository = $androidDeviceRepository;
        $this->actionRepository = $actionRepository;
        
    }
    
    public abstract function init(array $content,array $metaData);
    
    public function prerequisite() {
        // currently not supporting identity
        // try to turn event_value to array
        if (!is_array($this->content['event_value']) && !is_bool($this->content['event_value'])) {
            $this->content['event_value'] = json_decode($this->content['event_value'],true);
        }
        $newDevice = false;
        $newApplication = false;
        $deviceId = $this->getDeviceByIdentifier();
        if (!empty($deviceId)) {
            $this->deviceId = $deviceId;
        } else {
            $this->deviceId = $this->storeDevice();
            $newDevice = true;
        }
        $application = $this->getApplicationByIdentifier();
        if ($application instanceof Application) {
            $this->application = $application;
        } else {
            $this->application = $this->storeApplication();
            $newApplication = true;
        }
        //echo "new device?";var_dump($newDevice);echo "<hr/>";
        //die;
        if($newDevice == false && $newApplication == false){
            $action = $this->getActionByIdentifier();
            if($action instanceof Action){
                throw new \Exception("Duplicate Action");
            }
        }
        
    }
    
    public abstract function execute();
    
    protected abstract function getDeviceByIdentifier();
    
    protected abstract function storeDevice();
    
    protected abstract function getIdentityByIdentifier();
    
    protected abstract function storeIdentity();
    
    protected abstract function getApplicationByIdentifier();
    
    protected abstract function storeApplication();
    
    protected abstract function getActionByIdentifier();
    
    protected abstract function storeAction();
    
    protected abstract function completeTransaction();
    
    protected abstract function closeConnection();
    
    protected abstract function onSuccess();    
}
