<?php

namespace Hyper\EventBundle\Controller\Dashboard\Filter;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\DBAL\Driver\PDOStatement;

// DT Repository
use Hyper\DomainBundle\Repository\Filter\DTFilterRepository;

// Entities
use Hyper\Domain\Filter\Filter;
use Hyper\Domain\Device\Device;
use Hyper\Domain\Client\Client;

class PresetFilterController extends Controller
{
    public $authentication;
    public $client;
    public $intents;
    
    /**
    * @param ContainerInterface $container
    */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    
    public function indexAction(Request $request)
    {

    }
    
    public function showListAction(Request $request)
    {
        try {
            $authController = $this->get('auth.controller');
            $authIdFromSession = $authController->getLoggedAuthenticationId();
            
            $filterRepo = $this->container->get('filter_repository');
            $page = $request->get('page');
            //$page = 0;
            $rpp = 5;
            // TODO - get authentication_id by session
            $authenticationId = $authIdFromSession;
            
            $result = $filterRepo->getResultAndCount($page,$rpp,$authenticationId);
            $rows = $result['rows'];
            $totalCount = $result['total'];
            $paginator = new \lib\Paginator($page, $totalCount, $rpp);
            //var_dump($paginator);
            $pageList = $paginator->getPagesList();
            return $this->render('filter/filter_index.html.twig', 
                array(
                    'rows' => $rows, 
                    'paginator' => $pageList, 
                    'cur' => $page, 
                    'total' => $paginator->getTotalPages(), 
                    'authentication_id'=>$authenticationId,
                    'active' => 'audience_deck'
                    )
            );
        } catch (\lib\Exception\InvalidAuthenticationException $ex) {
            
            return $this->render('filter/filter_index.html.twig', 
                array(
                    'exception' => $ex->getMessage()
                    )
            );
        }
        
    }
    
    public function showAddAction(Request $request)
    {
        try 
        {
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
            $deviceRepo = $this->get('device_repository');
            $countries = $deviceRepo->getActiveCountries($appIds);
            
            $categoryRepo = $this->get('category_repository');
            $categories = $categoryRepo->getActiveCategories($appIds);
            $availableIntents = $this->getAvailableIntentsByClientApp(array());
            
            /* Added for edit/update feature 2015-12-23 paul.francisco */
            $this->mode      = $request->request->get('mode');
            $this->preset_id = $request->request->get('update_preset_id');
            if("update" == $this->mode && "" != $this->preset_id)
            {
                $filterRepo = $this->get('filter_repository');
                $record = $filterRepo->getRecordForUpdate("$this->preset_id");
                
                return $this->render('filter/custom_audience_add.html.twig', 
                    array(
                        'active' => "custom_audience",
                        'active_country_list' => $countries,
                        'active_platform' => array(
                            Device::IOS_PLATFORM_CODE => Device::IOS_PLATFORM_NAME,
                            Device::ANDROID_PLATFORM_CODE => Device::ANDROID_PLATFORM_NAME
                        ),
                        'active_interest' => $categories,
                        'active_intent' => $availableIntents,
                        'selected_record' => $record
                    )
                );
            }
            else
            {
                return $this->render('filter/custom_audience_add.html.twig', 
                    array(
                        'active' => "custom_audience",
                        'active_country_list' => $countries,
                        'active_platform' => array(
                            Device::IOS_PLATFORM_CODE => Device::IOS_PLATFORM_NAME,
                            Device::ANDROID_PLATFORM_CODE => Device::ANDROID_PLATFORM_NAME
                        ),
                        'active_interest' => $categories,
                        'active_intent' => $availableIntents
                    )
                );
            }
            
        } catch (\lib\Exception\InvalidAuthenticationException $ex) {
            
            return $this->render('filter/filter_index.html.twig', 
                array(
                    'exception' => $ex->getMessage()
                    )
            );
        }
    }
    
