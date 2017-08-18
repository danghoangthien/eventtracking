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
use Hyper\Domain\Action\MiscActionRepository;

// Entities
use Hyper\Domain\Identity\Identity;
use Hyper\Domain\Application\Application;
use Hyper\Domain\Device\Device;
use Hyper\Domain\Device\IOSDevice;
use Hyper\Domain\Device\AndroidDevice;
use Hyper\Domain\Action\Action;
use Hyper\Domain\Action\MiscAction;

abstract class AMisc extends AAction
{
    /**
     * Repositories
     */
    protected $miscActionRepository;
    
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
        MiscActionRepository $miscActionRepository
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

        $this->miscActionRepository = $miscActionRepository;
        
        // TODO - implement monolog,serializer,stopwatch,validator
    }
    
    public function registerMiscEvent() {
        try {
            
            $this->action = $this->storeAction();
            $this->storeMiscAction();

        } catch (\Exception $ex) {
            /**
             * TODO
             * catch more exception in detail write more custom exception class 
             * implement error log
             */ 
            throw new \Exception("could not register misc action.".$ex->getMessage(). " ".$ex->getFile(). " ". $ex->getLine() );
        }
    }
    
    public function execute(){
        try {
            $this->prerequisite();
            $this->registerMiscEvent();
            //$this->completeTransaction();
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

    protected abstract function storeMiscAction();
    
}
