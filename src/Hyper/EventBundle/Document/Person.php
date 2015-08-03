<?php
namespace Hyper\EventBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Hyper\EventBundle\Annotations as HyperEvent;
/**
 * @MongoDB\Document(
 *     db="bklp",
 *     collection="persons"
 * )
 */
class Person
{
    /**
     * @MongoDB\Id
     */
    protected $id;
    
    /**
     * @MongoDB\Field(name="hypid",type="string")
     */
    protected $hypid;
    /**
     * @MongoDB\Field(name="app_id",type="string")
     * @HyperEvent\CsvMeta(field="App ID")
     */
    protected $appId;

    /**
     * @MongoDB\Field(name="platform",type="string")
     * @HyperEvent\CsvMeta(field="Platform")
     */
    protected $platform;
    /**
     * @MongoDB\Field(name="click_time",type="string")
     * @HyperEvent\CsvMeta(field="Click Time")
     */
    protected $clickTime;
    /**
     * @MongoDB\Field(name="install_time",type="string")
     * @HyperEvent\CsvMeta(field="Install Time")
     */
    protected $installTime;
    /**
     * @MongoDB\Field(name="country_code",type="string")
     * @HyperEvent\CsvMeta(field="Country")
     */
    protected $countryCode;
    /**
     * @MongoDB\Field(name="city",type="string")
     * @HyperEvent\CsvMeta(field="City")
     */
    protected $city;
    /**
     * @MongoDB\Field(name="ip",type="string")
     * @HyperEvent\CsvMeta(field="IP")
     */
    protected $ip;
    /**
     * @MongoDB\Field(name="wifi",type="string")
     * @HyperEvent\CsvMeta(field="WIFI")
     */
    protected $wifi;
    /**
     * @MongoDB\Field(name="language",type="string")
     * @HyperEvent\CsvMeta(field="Language")
     */
    protected $language;
    /**
     * @MongoDB\Field(name="operator",type="string")
     * @HyperEvent\CsvMeta(field="Operator")
     */
    protected $operator;
    /**
     * @MongoDB\Field(name="advertising_id",type="string")
     * @HyperEvent\CsvMeta(field="Android AID")
     */
    protected $advertisingId;
    /**
     * @MongoDB\Field(name="android_id",type="string")
     * @HyperEvent\CsvMeta(field="Android ID")
     */
    protected $androidId;
    /**
     * @MongoDB\Field(name="imei",type="string")
     * @HyperEvent\CsvMeta(field="Android IMEI")
     */
    protected $imei;
    /**
     * @MongoDB\Field(name="idfa",type="string")
     * @HyperEvent\CsvMeta(field="IOS IDFA")
     */
    protected $idfa;
    /**
     * @MongoDB\Field(name="idfv",type="string")
     * @HyperEvent\CsvMeta(field="IOS IDFV")
     */
    protected $idfv;
    /**
     * @MongoDB\Field(name="mac",type="string")
     * @HyperEvent\CsvMeta(field="MAC")
     */
    protected $mac;
    /**
     * @MongoDB\Field(name="device_brand",type="string")
     * @HyperEvent\CsvMeta(field="Device Brand")
     */
    protected $deviceBrand;
    /**
     * @MongoDB\Field(name="device_model",type="string")
     * @HyperEvent\CsvMeta(field="Device Model")
     */
    protected $deviceModel;
    /**
     * @MongoDB\Field(name="device_name",type="string")
     * @HyperEvent\CsvMeta(field="IOS Device Name")
     */
    protected $deviceName;
    /**
     * @MongoDB\Field(name="device_type",type="string")
     * @HyperEvent\CsvMeta(field="IOS Device Type")
     */
    protected $deviceType;
    /**
     * @MongoDB\Field(name="os_version",type="string")
     * @HyperEvent\CsvMeta(field="OS Version")
     */
    protected $osVersion;
    /**
     * @MongoDB\Field(name="app_version",type="string")
     * @HyperEvent\CsvMeta(field="App Version")
     */
    protected $appVersion;
    /**
     * @MongoDB\Field(name="person_name",type="string")
     * @HyperEvent\CsvMeta(field="Person Name")
     */
    protected $personName;
    /**
     * @MongoDB\Field(name="person_email",type="string")
     * @HyperEvent\CsvMeta(field="Person Email")
     */
    protected $personEmail;
    /**
     * @MongoDB\Field(name="facebook_id",type="string")
     * @HyperEvent\CsvMeta(field="Facebook ID")
     */
    protected $facebookId;
    


