<?php
namespace Hyper\EventBundle\Service\Request\Appsflyer;

// Abstraction
use Hyper\EventBundle\Service\Request\Abstraction\ASearch;

// DT Repository
use Hyper\DomainBundle\Repository\Identity\DTIdentityRepository;
use Hyper\DomainBundle\Repository\Application\DTApplicationRepository;
use Hyper\DomainBundle\Repository\Device\DTDeviceRepository;
use Hyper\DomainBundle\Repository\Device\DTIOSDeviceRepository;
use Hyper\DomainBundle\Repository\Device\DTAndroidDeviceRepository;
use Hyper\DomainBundle\Repository\Action\DTActionRepository;
use Hyper\DomainBundle\Repository\Action\DTsearchActionRepository;

// Entities
use Hyper\Domain\Identity\Identity;
use Hyper\Domain\Application\Application;
use Hyper\Domain\Device\Device;
use Hyper\Domain\Device\IOSDevice;
use Hyper\Domain\Device\AndroidDevice;
use Hyper\Domain\Action\Action;
use Hyper\Domain\Action\SearchAction;

/**
 * Define a set of parameter that an search request from appsflyer postback provider must have
 */
class Search extends ASearch
{
    public $baseActionService;
    
    public function init(array $content,array $metaData = array()){
        
        $this->content = $content;
        
        $this->actionType = Action::ACTION_TYPES['IN_APP_EVENT_ACTION_TYPE'];
        $this->behaviourId = Action::BEHAVIOURS['SEARCH_BEHAVIOUR_ID'];
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
    
    protected function storeSearchAction(){

        if (isset($this->content['event_value']['af_search_string'])) {
            $string = $this->content['event_value']['af_search_string'];
        } else {
                throw new \Exception("invalid search string value");
        }
        
        $searchAction = new SearchAction();
        $action = $this->action;
        $searchAction->setAction($action);
        $searchAction->setDeviceId($this->deviceId);
        $searchAction->setAppId($this->application->getAppId());
        $searchAction->setApplicationId($this->application->getId());
        $searchAction->setSearchString($string);
        if (isset($this->content['event_value']['af_content_type'])) {
            $searchLogContentType = $this->content['event_value']['af_content_type'];
            $searchAction->setSearchLogContentType($searchLogContentType);     
        }
        $searchAction->setSearchedTime($this->action->getHappenedAt());
        $this->searchActionRepository->save($searchAction);
        return $searchAction;
    }
    
    protected function completeTransaction(){
        $this->searchActionRepository->completeTransaction();
    }
    
    protected function closeConnection(){
        $this->searchActionRepository->closeConnection();
    }
    
    protected function onSuccess(){
        
    }
    
}
