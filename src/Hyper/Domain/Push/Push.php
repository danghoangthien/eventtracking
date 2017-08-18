<?php

namespace Hyper\Domain\Push;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * Push
 *
 * @ORM\Table(name="push_notification")
 * @ORM\Entity(repositoryClass="Hyper\DomainBundle\Repository\Push\DTPushRepository")
 * @ExclusionPolicy("all")
 */
class Push
{
    public function __construct()
    {
        $this->id = uniqid('',true);    
        $this->updated = strtotime(date('Y-m-d h:i:s'));
    }
    
    /**
     * @ORM\Column(name="id", type="string", length=255, nullable=false)")
     * @ORM\Id
     * @Expose
     */
    protected $id;
    
    /**
     * @var string
     *
     * @ORM\Column(name="app_name", type="string", length=255, nullable=false)
     * @Expose
     */
    private $appName;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     * @Expose
     */
    private $title;
    
    /**
     * @var string
     *
     * @ORM\Column(name="message", type="string",nullable=false, length=255)
     * @Expose
     */
    private $message;
    
    /**
     * @var string
     *
     * @ORM\Column(name="device_token", type="string",nullable=false, length=255)
     * @Expose
     */
    private $deviceToken;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="success", type="integer", nullable=true)
     * @Expose
     */
    private $success;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="fail", type="integer", nullable=true)
     * @Expose
     */
    private $fail;
    
    /**
     * @var string
     *
     * @ORM\Column(name="callback", type="string",nullable=true, length=255)
     * @Expose
     */
    private $callback;
     
    /**
     * @var string
     *
     * @ORM\Column(name="json_file", type="string", length=13107, nullable=true)
     * @Expose
     */
    private $jsonFile;
    
    /**
     * @var string
     *
     * @ORM\Column(name="img_path", type="string",nullable=true, length=255)
     * @Expose
     */
    private $imgPath;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="created", type="integer", nullable=false,)
     * @Expose
     */
    private $created;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="updated", type="integer", nullable=false,)
     * @Expose
     */
    private $updated;

    /**
     * Set id
     *
     * @param string $id
     * @return Push
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
     * Set appName
     *
     * @param string $appName
     * @return Push
     */
    public function setAppName($appName)
    {
        $this->appName = $appName;

        return $this;
    }

    /**
     * Get appName
     *
     * @return string 
     */
    public function getAppName()
    {
        return $this->appName;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return Push
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set message
     *
     * @param string $message
     * @return Push
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message
     *
     * @return string 
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set deviceToken
     *
     * @param string $deviceToken
     * @return Push
     */
    public function setDeviceToken($deviceToken)
    {
        $this->deviceToken = $deviceToken;

        return $this;
    }

    /**
     * Get deviceToken
     *
     * @return string 
     */
    public function getDeviceToken()
    {
        return $this->deviceToken;
    }

    /**
     * Set success
     *
     * @param integer $success
     * @return Push
     */
    public function setSuccess($success)
    {
        $this->success = $success;

        return $this;
    }

    /**
     * Get success
     *
     * @return integer 
     */
    public function getSuccess()
    {
        return $this->success;
    }

    /**
     * Set fail
     *
     * @param integer $fail
     * @return Push
     */
    public function setFail($fail)
    {
        $this->fail = $fail;

        return $this;
    }

    /**
     * Get fail
     *
     * @return integer 
     */
    public function getFail()
    {
        return $this->fail;
    }

    /**
     * Set callback
     *
     * @param string $callback
     * @return Push
     */
    public function setCallback($callback)
    {
        $this->callback = $callback;

        return $this;
    }

    /**
     * Get callback
     *
     * @return string 
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * Set jsonFile
     *
     * @param string $jsonFile
     * @return Push
     */
    public function setJsonFile($jsonFile)
    {
        $this->jsonFile = $jsonFile;

        return $this;
    }

    /**
     * Get jsonFile
     *
     * @return string 
     */
    public function getJsonFile()
    {
        return $this->jsonFile;
    }

    /**
     * Set imgPath
     *
     * @param string $imgPath
     * @return Push
     */
    public function setImgPath($imgPath)
    {
        $this->imgPath = $imgPath;

        return $this;
    }

    /**
     * Get imgPath
     *
     * @return string 
     */
    public function getImgPath()
    {
        return $this->imgPath;
    }

    /**
     * Set created
     *
     * @param integer $created
     * @return Push
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
     * Set updated
     *
     * @param integer $updated
     * @return Push
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return integer 
     */
    public function getUpdated()
    {
        return $this->updated;
    }
}
