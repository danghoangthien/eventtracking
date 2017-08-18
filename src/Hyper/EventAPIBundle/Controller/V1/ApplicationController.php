<?php

namespace Hyper\EventAPIBundle\Controller\V1;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\JsonResponse,
    Symfony\Component\HttpFoundation\Response,
    Hyper\EventAPIBundle\Controller\APIBaseController,
    Hyper\Domain\Device\Device,
    Hyper\EventAPIBundle\Exception\ApiException;

use Hyper\EventBundle\Service\Cached\AnalyticMetadata\CountDeviceByAppTitleCached;

class ApplicationController extends APIBaseController
{
	protected $listAccessApp;

	public function indexAction(Request $request)
	{
	    $dataOutput = [
            'status'=>true,
            'client_access'=>true
       	];
        $clientId = $this->user->getClientId();
        /*
        $this->client = $this->getClientByClientId($clientId);
        $listClientApp = $this->client->getClientApp();
        if (empty($listClientApp)) {
            $dataOutput['data']['applications'] = [];
			return new JsonResponse($dataOutput, Response::HTTP_OK);
        }*/
        $countDeviceByAppTitleCached = new CountDeviceByAppTitleCached($this->container);
        $countDeviceByAppTitleCachedData = $countDeviceByAppTitleCached->hget($clientId);
        $countDeviceByAppTitleCachedData = json_decode($countDeviceByAppTitleCachedData,true);
        $listAppId = [];
        foreach ($countDeviceByAppTitleCachedData as $appTitleData) {
            foreach( $appTitleData as $appTitle ){
                $listAppId[]=$appTitle['app_id'];
            }
        }
        $listApp = $this->get('application_repository')->getListAppByAppId($listAppId);
        if (empty($listApp)) {
        	$dataOutput['data']['applications'] = [];
			return new JsonResponse($dataOutput, Response::HTTP_OK);
        }
        $listFlatform = [
            Device::IOS_PLATFORM_CODE => Device::IOS_PLATFORM_NAME,
            Device::ANDROID_PLATFORM_CODE => Device::ANDROID_PLATFORM_NAME
        ];
        $listAccessApp = [];
        foreach ($listApp as $app) {
            $platform = '';
            if (!empty($listFlatform[$app['platform']])) {
                $platform = $listFlatform[$app['platform']];
            }
            $listAccessApp[] = [
                'app_id' => $app['app_id'],
                'app_name'=> $app['app_name'],
                'platform'=> $platform
            ];
        }
        $dataOutput['data']['applications'] = $listAccessApp;

		return new JsonResponse($dataOutput, Response::HTTP_OK);
	}

	public function listAccessAppAction(Request $request)
	{
		$accessToken = $request->get('access_token');
	    $accessTokenConfig = $this->container->getParameter('list_access_app_access_token');
	    if ($accessToken != $accessTokenConfig) {
	        $dataOutput = ['status'=>false, 'client_access'=>false, 'message'=>'Access token invalid.'];
	        return new JsonResponse($dataOutput, Response::HTTP_FORBIDDEN);
	    }
		$clientId = $request->get('client_id');
		$dataOutput = [
            'status'=>true,
            'client_access'=>true
       	];
		if (empty($clientId)) {
	       	$dataOutput['message'] = "Miss arguments.";
			return new JsonResponse($dataOutput, Response::HTTP_BAD_REQUEST);
		}
		$listClientId = explode(',', $clientId);
        $listClient = $this->get('client_repository')->findBy(['id' => $listClientId]);
        if (empty($listClient)) {
        	$dataOutput['message'] = "Client not found.";
			return new JsonResponse($dataOutput, Response::HTTP_BAD_REQUEST);
        }
        $listClientApp = '';
        foreach ($listClient as $client) {
        	$listClientApp[] = $client->getClientApp();
        }
        $listAppIdString = implode(",", $listClientApp);
        if (empty($listAppIdString)) {
            $dataOutput['data']['applications'] = [];
			return new JsonResponse($dataOutput, Response::HTTP_OK);
        }
        $listAppId = explode(',', $listAppIdString);
        $listAppId = array_unique($listAppId);
        if (empty($listAppId)) {
            $dataOutput['data']['applications'] = [];
			return new JsonResponse($dataOutput, Response::HTTP_OK);
        }
        $listApp = $this->get('application_repository')->getListAppByAppId($listAppId);
        if (empty($listApp)) {
        	$dataOutput['data']['applications'] = [];
			return new JsonResponse($dataOutput, Response::HTTP_OK);
        }
        $listFlatform = [
            Device::IOS_PLATFORM_CODE => Device::IOS_PLATFORM_NAME,
            Device::ANDROID_PLATFORM_CODE => Device::ANDROID_PLATFORM_NAME
        ];
        foreach ($listApp as $app) {
            $platform = '';
            if (!empty($listFlatform[$app['platform']])) {
                $platform = $listFlatform[$app['platform']];
            }
            $this->listAccessApp[] = [
                'app_id' => $app['app_id'],
                'app_name'=> $app['app_name'],
                'platform'=> $platform
            ];
        }
        $dataOutput['data']['applications'] = $this->listAccessApp;

		return new JsonResponse($dataOutput, Response::HTTP_OK);
	}
}
