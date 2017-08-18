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
use Hyper\Domain\Action\ShareContentActionRepository;
use Hyper\Domain\Category\CategoryRepository;
use Hyper\Domain\Content\ContentRepository;
use Hyper\Domain\Content\InCategoryContentRepository;


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

abstract class AShareContent extends AAction
{
    /**
     * Repositories
     */
    protected $shareContentActionRepository;
    protected $contentRepository;
    protected $inCategoryContentRepository;
    protected $categoryRepository;
    
    protected $shareContentAction;
    protected $appContent;
    protected $inCategoryContent;
    protected $category;
    
    protected $shareMap; 
    
    public function __construct (
        ContainerInterface $container,
        IdentityRepository $identityRepository,
        ApplicationRepository $applicationRepository,
        DeviceRepository $deviceRepository,
        IOSDeviceRepository $iosDeviceRepository,
        AndroidDeviceRepository $androidDeviceRepository,
        ActionRepository $actionRepository,
        ShareContentActionRepository $shareContentActionRepository,
        ContentRepository $contentRepository,
        InCategoryContentRepository $inCategoryContentRepository,
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

        $this->shareContentActionRepository = $shareContentActionRepository;
        $this->contentRepository = $contentRepository;
        $this->inCategoryContentRepository = $inCategoryContentRepository;
        $this->categoryRepository = $categoryRepository;
        
        // TODO - implement monolog,serializer,stopwatch,validator
    }
    
    public function registerShareContentEvent() {
        try {
            /*
            $this->shareMap = $this->setShareMap();
            if(!empty($this->shareMap)){
                $categoryCodes = $this->shareMap['category_codes'];
                $parentCategory = null; 
                $this->category = null;
                foreach ($categoryCodes as $categoryCode) {
                    // each category code will derive or create category
                    // last category will be category of current app content
                    $this->category = $this->getCategoryByIdentifier($categoryCode);
                    if (!$this->category instanceof Category) {
                        $this->category = $this->storeCategory($parentCategory,$categoryCode);
                    }
                    $parentCategory = $this->category;
                }
                
                $appContentName = $this->shareMap['app_content_title'];
                $this->appContent = $this->getContentByIdentifier($appContentName);
                if (!$this->appContent instanceof Content) {
                    $this->appContent = $this->storeContent();
                }
                
                if($this->category instanceof Category) {
                    $this->inCategoryContent = $this->getInCategoryContentByIdentifier();
                    if (! $this->inCategoryContent instanceof InCategoryContent) {
                        $this->inCategoryContent = $this->storeInCategoryContent();
                    }
                }
            }
            */
            $this->action = $this->storeAction();
            $this->shareContentAction = $this->storeShareContentAction();
            
            

        } catch (\Exception $ex) {
            echo $ex->getMessage(). " ".$ex->getFile(). " ". $ex->getLine() ;
            echo $ex->getMessage()."---<hr/>";
            /**
             * TODO
             * catch more exception in detail write more custom exception class 
             * implement error log
             */ 
            throw new \Exception("could not register share content action");
        }
    }
    
    public function execute(){
        try {
            $this->prerequisite();
            $this->registerShareContentEvent();
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

    protected abstract function storeShareContentAction();
    
    protected abstract function getCategoryByIdentifier($categoryCode);
    
    protected abstract function getContentByIdentifier();
    
    protected abstract function storeCategory($parentCategory,$categoryCode);
    
    protected abstract function storeContent();
    
    protected abstract function getInCategoryContentByIdentifier();
    
    protected abstract function storeInCategoryContent();
    
    protected abstract function setShareMap();
    
}
