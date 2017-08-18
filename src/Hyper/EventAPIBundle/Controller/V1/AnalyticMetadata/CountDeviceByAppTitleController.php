<?php

namespace Hyper\EventAPIBundle\Controller\V1\AnalyticMetadata;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\JsonResponse,
    Symfony\Component\HttpFoundation\Response,
    Hyper\EventAPIBundle\Controller\APIBaseController,
    Hyper\EventBundle\Service\Cached\AnalyticMetadata\CountDeviceByAppTitleCached;

class CountDeviceByAppTitleController extends APIBaseController
{
	public function indexAction(Request $request)
	{
	    $clientId = $request->get('client_id');
	    $countDeviceByAppTitleCached = new CountDeviceByAppTitleCached($this->container);
	    $result = [];
	    //$clientId = '562048bacf7d40.91349663';
	    if (empty($clientId)) {
	        $tmpResult = $countDeviceByAppTitleCached->hgetall();
	        foreach ($tmpResult as $tmpJsonResult) {
	        	$jsonResult = json_decode($tmpJsonResult, true);
	        	foreach ($jsonResult as $appTitleId => $listAppTitle) {
	        		$result[$appTitleId] = $listAppTitle;
	        	}
	        }
	    } else {
	    	$result = $countDeviceByAppTitleCached->hget($clientId);
	    	if ($result) {
		    	$result = json_decode($result, true);
		    }
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
		foreach ($result as $appTitleId => $listAppTitle) {
			foreach ($listAppTitle as $appTitle) {
				$ret[$appTitle['client_name']][$appTitle['app_title']][$appTitle['platform']][$appTitle['app_id']] = $appTitle['device_count'];
			}
		}

		return $ret;
	}
}
