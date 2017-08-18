<?php
namespace Hyper\EventAPIBundle\Service\TrendCard\HighestSalesCard;

use Hyper\EventAPIBundle\Service\TrendCard\HighestSalesCard\HighestSalesCardSendToCacheRequest;
use Hyper\EventAPIBundle\Service\TrendCard\TrendCardResponse;

class HighestSalesCardResponse extends TrendCardResponse
{
    public function __construct(
        $queueId
        , $queueStatus = 0
        , $queueBody = []
        , $retrieveFromCacheRequest
        , $sendToCacheRequest
    )
    {
        parent::__construct($queueId, $queueStatus, $queueBody, $retrieveFromCacheRequest, $sendToCacheRequest);
    }

    public function expired()
    {
        if (!$this->sendToCacheRequest instanceof HighestSalesCardSendToCacheRequest) {
            throw new \Exception('Object reference not set to an instance of HighestSalesCardSendToCacheRequest');
        }
        $dtNow = new \DateTime('');
        $dtPast = new \DateTime('@'. $this->sendToCacheRequest->timestamp());
        $dtPast->setTimezone($dtNow->getTimezone());
        $dtPast->modify('first day of next month');
        $dtPast->setTime(0, 0, 0);
        if ($dtNow->getTimestamp() >= $dtPast->getTimestamp()) {
            return true;
        }

        return false;
    }
}