    /**
     * Get id
     *
     * @return id $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set hypid
     *
     * @param string $hypid
     * @return self
     */
    public function setHypid($hypid)
    {
        $this->hypid = $hypid;
        return $this;
    }

    /**
     * Get hypid
     *
     * @return string $hypid
     */
    public function getHypid()
    {
        return $this->hypid;
    }

    /**
     * Set appId
     *
     * @param string $appId
     * @return self
     */
    public function setAppId($appId)
    {
        $this->appId = $appId;
        return $this;
    }

    /**
     * Get appId
     *
     * @return string $appId
     */
    public function getAppId()
    {
        return $this->appId;
    }

    /**
     * Set platform
     *
     * @param string $platform
     * @return self
     */
    public function setPlatform($platform)
    {
        $this->platform = $platform;
        return $this;
    }

    /**
     * Get platform
     *
     * @return string $platform
     */
    public function getPlatform()
    {
        return $this->platform;
    }

    /**
     * Set clickTime
     *
     * @param string $clickTime
     * @return self
     */
    public function setClickTime($clickTime)
    {
        $this->clickTime = $clickTime;
        return $this;
    }

    /**
     * Get clickTime
     *
     * @return string $clickTime
     */
    public function getClickTime()
    {
        return $this->clickTime;
    }

    /**
     * Set countryCode
     *
     * @param string $countryCode
     * @return self
     */
    public function setCountryCode($countryCode)
    {
        $this->countryCode = $countryCode;
        return $this;
    }

    /**
     * Get countryCode
     *
     * @return string $countryCode
     */
    public function getCountryCode()
    {
        return $this->countryCode;
    }

    /**
     * Set city
     *
     * @param string $city
     * @return self
     */
    public function setCity($city)
    {
        $this->city = $city;
        return $this;
    }

    /**
     * Get city
     *
     * @return string $city
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set ip
     *
     * @param string $ip
     * @return self
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
        return $this;
    }

    /**
     * Get ip
     *
     * @return string $ip
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * Set wifi
     *
     * @param string $wifi
     * @return self
     */
    public function setWifi($wifi)
    {
        $this->wifi = $wifi;
        return $this;
    }

    /**
     * Get wifi
     *
     * @return string $wifi
     */
    public function getWifi()
    {
        return $this->wifi;
    }

    /**
     * Set language
     *
     * @param string $language
     * @return self
     */
    public function setLanguage($language)
    {
        $this->language = $language;
        return $this;
    }

    /**
     * Get language
     *
     * @return string $language
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Set operator
     *
     * @param string $operator
     * @return self
     */
    public function setOperator($operator)
    {
        $this->operator = $operator;
        return $this;
    }

    /**
     * Get operator
     *
     * @return string $operator
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * Set advertisingId
     *
     * @param string $advertisingId
     * @return self
     */
    public function setAdvertisingId($advertisingId)
    {
        $this->advertisingId = $advertisingId;
        return $this;
    }

    /**
     * Get advertisingId
     *
     * @return string $advertisingId
     */
    public function getAdvertisingId()
    {
        return $this->advertisingId;
    }

    /**
     * Set androidId
     *
     * @param string $androidId
     * @return self
     */
    public function setAndroidId($androidId)
    {
        $this->androidId = $androidId;
        return $this;
    }

    /**
     * Get androidId
     *
     * @return string $androidId
     */
    public function getAndroidId()
    {
        return $this->androidId;
    }

    /**
     * Set imei
     *
     * @param string $imei
     * @return self
     */
    public function setImei($imei)
    {
        $this->imei = $imei;
        return $this;
    }

    /**
     * Get imei
     *
     * @return string $imei
     */
    public function getImei()
    {
        return $this->imei;
    }

    /**
     * Set idfa
     *
     * @param string $idfa
     * @return self
     */
    public function setIdfa($idfa)
    {
        $this->idfa = $idfa;
        return $this;
    }

