<?php
namespace Hyper\EventAPIBundle\Service\TrendCard\InstallCityCard;

use Hyper\EventBundle\Service\Cached\TrendCard\TrendCardCached;
use Hyper\EventBundle\Service\Cached\App\AppCached;
use Hyper\EventAPIBundle\Service\TrendCard\TrendCardRetrieveFromCacheRequest;
use Hyper\EventAPIBundle\Service\TrendCard\InstallCityCard\InstallCityCardResponse;
use Hyper\EventAPIBundle\Service\TrendCard\InstallCityCard\InstallCityCardSendToCacheRequest;
use Hyper\Domain\Action\ActionRepository;

class InstallCityCardHandler
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
            $dataCached instanceof InstallCityCardResponse
            && $dataCached->expired()
        ) {
            $this->trencardCached->hdel($retrieveFromCacheRequest->queueId());
            return true;
        }
        if (!$dataCached instanceof InstallCityCardSendToCacheRequest) {
            throw new \Exception('data not instanceof InstallCityCardSendToCacheRequest.');
        }


        $response = new InstallCityCardResponse(
            $retrieveFromCacheRequest->queueId()
            , 1
            , $this->queueBody($dataCached->appId(), $dataCached->timestamp())
            , $retrieveFromCacheRequest
            , $dataCached
        );
        $this->trencardCached->hset($retrieveFromCacheRequest->queueId(), serialize($response));

    }

    protected function queueBody(
        $appId
        , $timestamp
    )
    {
        $top1CountryInstall = $this->actionRepo->top1CountryInstall(
            $appId
            , $timestamp
        );
        if (empty($top1CountryInstall['country_code'])) {
            return [
                'rate_percent' => 0
                , 'top_city' => []
            ];
        }
        $top3CityInstall = $this->actionRepo->top3CityInstallFromCountry(
            $appId
            , $timestamp
            , $top1CountryInstall['country_code']
        );
        if (empty($top3CityInstall)) {
            return [
                'rate_percent' => 0
                , 'top_city' => []
            ];
        }
        $totalInstallFromCountry = $top1CountryInstall['install_count'];
        $totalInstallFrom3City = $this->totalInstallFrom3City($top3CityInstall);

        return [
            'rate_percent' => $this->roundRatePercent($this->calculateRatePercent($totalInstallFromCountry, $totalInstallFrom3City))
            , 'top_city' => $this->top3City($top3CityInstall)
        ];
    }

    protected function top3City($records)
    {
        $ret = [];
        foreach ($records as $record) {
            $ret[] = $record['city'];
        }

        return $ret;
    }

    protected function totalInstallFrom3City($top3CityInstall)
    {
        $ret = 0;
        foreach ($top3CityInstall as $record) {
            $ret += $record['install_count'];
        }

        return $ret;
    }

    protected function calculateRatePercent($totalInstallFromCountry, $totalInstallFrom3City)
    {
        if ($totalInstallFromCountry == 0 || $totalInstallFrom3City == 0) {
            return 0;
        }

        return ($totalInstallFrom3City / $totalInstallFromCountry * 100);
    }

    protected function roundRatePercent($number)
    {
        return round($number , 2);
    }
}