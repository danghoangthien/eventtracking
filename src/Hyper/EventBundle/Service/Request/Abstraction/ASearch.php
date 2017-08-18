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
use Hyper\Domain\Action\SearchActionRepository;

// Entities
use Hyper\Domain\Identity\Identity;
use Hyper\Domain\Application\Application;
use Hyper\Domain\Device\Device;
use Hyper\Domain\Device\IOSDevice;
use Hyper\Domain\Device\AndroidDevice;
use Hyper\Domain\Action\Action;
use Hyper\Domain\Action\SearchAction;

abstract class ASearch extends AAction
{
    /**
     * Repositories
     */
    protected $searchActionRepository;
    
    protected $behaviourId;
    protected $actionType;
    protected $providerId;
    protected $happenedAt;
    
    public function __construct(
        ContainerInterface $container,
        IdentityRepository $identityRepository,
        ApplicationRepository $applicationRepository,
        DeviceRepository $deviceRepository,
        IOSDeviceRepository $iosDeviceRepository,
        AndroidDeviceRepository $androidDeviceRepository,
        ActionRepository $actionRepository,
        SearchActionRepository $searchActionRepository
    ) {
        parent::__construct(
            $container,
            $identityRepository,
            $applicationRepository,
            $deviceRepository,
            $iosDeviceRepository,
            $androidDeviceRepository,
            $actionRepository
        );

        $this->searchActionRepository = $searchActionRepository;
        
        // TODO - implement monolog,serializer,stopwatch,validator
    }
    
    public function registerSearchEvent() {
        try {
            
            $this->action = $this->storeAction();
            $this->storeSearchAction();

        } catch (\Exception $ex) {
            /**
             * TODO
             * catch more exception in detail write more custom exception class 
             * implement error log
             */ 
            throw new \Exception("could not register search action");
        }
    }
    
    public function execute(){
        try {
            $this->prerequisite();
            $this->registerSearchEvent();
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

    protected abstract function storeSearchAction();
    
}
