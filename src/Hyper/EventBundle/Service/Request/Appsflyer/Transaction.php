<?php
namespace Hyper\EventBundle\Service\Request\Appsflyer;

// Abstraction
use Hyper\EventBundle\Service\Request\Abstraction\ATransaction;
use Hyper\EventBundle\Service\Request\Appsflyer\Base;

// DT Repository
use Hyper\DomainBundle\Repository\Identity\DTIdentityRepository;
use Hyper\DomainBundle\Repository\Application\DTApplicationRepository;
use Hyper\DomainBundle\Repository\Device\DTDeviceRepository;
use Hyper\DomainBundle\Repository\Device\DTIOSDeviceRepository;
use Hyper\DomainBundle\Repository\Device\DTAndroidDeviceRepository;
use Hyper\DomainBundle\Repository\Action\DTActionRepository;
use Hyper\DomainBundle\Repository\Action\DTAddToCartActionRepository;
use Hyper\DomainBundle\Repository\Item\DTInCartItemRepository;
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
use Hyper\Domain\Action\TransactionAction;
use Hyper\Domain\Item\Item;
use Hyper\Domain\Item\InCategoryItem;
use Hyper\Domain\Item\TransactedItem;
use Hyper\Domain\Category\Category;

/**
 * Define a set of parameter that an install request from appsflyer postback provider must have
 */
class Transaction extends ATransaction
{
    public $baseActionService;
    
