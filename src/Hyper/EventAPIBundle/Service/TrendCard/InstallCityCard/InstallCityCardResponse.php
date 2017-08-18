<?php
namespace Hyper\EventAPIBundle\Service\TrendCard\InstallCityCard;

use Hyper\EventAPIBundle\Service\TrendCard\TrendCardResponse;

class InstallCityCardResponse extends TrendCardResponse
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
        if (!$this->sendToCacheRequest instanceof InstallCityCardSendToCacheRequest) {
            throw new \Exception('Object reference not set to an instance of InstallCityCardSendToCacheRequest');
        }
        $dtNow = new \DateTime();
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