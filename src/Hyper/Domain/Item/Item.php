<?php

namespace Hyper\Domain\Item;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * Item
 *
 * @ORM\Table(name="items")
 * @ORM\Entity(repositoryClass="Hyper\DomainBundle\Repository\Item\DTItemRepository")
 * @ExclusionPolicy("all")
 */
class Item
{
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
     * @ORM\Column(name="app_id", type="string",nullable=true)
     * @Expose
     */
    private $appId;

    /**
     * @ORM\ManyToOne(targetEntity="Hyper\Domain\Application\Application", fetch="EXTRA_LAZY", inversedBy="items")
     * @ORM\JoinColumn()
     * @Expose
     */
    private $application;


    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string")
     * @Expose
     */
    private $code;


    /**
     * @var float
     *
     * @ORM\Column(name="price", type="float", options={"unsigned"=true})
     * @Expose
     */
     
    private $price;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string")
     * @Expose
     */
     
    private $currency;
    
    /**
     * @var string
     *
     * @ORM\Column(name="metadata", type="string")
     * @Expose
     */
    private $metadata;
    
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
     * @return Item
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
     * Set code
     *
     * @param string $code
     * @return Item
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string 
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set price
     *
     * @param float $price
     * @return Item
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price
     *
     * @return float 
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set currency
     *
     * @param string $currency
     * @return Item
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get currency
     *
     * @return string 
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Set metadata
     *
     * @param string $metadata
     * @return Item
     */
    public function setMetadata($metadata)
    {
        $this->metadata = $metadata;

        return $this;
    }

    /**
     * Get metadata
     *
     * @return string 
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * Set created
     *
     * @param integer $created
     * @return Item
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
     * Set appId
     *
     * @param string $appId
     * @return Item
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
     * @return Item
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
}
