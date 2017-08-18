<?php

namespace Hyper\EventAPIBundle\Controller\V1\AnalyticMetadata;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\JsonResponse,
    Symfony\Component\HttpFoundation\Response,
    Hyper\EventAPIBundle\Controller\APIBaseController
    , Hyper\EventBundle\Service\Cached\AnalyticMetadata\RecentInAppEventCached
    , Hyper\EventBundle\Service\Cached\InappeventConfig\InappeventConfigCached
    , Hyper\EventAPIBundle\Service\AnalyticMetadata\RecentInAppEventService\RecentInAppEventService
    , Hyper\EventAPIBundle\Service\AnalyticMetadata\RecentInAppEventService\ValueObject\RecentInAppEventRequest;

class RecentInAppEventController extends APIBaseController
{
	const RECENT_LIMIT = 5;
	public function indexAction(Request $request)
	{
		try {
			$recentInAppEventService = new RecentInAppEventService(
				new RecentInAppEventRequest($request->query->get('client_id'))
				, $this->container->get('client_repository')
				, $this->container->get('application_title_repository')
				, $this->container->get('client_app_title_repository')
				, $this->container->get('application_platform_repository')
				, new RecentInAppEventCached($this->container)
				, new InappeventConfigCached($this->container)
			);
			$listRecentInAppEvent = $recentInAppEventService->execute();
			$result = $this->parseToResult($listRecentInAppEvent);
			$dataOutput = [
	            'status_code' => Response::HTTP_OK,
	            'result'=> $result
		    ];
		    return new JsonResponse($dataOutput, Response::HTTP_OK);
		} catch (\Exception $e) {
			$dataOutput = [
	        	'status_code' => Response::HTTP_BAD_REQUEST,
	            'message'=> $e->getMessage()
	       	];
			 return new JsonResponse($dataOutput, Response::HTTP_BAD_REQUEST);
		}
	}

	protected function parseToResult($listRecentInAppEvent)
	{
		$ret = [];
		foreach ($listRecentInAppEvent as $recentInAppEvent) {
			$eventName = $recentInAppEvent->eventName();
			if ($recentInAppEvent->eventFriendlyName()) {
				$eventName = $recentInAppEvent->eventFriendlyName();
			}
			$ret[] = [
				'id' => $recentInAppEvent->actionId()
				, 'icon' => $recentInAppEvent->icon()
				, 'color' => $recentInAppEvent->color()
				, 'amount_usd' => $recentInAppEvent->amountUsd()
				, 'tag_as_iap' => $recentInAppEvent->tagAsIAP()
				,'event_friendly_name' => $eventName
				, 'happened_at_dt' => date('Y-m-d H:i:s',$recentInAppEvent->happenedAt())
				, 'app_name' => $recentInAppEvent->appName()
				, 'app_platform' => $recentInAppEvent->appPlatform()
			];
		}

		return $ret;
	}
}