    public function executeAddAction(Request $request)
    {
        
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
                
                $countryCodes = $request->get('country_codes');
                $platformIds = $request->get('platform_ids');
                $categoryIds = $request->get('cat_ids');
                $description = $request->request->get('description');
                
                if (empty($categoryIds)) {
                    $categoryIds = array();
                }
                $filterRepo = $this->get('filter_repository');
                $intentKey = $request->get('intent_key');
                $filter = new Filter();
                $filter->setAuthenticationId($authenticationId);
                $filter->setPresetName($presetName);
                $filter->setDescription($description);
                if (!empty($countryCodes)){
                    $filterMetadata['\Hyper\Domain\Device\Device.countryCode'] = array(
                        'expression' => 'IN',
                        'value' => $countryCodes
                    );
                }
                //if (!empty($platformFilter)) {
                if (!empty($platformIds)) {
                    $filterMetadata['\Hyper\Domain\Device\Device.platform'] = array(
                        'expression' => 'IN',
                        'value' => $platformIds
                    );
                }
                if (!empty($intentKey)) {
                    $availableIntents = $this->getAvailableIntentsByClientApp($categoryIds);
                    if (isset($availableIntents[$intentKey])) {
                        $filterMetadata['intent_metadata'] = array(
                            'behaviour_id' => $availableIntents[$intentKey]['behaviour_id'],
                            'category_ids' => $categoryIds,
                            'intent_key' => $intentKey
                        );
                    }
                }
                $filter->setFilterMetadata($filterMetadata);
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
    
    public function executeDeleteAction(Request $request)
    {
        $this->id = $request->request->get('id');
        
        if((null != $this->id) && ("" != $this->id))
        {
            $filterRepo = $this->container->get('filter_repository');
            
            $delete = $filterRepo->deletePreset($this->id);
            
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
        
        $inWishList1MonthSql = " AND A.device_id IN ( SELECT distinct device_id FROM add_to_wishlist_actions GROUP BY device_id HAVING count(device_id) > 5 ) AND ( A.added_time > (extract(epoch from getdate()) - 2628000) AND A.added_time <= extract(epoch from getdate()) ) "; 
        $inPurchase1WeekSql = ' AND A.transacted_time > (extract(epoch from getdate()) - 2628000) AND A.transacted_time <= extract(epoch from getdate())';
        $inPurchase1Week5PurchaseSql = " AND A.device_id IN ( SELECT distinct device_id FROM transaction_actions GROUP BY device_id HAVING count(device_id) > 5 ) AND ( A.transacted_time > (extract(epoch from getdate()) - 2628000) AND A.transacted_time <= extract(epoch from getdate()) ) ";
        $MScoreGT51MonthEccomerceSql = " AND A.device_id in (select device_id from frm group by device_id,event_time HAVING SUM(amount) >= 300 and event_time > (extract(epoch from getdate())-10512000)  )";
        $MScoreGT51MonthGameSql = " AND A.device_id in (select device_id from frm group by device_id,event_time HAVING SUM(amount) >= 30 and event_time > (extract(epoch from getdate())-10512000)  )";
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
    
    public function getAvailableIntentsByClientApp(array $categoryIds) {
        $intents = $this->getIntents();
        $appIds = $this->client->getClientApp();
        $intentKeys = array('recent_install');
        //temporary set 
        if( strpos($appIds,'com.bukalapak.android')!== false || strpos($appIds,'id1003169137') !== false ) {
            $intentKeys = array('recent_install','potential_spenders','recent_purchase','shopaholic');
        } elseif ( strpos($appIds,'com.daidigames.banting')!== false || strpos($appIds,'id961876128') !== false ) {
            $intentKeys = array('recent_install','paying_gamers','active_spenders');
        } elseif ($this->client->getAccountType()  == Client::ACCOUNT_TYPE['E-commerce']) {
            $intentKeys = array('recent_install','potential_spenders','recent_purchase');
        } elseif ($this->client->getAccountType()  == Client::ACCOUNT_TYPE['Gaming']) {
            $intentKeys = array('recent_install','active_spenders');
        } 
        // to do support branding app
        foreach($intents as $key => $intent){
            if (!in_array($key,$intentKeys)) {
                unset($intents[$key]);   
            }
        }
        return $intents;
    }
    
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
            $preset_name = $request->request->get('preset_name');
            $description = $request->request->get('description');
            $countryCodes = $request->request->get('country_codes');
            $platformIds = $request->request->get('platform_ids');
            $categoryIds = $request->request->get('cat_ids');
            $intent      = $request->request->get('intent_key');
            
            if (!empty($countryCodes)){
                $filterMetadata['\Hyper\Domain\Device\Device.countryCode'] = array(
                    'expression' => 'IN',
                    'value' => $countryCodes
                );
            }
            
            //if (!empty($platformFilter)) {
            if (!empty($platformIds)) {
                $filterMetadata['\Hyper\Domain\Device\Device.platform'] = array(
                    'expression' => 'IN',
                    'value' => $platformIds
                );
            }
            if (!empty($intentKey)) {
                $availableIntents = $this->getAvailableIntentsByClientApp($categoryIds);
                if (isset($availableIntents[$intentKey])) {
                    $filterMetadata['intent_metadata'] = array(
                        'behaviour_id' => $availableIntents[$intentKey]['behaviour_id'],
                        'category_ids' => $categoryIds,
                        'intent_key' => $intentKey
                    );
                }
            }
            
            $filterRepo = $this->get('filter_repository');
            
            $update = $filterRepo->updateFilter($id, $authenticationId, $preset_name, $filterMetadata, $description, 0);
            
            return new Response(json_encode(array("msg" => $update)));
            
            // return new Response('Preset filter created successfully');
            
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
}
