<?php
namespace Hyper\EventBundle\Service\Request\Appsflyer;

// Abstraction
use Hyper\EventBundle\Service\Request\Abstraction\AShareContent;
use Hyper\EventBundle\Service\Request\Appsflyer\Base;

// DT Repository
use Hyper\DomainBundle\Repository\Identity\DTIdentityRepository;
use Hyper\DomainBundle\Repository\Application\DTApplicationRepository;
use Hyper\DomainBundle\Repository\Device\DTDeviceRepository;
use Hyper\DomainBundle\Repository\Device\DTIOSDeviceRepository;
use Hyper\DomainBundle\Repository\Device\DTAndroidDeviceRepository;
use Hyper\DomainBundle\Repository\Action\DTActionRepository;
use Hyper\DomainBundle\Repository\Action\DTShareContentActionRepository;
use Hyper\DomainBundle\Repository\Content\DTInCategoryContentRepository;
use Hyper\DomainBundle\Repository\Content\DTContentRepository;
use Hyper\DomainBundle\Repository\Category\DTCategoryRepository;



// Entities
use Hyper\Domain\Identity\Identity;
use Hyper\Domain\Application\Application;
use Hyper\Domain\Device\Device;
use Hyper\Domain\Device\IOSDevice;
use Hyper\Domain\Device\AndroidDevice;
use Hyper\Domain\Action\Action;
use Hyper\Domain\Action\ShareContentAction;
use Hyper\Domain\Content\Content;
use Hyper\Domain\Content\InCategoryContent;
use Hyper\Domain\Category\Category;

/**
 * Define a set of parameter that an install request from appsflyer postback provider must have
 */
class ShareContent extends AShareContent
{
    public $baseActionService;
    
    public function init(array $content,array $metaData = array()) {
        //echo "init share content service";
        $this->content = $content;
        
        $this->actionType = Action::ACTION_TYPES['IN_APP_EVENT_ACTION_TYPE'];
        $this->behaviourId = Action::BEHAVIOURS['SHARE_CONTENT_BEHAVIOUR_ID'];
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
    
    protected function storeShareContentAction() {
        $shareContentAction = new ShareContentAction();
        $action = $this->action;
        $shareContentAction->setAction($action);
        $shareContentAction->setDeviceId($this->deviceId);
        $shareContentAction->setApplicationId($this->application->getId());
        $shareContentAction->setAppId($this->application->getAppId());
        $shareContentAction->setSharedTime($this->action->getHappenedAt());
        
        if(!empty($this->shareMap)){
            $shareApp = (isset($this->shareMap['share_app']))?$this->shareMap['share_app']:'';
            $shareContentAction->setSharedApp($shareApp);
            if($this->category instanceof Category){
                $shareContentAction->setCategoryId($this->category->getId());
            } else {
                // old share event doesn't have category
                $shareContentAction->setCategoryId("");
            }         
            $shareContentAction->setContentId($this->appContent->getId());
        }
        else {
            $shareContentAction->setSharedApp("");
            $shareContentAction->setCategoryId("");
            $shareContentAction->setContentId("");
        }

        
        
        $shareContentAction->setMetadata($this->content['event_value']);
        $this->shareContentActionRepository->save($shareContentAction);
        return $shareContentAction;
    }
    
    protected function getCategoryByIdentifier($categoryCode) {
        $categoryIdentifier['app_id'] = $this->application->getAppId();
        $categoryIdentifier['code'] =  $categoryCode;
        $category = $this->categoryRepository->getByIdentifier($categoryIdentifier);
        return $category;
    }
    
    protected function getContentByIdentifier() {
        
        $identifier['app_id'] = $this->application->getAppId();
        $identifier['title'] =  $this->shareMap['app_content_title'];
        $content = $this->contentRepository->getByIdentifier($identifier);
        return $content;
    }
    
    protected function getInCategoryContentByIdentifier() {
        $identifier = array();
        $identifier['content_id'] = $this->appContent->getId();
        $identifier['category_id'] = $this->category->getId();
        $inCategoryContent = $this->inCategoryContentRepository->getByIdentifier($identifier);
        return $inCategoryContent;
    }
    
    protected function storeCategory($parentCategory,$categoryCode) {
        $category = new Category();
        $appId = $this->application->getAppId();
        $parentId = (empty($parentCategory)?'0':$parentCategory->getId());
        $category->setParentId($parentId);
        $category->setAppId($appId);
        $category->setCode($categoryCode);
        $category->setName('');
        $this->categoryRepository->save($category);
        return $category;
    }
    
    protected function storeContent() {
        $appContent = new Content();
        $appContent->setTitle($this->shareMap['app_content_title']);
        $appContent->setAppId($this->application->getAppId());
        $appContent->setApplication($this->application);
        $this->contentRepository->save($appContent);
         return $appContent;
    }
    
    protected function storeInCategoryContent() {
        $inCategoryContent = new InCategoryContent();
        $inCategoryContent->setCategory($this->category);
        $inCategoryContent->setContent($this->appContent);
        $this->inCategoryContentRepository->save($inCategoryContent);
        return $inCategoryContent;
    }
    
    protected function onSuccess() {
        
    }
    
    protected function completeTransaction(){
        $this->shareContentActionRepository->completeTransaction();
    }
    
    protected function closeConnection(){
        $this->shareContentActionRepository->closeConnection();
    }
    
    protected function setShareMap() {
        if (is_bool($this->content['event_value']['af_description']) && is_bool($this->content['event_value']['af_content_id'])) {
            return array();
        } elseif (isset($this->content['event_value']['af_description'])) {
            $categoryCodes = array();
            $appContentTitle = null;
            $shareToApp = '';
            $shareContentDelimiter = '] - [';
            if (strpos($eventValue,$shareContentDelimiter) !== false ) {
                $part = explode($shareContentDelimiter,$eventValue);
                if (count($part) != 3) {
                    throw new \Exception("In valid content value for content share.Missing one of Share App,Category or App Content ");
                }
                $shareToApp = ltrim($part[0],'[');
                $end = count($part)-1;
                $part[$end] = rtrim($part[$end],']');
                $appContentTitle = $part[$end];
                $categoryString = $part[1];
                $categoryDelimiter = '>>>';
                if (strpos($categoryDelimiter,$categoryString)!== 0) {
                    
                    $categoryCodes = explode($categoryDelimiter,$categoryString);
                    
                } else {
                    $categoryCodes = array($categoryString);
                    
                }
            } else {
                $appContentTitle = $eventValue;
                /*
                throw new \Exception("In valid content value for content share.<br/>
                    Expected : [share_app] - [category_code] - [app_content_title].<br/>
                    Example : [Whatsapp] - [Health] - [Ini Sebabnya....].<br/>
                    Found : ".$eventValue."<br/>");
                */
            }
            
            return array(
                'share_app' => $shareToApp,
                'category_codes' => $categoryCodes,
                'app_content_title' => $appContentTitle
            );
            
        } else {
            throw new \Exception("invalid share content value");
        }
        
    }
    
}
