<?php
namespace Hyper\EventAPIBundle\Service\TrendCard;

class TrendCardSendToCacheRequest
{
    public function __construct($queueId = '', $timestamp = '')
    {
        $this->setQueueId($queueId);
        $this->setTimestamp($timestamp);
    }

    protected function setQueueId($queueId)
    {
        if (empty($queueId)) {
            $this->queueId = uniqid('', true);
        } else {
            $this->queueId = $queueId;
        }
    }

    protected function setTimestamp($timestamp)
    {
        if (empty($timestamp)) {
            $dt = new \DateTime();
            $this->timestamp = $dt->getTimestamp();
        } else {
            $this->timestamp = $timestamp;
        }
    }

    public function appId()
    {
        return $this->appId;
    }

    public function queueId()
    {
        return $this->queueId;
    }

    public function timestamp()
    {
        return $this->timestamp;
    }
}