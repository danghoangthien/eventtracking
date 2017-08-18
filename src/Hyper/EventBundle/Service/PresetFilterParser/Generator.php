<?php
namespace Hyper\EventBundle\Service\PresetFilterParser;

class Generator
{
    protected $deviceId;
    protected $countryCode;
    protected $platform;
    protected $installTime;
    protected $lastActivity;
    protected $happenedAt;
    protected $eventName;
    protected $afContentType;
    protected $idfa;
    protected $advertisingId;

    public function __construct(
        $deviceId,
        $countryCode = '',
        $platform = '',
        $installTime = '',
        $lastActivity = '',
        $happenedAt = '',
        $eventName = '',
        $afContentType = '',
        $idfa,
        $advertisingId
    ) {
        $this->deviceId = $deviceId;
        $this->countryCode = $countryCode;
        $this->platform = $platform;
        $this->installTime = $installTime;
        $this->lastActivity = $lastActivity;
        $this->happenedAt = $happenedAt;
        $this->eventName = $eventName;
        $this->afContentType = $afContentType;
        $this->idfa = $idfa;
        $this->advertisingId = $advertisingId;
    }

    public function getDeviceId()
    {
        return $this->deviceId;
    }

    public function getCountryCode()
    {
        return $this->countryCode;
    }

    public function getPlatform()
    {
        return $this->platform;
    }

    public function getInstallTime()
    {
        return $this->installTime;
    }

    public function getLastActivity()
    {
        return $this->lastActivity;
    }

    public function getHappenedAt()
    {
        return $this->happenedAt;
    }

    public function getEventName()
    {
        return $this->eventName;
    }

    public function getAfContentType()
    {
        return $this->afContentType;
    }

    public function getIDFA()
    {
        return $this->idfa;
    }

    public function getAdvertisingId()
    {
        return $this->advertisingId;
    }
}