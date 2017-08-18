<?php
namespace Hyper\EventBundle\Service\Request\Appsflyer;

// Abstraction
use Hyper\EventBundle\Service\Request\Abstraction\ALaunch;

// DT Repository
use Hyper\DomainBundle\Repository\Identity\DTIdentityRepository;
use Hyper\DomainBundle\Repository\Application\DTApplicationRepository;
use Hyper\DomainBundle\Repository\Device\DTDeviceRepository;
use Hyper\DomainBundle\Repository\Device\DTIOSDeviceRepository;
use Hyper\DomainBundle\Repository\Device\DTAndroidDeviceRepository;
use Hyper\DomainBundle\Repository\Action\DTActionRepository;
use Hyper\DomainBundle\Repository\Action\DTLaunchActionRepository;

// Entities
use Hyper\Domain\Identity\Identity;
use Hyper\Domain\Application\Application;
use Hyper\Domain\Device\Device;
use Hyper\Domain\Device\IOSDevice;
use Hyper\Domain\Device\AndroidDevice;
use Hyper\Domain\Action\Action;
use Hyper\Domain\Action\LaunchAction;

/**
 * Define a set of parameter that an launch request from appsflyer postback provider must have
 */
class Launch extends ALaunch
{
    public $baseActionService;
    
    public function init(array $content,array $metaData = array()){
        
        $this->content = $content;
        
        $this->actionType = Action::ACTION_TYPES['IN_APP_EVENT_ACTION_TYPE'];
        $this->behaviourId = Action::BEHAVIOURS['LAUNCH_BEHAVIOUR_ID'];
        $this->providerId = 1;
        
        if (isset($metaData['s3_log_file'])) {
            $this->s3LogFile = $metaData['s3_log_file'];
        }
        
        $this->baseActionService =$this->container->get('appsflyer_base_action_service');
        $this->baseActionService->init($this->content,$this->actionType,$this->behaviourId,$this->providerId,$this->s3LogFile);

    }
    
    protected function getDeviceByIdentifier() {
        return $this->baseActionService->getDeviceByIdentifier();
    }
    
    protected function storeDevice() {
        return $this->baseActionService->storeDevice();
    }
    
    protected function getIdentityByIdentifier() {
        // currently not implement
    }
    
    protected function storeIdentity() {
        // currently not implement
    }
    
    protected function getApplicationByIdentifier() {
        
        return $this->baseActionService->getApplicationByIdentifier();
    }
    
    protected function storeApplication(){
        return $this->baseActionService->storeApplication();
    }
    protected function getActionByIdentifier() {
        return $this->baseActionService->getActionByIdentifier();
    }
    
    protected function storeAction() {
        return $this->baseActionService->storeAction();
    }
    
    protected function storeLaunchAction(){
        $launchAcion = new LaunchAction();
        $action = $this->action;
        $launchAcion->setAction($action);
        $launchAcion->setDeviceId($this->deviceId);
        $launchAcion->setAppId($this->application->getAppId());
        $launchAcion->setApplicationId($this->application->getId());
        $launchAcion->setLaunchedTime($this->action->getHappenedAt());
        $this->launchActionRepository->save($launchAcion);
        return $launchAcion;
    }
    
    protected function completeTransaction(){
        $this->launchActionRepository->completeTransaction();
    }
    
    protected function closeConnection(){
        $this->launchActionRepository->closeConnection();
    }
    
    protected function onSuccess(){
        
    }
    
}
