<?php
namespace Hyper\EventAPIBundle\Service\TrendCard\TotalSalesCard;

use Hyper\EventAPIBundle\Service\TrendCard\TrendCardResponse;

class TotalSalesCardResponse extends TrendCardResponse
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
        return false;
    }
}