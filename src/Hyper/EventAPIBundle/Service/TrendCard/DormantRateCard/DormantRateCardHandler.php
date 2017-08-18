<?php
namespace Hyper\EventAPIBundle\Service\TrendCard\DormantRateCard;

use Hyper\EventBundle\Service\Cached\TrendCard\TrendCardCached;
use Hyper\EventBundle\Service\Cached\App\AppCached;
use Hyper\EventAPIBundle\Service\TrendCard\TrendCardRetrieveFromCacheRequest;
use Hyper\EventAPIBundle\Service\TrendCard\DormantRateCard\DormantRateCardResponse;
use Hyper\EventAPIBundle\Service\TrendCard\DormantRateCard\DormantRateCardSendToCacheRequest;
use Hyper\EventBundle\Service\HyperESClient;

class DormantRateCardHandler
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
            $dataCached instanceof DormantRateCardResponse
            && $dataCached->expired()
        ) {
            $this->trencardCached->hdel($retrieveFromCacheRequest->queueId());
            return true;
        }
        if (!$dataCached instanceof DormantRateCardSendToCacheRequest) {
            throw new \Exception('data not instanceof DormantRateCardSendToCacheRequest.');
        }
        $response = new DormantRateCardResponse(
            $retrieveFromCacheRequest->queueId()
            , 1
            , [
                'rate_percent' => $this->getRatePercent(
                    $dataCached->appId()
                    , $dataCached->timestamp()
                )
            ]
            , $retrieveFromCacheRequest
            , $dataCached
        );
        $this->trencardCached->hset($retrieveFromCacheRequest->queueId(), serialize($response));

    }

    protected function getIndexFromAppId($appId)
    {
        return $this->appCached->hget($appId) .'_'. $this->indexVersion;
    }
    protected function getRatePercent($type, $timestamp){
        $index = $this->getIndexFromAppId($type);
        $totalUser = $this->getTotalUser($index, $type);
        if($totalUser == 0){
            return 0;
        }
        $totalActionUserRecent30Days = $this->getTotalActionUserRecent30Days($index, $type);
        $rate = (($totalUser - $totalActionUserRecent30Days) / $totalUser) * 100;
        return round($rate, 2);
    }

    private function getTotalUser($index, $type){
        $search = new \Elastica\Search($this->hyperESClient);
		$search->addIndex($index);
		$search->addType($type);

        $query = new \Elastica\Query();

        $matchAllQuery = new \Elastica\Query\MatchAll();
        $query->setQuery($matchAllQuery);

    	$carDevice = new \Elastica\Aggregation\Cardinality('count_unique_device');
        $carDevice->setField("device_id");
    	$query->addAggregation($carDevice);

    	$query->setSize(0);
    	$search->setQuery($query);
		$resultSet = $search->search();
		//echo "<pre>";
		//print_r($resultSet->getAggregation('count_unique_device'));exit;
        return $resultSet->getAggregation('count_unique_device')['value'];

    }
    private function getTotalActionUserRecent30Days($index, $type){
        $search = new \Elastica\Search($this->hyperESClient);
		$search->addIndex($index);
		$search->addType($type);
        $query = new \Elastica\Query();
        $rangeQuery = new \Elastica\Query\Range();
        $rangeQuery->addField('happened_at', ['gte'=> strtotime('-30 days'), 'lte'=> time()]);
        $query->setQuery($rangeQuery);

    	$carDevice = new \Elastica\Aggregation\Cardinality('count_unique_device');
        $carDevice->setField("device_id");
    	$query->addAggregation($carDevice);

    	$query->setSize(0);
    	$search->setQuery($query);
		$resultSet = $search->search();
		//echo "<pre>";
		//print_r($resultSet->getAggregation('count_unique_device'));exit;
        return $resultSet->getAggregation('count_unique_device')['value'];
    }
}