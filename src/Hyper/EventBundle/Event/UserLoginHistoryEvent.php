<?php

namespace Hyper\EventBundle\Event;

use Symfony\Component\EventDispatcher\Event,
    Hyper\Domain\Authentication\Authentication,
    Hyper\Domain\UserLoginHistory\UserLoginHistory,
    Hyper\DomainBundle\Repository\Authentication\DTAuthenticationRepository,
    Hyper\DomainBundle\Repository\UserLoginHistory\DTUserLoginHistoryRepository,
    DeviceDetector\DeviceDetector,
    Symfony\Component\DependencyInjection\ContainerInterface,
    DeviceDetector\Parser\Device\DeviceParserAbstract;

/**
 * The user_login_history.logined event is dispatched each time which user logged
 * in the system.
 */
class UserLoginHistoryEvent extends Event
{
    const USER_LOGIN_HISTORY_LOGINED = 'user_login_history.logined';
    
    protected $container;
    protected $userLoginHistory;
    protected $ip;
    protected $location;
    protected $auth;
    protected $browser;
    protected $os;
    protected $deviceType;

    public function __construct(ContainerInterface $container, Authentication $auth)
    {
        $this->container = $container;
        $this->auth = $auth;
        $this->initIp();
        $this->initLocation();
        $this->detectDevice();
        $this->onStore();
    }

    public function getUserLoginHistory()
    {
        return $this->userLoginHistory;
    }
    
    public function initIp()
    {
        $ipaddress = '';
        if (getenv('HTTP_CLIENT_IP')) {
            $ipaddress = getenv('HTTP_CLIENT_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR')) {
            $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('HTTP_X_FORWARDED')) {
            $ipaddress = getenv('HTTP_X_FORWARDED');
        } elseif (getenv('HTTP_FORWARDED_FOR')) {
            $ipaddress = getenv('HTTP_FORWARDED_FOR');
        } elseif (getenv('HTTP_FORWARDED')) {
            $ipaddress = getenv('HTTP_FORWARDED');
        } else if (getenv('REMOTE_ADDR')) {
            $ipaddress = getenv('REMOTE_ADDR');
        }
            
        $this->ip = $ipaddress;
    }
    
    public function initLocation()
    {
        $location = '';
        try {
            $geoip = $this->container->get('maxmind.geoip')->lookup($this->ip);
            $city = '';
            if($geoip->getCity()) {
                $city = $geoip->getCity();
            } else {
                $city = $geoip->getRegion();
            }
            $country = $geoip->getCountryName();
            
            $location = implode(", ", array($city, $country)); 
        } catch(\Exception $e) {
            echo $e->getMessage();exit;
            $location = '';
        }
        
        $this->location = implode(", ", array($city, $country));
    }
    
    public function detectDevice()
    {
        $dd = new DeviceDetector($_SERVER['HTTP_USER_AGENT']);
        $dd->parse();
        $this->browser = $dd->getClient();
        $this->os = $dd->getOs();
        $this->deviceType = $dd->getDeviceName($dd->getDevice());
    }
    
    public function onStore()
    {
        $userLoginHistory = new UserLoginHistory();
        $userLoginHistory->setIp($this->ip);
        $userLoginHistory->setLocation($this->location);
        if ($this->browser) {
            $userLoginHistory->setBrowserName($this->browser['name']);
            $userLoginHistory->setBrowserVersion($this->browser['version']);
        }
        if ($this->os) {
            $userLoginHistory->setOsName($this->os['name']);
            $userLoginHistory->setOsVersion($this->os['version']);
        }
        if ($this->deviceType) {
            $userLoginHistory->setDeviceType($this->deviceType);
        }
        $userLoginHistory->setAuthentication($this->auth);
        $ulhFoundByUpdate = $this->container->get('user_login_history_repository')
            ->storeUserLoginHistory($userLoginHistory);
        if ($ulhFoundByUpdate instanceof UserLoginHistory) {
            $this->userLoginHistory = $ulhFoundByUpdate;
            $this->auth->setLastLogin($this->userLoginHistory->getLastLogin());
            $this->auth->setIp($this->userLoginHistory->getIp());
            $this->auth->setLocation($this->userLoginHistory->getLocation());
            $this->auth->setBrowserName($this->userLoginHistory->getBrowserName());
            $this->auth->setBrowserVersion($this->userLoginHistory->getBrowserVersion());
            $this->auth->setOsName($this->userLoginHistory->getOsName());
            $this->auth->setOsVersion($this->userLoginHistory->getOsVersion());
            $this->auth->setDeviceType($this->userLoginHistory->getDeviceType());
            $this->container->get('authentication_repository')->save($this->auth);
            $this->container->get('authentication_repository')->completeTransaction();
        }
    }
}