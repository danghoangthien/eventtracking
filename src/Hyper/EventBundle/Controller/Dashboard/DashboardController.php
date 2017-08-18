<?php

namespace Hyper\EventBundle\Controller\Dashboard;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\JsonResponse,
    Hyper\EventBundle\Form\Type\CreateCardByPopupType,
    Hyper\Domain\Filter\Filter,
    Hyper\EventBundle\Service\FilterService\Condition\DataType\UsageDataType,
    Hyper\EventBundle\Service\Cached\User\UserFilterCached,
    Hyper\Domain\Device\Device;

class DashboardController extends Controller
{
    const MAX_TOP_COUNTRY = 10;
    const MAX_RECENT_FILTER = 4;

    public function indexAction(Request $request)
    {
        $auth = $this->container->get('security.context')->getToken()->getUser();
        if ($auth->isDemoAccount()) {
            return $this->render('::dashboard/index_demo.html.twig');
        }
        $isLimitAccount = $auth->isLimitAccount();

        return $this->render('::dashboard/index.html.twig', ['isLimitAccount' => $isLimitAccount]);
    }

    public function landingAction(Request $request)
    {
        $landingPageForwarder = $this->generateUrl('dashboard_main',$request->query->all());
        return $this->redirect($landingPageForwarder);
    }

    public function loadRecentFilterAction(Request $request)
    {
        $auth = $this->get('security.context')->getToken()->getUser();
        $listRecentFilter = $this->get('filter_repository')->getRecentFilter(
            $auth->getId(),
            self::MAX_RECENT_FILTER
        );
        $filterTotalCount = $this->get('filter_repository')->getFilterTotalCountByAuth($auth->getId());
        $resp = [
            'status' => 1,
            'content' => $this->renderView('::dashboard/_recent_filter.html.twig', array(
                'listRecentFilter' => $listRecentFilter
            )),
            'filterTotalCount' => $filterTotalCount
        ];

        return new JsonResponse($resp);
    }

    public function loadCountDeviceByPlatformAction(Request $request)
    {
        $auth = $this->get('security.context')->getToken()->getUser();
        $result = $this->container->get('hyper_event.event_api_resful')->analyticCountDeviceByPlatform(array(
            'client_id' => $auth->getClientId()
        ));
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
            'total_device' => $totalDevice
        ];

