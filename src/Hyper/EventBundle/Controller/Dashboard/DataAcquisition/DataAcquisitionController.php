<?php

namespace Hyper\EventBundle\Controller\Dashboard\DataAcquisition;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\JsonResponse,
    Hyper\Domain\Device\Device;

class DataAcquisitionController extends Controller
{
    const RECENT_LOGIN_SIZE = 3;
    const MAX_TOP_COUNTRY = 10;

    public function indexAction(Request $request)
    {
        return $this->render('::data_acquisition/index.html.twig', array(
        ));
    }

    public function renderRecentLoginAction(Request $request)
    {
        $ulhRepo = $this->get('user_login_history_repository');
        $listRecentLogin = $ulhRepo->getListRecentLogin(self::RECENT_LOGIN_SIZE);

        return $this->render('::data_acquisition/_recent_login.html.twig', array(
            'list_recent_login' => $listRecentLogin
        ));
    }

    public function loadCountDeviceByPlatformAction(Request $request)
    {
        $result = $this->container->get('hyper_event.event_api_resful')->analyticCountDeviceByPlatform();
        $totalClient = 0;
        $listClientName = [];
        $totalAndroid = 0;
        $totalIOS = 0;
        $totalDevice = 0;
        $listIOSCountByClient = [];
        $listAndroidCountByClient = [];
        if (!empty($result)) {
            foreach ($result as $client => $listPlatform) {
                $totalClient++;
                $listClientName[] = $client;
                $iosCountByClient = 0;
                $androidCountByClient = 0;
                foreach ($listPlatform as $platform => $listAppId) {
                    foreach ($listAppId as $appId => $profile) {
                        $totalDevice += $profile;
                        if ($platform == Device::ANDROID_PLATFORM_CODE) {
                            $totalAndroid += $profile;
                            $androidCountByClient += $profile;
                        } elseif ($platform == Device::IOS_PLATFORM_CODE) {
                            $totalIOS += $profile;
                            $iosCountByClient += $profile;
                        }
                    }
                }
                $listIOSCountByClient[] = $iosCountByClient;
                $listAndroidCountByClient[] = $androidCountByClient;
            }
        }

        $resp = [
            'total_client' => $totalClient,
            'total_android' => $totalAndroid,
            'total_ios' => $totalIOS,
            'total_device' => $totalDevice,
            'list_client_name' => $listClientName,
            'list_ios_count_by_client' => $listIOSCountByClient,
            'list_android_count_by_client' => $listAndroidCountByClient
        ];

        return new JsonResponse($resp);
    }

    public function loadCountDeviceByAppTitleAction(Request $request)
    {
        $result = $this->container->get('hyper_event.event_api_resful')->analyticCountDeviceByAppTitle();
        $totalClient = 0;
        $totalAndroid = 0;
        $totalIOS = 0;
        $totalDevice = 0;
        $listIOSCountByAppTitle = [];
        $listAndroidCountByAppTitle = [];
        $listAppTitleByClient = [];
        if (!empty($result)) {
            foreach ($result as $client => $listAppTitle) {
                $totalClient++;
                foreach ($listAppTitle as $appTitle => $listPlatform) {
                    $listAppTitleByClient[] = $appTitle;
                    $iosCountByAppTitle = 0;
                    $androidCountByAppTitle = 0;
                    foreach ($listPlatform as $platform => $listAppId) {
                        foreach ($listAppId as $appId => $profile) {
                            $totalDevice += $profile;
                            if ($platform == Device::ANDROID_PLATFORM_CODE) {
                                $totalAndroid += $profile;
                                $androidCountByAppTitle += $profile;
                            } elseif ($platform == Device::IOS_PLATFORM_CODE) {
                                $totalIOS += $profile;
                                $iosCountByAppTitle += $profile;
                            }
                        }
                    }
                    $listIOSCountByAppTitle[] = $iosCountByAppTitle;
                    $listAndroidCountByAppTitle[] = $androidCountByAppTitle;
                }

            }
        }

        $resp = [
            'total_client' => $totalClient,
            'total_android' => $totalAndroid,
            'total_ios' => $totalIOS,
            'total_device' => $totalDevice,
            'list_app_title' => $listAppTitleByClient,
            'list_ios_count_by_app_title' => $listIOSCountByAppTitle,
            'list_android_count_by_app_title' => $listAndroidCountByAppTitle
        ];

        return new JsonResponse($resp);
    }



