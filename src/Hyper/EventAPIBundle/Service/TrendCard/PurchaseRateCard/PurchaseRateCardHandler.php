<?php
namespace Hyper\EventAPIBundle\Service\TrendCard\PurchaseRateCard;

use Hyper\EventBundle\Service\Cached\TrendCard\TrendCardCached;
use Hyper\EventBundle\Service\Cached\App\AppCached;
use Hyper\EventAPIBundle\Service\TrendCard\TrendCardRetrieveFromCacheRequest;
use Hyper\EventAPIBundle\Service\TrendCard\PurchaseRateCard\PurchaseRateCardResponse;
use Hyper\EventAPIBundle\Service\TrendCard\PurchaseRateCard\PurchaseRateCardSendToCacheRequest;
use Hyper\EventBundle\Service\HyperESClient;

class PurchaseRateCardHandler
{
    const BATCH_NUMBER = 10000;


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
            $dataCached instanceof PurchaseRateCardResponse
            && $dataCached->expired()
        ) {
            $this->trencardCached->hdel($retrieveFromCacheRequest->queueId());
            return true;
        }
        if (!$dataCached instanceof PurchaseRateCardSendToCacheRequest) {
            throw new \Exception('data not instanceof HighestSalesCardSendToCacheRequest.');
        }
        $response = new PurchaseRateCardResponse(
            $retrieveFromCacheRequest->queueId()
            , 1
            , [
                'rate_percent' => $this->getRatePercent(
                    $dataCached->appId()
                    , $dataCached->timestamp()
                    , $dataCached->inAppPurchase()
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
    protected function getRatePercent($type, $timestamp, $inAppPurchase){

        $index = $this->getIndexFromAppId($type);
        $from = new \DateTime('first day of previous month');
        $from->setTime(00, 00, 00);
        $fromTime = $from->getTimestamp();
        $to = new \DateTime('last day of previous month');
        $to->setTime(23, 59, 59);
        $toTime = $to->getTimestamp();
        $cacheIds = [];
        $cacheType = $type . "_" . date('Ym', strtotime('last month'));
        $cacheIds = $this->getcacheIds($cacheType);
        //$cacheIds = $this->clearCache($cacheType, $cacheIds);
        if(count($cacheIds) == 0){
            $this->hyperESClient->request($index . '/_settings', \Elastica\Request::PUT,  ["index"=>["max_result_window" => 999999999]]);
            $this->createCache($index, $type, $fromTime, $toTime, $cacheType);
            $cacheIds = $this->getcacheIds($cacheType);
        }
        if(count($cacheIds) == 0){
            return 0;
        }
        $totalInstall = $this->getTotalDeviceInstallLastMonth($index, $type, $fromTime, $toTime);
        $totalPurchase = $this->getTotalPurchaseActionUserLastMonth($index, $type, $inAppPurchase, $fromTime, $cacheType, $cacheIds);
        if($totalInstall == 0){
            return 0;
        }
        $rate = ($totalPurchase / $totalInstall)*100;
        return round($rate, 2);
    }
    private function getTotalDeviceInstallLastMonth($index, $type, $fromTime, $toTime){
        $search = new \Elastica\Search($this->hyperESClient);
		$search->addIndex($index);
		$search->addType($type);

        $query = new \Elastica\Query();
        $termQuery = new \Elastica\Query\Term();
        $termQuery->setTerm('event_name', 'install');

        $rangeQuery = new \Elastica\Query\Range();
        $rangeQuery->addField('happened_at', ['gte'=> $fromTime, 'lte'=> $toTime]);

        $boolQuery = new \Elastica\Query\Bool();
        $boolQuery->addMust($termQuery);
        $boolQuery->addMust($rangeQuery);
        $query->setQuery($boolQuery);
    	$carDevice = new \Elastica\Aggregation\Cardinality("device_id");
        $carDevice->setField("device_id");
		$query->addAggregation($carDevice);
		$query->setSize(0);
    	$search->setQuery($query);
		$resultSet = $search->search();
		$aggs = $resultSet->getAggregations();
		//echo "<pre>";print_r($aggs);exit;
		return $aggs['device_id']['value'];

    }
    private function getListDeviceInstallLastMonth($index, $type, $fromTime, $toTime, $from, $size){
        $search = new \Elastica\Search($this->hyperESClient);
		$search->addIndex($index);
		$search->addType($type);

        $query = new \Elastica\Query();

        $termQuery = new \Elastica\Query\Term();
        $termQuery->setTerm('event_name', 'install');

        $rangeQuery = new \Elastica\Query\Range();
        $rangeQuery->addField('happened_at', ['gte'=> $fromTime, 'lte'=> $toTime]);

        $boolQuery = new \Elastica\Query\Bool();
        $boolQuery->addMust($termQuery);
        $boolQuery->addMust($rangeQuery);
        $query->setQuery($boolQuery);
    	$query->setSize($size);
    	$query->setFrom($from);
    	$query->setFields(['device_id']);
    	$search->setQuery($query);
		$resultSet = $search->search();
		$deviceIds = [];
		$results = $resultSet->getResults();
		//echo "<pre>";print_r($results);exit;
        foreach ($results as $result) {
            $deviceIds[] = $result->getFields()['device_id']['0'];
        }
        return $deviceIds;

    }
    private function cacheDeviceId($cacheType, $deviceIds){
        $esIndex = $this->hyperESClient->getIndex('device_install');
        $esType = $esIndex->getType($cacheType);
        $data = ['device_ids' => $deviceIds];
        $deviceIds = array_chunk($deviceIds, 1000);
        foreach ($deviceIds as $key => $value) {
            $document = new \Elastica\Document(md5(microtime()), ['device_ids' => $value]);
            $esType->addDocument($document);
        }
        $esType->getIndex()->refresh();
    }
    private function getcacheIds($cacheType){
        $search = new \Elastica\Search($this->hyperESClient);
		$search->addIndex('device_install');
		$search->addType($cacheType);
		$matchAllQuery = new \Elastica\Query\MatchAll();
		$query = new \Elastica\Query($matchAllQuery);
		$query->setSize(10000);
		$search->setQuery($query);
		$results = $search->search()->getResults();
		//echo "<pre>";print_r($results);exit;
		$cacheIds = [];
		foreach ($results as $result) {
		    $cacheIds[] = $result->getId();
		}
		return $cacheIds;
    }
    //get total user have purchase action from last month to now
    private function getTotalPurchaseActionUserLastMonth($index, $type, $inAppPurchase, $fromTime, $cacheType, $cacheIds){
        $toTime = time();
        $query = '	{
    					"query" : {
    						"bool": {
						    	"must" : [
						    		{ "range" : { "happened_at" : { "gte" : %from%, "lte" : %to% } } },
									{ "terms" : { "event_name" : ["%event_name%"] } },
									{
										"bool" : {
											"should" : [%should%]
										}
									}
						    	]
						    }
    					},
    					"size" : 0,
    					"aggs" : {
        					"distinct_device_id" : {
            					"cardinality" : {
            						"field" : "device_id"
        						}
        					}

    					}
					}';
		$temp = '{ "terms" :
                        { "device_id" : {
                            "index" : "device_install",
								"type" : "%cache_type%",
								"id" : "%cache_id%",
								"path" : "device_ids"
							}
		                }
				},';
		$should = "";
		foreach($cacheIds as $cacheId){
            $should .= str_replace(['%cache_type%','%cache_id%'], [$cacheType, $cacheId], $temp);
		}
		$query = str_replace(['%from%', '%to%', '%should%','%event_name%'], [$fromTime, $toTime, trim($should, ","), $inAppPurchase], $query);
		$url = $index . '/' . $type . '/_search';
        $response = $this->hyperESClient->request($url, \Elastica\Request::GET, $query);
		$data= $response->getData();
		//echo "<pre>";
		//print_r($data);exit;
		return $data['aggregations']['distinct_device_id']['value'];

    }
    //clear cache id for debug
    private function clearCache($cacheType, $cacheIds){
        if(count($cacheIds) == 0){
            return [];
        }
        $esIndex = $this->hyperESClient->getIndex('device_install');
        $esType = $esIndex->getType($cacheType);
        $esType->deleteIds($cacheIds);
        return [];
    }
    //create index store list device id installed last month
    private function createCache($index, $type, $fromTime, $toTime, $cacheType){
        $from = 0;
        $size = self::BATCH_NUMBER;
        $esIndex = $this->hyperESClient->getIndex('device_install');
        if(!$esIndex->exists()){
            $esIndex->create();
        }
        $esType = $esIndex->getType($cacheType);
        // Define mapping
        $mapping = new \Elastica\Type\Mapping();
        $mapping->setType($esType);
        // Set mapping
        $mapping->setProperties([
            'device_ids' => ['type' => 'string', 'include_in_all' => TRUE]
        ]);
        $mapping->send();
        for($i = 1; $i <= PHP_INT_MAX; $i++){
            $deviceIds = $this->getListDeviceInstallLastMonth($index, $type, $fromTime, $toTime, $from, $size);
            $this->cacheDeviceId($cacheType, $deviceIds);
            $from += $size;
            if(count($deviceIds) < $size){
                break;
            }
        }
    }


}