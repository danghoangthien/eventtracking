<?php

namespace Hyper\Domain\Device;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * IOSDevice
 *
 * @ORM\Table(name="ios_devices")
 * @ORM\Entity(repositoryClass="Hyper\DomainBundle\Repository\Device\DTIOSDeviceRepository")
 * @ExclusionPolicy("all")
 */
class IOSDevice
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
     * @ORM\Column(name="idfa", type="string")
     * @Expose
     */
    private $idfa;


    /**
     * @var string
     *
     * @ORM\Column(name="idfv", type="string")
     * @Expose
     */
    private $idfv;

    /**
     * @var string
     *
     * @ORM\Column(name="device_name", type="string")
     * @Expose
     */
    private $deviceName;
    
    /**
     * @var string
     *
     * @ORM\Column(name="device_type", type="string")
     * @Expose
     */
    private $deviceType;

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
     * Set idfa
     *
     * @param string $idfa
     * @return IOSDevice
     */
    public function setIdfa($idfa)
    {
        $this->idfa = $idfa;

        return $this;
    }

    /**
     * Get idfa
     *
     * @return string 
     */
    public function getIdfa()
    {
        return $this->idfa;
    }

    /**
     * Set idfv
     *
     * @param string $idfv
     * @return IOSDevice
     */
    public function setIdfv($idfv)
    {
        $this->idfv = $idfv;

        return $this;
    }

    /**
     * Get idfv
     *
     * @return string 
     */
    public function getIdfv()
    {
        return $this->idfv;
    }

    /**
     * Set deviceName
     *
     * @param string $deviceName
     * @return IOSDevice
     */
    public function setDeviceName($deviceName)
    {
        $this->deviceName = $deviceName;

        return $this;
    }

    /**
     * Get deviceName
     *
     * @return string 
     */
    public function getDeviceName()
    {
        return $this->deviceName;
    }

    /**
     * Set deviceType
     *
     * @param string $deviceType
     * @return IOSDevice
     */
    public function setDeviceType($deviceType)
    {
        $this->deviceType = $deviceType;

        return $this;
    }

    /**
     * Get deviceType
     *
     * @return string 
     */
    public function getDeviceType()
    {
        return $this->deviceType;
    }

    /**
     * Set created
     *
     * @param integer $created
     * @return IOSDevice
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
     * @return IOSDevice
     */
    public function setDevice(\Hyper\Domain\Device\Device $device = null)
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
