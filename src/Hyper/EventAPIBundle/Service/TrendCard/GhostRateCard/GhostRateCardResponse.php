<?php
namespace Hyper\EventAPIBundle\Service\TrendCard\GhostRateCard;

use Hyper\EventAPIBundle\Service\TrendCard\GhostRateCard\GhostRateCardSendToCacheRequest;
use Hyper\EventAPIBundle\Service\TrendCard\TrendCardResponse;

class GhostRateCardResponse extends TrendCardResponse
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
        if (!$this->sendToCacheRequest instanceof GhostRateCardSendToCacheRequest) {
            throw new \Exception('Need data of sendToCacheRequest');
        }
        $dtNow = new \DateTime('');
        $dtPast = new \DateTime('@'. $this->sendToCacheRequest->timestamp());
        $dtPast->setTimezone($dtNow->getTimezone());
        $dtPast->modify('+1 day');
        $dtPast->setTime(0, 0, 0);
        if ($dtNow->getTimestamp() >= $dtPast->getTimestamp()) {
            return true;
        }
        return false;
    }
}