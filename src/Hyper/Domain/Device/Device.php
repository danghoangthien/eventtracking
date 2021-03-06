<?php

namespace Hyper\Domain\Device;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * Device
 *
 * @ORM\Table(name="devices")
 * @ORM\Entity(repositoryClass="Hyper\DomainBundle\Repository\Device\DTDeviceRepository")
 * @ExclusionPolicy("all")
 */
class Device
{
    /**
     * TODO move these const to Device Platform value object
     */
    const ANDROID_PLATFORM_CODE = '2';
    const IOS_PLATFORM_CODE = '1';
    
    const ANDROID_PLATFORM_NAME = 'android';
    const IOS_PLATFORM_NAME = 'ios';
    
    /**
     * @var string
     * @ORM\Column(name="id", type="string")
     * @ORM\Id
     * @Expose
     */
    private $id;


    /**
     * @ORM\ManyToOne(targetEntity="Hyper\Domain\Identity\Identity", fetch="EXTRA_LAZY", inversedBy="devices")
     * @ORM\JoinColumn()
     * @Expose
     */
    private $identity;


    /**
     * @var integer
     *
     * @ORM\Column(name="platform", type="integer")
     * @Expose
     */
    private $platform;


    /**
     * @var integer
     *
     * @ORM\Column(name="click_time", type="integer", nullable=true, options={"unsigned"=true} )
     * @Expose
     */
    private $clickTime;

    /**
     * @var integer
     * 
     * @ORM\Column(name="install_time", type="integer", nullable=true, options={"unsigned"=true})
     * @Expose
     */
    private $installTime;
    
    /**
     * @var string
     *
     * @ORM\Column(name="country_code", type="string", nullable=true)
     * @Expose
     */
    private $countryCode;

    /**
     * @var string
     *
     * @ORM\Column(name="city", type="string", nullable=true)
     * @Expose
     */
    private $city;

    /**
     * @var string
     *
     * @ORM\Column(name="ip", type="string", nullable=true)
     * @Expose
     */
    private $ip;
    
    /**
     * @var string
     *
     * @ORM\Column(name="wifi", type="string", nullable=true)
     * @Expose
     */
    private $wifi;

    /**
     * @var string
     *
     * @ORM\Column(name="language", type="string", nullable=true)
     * @Expose
     */
    private $language;
    
    /**
     * @var string
     *
     * @ORM\Column(name="mac", type="string", nullable=true)
     * @Expose
     */
    private $mac;
    
    /**
     * @var string
     *
     * @ORM\Column(name="operator", type="string", nullable=true)
     * @Expose
     */
    private $operator;
    
    /**
     * @var string
     *
     * @ORM\Column(name="device_os_version", type="string", nullable=true)
     * @Expose
     */
    private $deviceOsVersion;   
    
    /**
     * @var integer
     *
     * @ORM\Column(name="created", type="integer")
     * @Expose
     */
    private $created;
    
    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="Hyper\Domain\Action\Action", mappedBy="device", fetch="EXTRA_LAZY", cascade={"persist"})
     */
     private $actions;
    
    
    
    
    public function __construct($deviceId = null)
    {
        if (!empty($deviceId)) {
            $this->id = $deviceId;
        } else {
            $this->id = uniqid('',true);
        }
        $this->actions = new \Doctrine\Common\Collections\ArrayCollection();
        $this->created = time();
    }
    
    


    /**
     * Set id
     *
     * @param string $id
     * @return Device
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
     * Set identityId
     *
     * @param string $identityId
     * @return Device
     */
    public function setIdentityId($identityId)
    {
        $this->identityId = $identityId;

        return $this;
    }

    /**
     * Get identityId
     *
     * @return string 
     */
    public function getIdentityId()
    {
        return $this->identityId;
    }

    /**
     * Set platform
     *
     * @param integer $platform
     * @return Device
     */
    public function setPlatform ($platform)
    {
        $this->platform = $platform;

        return $this;
    }

    /**
     * Get platform
     *
     * @return integer 
     */
    public function getPlatform()
    {
        return $this->platform;
    }

    /**
     * Set clickTime
     *
     * @param integer $clickTime
     * @return Device
     */
    public function setClickTime ($clickTime)
    {
        $this->clickTime = $clickTime;

        return $this;
    }

    /**
     * Get clickTime
     *
     * @return integer 
     */
    public function getClickTime()
    {
        return $this->clickTime;
    }

    /**
     * Set installTime
     *
     * @param integer $installTime
     * @return Device
     */
    public function setInstallTime($installTime)
    {
        $this->installTime = $installTime;

        return $this;
    }

    /**
     * Get installTime
     *
     * @return integer 
     */
    public function getInstallTime()
    {
        return $this->installTime;
    }

    /**
     * Set countryCode
     *
     * @param string $countryCode
     * @return Device
     */
    public function setCountryCode($countryCode)
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    /**
     * Get countryCode
     *
     * @return string 
     */
    public function getCountryCode()
    {
        return $this->countryCode;
    }

    /**
     * Set city
     *
     * @param string $city
     * @return Device
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city
     *
     * @return string 
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set ip
     *
     * @param string $ip
     * @return Device
     */
    public function setIp($ip)
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * Get ip
     *
     * @return string 
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * Set wifi
     *
     * @param string $wifi
     * @return Device
     */
    public function setWifi($wifi)
    {
        $this->wifi = $wifi;

        return $this;
    }

    /**
     * Get wifi
     *
     * @return string 
     */
    public function getWifi()
    {
        return $this->wifi;
    }

    /**
     * Set language
     *
     * @param string $language
     * @return Device
     */
    public function setLanguage($language)
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Get language
     *
     * @return string 
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Set operator
     *
     * @param string $operator
     * @return Device
     */
    public function setOperator($operator)
    {
        $this->operator = $operator;

        return $this;
    }

    /**
     * Get operator
     *
     * @return string 
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * Set deviceOsVersion
     *
     * @param string $deviceOsVersion
     * @return Device
     */
    public function setDeviceOsVersion($deviceOsVersion)
    {
        $this->deviceOsVersion = $deviceOsVersion;

        return $this;
    }

    /**
     * Get deviceOsVersion
     *
     * @return string 
     */
    public function getDeviceOsVersion()
    {
        return $this->deviceOsVersion;
    }

    /**
     * Set created
     *
     * @param integer $created
     * @return Device
     */
    public function setCreated ($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return integer 
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set identity
     *
     * @param \Hyper\Domain\Identity\Identity $identity
     * @return Device
     */
    public function setIdentity(\Hyper\Domain\Identity\Identity $identity = null)
    {
        $this->identity = $identity;

        return $this;
    }

    /**
     * Get identity
     *
     * @return \Hyper\Domain\Identity\Identity 
     */
    public function getIdentity()
    {
        return $this->identity;
    }

    /**
     * Add actions
     *
     * @param \Hyper\Domain\Action\Action $actions
     * @return Device
     */
    public function addAction(\Hyper\Domain\Action\Action $actions)
    {
        $this->actions[] = $actions;

        return $this;
    }

    /**
     * Remove actions
     *
     * @param \Hyper\Domain\Action\Action $actions
     */
    public function removeAction(\Hyper\Domain\Action\Action $actions)
    {
        $this->actions->removeElement($actions);
    }

    /**
     * Get actions
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * Set mac
     *
     * @param string $mac
     * @return Device
     */
    public function setMac($mac)
    {
        $this->mac = $mac;

        return $this;
    }

    /**
     * Get mac
     *
     * @return string 
     */
    public function getMac()
    {
        return $this->mac;
    }
}
