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
     * @var integer
     *
     * @ORM\Column(name="quantity", type="integer")
     * @Expose
     */
    private $quantity;
    
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
     * @ORM\OneToMany(targetEntity="Hyper\Domain\Item\InWishlistItem", mappedBy="add_to_wishlist_actions", fetch="EXTRA_LAZY", cascade={"persist"})
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
     * Set quantity
     *
     * @param integer $quantity
     * @return AddToWishlistAction
     */
    public function setQuantity ($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Get quantity
     *
     * @return integer 
     */
    public function getQuantity()
    {
        return $this->quantity;
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
}
