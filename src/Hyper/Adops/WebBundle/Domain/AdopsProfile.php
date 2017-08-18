<?php

namespace Hyper\Adops\WebBundle\Domain;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Util\Inflector;

use Symfony\Component\Validator\Constraints as Assert;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
* @ORM\Entity(repositoryClass="Hyper\Adops\WebBundle\DomainBundle\Repository\DTProfileRepository")
* @ORM\Table(name="adops_profiles")
*
* @author Carl Pham <vanca.vnn@gmail.com>
*/
class AdopsProfile
{
     /**
     * @ORM\Column(name="id", type="string")
     * @ORM\Id
     */
    private $id;

    /**
     * @ORM\Column(name="report_id", type="string", nullable=false)
     *
     */
    private $reportId;

    /**
     * @ORM\Column(name="idfa", type="string", nullable=true)
     *
     */
    private $idfa;

    /**
     * @ORM\Column(name="advertising_id", type="string", nullable=true)
     *
     */
    private $advertisingId;

    /**
     * @ORM\Column(name="android_id", type="string", nullable=true)
     *
     */
    private $androidId;

    /**
     * @ORM\Column(name="wifi", type="string", nullable=true)
     *
     */
    private $wifi;

    /**
     * @ORM\Column(name="click_time", type="integer", nullable=true)
     *
     */
    private $clickTime;

    /**
     * @ORM\Column(name="install_time", type="integer", nullable=true)
     *
     */
    private $installTime;

    /**
     * @ORM\Column(name="code_country", type="string", nullable=true)
     *
     */
    private $codeCountry;

    /**
     * @ORM\Column(name="city", type="string", nullable=true)
     *
     */
    private $city;

    /**
     * @ORM\Column(name="devicebrand", type="string", nullable=true)
     *
     */
    private $devicebrand;

    /**
     * @ORM\Column(name="device_carrier", type="string", nullable=true)
     *
     */
    private $deviceCarrier;

    /**
     * @ORM\Column(name="device_id", type="string", nullable=true)
     *
     */
    private $deviceId;

    /**
     * @ORM\Column(name="device_model", type="string", nullable=true)
     *
     */
    private $deviceModel;

    /**
     * @ORM\Column(name="language", type="string", nullable=true)
     *
     */
    private $language;

    /**
     * @ORM\Column(name="sdk_version", type="string", nullable=true)
     *
     */
    private $sdkVersion;

    /**
     * @ORM\Column(name="version", type="string", nullable=true)
     *
     */
    private $version;

    /**
     * @ORM\Column(name="ua", type="string", nullable=true)
     *
     */
    private $ua;

    /**
     * @ORM\Column(name="revenue", type="string", nullable=true)
     *
     */
    private $revenue;

    /**
     * @ORM\Column(name="currency", type="string", nullable=true)
     *
     */
    private $currency;

    /**
     * @ORM\Column(name="json", type="string", nullable=true, length=65535)
     *
     */
    private $json;

    public function __construct()
    {
        $this->id = uniqid('',true);
    }

    /**
     * Set id
     *
     * @param string $id
     * @return AdopsReport
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
    
    public function setReportId($reportId)
    {
        $this->reportId = $reportId;
    }
    
    public function setIdfa($idfa)
    {
        $this->idfa = $idfa;
    }
    
    public function setAdvertisingId($advertisingId)
    {
        $this->advertisingId = $advertisingId;
    }
    
    public function setAndroidId($androidId)
    {
        $this->androidId = $androidId;
    }
    
    public function setWifi($wifi)
    {
        $this->wifi = $wifi;
    }
    
    public function setClickTime($clickTime)
    {
        $this->clickTime = $clickTime;
    }
    
    public function setInstallTime($installTime)
    {
        $this->installTime = $installTime;
    }
    
    public function setCodeCountry($codeCountry)
    {
        $this->codeCountry = $codeCountry;
    }
    
    public function setCity($city)
    {
        $this->city = $city;
    }
    
    public function setDeviceBrand($deviceBrand)
    {
        $this->devicebrand = $deviceBrand;
    }
    
    public function setDeviceCarrier($deviceCarrier)
    {
        $this->deviceCarrier = $deviceCarrier;
    }
    
    public function setDeviceId($deviceId)
    {
        $this->deviceId = $deviceId;
    }
    
    public function setDeviceModel($deviceModel)
    {
        $this->deviceModel = $deviceModel;
    }
    
    public function setLanguage($language)
    {
        $this->language = $language;
    }
    
    public function setSdkVersion($sdkVersion)
    {
        $this->sdkVersion = $sdkVersion;
    }
    
    public function setVersion($version)
    {
        $this->version = $version;
    }
    
    public function setUa($ua)
    {
        $this->ua = $ua;
    }
    
    public function setRevenue($revenue)
    {
        $this->revenue = $revenue;
    }
    
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }
    
    public function setJson($json)
    {
        $this->json = $json;
    }

    public function get($fieldName)
    {
        $realFieldName = lcfirst(Inflector::classify($fieldName));
        return $this->$realFieldName;
    }

    public function set($fieldName, $value)
    {
        $realFieldName = lcfirst(Inflector::classify($fieldName));
        $this->$realFieldName = $value;
    }

    public function setData($data)
    {
        if (!empty($data)) {
            foreach ($data as $fieldName => $value) {
                $this->set($fieldName, $value);
            }
        }

        return $this;
    }
}