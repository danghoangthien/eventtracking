<?php

namespace Hyper\Domain\Client;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * Client
 *
 * @ORM\Table(name="client")
 * @ORM\Entity(repositoryClass="Hyper\DomainBundle\Repository\Client\DTClientRepository")
 * @ExclusionPolicy("all")
 */
class Client
{
    const ACCOUNT_TYPE = array(
        'E-commerce' => 1,
        'Gaming' => 2,
        'Branding' => 3
    );

    const USAGE_PLAN_TYPE_FREE_PLAN = 1;
    const USAGE_PLAN_TYPE_STUDIO_PLAN = 2;
    const USAGE_PLAN_TYPE_BUSINESS_PLAN = 3;
    const USAGE_PLAN_TYPE = [
        self::USAGE_PLAN_TYPE_FREE_PLAN => 'Free Plan'
        , self::USAGE_PLAN_TYPE_STUDIO_PLAN => 'Studio Plan'
        , self::USAGE_PLAN_TYPE_BUSINESS_PLAN => 'Business Plan'
    ];

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
     * @ORM\Column(name="client_name", type="string", length=255, nullable=false)
     * @Expose
     */
    private $client_name;

    /**
     * @var string
     *
     * @ORM\Column(name="client_app", type="string", length=13107, nullable=true)
     * @Expose
     */
    private $client_app;

    /**
     * @var string
     *
     * @ORM\Column(name="s3_folder", type="string", length=13107, nullable=true)
     * @Expose
     */
    private $s3_folder;

    /**
     * @var integer
     *
     * @ORM\Column(name="account_type", type="integer", nullable=true)
     * @Expose
     */
    private $account_type;

    /**
     * @var integer
     *
     * @ORM\Column(name="created", type="integer", nullable=false)
     * @Expose
     */
    private $created;

    /**
     * @var integer
     *
     * @ORM\Column(name="updated", type="integer", nullable=false)
     * @Expose
     */
    private $updated;

    /**
     * @var integer
     *
     * @ORM\Column(name="usage_plan_type", type="integer", nullable=false)
     * @Expose
     */
    private $usagePlanType;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_limit", type="integer", nullable=true)
     * @Expose
     */
    private $userLimit;

    /**
     * Set id
     *
     * @param string $id
     * @return Client
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
     * Set client_name
     *
     * @param string $clientName
     * @return Client
     */
    public function setClientName($clientName)
    {
        $this->client_name = $clientName;

        return $this;
    }

    /**
     * Get client_name
     *
     * @return string
     */
    public function getClientName()
    {
        return $this->client_name;
    }

    /**
     * Set client_app
     *
     * @param string $clientApp
     * @return Client
     */
    public function setClientApp($clientApp)
    {
        $this->client_app = $clientApp;

        return $this;
    }

    /**
     * Get client_app
     *
     * @return string
     */
    public function getClientApp()
    {
        return $this->client_app;
    }

    /**
     * Set account_type
     *
     * @param integer $accountType
     * @return Client
     */
    public function setAccountType($accountType)
    {
        $this->account_type = $accountType;

        return $this;
    }

    /**
     * Get account_type
     *
     * @return integer
     */
    public function getAccountType()
    {
        return $this->account_type;
    }

    /**
     * Set created
     *
     * @param integer $created
     * @return Client
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
     * @return Client
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

    /**
     * Set s3_folder
     *
     * @param string $s3Folder
     * @return Client
     */
    public function setS3Folder($s3Folder)
    {
        $this->s3_folder = $s3Folder;

        return $this;
    }

    /**
     * Get s3_folder
     *
     * @return string
     */
    public function getS3Folder()
    {
        return $this->s3_folder;
    }

    public function setUsagePlanType($usagePlanType)
    {
        $this->usagePlanType = $usagePlanType;

        return $this;
    }

    public function getUsagePlanType()
    {
        return $this->usagePlanType;
    }

    public function setUserLimit($userLimit)
    {
        $this->userLimit = $userLimit;

        return $this;
    }

    public function getUserLimit()
    {
        return $this->userLimit;
    }
}
