<?php

namespace Hyper\Adops\WebBundle\Domain;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Util\Inflector;

use Symfony\Component\Validator\Constraints as Assert;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
* @ORM\Entity(repositoryClass="Hyper\Adops\WebBundle\DomainBundle\Repository\DTReportRepository")
* @ORM\Table(name="adops_reports")
*
* @author Carl Pham <vanca.vnn@gmail.com>
*/
class AdopsReport
{
     /**
     * @ORM\Column(name="id", type="string")
     * @ORM\Id
     */
    private $id;
    
    /**
     * @ORM\Column(name="event_type", type="string", nullable=true, length=65535)
     *
     */
    private $eventType;
    
    /**
     * @ORM\Column(name="created", type="integer")
     *
     */
    private $created;
    
    /**
     * @ORM\Column(name="app_id", type="string", nullable=true, length=65535)
     *
     */
    private $appId;
    
    /**
     * @ORM\Column(name="site_id", type="string", nullable=true, length=65535)
     *
     */
    private $siteId;
    
    /**
     * @ORM\Column(name="c", type="string", nullable=true, length=65535)
     *
     */
    private $c;
    
    /**
     * @ORM\Column(name="campaign_payout", type="string", nullable=true, length=65535)
     *
     */
    private $campaignPayout;
    
    /**
     * @ORM\Column(name="postback_url", type="string", nullable=true, length=65535)
     * 
     */
    private $postbackUrl;
    
    /**
     * @ORM\Column(name="status", type="integer", nullable=true)
     *
     */
    private $status;
    
    /**
     * @ORM\Column(name="af_adset", type="string", nullable=true, length=65535)
     * 
     */
    private $afAdset;
    
    /**
     * @ORM\Column(name="af_sub1", type="string", nullable=true, length=65535)
     * 
     */
    private $afSub1;
    
    /**
     * @ORM\Column(name="event_name", type="string", nullable=true, length=255)
     * 
     */
    private $eventName;
    
    /**
     * @ORM\Column(name="profile_id", type="string", nullable=true)
     * 
     */
    private $profileId;
    
    public function __construct()
    {
        $this->id = uniqid('',true);
    }
    
    /**
     * Set id
     *
     * @param string $id
     * @return AdopsReport
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
    
    public function setEventType($eventType)
    {
        $this->eventType = $eventType;
        return $this;
    }
    
    public function getEventType()
    {
        return $this->eventType;
    }
    
    /**
     * Set Created
     *
     * @param integer $created 
     * @return AdopsReport
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
    
    public function setAppId($appId)
    {
        $this->appId = $appId;
        return $this;
    }
    
    public function getAppId()
    {
        return $this->appId;
    }
    
    public function setSiteId($siteId)
    {
        $this->siteId = $siteId;
        return $this;
    }
    
    public function getSiteId()
    {
        return $this->siteId;
    }
    
    public function setC($c)
    {
        $this->c = $c;
        return $this;
    }
    
    public function getC()
    {
        return $this->c;
    }
    
    public function setCampaignPayout($campaignPayout)
    {
        $this->campaignPayout = $campaignPayout;
        return $this;
    }
    
    public function getCampaignPayout()
    {
        return $this->campaignPayout;
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
    
    /**
     * Set Status
     *
     * @param integer $status 
     * @return AdopsReport
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
     * Set AfAdset
     *
     * @param string $afAdset 
     * @return AdopsReport
     */
    public function setAfAdset($afAdset)
    {
        $this->afAdset = $afAdset;
        return $this;
    }
    
    /**
     * Get AfAdset
     *
     * @return string
     */
    public function getAfAdset()
    {
        return $this->afAdset;
    }
    
    /**
     * Set AfSub1
     *
     * @param string $afSub1
     * @return AdopsReport
     */
    public function setAfSub1($afSub1)
    {
        $this->afSub1 = $afSub1;
        return $this;
    }
    
    /**
     * Get AfSub1
     *
     * @return string
     */
    public function getAfSub1()
    {
        return $this->afSub1;
    }
    
    /**
     * Set EventName
     *
     * @param string $eventName
     * @return AdopsReport
     */
    public function setEventName($eventName)
    {
        $this->eventName = $eventName;
        return $this;
    }
    
    /**
     * Get EventName
     *
     * @return string
     */
    public function getEventName()
    {
        return $this->eventName;
    }
    
    /**
     * Set Profile Id
     *
     * @param string $profileId
     * @return AdopsReport
     */
    public function setProfileId($profileId)
    {
        $this->profileId = $profileId;
        return $this;
    }
    
    /**
     * Get Profile Id
     *
     * @return string
     */
    public function getProfileId()
    {
        return $this->profileId;
    }
    
    public function setData($data)
    {
        if (!empty($data)) {
            foreach ($data as $fieldName => $value) {
                $this->set($fieldName, $value);
            }
        }

        return $this;
    }

    public function get($fieldName)
    {
        $realFieldName = lcfirst(Inflector::classify($fieldName));
        return $this->$realFieldName;
    }

    public function set($fieldName, $value)
    {
        $realFieldName = lcfirst(Inflector::classify($fieldName));
        $this->$realFieldName = $value;
    }
}