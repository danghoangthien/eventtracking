<?php

namespace Hyper\Domain\Action;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * AddToWishlistAction
 *
 * @ORM\Table(name="add_to_wishlist_actions")
 * @ORM\Entity(repositoryClass="Hyper\DomainBundle\Repository\Action\DTAddToWishlistActionRepository")
 * @ExclusionPolicy("all")
 */
class AddToWishlistAction
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
     * @ORM\OneToMany(targetEntity="Hyper\Domain\Item\InWishlistItem", mappedBy="wishlist", fetch="EXTRA_LAZY", cascade={"persist"})
     */
     private $inWishlistItems;
    
    
    public function __construct()
    {
        $this->created = time();
        $this->inWishlistItems = new \Doctrine\Common\Collections\ArrayCollection();
    }
    

    /**
     * Set deviceId
     *
     * @param string $deviceId
     * @return AddToWishlistAction
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
     * Set applicationId
     *
     * @param string $applicationId
     * @return AddToWishlistAction
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
     * @return AddToWishlistAction
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
     * Set addedTime
     *
     * @param integer $addedTime
     * @return AddToWishlistAction
     */
    public function setAddedTime ($addedTime)
    {
        $this->addedTime = $addedTime;

        return $this;
    }
    
    /**
     * Set metadata
     *
     * @param string $metadata
     * @return AddToWishlistAction
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
     * @return AddToWishlistAction
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
     * @return AddToWishlistAction
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
     * Add inWishlistItems
     *
     * @param \Hyper\Domain\Item\InWishlistItem $inWishlistItems
     * @return AddToWishlistAction
     */
    public function addInWishlistItem(\Hyper\Domain\Item\InWishlistItem $inWishlistItems)
    {
        $this->inWishlistItems[] = $inWishlistItems;

        return $this;
    }

    /**
     * Remove inWishlistItems
     *
     * @param \Hyper\Domain\Item\InWishlistItem $inWishlistItems
     */
    public function removeInWishlistItem(\Hyper\Domain\Item\InWishlistItem $inWishlistItems)
    {
        $this->inWishlistItems->removeElement($inWishlistItems);
    }

    /**
     * Get inWishlistItems
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getInWishlistItems()
    {
        return $this->inWishlistItems;
    }

    /**
     * Set appId
     *
     * @param string $appId
     * @return AddToWishlistAction
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
}
