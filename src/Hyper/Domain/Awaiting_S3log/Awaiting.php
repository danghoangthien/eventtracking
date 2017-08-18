<?php

namespace Hyper\Domain\Awaiting_S3log;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * Metadata
 *
 * @ORM\Table(name="awaiting_s3_log")
 * @ORM\Entity(repositoryClass="Hyper\DomainBundle\Repository\Awaiting\DTAwaitingRepository")
 * @ExclusionPolicy("all")
 */
class Awaiting
{
    /**
     * @var string
     * @ORM\Column(name="id", type="string", options={"unsigned"=true})
     * @ORM\Id
     * @Expose
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="s3_log_file", type="string", length=65535)
     * @Expose
     */
    private $s3LogFile;
    
    /**
     * @var string
     *
     * @ORM\Column(name="app_id", type="string", length=65535)
     * @Expose
     */
    private $appId;
    
    /**
     * @var string
     *
     * @ORM\Column(name="s3_app_folder", type="string", length=255)
     * @Expose
     */
    private $s3AppFolder;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="event_type", type="integer")
     * @Expose
     */
    private $eventType;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer",options={"default"=1})
     * @Expose
     */
    private $status;
    
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
     * Set id
     *
     * @param string $id
     * @return Awaiting
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
     * Set s3LogFile
     *
     * @param string $s3LogFile
     * @return Awaiting
     */
    public function setS3LogFile($s3LogFile)
    {
        $this->s3LogFile = $s3LogFile;

        return $this;
    }

    /**
     * Get s3LogFile
     *
     * @return string 
     */
    public function getS3LogFile()
    {
        return $this->s3LogFile;
    }

    /**
     * Set appId
     *
     * @param string $appId
     * @return Awaiting
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
     * Set s3AppFolder
     *
     * @param string $s3AppFolder
     * @return Awaiting
     */
    public function setS3AppFolder($s3AppFolder)
    {
        $this->s3AppFolder = $s3AppFolder;

        return $this;
    }

    /**
     * Get s3AppFolder
     *
     * @return string 
     */
    public function getS3AppFolder()
    {
        return $this->s3AppFolder;
    }

    /**
     * Set eventType
     *
     * @param integer $eventType
     * @return Awaiting
     */
    public function setEventType($eventType)
    {
        $this->eventType = $eventType;

        return $this;
    }

    /**
     * Get eventType
     *
     * @return integer 
     */
    public function getEventType()
    {
        return $this->eventType;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return Awaiting
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set created
     *
     * @param integer $created
     * @return Awaiting
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
}
