<?php
namespace Hyper\EventAPIBundle\Service\AnalyticMetadata\RecentInAppEventService\ValueObject;

class RecentInAppEventValue
{
    protected $actionId;
    protected $appId;
    protected $eventName;
    protected $amountUsd;
    protected $eventFriendlyName;
    protected $tagAsIAP;
    protected $icon;
    protected $color;
    protected $happenedAt;
    protected $appName;
    protected $appPlatform;

    public function __construct(
        $actionId
        , $appId
        , $eventName
        , $amountUsd
        , $eventFriendlyName
        , $tagAsIAP
        , $icon
        , $color
        , $happenedAt
        , $appName
        , $appPlatform
    )
    {
        $this->actionId = $actionId;
        $this->appId = $appId;
        $this->eventName = $eventName;
        $this->amountUsd = $amountUsd;
        $this->eventFriendlyName = $eventFriendlyName;
        $this->tagAsIAP = $tagAsIAP;
        $this->icon = $icon;
        $this->color = $color;
        $this->happenedAt = $happenedAt;
        $this->appName = $appName;
        $this->appPlatform = $appPlatform;
    }

    public function actionId()
    {
        return $this->actionId;
    }

    public function appId()
    {
        return $this->appId;
    }

    public function eventName()
    {
        return $this->eventName;
    }

    public function amountUsd()
    {
        return $this->amountUsd;
    }

    public function eventFriendlyName()
    {
        return $this->eventFriendlyName;
    }

    public function tagAsIAP()
    {
        return $this->tagAsIAP;
    }

    public function icon()
    {
        return $this->icon;
    }

    public function color()
    {
        return $this->color;
    }

    public function happenedAt()
    {
        return $this->happenedAt;
    }

    public function appName()
    {
        return $this->appName;
    }

    public function appPlatform()
    {
        return $this->appPlatform;
    }
}