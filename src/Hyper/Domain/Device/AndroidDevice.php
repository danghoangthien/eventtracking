<?php

namespace Hyper\Domain\Device;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * AndroidDevice
 *
 * @ORM\Table(name="android_devices")
 * @ORM\Entity(repositoryClass="Hyper\DomainBundle\Repository\Device\DTAndroidDeviceRepository")
 * @ExclusionPolicy("all")
 */
class AndroidDevice
{
    /**
     * @ORM\OneToOne(targetEntity="Device")
     * @ORM\JoinColumn(name="id", referencedColumnName="id")
     * @ORM\Id
     */
    private $device;


    /**
     * @var string
     *
     * @ORM\Column(name="advertising_id", type="string")
     * @Expose
     */
    private $advertisingId;


    /**
     * @var string
     *
     * @ORM\Column(name="android_id", type="string")
     * @Expose
     */
    private $androidId;


    /**
     * @var string
     *
     * @ORM\Column(name="imei", type="string")
     * @Expose
     */
    private $imei;

    /**
     * @var string
     *
     * @ORM\Column(name="device_brand", type="string")
     * @Expose
     */
    private $deviceBrand;
    
    /**
     * @var string
     *
     * @ORM\Column(name="device_model", type="string")
     * @Expose
     */
    private $deviceModel;

    /**
     * @var integer
     *
     * @ORM\Column(name="created", type="integer")
     * @Expose
     */
    private $created;
    
    
    
    public function __construct()
    {
        $this->created = time();
    }
    

    /**
     * Set advertisingId
     *
     * @param string $advertisingId
     * @return AndroidDevice
     */
    public function setAdvertisingId($advertisingId)
    {
        $this->advertisingId = $advertisingId;

        return $this;
    }

    /**
     * Get advertisingId
     *
     * @return string 
     */
    public function getAdvertisingId()
    {
        return $this->advertisingId;
    }

    /**
     * Set androidId
     *
     * @param string $androidId
     * @return AndroidDevice
     */
    public function setAndroidId($androidId)
    {
        $this->androidId = $androidId;

        return $this;
    }

    /**
     * Get androidId
     *
     * @return string 
     */
    public function getAndroidId()
    {
        return $this->androidId;
    }

    /**
     * Set imei
     *
     * @param string $imei
     * @return AndroidDevice
     */
    public function setImei($imei)
    {
        $this->imei = $imei;

        return $this;
    }

    /**
     * Get imei
     *
     * @return string 
     */
    public function getImei()
    {
        return $this->imei;
    }

    /**
     * Set deviceBrand
     *
     * @param string $deviceBrand
     * @return AndroidDevice
     */
    public function setDeviceBrand($deviceBrand)
    {
        $this->deviceBrand = $deviceBrand;

        return $this;
    }

    /**
     * Get deviceBrand
     *
     * @return string 
     */
    public function getDeviceBrand()
    {
        return $this->deviceBrand;
    }

    /**
     * Set deviceModel
     *
     * @param string $deviceModel
     * @return AndroidDevice
     */
    public function setDeviceModel($deviceModel)
    {
        $this->deviceModel = $deviceModel;

        return $this;
    }

    /**
     * Get deviceModel
     *
     * @return string 
     */
    public function getDeviceModel()
    {
        return $this->deviceModel;
    }

    /**
     * Set created
     *
     * @param integer $created
     * @return AndroidDevice
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
     * Set device
     *
     * @param \Hyper\Domain\Device\Device $device
     * @return AndroidDevice
     */
    public function setDevice(\Hyper\Domain\Device\Device $device)
    {
        $this->device = $device;

        return $this;
    }

    /**
     * Get device
     *
     * @return \Hyper\Domain\Device\Device 
     */
    public function getDevice()
    {
        return $this->device;
    }
}
