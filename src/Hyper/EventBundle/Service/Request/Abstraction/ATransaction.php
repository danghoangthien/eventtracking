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
use Hyper\Domain\Action\TransactionActionRepository;
use Hyper\Domain\Item\ItemRepository;
use Hyper\Domain\Item\TransactedItemRepository;
use Hyper\Domain\Item\InCategoryItemRepository;
use Hyper\Domain\Category\CategoryRepository;

// Entities
use Hyper\Domain\Identity\Identity;
use Hyper\Domain\Application\Application;
use Hyper\Domain\Device\Device;
use Hyper\Domain\Device\IOSDevice;
use Hyper\Domain\Device\AndroidDevice;
use Hyper\Domain\Action\Action;
use Hyper\Domain\Action\TransactionAction;
use Hyper\Domain\Item\Item;
use Hyper\Domain\Item\TransactedItem;
use Hyper\Domain\Item\InCategoryItem;
use Hyper\Domain\Category\Category;

abstract class ATransaction extends AAction
{
    /**
     * Repositories
     */
    protected $transactionActionRepository;
    protected $itemRepository;
    protected $transactedItemRepository;
    protected $inCategoryRepository;
    protected $categoryRepository;
    
    protected $addToCartAction;
    protected $itemLog;
    protected $item;
    protected $transactedItem;
    protected $inCategoryItem;
    protected $totalItems;
    protected $category;
    
    public function __construct (
        ContainerInterface $container,
        IdentityRepository $identityRepository,
        ApplicationRepository $applicationRepository,
        DeviceRepository $deviceRepository,
        IOSDeviceRepository $iosDeviceRepository,
        AndroidDeviceRepository $androidDeviceRepository,
        ActionRepository $actionRepository,
        TransactionActionRepository $transactionActionRepository,
        ItemRepository $itemRepository,
        TransactedItemRepository $transactedItemRepository,
        InCategoryItemRepository $inCategoryItemRepository,
        CategoryRepository $categoryRepository
    ) {
        parent::__construct (
            $container,
            $identityRepository,
            $applicationRepository,
            $deviceRepository,
            $iosDeviceRepository,
            $androidDeviceRepository,
            $actionRepository
        );

        $this->transactionActionRepository = $transactionActionRepository;
        $this->itemRepository = $itemRepository;
        $this->transactedItemRepository = $transactedItemRepository;
        $this->inCategoryItemRepository = $inCategoryItemRepository;
        $this->categoryRepository = $categoryRepository;
        
        // TODO - implement monolog,serializer,stopwatch,validator
    }
    
    public function registerTransactionEvent() {
        try {
            
            $this->action = $this->storeAction();
            $this->transactionAction = $this->storeTransactionAction();
            
            $itemLogs = $this->getItemLogs();
            $this->totalItems = 0;
            //var_dump($itemLogs);
            foreach ($itemLogs as $itemLog) {
                $this->itemLog = $itemLog;
                $this->item = $this->getItemByIdentifier();
                $this->category = $this->getCategoryByIdentifier();
                var_dump($this->category instanceof Category);
                if(!$this->category instanceof Category){
                    $this->category = $this->storeCategory();
                }
                if(!$this->item instanceof Item){
                    $this->item = $this->storeItem();
                }
                if($this->item instanceof Item){
                    //store incategory
                    $this->inCategoryItem = $this->getInCategoryItemByIdentifier();
                    if(!$this->inCategoryItem instanceof InCategoryItem){
                        $this->storeInCategoryItem();
                    }
                    
                    $transactedItem = $this->storeTransactedItem();
                    if ($transactedItem instanceof TransactedItem){
                        //echo "store transacted item in DB <br/>";
                        $this->totalItems++;
                        $this->transactionAction->addTransactedItem($transactedItem);
                    }
                    
                }
            }
            $this->setQuantity();
            $this->storeFrm();
        } catch (\Exception $ex) {
            echo $ex->getMessage(). " ".$ex->getFile(). " ". $ex->getLine() ;
            echo $ex->getMessage()."<hr/>";
            /**
             * TODO
             * catch more exception in detail write more custom exception class 
             * implement error log
             */ 
            throw new \Exception("could not register transaction action");
        }
    }
    
    public function execute(){
        try {
            $this->prerequisite();
            $this->registerTransactionEvent();
            $this->completeTransaction();
            $this->onSuccess();
            return true;
        } catch (\Exception $ex){
             /**
              * TODO - implement error log 
              */
             echo $ex->getMessage();
             //$this->closeConnection();
             return false;
        }
    }

    protected abstract function storeTransactionAction();
    
    protected abstract function storeFrm();
    
    protected abstract function getCategoryByIdentifier();
    
    protected abstract function getItemByIdentifier();
    
    protected abstract function storeCategory();
    
    protected abstract function storeItem();
    
    protected abstract function getInCategoryItemByIdentifier();
    
    protected abstract function storeInCategoryItem();
    
    protected abstract function storeTransactedItem();
    
    protected abstract function setQuantity();
    
    protected abstract function getItemLogs();
    
}
