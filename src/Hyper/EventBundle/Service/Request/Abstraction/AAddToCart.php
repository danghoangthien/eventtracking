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
use Hyper\Domain\Action\AddToCartActionRepository;
use Hyper\Domain\Item\ItemRepository;
use Hyper\Domain\Item\InCartItemRepository;
use Hyper\Domain\Item\InCategoryItemRepository;
use Hyper\Domain\Category\CategoryRepository;

// Entities
use Hyper\Domain\Identity\Identity;
use Hyper\Domain\Application\Application;
use Hyper\Domain\Device\Device;
use Hyper\Domain\Device\IOSDevice;
use Hyper\Domain\Device\AndroidDevice;
use Hyper\Domain\Action\Action;
use Hyper\Domain\Action\AddToCartAction;
use Hyper\Domain\Item\Item;
use Hyper\Domain\Item\InCartItem;
use Hyper\Domain\Item\InCategoryItem;
use Hyper\Domain\Category\Category;

abstract class AAddToCart extends AAction
{
    /**
     * Repositories
     */
    protected $addToCartActionRepository;
    protected $itemRepository;
    protected $inCartItemRepository;
    protected $inCategoryRepository;
    protected $categoryRepository;
    
    protected $addToCartAction;
    protected $itemLog;
    protected $item;
    protected $inCartItem;
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
        AddToCartActionRepository $addToCartActionRepository,
        ItemRepository $itemRepository,
        InCartItemRepository $inCartItemRepository,
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

        $this->addToCartActionRepository = $addToCartActionRepository;
        $this->itemRepository = $itemRepository;
        $this->inCartItemRepository = $inCartItemRepository;
        $this->inCategoryItemRepository = $inCategoryItemRepository;
        $this->categoryRepository = $categoryRepository;
        
        // TODO - implement monolog,serializer,stopwatch,validator
    }
    
    public function registerAddToCartEvent() {
        try {
            
            $this->action = $this->storeAction();
            $this->totalItems = 0;
            $this->addToCartAction = $this->storeAddToCartAction();
            
            $itemLogs = $this->getItemLogs();
            $this->totalItems = count($itemLogs);
            /*
            //$this->totalItems = 0;
            //var_dump($itemLogs);
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
                    
                    //store incart item
                    $inCartItem = $this->storeInCartItem();
                    if ($inCartItem instanceof InCartItem){
                        $this->totalItems++;
                    }
                }
            }
            */
            $this->setTotalItemsInCart();
        } catch (\Exception $ex) {
            echo $ex->getMessage(). " ".$ex->getFile(). " ". $ex->getLine() ;
            echo $ex->getMessage()."---<hr/>";
            /**
             * TODO
             * catch more exception in detail write more custom exception class 
             * implement error log
             */ 
            throw new \Exception("could not register add to cart action");
        }
    }
    
    public function execute(){
        try {
            $this->prerequisite();
            $this->registerAddToCartEvent();
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

    protected abstract function storeAddToCartAction();
    
    protected abstract function getCategoryByIdentifier();
    
    protected abstract function getItemByIdentifier();
    
    protected abstract function storeCategory();
    
    protected abstract function storeItem();
    
    protected abstract function getInCategoryItemByIdentifier();
    
    protected abstract function storeInCategoryItem();
    
    protected abstract function storeInCartItem();
    
    protected abstract function setTotalItemsInCart();
    
    protected abstract function getItemLogs();
    
}
