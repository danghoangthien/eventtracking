<?php

namespace Hyper\EventBundle\Controller\Dashboard\Filter;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Doctrine\DBAL\Driver\PDOStatement;

// DT Repository
use Hyper\DomainBundle\Repository\Filter\DTFilterRepository;

// Entities
use Hyper\Domain\Filter\Filter;
use Hyper\Domain\Device\Device;
use Hyper\Domain\Client\Client;
use Hyper\Domain\Action\Action;
use Hyper\EventBundle\Service\PresetFilterParser\PresetFilterParser;


// Facebook SDK
use FacebookAds\Api;
use FacebookAds\Object\AdUser;
use FacebookAds\Object\Fields\AdAccountFields;
use FacebookAds\Object\Fields\ConnectionObjectFields;
use FacebookAds\Object\Values\ConnectionObjectTypes;
use FacebookAds\Object\AdAccount;

use Facebook\Facebook;
use Facebook\Authentication\AccessToken;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;

use FacebookAds\Object\CustomAudience;
use FacebookAds\Object\Fields\CustomAudienceFields;
use FacebookAds\Object\Values\CustomAudienceTypes;
use FacebookAds\Object\Values\CustomAudienceSubtypes;

use FacebookAds\Object\AdsPixel;
use FacebookAds\Object\Fields\AdsPixelsFields;

// cached
use Hyper\EventBundle\Service\Cached\User\UserFilterCached;
use Hyper\EventBundle\Service\Cached\User\UserAppCached;
use Hyper\EventBundle\Service\Cached\InappeventConfig\InappeventConfigCached;
use Hyper\EventBundle\Service\Cached\AnalyticMetadata\CountDeviceByAppTitleCached;
use Hyper\EventBundle\Service\Cached\AnalyticMetadata\CountDeviceByCountryCached;

