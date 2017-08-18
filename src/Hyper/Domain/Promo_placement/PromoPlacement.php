<?php

namespace Hyper\Domain\Promo_placement;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * PromoPlacement
 *
 * @ORM\Table(name="promo_placement")
 * @ORM\Entity(repositoryClass="Hyper\DomainBundle\Repository\Promo_placement\DTPromoPlacementRepository")
 * @ExclusionPolicy("all")
 */
class PromoPlacement
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
     * @ORM\Column(name="placement_name", type="string", length=10000, nullable=false)
     * @Expose
     */
    private $placementName;
    
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
     * @return PromoPlacement
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
     * @return PromoPlacement
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
     * Set placementName
     *
     * @param string $placementName
     * @return PromoPlacement
     */
    public function setPlacementName($placementName)
    {
        $this->placementName = $placementName;

        return $this;
    }

    /**
     * Get placementName
     *
     * @return string 
     */
    public function getPlacementName()
    {
        return $this->placementName;
    }

    /**
     * Set created
     *
     * @param integer $created
     * @return PromoPlacement
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
     * @return PromoPlacement
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
