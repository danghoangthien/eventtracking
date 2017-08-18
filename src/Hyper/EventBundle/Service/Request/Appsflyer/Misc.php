<?php
namespace Hyper\EventBundle\Service\Request\Appsflyer;

// Abstraction
use Hyper\EventBundle\Service\Request\Abstraction\AMisc;

// DT Repository
use Hyper\DomainBundle\Repository\Identity\DTIdentityRepository;
use Hyper\DomainBundle\Repository\Application\DTApplicationRepository;
use Hyper\DomainBundle\Repository\Device\DTDeviceRepository;
use Hyper\DomainBundle\Repository\Device\DTIOSDeviceRepository;
use Hyper\DomainBundle\Repository\Device\DTAndroidDeviceRepository;
use Hyper\DomainBundle\Repository\Action\DTActionRepository;
use Hyper\DomainBundle\Repository\Action\DTMiscActionRepository;

// Entities
use Hyper\Domain\Identity\Identity;
use Hyper\Domain\Application\Application;
use Hyper\Domain\Device\Device;
use Hyper\Domain\Device\IOSDevice;
use Hyper\Domain\Device\AndroidDevice;
use Hyper\Domain\Action\Action;
use Hyper\Domain\Action\MiscAction;

/**
 * Define a set of parameter that an launch request from appsflyer postback provider must have
 */
class Misc extends AMisc
{
    public $baseActionService;
    
    public function init(array $content,array $metaData = array()){
        
        $this->content = $content;
        
        $this->actionType = Action::ACTION_TYPES['IN_APP_EVENT_ACTION_TYPE'];
        $this->behaviourId = $metaData['behaviour_id'];
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
    
    protected function storeMiscAction(){
        $miscAcion = new MiscAction();
        $action = $this->action;
        $miscAcion->setAction($action);
        $miscAcion->setDeviceId($this->deviceId);
        $miscAcion->setAppId($this->application->getAppId());
        $miscAcion->setApplicationId($this->application->getId());
        $miscAcion->setEventName($this->content['event_name']);
        $miscAcion->setEventValue($this->content['event_value']);//serialized
        $miscAcion->setEventTime($this->action->getHappenedAt());
        $this->miscActionRepository->save($miscAcion);
        return $miscAcion;
    }
    
    protected function completeTransaction(){
        $this->miscActionRepository->completeTransaction();
    }
    
    protected function closeConnection(){
        $this->miscActionRepository->closeConnection();
    }
    
    protected function onSuccess(){
        
    }
    
}
