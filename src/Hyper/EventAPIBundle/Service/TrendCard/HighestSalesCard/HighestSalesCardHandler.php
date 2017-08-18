<?php
namespace Hyper\EventAPIBundle\Service\TrendCard\HighestSalesCard;

use Hyper\EventBundle\Service\Cached\TrendCard\TrendCardCached;
use Hyper\EventBundle\Service\Cached\App\AppCached;
use Hyper\EventAPIBundle\Service\TrendCard\TrendCardRetrieveFromCacheRequest;
use Hyper\EventAPIBundle\Service\TrendCard\HighestSalesCard\HighestSalesCardResponse;
use Hyper\EventAPIBundle\Service\TrendCard\HighestSalesCard\HighestSalesCardSendToCacheRequest;
use Hyper\Domain\Action\ActionRepository;

class HighestSalesCardHandler
{
    protected $trencardCached;
    protected $appCached;
    protected $actionRepo;

    public function __construct(
        TrendCardCached $trencardCached
        , AppCached $appCached
        , ActionRepository $actionRepo
    )
    {
        $this->trencardCached = $trencardCached;
        $this->appCached = $appCached;
        $this->actionRepo = $actionRepo;
    }

    public function handle(TrendCardRetrieveFromCacheRequest $retrieveFromCacheRequest)
    {
        if (!$this->trencardCached->hget($retrieveFromCacheRequest->queueId())) {
            throw new \InvalidArgumentException('given queue_id does not exist.');
        }

        $dataCached = $this->trencardCached->hget($retrieveFromCacheRequest->queueId());
        if ($dataCached) {
            $dataCached = unserialize($dataCached);
        }
        if (
            $dataCached instanceof HighestSalesCardResponse
            && $dataCached->expired()
        ) {
            $this->trencardCached->hdel($retrieveFromCacheRequest->queueId());
            return true;
        }
        if (!$dataCached instanceof HighestSalesCardSendToCacheRequest) {
            throw new \Exception('data not instanceof HighestSalesCardSendToCacheRequest.');
        }
        $response = new HighestSalesCardResponse(
            $retrieveFromCacheRequest->queueId()
            , 1
            , [
                'highest_amount' => $this->roundHighestAmount(
                    $this->highestAmount(
                        $dataCached->appId()
                        , $dataCached->timestamp()
                        , $dataCached->inAppPurchase()
                    )
                )
            ]
            , $retrieveFromCacheRequest
            , $dataCached
        );
        $this->trencardCached->hset($retrieveFromCacheRequest->queueId(), serialize($response));

    }

    protected function roundHighestAmount($number)
    {
        return round($number , 2);
    }

    protected function highestAmount($appId, $timestamp, $inAppPurchase)
    {
        return $this->actionRepo->highestAmount($appId, $timestamp, $inAppPurchase);

    }
}