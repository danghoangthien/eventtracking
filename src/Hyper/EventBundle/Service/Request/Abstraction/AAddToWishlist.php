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
use Hyper\Domain\Action\AddToWishlistActionRepository;
use Hyper\Domain\Item\ItemRepository;
use Hyper\Domain\Item\InWishlistItemRepository;
use Hyper\Domain\Item\InCategoryItemRepository;
use Hyper\Domain\Category\CategoryRepository;

// Entities
use Hyper\Domain\Identity\Identity;
use Hyper\Domain\Application\Application;
use Hyper\Domain\Device\Device;
use Hyper\Domain\Device\IOSDevice;
use Hyper\Domain\Device\AndroidDevice;
use Hyper\Domain\Action\Action;
use Hyper\Domain\Action\AddToWishlistAction;
use Hyper\Domain\Item\Item;
use Hyper\Domain\Item\InWishlistItem;
use Hyper\Domain\Item\InCategoryItem;
use Hyper\Domain\Category\Category;

abstract class AAddToWishlist extends AAction
{
    /**
     * Repositories
     */
    protected $addToWishlistRepository;
    protected $itemRepository;
    protected $inWishlistItemRepository;
    protected $inCategoryRepository;
    protected $categoryRepository;
    
    protected $addToWishlistAction;
    protected $itemLog;
    protected $item;
    protected $inWishlistItem;
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
        AddToWishlistActionRepository $addToWishlistActionRepository,
        ItemRepository $itemRepository,
        InWishlistItemRepository $inWishlistItemRepository,
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

        $this->addToWishlistActionRepository = $addToWishlistActionRepository;
        $this->itemRepository = $itemRepository;
        $this->inWishlistItemRepository = $inWishlistItemRepository;
        $this->inCategoryItemRepository = $inCategoryItemRepository;
        $this->categoryRepository = $categoryRepository;
        
        // TODO - implement monolog,serializer,stopwatch,validator
    }
    
    public function registerAddToWishlistEvent() {
        try {
            
            $this->action = $this->storeAction();
            $this->totalItems = 0;
            $this->addToWishlistAction = $this->storeAddToWishlistAction();
            
            $itemLogs = $this->getItemLogs();
            $this->totalItems = count($itemLogs);
            //var_dump($itemLogs);
            /*
            foreach ($itemLogs as $itemLog) {
                $this->itemLog = $itemLog;
                $this->category = $this->getCategoryByIdentifier();
                if(!$this->category instanceof Category){
                    $this->category = $this->storeCategory();
                }
                $this->item = $this->getItemByIdentifier();
                if(!$this->item instanceof Item){
                    $this->item = $this->storeItem();
                }
                if($this->item instanceof Item){
                    //store incategory
                    $this->inCategoryItem = $this->getInCategoryItemByIdentifier();
                    if(!$this->inCategoryItem instanceof InCategoryItem){
                        $this->storeInCategoryItem();
                    }
                    
                    //store inwishlist item
                    $inWishlistItem = $this->storeInWishlistItem();
                    if ($inWishlistItem instanceof InWishlistItem){
                        $this->totalItems++;
                    }
                }
            }
            */
            $this->setTotalItemsInWishlist();
        } catch (\Exception $ex) {
            echo $ex->getMessage(). " ".$ex->getFile(). " ". $ex->getLine() ;
            echo $ex->getMessage()."---<hr/>";
            /**
             * TODO
             * catch more exception in detail write more custom exception class 
             * implement error log
             */ 
            throw new \Exception("could not register add to wish list action");
        }
    }
    
    public function execute(){
        try {
            $this->prerequisite();
            $this->registerAddToWishlistEvent();
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

    protected abstract function storeAddToWishlistAction();
    
    protected abstract function getCategoryByIdentifier();
    
    protected abstract function getItemByIdentifier();
    
    protected abstract function storeCategory();
    
    protected abstract function storeItem();
    
    protected abstract function getInCategoryItemByIdentifier();
    
    protected abstract function storeInCategoryItem();
    
    protected abstract function storeInWishlistItem();
    
    protected abstract function setTotalItemsInWishlist();
    
    protected abstract function getItemLogs();
    
}
