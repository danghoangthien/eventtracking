<?php

namespace Hyper\Domain\Action;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * AddToCartAction
 *
 * @ORM\Table(name="add_to_cart_actions")
 * @ORM\Entity(repositoryClass="Hyper\DomainBundle\Repository\Action\DTAddToCartActionRepository")
 * @ExclusionPolicy("all")
 */
class AddToCartAction
{
    /**
     * @ORM\OneToOne(targetEntity="Action")
     * @ORM\JoinColumn(name="id", referencedColumnName="id")
     * @ORM\Id
     */
    private $action;


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
     * @ORM\Column(name="app_id", type="string", nullable=true)
     * @Expose
     */
    private $appId;

    /**
     * @var string
     *
     * @ORM\Column(name="application_id", type="string")
     * @Expose
     */
    private $applicationId;


    /**
     * @var integer
     *
     * @ORM\Column(name="total_items", type="integer")
     * @Expose
     */
    private $totalItems;
    
    /**
     * @var string
     *
     * @ORM\Column(name="metadata", type="string", length=13107, nullable=true)
     * @Expose
     */
    private $metadata;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="added_time", type="integer")
     * @Expose
     */
    private $addedTime;
    

    /**
     * @var integer
     *
     * @ORM\Column(name="created", type="integer")
     * @Expose
     */
    private $created;    
    
    
    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="Hyper\Domain\Item\InCartItem", mappedBy="addToCartActionId", fetch="EXTRA_LAZY", cascade={"persist"})
     */
     private $inCartItems;
    
    
    public function __construct()
    {
        $this->created = time();
        $this->inCartItems = new \Doctrine\Common\Collections\ArrayCollection();
    }
    

    /**
     * Set deviceId
     *
     * @param string $deviceId
     * @return AddToCartAction
     */
    public function setDeviceId($deviceId)
    {
        $this->deviceId = $deviceId;

        return $this;
    }
    
    /**
     * Set appId
     *
     * @param string $appId
     * @return AddToCartAction
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
     * Get deviceId
     *
     * @return string 
     */
    public function getDeviceId()
    {
        return $this->deviceId;
    }

    /**
     * Set applicationId
     *
     * @param string $applicationId
     * @return AddToCartAction
     */
    public function setApplicationId($applicationId)
    {
        $this->applicationId = $applicationId;

        return $this;
    }

    /**
     * Get applicationId
     *
     * @return string 
     */
    public function getApplicationId()
    {
        return $this->applicationId;
    }

    /**
     * Set totalItems
     *
     * @param integer $totalItems
     * @return AddToCartAction
     */
    public function setTotalItems ($totalItems)
    {
        $this->totalItems = $totalItems;

        return $this;
    }

    /**
     * Get totalItems
     *
     * @return integer 
     */
    public function getTotalItems()
    {
        return $this->totalItems;
    }
    
    /**
     * Set metadata
     *
     * @param string $metadata
     * @return AddToCartAction
     */
    public function setMetadata($metadata)
    {
        $this->metadata = serialize($metadata);

        return $this;
    }

    /**
     * Get metadata
     *
     * @return string 
     */
    public function getMetadata()
    {
        return unserialize($this->metadata);
    }

    /**
     * Set addedTime
     *
     * @param integer $addedTime
     * @return AddToCartAction
     */
    public function setAddedTime ($addedTime)
    {
        $this->addedTime = $addedTime;

        return $this;
    }

    /**
     * Get addedTime
     *
     * @return integer 
     */
    public function getAddedTime()
    {
        return $this->addedTime;
    }

    /**
     * Set created
     *
     * @param integer $created
     * @return AddToCartAction
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
     * Set action
     *
     * @param \Hyper\Domain\Action\Action $action
     * @return AddToCartAction
     */
    public function setAction(\Hyper\Domain\Action\Action $action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Get action
     *
     * @return \Hyper\Domain\Action\Action 
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Add inCartItems
     *
     * @param \Hyper\Domain\Item\InCartItem $inCartItems
     * @return AddToCartAction
     */
    public function addInCartItem(\Hyper\Domain\Item\InCartItem $inCartItems)
    {
        $this->inCartItems[] = $inCartItems;

        return $this;
    }

    /**
     * Remove inCartItems
     *
     * @param \Hyper\Domain\Item\InCartItem $inCartItems
     */
    public function removeInCartItem(\Hyper\Domain\Item\InCartItem $inCartItems)
    {
        $this->inCartItems->removeElement($inCartItems);
    }

    /**
     * Get inCartItems
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getInCartItems()
    {
        return $this->inCartItems;
    }
}