class PresetFilterControllerV2 extends Controller
{
    const LIST_FILTER_SIZE = 5;
    public $authentication;
    public $client;
    public $intents;
    public $filterId;
    /**
    * @param ContainerInterface $container
    */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->ieaConfigCached = new InappeventConfigCached($this->container);
    }

    public function indexAction(Request $request)
    {

    }

    public function showListAction(Request $request)
    {
        $auth = $this->get('security.context')->getToken()->getUser();
        if ($request->isXmlHttpRequest()) {
            $pageNumber = $request->query->getInt('page', 1);
        } else {
            $pageNumber = $request->attributes->get('page', 1);
        }
        $filterRepo = $this->get('filter_repository');
        $userFilterCached = new UserFilterCached($this->container, $auth->getId());
        $listFilter = [];
        if (!$userFilterCached->exists()) {
            $serializer = $this->get('jms_serializer');
            $listFilterTemp = $filterRepo->findBy([
                'authenticationId' => $auth->getId()
            ]);
            if ($listFilterTemp) {
                $listFilterTemp = $serializer->toArray($listFilterTemp);
            }
            $listFilterCached = [];
            foreach ($listFilterTemp as $key => $value) {
                $listFilterCached[$value['id']] = json_encode($value);
                if (!empty($value['filter_data']) && is_string($value['filter_data'])) {
                   $value['filter_data'] = unserialize($value['filter_data']);
                }
                $listFilter[$value['id']] = $value;
            }
            if (!empty($listFilterCached) && is_array($listFilterCached)) {
                $userFilterCached->hmset($listFilterCached);
            }
        } else {
            $listFilterTemp = $userFilterCached->hgetall();
            foreach ($listFilterTemp as $key => $value) {
                $listFilter[$key] = json_decode($value, true);
                if (!empty($listFilter[$key]['filter_data']) && is_string($listFilter[$key]['filter_data'])) {
                   $listFilter[$key]['filter_data'] = unserialize($listFilter[$key]['filter_data']);
                }
            }
        }
        $pageSize = self::LIST_FILTER_SIZE;
        if ($pageNumber == 1) {
            $pageSize = $pageSize * 3;
        } else {
            $pageNumber = $pageNumber + 2;
        }
        uasort($listFilter, function ($a, $b) {
            return ( $a['created'] > $b['created'] ? -1 : 1 );
        });
        $paginator = $this->get('knp_paginator');
        $paginator = $paginator->paginate(
            $listFilter,
            $pageNumber,
            $pageSize
        );
        $parameters = array(
            'listFilter' => $paginator,
            'pageCurrent' => $pageNumber,
            'listPlatform' => array(
                Device::IOS_PLATFORM_CODE => 'apple',
                Device::ANDROID_PLATFORM_CODE => Device::ANDROID_PLATFORM_NAME
            ),
            'isDemoAccount' => $auth->isDemoAccount(),
            'isLimitAccount' => $auth->isLimitAccount()
        );
        if ($request->isXmlHttpRequest()) {
            $json = array(
                'status' => 1,
                'content' => $this->renderView('::audience_deck/_paginate.html.twig', $parameters),
                'is_last_page' => ($pageNumber == $paginator->getPageCount())
            );

            return new JsonResponse($json);
        }

        return $this->render('::audience_deck/index.html.twig', $parameters);

    }

    public function showAddAction(Request $request)
    {
        $auth = $this->get('security.context')->getToken()->getUser();
        try
        {
            
            $listAppFlatform = [];
            $listAppTitle = $this->getListAppTitleByCache();
            
            $deviceRepo = $this->get('device_repository');
            //$countries = $deviceRepo->getActiveCountries($appIds);
            $countries = $this->getListCoutryCache();
            $actionRepo = $this->get('action_repository');
            $this->filterId = $request->get('filter_id');
            $listAppTitleSelected = [];
            $listAppIdSelected = [];
            $availableBehaviours = [[],[],[]];
            $categories = [[],[],[]];
            if($this->filterId) {
                $filterRepo = $this->get('filter_repository');
                $presetFilter = $filterRepo->getRecordForUpdate($this->filterId);
                if (!$presetFilter instanceof Filter ) {
                    throw new \InvalidArgumentException("Invalid preset filter id");
                }
                $filterData = $presetFilter->getFilterData();

                if (!empty($listAppFlatform)) {
                    foreach ($listAppFlatform as $appPlatform) {
                        if (@in_array($appPlatform->getAppTitle()->getId(), $listAppTitleSelected)) {
                            $listAppIdSelected[] = $appPlatform->getAppId();
                        }
                    }
                }

                return $this->render('filter/custom_audience_add_v3.html.twig',
                    array(
                        'active' => "custom_audience",
                        'active_country_list' => $countries,
                        'active_platform' => array(
                            Device::IOS_PLATFORM_CODE => Device::IOS_PLATFORM_NAME,
                            Device::ANDROID_PLATFORM_CODE => Device::ANDROID_PLATFORM_NAME
                        ),
                        'active_interest' => $categories,
                        'active_behaviours' => $availableBehaviours,
                        'preset_filter' => $presetFilter,
                        'listAppTitle' => $listAppTitle,
                        'listAppTitleSelected' => $listAppTitleSelected,
                        'isDemoAccount' => $auth->isDemoAccount()
                    )
                );
            }
            else
            {
                //var_dump($categories);die;
                return $this->render('filter/custom_audience_add_v3.html.twig',
                    array(
                        'active' => "custom_audience",
                        'active_country_list' => $countries,
                        'active_platform' => array(
                            Device::IOS_PLATFORM_CODE => Device::IOS_PLATFORM_NAME,
                            Device::ANDROID_PLATFORM_CODE => Device::ANDROID_PLATFORM_NAME
                        ),
                        'active_interest' => $categories,
                        'active_behaviours' => $availableBehaviours,
                        'listAppTitle' => $listAppTitle,
                        'listAppTitleSelected' => $listAppTitleSelected,
                        'isDemoAccount' => $auth->isDemoAccount()
                    )
                );
            }

        }
        catch (\InvalidArgumentException $ex) {
            $response = new Response($ex->getMessage());
            $response->setStatusCode(400);
            return $response;
        }
        catch (\lib\Exception\InvalidAuthenticationException $ex) {

            return $this->render('filter/filter_index.html.twig',
                array(
                    'exception' => $ex->getMessage()
                    )
            );
        }
    }

    public function executeAddAction(Request $request)
    {
        // https://hyperdev.atlassian.net/browse/AKWA-65
        $auth = $this->get('security.context')->getToken()->getUser();
        if ($auth->isDemoAccount()) {
            throw new HttpException(Response::HTTP_FORBIDDEN, "Sorry you cannot make changes as this feature is disabled in demo mode.");
        }
        /*
        $post = $request->request->all();
        echo "<pre>";
        var_dump($post);
        die;
        */
        // throw new \Exception("Duplicate Preset name");
        try {
            $filterMetadata = array();
            $authController = $this->get('auth.controller');
            $authRepo = $this->get('authentication_repository');
            $authIdFromSession = $authController->getLoggedAuthenticationId();
            $this->authentication = $authRepo->find($authIdFromSession);
            $clientId = $this->authentication->getClientId();
            if(strpos($clientId,',') !== false) {
                //throw new \Exception('non admin user must have just one client associated');
                $this->url = $this->generateUrl('dashboard_logout');
                return $this->redirect($this->url, 301);
                // throw new \Exception('non admin user must have just one client associated');
            }
            $clientIds  = array($clientId);

            $clientRepo = $this->get('client_repository');
            $this->client = $clientRepo->find($clientId);

            $authenticationId = $authIdFromSession;
            $presetName = $request->get('preset_name');
            $filterRepo = $this->get('filter_repository');
            $reservedFilter = $filterRepo->getByIdentifier($authenticationId,$presetName);

            if ($reservedFilter instanceof Filter) {
                throw new \Exception("Duplicate Preset name");
            }
            else {
                $sql = "";
                $countryCodes = $request->get('country_codes');
                $platformIds = $request->get('platform_ids');

                $description = $request->request->get('description');
                if(!empty($installTimeSince)){
                    $installTimeSince = $request->request->get('install_time_since');
                    $dt = \DateTime::createFromFormat('d/m/Y', $installTimeSince);
                    $installTimeSince = $dt->getTimestamp(); # or $dt->format('U');
                }

                if(!empty($lastHappenedAt)){
                    $lastHappenedAt = $request->request->get('last_happened_at');
                    $dt = \DateTime::createFromFormat('d/m/Y', $lastHappenedAt);
                    $lastHappenedAt = $dt->getTimestamp(); # or $dt->format('U');
                }

                $actions = $request->request->get('actions');

                if(!empty($happenedAtFrom)) {
                    $happenedAtFrom = $request->request->get('happened_at_from');
                    $happenedAtFrom = $happenedAtFrom." 00:00:00";
                    $dt = \DateTime::createFromFormat('d/m/Y H:i:s', $happenedAtFrom);
                    $happenedAtFrom = $dt->getTimestamp(); # or $dt->format('U');
                }
                if(!empty($happenedAtTo)) {
                    $happenedAtTo = $request->request->get('happened_at_to');
                    $happenedAtTo = $happenedAtTo." 00:00:00";
                    $dt = \DateTime::createFromFormat('d/m/Y H:i:s', $happenedAtTo);
                    $happenedAtTo = $dt->getTimestamp(); # or $dt->format('U');
                }
                $filterRepo = $this->get('filter_repository');
                $filter = new Filter();
                $filter->setAuthenticationId($authenticationId);
                $filter->setPresetName($presetName);
                $filter->setDescription($description);
                //print_r($countryCodes);//die;
                if (!empty($countryCodes)){
                    if(!is_array($countryCodes)) {
                        $countryCodes = array($countryCodes);
                    }
                    $countryCodesString = implode("','",$countryCodes);
                    $sql.= " AND C.country_code IN ('$countryCodesString') ";
                }
                //print_r($platformIds);
                //if (!empty($platformFilter)) {
                if (!empty($platformIds)) {
                    if(!is_array($platformIds)) {
                        $platformIds = array($platformIds);
                    }
                    $platformIdsString = implode("','",$platformIds);
                    $sql.= " AND C.platform IN ('$platformIdsString') ";
                }
                if (!empty($installTimeSince)) {
                    $sql.= " AND C.installTime >= '$installTimeSince' ";
                }
                if (!empty($lastHappenedAt)) {
                    $sql.= " AND A.device_id IN  (select device_id from actions group by device_id having max(happened_at)< $lastHappenedAt ) ";
                }
                if (!empty($happenedAtFrom)) {
                    $sql.= " AND A.happened_at >= $happenedAtFrom";
                }
                if (!empty($happenedAtTo)) {
                    $sql.= " AND A.happened_at <= $happenedAtTo";
                }
                if (!empty($actions)) {
                    foreach($actions as $action) {
                        $frequent = $action['frequent'];
                        $behaviourId = $action['behaviour_id'];
                        $frequentValue = $frequent['value'];
                        $frequentExp = $frequent['expression'];//>,<,=
                        $sql.= " AND (A.device_id IN (select device_id from actions where behaviour_id = '$behaviourId' group by device_id having count(device_id) $frequentExp 5 )) ";

                        if (!empty($action['cat_id'])) {
                            $catId = $action['cat_id'];
                            $categoryIdsString = "'".$catId."'";
                            if ($behaviourId == Action::BEHAVIOURS['VIEW_CONTENT_BEHAVIOUR_ID']) {
                                $sql.= " AND (A.device_id IN ( select device_id from view_content_actions where category_id = '') ";
                            }
                            if ($behaviourId == Action::BEHAVIOURS['SHARE_CONTENT_BEHAVIOUR_ID']) {
                                $sql.= " AND (A.device_id IN ( select device_id from share_content_actions where category_id = '') ";
                            }
                            if ($behaviourId == Action::BEHAVIOURS['ADD_TO_CART_BEHAVIOUR_ID']) {
                                $sql.= " AND (A.device_id IN (
                                            select device_id from add_to cart_actions where id IN (
                                                select cart_id from in_cart_items
                    								where item_id in (
                    									select id from items
                    										where code in (
                    											select item_code from in_category_items where category_id in ('".$categoryIdsString."')
                    										)
                    								)
                                            )
                                        )) ";
                            }
                            if ($behaviourId == Action::BEHAVIOURS['ADD_TO_CART_BEHAVIOUR_ID']) {
                                $sql.=  " AND (A.device_id IN (
                                            select device_id from add_to cart_actions where id IN (
                                                select cart_id from in_cart_items
                    								where item_id in (
                    									select id from items
                    										where code in (
                    											select item_code from in_category_items where category_id in ('".$categoryIdsString."')
                    										)
                    								)
                                            )
                                        )) ";
                            }
                            if ($behaviourId == Action::BEHAVIOURS['ADD_TO_WISHLIST_BEHAVIOUR_ID']) {
                                $sql.=  " AND (A.device_id IN (
                                            select device_id from add_to wishlist_actions where id IN (
                                                select wishlist_id from in_wishlist_items
                								where item_id in (
                									select id from items
                										where code in (
                											select item_code from in_category_items where category_id in ('".$categoryIdsString."')
                										)
                								)
                                            )

                                        )) ";
                            }
                            if ($behaviourId == Action::BEHAVIOURS['PURCHASE_BEHAVIOUR_ID']) {
                                $sql.=  " AND (A.device_id IN (
                                            select device_id from transaction_actions where transaction_actions.id IN (
                    							select transaction_id from transacted_items
                    								where item_id in (
                    									select id from items
                    										where code in (
                    											select item_code from in_category_items where category_id in ('".$categoryIdsString."')
                    										)
                    								)
                    						)
                                        )) ";
                            }

                        }

                    }
                }
                //echo $sql;die;
                $filterMetadata = array('sql_condition' => $sql);
                $filter->setFilterMetadata($filterMetadata);//save

                $postData = $request->request->all();

                $filterData = $postData;
                $filter->setFilterData($filterData);//save post data

                $filterRepo->save($filter);
                $filterRepo->completeTransaction();

                return new Response('Preset filter created successfully');
            }
        } catch (\Exception $ex) {

            if ($ex->getMessage() == 'Duplicate Preset name'){
                $response = new Response($ex->getMessage());
                $response->setStatusCode(400);
                return $response;
            } else {
                $response = new Response($ex->getMessage());
                $response->setStatusCode(500);
                return $response;
            }
        }
    }

    public function executeAddActionV2(Request $request)
    {
        $auth = $this->get('security.context')->getToken()->getUser();
        if ($auth->isDemoAccount()) {
            throw new HttpException(Response::HTTP_FORBIDDEN, "Sorry you cannot make changes as this feature is disabled in demo mode.");
        }
        /*
        $post = $request->request->all();
        echo "<pre>";
        var_dump($post);
        die;
        */
        // throw new \Exception("Duplicate Preset name");
        try {
            $filterMetadata = array();

            $authController = $this->get('auth.controller');
            $authRepo = $this->get('authentication_repository');
            $authIdFromSession = $authController->getLoggedAuthenticationId();
            $this->authentication = $authRepo->find($authIdFromSession);
            $clientId = $this->authentication->getClientId();
            if(strpos($clientId,',') !== false) {
                //throw new \Exception('non admin user must have just one client associated');
                $this->url = $this->generateUrl('dashboard_logout');
                return $this->redirect($this->url, 301);
                // throw new \Exception('non admin user must have just one client associated');
            }
            $clientIds  = array($clientId);

            $clientRepo = $this->get('client_repository');
            $this->client = $clientRepo->find($clientId);

            $authenticationId = $authIdFromSession;
            $presetName = $request->get('preset_name');
            $description = $request->get('description');
            $filterRepo = $this->get('filter_repository');
            $cardBgColorCode = $request->get('card_bg_color_code');
            $cardHighlightColorCode = $request->get('card_highlight_color_code');
            $cardTextColorCode = $request->get('card_text_color_code');
            $reservedFilter = $filterRepo->getByIdentifier($authenticationId,$presetName);

            if ($reservedFilter instanceof Filter) {
                throw new \Exception("Duplicate Preset name");
            }
            else {

                $filterRepo = $this->get('filter_repository');
                $filter = new Filter();
                $filter->setAuthenticationId($authenticationId);
                $filter->setPresetName($presetName);
                $filter->setDescription($description);

                $filterMetadata = array();
                $filter->setFilterMetadata($filterMetadata);//save

                $postData = $request->request->all();
                $em = $this->getDoctrine()->getManager('pgsql');
                /*if ($postData['history']) {
                    foreach ($postData['history'] as $key => $history) {
                        if (
                            empty($history['install_time_since']) &&
                            empty($history['last_happened_at']) &&
                            empty($history['in'])
                        ) {
                            continue;
                        }
                        $listAppId = [];
                        $listAppTitleId = $history['in'];
                        if (!$listAppTitleId) {
                            continue;
                        }
                        $listAppFlatform = $em->getRepository('Hyper\Domain\Application\ApplicationPlatform')->findByAppTitle($listAppTitleId);
                        if (!empty($listAppFlatform)) {
                            foreach ($listAppFlatform as $appPlatform) {
                                $listAppId[] = $appPlatform->getAppId();
                            }
                        }
                        $postData['history'][$key]['app_id'] = $listAppId;
                    }
                }
                if ($postData['actions']) {
                    foreach ($postData['actions'] as $key => $actions) {
                        if (
                            empty($history['in'])
                        ) {
                            continue;
                        }
                        $listAppId = [];
                        $listAppTitleId = $actions['in'];
                        if (!$listAppTitleId) {
                            continue;
                        }
                        $listAppFlatform = $em->getRepository('Hyper\Domain\Application\ApplicationPlatform')->findByAppTitle($listAppTitleId);
                        if (!empty($listAppFlatform)) {
                            foreach ($listAppFlatform as $appPlatform) {
                                $listAppId[] = $appPlatform->getAppId();
                            }
                        }
                        $postData['actions'][$key]['app_id'] = $listAppId;
                    }
                }*/
                $filterData = $postData;
                $filter->setFilterData($filterData);//save post data

                $filter->setCardBgColorCode($cardBgColorCode);
                $filter->setCardHighlightColorCode($cardHighlightColorCode);
                $filter->setCardTextColorCode($cardTextColorCode);

                $filterRepo->save($filter);
                $filterRepo->completeTransaction();

                // save cache
                $auth = $this->get('security.context')->getToken()->getUser();
                $userFilterCached = new UserFilterCached($this->container, $auth->getId());
                $serializer = $this->get('jms_serializer');
                if ($filter) {
                    $filter = $serializer->toArray($filter);
                }
                $userFilterCached->hset($filter['id'], json_encode($filter));
                $sqsWraper = $this->container->get('hyper_event_processing.sqs_wrapper');
                $sqsWraper->sendMessageToQueue($this->container->getParameter('amazon_sqs_queue_cache_filter'), ['id' => $filter['id']]);
                return new Response('Audience Card created and saved to your Deck');
            }
        } catch (\Exception $ex) {

            if ($ex->getMessage() == 'Duplicate Preset name'){
                $response = new Response($ex->getMessage());
                $response->setStatusCode(400);
                return $response;
            } else {
                $response = new Response($ex->getMessage());
                $response->setStatusCode(500);
                return $response;
            }
        }
    }

    public function executeDeleteAction(Request $request)
    {
        $auth = $this->get('security.context')->getToken()->getUser();
        if ($auth->isDemoAccount()) {
            throw new HttpException(Response::HTTP_FORBIDDEN, "Sorry you cannot make changes as this feature is disabled in demo mode.");
        }
        $this->id = $request->request->get('id');

        if((null != $this->id) && ("" != $this->id))
        {
            $filterRepo = $this->container->get('filter_repository');

            $delete = $filterRepo->deletePreset($this->id);

            // delete cache
            $auth = $this->get('security.context')->getToken()->getUser();
            $userFilterCached = new UserFilterCached($this->container, $auth->getId());
            $userFilterCached->hdel($this->id);

            return new Response(json_encode(array("status" => $delete)));
        }
        else
        {
            return new Response(json_encode(array("status" => "No Preset to delete.")));
        }
    }

    public function getIntents(array $categoryIds = array()){
        $purchaseBehaviourId = \Hyper\Domain\Action\Action::BEHAVIOURS['PURCHASE_BEHAVIOUR_ID'];
        $wishlistBehaviourId = \Hyper\Domain\Action\Action::BEHAVIOURS['ADD_TO_WISHLIST_BEHAVIOUR_ID'];
        $categoryIdsString = implode(',',$categoryIds);
        $recentInstallSql = ' AND A.installed_time > (extract(epoch from getdate()) - 604800) AND A.installed_time <= extract(epoch from getdate())  ';

        $inWishList1MonthSql = " AND A.device_id IN ( SELECT distinct device_id FROM add_to_wishlist_actions GROUP BY device_id HAVING count(device_id) > 5 ) AND ( A.added_time > (getdate() - 2628000) AND A.added_time <= getdate() ) ";
        $inPurchase1WeekSql = ' AND A.transacted_time > (extract(epoch from getdate()) - 2628000) AND A.transacted_time <= extract(epoch from getdate())';
        $inPurchase1Week5PurchaseSql = " AND A.device_id IN ( SELECT distinct device_id FROM transaction_actions GROUP BY device_id HAVING count(device_id) > 5 ) AND ( A.transacted_time > (extract(epoch from getdate()) - 2628000) AND A.transacted_time <= extract(epoch from getdate()) ) ";
        $MScoreGT51MonthEccomerceSql = " AND A.device_id in (select device_id from frm group by device_id,event_time HAVING SUM(amount) >= 300 and event_time > (GETDATE()-10512000)  )";
        $MScoreGT51MonthGameSql = " AND A.device_id in (select device_id from frm group by device_id,event_time HAVING SUM(amount) >= 30 and event_time > (GETDATE()-10512000)  )";
        if (empty($categoryIds)) {
            /*
            $recentInstallSql.= '
            AND (
                ( A.device_id IN (select device_id from transaction_actions) )
                OR
                ( A.device_id IN (select device_id from add_to cart_actions) )
                OR
                ( A.device_id IN (select device_id from add_to wishlist_actions) )
            ) ';
            */
        } else {
            /*
            $recentInstallSql.= "
            AND (
                ( A.device_id IN (
                        select device_id from transaction_actions where transaction_actions.id IN (
							select transaction_id from transacted_items
								where item_id in (
									select id from items
										where code in (
											select item_code from in_category_items where category_id in ('".$categoryIdsString."')
										)
								)
						)
                    )
                )
                OR
                ( A.device_id IN (
                        select device_id from add_to cart_actions where id IN (
                            select cart_id from in_cart_items
								where item_id in (
									select id from items
										where code in (
											select item_code from in_category_items where category_id in ('".$categoryIdsString."')
										)
								)
                        )
                    )
                )
                OR
                ( A.device_id IN (
                        select device_id from add_to wishlist_actions where id IN (
                            select wishlist_id from in_wishlist_items
								where item_id in (
									select id from items
										where code in (
											select item_code from in_category_items where category_id in ('".$categoryIdsString."')
										)
								)
                        )

                    )
                )
            ) ";
            */
            /*
            $recentInstallSql.= "
            AND (
                ( A.device_id IN (
                        select device_id from transaction_actions where transaction_actions.id IN (
							select transaction_id from transacted_items
								where item_id in (
									select id from items
										where code in (
											select item_code from in_category_items where category_id in ('".$categoryIdsString."')
										)
								)
						)
                    )
                )

            ) ";
            */
            $inWishList1MonthSql.= "
                AND (
                    A.device_id IN (
                        select device_id from add_to wishlist_actions where id IN (
                            select wishlist_id from in_wishlist_items
								where item_id in (
									select id from items
										where code in (
											select item_code from in_category_items where category_id in ('".$categoryIdsString."')
										)
								)
                        )

                    )
                )
            ";
            $inPurchase1WeekSql.="
                AND (
                    A.device_id IN (
                        select device_id from transaction_actions where transaction_actions.id IN (
							select transaction_id from transacted_items
								where item_id in (
									select id from items
										where code in (
											select item_code from in_category_items where category_id in ('".$categoryIdsString."')
										)
								)
						)
                    )
                )
            ";
            $inPurchase1Week5PurchaseSql.= "
                AND (
                    A.device_id IN (
                        select device_id from transaction_actions where transaction_actions.id IN (
							select transaction_id from transacted_items
								where item_id in (
									select id from items
										where code in (
											select item_code from in_category_items where category_id in ('".$categoryIdsString."')
										)
								)
						)
                    )
                )
            ";

        }

        $intents = array(
                'recent_install' => array(
                    'name' => 'Recent Install',
                    'description' => 'Users who Install within 1 week',
                    'type' => 'sql',
                    'behaviour_id' => \Hyper\Domain\Action\Action::BEHAVIOURS['INSTALL_BEHAVIOUR_ID'],
                    'metadata' => $recentInstallSql
                ),
                'potential_spenders' => array(
                    'name' => 'Potential Spenders',
                    'description' => 'Users who perform add to wishlist action within 1 month',
                    'type' => 'sql',
                    'behaviour_id' => \Hyper\Domain\Action\Action::BEHAVIOURS['ADD_TO_WISHLIST_BEHAVIOUR_ID'],
                    'metadata' => $inWishList1MonthSql
                ),
                'recent_purchase' => array(
                    'name' => 'Recent Purchase',
                    'description' => 'Users who make Purchase within 1 week',
                    'type' => 'sql',
                    'behaviour_id' => \Hyper\Domain\Action\Action::BEHAVIOURS['PURCHASE_BEHAVIOUR_ID'],
                    'metadata' => $inPurchase1WeekSql
                ),
                'high_spender' => array(
                    'name' => 'Shopaholic',
                    'description' => 'Users who make Purchase within 1 week',
                    'type' => 'sql',
                    'behaviour_id' => \Hyper\Domain\Action\Action::BEHAVIOURS['PURCHASE_BEHAVIOUR_ID'],
                    'metadata' => $MScoreGT51MonthEccomerceSql
                ),
                'paying_gamers' => array(
                    'name' => 'Recent Purchase',
                    'description' => 'Users who make Purchase within 1 week',
                    'type' => 'sql',
                    'behaviour_id' => \Hyper\Domain\Action\Action::BEHAVIOURS['PURCHASE_BEHAVIOUR_ID'],
                    'metadata' => $inPurchase1WeekSql
                ),
                'hardcore_gamer' => array(
                    'name' => 'Recent Purchase',
                    'description' => 'Users who make > 5 Purchases within 1 week',
                    'type' => 'sql',
                    'behaviour_id' => \Hyper\Domain\Action\Action::BEHAVIOURS['PURCHASE_BEHAVIOUR_ID'],
                    'metadata' => $MScoreGT51MonthGameSql
                )


        );
        return $intents;
    }

    public function getAvailableListEventNameByClientApp() {
        $appIds = $this->client->getClientApp();
        $appIdsArray = explode(',',$appIds);
        $actionRepo = $this->get('action_repository');
        $listEventNames = $actionRepo->getEventNameByAppIds($appIdsArray);

        return $listEventNames;
    }

    //select behaviour_id from actions where app_id in ('com.woi.liputan6.android') group by behaviour_id;

    public function getEstimationAction(Request $request) {

        $authController = $this->get('auth.controller');
        $authRepo = $this->get('authentication_repository');
        $authIdFromSession = $authController->getLoggedAuthenticationId();

        /* ADDED TO REDIRECT TO LOGIN IF THERE IS NO SESSION paul.francisco 2015-12-18 */
        if($authIdFromSession == null)
        {
            $this->url = $this->generateUrl('dashboard_logout');
            return $this->redirect($this->url, 301);
        }

        $this->authentication = $authRepo->find($authIdFromSession);
        $clientId = $this->authentication->getClientId();
        if(strpos($clientId,',') !== false) {
            //throw new \Exception('non admin user must have just one client associated');
            $this->url = $this->generateUrl('dashboard_logout');
            return $this->redirect($this->url, 301);
        }

        $clientIds  = array($clientId);

        $clientRepo = $this->get('client_repository');
        $this->client = $clientRepo->find($clientId);
        $appIds = $clientRepo->getClientAppsByIds($clientIds);
        $clientAppIdsString = implode("','",$appIds);

        $estimate = 0;

        $countryCodes = $request->get('country_codes');
        $platformIds = $request->get('platform_ids');
        $catIds = $request->get('cat_ids');
        $intentKey = $request->get('intent_key');

        //var_dump($countryCodes);
        /*
        echo "<hr/>";
        var_dump(isset($countryCodes));
        var_dump(empty($countryCodes));
        if(!empty($countryCodes)){
            var_dump($countryCodes);
        }
        die;
        */
        $countSQL = " select count(id) as count from actions A where 1 =1 AND app_id IN ('".$clientAppIdsString."') ";
        if (!empty($countryCodes)) {
            $countryCodesString = implode("','",$countryCodes);
            $countSQL .= " AND A.device_id IN ( SELECT id FROM devices WHERE country_code IN ('".$countryCodesString."')) ";
        }
        if (!empty($platformIds)) {
            $platformIdsString = implode("','",$platformIds);
            $countSQL .= " AND A.device_id IN ( SELECT id FROM devices WHERE platform IN ('".$platformIdsString."')) ";
        }
        if (!empty($intentKey)) {
            if(!empty($catIds)) {
                $intentKeys = $this->getIntents($catIds);
            } else {
                $intents = $this->getIntents();
            }
            if (isset($intents['$intentKey'])) {
                $intentSQL = $intents['$intentKey']['metadata'];
                $countSQL .= $intentSQL;
            }
        }
        $conn = $this->get('doctrine.dbal.pgsql_connection');
        //echo $countSQL;die;
        $result = $conn->query($countSQL);
        if ($result instanceof PDOStatement) {
            $row = $result->fetch();
            //print_r($row);die;
            $estimate = $row["count"];

        }
        //return $estimate;
        $response = new Response(
                        json_encode(
                            array(
                                'estimate' => $estimate
                            )
                        )
                    );
        return  $response;

    }

    public function executeUpdateAction(Request $request)
    {
        $auth = $this->get('security.context')->getToken()->getUser();
        if ($auth->isDemoAccount()) {
            throw new HttpException(Response::HTTP_FORBIDDEN, "Sorry you cannot make changes as this feature is disabled in demo mode.");
        }
        try
        {
            $filterMetadata = array();

            $authController = $this->get('auth.controller');
            $authRepo = $this->get('authentication_repository');
            $authIdFromSession = $authController->getLoggedAuthenticationId();
            $this->authentication = $authRepo->find($authIdFromSession);
            $clientId = $this->authentication->getClientId();
            if(strpos($clientId,',') !== false) {
                //throw new \Exception('non admin user must have just one client associated');
                $this->url = $this->generateUrl('dashboard_logout');
                return $this->redirect($this->url, 301);
                // throw new \Exception('non admin user must have just one client associated');
            }

            $authController = $this->get('auth.controller');
            $authRepo = $this->get('authentication_repository');
            $authIdFromSession = $authController->getLoggedAuthenticationId();
            $this->authentication = $authRepo->find($authIdFromSession);

            $id = $request->request->get('id');
            $authenticationId = $authIdFromSession;
            $presetName = $request->get('preset_name');
            $description = $request->get('description');
            $filterRepo = $this->get('filter_repository');
            $cardBgColorCode = $request->get('card_bg_color_code');
            $cardHighlightColorCode = $request->get('card_highlight_color_code');
            $cardTextColorCode = $request->get('card_text_color_code');

            $filter =$filterRepo->getRecordForUpdate($id);
            $filter->setAuthenticationId($authenticationId);
            $filter->setPresetName($presetName);
            $filter->setDescription($description);

            $filterMetadata = array();
            $postData = $request->request->all();
            $em = $this->getDoctrine()->getManager('pgsql');
            /*if ($postData['history']) {
                foreach ($postData['history'] as $key => $history) {
                    if (
                        empty($history['install_time_since']) &&
                        empty($history['last_happened_at']) &&
                        empty($history['in'])
                    ) {
                        continue;
                    }
                    $listAppId = [];
                    if (empty($history['in'])) {
                        continue;
                    }
                    $listAppTitleId = $history['in'];
                    $listAppFlatform = $em->getRepository('Hyper\Domain\Application\ApplicationPlatform')->findByAppTitle($listAppTitleId);
                    if (!empty($listAppFlatform)) {
                        foreach ($listAppFlatform as $appPlatform) {
                            $listAppId[] = $appPlatform->getAppId();
                        }
                    }
                    $postData['history'][$key]['app_id'] = $listAppId;
                }
            }
            if ($postData['actions']) {
                foreach ($postData['actions'] as $key => $actions) {
                    if (
                        empty($actions['in'])
                    ) {
                        continue;
                    }
                    $listAppId = [];
                    $listAppTitleId = $actions['in'];
                    $listAppFlatform = $em->getRepository('Hyper\Domain\Application\ApplicationPlatform')->findByAppTitle($listAppTitleId);
                    if (!empty($listAppFlatform)) {
                        foreach ($listAppFlatform as $appPlatform) {
                            $listAppId[] = $appPlatform->getAppId();
                        }
                    }
                    $postData['actions'][$key]['app_id'] = $listAppId;
                }
            }*/
            $filterData = $filter->getFilterData();
            $filterDataNew = $postData;
            $filter->setFilterData($filterDataNew);//save post data

            $filter->setCardBgColorCode($cardBgColorCode);
            $filter->setCardTextColorCode($cardTextColorCode);

            $filterRepo->save($filter);
            $filterRepo->completeTransaction();
            $auth = $this->get('security.context')->getToken()->getUser();
            $userFilterCached = new UserFilterCached($this->container, $auth->getId());
            $presetFilter = $userFilterCached->hget($filter->getId());
            $profileCount = '';
            $exportCsvPath = '';
            $audienceCsvPath = '';
            if (!empty($presetFilter['profile_count'])) {
                $profileCount = $presetFilter['profile_count'];
            }
            if (!empty($presetFilter['export_csv_path'])) {
                $exportCsvPath = $presetFilter['export_csv_path'];
            }
            if (!empty($presetFilter['audience_csv_path'])) {
                $audienceCsvPath = $presetFilter['audience_csv_path'];
            }

            $serializer = $this->get('jms_serializer');
            if ($filter) {
                $filter = $serializer->toArray($filter);
                if (isset($presetFilter['profile_count'])) {
                    $filter['profile_count'] = $presetFilter['profile_count'];
                }
                if (isset($presetFilter['export_csv_path'])) {
                    $filter['export_csv_path'] = $presetFilter['export_csv_path'];
                }
                if (isset($presetFilter['audience_csv_path'])) {
                    $filter['audience_csv_path'] = $presetFilter['audience_csv_path'];
                }
            }
            if (md5(serialize($filterData)) != md5(serialize($filterDataNew))) {
                unset($filter['profile_count']);
                unset($filter['export_csv_path']);
                unset($filter['audience_csv_path']);
                $sqsWraper = $this->container->get('hyper_event_processing.sqs_wrapper');
                $sqsWraper->sendMessageToQueue($this->container->getParameter('amazon_sqs_queue_cache_filter'), ['id' => $filter['id']]);
            }
            $userFilterCached->hset($filter['id'], json_encode($filter));

            return new Response('Audience Card updated');

        } catch (\Exception $ex) {

            if ($ex->getMessage() == 'Duplicate Preset name'){
                $response = new Response($ex->getMessage());
                $response->setStatusCode(400);
                return $response;
            } else {
                $response = new Response($ex->getMessage());
                $response->setStatusCode(500);
                return $response;
            }
        }
    }

    public function exportCSVAction(Request $request)
    {
        $auth = $this->get('security.context')->getToken()->getUser();
        if (
            $auth->isDemoAccount()
            || $auth->isLimitAccount()
        ) {
            throw new HttpException(Response::HTTP_FORBIDDEN, "Sorry you cannot make changes as this feature is disabled in demo mode.");
        }
        $cardId = $request->query->get('id');
        $userFilterCached = new UserFilterCached($this->container, $auth->getId());
        $presetFilter = $userFilterCached->hget($cardId);
        if (
            !$userFilterCached->exists() || !$presetFilter
        ) {
            throw new \Exception('The filter not found.');
        }
        $presetFilter = json_decode($presetFilter, true);
        if (empty($presetFilter['export_csv_path'])) {
            throw new \Exception('CSV file not found.');
        }
        $exportCsvPath = $presetFilter['export_csv_path'];
        $presetName = $presetFilter['preset_name'];
        $s3Client = $this->container->get('hyper_event_processing.s3_wrapper')->getS3Client();
        $response = new StreamedResponse(function() use ($exportCsvPath, $s3Client) {
            // Register the stream wrapper from a client object
            $s3Client->registerStreamWrapper();
            // Open a stream in read-only mode
            if (!($stream = fopen($exportCsvPath, 'r'))) {
                die('Could not open stream for reading');
            }
            // Check if the stream has more data to read
            while (!feof($stream)) {
                // Read 1024 bytes from the stream
                echo fread($stream, 1024);
            }
            // Be sure to close the stream resource when you're done with it
            fclose($stream);
        });


        $response->headers->set('Content-Type', 'application/force-download');
        $response->headers->set('Content-Disposition',"attachment; filename=\"{$presetName}.csv\"");

        return $response;
    }

    public function loadCardAction(Request $request)
    {
        $json = array(
            'status' => 0
        );
        try {
            $cardId = $request->query->get('card_id');
            $cardIndex = $request->query->get('card_index');
            if (empty($cardId)) {
                throw new \Exception('Card must be a value.');
            }
            $auth = $this->get('security.context')->getToken()->getUser();
            $userFilterCached = new UserFilterCached($this->container, $auth->getId());
            $presetFilter = $userFilterCached->hget($cardId);
            if (
                !$userFilterCached->exists() || !$presetFilter
            ) {
                $filterRepo = $this->get('filter_repository');
                $presetFilterTemp = $filterRepo->findOneBy([
                    'id' => $cardId,
                    'authenticationId' => $auth->getId()
                ]);
                if (!$presetFilterTemp instanceof Filter) {
                    throw new \Exception('The filter not found.');
                }
                $serializer = $this->get('jms_serializer');
                $presetFilterCached = $serializer->toArray($presetFilterTemp);
                $userFilterCached->hset($presetFilterCached['id'], json_encode($presetFilterCached));
                $presetFilter = $presetFilterCached;
            } else {
                $presetFilter = json_decode($presetFilter, true);
            }
            if (!empty($presetFilter['filter_data']) && is_string($presetFilter['filter_data'])) {
               $presetFilter['filter_data'] = unserialize($presetFilter['filter_data']);
            }
            $json['content'] = $this->renderView("::audience_deck/_card.html.twig", array(
                'presetFilter' => $presetFilter,
                'listPlatform' => array(
                    Device::IOS_PLATFORM_CODE => 'apple',
                    Device::ANDROID_PLATFORM_CODE => Device::ANDROID_PLATFORM_NAME
                ),
                'cardIndex' => $cardIndex
            ));
            $json['status'] = 1;
        } catch(\Exception $e) {
            $json['msg'] = $e->getMessage();
        }

        return new JsonResponse($json);
    }

    public function pushToFacebookAction(Request $request)
    {
        $auth = $this->get('security.context')->getToken()->getUser();
        if (
            $auth->isDemoAccount()
            || $auth->isLimitAccount()
        ){
            throw new HttpException(Response::HTTP_FORBIDDEN, "Sorry you cannot make changes as this feature is disabled in demo mode.");
        }
        $cardId = $request->query->get('card_id');
        $userFilterCached = new UserFilterCached($this->container, $auth->getId());
        $presetFilter = $userFilterCached->hget($cardId);
        if (
            !$userFilterCached->exists() || !$presetFilter
        ) {
            throw new \Exception('The filter not found.');
        }
        $presetFilter = json_decode($presetFilter, true);
        if (empty($presetFilter['audience_csv_path'])) {
            throw new \Exception('List audience custom not found.');
        }
        $adAccountId = $request->query->get('ad_account_id');
        $appId = $this->getParameter('facebook_app_id');
        $appSecret = $this->getParameter('facebook_app_secret');
        $fb = new Facebook(['app_id' => $appId,'app_secret' => $appSecret]);
        // Check login facebook
        $session = $request->getSession();
        //$session->set('facebook_access_token', null);
        $auth = $this->get('security.context')->getToken()->getUser();
        $userKeyAccessTokenFB = $auth->getId() . '_facebook_access_token';
        if (!$session->has($userKeyAccessTokenFB)) {
            $session->set($userKeyAccessTokenFB, null);
        }
        $userAccessTokenFB = $session->get($userKeyAccessTokenFB);
        if (!$userAccessTokenFB) {
            $helper = $fb->getRedirectLoginHelper();
            $permissions = ['ads_management'];
            $url = $this->generateUrl('dashboard_filter_callack_oauth_facebook_v2', [], true);
            $loginUrl = $helper->getLoginUrl($url, $permissions);

            return new JsonResponse([
                'error'=>1,
                'content'=> '<a href="'.$loginUrl.'" target="_blank">Please <strong>login</strong> with Facebook first and then press push to Facebook again!</a>'
            ]);
        }
        Api::init($appId, $appSecret, $userAccessTokenFB);

        $audienceCsvPath = $presetFilter['audience_csv_path'];
        $s3Client = $this->container->get('hyper_event_processing.s3_wrapper')->getS3Client();
        // Register the stream wrapper from a client object
        $s3Client->registerStreamWrapper();
        $audienceId = null;
        $estimatedSize = '';
        $rows = [];
        $users = [];
        $batch = 500;
        $i = 0;
        if (($fp = fopen($audienceCsvPath, "r")) !== false) {
            while (($row = fgetcsv($fp)) !== false) {
                if (!empty($row[0])) {
                     $rows[] = $row[0];
                }
            }
            if (!empty($rows)) {
                $rows = array_unique($rows);
            }
            $countRow = count($rows);
            try {
                foreach ($rows as $record) {
                    $users[] = strtolower($record);
                    $i++;
                    if (
                        (($i % $batch) == 0 && $i != 0)
                        || ($i + 1 == $countRow)
                    ) {
                        // Create a custom audience object, setting the parent to be the account id
                        $audience = new CustomAudience($audienceId, $adAccountId);
                        $audience->setData(array(
                            CustomAudienceFields::NAME => $presetFilter['preset_name'],
                            CustomAudienceFields::DESCRIPTION => $presetFilter['description'],
                            CustomAudienceFields::SUBTYPE => CustomAudienceSubtypes::CUSTOM,
                        ));
                        if (empty($audienceId)) {
                            // Create the audience
                            $audience->create();
                        }
                        $audience->addUsers($users, CustomAudienceTypes::MOBILE_ADVERTISER_ID);
                        $audience->read(array(CustomAudienceFields::APPROXIMATE_COUNT));
                        $audienceId = $audience->id;
                        $estimatedSize = $audience->{CustomAudienceFields::APPROXIMATE_COUNT};
                        $users = [];
                    }
                }

                return new JsonResponse([
                    'error' => 0,
                    'content'=> [
                        'audience_id' => $audienceId,
                        'estimated_size' => $estimatedSize
                    ]
                ]);
            } catch (\Exception $e) {
                $helper = $fb->getRedirectLoginHelper();
                $permissions = ['ads_management'];
                $url = $this->generateUrl('dashboard_filter_callack_oauth_facebook_v2', [], true);
                $loginUrl = $helper->getLoginUrl($url, $permissions);
                $msg = $e->getMessage();
                return new JsonResponse([
                    'error'=>1,
                    'content'=> '<div>'.$msg.'</div>'.'<a href="'.$loginUrl.'" target="_blank">Please <strong>login</strong> with Facebook first and then press push to Facebook again!</a>'
                ]);
            }

        } else {
            return new JsonResponse([
                'error'=>1,
                'content'=> 'Read a CSV file is unable.'
            ]);
        }
    }

    public function callbackOauthFacebookAction(Request $request)
    {
        $auth = $this->get('security.context')->getToken()->getUser();
        if ($auth->isDemoAccount()) {
            throw new HttpException(Response::HTTP_FORBIDDEN, "Sorry you cannot make changes as this feature is disabled in demo mode.");
        }
        $appId = $this->getParameter('facebook_app_id');
        $appSecret = $this->getParameter('facebook_app_secret');
        $code = $request->get('code');
        $fb = new Facebook(['app_id' => $appId,'app_secret' => $appSecret]);
        $helper = $fb->getRedirectLoginHelper();
        $session = $request->getSession();
        $hasAccessToken = false;
        try {
            $fbSession = (string) $helper->getAccessToken();
            $userFbAccessToken = $auth->getId() . '_facebook_access_token';
            $session->set($userFbAccessToken, $fbSession);
            $hasAccessToken = true;
        } catch (FacebookResponseException $e) {
            $hasAccessToken = false;
        } catch(FacebookSDKException $e) {
            $hasAccessToken = false;
        }

        return $this->render('::audience_deck/callback_oauth_facebook.html.twig', array('hasAccessToken' => $hasAccessToken));
    }

    public function loadListEventNameByAppTitleAction(Request $request)
    {
        $json = ['status' => 0];
        $listAppTitleId = $request->query->get('list_app_title_id');
        try {
            if (empty($listAppTitleId)) {
                throw new \Exception('List App Title must be a value.');
            }
            $em = $this->getDoctrine()->getManager('pgsql');
            $actionRepo = $this->get('action_repository');
            $listAppFlatform = $em->getRepository('Hyper\Domain\Application\ApplicationPlatform')->findByAppTitle($listAppTitleId);
            if (!empty($listAppFlatform)) {
                foreach ($listAppFlatform as $appPlatform) {
                    $listAppId[] = $appPlatform->getAppId();
                }
            }
            $listEventName = [];
            $listEventIAP = [];
            $listContentTypesByIAE = [];
            $listContent = [];
            if (!empty($listAppId)) {
                foreach($listAppId as $appId) {
                    if (
                        empty($listAppId)
                        || !$this->ieaConfigCached->exists()
                        || (!$iaeConfig = $this->ieaConfigCached->hget($appId))
                    ) {
                        continue;
                    }
                    $iaeConfig = json_decode($iaeConfig, true);
                    if (!empty($iaeConfig)) {
                        foreach ($iaeConfig as $eventName => $iae) {
                            $platformPrefix = $this->getPrefixByPlatform($appId);
                            $humanEventName = $platformPrefix . $eventName;
                            if (!empty($iae['event_friendly_name'])) {
                                $humanEventName =  $platformPrefix . $iae['event_friendly_name'];
                            }
                            $listEventName[$eventName] = $humanEventName;
                            if (!empty($iae['tag_as_iap'])) {
                                $listEventIAP[] = $eventName;
                            }
                            if (array_key_exists('content_types',$iae) && !empty($iae['content_types'])) {
                                $listContentTypesByIAE[$eventName] = $iae['content_types'];
                                $listContent = array_merge($listContent,$iae['content_types']);
                            }
                        }
                        
                    }
                }
            }
            // If list event name in cache is empty
            if (empty($listEventName)) {
                // then fetch list event name in database
                $listEventName = $this->getAvailableListEventNameByAppId($listAppId);
                if (!empty($listEventName)) {
                    foreach($listEventName as $key => $eventName) {
                        $platformPrefix = $this->getPrefixByPlatform($eventName['appId']);
                        $listEventName[$eventName['eventName']][] = $platformPrefix.$eventName['eventName'];
                        unset($listEventName[$key]);
                    }
                }
                // Fetch content type in DB only if the in-app-event config cache is empty
                $listContent = $actionRepo->getContentTypeByAppIds($listAppId);
            }
            
            $json['status'] = 1;
            $json['data']['list_category'] = $listContent;
            $json['data']['list_category_by_iae'] = $listContentTypesByIAE;
            $json['data']['list_event_name'] = $listEventName;
            $json['data']['list_event_iap'] = $listEventIAP;
            
        } catch (\Exception $e) {
            $json['msg'] = $e->getMessage();
        }
        return new JsonResponse($json);
    }

    private function getAvailableListEventNameByAppId($listAppId = array()) {
        $actionRepo = $this->get('action_repository');
        $listEventName = $actionRepo->getEventNameAppIdByAppIds($listAppId);

        return $listEventName;
    }

    private function getPrefixByPlatform($appId)
    {
        $platformPrefix = '';
        if (strpos($appId,'.') !== false) {
            $platformPrefix = 'Android - ';
        } elseif (preg_match("/id(\d+)/", $appId, $match)) {
            $platformPrefix = 'IOS - ';
        }

        return $platformPrefix;
    }

    public function loadListAdAccountFbAction(Request $request)
    {
        $auth = $this->get('security.context')->getToken()->getUser();
        if ($auth->isDemoAccount()) {
            throw new HttpException(Response::HTTP_FORBIDDEN, "Sorry you cannot make changes as this feature is disabled in demo mode.");
        }
        $appId = $this->getParameter('facebook_app_id');
        $appSecret = $this->getParameter('facebook_app_secret');
        $fb = new Facebook(['app_id' => $appId,'app_secret' => $appSecret]);
        // Check login facebook
        $session = $request->getSession();
        $userKeyAccessTokenFB = $auth->getId() . '_facebook_access_token';
        //$session->set($userKeyAccessTokenFB, null);
        if (!$session->has($userKeyAccessTokenFB)) {
            $session->set($userKeyAccessTokenFB, null);
        }
        $userAccessTokenFB = $session->get($userKeyAccessTokenFB);
        $isLoginFB = true;
        if (!$userAccessTokenFB) {
            $helper = $fb->getRedirectLoginHelper();
            $permissions = ['ads_management'];
            $url = $this->generateUrl('dashboard_filter_callack_oauth_facebook_v2', [], true);
            $loginUrl = $helper->getLoginUrl($url, $permissions);

            return new JsonResponse([
                'error'=>1,
                'content'=> '<a href="'.$loginUrl.'" target="_blank">Please <strong>login</strong> with Facebook first and then press push to Facebook again!</a>'
            ]);
        }
        $listAdAccountParsed = [];
        try {
            Api::init($appId, $appSecret, $userAccessTokenFB);
            $me = new AdUser('me');
            $listAdAccount = $me->getAdAccounts();
            if (!empty($listAdAccount)) {
                foreach ($listAdAccount as $adAccount) {
                    $adAccountData = $adAccount->getData();
                    $listAdAccountParsed[] = $adAccountData['id'];
                }
            }
            return new JsonResponse([
                'error' => 0
                , 'content' => $listAdAccountParsed
            ]);
        } catch (\Exception $e) {
            $helper = $fb->getRedirectLoginHelper();
            $permissions = ['ads_management'];
            $url = $this->generateUrl('dashboard_filter_callack_oauth_facebook_v2', [], true);
            $loginUrl = $helper->getLoginUrl($url, $permissions);
            $msg = $e->getMessage();

            return new JsonResponse([
                'error'=>1,
                'content'=> '<div>'.$msg.'</div>'.'<a href="'.$loginUrl.'" target="_blank">Please <strong>login</strong> with Facebook first and then press push to Facebook again!</a>'
            ]);
        }
    }

    private function getListAppTitleByCache() {
        $listAppTitle = array();
        $countDeviceByAppTitleCached = new CountDeviceByAppTitleCached($this->container);
        $countDeviceByAppTitleCachedData = $countDeviceByAppTitleCached->hget($_SESSION['client_id']);
        $countDeviceByAppTitleCachedData = json_decode($countDeviceByAppTitleCachedData,true);
        //echo "<pre>";
        //var_dump($countDeviceByAppTitleCachedData); die;
        foreach ($countDeviceByAppTitleCachedData as $appTitleId =>$appTitleData) {
            $appTitleValue = array('id'=>$appTitleId);
            foreach( $appTitleData as $appTitle ){
                $appTitleValue['title'] = $appTitle['app_title'];
            }
            $listAppTitle[] = $appTitleValue;
        }

        return $listAppTitle;
    }
    
    private function getListAppIdByCache() {
        $listAppId = array();
        $countDeviceByAppTitleCached = new CountDeviceByAppTitleCached($this->container);
        $countDeviceByAppTitleCachedData = $countDeviceByAppTitleCached->hget($_SESSION['client_id']);
        $countDeviceByAppTitleCachedData = json_decode($countDeviceByAppTitleCachedData,true);
        foreach ($countDeviceByAppTitleCachedData as $appTitleData) {
            foreach( $appTitleData as $appTitleId => $appTitle ){
                $listAppId[] = $appTitle['app_id'];
            }
        }
        return $listAppId;
    }
    
    private function getListCoutryCache() {
        $listCountry= array();
        $countDeviceByCountryCached = new CountDeviceByCountryCached($this->container);
        $countDeviceByCountryCachedData = $countDeviceByCountryCached->hget($_SESSION['client_id']);
        $countDeviceByCountryCachedData = json_decode($countDeviceByCountryCachedData,true);
        foreach ($countDeviceByCountryCachedData as $deviceByCountryCachedData) {
            if ($deviceByCountryCachedData['1'] >0 || $deviceByCountryCachedData['2'] >0) {
                $listCountry[] = array('countryCode' => $deviceByCountryCachedData['country_code']);
            } 
        }
        asort($listCountry);
        return $listCountry;
    }

}
