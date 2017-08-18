<?php

namespace Hyper\Domain\Filter;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * Filter
 *
 * @ORM\Table(name="dashboard_preset_filters")
 * @ORM\Entity(repositoryClass="Hyper\DomainBundle\Repository\Filter\DTFilterRepository")
 * @ExclusionPolicy("all")
 */
class Filter
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
     * @ORM\Column(name="authentication_id", type="string")
     * @Expose
     */
    private $authenticationId;
    
    /**
     * @var string
     *
     * @ORM\Column(name="preset_name", type="string")
     * @Expose
     */
    private $presetName;
    
    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=20000,options={"default"=""})
     * @Expose
     */
    private $description;
    
    /**
     * @var string
     *
     * @ORM\Column(name="filter_metadata", type="string", length=65535)
     * @Expose
     */
    private $filterMetadata;
    
    /**
     * @var string
     *
     * @ORM\Column(name="filter_data", type="string", length=65535,nullable=true)
     * @Expose
     */
     
    private $filterData;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="is_default", type="integer",options={"default"=1})
     * @Expose
     */
    private $isDefault;
    
    /**
     * @var string
     *
     * @ORM\Column(name="card_bg_color_code", type="string", nullable=true)
     * @Expose
     */
    private $cardBgColorCode;
    
    /**
     * @var string
     *
     * @ORM\Column(name="card_highlight_color_code", type="string", nullable=true)
     * @Expose
     */
    private $cardHighlightColorCode;
    
    /**
     * @var string
     *
     * @ORM\Column(name="card_text_color_code", type="string", nullable=true)
     * @Expose
     */
    private $cardTextColorCode;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="created", type="integer")
     * @Expose
     */
    private $created;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="last_update_cache", type="integer")
     * @Expose
     */
    private $lastUpdateCache;
    
    public function __construct()
    {
        $this->id = uniqid('',true);
        $this->created = time();
        $this->isDefault = 0;
    }

    /**
     * Set id
     *
     * @param string $id
     * @return Filter
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
     * Set authenticationId
     *
     * @param string $authenticationId
     * @return Filter
     */
    public function setAuthenticationId($authenticationId)
    {
        $this->authenticationId = $authenticationId;

        return $this;
    }

    /**
     * Get authenticationId
     *
     * @return string 
     */
    public function getAuthenticationId()
    {
        return $this->authenticationId;
    }

    /**
     * Set presetName
     *
     * @param string $presetName
     * @return Filter
     */
    public function setPresetName($presetName)
    {
        $this->presetName = $presetName;

        return $this;
    }

    /**
     * Get presetName
     *
     * @return string 
     */
    public function getPresetName()
    {
        return $this->presetName;
    }

    /**
     * Set filterMetadata
     *
     * @param string $filterMetadata
     * @return Filter
     */
    public function setFilterMetadata($filterMetadata)
    {
        $this->filterMetadata = serialize($filterMetadata);

        return $this;
    }

    /**
     * Get filterMetadata
     *
     * @return string 
     */
    public function getFilterMetadata()
    {
        return unserialize($this->filterMetadata);
    }
    
    /**
     * Set filterData
     *
     * @param string $filterData
     * @return Filter
     */
    public function setFilterData($filterData)
    {
        $this->filterData = serialize($filterData);

        return $this;
    }

    /**
     * Get filterData
     *
     * @return string 
     */
    public function getFilterData()
    {
        return unserialize($this->filterData);
    }

    /**
     * Set created
     *
     * @param integer $created
     * @return Filter
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
     * Set description
     *
     * @param string $description
     * @return Filter
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set isDefault
     *
     * @param integer $isDefault
     * @return Filter
     */
    public function setIsDefault($isDefault)
    {
        $this->isDefault = $isDefault;

        return $this;
    }

    /**
     * Get isDefault
     *
     * @return integer 
     */
    public function getIsDefault()
    {
        return $this->isDefault;
    }
    
    /**
     * Set cardBgColorCode
     *
     * @param string $cardBgColorCode
     * @return Filter
     */
    public function setCardBgColorCode($cardBgColorCode)
    {
        $this->cardBgColorCode = $cardBgColorCode;

        return $this;
    }

    /**
     * Get cardBgColorCode
     *
     * @return string 
     */
    public function getCardBgColorCode()
    {
        return $this->cardBgColorCode;
    }
    
    /**
     * Set cardHighlightColorCode
     *
     * @param string $cardHighlightColorCode
     * @return Filter
     */
    public function setCardHighlightColorCode($cardHighlightColorCode)
    {
        $this->cardHighlightColorCode = $cardHighlightColorCode;

        return $this;
    }

    /**
     * Get cardHighlightColorCode
     *
     * @return string 
     */
    public function getCardHighlightColorCode()
    {
        return $this->cardHighlightColorCode;
    }
    
    /**
     * Set cardTextColorCode
     *
     * @param string $cardTextColorCode
     * @return Filter
     */
    public function setCardTextColorCode($cardTextColorCode)
    {
        $this->cardTextColorCode = $cardTextColorCode;

        return $this;
    }

    /**
     * Get cardTextColorCode
     *
     * @return string 
     */
    public function getCardTextColorCode()
    {
        return $this->cardTextColorCode;
    }
    
    /**
     * Set lastUpdateCache
     *
     * @param string $lastUpdateCache
     * @return Filter
     */
    public function setLastUpdateCache($lastUpdateCache)
    {
        $this->lastUpdateCache = $lastUpdateCache;

        return $this;
    }

    /**
     * Get lastUpdateCache
     *
     * @return string 
     */
    public function getLastUpdateCache()
    {
        return $this->lastUpdateCache;
    }
    
}
