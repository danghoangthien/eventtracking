<?php
namespace Hyper\EventAPIBundle\Service\TrendCard\IAEStatsCard;

use Hyper\EventAPIBundle\Service\TrendCard\TrendCardSendToCacheRequest;
use Hyper\EventAPIBundle\Service\TrendCard\TrendCardSendToCacheRequestInterface;

class IAEStatsCardSendToCacheRequest
    extends TrendCardSendToCacheRequest
    implements TrendCardSendToCacheRequestInterface
{
    protected $appId;
    protected $eventName;

    public function __construct($appId, $eventName, $queueId = '', $timestamp = '')
    {
        $this->setAppId($appId);
        $this->setEventName($eventName);
        parent::__construct($queueId, $timestamp);
    }

    protected function setAppId($appId)
    {
        if (empty($appId)) {
            throw new \InvalidArgumentException('app_id must not empty.');
        }
        $this->appId = $appId;
    }

    protected function setEventName($eventName)
    {
        if (empty($eventName)) {
            throw new \InvalidArgumentException('event_name must not empty.');
        }
        $this->eventName = $eventName;
    }

    public function appId()
    {
        return $this->appId;
    }

    public function eventName()
    {
        return $this->eventName;
    }

    public function serialize()
    {
        return serialize([
            $this->queueId
            , $this->appId
            , $this->eventName
            , $this->timestamp
        ]);
    }

    public function unserialize($serialized)
    {
        list (
            $this->queueId
            , $this->appId
            , $this->eventName
            , $this->timestamp
        ) = unserialize($serialized);
    }
}