    public function init(array $content,array $metaData = array()){
        //echo "init purchase service";
        $this->content = $content;
        
        $this->actionType = Action::ACTION_TYPES['IN_APP_EVENT_ACTION_TYPE'];
        $this->behaviourId = Action::BEHAVIOURS['PURCHASE_BEHAVIOUR_ID'];
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
    
    protected function storeTransactionAction() {
        $content = $this->content;
        $transactionAction = new TransactionAction();
        $action = $this->action;
        $transactionAction->setAction($action);
        $transactionAction->setDeviceId($this->deviceId);
        $transactionAction->setAppId($this->application->getAppId());
        $transactionAction->setApplicationId($this->application->getId());
        $transactionAction->setTransactedTime($this->action->getHappenedAt());
        $eventValue = $this->content['event_value'];
        $transactionAction->setMetadata($eventValue);
        
        //TODO -find a better solution purchase param more flexible
        if(!is_array($content['event_value'])) {
                 $transactedPrice = $content['event_value'];
        } else {
            if (isset($content['event_value']['af_revenue'])) {
                $transactedPrice = $content['event_value']['af_revenue'];
            } elseif (isset($content['event_value']['af_price'])) {
                $transactedPrice = $content['event_value']['af_price'];
            } else {
                $transactedPrice = '-1';
            }
                
        }
        $transactionAction->setTransactedPrice($transactedPrice);
        
        $currency = $content['currency'];
        $transactionAction->setCurrency($currency);
        $this->transactionActionRepository->save($transactionAction);
        return $transactionAction;
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
    
    protected function storeTransactedItem() {
        $itemLog = $this->itemLog;
        $transactedItem = new TransactedItem();
        $transactedItem->setDeviceId($this->deviceId);
        $transactedItem->setAppId($this->application->getAppId());
        $transactedItem->setApplicationId($this->application->getId());
        $transactedItem->setTransaction($this->transactionAction);
        $transactedItem->setItem($this->item);
        $transactedItem->setTransactedPrice($itemLog['price']);
        $this->transactedItemRepository->save($transactedItem);
        return $transactedItem;
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
             if ($this->isSingleItemInCart()) {
                 $eventValue = $content['event_value'];
                 if (is_array($eventValue)){
                    if (isset($eventValue['af_price'])) {
                        $itemLog['price'] = $eventValue['af_price'];
                    } elseif (isset($eventValue['af_revenue'])) {
                        $itemLog['price'] = $eventValue['af_revenue'];
                    }
                    if (isset($eventValue['af_currency'])) {
                        $itemLog['currency'] = $eventValue['af_currency'];
                    }
                    else {
                        $itemLog['currency'] = $content['currency'];
                    }
                    $itemLog['code'] = $eventValue['af_content_id'];//item code
                    $itemLog['category_code'] = $eventValue['af_content_type'];//category code
                    $itemLog['metadata'] =json_encode($eventValue);
                    $itemLogs[] = $itemLog;
                 }
                 
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
    
    protected function setQuantity(){
        $totalItems = $this->totalItems;
        $transactionAction = $this->transactionAction;
        $transactionAction->setQuantity($totalItems);
        $this->transactionActionRepository->save($transactionAction);
        return $transactionAction;
    }
    
    protected function storeFrm() {
        $transactionAction = $this->transactionAction;
        $transactedItems = $transactionAction->getTransactedItems();
        $transactionCurrency = $transactionAction->getCurrency();
        //print_r($transactedItemsIterator);die;
        //$transactedItems = $transactedItems->toArray();
        //echo "transactedItems:<br/>";
        //var_dump( count($transactedItems) );die;
        $itemMeta = array();


        foreach($transactedItems as $transactedItem) {
           // echo "loop transactedItemsIterator <br/>";
            //print_r($transactedItem);
            $item = $transactedItem->getItem();
            $code = $item->getCode();
            $metaData = $item->getMetadata();
            $metaData = json_decode($metaData,true);
            $itemMeta[$code]['item_code'] = $code;
            if (isset($metaData['content_type'])) {
                $itemMeta[$code]['category_code'] = $metaData['content_type'];
            } elseif (isset($metaData['af_content_type'])) {
                $itemMeta[$code]['category_code'] = $metaData['af_content_type'];
            }
            if ($transactionCurrency == 'USD'){
                $baseCurrencyAmount = $transactedItem->getTransactedPrice();
            } else {
                $baseCurrencyAmount = $this->getBaseCurrencyAmount($transactionCurrency,$transactedItem->getTransactedPrice());
            }
            //$baseCurrencyAmount = $this->getBaseCurrencyAmount($transactionCurrency,$transactedItem->getTransactedPrice());
            $itemMeta[$code]['base_curency_transacted_amount'] = $baseCurrencyAmount;
            //echo "base currency amount from item ".$baseCurrencyAmount."<br/>";
        }


        $frm = new \Hyper\Domain\Frm\Frm();
        $frmRepository = $this->container->get('frm_repository');
        
        $deviceId = $transactionAction->getDeviceId();
        $frm->setDeviceId($deviceId);
        $appId = $this->application->getAppId();
        $frm->setAppId($appId);
        $action = $this->action;
        $eventType = $action->getBehaviourId();
        $frm->setEventType($eventType);//action behaviour id
        $frm->setAccountType(1);//hard coding
        $actionId = $action->getId();
        $frm->setReferenceEventId($actionId);//transaction_id
        $transactedItems = $transactionAction->getTransactedItems();
        $frm->setReferenceItemCodes($itemMeta);
        $transactionAmount = $transactionAction->getTransactedPrice();
        if ($transactionCurrency == 'USD'){
            $baseCurrencyAmount = $transactionAmount;
        } else {
            $baseCurrencyAmount = $this->getBaseCurrencyAmount($transactionCurrency,$transactionAmount);
        }
        $frm->setAmount($baseCurrencyAmount);
        //echo "<br/>".$baseCurrencyAmount. "in USD";
        $frm->setBaseCurrency('USD');
        $transactedTime = $transactionAction->getTransactedTime();
        $frm->setEventTime($transactedTime);
        $frmRepository->save($frm);
    }
    
    protected function completeTransaction(){
        $this->transactionActionRepository->completeTransaction();
    }
    
    protected function closeConnection(){
        $this->transactionActionRepository->closeConnection();
    }
    
    private function isSingleItemInCart() {
        return true;
    }
    
    private function getBaseCurrencyAmount($fromCurrency,$amount) {
        $currencyRepo = $this->container->get('currency_repository');
        $convertedAmount = $currencyRepo->convert($fromCurrency,$amount);
        if($convertedAmount === false){
            throw new \Exception("Cannot convert amount");
        }
        return $convertedAmount;
    }
    
}
