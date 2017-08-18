<?php
namespace Hyper\EventAPIBundle\Service\TrendCard\TotalSalesCard;

use Hyper\EventAPIBundle\Service\TrendCard\TrendCardSendToCacheRequest;
use Hyper\EventAPIBundle\Service\TrendCard\TrendCardSendToCacheRequestInterface;

class TotalSalesCardSendToCacheRequest
    extends TrendCardSendToCacheRequest
    implements TrendCardSendToCacheRequestInterface
{
    protected $appId;

    public function __construct($appId, $queueId = '', $timestamp = '')
    {
        $this->setAppId($appId);
        parent::__construct($queueId, $timestamp);
    }

    protected function setAppId($appId)
    {
        if (empty($appId)) {
            throw new \InvalidArgumentException('app_id must not empty.');
        }
        $this->appId = $appId;
    }

    public function appId()
    {
        return $this->appId;
    }

    public function serialize()
    {
        return serialize([
            $this->queueId
            , $this->appId
            , $this->timestamp
        ]);
    }

    public function unserialize($serialized)
    {
        list (
            $this->queueId
            , $this->appId
            , $this->timestamp
        ) = unserialize($serialized);
    }
}