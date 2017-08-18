<?php
namespace Hyper\EventBundle\Service\Request\Appsflyer;

// Abstraction
use Hyper\EventBundle\Service\Request\Abstraction\AViewContent;
use Hyper\EventBundle\Service\Request\Appsflyer\Base;

// DT Repository
use Hyper\DomainBundle\Repository\Identity\DTIdentityRepository;
use Hyper\DomainBundle\Repository\Application\DTApplicationRepository;
use Hyper\DomainBundle\Repository\Device\DTDeviceRepository;
use Hyper\DomainBundle\Repository\Device\DTIOSDeviceRepository;
use Hyper\DomainBundle\Repository\Device\DTAndroidDeviceRepository;
use Hyper\DomainBundle\Repository\Action\DTActionRepository;
use Hyper\DomainBundle\Repository\Action\DTViewContentActionRepository;
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
use Hyper\Domain\Action\ViewContentAction;
use Hyper\Domain\Content\Content;
use Hyper\Domain\Content\InCategoryContent;
use Hyper\Domain\Category\Category;
use Hyper\Domain\Item\Item;
use Hyper\Domain\Item\InCategoryItem;

/**
 * Define a set of parameter that an install request from appsflyer postback provider must have
 */
class ViewContent extends AViewContent
{
    public $baseActionService;
    
