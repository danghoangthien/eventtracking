<?php

namespace Hyper\Domain\DeviceAppInformation;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * DeviceAppInformation
 *
 * @ORM\Table(name="device_app_information")
 * @ORM\Entity(repositoryClass="Hyper\DomainBundle\Repository\DeviceAppInformation\DTDeviceAppInformationRepository")
 * @ExclusionPolicy("all")
 */
class DeviceAppInformation
{
    /**
     * @ORM\OneToOne(targetEntity="Hyper\Domain\Device\Device")
     * @ORM\JoinColumn(name="id", referencedColumnName="id")
     * @ORM\Id
     */
    private $device;

    /**
     * @ORM\Column(name="app_id", type="string", nullable=true)
     * @ORM\Id
     */
    private $appId;

    /**
     * @var integer
     *
     * @ORM\Column(name="install_time", type="integer")
     * @Expose
     */
    private $installTime;

    /**
     * @var integer
     *
     * @ORM\Column(name="last_activity", type="integer")
     * @Expose
     */
    private $lastActivity;



    public function __construct()
    {

    }

    /**
     * Set Device
     *
     * @param \Hyper\Domain\Device\Device $device
     * @return DeviceAppInformation
     */
    public function setDevice(\Hyper\Domain\Device\Device $device)
    {
        $this->device = $device;

        return $this;
    }

    /**
     * Get Device
     *
     * @return \Hyper\Domain\Device\Device
     */
    public function getDevice()
    {
        return $this->device;
    }

    /**
     * Set AppId
     *
     * @param string $appId
     * @return DeviceAppInformation
     */
    public function setAppId($appId)
    {
        $this->appId = $appId;

        return $this;
    }

    /**
     * Get AppId
     *
     * @return string
     */
    public function getAppId()
    {
        return $this->appId;
    }

    /**
     * Set Install Time
     *
     * @param integer $installTime
     * @return DeviceAppInformation
     */
    public function setInstallTime($installTime)
    {
        $this->installTime = $installTime;

        return $this;
    }

    /**
     * Get Install Time
     *
     * @return string
     */
    public function getInstallTime()
    {
        return $this->installTime;
    }

    /**
     * Set Last Activity
     *
     * @param integer $lastActivity
     * @return DeviceAppInformation
     */
    public function setLastActivity($lastActivity)
    {
        $this->lastActivity = $lastActivity;

        return $this;
    }

    /**
     * Get Last Activity
     *
     * @return string
     */
    public function getLastActivity()
    {
        return $this->lastActivity;
    }
}
