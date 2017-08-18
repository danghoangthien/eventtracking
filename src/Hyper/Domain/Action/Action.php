<?php

namespace Hyper\Domain\Action;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * Action
 *
 * @ORM\Table(name="actions")
 * @ORM\Entity(repositoryClass="Hyper\DomainBundle\Repository\Action\DTActionRepository")
 * @ExclusionPolicy("all")
 */
class Action
{
    const BEHAVIOURS = array(
        'INSTALL_BEHAVIOUR_ID' => 1,
        'ADD_TO_WISHLIST_BEHAVIOUR_ID' => 2,
        'ADD_TO_CART_BEHAVIOUR_ID' => 3,
        'PURCHASE_BEHAVIOUR_ID' => 4,
        'LAUNCH_BEHAVIOUR_ID' => 5,
        'SHARE_CONTENT_BEHAVIOUR_ID' => 6,
        'TUTORIAL_BEHAVIOUR_ID' => 7,
        'SEARCH_BEHAVIOUR_ID' => 8,
        'VIEW_CONTENT_BEHAVIOUR_ID' => 9,
        'USER_REGISTERED_BEHAVIOUR_ID' => 10,
        'LOGIN_BEHAVIOUR_ID' => 11,
        'ADD_PAYMENT_INFO_BEHAVIOUR_ID' => 12,
        'TRAVEL_BOOKING_BEHAVIOUR_ID' => 13
        
    );
    
    const ACTION_TYPES = array(
        'INSTALL_ACTION_TYPE' => 1,
        'IN_APP_EVENT_ACTION_TYPE' => 2
    );
    
    const PROVIDERS = [
        'DEPRECATED_APPSFLYER'=>1,
        'HASOFFER'=>2,
        'HYPERGROWTH'=>3,
        'APPSFLYER'=>4,
        'KOCHAVA'=>5,
        'ADJUST'=>6
    ];
    
    /**
     * @var string
     * @ORM\Column(name="id", type="string")
     * @ORM\Id
     * @Expose
     */
    private $id;


    /**
     * @ORM\ManyToOne(targetEntity="Hyper\Domain\Device\Device", fetch="EXTRA_LAZY", inversedBy="actions")
     * @ORM\JoinColumn(name="device_id", referencedColumnName="id")
     * @Expose
     */
    private $device;
    
    /**
     * @var string
     *
     * @ORM\Column(name="app_id", type="string", nullable=true)
     * @Expose
     */
    private $appId;


    /**
     * @ORM\ManyToOne(targetEntity="Hyper\Domain\Application\Application", fetch="EXTRA_LAZY", inversedBy="actions")
     * @ORM\JoinColumn(name="application_id", referencedColumnName="id")
     * @Expose
     */
    private $application;


