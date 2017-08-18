<?php

namespace Hyper\Domain\Action;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * MiscAction
 *
 * @ORM\Table(name="misc_actions")
 * @ORM\Entity(repositoryClass="Hyper\DomainBundle\Repository\Action\DTMiscActionRepository")
 * @ExclusionPolicy("all")
 */
class MiscAction
{
    /**
     * @ORM\OneToOne(targetEntity="Action")
     * @ORM\JoinColumn(name="id", referencedColumnName="id")
     * @ORM\Id
     */
    private $action;


    /**
     * @var string
     *
     * @ORM\Column(name="device_id", type="string")
     * @Expose
     */
    private $deviceId;
    
    /**
     * @var string
     *
     * @ORM\Column(name="app_id", type="string", nullable=true)
     * @Expose
     */
    private $appId;


    /**
     * @var string
     *
     * @ORM\Column(name="application_id", type="string")
     * @Expose
     */
    private $applicationId;
    
    /**
     * @var string
     *
     * @ORM\Column(name="event_name", type="string")
     * @ORM\Id
     * @Expose
     */
    private $eventName;
    
    /**
     * @var string
     *
     * @ORM\Column(name="event_value", type="string", length=13107)
     * @ORM\Id
     * @Expose
     */
    private $eventValue;


    /**
     * @var integer
     *
     * @ORM\Column(name="event_time", type="integer")
     * @ORM\Id
     * @Expose
     */
    private $eventTime;
    

    /**
     * @var integer
     *
     * @ORM\Column(name="created", type="integer")
     * @Expose
     */
    private $created;    
    
    
    
    
    public function __construct()
    {
        $this->id = uniqid('',true);
        $this->created = time();
    }


    /**
     * Set deviceId
     *
     * @param string $deviceId
     * @return MiscAction
     */
    public function setDeviceId($deviceId)
    {
        $this->deviceId = $deviceId;

        return $this;
    }

    /**
     * Get deviceId
     *
     * @return string 
     */
    public function getDeviceId()
    {
        return $this->deviceId;
    }

    /**
     * Set appId
     *
     * @param string $appId
     * @return MiscAction
     */
    public function setAppId($appId)
    {
        $this->appId = $appId;

        return $this;
    }

    /**
     * Get appId
     *
     * @return string 
     */
    public function getAppId()
    {
        return $this->appId;
    }

    /**
     * Set applicationId
     *
     * @param string $applicationId
     * @return MiscAction
     */
    public function setApplicationId($applicationId)
    {
        $this->applicationId = $applicationId;

        return $this;
    }

    /**
     * Get applicationId
     *
     * @return string 
     */
    public function getApplicationId()
    {
        return $this->applicationId;
    }

    /**
     * Set eventName
     *
     * @param string $eventName
     * @return MiscAction
     */
    public function setEventName($eventName)
    {
        $this->eventName = $eventName;

        return $this;
    }

    /**
     * Get eventName
     *
     * @return string 
     */
    public function getEventName()
    {
        return $this->eventName;
    }

    /**
     * Set eventValue
     *
     * @param string $eventValue
     * @return MiscAction
     */
    public function setEventValue($eventValue)
    {
        $this->eventValue = serialize($eventValue);

        return $this;
    }

    /**
     * Get eventValue
     *
     * @return string 
     */
    public function getEventValue()
    {
        return unserialize($this->eventValue);
    }

    /**
     * Set eventTime
     *
     * @param integer $eventTime
     * @return MiscAction
     */
    public function setEventTime($eventTime)
    {
        $this->eventTime = $eventTime;

        return $this;
    }

    /**
     * Get eventTime
     *
     * @return integer 
     */
    public function getEventTime()
    {
        return $this->eventTime;
    }

    /**
     * Set created
     *
     * @param integer $created
     * @return MiscAction
     */
    public function setCreated($created)
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
     * Set action
     *
     * @param \Hyper\Domain\Action\Action $action
     * @return MiscAction
     */
    public function setAction(\Hyper\Domain\Action\Action $action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Get action
     *
     * @return \Hyper\Domain\Action\Action 
     */
    public function getAction()
    {
        return $this->action;
    }
}
