<?php
namespace Hyper\EventAPIBundle\Service\TrendCard\IAEStatsCard;

use Hyper\EventBundle\Service\Cached\TrendCard\TrendCardCached;
use Hyper\EventBundle\Service\Cached\App\AppCached;
use Hyper\EventAPIBundle\Service\TrendCard\TrendCardRetrieveFromCacheRequest;
use Hyper\EventAPIBundle\Service\TrendCard\IAEStatsCard\IAEStatsCardResponse;
use Hyper\EventAPIBundle\Service\TrendCard\IAEStatsCard\IAEStatsCardSendToCacheRequest;
use Hyper\EventBundle\Service\HyperESClient;

class IAEStatsCardHandler
{
    protected $trencardCached;
    protected $appCached;
    protected $hyperESClient;
    protected $indexVersion;

    public function __construct(
        TrendCardCached $trencardCached
        , AppCached $appCached
        , HyperESClient $hyperESClient
        , $indexVersion
    )
    {
        $this->trencardCached = $trencardCached;
        $this->appCached = $appCached;
        $this->hyperESClient = $hyperESClient->getClient();
        $this->indexVersion = $indexVersion;
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
            $dataCached instanceof IAEStatsCardResponse
            && $dataCached->expired()
        ) {
            $this->trencardCached->hdel($retrieveFromCacheRequest->queueId());
            return true;
        }
        if (!$dataCached instanceof IAEStatsCardSendToCacheRequest) {
            throw new \Exception('data not instanceof IAEStatsCardSendToCacheRequest.');
        }

        $countEventGivenLastMonth = $this->countEventGivenLastMonth(
            $dataCached->appId()
            , $dataCached->eventName()
            , $dataCached->timestamp()
        );
        $countEventGivenThisMonth = $this->countEventGivenThisMonth(
            $dataCached->appId()
            , $dataCached->eventName()
            , $dataCached->timestamp()
        );

        $queueBody = $this->queueBody($countEventGivenThisMonth, $countEventGivenLastMonth);
        $response = new IAEStatsCardResponse(
            $retrieveFromCacheRequest->queueId()
            , 1
            , $queueBody
            , $retrieveFromCacheRequest
            , $dataCached
        );
        $this->trencardCached->hset($retrieveFromCacheRequest->queueId(), serialize($response));

    }

    protected function queueBody(
        $countEventGivenThisMonth
        , $countEventGivenLastMonth
    )
    {
        if ($countEventGivenThisMonth == 0 && $countEventGivenLastMonth == 0) {
            return [
                'rate_percent'=> 0
                , 'operator' => '='
            ];
        }
        if ($countEventGivenThisMonth == 0 && $countEventGivenLastMonth != 0) {
            return [
                'event_count'=> $countEventGivenLastMonth
                , 'operator' => '<'
            ];
        }

        if ($countEventGivenThisMonth != 0 && $countEventGivenLastMonth == 0) {
            return [
                'event_count'=> $countEventGivenThisMonth
                , 'operator' => '>'
            ];
        }

        $perecent = $this->calculateRatePercent($countEventGivenThisMonth, $countEventGivenLastMonth);

        return [
            'rate_percent' => $this->roundRatePercent(abs($perecent))
            , 'operator' => $this->operatorFrom($perecent)
        ];

    }

    protected function calculateRatePercent($countEventGivenThisMonth, $countEventGivenLastMonth)
    {
        return ($countEventGivenThisMonth / $countEventGivenLastMonth * 100) - 100;
    }

    protected function operatorFrom($perecent)
    {
        $operator = '';
        if ($perecent < 0) {
            $operator = '<';
        } else if ($perecent > 0) {
            $operator = '>';
        } else {
            $operator = '=';
        }

        return $operator;
    }


    protected function roundRatePercent($number)
    {
        return round($number , 2);
    }

    protected function countEventGivenThisMonth(
        $appId
        , $eventName
        , $timestamp
    )
    {
        $dtNow = new \DateTime();
        $dt = new \DateTime('@'.$timestamp);
        $dt->setTimezone($dtNow->getTimezone());
        $gteDt = clone $dt;
        $lteDt = clone $dt;
        $gteDt->modify('first day of last month');
        $lteDt->modify('last day of last month');
        $rangeQuery = new \Elastica\Query\Range(
            'happened_at'
            , [
                'gte' => $gteDt->getTimestamp()
                , 'lte' => $lteDt->getTimestamp()
            ]
        );
        $termQuery = new \Elastica\Query\Term();
        $termQuery->setTerm('event_name', $eventName);
        $boolQuery = new \Elastica\Query\Bool();
        $boolQuery
            ->addMust($termQuery)
            ->addMust($rangeQuery);
        $elasticaSearch = new \Elastica\Search($this->hyperESClient);
        $elasticaSearch->addIndex($this->getIndexFromAppId($appId));
        $elasticaSearch->addType($appId);
        $elasticaSearch->setQuery($boolQuery);
        $count = $elasticaSearch->count();

        return $count;
    }

    protected function countEventGivenLastMonth(
        $appId
        , $eventName
        , $timestamp
    )
    {
        $dtNow = new \DateTime();
        $dt = new \DateTime('@'.$timestamp);
        $dt->setTimezone($dtNow->getTimezone());
        $gteDt = clone $dt;
        $lteDt = clone $dt;
        $gteDt->modify('first day of this month')
            ->modify('-2 months');
        $lteDt->modify('last day of this month')
            ->modify('-2 months');
        $rangeQuery = new \Elastica\Query\Range(
            'happened_at'
            , [
                'gte' => $gteDt->getTimestamp()
                , 'lte' => $lteDt->getTimestamp()
            ]
        );
        $termQuery = new \Elastica\Query\Term();
        $termQuery->setTerm('event_name', $eventName);
        $boolQuery = new \Elastica\Query\Bool();
        $boolQuery
            ->addMust($termQuery)
            ->addMust($rangeQuery);
        $elasticaSearch = new \Elastica\Search($this->hyperESClient);
        $elasticaSearch->addIndex($this->getIndexFromAppId($appId));
        $elasticaSearch->addType($appId);
        $elasticaSearch->setQuery($boolQuery);
        $count = $elasticaSearch->count();

        return $count;
    }

    protected function getIndexFromAppId($appId)
    {
        return $this->appCached->hget($appId) . '_' .$this->indexVersion;
    }
}