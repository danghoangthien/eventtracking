<?php

namespace Hyper\Domain\Item;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * InCartItem
 *
 * @ORM\Table(name="in_cart_items")
 * @ORM\Entity(repositoryClass="Hyper\DomainBundle\Repository\Item\DTInCartItemRepository")
 * @ExclusionPolicy("all")
 */
class InCartItem
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
     * @ORM\ManyToOne(targetEntity="Hyper\Domain\Action\AddToCartAction", fetch="EXTRA_LAZY", inversedBy="inCartItems")
     * @ORM\JoinColumn(name="cart_id", referencedColumnName="id")
     * @Expose
     */
    private $cart;
    
    /**
     * @ORM\ManyToOne(targetEntity="Hyper\Domain\Item\Item", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="item_id", referencedColumnName="id")
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
     * @return InCartItem
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
     * @return InCartItem
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
     * @return InCartItem
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
     * Set appId
     *
     * @param string $appId
     * @return InCartItem
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
     * Set cart
     *
     * @param \Hyper\Domain\Action\AddToCartAction $cart
     * @return InCartItem
     */
    public function setCart(\Hyper\Domain\Action\AddToCartAction $cart = null)
    {
        $this->cart = $cart;

        return $this;
    }

    /**
     * Get cart
     *
     * @return \Hyper\Domain\Action\AddToCartAction 
     */
    public function getCart()
    {
        return $this->cart;
    }

    /**
     * Set item
     *
     * @param \Hyper\Domain\Item\Item $item
     * @return InCartItem
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
