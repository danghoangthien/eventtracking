<?php
namespace Hyper\EventBundle\Service\Request\Appsflyer;

// Abstraction
use Hyper\EventBundle\Service\Request\Abstraction\AInstall;

// DT Repository
use Hyper\DomainBundle\Repository\Identity\DTIdentityRepository;
use Hyper\DomainBundle\Repository\Application\DTApplicationRepository;
use Hyper\DomainBundle\Repository\Device\DTDeviceRepository;
use Hyper\DomainBundle\Repository\Device\DTIOSDeviceRepository;
use Hyper\DomainBundle\Repository\Device\DTAndroidDeviceRepository;
use Hyper\DomainBundle\Repository\Action\DTActionRepository;
use Hyper\DomainBundle\Repository\Action\DTInstallActionRepository;

// Entities
use Hyper\Domain\Identity\Identity;
use Hyper\Domain\Application\Application;
use Hyper\Domain\Device\Device;
use Hyper\Domain\Device\IOSDevice;
use Hyper\Domain\Device\AndroidDevice;
use Hyper\Domain\Action\Action;
use Hyper\Domain\Action\InstallAction;

/**
 * Define a set of parameter that an install request from appsflyer postback provider must have
 */
class Install extends AInstall
{
    public $baseActionService;
    
    public function init(array $content,array $metaData = array()){
        
        $this->content = $content;
        
        $this->actionType = Action::ACTION_TYPES['INSTALL_ACTION_TYPE'];
        $this->behaviourId = Action::BEHAVIOURS['INSTALL_BEHAVIOUR_ID'];
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
    
    protected function storeInstallAction(){
        $installAction = new InstallAction();
        $action = $this->action;
        $installAction->setAction($action);
        $installAction->setDeviceId($this->deviceId);
        $installAction->setAppId($this->application->getAppId());
        $installAction->setApplicationId($this->application->getId());
        $installAction->setInstalledTime(strtotime($this->content['install_time']));
        $this->installActionRepository->save($installAction);
        return $installAction;
    }
    
    protected function completeTransaction(){
        $this->installActionRepository->completeTransaction();
    }
    
    protected function closeConnection(){
        $this->installActionRepository->closeConnection();
    }
    
    protected function onSuccess(){
        
    }
    
}
