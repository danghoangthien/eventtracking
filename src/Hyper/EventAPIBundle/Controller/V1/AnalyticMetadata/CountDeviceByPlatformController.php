<?php

namespace Hyper\EventAPIBundle\Controller\V1\AnalyticMetadata;

use Symfony\Bundle\FrameworkBundle\Controller\Controller; 
use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\JsonResponse,
    Symfony\Component\HttpFoundation\Response,
    Hyper\EventAPIBundle\Controller\APIBaseController,
    Hyper\Domain\Device\Device;

class CountDeviceByPlatformController extends APIBaseController
{
	public function indexAction(Request $request)
	{
	    $clientId = $request->get('client_id');
	    $this->cache = $this->container->get('snc_redis.default');
	    if (empty($clientId)) {
	        $tmpResult = $this->cache->hgetall($this->generateCacheKey());
	        foreach ($tmpResult as $tmpJsonResult) {
	        	$listApp = json_decode($tmpJsonResult, true);
	        	foreach ($listApp as $app) {
	        		$result[] = $app;
	        	}
	        }
	    } else {
	    	$jsonResult = $this->cache->hget($this->generateCacheKey(), $clientId);
	    	$result = json_decode($jsonResult, true);
	    }
	    if (empty($result)) {
	    	$dataOutput = [
	        	'status_code' => Response::HTTP_BAD_REQUEST,
	            'message'=> "client not found.",
	       	];
	        return new JsonResponse($dataOutput, Response::HTTP_BAD_REQUEST);
	    }
	    
	    $result = $this->parseToResult($result);
	    
	    $dataOutput = [
            'status_code' => Response::HTTP_OK,
            'result'=> $result,
	    ];
	    
	    return new JsonResponse($dataOutput, Response::HTTP_OK);
	}
	
	protected function parseToResult($result)
	{
		$ret = [];
		foreach ($result as $tmp) {
			$ret[$tmp['client_name']][$tmp['platform']][$tmp['app_id']] = $tmp['device_count'];
		}
		
		return $ret;
	}
	
	protected function generateCacheKey()
    {
        return md5('ANALYTIC_METADATA_COUNT_DEVICE_BY_FLATFORM');
    }
}
