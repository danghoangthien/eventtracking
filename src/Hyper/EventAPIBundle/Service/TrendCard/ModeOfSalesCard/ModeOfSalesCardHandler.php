<?php
namespace Hyper\EventAPIBundle\Service\TrendCard\ModeOfSalesCard;

use Hyper\EventBundle\Service\Cached\TrendCard\TrendCardCached;
use Hyper\EventBundle\Service\Cached\App\AppCached;
use Hyper\EventAPIBundle\Service\TrendCard\TrendCardRetrieveFromCacheRequest;
use Hyper\EventAPIBundle\Service\TrendCard\ModeOfSalesCard\ModeOfSalesCardResponse;
use Hyper\EventAPIBundle\Service\TrendCard\ModeOfSalesCard\ModeOfSalesCardSendToCacheRequest;
use Hyper\Domain\Action\ActionRepository;

class ModeOfSalesCardHandler
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
            $dataCached instanceof ModeOfSalesCardResponse
            && $dataCached->expired()
        ) {
            $this->trencardCached->hdel($retrieveFromCacheRequest->queueId());
            return true;
        }
        if (!$dataCached instanceof ModeOfSalesCardSendToCacheRequest) {
            throw new \Exception('data not instanceof ModeOfSalesCardSendToCacheRequest.');
        }
        $response = new ModeOfSalesCardResponse(
            $retrieveFromCacheRequest->queueId()
            , 1
            , [
                'most_popular_amount' => $this->roundMostPopularAmount(
                    $this->mostPopularAmount(
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

    protected function getIndexFromAppId($appId)
    {
        return $this->appCached->hget($appId) . '_' .$this->indexVersion;
    }

    protected function roundMostPopularAmount($number)
    {
        return round($number , 2);
    }

    protected function mostPopularAmount($appId, $timestamp, $inAppPurchase)
    {
        return $this->actionRepo->mostPopularAmount($appId, $timestamp, $inAppPurchase);
    }
}