    /*public function loadCountDeviceByAppTitleAction(Request $request)
    {
        $result = $this->container->get('hyper_event.event_api_resful')->analyticCountDeviceByAppTitle();
        $listAppTitle = [];
        $listIOSCountByAppTitle = [];
        $listAndroidCountByAppTitle = [];
        if (!empty($result)) {
            foreach ($result as $appTitle => $listPlatform) {
                $listAppTitle[] = $appTitle;
                $iosCountByAppTitle = 0;
                $androidCountByAppTitle = 0;
                foreach ($listPlatform as $platform => $listAppId) {
                    foreach ($listAppId as $appId => $profile) {
                        if ($platform == Device::ANDROID_PLATFORM_CODE) {
                            $totalAndroid += $profile;
                            $iosCountByAppTitle += $profile;
                        } elseif ($platform == Device::IOS_PLATFORM_CODE) {
                            $totalIOS += $profile;
                            $androidCountByAppTitle += $profile;
                        }
                    }
                }
                $listIOSCountByAppTitle[] = $iosCountByAppTitle;
                $listAndroidCountByAppTitle[] = $androidCountByAppTitle;
            }
        }

        $resp = [
            'list_app_title' => $listAppTitle,
            'list_ios_count_by_app_title' => $listIOSCountByAppTitle,
            'list_android_count_app_title' => $listAndroidCountByAppTitle
        ];

        return new JsonResponse($resp);
    }*/

    public function loadCountDeviceByCountryAction(Request $request)
    {
        $result = $this->container->get('hyper_event.event_api_resful')->analyticCountDeviceByCountry();
        $listCountry = [];
        $listCountryCount = [];
        /**
        $result = [
            "ID" => [
                1 => 2,
                2 => 0
            ],
            "VN" => [
                2 => 2
            ]
        ];
        **/
        if (!empty($result)) {
            foreach ($result as $country => $listPlatform) {
                foreach ($listPlatform as $platform => $count) {
                    if (!$count) {
                        continue;
                    }
                    $listCountry[$platform][] = $country;
                    $listCountryCount[$platform][] = $count;
                }
            }
        }
        if (!empty($listCountry[Device::ANDROID_PLATFORM_CODE])) {
            list($listCountryByPlatform, $listCountryCountByPlatform) = $this->makeListTopCountryByPlatform(Device::ANDROID_PLATFORM_CODE, $listCountry, $listCountryCount);
            $listCountry[Device::ANDROID_PLATFORM_CODE] = $listCountryByPlatform;
            $listCountryCount[Device::ANDROID_PLATFORM_CODE] = $listCountryCountByPlatform;
        }
        if (!empty($listCountry[Device::IOS_PLATFORM_CODE])) {
            list($listCountryByPlatform, $listCountryCountByPlatform) = $this->makeListTopCountryByPlatform(Device::IOS_PLATFORM_CODE, $listCountry, $listCountryCount);
            $listCountry[Device::IOS_PLATFORM_CODE] = $listCountryByPlatform;
            $listCountryCount[Device::IOS_PLATFORM_CODE] = $listCountryCountByPlatform;
        }
        $resp = [
            'list_country' => $listCountry,
            'list_country_count' => $listCountryCount
        ];
        return new JsonResponse($resp);
    }

    private function makeListTopCountryByPlatform($platform, $listCountry, $listCountryCount)
    {
        $listCountryCountByPlatform = [];
        $listCountryByPlatform = [];
        if (!empty($listCountryCount[$platform])) {
            arsort($listCountryCount[$platform]);
            $i = 0;
            foreach ($listCountryCount[$platform] as $key => $value) {
                $listCountryCountByPlatform[] = $value;
                $listCountryByPlatform[] = $listCountry[$platform][$key];
                $i++;
                if (self::MAX_TOP_COUNTRY == $i) {
                    break;
                }
            }
        }

        return array($listCountryByPlatform, $listCountryCountByPlatform);
    }
}