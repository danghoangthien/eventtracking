<?php

namespace Hyper\Adops\WebBundle\Domain;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Util\Inflector;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
* @ORM\Entity(repositoryClass="Hyper\Adops\WebBundle\DomainBundle\Repository\DTPostbackRepository")
* @ORM\Table(name="adops_postbacks")
*
* @author Carl Pham <vanca.vnn@gmail.com>
*/
class AdopsPostback
{
    /**
     * @ORM\Column(type="string", name="id")
     * @ORM\Id
     */
    private $id;

    /**
     * @ORM\Column(name="event_type", type="string", length=255)
     *
     */
    private $eventType;

    /**
     * @ORM\Column(name="postback_url", type="string", nullable=true, length=65535)
     *
     */
    private $postbackUrl;
    
    /**
     * @ORM\ManyToOne(targetEntity="Hyper\Adops\WebBundle\Domain\AdopsApplication", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn()
     * @Expose
     */
    private $application;
    
    /**
     * @ORM\ManyToOne(targetEntity="Hyper\Adops\WebBundle\Domain\AdopsPublisher", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn()
     * @Expose
     */
    private $publisher;
    
    /**
     * @ORM\ManyToOne(targetEntity="Hyper\Adops\WebBundle\Domain\AdopsCampaign", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn()
     * @Expose
     */
    private $campaign;
    
    /**
     * @ORM\ManyToOne(targetEntity="Hyper\Adops\WebBundle\Domain\AdopsInappevent", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn()
     * @Expose
     */
    private $inappevent;

    public function __construct()
    {
        $this->id = uniqid('',true);
    }

    /**
     * Set id
     *
     * @param string $id
     * @return AdopsPostback
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
     * Set Event Type
     *
     * @param string $name
     * @return AdopsPostback
     */
    public function setEventType($eventType)
    {
        $this->eventType = $eventType;
        return $this;
    }

    /**
     * Get Event Type
     *
     * @return string
     */
    public function getEventType()
    {
        return $this->eventType;
    }
    
    /**
     * Set Postback URL
     *
     * @param string $postbackUrl
     * @return AdopsPostback
     */
    public function setPostbackUrl($postbackUrl)
    {
        $this->postbackUrl = $postbackUrl;
        return $this;
    }

    /**
     * Get Postback URL
     *
     * @return string
     */
    public function getPostbackUrl()
    {
        return $this->postbackUrl;
    }
    
    /**
     * Set Application
     * 
     * @return $postbackUrl
     */
    public function setApplication(\Hyper\Adops\WebBundle\Domain\AdopsApplication $application=null)
    {
        $this->application = $application;
        
        return $this;
    }
    
    /**
     * Get Application
     * 
     * @return AdopsCampaign Entity
     */
    public function getApplication()
    {
        return $this->application;
    }
    
    /**
     * Set Publisher
     * 
     * @return $postbackUrl
     */
    public function setPublisher(\Hyper\Adops\WebBundle\Domain\AdopsPublisher $publisher=null)
    {
        $this->publisher = $publisher;
        
        return $this;
    }
    
    /**
     * Get Publisher
     * 
     * @return AdopsPublisher Entity
     */
    public function getPublisher()
    {
        return $this->publisher;
    }
    
    /**
     * Set Publisher
     * 
     * @return $postbackUrl
     */
    public function setCampaign(\Hyper\Adops\WebBundle\Domain\AdopsCampaign $campaign=null)
    {
        $this->campaign = $campaign;
        
        return $this;
    }
    
    /**
     * Get Publisher
     * 
     * @return AdopsCampaign Entity
     */
    public function getCampaign()
    {
        return $this->campaign;
    }
    
    /**
     * Set In app event
     * 
     * @return AdopsPostback
     */
    public function setInappevent(\Hyper\Adops\WebBundle\Domain\AdopsInappevent $inappevent=null)
    {
        $this->inappevent = $inappevent;
        
        return $this;
    }
    
    /**
     * Get Publisher
     * 
     * @return AdopsInappevent Entity
     */
    public function getInappevent()
    {
        return $this->inappevent;
    }

}