<?php
namespace Hyper\EventAPIBundle\Service\TrendCard;

use Hyper\EventBundle\Service\Cached\TrendCard\TrendCardCached;
use Hyper\EventAPIBundle\Service\TrendCard\TrendCardSendToCacheRequestInterface;

class SendToCacheHandler
{
    protected $trencardCached;

    public function __construct(
        TrendCardCached $trencardCached
    )
    {
        $this->trencardCached = $trencardCached;
    }

    public function handle(TrendCardSendToCacheRequestInterface $sendToCacheRequest)
    {
        $serialize = serialize($sendToCacheRequest);
        $hashKey = md5($sendToCacheRequest->queueId());
        $this->trencardCached->hset($hashKey, $serialize);

        return $hashKey;
    }
}