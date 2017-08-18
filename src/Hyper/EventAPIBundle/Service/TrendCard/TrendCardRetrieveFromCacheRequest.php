<?php
namespace Hyper\EventAPIBundle\Service\TrendCard;

use Hyper\EventAPIBundle\Service\TrendCard\TrendCardRetrieveFromCacheRequestInterface;

class TrendCardRetrieveFromCacheRequest
    implements TrendCardRetrieveFromCacheRequestInterface
{
    protected $queueId;

    public function __construct($queueId)
    {
        $this->setQueueId($queueId);
    }

    protected function setQueueId($queueId)
    {
        if (empty($queueId)) {
            throw new \InvalidArgumentException('queue_id must not empty.');
        }
        $this->queueId = $queueId;
    }

    public function queueId()
    {
        return $this->queueId;
    }
}