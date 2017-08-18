<?php

namespace Hyper\Adops\APIBundle\Domain;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Util\Inflector;

use Symfony\Component\Validator\Constraints as Assert;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
* @ORM\Entity(repositoryClass="Hyper\Adops\APIBundle\DomainBundle\Repository\DTLogRepository")
* @ORM\Table(name="adops_logs_2")
*
* @author Carl Pham <vanca.vnn@gmail.com>
*/
class AdopsLog
{
    /**
     * @ORM\Column(name="id", type="string")
     * @ORM\Id
     */
    private $id;

    /**
     * @ORM\Column(name="detail", type="string", nullable=true, length=65535)
     *
     */
    private $detail;
    
    /**
     * @ORM\Column(name="status", type="integer", nullable=true)
     *
     */
    private $status;
    
    /**
     * @ORM\Column(name="created", type="integer")
     *
     */
    private $created;
    
    /**
     * @ORM\Column(name="postback_id", type="string", nullable=true)
     * 
     */
    private $postbackId;
    
    /**
     * @ORM\Column(name="postback_url", type="string", nullable=true, length=65535)
     * 
     */
    private $postbackUrl;
    
    public function __construct()
    {
        $this->id = uniqid('',true);
    }

    /**
     * Set id
     *
     * @param string $id
     * @return AdopsLog
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
     * Set Detail
     *
     * @param string $detail
     * @return AdopsLog
     */
    public function setDetail($detail)
    {
        $this->detail = $detail;
        return $this;
    }

    /**
     * Get Detail
     *
     * @return string
     */
    public function getDetail()
    {
        return $this->detail;
    }
    
    /**
     * Set Status
     *
     * @param integer $status 
     * @return AdopsLog
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }
    
    /**
     * Get Status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }
    
    /**
     * Set Created
     *
     * @param integer $created 
     * @return AdopsLog
     */
    public function setCreated($created)
    {
        $this->created = $created;
        return $this;
    }
    
    /**
     * Get Created
     *
     * @return integer
     */
    public function getCreated()
    {
        return $this->created;
    }
    
    public function setPostbackId($postbackId)
    {
        $this->postbackId = $postbackId;
        return $this;
    }
    
    public function getPostbackId()
    {
        return $this->postbackId;
    }
    
    public function setPostbackUrl($postbackUrl)
    {
        $this->postbackUrl = $postbackUrl;
        return $this;
    }
    
    public function getPostbackUrl()
    {
        return $this->postbackUrl;
    }

}