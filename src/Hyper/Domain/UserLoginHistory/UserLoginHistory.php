<?php

namespace Hyper\Domain\UserLoginHistory;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * User Login History
 *
 * @ORM\Table(name="user_login_history")
 * @ORM\Entity(repositoryClass="Hyper\DomainBundle\Repository\UserLoginHistory\DTUserLoginHistoryRepository")
 * @ExclusionPolicy("all")
 */
class UserLoginHistory
{
    /**
     * @var string
     * @ORM\Column(name="id", type="string")
     * @ORM\Id
     * @Expose
     */
    private $id;


    /**
     * @var string
     *
     * @ORM\Column(name="last_login", type="integer")
     * @Expose
     */
    private $lastLogin;


    /**
     * @var string
     *
     * @ORM\Column(name="ip", type="string")
     * @Expose
     */
    private $ip;


    /**
     * @var string
     *
     * @ORM\Column(name="location", type="string")
     * @Expose
     */
    private $location;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="browser_name", type="string")
     * @Expose
     */
    private $browserName;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="browser_version", type="string")
     * @Expose
     */
    private $browserVersion;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="os_name", type="string")
     * @Expose
     */
    private $osName;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="os_version", type="string")
     * @Expose
     */
    private $osVersion;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="device_type", type="string")
     * @Expose
     */
    private $deviceType;
    
    /**
     * @ORM\ManyToOne(targetEntity="Hyper\Domain\Authentication\Authentication", fetch="EXTRA_LAZY", inversedBy="user_login_history")
     * @ORM\JoinColumn(name="auth_id", referencedColumnName="id")
     * @Expose
     */
    private $authentication;
    
    public function __construct($id = null)
    {
        if (!empty($id)) {
            $this->id = $id;
        } else {
            $this->id = uniqid('',true);
        }
        $this->lastLogin = time();
    }
    

    /**
     * Set id
     *
     * @param string $id
     * @return UserLoginHistory
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id
     *
     * @return string 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set Last Login
     *
     * @param string $lastLogin
     * @return UserLoginHistory
     */
    public function setLastLogin($lastLogin)
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    /**
     * Get Last Login
     *
     * @return string 
     */
    public function getLastLogin()
    {
        return $this->lastLogin;
    }

    /**
     * Set IP
     *
     * @param string $ip
     * @return UserLoginHistory
     */
    public function setIp($ip)
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * Get IP
     *
     * @return string 
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * Set Location
     *
     * @param string $location
     * @return UserLoginHistory
     */
    public function setLocation($location)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get Location
     *
     * @return string 
     */
    public function getLocation()
    {
        return $this->location;
    }
    
    /**
     * Set Browser Name
     *
     * @param string $browserName
     * @return UserLoginHistory
     */
    public function setBrowserName($browserName)
    {
        $this->browserName = $browserName;

        return $this;
    }

    /**
     * Get Browser Name
     *
     * @return string 
     */
    public function getBrowserName()
    {
        return $this->browserName;
    }
    
    /**
     * Set Browser Version
     *
     * @param string $browserName
     * @return UserLoginHistory
     */
    public function setBrowserVersion($browserVersion)
    {
        $this->browserVersion = $browserVersion;

        return $this;
    }

    /**
     * Get Browser Version
     *
     * @return string 
     */
    public function getBrowserVersion()
    {
        return $this->browserVersion;
    }
    
    /**
     * Set OS Name
     *
     * @param string $osName
     * @return UserLoginHistory
     */
    public function setOsName($osName)
    {
        $this->osName = $osName;

        return $this;
    }

    /**
     * Get OS Name
     *
     * @return string 
     */
    public function getOsName()
    {
        return $this->osName;
    }
    
    /**
     * Set OS Version
     *
     * @param string $osVersion
     * @return UserLoginHistory
     */
    public function setOsVersion($osVersion)
    {
        $this->osVersion = $osVersion;

        return $this;
    }

    /**
     * Get Browser Version
     *
     * @return string 
     */
    public function getOsVersion()
    {
        return $this->osVersion;
    }
    
    
    /**
     * Set Device Type
     *
     * @param string $deviceType
     * @return UserLoginHistory
     */
    public function setDeviceType($deviceType)
    {
        $this->deviceType = $deviceType;

        return $this;
    }

    /**
     * Get platform
     *
     * @return integer 
     */
    public function getDeviceType()
    {
        return $this->deviceType;
    }
    
    /**
     * Set Authentication
     *
     * @param \Hyper\Domain\Identity\Identity $identity
     * @return Device
     */
    public function setAuthentication(\Hyper\Domain\Authentication\Authentication $authentication = null)
    {
        $this->authentication = $authentication;

        return $this;
    }

    /**
     * Get Authentication
     *
     * @return \Hyper\Domain\Authentication\Authentication 
     */
    public function geAuthentication()
    {
        return $this->authentication;
    }
}