    /**
     * @var integer
     *
     * @ORM\Column(name="action_type", type="integer")
     * @Expose
     */
    private $actionType;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="behaviour_id", type="integer")
     * @Expose
     */
    private $behaviourId;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="provider_id", type="integer", options={"default" = 0})
     * @Expose
     */
    private $providerId;
    
    /**
     * @var string
     *
     * @ORM\Column(name="s3_log_file", type="string",nullable=true)
     * @Expose
     */
    private $s3LogFile;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="happened_at", type="integer")
     * @Expose
     */
    private $happenedAt;

    /**
     * @var integer
     *
     * @ORM\Column(name="created", type="integer")
     * @Expose
     */
    private $created;  
    
    /**
     * @var float
     *
     * @ORM\Column(name="af_revenue", type="float", nullable=true)
     * @Expose
     */
    private $afRevenue;
    
    /**
     * @var float
     *
     * @ORM\Column(name="af_price", type="float", nullable=true)
     * @Expose
     */
    private $afPrice;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="af_level", type="integer", nullable=true)
     * @Expose
     */
    private $afLevel;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="af_success", type="boolean", nullable=true)
     * @Expose
     */
    private $afSuccess;
    
    /**
     * @var string
     *
     * @ORM\Column(name="af_content_type", type="string", nullable=true)
     * @Expose
     */
    private $afContentType;
    
    /**
     * @var string
     *
     * @ORM\Column(name="af_content_list", type="string", nullable=true)
     * @Expose
     */
    private $afContentList;
    
    /**
     * @var string
     *
     * @ORM\Column(name="af_content_id", type="string", nullable=true)
     * @Expose
     */
    private $afContentId;
    
    /**
     * @var string
     *
     * @ORM\Column(name="af_currency", type="string", nullable=true)
     * @Expose
     */
    private $afCurrency;
    
    /**
     * @var string
     *
     * @ORM\Column(name="af_registration_method", type="string", nullable=true)
     * @Expose
     */
    private $afRegistrationMethod;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="af_quantity", type="integer", nullable=true)
     * @Expose
     */
    private $afQuantity;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="af_payment_info_available", type="boolean", nullable=true)
     * @Expose
     */
    private $afPaymentInfoAvailable;
    
    /**
     * @var float
     *
     * @ORM\Column(name="af_rating_value", type="float", nullable=true)
     * @Expose
     */
    private $afRatingValue;
    
    /**
     * @var float
     *
     * @ORM\Column(name="af_max_rating_value", type="float", nullable=true)
     * @Expose
     */
    private $afMaxRatingValue;
    
    /**
     * @var string
     *
     * @ORM\Column(name="af_search_string", type="string", nullable=true)
     * @Expose
     */
    private $afSearchString;
    
    /**
     * @var string
     *
     * @ORM\Column(name="af_description", type="string", nullable=true)
     * @Expose
     */
    private $afDescription;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="af_score", type="integer", nullable=true)
     * @Expose
     */
    private $afScore;
    
    /**
     * @var string
     *
     * @ORM\Column(name="af_destination_a", type="string", nullable=true)
     * @Expose
     */
    private $afDestinationA;
    
    /**
     * @var string
     *
     * @ORM\Column(name="af_destination_b", type="string", nullable=true)
     * @Expose
     */
    private $afDestinationB;
    
    /**
     * @var string
     *
     * @ORM\Column(name="af_class", type="string", nullable=true)
     * @Expose
     */
    private $afClass;
    
    /**
     * @var string
     *
     * @ORM\Column(name="af_date_a", type="string", nullable=true)
     * @Expose
     */
    private $afDateA;
    
    /**
     * @var string
     *
     * @ORM\Column(name="af_date_b", type="string", nullable=true)
     * @Expose
     */
    private $afDateB;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="af_event_start", type="integer", nullable=true)
     * @Expose
     */
    private $afEventStart;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="af_event_end", type="integer", nullable=true)
     * @Expose
     */
    private $afEventEnd;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="af_lat", type="integer", nullable=true)
     * @Expose
     */
    private $afLat;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="af_long", type="integer", nullable=true)
     * @Expose
     */
    private $afLong;
    
    /**
     * @var string
     *
     * @ORM\Column(name="af_customer_user_id", type="string", nullable=true)
     * @Expose
     */
    private $afCustomerUserId;
    
    /**
     * @var string
     *
     * @ORM\Column(name="af_validated", type="string", nullable=true)
     * @Expose
     */
    private $afValidated;
    
    /**
     * @var string
     *
     * @ORM\Column(name="af_receipt_id", type="string", nullable=true)
     * @Expose
     */
    private $afReceiptId;
    
    /**
     * @var string
     *
     * @ORM\Column(name="af_param_1", type="string", nullable=true)
     * @Expose
     */
    private $afParam1;
    
    /**
     * @var string
     *
     * @ORM\Column(name="af_param_2", type="string", nullable=true)
     * @Expose
     */
    private $afParam2;
    
    /**
     * @var string
     *
     * @ORM\Column(name="af_param_3", type="string", nullable=true)
     * @Expose
     */
    private $afParam3;
    
    /**
     * @var string
     *
     * @ORM\Column(name="af_param_4", type="string", nullable=true)
     * @Expose
     */
    private $afParam4;
    
    /**
     * @var string
     *
     * @ORM\Column(name="af_param_5", type="string", nullable=true)
     * @Expose
     */
    private $afParam5;
    
    /**
     * @var string
     *
     * @ORM\Column(name="af_param_6", type="string", nullable=true)
     * @Expose
     */
    private $afParam6;
    
    /**
     * @var string
     *
     * @ORM\Column(name="af_param_7", type="string", nullable=true)
     * @Expose
     */
    private $afParam7;
    
    /**
     * @var string
     *
     * @ORM\Column(name="af_param_8", type="string", nullable=true)
     * @Expose
     */
    private $afParam8;
    
    /**
     * @var string
     *
     * @ORM\Column(name="af_param_9", type="string", nullable=true)
     * @Expose
     */
    private $afParam9;
    
    /**
     * @var string
     *
     * @ORM\Column(name="af_param_10", type="string", nullable=true)
     * @Expose
     */
    private $afParam10;
    
    /**
     * @var string
     *
     * @ORM\Column(name="event_value_text", type="string", nullable=true)
     * @Expose
     */
    private $eventValueText;
    
    /**
     * @var string
     *
     * @ORM\Column(name="event_name", type="string")
     * @Expose
     */
    private $eventName;
    
    
    
    public function __construct($actionId = null)
    {
        if (!empty($actionId)) {
            $this->id = $actionId;
        } else {
            $this->id = uniqid('',true);
        }
        $this->created = time();
    }
    

    /**
     * Set id
     *
     * @param string $id
     * @return Action
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
     * Set actionType
     *
     * @param integer $actionType
     * @return Action
     */
    public function setActionType ($actionType)
    {
        $this->actionType = $actionType;

        return $this;
    }

    /**
     * Get actionType
     *
     * @return integer 
     */
    public function getActionType()
    {
        return $this->actionType;
    }

    /**
     * Set behaviourId
     *
     * @param integer $behaviourId
     * @return Action
     */
    public function setBehaviourId ($behaviourId)
    {
        $this->behaviourId = $behaviourId;

        return $this;
    }

    /**
     * Get behaviourId
     *
     * @return integer 
     */
    public function getBehaviourId()
    {
        return $this->behaviourId;
    }

    /**
     * Set happenedAt
     *
     * @param integer $happenedAt
     * @return Action
     */
    public function setHappenedAt ($happenedAt)
    {
        $this->happenedAt = $happenedAt;

        return $this;
    }

    /**
     * Get happenedAt
     *
     * @return integer 
     */
    public function getHappenedAt()
    {
        return $this->happenedAt;
    }

    /**
     * Set created
     *
     * @param integer $created
     * @return Action
     */
    public function setCreated ($created)
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
     * Set device
     *
     * @param \Hyper\Domain\Device\Device $device
     * @return Action
     */
    public function setDevice(\Hyper\Domain\Device\Device $device = null)
    {
        $this->device = $device;

        return $this;
    }

    /**
     * Get device
     *
     * @return \Hyper\Domain\Device\Device 
     */
    public function getDevice()
    {
        return $this->device;
    }

    /**
     * Set providerId
     *
     * @param integer $providerId
     * @return Action
     */
    public function setProviderId($providerId)
    {
        $this->providerId = $providerId;

        return $this;
    }

    /**
     * Get providerId
     *
     * @return integer 
     */
    public function getProviderId()
    {
        return $this->providerId;
    }
    
    /**
     * Set appId
     *
     * @param string $appId
     * @return Action
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
     * Set application
     *
     * @param \Hyper\Domain\Application\Application $application
     * @return Action
     */
    public function setApplication(\Hyper\Domain\Application\Application $application = null)
    {
        $this->application = $application;

        return $this;
    }

    /**
     * Get application
     *
     * @return \Hyper\Domain\Application\Application 
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * Set s3LogFile
     *
     * @param string $s3LogFile
     * @return Action
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
     * Set afRevenue
     *
     * @param string $afRevenue
     * @return Action
     */
    public function setAfRevenue($afRevenue)
    {
        $this->afRevenue = $afRevenue;

        return $this;
    }
    
    /**
     * Get afRevenue
     *
     * @return string 
     */
    public function getAfRevenue()
    {
        return $this->afRevenue;
    }
    
    /**
     * Set afPrice
     *
     * @param string $afPrice
     * @return Action
     */
    public function setAfPrice($afPrice)
    {
        $this->afPrice = $afPrice;

        return $this;
    }
    
    /**
     * Get afPrice
     *
     * @return string 
     */
    public function getAfPrice()
    {
        return $this->afPrice;
    }
    
    /**
     * Set afLevel
     *
     * @param string $afLevel
     * @return Action
     */
    public function setAfLevel($afLevel)
    {
        $this->afLevel = $afLevel;

        return $this;
    }
    
    /**
     * Get afLevel
     *
     * @return string 
     */
    public function getAfLevel()
    {
        return $this->afLevel;
    }
    
    /**
     * Set afSuccess
     *
     * @param string $afSuccess
     * @return Action
     */
    public function setAfSuccess($afSuccess)
    {
        $this->afSuccess = $afSuccess;

        return $this;
    }
    
    /**
     * Get afSuccess
     *
     * @return string 
     */
    public function getAfSuccess()
    {
        return $this->afSuccess;
    }
    
    /**
     * Set afContentType
     *
     * @param string $afContentType
     * @return Action
     */
    public function setAfContentType($afContentType)
    {
        $this->afContentType = $afContentType;

        return $this;
    }
    
    /**
     * Get afContentType
     *
     * @return string 
     */
    public function getAfContentType()
    {
        return $this->afContentType;
    }
    
    /**
     * Set afContentList
     *
     * @param string $afContentList
     * @return Action
     */
    public function setAfContentList($afContentList)
    {
        $this->afContentList = $afContentList;

        return $this;
    }
    
    /**
     * Get afContentList
     *
     * @return string 
     */
    public function getAfContentList()
    {
        return $this->afContentList;
    }
    
    /**
     * Set afContentId
     *
     * @param string $afContentId
     * @return Action
     */
    public function setAfContentId($afContentId)
    {
        $this->afContentId = $afContentId;

        return $this;
    }
    
    /**
     * Get afContentId
     *
     * @return string 
     */
    public function getAfContentId()
    {
        return $this->afContentId;
    }
    
    /**
     * Set afCurrency
     *
     * @param string $afCurrency
     * @return Action
     */
    public function setAfCurrency($afCurrency)
    {
        $this->afCurrency = $afCurrency;

        return $this;
    }
    
    /**
     * Get afCurrency
     *
     * @return string 
     */
    public function getAfCurrency()
    {
        return $this->afCurrency;
    }
    
    /**
     * Set afRegistrationMethod
     *
     * @param string $afRegistrationMethod
     * @return Action
     */
    public function setAfRegistrationMethod($afRegistrationMethod)
    {
        $this->afRegistrationMethod = $afRegistrationMethod;

        return $this;
    }
    
    /**
     * Get afRegistrationMethod
     *
     * @return string 
     */
    public function getAfRegistrationMethod()
    {
        return $this->afRegistrationMethod;
    }
    
    /**
     * Set afQuantity
     *
     * @param string $afQuantity
     * @return Action
     */
    public function setAfQuantity($afQuantity)
    {
        $this->afQuantity = $afQuantity;

        return $this;
    }
    
    /**
     * Get afQuantity
     *
     * @return string 
     */
    public function getAfQuantity()
    {
        return $this->afQuantity;
    }
    
    /**
     * Set afPaymentInfoAvailable
     *
     * @param string $afPaymentInfoAvailable
     * @return Action
     */
    public function setAfPaymentInfoAvailable($afPaymentInfoAvailable)
    {
        $this->afPaymentInfoAvailable = $afPaymentInfoAvailable;

        return $this;
    }
    
    /**
     * Get afPaymentInfoAvailable
     *
     * @return string 
     */
    public function getAfPaymentInfoAvailable()
    {
        return $this->afPaymentInfoAvailable;
    }
    
    /**
     * Set afRatingValue
     *
     * @param string $afRatingValue
     * @return Action
     */
    public function setAfRatingValue($afRatingValue)
    {
        $this->afRatingValue = $afRatingValue;

        return $this;
    }
    
    /**
     * Get afRatingValue
     *
     * @return string 
     */
    public function getAfRatingValue()
    {
        return $this->afRatingValue;
    }
    
    /**
     * Set afMaxRatingValue
     *
     * @param string afMaxRatingValue
     * @return Action
     */
    public function setAfMaxRatingValue($afMaxRatingValue)
    {
        $this->afMaxRatingValue = $afMaxRatingValue;

        return $this;
    }
    
    /**
     * Get afRatingValue
     *
     * @return string 
     */
    public function getAfMaxRatingValue()
    {
        return $this->afMaxRatingValue;
    }
    
    /**
     * Set afSearchString
     *
     * @param string $afSearchString
     * @return Action
     */
    public function setAfSearchString($afSearchString)
    {
        $this->afSearchString = $afSearchString;

        return $this;
    }
    
    /**
     * Get afSearchString
     *
     * @return string 
     */
    public function getAfSearchString()
    {
        return $this->afSearchString;
    }
    
    /**
     * Set afDescription
     *
     * @param string $afDescription
     * @return Action
     */
    public function setAfDescription($afDescription)
    {
        $this->afDescription = $afDescription;

        return $this;
    }
    
    /**
     * Get afSearchString
     *
     * @return string 
     */
    public function getAfDescription()
    {
        return $this->afSearchString;
    }
    
    /**
     * Set afScore
     *
     * @param string $afScore
     * @return Action
     */
    public function setAfScore($afScore)
    {
        $this->afScore = $afScore;

        return $this;
    }
    
    /**
     * Get afScore
     *
     * @return string 
     */
    public function getAfScore()
    {
        return $this->afScore;
    }
    
    /**
     * Set afDestinationA
     *
     * @param string $afScore
     * @return Action
     */
    public function setAfDestinationA($afDestinationA)
    {
        $this->afDestinationA = $afDestinationA;

        return $this;
    }
    
    /**
     * Get afDestinationA
     *
     * @return string 
     */
    public function getAfDestinationA()
    {
        return $this->afDestinationA;
    }
    
    /**
     * Set afDestinationB
     *
     * @param string $afDestinationB
     * @return Action
     */
    public function setAfDestinationB($afDestinationB)
    {
        $this->afDestinationB = $afDestinationB;

        return $this;
    }
    
    /**
     * Get afDestinationA
     *
     * @return string 
     */
    public function getAfDestinationB()
    {
        return $this->afDestinationB;
    }
    
    /**
     * Set afClass
     *
     * @param string $afClass
     * @return Action
     */
    public function setAfClass($afClass)
    {
        $this->afClass = $afClass;

        return $this;
    }
    
    /**
     * Get afClass
     *
     * @return string 
     */
    public function getAfClass()
    {
        return $this->afClass;
    }
    
    /**
     * Set afDateB
     *
     * @param string $afDateB
     * @return Action
     */
    public function setAfDateB($afDateB)
    {
        $this->afDateB = $afDateB;

        return $this;
    }
    
    /**
     * Get afDateB
     *
     * @return string 
     */
    public function getAfDateB()
    {
        return $this->afDateB;
    }
    
    /**
     * Set afEventStart
     *
     * @param string $afEventStart
     * @return Action
     */
    public function setAfEventStart($afEventStart)
    {
        $this->afEventStart = $afEventStart;

        return $this;
    }
    
    /**
     * Get afEventStart
     *
     * @return string 
     */
    public function getAfEventStart()
    {
        return $this->afEventStart;
    }
    
    /**
     * Set afEventEnd
     *
     * @param string $afEventEnd
     * @return Action
     */
    public function setAfEventEnd($afEventEnd)
    {
        $this->afEventEnd = $afEventEnd;

        return $this;
    }
    
    /**
     * Get afEventEnd
     *
     * @return string 
     */
    public function getAfEventEnd()
    {
        return $this->afEventEnd;
    }
    
    /**
     * Set afLat
     *
     * @param string $afLat
     * @return Action
     */
    public function setAfLat($afLat)
    {
        $this->afLat = $afLat;

        return $this;
    }
    
    /**
     * Get afLat
     *
     * @return string 
     */
    public function getAfLat()
    {
        return $this->afLat;
    }
    
    /**
     * Set afLong
     *
     * @param string $afLong
     * @return Action
     */
    public function setAfLong($afLong)
    {
        $this->afLong = $afLong;

        return $this;
    }
    
    /**
     * Get afLong
     *
     * @return string 
     */
    public function getAfLong()
    {
        return $this->afLong;
    }
    
    /**
     * Set afCustomerUserId
     *
     * @param string $afCustomerUserId
     * @return Action
     */
    public function setAfCustomerUserId($afCustomerUserId)
    {
        $this->afCustomerUserId = $afCustomerUserId;

        return $this;
    }
    
    /**
     * Get afCustomerUserId
     *
     * @return string 
     */
    public function getAfCustomerUserId()
    {
        return $this->afCustomerUserId;
    }
    
    /**
     * Set afValidated
     *
     * @param string $afCustomerUserId
     * @return Action
     */
    public function setAfValidated($afValidated)
    {
        $this->afValidated = $afValidated;

        return $this;
    }
    
    /**
     * Set afReceiptId
     *
     * @param string $afReceiptId
     * @return Action
     */
    public function setAfReceiptId($afReceiptId)
    {
        $this->afReceiptId = $afReceiptId;

        return $this;
    }
    
    /**
     * Get afReceiptId
     *
     * @return string 
     */
    public function getAfReceiptId()
    {
        return $this->afReceiptId;
    }
    
    /**
     * Set afParam1
     *
     * @param string $afParam1
     * @return Action
     */
    public function setAfParam1($afParam1)
    {
        $this->afParam1 = $afParam1;

        return $this;
    }
    
    /**
     * Get afParam1
     *
     * @return string 
     */
    public function getAfParam1()
    {
        return $this->afParam1;
    }
    
    /**
     * Set afParam2
     *
     * @param string $afParam2
     * @return Action
     */
    public function setAfParam2($afParam2)
    {
        $this->afParam2 = $afParam2;

        return $this;
    }
    
    /**
     * Get afParam2
     *
     * @return string 
     */
    public function getAfParam2()
    {
        return $this->afParam2;
    }
    
    /**
     * Set afParam3
     *
     * @param string $afParam3
     * @return Action
     */
    public function setAfParam3($afParam3)
    {
        $this->afParam3 = $afParam3;

        return $this;
    }
    
    /**
     * Get afParam3
     *
     * @return string 
     */
    public function getAfParam3()
    {
        return $this->afParam3;
    }
    
    /**
     * Set afParam4
     *
     * @param string $afParam4
     * @return Action
     */
    public function setAfParam4($afParam4)
    {
        $this->afParam4 = $afParam4;

        return $this;
    }
    
    /**
     * Get afParam4
     *
     * @return string 
     */
    public function getAfParam4()
    {
        return $this->afParam4;
    }
    
    /**
     * Set afParam5
     *
     * @param string $afParam5
     * @return Action
     */
    public function setAfParam5($afParam5)
    {
        $this->afParam5 = $afParam5;

        return $this;
    }
    
    /**
     * Get afParam5
     *
     * @return string 
     */
    public function getAfParam5()
    {
        return $this->afParam5;
    }
    
    /**
     * Set afParam6
     *
     * @param string $afParam6
     * @return Action
     */
    public function setAfParam6($afParam6)
    {
        $this->afParam6 = $afParam6;

        return $this;
    }
    
    /**
     * Get afParam5
     *
     * @return string 
     */
    public function getAfParam6()
    {
        return $this->afParam6;
    }
    
    /**
     * Set afParam7
     *
     * @param string $afParam7
     * @return Action
     */
    public function setAfParam7($afParam7)
    {
        $this->afParam7 = $afParam7;

        return $this;
    }
    
    /**
     * Get afParam7
     *
     * @return string 
     */
    public function getAfParam7()
    {
        return $this->afParam7;
    }
    
    /**
     * Set afParam8
     *
     * @param string $afParam8
     * @return Action
     */
    public function setAfParam8($afParam8)
    {
        $this->afParam8 = $afParam8;

        return $this;
    }
    
    /**
     * Get afParam8
     *
     * @return string 
     */
    public function getAfParam8()
    {
        return $this->afParam8;
    }
    
    /**
     * Set afParam9
     *
     * @param string $afParam9
     * @return Action
     */
    public function setAfParam9($afParam9)
    {
        $this->afParam9 = $afParam9;

        return $this;
    }
    
    /**
     * Get afParam9
     *
     * @return string 
     */
    public function getAfParam9()
    {
        return $this->afParam9;
    }
    
    /**
     * Set afParam10
     *
     * @param string $afParam10
     * @return Action
     */
    public function setAfParam10($afParam10)
    {
        $this->afParam10 = $afParam10;

        return $this;
    }
    
    /**
     * Get afParam10
     *
     * @return string 
     */
    public function getAfParam10()
    {
        return $this->afParam10;
    }
    
    /**
     * Set eventValueText
     *
     * @param string $eventValueText
     * @return Action
     */
    public function setEventValueText($eventValueText)
    {
        $this->eventValueText = $eventValueText;

        return $this;
    }
    
    /**
     * Get eventValueText
     *
     * @return string 
     */
    public function getEventValueText()
    {
        return $this->eventValueText;
    }
    
    /**
     * Set eventName
     *
     * @param string $eventName
     * @return Action
     */
    public function setEventName($eventName)
    {
        $this->eventName = $eventName;

        return $this;
    }
    
    /**
     * Get eventValueText
     *
     * @return string 
     */
    public function getEventName()
    {
        return $this->eventName;
    }
}
