<?php

namespace Hyper\Domain\Promo;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * Promo
 *
 * @ORM\Table(name="promo_banner")
 * @ORM\Entity(repositoryClass="Hyper\DomainBundle\Repository\Promo\DTPromoRepository")
 * @ExclusionPolicy("all")
 */
class Promo
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
     * @ORM\Column(name="app_name", type="string", length=255, nullable=false)
     * @Expose
     */
    private $appName;

    /**
     * @var string
     *
     * @ORM\Column(name="placement_name", type="string", length=255, nullable=false)
     * @Expose
     */
    private $placementName;
    
    /**
     * @var string
     *
     * @ORM\Column(name="campaign_title", type="string",nullable=false, length=255)
     * @Expose
     */
    private $campaignTitle;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="success", type="integer", nullable=true)
     * @Expose
     */
    private $success;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="fail", type="integer", nullable=true)
     * @Expose
     */
    private $fail;
    
    /**
     * @var string
     *
     * @ORM\Column(name="callback", type="string",nullable=true, length=255)
     * @Expose
     */
    private $callback;
     
    /**
     * @var string
     *
     * @ORM\Column(name="json_file", type="string", length=13107, nullable=true)
     * @Expose
     */
    private $jsonFile;
    
    /**
     * @var string
     *
     * @ORM\Column(name="img_path", type="string",nullable=true, length=255)
     * @Expose
     */
    private $imgPath;
    
    /**
     * @var string
     *
     * @ORM\Column(name="html_path", type="string",nullable=true, length=255)
     * @Expose
     */
    private $htmlPath;
    
    /**
     * @var string
     *
     * @ORM\Column(name="orientation", type="string",nullable=true, length=255)
     * @Expose
     */
    private $orientation;
    
    /**
     * @var string
     *
     * @ORM\Column(name="landing_page", type="string", nullable=true)
     * @Expose
     */
    private $landingPage;
    
    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", nullable=true)
     * @Expose
     */
    private $url;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="date_from", type="string",nullable=true, length=255)
     * @Expose
     */
    private $dateFrom;

    /**
     * @var integer
     *
     * @ORM\Column(name="date_to", type="string",nullable=true, length=255)
     * @Expose
     */
    private $dateTo;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="frequency", type="integer", nullable=false,)
     * @Expose
     */
    private $frequency;
    
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
     * @return Promo
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
     * Set appName
     *
     * @param string $appName
     * @return Promo
     */
    public function setAppName($appName)
    {
        $this->appName = $appName;

        return $this;
    }

    /**
     * Get appName
     *
     * @return string 
     */
    public function getAppName()
    {
        return $this->appName;
    }

    /**
     * Set placementName
     *
     * @param string $placementName
     * @return Promo
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
     * Set campaignTitle
     *
     * @param string $campaignTitle
     * @return Promo
     */
    public function setCampaignTitle($campaignTitle)
    {
        $this->campaignTitle = $campaignTitle;

        return $this;
    }

    /**
     * Get campaignTitle
     *
     * @return string 
     */
    public function getCampaignTitle()
    {
        return $this->campaignTitle;
    }

    /**
     * Set success
     *
     * @param integer $success
     * @return Promo
     */
    public function setSuccess($success)
    {
        $this->success = $success;

        return $this;
    }

    /**
     * Get success
     *
     * @return integer 
     */
    public function getSuccess()
    {
        return $this->success;
    }

    /**
     * Set fail
     *
     * @param integer $fail
     * @return Promo
     */
    public function setFail($fail)
    {
        $this->fail = $fail;

        return $this;
    }

    /**
     * Get fail
     *
     * @return integer 
     */
    public function getFail()
    {
        return $this->fail;
    }

    /**
     * Set callback
     *
     * @param string $callback
     * @return Promo
     */
    public function setCallback($callback)
    {
        $this->callback = $callback;

        return $this;
    }

    /**
     * Get callback
     *
     * @return string 
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * Set jsonFile
     *
     * @param string $jsonFile
     * @return Promo
     */
    public function setJsonFile($jsonFile)
    {
        $this->jsonFile = $jsonFile;

        return $this;
    }

    /**
     * Get jsonFile
     *
     * @return string 
     */
    public function getJsonFile()
    {
        return $this->jsonFile;
    }

    /**
     * Set imgPath
     *
     * @param string $imgPath
     * @return Promo
     */
    public function setImgPath($imgPath)
    {
        $this->imgPath = $imgPath;

        return $this;
    }

    /**
     * Get imgPath
     *
     * @return string 
     */
    public function getImgPath()
    {
        return $this->imgPath;
    }

    /**
     * Set htmlPath
     *
     * @param string $htmlPath
     * @return Promo
     */
    public function setHtmlPath($htmlPath)
    {
        $this->htmlPath = $htmlPath;

        return $this;
    }

    /**
     * Get htmlPath
     *
     * @return string 
     */
    public function getHtmlPath()
    {
        return $this->htmlPath;
    }

    /**
     * Set orientation
     *
     * @param string $orientation
     * @return Promo
     */
    public function setOrientation($orientation)
    {
        $this->orientation = $orientation;

        return $this;
    }

    /**
     * Get orientation
     *
     * @return string 
     */
    public function getOrientation()
    {
        return $this->orientation;
    }

    /**
     * Set landingPage
     *
     * @param string $landingPage
     * @return Promo
     */
    public function setLandingPage($landingPage)
    {
        $this->landingPage = $landingPage;

        return $this;
    }

    /**
     * Get landingPage
     *
     * @return string 
     */
    public function getLandingPage()
    {
        return $this->landingPage;
    }

    /**
     * Set url
     *
     * @param string $url
     * @return Promo
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string 
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set dateFrom
     *
     * @param integer $dateFrom
     * @return Promo
     */
    public function setDateFrom($dateFrom)
    {
        $this->dateFrom = $dateFrom;

        return $this;
    }

    /**
     * Get dateFrom
     *
     * @return integer 
     */
    public function getDateFrom()
    {
        return $this->dateFrom;
    }

    /**
     * Set dateTo
     *
     * @param integer $dateTo
     * @return Promo
     */
    public function setDateTo($dateTo)
    {
        $this->dateTo = $dateTo;

        return $this;
    }

    /**
     * Get dateTo
     *
     * @return integer 
     */
    public function getDateTo()
    {
        return $this->dateTo;
    }

    /**
     * Set frequency
     *
     * @param integer $frequency
     * @return Promo
     */
    public function setFrequency($frequency)
    {
        $this->frequency = $frequency;

        return $this;
    }

    /**
     * Get frequency
     *
     * @return integer 
     */
    public function getFrequency()
    {
        return $this->frequency;
    }

    /**
     * Set created
     *
     * @param integer $created
     * @return Promo
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
     * @return Promo
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