    /**
     * Get idfa
     *
     * @return string $idfa
     */
    public function getIdfa()
    {
        return $this->idfa;
    }
    
    /**
     * Set idfv
     *
     * @param string $idfv
     * @return self
     */
    public function setIdfv($idfv)
    {
        $this->idfv = $idfv;
        return $this;
    }

    /**
     * Get idfv
     *
     * @return string $idfv
     */
    public function getIdfv()
    {
        return $this->idfv;
    }

    /**
     * Set mac
     *
     * @param string $mac
     * @return self
     */
    public function setMac($mac)
    {
        $this->mac = $mac;
        return $this;
    }

    /**
     * Get mac
     *
     * @return string $mac
     */
    public function getMac()
    {
        return $this->mac;
    }

    /**
     * Set deviceBrand
     *
     * @param string $deviceBrand
     * @return self
     */
    public function setDeviceBrand($deviceBrand)
    {
        $this->deviceBrand = $deviceBrand;
        return $this;
    }

    /**
     * Get deviceBrand
     *
     * @return string $deviceBrand
     */
    public function getDeviceBrand()
    {
        return $this->deviceBrand;
    }

    /**
     * Set deviceModel
     *
     * @param string $deviceModel
     * @return self
     */
    public function setDeviceModel($deviceModel)
    {
        $this->deviceModel = $deviceModel;
        return $this;
    }

    /**
     * Get deviceModel
     *
     * @return string $deviceModel
     */
    public function getDeviceModel()
    {
        return $this->deviceModel;
    }

    /**
     * Set deviceName
     *
     * @param string $deviceName
     * @return self
     */
    public function setDeviceName($deviceName)
    {
        $this->deviceName = $deviceName;
        return $this;
    }

    /**
     * Get deviceName
     *
     * @return string $deviceName
     */
    public function getDeviceName()
    {
        return $this->deviceName;
    }

    /**
     * Set deviceType
     *
     * @param string $deviceType
     * @return self
     */
    public function setDeviceType($deviceType)
    {
        $this->deviceType = $deviceType;
        return $this;
    }

    /**
     * Get deviceType
     *
     * @return string $deviceType
     */
    public function getDeviceType()
    {
        return $this->deviceType;
    }

    /**
     * Set osVersion
     *
     * @param string $osVersion
     * @return self
     */
    public function setOsVersion($osVersion)
    {
        $this->osVersion = $osVersion;
        return $this;
    }

    /**
     * Get osVersion
     *
     * @return string $osVersion
     */
    public function getOsVersion()
    {
        return $this->osVersion;
    }

    /**
     * Set appVersion
     *
     * @param string $appVersion
     * @return self
     */
    public function setAppVersion($appVersion)
    {
        $this->appVersion = $appVersion;
        return $this;
    }

    /**
     * Get appVersion
     *
     * @return string $appVersion
     */
    public function getAppVersion()
    {
        return $this->appVersion;
    }

    /**
     * Set installTime
     *
     * @param string $installTime
     * @return self
     */
    public function setInstallTime($installTime)
    {
        $this->installTime = $installTime;
        return $this;
    }

    /**
     * Get installTime
     *
     * @return string $installTime
     */
    public function getInstallTime()
    {
        return $this->installTime;
    }

    /**
     * Set personName
     *
     * @param string $personName
     * @return self
     */
    public function setPersonName($personName)
    {
        $this->personName = $personName;
        return $this;
    }

    /**
     * Get personName
     *
     * @return string $personName
     */
    public function getPersonName()
    {
        return $this->personName;
    }

    /**
     * Set personEmail
     *
     * @param string $personEmail
     * @return self
     */
    public function setPersonEmail($personEmail)
    {
        $this->personEmail = $personEmail;
        return $this;
    }

    /**
     * Get personEmail
     *
     * @return string $personEmail
     */
    public function getPersonEmail()
    {
        return $this->personEmail;
    }

    /**
     * Set facebookId
     *
     * @param string $facebookId
     * @return self
     */
    public function setFacebookId($facebookId)
    {
        $this->facebookId = $facebookId;
        return $this;
    }

    /**
     * Get facebookId
     *
     * @return string $facebookId
     */
    public function getFacebookId()
    {
        return $this->facebookId;
    }
}
