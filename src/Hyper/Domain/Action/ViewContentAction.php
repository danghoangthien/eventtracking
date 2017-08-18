<?php

namespace Hyper\Domain\Action;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * ViewContentAction
 *
 * @ORM\Table(name="view_content_actions")
 * @ORM\Entity(repositoryClass="Hyper\DomainBundle\Repository\Action\DTViewContentActionRepository")
 * @ExclusionPolicy("all")
 */
class ViewContentAction
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
     * @ORM\Column(name="category_id", type="string")
     * @Expose
     */
    private $categoryId;

    /**
     * @var string
     *
     * @ORM\Column(name="content_id", type="string")
     * @Expose
     */
    private $contentId;
    
    /**
     * @var string
     * 
     * af_content_type from json log
     *
     * @ORM\Column(name="log_content_type", type="string", length=2048,nullable=true)
     * @Expose
     */
    private $logContentType;
    
    /**
     * @var string
     * 
     * af_content_id from json log
     *
     * @ORM\Column(name="log_content_id", type="string", length=2048,nullable=true)
     * @Expose
     */
    private $logContentId;
    
    /**
     * @var string
     *
     * @ORM\Column(name="metadata", type="string", length=13107)
     * @Expose
     */
    private $metadata;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="viewed_time", type="integer")
     * @Expose
     */
    private $viewedTime;
    

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
     * Set deviceId
     *
     * @param string $deviceId
     * @return ViewContentAction
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
     * @return ViewContentAction
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
     * @return ViewContentAction
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
     * Set categoryId
     *
     * @param string $categoryId
     * @return ViewContentAction
     */
    public function setCategoryId($categoryId)
    {
        $this->categoryId = $categoryId;

        return $this;
    }

    /**
     * Get categoryId
     *
     * @return string 
     */
    public function getCategoryId()
    {
        return $this->categoryId;
    }

    /**
     * Set contentId
     *
     * @param string $contentId
     * @return ViewContentAction
     */
    public function setContentId($contentId)
    {
        $this->contentId = $contentId;

        return $this;
    }

    /**
     * Get contentId
     *
     * @return string 
     */
    public function getContentId()
    {
        return $this->contentId;
    }
    
    /**
     * Set logContentType
     *
     * @param string $logContentType
     * @return ViewContentAction
     */
    public function setLogContentType($logContentType)
    {
        $this->logContentType = $logContentType;

        return $this;
    }

    /**
     * Get logContentType
     *
     * @return string 
     */
    public function getViewLogContentType()
    {
        return $this->logContentType;
    }
    
    /**
     * Set logContentId
     *
     * @param string $logContentId
     * @return ViewContentAction
     */
    public function setLogContentId($logContentId)
    {
        $this->logContentId = $logContentId;

        return $this;
    }

    /**
     * Get logContentId
     *
     * @return string 
     */
    public function getLogContentId()
    {
        return $this->logContentId;
    }
    
    /**
     * Set metadata
     *
     * @param string $metadata
     * @return ViewContentAction
     */
    public function setMetadata($metadata)
    {
        $this->metadata = serialize($metadata);

        return $this;
    }

    /**
     * Get metadata
     *
     * @return string 
     */
    public function getMetadata()
    {
        return unserialize($this->metadata);
    }

    /**
     * Set viewedTime
     *
     * @param integer $viewedTime
     * @return ViewContentAction
     */
    public function setViewedTime($viewedTime)
    {
        $this->viewedTime = $viewedTime;

        return $this;
    }

    /**
     * Get viewedTime
     *
     * @return integer 
     */
    public function getViewedTime()
    {
        return $this->viewedTime;
    }

    /**
     * Set created
     *
     * @param integer $created
     * @return ViewContentAction
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
     * @return ViewContentAction
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
