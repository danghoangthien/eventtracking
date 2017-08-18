<?php
namespace Hyper\EventAPIBundle\Service\TrendCard\TotalSalesCard;

use Hyper\EventBundle\Service\Cached\TrendCard\TrendCardCached;
use Hyper\EventBundle\Service\Cached\App\AppCached;
use Hyper\EventAPIBundle\Service\TrendCard\TrendCardRetrieveFromCacheRequest;
use Hyper\EventAPIBundle\Service\TrendCard\TotalSalesCard\TotalSalesCardResponse;
use Hyper\EventAPIBundle\Service\TrendCard\TotalSalesCard\TotalSalesCardSendToCacheRequest;
use Hyper\Domain\Action\ActionRepository;
use Hyper\Domain\Application\ApplicationTitleRepository;
use Hyper\Domain\Application\ApplicationPlatformRepository;
use Hyper\Domain\Client\ClientAppTitleRepository;

class TotalSalesCardHandler
{
    protected $trencardCached;
    protected $appCached;
    protected $actionRepo;
    protected $hyperESClient;
    protected $indexVersion;

    public function __construct(
        TrendCardCached $trencardCached
        , AppCached $appCached
        , ActionRepository $actionRepo
        , ApplicationTitleRepository $appTitleRepo
        , ApplicationPlatformRepository $appPlatformRepo
        , ClientAppTitleRepository $clientAppTitleRepo
    )
    {
        $this->trencardCached = $trencardCached;
        $this->appCached = $appCached;
        $this->actionRepo = $actionRepo;
        $this->appTitleRepo = $appTitleRepo;
        $this->appPlatformRepo = $appPlatformRepo;
        $this->clientAppTitleRepo = $clientAppTitleRepo;
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
            $dataCached instanceof TotalSalesCardResponse
            && $dataCached->expired()
        ) {
            $this->trencardCached->hdel($retrieveFromCacheRequest->queueId());
            return true;
        }
        if (!$dataCached instanceof TotalSalesCardSendToCacheRequest) {
            throw new \Exception('data not instanceof TotalSalesCardSendToCacheRequest.');
        }

        $response = new TotalSalesCardResponse(
            $retrieveFromCacheRequest->queueId()
            , 1
            , [
                'total_revenue' => $this->roundTotalSales(
                    $this->totalSales(
                        $dataCached->appId()
                        , $dataCached->timestamp()
                    )
                )
            ]
            , $retrieveFromCacheRequest
            , $dataCached
        );
        $this->trencardCached->hset($retrieveFromCacheRequest->queueId(), serialize($response));

    }

    protected function roundTotalSales($number)
    {
        return round($number , 2);
    }

    protected function totalSales($appId, $timestamp)
    {
        $listAppId = [];
        if (!is_array($appId)) {
            $listAppId[] = $appId;
        } else {
            $listAppId = $appId;
        }
        if (empty($listAppId)) {
            return 0;
        }

        return $this->actionRepo->totalSales($listAppId, $timestamp);

    }
}