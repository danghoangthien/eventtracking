<?php

namespace Hyper\Domain\Frm;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * Frm
 *
 * @ORM\Table(name="frm")
 * @ORM\Entity(repositoryClass="Hyper\DomainBundle\Repository\Frm\DTFrmRepository")
 * @ExclusionPolicy("all")
 */
class Frm
{
    const UNCATEGORIZED = '000000000000';
    
    /**
     * @var string
     * @ORM\Column(name="id", type="string")
     * @ORM\Id
     * @Expose
     */
    private $id;


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
     * @ORM\Column(name="app_id", type="string")
     * @Expose
     */
    private $appId;
    
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
     * A.K.A app_industry
     *
     * @ORM\Column(name="account_type", type="integer")
     * @Expose
     */
    private $accountType;
    
    /**
     * @var string
     * 
     * could be transaction id,add to cart id,add to wishlist id
     *
     * @ORM\Column(name="reference_event_id", type="string")
     * @Expose
     */
    private $referenceEventId;
    
    /**
     * @var string
     * 
     * comma separated item codes that added to cart|added to wishlist|purchased
     *
     * @ORM\Column(name="reference_item_codes", type="string",length=13107)
     * @Expose
     */
    private $referenceItemCodes;


    /**
     * @var float
     *
     * @ORM\Column(name="amount", type="float")
     * @Expose
     */
    private $amount;
    
    /**
     * @var string
     *
     * @ORM\Column(name="base_currency", type="string")
     * @Expose
     */
    private $baseCurrency;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="event_time", type="integer")
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
     * Set id
     *
     * @param string $id
     * @return Frm
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
     * Set deviceId
     *
     * @param string $deviceId
     * @return Frm
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
     * @return Frm
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
     * Set eventType
     *
     * @param integer $eventType
     * @return Frm
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
     * Set accountType
     *
     * @param integer $accountType
     * @return Frm
     */
    public function setAccountType($accountType)
    {
        $this->accountType = $accountType;

        return $this;
    }

    /**
     * Get accountType
     *
     * @return integer 
     */
    public function getAccountType()
    {
        return $this->accountType;
    }

    /**
     * Set referenceEventId
     *
     * @param string $referenceEventId
     * @return Frm
     */
    public function setReferenceEventId($referenceEventId)
    {
        $this->referenceEventId = $referenceEventId;

        return $this;
    }

    /**
     * Get referenceEventId
     *
     * @return string 
     */
    public function getReferenceEventId()
    {
        return $this->referenceEventId;
    }

    /**
     * Set referenceItemCodes
     *
     * @param string $referenceItemCodes
     * @return Frm
     */
    public function setReferenceItemCodes($referenceItemCodes)
    {
        $this->referenceItemCodes = serialize($referenceItemCodes);

        return $this;
    }

    /**
     * Get referenceItemCodes
     *
     * @return string 
     */
    public function getReferenceItemCodes()
    {
        return unserialize($this->referenceItemCodes);
    }

    /**
     * Set amount
     *
     * @param float $amount
     * @return Frm
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return float 
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set baseCurrency
     *
     * @param string $baseCurrency
     * @return Frm
     */
    public function setBaseCurrency($baseCurrency)
    {
        $this->baseCurrency = $baseCurrency;

        return $this;
    }

    /**
     * Get baseCurrency
     *
     * @return string 
     */
    public function getBaseCurrency()
    {
        return $this->baseCurrency;
    }

    /**
     * Set eventTime
     *
     * @param integer $eventTime
     * @return Frm
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
     * @return Frm
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
