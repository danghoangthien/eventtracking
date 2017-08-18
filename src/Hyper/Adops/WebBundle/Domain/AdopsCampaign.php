<?php

namespace Hyper\Adops\WebBundle\Domain;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Util\Inflector;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
* @ORM\Entity(repositoryClass="Hyper\Adops\WebBundle\DomainBundle\Repository\DTCampaignRepository")
* @ORM\Table(name="adops_campaigns")
*
* @author Carl Pham <vanca.vnn@gmail.com>
*/
class AdopsCampaign
{
    /**
     * @ORM\Column(type="string", name="id")
     * @ORM\Id
     */
    private $id;

    /**
     * @ORM\Column(name="name", type="string", length=255)
     *
     */
    private $name;
    
    /**
     * @ORM\Column(name="code", type="string", length=255)
     *
     */
    private $code;

    /**
     * @ORM\Column(name="tracking_url", type="string", nullable=true,  length=65535)
     *
     */
    private $trackingUrl;

    /**
     * @ORM\Column(name="payout", type="string")
     *
     */
    private $payout;
    
    /**
     * @ORM\Column(name="status", type="integer")
     *
     */
    private $status;
    
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

    public function __construct()
    {
        $this->id = uniqid('',true);
    }

    /**
     * Set id
     *
     * @param string $id
     * @return AdopsCampaign
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
     * Set App Name
     *
     * @param string $name
     * @return AdopsCampaign
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get App Name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * Set App Name
     *
     * @param string $code
     * @return AdopsCampaign
     */
    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }

    /**
     * Get App Name
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }
    
    /**
     * Set Tracking URL
     *
     * @param string $trackingUrl
     * @return AdopsCampaign
     */
    public function setTrackingUrl($trackingUrl)
    {
        $this->trackingUrl = $trackingUrl;
        return $this;
    }

    /**
     * Get App Name
     *
     * @return string
     */
    public function getTrackingUrl()
    {
        return $this->trackingUrl;
    }
    
    /**
     * Set Payout
     *
     * @param string $payout
     * @return AdopsCampaign
     */
    public function setPayout($payout)
    {
        $this->payout = $payout;
        return $this;
    }

    /**
     * Get Payout
     *
     * @return string
     */
    public function getPayout()
    {
        return $this->payout;
    }
    
    /**
     * Set Status
     *
     * @param integer $status
     * @return AdopsCampaign
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Get Status
     *
     * @return AdopsCampaign
     */
    public function getStatus()
    {
        return $this->status;
    }
    
    /**
     * Set Application
     * 
     * @return AdopsCampaign
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
     * @return AdopsCampaign
     */
    public function setPublisher(\Hyper\Adops\WebBundle\Domain\AdopsPublisher $publisher=null)
    {
        $this->publisher = $publisher;
        
        return $this;
    }
    
    /**
     * Get Publisher
     * 
     * @return AdopsCampaign Entity
     */
    public function getPublisher()
    {
        return $this->publisher;
    }

}