    public function init(array $content,array $metaData = array()) {
        //echo "init view content service";
        $this->content = $content;
        
        $this->actionType = Action::ACTION_TYPES['IN_APP_EVENT_ACTION_TYPE'];
        $this->behaviourId = Action::BEHAVIOURS['VIEW_CONTENT_BEHAVIOUR_ID'];
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
    
    protected function storeViewContentAction() {
        $viewContentAction = new ViewContentAction();
        $action = $this->action;
        $viewContentAction->setAction($action);
        $viewContentAction->setDeviceId($this->deviceId);
        $viewContentAction->setApplicationId($this->application->getId());
        $viewContentAction->setAppId($this->application->getAppId());
        $viewContentAction->setViewedTime($this->action->getHappenedAt());
        $viewMap = $this->viewMap;
        $viewContentAction->setLogContentType($viewMap['log_content_type']);
        $viewContentAction->setLogContentId($viewMap['log_content_id']);
        $viewContentAction->setCategoryId("");
        $viewContentAction->setContentId("");
        $viewContentAction->setMetadata($this->content['event_value']);
        $this->viewContentActionRepository->save($viewContentAction);
        return $viewContentAction;
    }
    
    protected function getCategoryByIdentifier($categoryCode) {
        $categoryIdentifier['app_id'] = $this->application->getAppId();
        $categoryIdentifier['code'] =  $categoryCode;
        $category = $this->categoryRepository->getByIdentifier($categoryIdentifier);
        return $category;
    }
    
    protected function getContentByIdentifier() {
        
        $identifier['app_id'] = $this->application->getAppId();
        $identifier['title'] =  $this->viewMap['app_content_title'];
        $content = $this->contentRepository->getByIdentifier($identifier);
        return $content;
    }
    
    protected function getItemByIdentifier() {
        $itemCode = $this->viewMap['code'];
        $identifier = array();
        $identifier['application_id'] = $this->application->getId();
        $identifier['code'] =  $itemCode;
        $itemRepository = $this->container->get('item_repository');
        $item = $itemRepository->getByIdentifier($identifier);
        return $item;
    }
    
    protected function getInCategoryContentByIdentifier() {
        $identifier = array();
        $identifier['content_id'] = $this->appContent->getId();
        $identifier['category_id'] = $this->category->getId();
        $inCategoryContent = $this->inCategoryContentRepository->getByIdentifier($identifier);
        return $inCategoryContent;
    }
    
    //getInCategoryItemByIdentifier
    protected function getInCategoryItemByIdentifier() {
        $itemLog = $this->viewMap;
        $identifier = array();
        $identifier['item_code'] = $itemLog['code'];
        $identifier['category_id'] = $this->category->getId();
        $inCategoryItemRepository = $this->container->get('in_category_item_repository');
        $inCategoryItem = $inCategoryItemRepository->getByIdentifier($identifier);
        return $inCategoryItem;
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
        $appContent->setTitle($this->viewMap['app_content_title']);
        $appContent->setAppId($this->application->getAppId());
        $appContent->setApplication($this->application);
        $this->contentRepository->save($appContent);
         return $appContent;
    }
    
    protected function storeItem() {
        $itemLog = $this->viewMap;
        $item = new Item();
        $item->setApplication($this->application);
        $item->setAppId($this->application->getAppId());
        $item->setCode($itemLog['code']);
        $item->setPrice($itemLog['price']);
        $item->setCurrency($itemLog['currency']);
        $item->setMetadata($itemLog['metadata']);
        $itemRepository = $this->container->get('item_repository');
        $itemRepository->save($item);
        return $item;
    }
    
    protected function storeInCategoryContent() {
        $inCategoryContent = new InCategoryContent();
        $inCategoryContent->setCategory($this->category);
        $inCategoryContent->setContent($this->appContent);
        $this->inCategoryContentRepository->save($inCategoryContent);
        return $inCategoryContent;
    }
    
    protected function storeInCategoryItem() {
        $itemLog = $this->viewMap;
        //$category = $itemLog['category_code'];
        $inCategoryItem = new InCategoryItem();
        $inCategoryItem->setCategory($this->category);
        $inCategoryItem->setItemCode($itemLog['code']);
        $inCategoryItemRepository = $this->container->get('in_category_item_repository');
        $inCategoryItemRepository->save($inCategoryItem);
    }
    
    protected function onSuccess() {
        
    }
    
    protected function completeTransaction(){
        $this->viewContentActionRepository->completeTransaction();
    }
    
    protected function closeConnection(){
        $this->viewContentActionRepository->closeConnection();
    }
    
    protected function setViewMap() {
        if(isset($this->content['event_value']['af_description'])){
            // old format
            $eventValue = $this->content['event_value']['af_description'];
            $categoryCodes = array();
            $appContentTitle = null;
            $viewContentDelimiter = '] - [';
            if (strpos($eventValue,$viewContentDelimiter) !== false ) {
                $part = explode($viewContentDelimiter,$eventValue);
                if (count($part) != 2) {
                    throw new \Exception("In valid content value for content view.Missing Category or App Content ");
                }
                $categoryString = ltrim($part[0],'[');
                $end = count($part)-1;
                $appContentTitle = rtrim($part[$end],']');
                $categoryDelimiter = '>>>';
                if (strpos($categoryDelimiter,$categoryString)!== 0) {
                    
                    $categoryCodes = explode($categoryDelimiter,$categoryString);
                    
                } else {
                    $categoryCodes = array($categoryString);
                    
                }
            } else {
                throw new \Exception("In valid event value for content view.<br/>
                    Expected : [category_code] - [app_content_title].<br/>
                    Example : [Health] - [Ini Sebabnya....].<br/>
                    Found : ".$eventValue."<br/>");
            }
            
            return array(
                'log_content_type' => end($categoryCodes),
                'log_content_id' => $appContentTitle
            );
        } elseif (isset($this->content['event_value']['af_content_type'])) {
            // new format
            $eventValue = $this->content['event_value'];
            //af_content_type && af_content_id are compulsory
            if ( !isset($eventValue['af_content_type']) && !isset($eventValue['af_content_id'])) {
                throw new \Exception("invalid view content value,af_content_type && af_content_id are compulsory in new format");
            }
            $logContentType = $eventValue['af_content_type'];
            $logContentId = $eventValue['af_content_id'];
            return array(
                'log_content_type' => $logContentType,
                'log_content_id' => $logContentId
            );
            
        } else {
            throw new \Exception("invalid view content value");
        }
        
        
        //check if content value folow new format
        // "event_value": "{\"af_content_type\":\"flight\",\"af_price\":937000,\"af_content_id\":111494841,\"af_currency\":\"IDR\"}",
    
    }
    
}
