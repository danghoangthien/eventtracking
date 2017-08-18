<?php
namespace Hyper\EventAPIBundle\Service\TrendCard;

use Hyper\EventBundle\Service\Cached\TrendCard\TrendCardCached;
use Hyper\EventAPIBundle\Service\TrendCard\TrendCardRetrieveFromCacheRequestInterface;
use Hyper\EventAPIBundle\Service\TrendCard\TrendCardSendToCacheRequestInterface;
use Hyper\EventAPIBundle\Service\TrendCard\TrendCardReponseInterface;

class RetrieveFromCacheHandler
{
    protected $trencardCached;

    public function __construct(
        TrendCardCached $trencardCached
    )
    {
        $this->trencardCached = $trencardCached;
    }

    public function handle(TrendCardRetrieveFromCacheRequestInterface $retrieveFromCacheRequest)
    {
        $ret = [];
        if (!$this->trencardCached->hget($retrieveFromCacheRequest->queueId())) {
            throw new \InvalidArgumentException('given queue_id does not exist.');
        }
        $dataCached = $this->trencardCached->hget($retrieveFromCacheRequest->queueId());
        if ($dataCached) {
            $dataCached = unserialize($dataCached);
        }
        if ($dataCached instanceof TrendCardSendToCacheRequestInterface) {
            return [
                'queue_id' => $retrieveFromCacheRequest->queueId()
                , 'queue_status' => 0
                , 'queue_body' => []
            ];
        }
        if ($dataCached instanceof TrendCardReponseInterface) {
            return [
                'queue_id' => $dataCached->queueId()
                , 'queue_status' => $dataCached->queueStatus()
                , 'queue_body' => $dataCached->queueBody()
            ];
        }
    }
}