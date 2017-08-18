<?php
namespace Hyper\EventAPIBundle\Service\TrendCard;

use Hyper\EventAPIBundle\Service\TrendCard\TrendCardReponseInterface;

abstract class TrendCardResponse implements TrendCardReponseInterface
{
    protected $queueId;

    protected $queueStatus;

    protected $queueBody;

    protected $retrieveFromCacheRequest;

    protected $sendToCacheRequest;

    public function __construct(
        $queueId
        , $queueStatus = 0
        , $queueBody = []
        , $retrieveFromCacheRequest
        , $sendToCacheRequest
        )
    {
        $this->setQueueId($queueId);
        $this->setQueueStatus($queueStatus);
        $this->setQueueBody($queueBody);
        $this->setRetrieveFromCacheRequest($retrieveFromCacheRequest);
        $this->setSendToCacheRequest($sendToCacheRequest);
    }

    protected function setQueueId($queueId)
    {
        if (empty($queueId)) {
            throw new \InvalidArgumentException('queue_id must not empty.');
        }
        $this->queueId = $queueId;
    }

    protected function setQueueStatus($queueStatus)
    {
         if (!in_array($queueStatus, [0, 1])) {
            throw new \InvalidArgumentException('queue_status is wrong.');
        }
        $this->queueStatus = $queueStatus;
    }

    protected function setQueueBody($queueBody)
    {
        $this->queueBody = $queueBody;
    }

    public function setRetrieveFromCacheRequest($retrieveFromCacheRequest)
    {
        $this->retrieveFromCacheRequest = $retrieveFromCacheRequest;
    }

    public function setSendToCacheRequest($sendToCacheRequest)
    {
        $this->sendToCacheRequest = $sendToCacheRequest;
    }

    public function queueId()
    {
        return $this->queueId;
    }

    public function queueStatus()
    {
        return $this->queueStatus;
    }

    public function queueBody()
    {
        return $this->queueBody;
    }

    abstract public function expired();

    public function serialize()
    {
        return serialize([
            $this->queueId
            , $this->queueStatus
            , $this->queueBody
        ]);
    }

    public function unserialize($serialized)
    {
        list (
            $this->queueId
            , $this->queueStatus
            , $this->queueBody
        ) = unserialize($serialized);
    }
}