        return new JsonResponse($resp);
    }

    public function loadCountDeviceByAppTitleAction(Request $request)
    {
        $auth = $this->get('security.context')->getToken()->getUser();
        $result = $this->container->get('hyper_event.event_api_resful')->analyticCountDeviceByAppTitle(array(
            'client_id' => $auth->getClientId()
        ));
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

    public function loadCountDeviceAndEventByAppTitleAction(Request $request)
    {
        $auth = $this->get('security.context')->getToken()->getUser();
        $result = $this->container->get('hyper_event.event_api_resful')->analyticCountDeviceAndEventByAppTitle(array(
            'client_id' => $auth->getClientId()
        ));
        $listDeviceAndEventCountByApptitle = [];
        if (!empty($result)) {
            //echo "<pre>";
            //var_dump($result);
            foreach ($result as $client => $listAppTitle) {
                foreach ($listAppTitle as $appTitle => $listPlatform) {
                    $listDeviceAndEventCountByApptitle[$appTitle]= [];
                    foreach ($listPlatform as $platform => $listAppId) {
                        $listDeviceAndEventCountByApptitle[$appTitle][$platform]['device_count'] = 0;
                        $listDeviceAndEventCountByApptitle[$appTitle][$platform]['event_count'] = 0;
                        foreach ($listAppId as $appId => $count) {
                            $listDeviceAndEventCountByApptitle[$appTitle][$platform]['device_count'] += $count['device_count'];
                            $listDeviceAndEventCountByApptitle[$appTitle][$platform]['event_count'] += $count['event_count'];
                            $listDeviceAndEventCountByApptitle[$appTitle][$platform]['ghost_user_count'] = $count['ghost_user_count'];
                            $listDeviceAndEventCountByApptitle[$appTitle][$platform]['dormant_user_count'] = $count['dormant_user_count'];
                            $listDeviceAndEventCountByApptitle[$appTitle][$platform]['app_title_id'] = $count['app_title_id'];

                        }
                    }
                }

            }
        }

        $resp = $listDeviceAndEventCountByApptitle;
        return new JsonResponse($resp);
    }

    public function loadRecentInAppEventAction(Request $request)
    {
        $auth = $this->get('security.context')->getToken()->getUser();
        $result = $this->container->get('hyper_event.event_api_resful')->analyticRecentInAppEvent(array(
            'client_id' => $auth->getClientId()
        ));

        $resp = $result;
        return new JsonResponse($resp);
    }

    public function loadCountDeviceByCountryAction(Request $request)
    {
        $auth = $this->get('security.context')->getToken()->getUser();
        $result = $this->container->get('hyper_event.event_api_resful')->analyticCountDeviceByCountry(array(
            'client_id' => $auth->getClientId()
        ));
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

    public function endTourAction(Request $request)
    {
        $json['status'] = 1;
        $auth = $this->get('security.context')->getToken()->getUser();
        if ($auth->isDemoAccount()) {
            $session = $request->getSession();
            $sessionId = $session->getId();
            $sessionKey = 'show_tutorial_' . $sessionId;
            if (!$session->has($sessionKey)) {
                $session->set($sessionKey, 1);
            }
            if ($session->get($sessionKey) == 1) {
                return new JsonResponse($json);
            }
            $session->set($sessionKey, 1);
            return new JsonResponse($json);
        } else {
            if ($auth->getShowTutorial() == 1) {
                return new JsonResponse($json);
            }
            $auth->setShowTutorial(1);
            $em = $this->container->get('doctrine')->getManager('pgsql');
            $em->persist($auth);
            $em->flush();
        }

        return new JsonResponse($json);
    }

    public function createCardByPopupAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager('pgsql');
        $auth = $this->get('security.context')->getToken()->getUser();
        $createCardByPopupForm = $this->createForm(new CreateCardByPopupType(), []);
        $json = [
            'status' => 0,
            'msg' => ''
        ];
        if( $request->isMethod('POST')) {
            $createCardByPopupForm->handleRequest($request);
            if ($createCardByPopupForm->isValid()) {
                $name = $createCardByPopupForm->get('name')->getData();
                $desc = $createCardByPopupForm->get('desc')->getData();
                $iosPlatform = $createCardByPopupForm->get('platform_ios')->getData();
                $androidPlatform = $createCardByPopupForm->get('platform_android')->getData();
                $platform = [];
                if ($iosPlatform) {
                    $platform[] = Device::IOS_PLATFORM_CODE;
                }
                if ($androidPlatform) {
                    $platform[] = Device::ANDROID_PLATFORM_CODE;
                }
                $appTitleId = $createCardByPopupForm->get('app_title_id')->getData();
                $ghostUserTarget = $createCardByPopupForm->get('target_ghost')->getData();
                $dormantUserTarget = $createCardByPopupForm->get('target_dormant')->getData();
                if ($ghostUserTarget && $dormantUserTarget) {
                    $filter = $this->initGhostUserDormantUserCard($auth,$name,$desc,$platform, $appTitleId);
                } elseif ($ghostUserTarget) {
                    $filter = $this->initGhostUserCard($auth,$name,$desc,$platform, $appTitleId);
                } elseif ($dormantUserTarget) {
                    $filter = $this->initDormantUserCard($auth,$name,$desc,$platform, $appTitleId);
                }
                try {
                    if ($filter instanceof Filter) {
                        $em->persist($filter);
                        $em->flush();
                        // save cache
                        $userFilterCached = new UserFilterCached($this->container, $auth->getId());
                        $serializer = $this->get('jms_serializer');
                        if ($filter) {
                            $filter = $serializer->toArray($filter);
                        }
                        $userFilterCached->hset($filter['id'], json_encode($filter));
                        $sqsWraper = $this->container->get('hyper_event_processing.sqs_wrapper');
                        $sqsWraper->sendMessageToQueue($this->container->getParameter('amazon_sqs_queue_cache_filter'), ['id' => $filter['id']]);
                        $json = [
                            'status' => 1,
                            'msg' => 'Audience Card created and saved to your Deck'
                        ];
                    }
                } catch (\Exception $e) {
                    $json = [
                            'status' => 0,
                            'msg' => $e->getMessage()
                        ];
                }
            }
        }

        return new JsonResponse($json);
    }

    private function initGhostUserCard(
        $auth
        , $name
        , $desc
        , $platform
        , $appTitleId
    ) {
        $filter = new Filter();
        $color = "#093145";
        $txtColor = "#f5f5f5";
        $filterData = [
            'preset_name' => $name
            , 'description' => $desc
            , 'country_codes' => []
            , 'platform_ids' => $platform
            , 'filter_type' => 'user_behaviors'
            , 'audience' => [
                [
                    'usage' => [
                        'in' => $appTitleId
                        , 'perform' => UsageDataType::PERFORM_TYPE_NOT_PERFORM
                        , 'behaviour_id' => ''
                        , 'cat_id' => ''
                        , 'happened_at' => [
                            'type' => UsageDataType::HAPPENED_AT_TYPE_LIFETIME
                            , 'value' => [
                                0 => '',
                                1 => ''
                            ]
                        ]

                    ]
                ]
            ]
            , 'card_bg_color_code' => $color
            , 'card_text_color_code' => $txtColor
        ];
        $filter->setPresetName($name)
            ->setDescription($desc)
            ->setAuthenticationId($auth->getId())
            ->setIsDefault(0)
            ->setFilterMetadata([])
            ->setFilterData($filterData)
            ->setCardBgColorCode($color)
            ->setCardTextColorCode($txtColor);

        return $filter;
    }

    private function initDormantUserCard(
        $auth
        , $name
        , $desc
        , $platform
        , $appTitleId
    ) {
        $filter = new Filter();
        $color = "#093145";
        $txtColor = "#f5f5f5";
        $filterData = [
            'preset_name' => $name
            , 'description' => $desc
            , 'country_codes' => []
            , 'platform_ids' => $platform
            , 'filter_type' => 'user_behaviors'
            , 'audience' => [
                [
                    'usage' => [
                        'in' => $appTitleId
                        , 'perform' => UsageDataType::PERFORM_TYPE_NOT_PERFORM
                        , 'behaviour_id' => ''
                        , 'cat_id' => ''
                        , 'happened_at' => [
                            'type' => UsageDataType::HAPPENED_AT_TYPE_LAST
                            , 'value' => [
                                0 => 7,
                                1 => ''
                            ]
                        ]

                    ]
                ]
            ]
            , 'card_bg_color_code' => $color
            , 'card_text_color_code' => $txtColor
        ];
        $filter->setPresetName($name)
            ->setDescription($desc)
            ->setAuthenticationId($auth->getId())
            ->setIsDefault(0)
            ->setFilterMetadata([])
            ->setFilterData($filterData)
            ->setCardBgColorCode($color)
            ->setCardTextColorCode($txtColor);

        return $filter;
    }

    private function initGhostUserDormantUserCard(
        $auth
        , $name
        , $desc
        , $platform
        , $appTitleId
    ) {
        $filter = new Filter();
        $color = "#093145";
        $txtColor = "#f5f5f5";
        $filterData = [
            'preset_name' => $name
            , 'description' => $desc
            , 'country_codes' => []
            , 'platform_ids' => $platform
            , 'filter_type' => 'user_behaviors'
            , 'audience' => [
                [
                    'usage' => [
                        'in' => $appTitleId
                        , 'perform' => UsageDataType::PERFORM_TYPE_NOT_PERFORM
                        , 'behaviour_id' => ''
                        , 'cat_id' => ''
                        , 'happened_at' => [
                            'type' => UsageDataType::HAPPENED_AT_TYPE_LIFETIME
                            , 'value' => [
                                0 => '',
                                1 => ''
                            ]
                        ]
                    ]
                ]
                , [
                    'append_relation' => 'and'
                    , 'usage' => [
                        'in' => $appTitleId
                        , 'perform' => UsageDataType::PERFORM_TYPE_NOT_PERFORM
                        , 'behaviour_id' => ''
                        , 'cat_id' => ''
                        , 'happened_at' => [
                            'type' => UsageDataType::HAPPENED_AT_TYPE_LAST
                            , 'value' => [
                                0 => 7,
                                1 => ''
                            ]
                        ]

                    ]
                ]
            ]
            , 'card_bg_color_code' => $color
            , 'card_text_color_code' => $txtColor
        ];
        $filter->setPresetName($name)
            ->setDescription($desc)
            ->setAuthenticationId($auth->getId())
            ->setIsDefault(0)
            ->setFilterMetadata([])
            ->setFilterData($filterData)
            ->setCardBgColorCode($color)
            ->setCardTextColorCode($txtColor);

        return $filter;
    }
}