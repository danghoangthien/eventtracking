<?php

namespace Hyper\EventAPIBundle\Controller\V1\AnalyticMetadata;

use Symfony\Bundle\FrameworkBundle\Controller\Controller; 
use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\JsonResponse,
    Symfony\Component\HttpFoundation\Response,
    Hyper\EventAPIBundle\Controller\APIBaseController,
    Hyper\Domain\Device\Device;

class CountDeviceByCountryController extends APIBaseController
{
	public function indexAction(Request $request)
	{
	    $clientId = $request->get('client_id');
	    if (empty($clientId)) {
	        $clientId = "all";
	    }
	    $this->cache = $this->container->get('snc_redis.default');
	    $jsonResult = $this->cache->hget($this->generateCacheKey(), $clientId);
	    if (empty($jsonResult)) {
	    	$dataOutput = [
	        	'status_code' => Response::HTTP_BAD_REQUEST,
	            'message'=> "Not found.",
	       	];
	        return new JsonResponse($dataOutput, Response::HTTP_BAD_REQUEST);
	    }
	    $result = json_decode($jsonResult, true);
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
			$ret[$tmp['country_code']] = [
				Device::IOS_PLATFORM_CODE => $tmp[Device::IOS_PLATFORM_CODE],
                Device::ANDROID_PLATFORM_CODE => $tmp[Device::ANDROID_PLATFORM_CODE]
			];
		}
		
		return $ret;
	}
	
	protected function generateCacheKey()
    {
        return md5('ANALYTIC_METADATA_COUNT_DEVICE_BY_COUNTRY');
    }
}
