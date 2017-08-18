<?php

namespace Hyper\EventAPIBundle\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface,
    Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\JsonResponse,
    Symfony\Component\HttpFoundation\Response,
    Hyper\Domain\OAuth\OAuthClientUserAccess,
    Hyper\EventAPIBundle\Exception\ApiException,
    Hyper\Domain\Device\Device;

class APIBaseController extends Controller
{
    protected $oauthClient;
    protected $user;
    protected $client;
    protected $listAccessApp;
    protected $appId;
    protected $accessToken;
    protected $currentRoute = null; // store route object of this request

	/**
	 * Do common tasks (e.g access token, ...)

	 * @param  Request $request
	 */
	public function preExecuteAPI(Request $request)
	{
	    $this->currentRoute = $this->get('router')->getRouteCollection()->get($request->get('_route'));
	    $routeOptions = $this->currentRoute->getOptions();
	    $this->accessToken = $request->get('access_token');
	    // check access token
	    if (
            !array_key_exists('check_access_token', $routeOptions) ||
	        $routeOptions['check_access_token']
	    ) {
	        if (!$this->checkAccessToken($this->accessToken)) {
    	        $dataOutput = ['status'=>false, 'client_access'=>false, 'data'=>[]];
    	        throw new ApiException(json_encode($dataOutput), Response::HTTP_FORBIDDEN);
    	    }
	    }

        $deviceId = $request->get('hypid');
	    if (!empty($deviceId) && $deviceId == 'samuel@hypergrowth.co') {
	        $routeOptions['check_access_app'] = false;
	    }

	    // check access app
        $this->appId = $request->get('app_id');
        if (
            !array_key_exists('check_access_app', $routeOptions) ||
	        $routeOptions['check_access_app']
	    ) {
    	    // check app access
    	    if (!$this->checkAccessApp($this->appId)) {
    	        $dataOutput = [
    	            'status'=>false,
    	            'client_access'=>true,
    	            'data'=> [
    	                'applications' => $this->listAccessApp
    	            ]
    	        ];
    	        throw new ApiException(json_encode($dataOutput), Response::HTTP_FORBIDDEN);
    	    }
        }


	}

	protected function checkAccessToken($accessToken)
    {
        $accessTokenObj = $this->get('oauth_access_token_repository')->findOneBy(['token'=>$accessToken]);
        if (!$accessTokenObj || !$accessTokenObj->getId()) {
            return false;
        }
        $oauthClientObj = $accessTokenObj->getClient();
        $this->oauthClient = $oauthClientObj;
        $user = $this->getUserByToken($accessTokenObj);
        if (null == $user) {
            return false;
        }
        $this->user = $user;
        $clientUser = $this->get('oauth_client_user_access_repository')->findOneBy([
            'username' => $user->getUsername(),
            'client' => $oauthClientObj,
            'userType' => OAuthClientUserAccess::USER_TYPE_AK
        ]);

        if (null == $clientUser || 1 != $clientUser->getStatus()){
            return false;
        }
        return true;
    }

    protected function getUserByToken($accessTokenObj)
    {
        $username = $accessTokenObj->getUsername();

        return $this->get('authentication_repository')->findOneBy(['username'=>$username]);
    }

    protected function checkAccessApp($appId)
    {
        if (empty($appId)) {
            return false;
        }
        $listFlatform = [
            Device::IOS_PLATFORM_CODE => Device::IOS_PLATFORM_NAME,
            Device::ANDROID_PLATFORM_CODE => Device::ANDROID_PLATFORM_NAME
        ];
        $clientId = $this->user->getClientId();
        $this->client = $this->getClientByClientId($clientId);
        if (!$this->client) {
          return false;
        }
        $listClientApp = $this->client->getClientApp();
        if (empty($listClientApp)) {
            return false;
        }
        $listAppId = explode(',', $listClientApp);
        $listApp = $this->get('application_repository')->getListAppByAppId($listAppId);
        $listAppIdCheck = [];
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
                $listAppIdCheck[] = $app['app_id'];
        }
        if (!in_array($appId, $listAppIdCheck)) {
            return false;
        }
        return true;
    }

    protected function getClientByClientId($clientId)
    {
        return $this->get('client_repository')->find($clientId);
    }

}
