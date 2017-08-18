<?php

namespace Hyper\Domain\Action;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * LaunchAction
 *
 * @ORM\Table(name="launch_actions")
 * @ORM\Entity(repositoryClass="Hyper\DomainBundle\Repository\Action\DTLaunchActionRepository")
 * @ExclusionPolicy("all")
 */
class LaunchAction
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
     * @var integer
     *
     * @ORM\Column(name="launch_time", type="integer")
     * @ORM\Id
     * @Expose
     */
    private $launchedTime;
    

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
     * @return LaunchAction
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
     * @return LaunchAction
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
     * @return LaunchAction
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
     * Set launchedTime
     *
     * @param integer $launchedTime
     * @return LaunchAction
     */
    public function setLaunchedTime($launchedTime)
    {
        $this->launchedTime = $launchedTime;

        return $this;
    }

    /**
     * Get launchedTime
     *
     * @return integer 
     */
    public function getLaunchededTime()
    {
        return $this->launchedTime;
    }

    /**
     * Set created
     *
     * @param integer $created
     * @return LaunchAction
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
     * @return LaunchAction
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

    /**
     * Get launchedTime
     *
     * @return integer 
     */
    public function getLaunchedTime()
    {
        return $this->launchedTime;
    }
}
