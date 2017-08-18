<?php
namespace Hyper\EventBundle\Service\Request\Appsflyer;

// Abstraction
use Hyper\EventBundle\Service\Request\Abstraction\AAddToWishlist;
use Hyper\EventBundle\Service\Request\Appsflyer\Base;

// DT Repository
use Hyper\DomainBundle\Repository\Identity\DTIdentityRepository;
use Hyper\DomainBundle\Repository\Application\DTApplicationRepository;
use Hyper\DomainBundle\Repository\Device\DTDeviceRepository;
use Hyper\DomainBundle\Repository\Device\DTIOSDeviceRepository;
use Hyper\DomainBundle\Repository\Device\DTAndroidDeviceRepository;
use Hyper\DomainBundle\Repository\Action\DTActionRepository;
use Hyper\DomainBundle\Repository\Action\DTAddToWishlistActionRepository;
use Hyper\DomainBundle\Repository\Item\DTInWishlistItemRepository;
use Hyper\DomainBundle\Repository\Item\DTInCategoryItemRepository;
use Hyper\DomainBundle\Repository\Item\DTItemRepository;
use Hyper\DomainBundle\Repository\Category\DTCategoryRepository;



// Entities
use Hyper\Domain\Identity\Identity;
use Hyper\Domain\Application\Application;
use Hyper\Domain\Device\Device;
use Hyper\Domain\Device\IOSDevice;
use Hyper\Domain\Device\AndroidDevice;
use Hyper\Domain\Action\Action;
use Hyper\Domain\Action\AddToWishlistAction;
use Hyper\Domain\Item\Item;
use Hyper\Domain\Item\InCategoryItem;
use Hyper\Domain\Item\InWishlistItem;
use Hyper\Domain\Category\Category;

/**
 * Define a set of parameter that an install request from appsflyer postback provider must have
 */
class AddToWishlist extends AAddToWishlist
{
    public $baseActionService;
    
    public function init(array $content,array $metaData = array()) {
        //echo "init add to Wishlist service";
        $this->content = $content;
        
        $this->actionType = Action::ACTION_TYPES['IN_APP_EVENT_ACTION_TYPE'];
        $this->behaviourId = Action::BEHAVIOURS['ADD_TO_WISHLIST_BEHAVIOUR_ID'];
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
    
    protected function storeAddToWishlistAction() {
        $addToWishlistAction = new AddToWishlistAction();
        $action = $this->action;
        $addToWishlistAction->setAction($action);
        $addToWishlistAction->setDeviceId($this->deviceId);
        $addToWishlistAction->setApplicationId($this->application->getId());
        $addToWishlistAction->setAppId($this->application->getAppId());
        $addToWishlistAction->setTotalItems($this->totalItems);
        $eventValue = $this->content['event_value'];
        $addToWishlistAction->setMetadata($this->totalItems);
        $addToWishlistAction->setAddedTime($this->action->getHappenedAt());
        $this->addToWishlistActionRepository->save($addToWishlistAction);
        return $addToWishlistAction;
    }
    
    protected function getCategoryByIdentifier() {
        $itemLog = $this->itemLog;
        $identifier = array();
        $identifier['app_id'] = $this->application->getAppId();
        $identifier['code'] =  $itemLog['category_code'];
        $category = $this->categoryRepository->getByIdentifier($identifier);
        return $category;
    }
    
    protected function getItemByIdentifier() {
        $itemLog = $this->itemLog;
        $identifier = array();
        $identifier['application_id'] = $this->application->getId();
        $identifier['code'] =  $itemLog['code'];
        $item = $this->itemRepository->getByIdentifier($identifier);
        return $item;
    }
    
    protected function getInCategoryItemByIdentifier() {
        $itemLog = $this->itemLog;
        $identifier = array();
        $identifier['item_code'] = $itemLog['code'];
        $identifier['category_id'] = $this->category->getId();
        $inCategoryItem = $this->inCategoryItemRepository->getByIdentifier($identifier);
        return $inCategoryItem;
    }
    
    protected function storeCategory() {
        $itemLog = $this->itemLog;
        $category = new Category();
        $appId = $this->application->getAppId();
        $category->setParentId(0);
        $category->setAppId($appId);
        $category->setCode($itemLog['category_code']);
        $category->setName('');
        $this->categoryRepository->save($category);
        return $category;
    }
    
    protected function storeItem() {
        $itemLog = $this->itemLog;
        $item = new Item();
        $item->setApplication($this->application);
        $item->setAppId($this->application->getAppId());
        $item->setCode($itemLog['code']);
        $item->setPrice($itemLog['price']);
        $item->setCurrency($itemLog['currency']);
        $item->setMetadata($itemLog['metadata']);
        $this->itemRepository->save($item);
        return $item;
    }
    
    protected function storeInCategoryItem() {
        $itemLog = $this->itemLog;
        $category = $this->category;
        $inCategoryItem = new InCategoryItem();
        $inCategoryItem->setCategory($category);
        $inCategoryItem->setItemCode($itemLog['code']);
        $this->inCategoryItemRepository->save($inCategoryItem);
    }
    
    protected function storeInWishlistItem() {
        $inWishlistItem = new InWishlistItem();
        $inWishlistItem->setDeviceId($this->deviceId);
        $inWishlistItem->setAppId($this->application->getAppId());
        $inWishlistItem->setApplicationId($this->application->getId());
        $inWishlistItem->setWishlist($this->addToWishlistAction);
        $inWishlistItem->setItem($this->item);
        $this->inWishlistItemRepository->save($inWishlistItem);
        return $inWishlistItem;
    }
    
    protected function onSuccess() {
        
    }
    
    protected function getItemLogs() {
         $content = $this->content;
         
         $itemLogs = array();
         
         if (empty($content['event_value'])) {
             throw new Exception("could not get items log from empty event_value");
         }
         else {
             if ($this->isSingleItemInWishlist()) {
                 $eventValue = $content['event_value'];
                 $itemLog['price'] = $eventValue['af_price'];
                 if(isset($eventValue['af_currency'])){
                     $itemLog['currency'] = $eventValue['af_currency'];
                 }
                 else{
                     $itemLog['currency'] = $content['currency'];
                 }
                 $itemLog['code'] = $eventValue['af_content_id'];//item code
                 $itemLog['category_code'] = $eventValue['af_content_type'];//category code
                 $itemLog['metadata'] =json_encode($eventValue);
                 $itemLogs[] = $itemLog;
             }
             else {
                 
             }
             //single item
             //multiple item
         }
         //echo "<pre>";
         //var_dump($itemLogs);
         return $itemLogs;
    }
    
    protected function setTotalItemsInWishlist(){
        $totalItems = $this->totalItems;
        $addToWishlistAction = $this->addToWishlistAction;
        $addToWishlistAction->setTotalItems($totalItems);
        $this->addToWishlistActionRepository->save($addToWishlistAction);
        return $addToWishlistAction;
    }
    
    protected function completeTransaction(){
        $this->addToWishlistActionRepository->completeTransaction();
    }
    
    protected function closeConnection(){
        $this->addToWishlistActionRepository->closeConnection();
    }
    
    private function isSingleItemInWishlist() {
        return true;
    }
    
}
