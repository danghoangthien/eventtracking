<?php

namespace Hyper\Domain\Promo_landing;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * PromoLanding
 *
 * @ORM\Table(name="promo_landing")
 * @ORM\Entity(repositoryClass="Hyper\DomainBundle\Repository\Promo_landing\DTPromoLandingRepository")
 * @ExclusionPolicy("all")
 */
class PromoLanding
{
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
     * @ORM\Column(name="app_id", type="string", length=255, nullable=false)
     * @Expose
     */
    private $appId;

    /**
     * @var string
     *
     * @ORM\Column(name="deeplink_map", type="string", length=10000, nullable=false)
     * @Expose
     */
    private $deeplinkMap;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="created", type="integer", nullable=false,)
     * @Expose
     */
    private $created;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="updated", type="integer", nullable=false,)
     * @Expose
     */
    private $updated;

    /**
     * Set id
     *
     * @param string $id
     * @return PromoLanding
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
     * Set appId
     *
     * @param string $appId
     * @return PromoLanding
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
     * Set deeplinkMap
     *
     * @param string $deeplinkMap
     * @return PromoLanding
     */
    public function setDeeplinkMap($deeplinkMap)
    {
        $this->deeplinkMap = $deeplinkMap;

        return $this;
    }

    /**
     * Get deeplinkMap
     *
     * @return string 
     */
    public function getDeeplinkMap()
    {
        return $this->deeplinkMap;
    }

    /**
     * Set created
     *
     * @param integer $created
     * @return PromoLanding
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
     * @return PromoLanding
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
}
