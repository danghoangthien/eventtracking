<?php

namespace Hyper\Domain\Item;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * InWishlistItem
 *
 * @ORM\Table(name="in_wishlist_items")
 * @ORM\Entity(repositoryClass="Hyper\DomainBundle\Repository\Item\DTInWishlistItemRepository")
 * @ExclusionPolicy("all")
 */
class InWishlistItem
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
     * @ORM\ManyToOne(targetEntity="Hyper\Domain\Action\AddToWishlistAction", fetch="EXTRA_LAZY", inversedBy="in_wishlist_items")
     * @ORM\JoinColumn()
     * @Expose
     */
    private $wishlist;
    
    /**
     * @ORM\ManyToOne(targetEntity="Hyper\Domain\Item\Item", fetch="EXTRA_LAZY", inversedBy="in_wishlist_items")
     * @ORM\JoinColumn()
     * @Expose
     */
    private $item;
    
    public function __construct()
    {
        $this->id = uniqid('',true);
        $this->created = time();
    }
    

    /**
     * Set id
     *
     * @param string $id
     * @return InWishlistItem
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
     * @return InWishlistItem
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
     * @return InWishlistItem
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
     * Set wishlist
     *
     * @param \Hyper\Domain\Action\AddTowishlist $wishlist
     * @return InWishlistItem
     */
    public function setWishList(\Hyper\Domain\Action\AddToWishlistAction $wishlist = null)
    {
        $this->wishlist = $wishlist;

        return $this;
    }

    /**
     * Get wishlist
     *
     * @return \Hyper\Domain\Action\AddToWishlistAction 
     */
    public function getWishlist()
    {
        return $this->wishlist;
    }

    /**
     * Set item
     *
     * @param \Hyper\Domain\Item\Item $item
     * @return InWishlistItem
     */
    public function setItem(\Hyper\Domain\Item\Item $item = null)
    {
        $this->item = $item;

        return $this;
    }

    /**
     * Get item
     *
     * @return \Hyper\Domain\Item\Item 
     */
    public function getItem()
    {
        return $this->item;
    }
}
