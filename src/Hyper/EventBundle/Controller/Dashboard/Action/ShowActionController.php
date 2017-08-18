<?php
namespace Hyper\EventBundle\Controller\Dashboard\Action;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Hyper\EventBundle\Service\EventProcess;

// entities
use Hyper\Domain\Filter\Filter;
use Hyper\Domain\Authentication\Authentication;
use Hyper\Domain\Device\Device;
use Hyper\Domain\Frm\Frm;
use Hyper\Domain\Action\Action;
// Doctrine
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\DBAL\Driver\PDOStatement;

class ShowActionController extends Controller
{

    public $behaviour_ids = array();
    public $transaction_days = array();
    public $common_record_field = array();

    /**
    * @param ContainerInterface $container
    */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->setBehaviourIds();
        $this->setTransactionDays();
        $this->setRecordFields();

    }
    
    public function showByClientAction(Request $request)
    {
        //$result_data = array();
        //$offset = 0;
        // check for login
        //$post = $request->request->all();
        //echo "<pre>";
        //var_dump($post);
        //die;
        try {
            $form_data = array();
            $authController = $this->get('auth.controller');
            $authIdInSession = $authController->getLoggedAuthenticationId();
            // get session id
            // fetch preset filter by authentication session
            /* ADDED TO REDIRECT TO LOGIN IF THERE IS NO SESSION paul.francisco 2015-12-18 */
            if($authIdInSession == null)
            {
                $this->url = $this->generateUrl('dashboard_logout');
                return $this->redirect($this->url, 301);
            }
            
            $authenticationRepo = $this->get('authentication_repository');
            $presetFilterRepo = $this->get('filter_repository');
            $presetFilterByAuthSession = $presetFilterRepo->getAllByAuthenticationId($authIdInSession);
            $form_data['preset_filters_by_auth_session'] = $presetFilterByAuthSession;
            //print_r($form_data['preset_filters_by_auth_session']);
            
            $filterMetadata = array();
            $appIdsByAuthentication = null;
            //$preset_filter_id = $request->get('preset_filter_id');
            $preset_filter_id = isset($_POST['preset_filter_id'])?$_POST['preset_filter_id']:null;
            //var_dump($preset_filter_id);//die;
            if (null == $preset_filter_id) {
               // throw new \Exception('Please select preset_filter');
            }
            
            $last_preset_filter_id = isset($_POST['last_preset_filter_id'])?$_POST['last_preset_filter_id']:null;
            $last_client_id = isset($_POST['last_client_id'])?$_POST['last_client_id']:null;
            
            $behaviour_id = $request->get('behaviour_id');
            $last_behaviour_id = $request->get('last_behaviour_id');
            $last_sort_field = $request->get('last_sort_field');
            $transaction_day = $request->get('transaction_day');
            $row_number = $request->get('row_number');
            $page_num = $request->get('page_num');
            $sort_field = $request->get('sort_field');
            $sort_order = $request->get('sort_order');
            $search_field = $request->get('search_field');
            $search_string = $request->get('search_string');
            $export_data = $request->get('export_data');
            $app_id = $request->get('client_app');
            $last_preset_name = $request->request->get('last_preset_name');
            
            if (null != $preset_filter_id) {
                
                $presetFilter = $presetFilterRepo->find($preset_filter_id);
                if (!$presetFilter instanceof Filter) {
                    throw new \Exception('invalid preset filter');
                }
                $filterMetadata = $presetFilter->getFilterMetadata();
                if (isset($filterMetadata['intent_metadata']['behaviour_id'])) {
                    $behaviour_id = $filterMetadata['intent_metadata']['behaviour_id'];
                    $last_behaviour_id = $behaviour_id;
                    $transaction_day = null;
                } else {
                    $behaviour_id = -1;
                }
                //$authenticationId = $presetFilter->getAuthenticationId();
            }
            else {
                //$authenticationId = $authIdInSession;
            }
            $authenticationId = $authIdInSession;
            $authentication = $authenticationRepo->findbyCriteria('id',"$authenticationId");
            if (!$authentication instanceof Authentication) {
                throw new \Exception('invalid authentication');
            }
            
            /* CLEAN THE VALUE FROM DATA LOGS OR AUDIENCE DECK TO BE USED IN REDSHIFTDATA
            *  2015-12-18 paul.francisco 
            */
            if($app_id != "")
            {
                //should be $appIds
                $appIds = "'$app_id'";
            }
            else
            {
                $clientIds = $authController->refreshClient();
                
                $apps = explode(",", $clientIds);
                
                $clientRepo = $this->container->get('client_repository');
                $clientids  = $clientRepo->getClientAppsByIds(($apps));
                
                $appIds  = "'" .implode("','", $clientids) ."'";
                //print_r($app_tom); die;
            }
            
            //$clientIds = $authController->refreshClient();
            
            // $appIdsByAuthentication = $client->getClientApp();// 'com.bukalapak.android,id1003169137'
            
            if ((null !== $last_preset_filter_id) && ($preset_filter_id != $last_preset_filter_id)) {
                $page_num = null;
                $sort_field = null;
                $sort_order = null;
                $search_field = null;
                $search_string = null;
            }
            
            // If new action type is selected, reset page number, and criteria for sort and search
            /*
            if ((null !== $last_behaviour_id) && ($behaviour_id != $last_behaviour_id)) {
                $page_num = null;
                $sort_field = null;
                $sort_order = null;
                $search_field = null;
                $search_string = null;
            }
            */
    
            if (null == $page_num) {
                $offset = 0;
            } else {
                $offset = ($row_number * ($page_num - 1));
            }
    
            $behaviour_ids = $this->getBehaviourIds();
            /*
            if ((null != $behaviour_id) && (isset($behaviour_ids[$behaviour_id]))) {
                $behaviour_ids[$behaviour_id]["is_selected"] = 1;
            }
            */
            $from_date = null;
            $transaction_days = $this->getTransactionDays();
            if (null != $transaction_day) {
                if (isset($transaction_days[$transaction_day])) {
                    $transaction_days[$transaction_day]["is_selected"] = 1;
                }
    
                if (1 == $transaction_day) {
                    $from_date = strtotime('-1 day');
                } else if (2 == $transaction_day) {
                    $from_date = strtotime('-3 day');
                } else if (3 == $transaction_day) {
                    $from_date = strtotime('-7 day');
                } else {
                    $from_date = null;
                }
            }
    
            $form_data["behaviour_ids"] = $behaviour_ids;
            $form_data["transaction_days"] = $transaction_days;
    
            $table_field = null;
            $table_order = null;
            $table_search_field = null;
            $table_search_string = null;
            //print_r($behaviour_id);die;
            if (null != $behaviour_id) {
                //$tmp = array();
                //$result_data = $this->getRedShiftData($action_type, $from_date, $row_number, $offset);
    
                if ((null != $sort_field) && ('' != $sort_field)) {
                    $table_field = $this->getFieldID($sort_field);
    
                    if ($last_sort_field == $sort_field) {
                        if (0 == $sort_order) {
                            $table_order = "asc";
                        } else if (1 == $sort_order) {
                            $table_order = "desc";
                        } else {
                            $sort_field = null;
                            $sort_order = null;
                            $table_field = null;
                            $table_order = null;
                        }
                    } else {
                        $table_order = "asc";
                    }
                } else {
                    $sort_field = null;
                    $sort_order = null;
                }
    
                if ((null != $search_string) || ("" != trim($search_string))) {
                    $table_search_field = $this->getFieldID($search_field);
                    $table_search_string = $search_string;
                }
                
                // if($app_id != "" || $app_id != null)
                // {
                //     $result_data = $this->getRedShiftData($behaviour_id, $from_date, $row_number, $offset, $table_field, $table_order, $table_search_field, $table_search_string, $filterMetadata, $app_id, "logs");
                // }
                // else
                // {
                //     $result_data = $this->getRedShiftData($behaviour_id, $from_date, $row_number, $offset, $table_field, $table_order, $table_search_field, $table_search_string, $filterMetadata, $clientIds, "audience_deck");    
                // }

                $result_data = $this->getRedShiftData($behaviour_id, $from_date, $row_number, $offset, $table_field, $table_order, $table_search_field, $table_search_string, $filterMetadata, $appIds);    
                
                /* FOR DEBUGGING SQL */
                $form_data['sql'] = $result_data["sql"];
                //echo $result_data['sql']; die;
    
                $form_data["column_names"] = $result_data["column_names"];
                if (count($result_data["records"])) {
                    // Ensures that is the user switch from a high "Date Within" to a low value it will not cause problems
                    if ($offset >= $result_data["record_count"]) {
                        $page_num = 1;
                    }
    
                    $form_data["result_data"]   = $result_data["records"];
                    $form_data["record_count"]  = $result_data["record_count"];
                    $form_data["page_count"]    = $result_data["page_count"];
                    $form_data["page_num"]      = $page_num;
                    $form_data["row_number"]    = $row_number;
                    $form_data["sort_field"]    = $sort_field;
                    $form_data["sort_order"]    = $sort_order;
                    $form_data["search_field"]  = $search_field;
                    $form_data["search_string"] = $search_string;
    
                } else {
                    foreach ($form_data["column_names"] as $key => $column) {
                        $form_data["result_data"][0][$column] = "";
                    }
                    $form_data["record_count"] = 0;
                    $form_data["page_count"]   = 0;
                    $form_data["page_num"]     = 0;
                    $form_data["row_number"]   = 10;
                    $form_data["sort_field"]   = "";
                    $form_data["sort_order"]   = "";
                    $form_data["search_field"] = "";
                    $form_data["search_string"]= "";
    
                }
            } else {
    
                $form_data["column_names"] = $this->getRecordFields(array());
                foreach ($form_data["column_names"] as $key => $column) {
                    $form_data["result_data"][0][$column] = "";
                }
                $form_data["record_count"] = 0;
                $form_data["page_count"]   = 0;
                $form_data["page_num"]     = 0;
                $form_data["row_number"]   = 10;
                $form_data["sort_field"]   = "";
                $form_data["sort_order"]   = "";
                $form_data["search_field"] = "";
                $form_data["search_string"]= "";
            }
    
            $form_data['last_behaviour_id'] = $behaviour_id;
            $form_data['last_sort_field']  = $sort_field;
            $form_data['last_preset_filter_id'] = $preset_filter_id;
            $form_data['last_client_id'] = $last_client_id;
            $form_data['last_preset_name'] = $last_preset_name;
    
            //echo($form_data['last_preset_name'] ); die; 
            
            foreach($form_data["column_names"] as $key => $columns) {
                if (false === strpos($columns, "Date")) {
                    $search_columns[] = $columns;
                }
            }
            
            /* Added to support client display and query
            *  Modified to get only available apps for the current logged in
            *  2015-12-04 paul.francisco
            */
            /* GET client in authentication */
            $client = $authController->getClientIds();
            $c_ids  = explode(",", $client);
            $c_ids  = "'" .implode("','", $c_ids) ."'";
            
            /* GET the app_ids incorporated in that client_id in client table */
            $conn = $this->get('doctrine.dbal.pgsql_connection');
            $cSql = $conn->prepare("SELECT DISTINCT id, client_app FROM client WHERE id IN ($c_ids);");
            $cSql->execute();
            $c_app = "";
            for($c = 0; $cow = $cSql->fetch(); $c++)
            {
                $c_app .= $cow['client_app'] . ",";
            }
            
            $c_app = substr($c_app, 0, -1);
            
            /* GET the informations in application table */
            $app_ids  = explode(",", $c_app);
            $app_ids  = "'" .implode("','", $app_ids) ."'";
            
            //print "SELECT DISTINCT app_id, app_name FROM applications WHERE app_id IN ($app_ids);"; die;
            
            $sql  = $conn->prepare("SELECT DISTINCT app_id, app_name FROM applications WHERE app_id IN ($app_ids);");                      
            $sql->execute();
            $data = array();
            for($x = 0; $row = $sql->fetch(); $x++) 
            {
                $data[] = $row;
            }  
            
            //$access_rights = $authController->getLoggedAuthentication()->getUserType();
            /* Check which page requested 
             * paul.francisco 2015-12-04
             */
            if(!null == $request->get('page'))
            {
                $access_rights = $request->get('page');
            }
            else
            {
                $access_rights = $request->query->get('page');
            }
            
            $form_data['clients']       = $data;
            $form_data['search_fields'] = $search_columns;
            //$form_data['active']        = 'audience_deck';
            $form_data['count_card']    = count($form_data['preset_filters_by_auth_session']);
    
            // add by Thien to return data only without render to the template
            if($export_data == true){
                return $form_data;
            }
            
            //print_r($form_data['column_names']); die;
            
            /* Remove unset of columns as it is already limited in sql 2015-12-16 paul.francisco */
            
            //$idr = 13424.50;
            $currencyRepo = $this->container->get('currency_repository');
            $currency = $currencyRepo->findbyCriteria("name","idr");
            $rate = $currency->getRate();
            
            $cents = count($form_data['result_data']);
            for($x = 0; $x < $cents; $x++)
            {
                /* Remove unset of columns as it is already limited in sql 2015-12-16 paul.francisco */
                
                /* Convert currency to USD before displaying to frontend
                * 2015-12-04 paul.francisco
                */
                if(isset($form_data['result_data'][$x]['transacted_price']))
                {
                    $conversion = $currencyRepo->convert("idr", $form_data['result_data'][$x]['transacted_price']);
                    $converted = explode(".", $conversion);
                    
                    if($converted[0] < 1)
                    {
                        $form_data['result_data'][$x]['transacted_price'] = '$' . $conversion;
                    }
                    else
                    {
                        $converted  = $converted[0] < 10 && $converted[0] > 0 ? '0'.$converted[0].'.'.$converted[1] : $converted[0].'.'.$converted[1];
                        $form_data['result_data'][$x]['transacted_price'] = '$' . $converted;
                    }
                    /*
                    $conversion = number_format($form_data['result_data'][$x]['transacted_price'] / $idr, 2);
                    $converted  = explode(".", $conversion);
                    
                    if($converted[0] < 1)
                    {
                        $min = number_format($form_data['result_data'][$x]['transacted_price'] / $idr, 5);
                        // $form_data['result_data'][$x]['transacted_price'] = '(idr) '.$form_data['result_data'][$x]['transacted_price']. ' - $' . $min;
                        $form_data['result_data'][$x]['transacted_price'] = '$' . $min;
                    }
                    else
                    {
                        $converted  = $converted[0] < 10 && $converted[0] > 0 ? '0'.$converted[0].'.'.$converted[1] : $converted[0].'.'.$converted[1];
                        // $form_data['result_data'][$x]['transacted_price'] = '(idr) '.$form_data['result_data'][$x]['transacted_price']. ' - $' . $converted;
                        $form_data['result_data'][$x]['transacted_price'] = '$' . $converted;
                    }
                    */
                }
            }
            
            if($access_rights == 'client')
            {
                $form_data['active']  = 'audience_deck';
                return $this->render('action/client/list.html.twig', $form_data);
            }
            else
            {
                //print_r($form_data['result_data']); die;
                //print_r($form_data['column_names']); die;
                
                $form_data['active']  = 'logs';
                return $this->render('action/admin/data_logs.html.twig', $form_data);
            }
        } catch(\Exception $ex) {
            print_r($ex->getLine());
            print_r($ex->getFile());
            print_r($ex->getMessage());
            die;
            return $this->render('action/client/list.html.twig', 
                array(
                    'exception' => $ex->getMessage()
                    )
            );
        } catch (\lib\Exception\InvalidAuthenticationException $ex) {
            
            return $this->render('action/client/list.html.twig', 
                array(
                    'exception' => $ex->getMessage()
                    )
            );
        }
        
    }

    public function setBehaviourIds() {

        $this->behaviour_ids = array(
            Action::BEHAVIOURS['INSTALL_BEHAVIOUR_ID'] => array("name" => "Install", "is_selected" => 0),
            Action::BEHAVIOURS['SEARCH_BEHAVIOUR_ID'] => array("name" => "Search", "is_selected" => 0),
            Action::BEHAVIOURS['ADD_TO_WISHLIST_BEHAVIOUR_ID'] => array("name" => "Add to Wishlist", "is_selected" => 0),
            Action::BEHAVIOURS['ADD_TO_CART_BEHAVIOUR_ID'] => array("name" => "Add to Cart", "is_selected" => 0),
            Action::BEHAVIOURS['PURCHASE_BEHAVIOUR_ID'] => array("name" => "Purchase", "is_selected" => 0)
        );
    }

    public function getBehaviourIds() {
        return $this->behaviour_ids;
    }

    public function setTransactionDays() {
        $this->transaction_days = array(
            "1" => array("name" => "24 hours", "is_selected" => 0),
            "2" => array("name" => "3 days", "is_selected" => 0),
            "3" => array("name" => "1 week", "is_selected" => 0),
            "4" => array("name" => "1 month", "is_selected" => 0)
        );
    }

    public function getTransactionDays() {
        return $this->transaction_days;
    }

    public function getRedShiftData($behaviour_id, $from_date = null, $limit = null, $offset = null, $sort_field = null, $sort_order = null, $search_field = null, $search_string = null, $filterMetadata = array(), $appIds = null) {
        $index = 0;
        $date_field = "";
        $column_names = array();

        $conn = $this->get('doctrine.dbal.pgsql_connection');

        if ("id" == $search_field) {
            $search_field = "A.id";
        }
        
        /* REMOVE FROM QUERY 
         *  id, app_id, app_version, currency
         */
        switch ($behaviour_id) {
            case -1:
                $table = "install_actions";
                $date_field = "installed_time";
                $columns = " A.device_id, B.app_name, A.installed_time ";
                break;
            case Action::BEHAVIOURS['INSTALL_BEHAVIOUR_ID']:
                $table = "install_actions";
                $date_field = "installed_time";
                //$columns = " A.id as hypid, A.device_id as device_id, B.app_id as application_id, B.app_name as application_name, B.app_version as application_version, A.installed_time as install_date ";
                // $columns = " A.id, A.device_id, B.app_id, B.app_name, B.app_version, A.installed_time ";
                $columns = " A.device_id, B.app_name, A.installed_time ";
                break;
            case Action::BEHAVIOURS['SEARCH_BEHAVIOUR_ID']:
                $table = "search_actions";
                $date_field = "searched_time";
                //$columns = " A.id as hypid, A.device_id as device_id, B.app_id as application_id, B.app_name as application_name, B.app_version as application_version, A.search_string as search_string, A.searched_time as searched_date ";
                // $columns = " A.id, A.device_id, B.app_id, B.app_name, B.app_version, A.search_string, A.searched_time ";
                $columns = " A.device_id, B.app_name, A.search_string, A.searched_time ";
                break;
            case Action::BEHAVIOURS['ADD_TO_WISHLIST_BEHAVIOUR_ID']:
                $table = "add_to_wishlist_actions";
                $date_field = "added_time";
                //$columns = " A.id as hypid, A.device_id as device_id, B.app_id as application_id, B.app_name as application_name, B.app_version as application_version, A.total_items as total_items, A.quantity as quantity, A.added_time as add_date ";
                // $columns = " A.id, A.device_id, B.app_id, B.app_name, B.app_version, A.total_items, A.quantity, A.added_time ";
                $columns = " A.device_id, B.app_name, A.total_items, A.quantity, A.added_time ";
                break;
            case Action::BEHAVIOURS['ADD_TO_CART_BEHAVIOUR_ID']:
                $table = "add_to_cart_actions";
                $date_field = "added_time";
                //$columns = " A.id as hypid, A.device_id as device_id, B.app_id as application_id, B.app_name as application_name, B.app_version as application_version, A.total_items as total_items, A.added_time as add_date";
                // $columns = " A.id, A.device_id, B.app_id, B.app_name, B.app_version, A.total_items, A.added_time ";
                $columns = " A.device_id, B.app_name, A.total_items, A.added_time ";
                break;
            case Action::BEHAVIOURS['PURCHASE_BEHAVIOUR_ID']:
                $table = "transaction_actions";
                $date_field = "transacted_time";
                //$columns = " A.id as hypid, A.device_id as device_id, B.app_id as application_id, B.app_name as application_name, B.app_version as application_version, A.transacted_price as transact_price, A.quantity as quantity, A.currency as currency, A.transacted_time as transaction_date";
                // $columns = " A.id, A.device_id, B.app_id, B.app_name, B.app_version, A.transacted_price, A.quantity, A.currency, A.transacted_time ";
                $columns = " A.device_id, B.app_name, A.transacted_price, A.quantity, A.transacted_time ";
                break;
        }
        
        // if($identity == "audience_deck")
        // {
        //     $page_info = $this->getRedShiftDataCount($conn, $table, $date_field, $from_date, $limit, $search_field, $search_string, $filterMetadata, $appIds, $identifier = "Audience Deck");    
        // }
        // else
        // {
        //     $page_info = $this->getRedShiftDataCount($conn, $table, $date_field, $from_date, $limit, $search_field, $search_string, $filterMetadata, $appIds, $identifier = "logs");
        // }
        
        $page_info = $this->getRedShiftDataCount($conn, $table, $date_field, $from_date, $limit, $search_field, $search_string, $filterMetadata, $appIds);
        

        // Ensures that is the user switch from a high "Date Within" to a low value it will not cause problems
        if ($offset >= $page_info["record_count"]) {
            $offset = 0;
        }

        //$sql = 'select ' . $columns . ' from '.$table.' A left join applications B on A.application_id=B.id left join devices C on A.device_id=C.id';
        /* EDITED: 2015-11-25 paul.francisco
        * Use condition based on the request (Data Logs or Custom Audience)
        */
        
        $sql = 'select ' . $columns . ' from '.$table.' A left join applications B on A.application_id=B.id left join devices C on A.device_id=C.id';
        
        // if($identity == "audience_deck")
        // {
        //     $sql = 'select ' . $columns . ' from '.$table.' A left join applications B on A.application_id=B.id left join devices C on A.device_id=C.id';
        // }
        // else
        // {
        //     $sql = 'select ' . $columns . ' from '.$table.' A join applications B on A.application_id=B.id join devices C on A.device_id=C.id';
        // }
        
        if (null != $from_date) {
            $sql .= " where " . $date_field . " >= " . $from_date . " ";
        }
        else{
            $sql .= " where 1=1 ";
        }
        
        if (!empty($filterMetadata)) {
            $sql .= $this->filterMeta2SQL($filterMetadata);
        }
        
        // if($identity == "audience_deck")
        // {
        //     if (!empty($appIds)){
        //         $apps = explode(",", $appIds);
                
        //         $clientRepo = $this->container->get('client_repository');
        //         $clientids  = $clientRepo->getClientAppsByIds(($apps));
                
        //         //$ids  = explode(",", $clientids);
        //         $ids  = "'" .implode("','", $clientids) ."'";
        //         //$appIds = str_replace(',',"','",$appIds);
        //         $sql .= " and B.app_id IN (".$ids.") ";
        //     }
        // }
        // else
        // {
        //     $sql .= " and B.app_id = '$appIds'";
        // }
        
        $sql .= " and B.app_id IN (".$appIds.") ";
        
        if ((null != $search_field) && (null !== $search_string)) {
            $sql .= " and " . $search_field . " LIKE '%" . $search_string . "%' ";
        }

        if (null == $sort_field) {
        $sql .= " order by " . $date_field . " desc ";
        } else {
            $sql .= " order by " . $sort_field . " " . $sort_order;
        }

        if (null != $limit) {
            $sql .= " limit " . $limit . " ";
            if (null != $offset) {
                $sql .= " offset " . $offset . " ";
            }
        }
        
        // echo "<hr/>".$sql."<hr/>"; die;
        //$sql = "select A.id, A.device_id, B.app_id, B.app_name, B.app_version, A.installed_time from install_actions A left join applications B on A.application_id=B.id left join devices C on A.device_id=C.id where C.country_code IN ('ID') and B.app_id IN ('combukalapakandroid','id1003169137') order by installed_time desc limit 50";
        $result_data = $conn->query($sql);

        $column_names = $this->getRecordFields(array());
        $rs_data = array();
        if ($result_data instanceof PDOStatement) {
            $tmp = array();
            while ($row = $result_data->fetch()) {
                //var_dump($row);die;
                if ($index == 0) {
                    $column_names = $this->getRecordFields($row);

                    foreach($column_names as $key => $val) {
                        if ($val == "Date") {
                            $date_field = $key;
                        }
                    }
                    $index++;
                }

                if (isset($row[$date_field])) {
                    $row[$date_field] = date("Y-m-d H:i:s", $row["$date_field"]);
                }

                $tmp[] = $row;
            }

            $rs_data["record_count"] = $page_info["record_count"];
            $rs_data["page_count"]   = $page_info["page_count"];
            $rs_data["column_names"] = $column_names;
            $rs_data["records"]      = $tmp;
            $rs_data['sql']          = $sql;

        }
        return $rs_data;
    }

    public function getRedShiftDataCount($conn, $table, $date_field, $from_date = null, $rows_per_page = null, $search_field = null, $search_string = null,$filterMetadata = array(), $appIds = null) {
        $page_count["record_count"] = 0;
        $page_count["page_count"] = 0;

        $sql = 'select count(1) as cnt from '.$table.' A left join applications B on A.application_id=B.id left join devices C on A.device_id=C.id ';

        if (null != $from_date) {
            $sql .= " where " . $date_field . " >= " . $from_date . " ";
        } else {
            $sql .= " where 1=1 ";
        }
        
        if (!empty($filterMetadata)) {
            $sql .= $this->filterMeta2SQL($filterMetadata);
        }
        
        // if($identifier == "audience_deck")
        // {
        //     if (null != $appIds){
            
        //     $apps = explode(",", $appIds);
                
        //     $clientRepo = $this->container->get('client_repository');
        //     $clientids  = $clientRepo->getClientAppsByIds(($apps));
            
        //     $ids  = explode(",", $clientids);
        //     $ids  = "'" .implode("','", $ids) ."'";
        //     $sql .= " and B.app_id IN "."(".$ids.") ";
        //     }
        // }
        // else
        // {
        //     $appIds = str_replace(',',"','",$appIds);
        //     $sql .= " and B.app_id IN "."('".$appIds."') ";
        // }
        
        $sql .= " and B.app_id IN "."(".$appIds.") ";

        if ((null != $search_field) && (null !== $search_string)) {
            $sql .= " and " . $search_field . " LIKE '%" . $search_string . "%' ";
        }
        //echo "<hr/>".$sql."<hr/>"; die;
        //$sql = "select count(1) as cnt from install_actions A left join applications B on A.application_id=B.id left join devices C on A.device_id=C.id where  C.country_code IN ('ID') and B.app_id IN ('combukalapakandroid','id1003169137');";
        $result_data = $conn->query($sql);

        if ($result_data instanceof PDOStatement) {
            $row = $result_data->fetch();
            //print_r($row);die;
            $page_count["record_count"] = $row["cnt"];

            if($rows_per_page != null) {
                $page_count["page_count"] = ceil($row["cnt"]/$rows_per_page);
            } else {
                $page_count["page_count"] = $row["cnt"];
            }
        }

        return $page_count;
    }

    public function setRecordFields() {
        $this->common_record_field = array(
            "id"               => "Action ID",
            "device_id"        => "HypID",
            "app_id"           => "Application ID",
            "app_name"         => "Application Name",
            "app_version"      => "Application Version",
            "search_string"    => "Search String",
            "total_items"      => "Total Items",
            "quantity"         => "Quantity",
            "transacted_price" => "Price",
            "currency"         => "Currency",
            "installed_time"   => "Install Date",
            "searched_time"    => "Search Date",
            "added_time"       => "Add Date",
            "transacted_time"  => "Transaction Date"
        );
    }

    public function getFieldID($field) {
        $key = array_search($field, $this->common_record_field);

        return $key;
    }

    public function getRecordFields($record) {
        $tmp_field = array();

        if (count($record)) {
            foreach($record as $key => $field) {
                $tmp_field[$key] = $this->common_record_field[$key];
            }
        } else {
            $tmp_field = array(
                "id"             => "Action ID",
                "device_id"      => "HypID",
                "app_id"         => "Application ID",
                "app_name"       => "Application Name",
                "app_version"    => "Application Version",
                "installed_time" => "Install Date"
            );
        }

        return $tmp_field;
    }

    public function createEmptyResultPage($columns) {
        foreach ($columns as $key => $column) {
            $form_data["result_data"][0][$column] = "";
        }
        $form_data["record_count"] = 0;
        $form_data["page_count"]   = 0;
        $form_data["page_num"]     = 0;
        $form_data["row_number"]   = 10;
        $form_data["sort_field"]   = "";
        $form_data["sort_order"]   = "";
        $form_data["search_field"] = "";
        $form_data["search_string"]= "";

        return $form_data;
    }
    
    private function parseColumnName($entityClass,$currentProperty){
        $column = null;
        $reflectionClass = new \ReflectionClass(new $entityClass);
		$properties = $reflectionClass->getProperties();
        $reader = new AnnotationReader();
        foreach ($properties as $property) {
            if($property->name == $currentProperty){
                $column = $reader->getPropertyAnnotation($property,'Doctrine\ORM\Mapping\Column')->name;
		        break;
            }
        }
        return $column;
    }
    
    private function filterMeta2SQL($filterMetadata){
        $sql = '';
        foreach ($filterMetadata as $criteria_key=>$criteria_value) {
            //print_r($criteria_key);die;
            if($criteria_key == 'sql_condition') {
                $sql.= $criteria_value;
            } elseif($criteria_key == 'intent_metadata') {
                $presetFilterController = $this->get('dashboard_filter_controller');
                
                $intentMetadata = $criteria_value;
                $categoryIds = $criteria_value['category_ids'];
                
                $intents = $presetFilterController->getIntents($categoryIds);
                $intentKey = $intentMetadata['intent_key'];
                $sql.= $intents[$intentKey]['metadata'];
            } else {
                $alias ='';
                // ex : \Hyper\Domain\Device\Device.countryCode
                $criteria_key_parse = explode('.',$criteria_key);
                // ex : \Hyper\Domain\Device\Device
                $entityClass = ucfirst( array_shift($criteria_key_parse) );
                $currentProperty = array_shift($criteria_key_parse);
                $field_name = $this->parseColumnName($entityClass,$currentProperty);
                $expression = $criteria_value['expression'];
                
                if ($expression == 'IN'){
                    //print_r($criteria_value);
                    if (!is_array($criteria_value['value'])) {
                        $criteria_value['value'] = array($criteria_value['value']);
                    }
                    $valueToCompare = " ('".implode("','",$criteria_value['value'])."') ";
                    //$valueToCompare = " (".implode(',',$criteria_value['value']).") ";
                }
                if (in_array($expression,array('>','<','=','<>'))){
                    $valueToCompare = $criteria_value['value'];
                }
                if($entityClass == '\Hyper\Domain\Device\Device'){
                    $alias = 'C';
                }
                if($entityClass == '\Hyper\Domain\Application\Application'){
                    $alias = 'B';
                }
                $pointer = (empty($alias))?'':'.';
                $sql .= " and ".$alias.$pointer.$field_name." ".$expression." ".$valueToCompare." ";                
            }
                
            
             
                
        }   
        //echo $sql;die;
        return $sql;
    
    }
    
    public function showHypidData(Request $request) {
        // TODO - fiter app by platform,parse platform as a parameter
        //$request->get('action_type');
        //$deviceId = '5646720db7bfb8.15429629';
        
        $deviceId = $request->get('device_id');
        
        /*
        $authRepo  = $this->container->get('auth.controller');
        $this->client = $authRepo->refreshClient();
        
        $app_id = explode(",", $this->client);
        
        $apps = "'" . implode("','", $app_id) . "'";
        
        $conn = $this->get('doctrine.dbal.pgsql_connection');
        $sql  = $conn->prepare("SELECT DISTINCT client_app FROM client WHERE id IN($apps);");                      
        $sql->execute();
        
        $app_names = array();

        for($i = 0; $row = $sql->fetch(); $i++) 
        {
            $app_names[] = $row['client_app'];
        }
        
        $apps = "";
        for($x = 0; $x < count($app_names); $x++)
        {
            $apps .= $app_names[$x] . ",";
        }
        
        $apps = rtrim($apps, ",");
        $unique = explode(",", $apps);
        $unique = array_values(array_unique($unique));
        $unique = "'" . implode("','", $unique) . "'";
        
        $ac = $this->container->get('action_repository');
        $test = $ac->getResultAndCount(1, 10, $deviceId, $unique);
        
        return new Response(
            json_encode(
                array(
                    'data' => $test,
                    'device' => $deviceId,
                    'apps' => $unique
                )
            )
        );
        
        
        $user_activities = $this->getUserActivities($deviceId);
        
        return new Response(
            json_encode(
                array(
                    'user_activities' => $user_activities,
                    'count' => count($user_activities)
                )
            )
        );
        */
        
        $authController = $this->get('auth.controller');
        $authIdInSession = $authController->getLoggedAuthenticationId();
        /*Added to avoid undefined variables 2015-11-26, Paul */
        $authenticationRepo = $this->container->get('authentication_repository');
        $authentication = $authenticationRepo->findbyCriteria('id', "$authIdInSession");
        // print $authIdInSession ." -- " . $authentication->getId(); die;
        if (!$authentication instanceof Authentication) {
                throw new \Exception('invalid authentication');
        }
        //"app_id1,app_id2,app_id3...""
        
        /* implode then get applications in Client table: Paul */
        
        $clientIdsByAuthentication = $authentication->getClientId();
        $client_ids  = explode(",", $clientIdsByAuthentication);
        $client_ids  = "'" .implode("','", $client_ids) ."'";
        $conn = $this->get('doctrine.dbal.pgsql_connection');                                    
        $sql  = $conn->prepare("SELECT client_app FROM client WHERE id IN ($client_ids);");                      
        $sql->execute();
        $data = array();
        for($x = 0; $row = $sql->fetch(); $x++) 
        {
            $data[] = $row;
        }  
        
        $appIdsByAuthentication = $data[0]['client_app'];
        $deviceRepo = $this->get('device_repository');
        $devicePlatformId = $deviceRepo->getPlatformId($deviceId);
        //print_r($devicePlatformId);die;
        //get device platform
        //get device google ads id or idfv
        $frmRepository = $this->get('frm_repository');
        $appIds = explode(',',$appIdsByAuthentication);
        //$appIds  = "'" .implode("','", $appIds) ."'";
        $frmData = $frmRepository->getDeviceFrmByAppIds($deviceId,$appIds);
        //print_r($frmData);die;
        //get total transaction by app ids
        $totalTransaction = count($frmData);
        //get total transacted amount of device by app ids
        $totalAmount = 0;
        $frmDataByAppIds = array();
        foreach ($frmData as $frm){
            $key = $frm['appId'];
            $frmDataByAppIds[$key][] = $frm;
            $totalAmount += $frm['amount'];
        }
        //get last transaction by app ids
        $lastTransaction = end($frmData);
        $lastTransactionTime = date('Y/m/d H:i:s',$lastTransaction['eventTime']);
        
        //get last actions(event)
        $actionRepo = $this->get('action_repository');
        $result = $actionRepo->getLastActivityTime($deviceId,$appIds);
        if (isset($result['happenedAt'])){
            //print_r($result['happenedAt']);//die;
            $lastActivity = date('Y/m/d H:i:s',$result['happenedAt']);
            //print_r($lastActivity);die;
        } else {
            $lastActivity = null;
        }
        
        $frmScoreByAppIds = array();
        
        foreach ($appIds as $appId){
            if(isset ($frmDataByAppIds[$appId]))
            {
                $frmScoreByAppIds[$appId] = $this->calculateDeviceFrm($appId,$deviceId,$frmDataByAppIds[$appId]);    
            }
        }
        
        $cnt = count($frmScoreByAppIds);
        
        if($lastActivity == null)
        {
            $lastActivity = "00:00:00";
        }
        
        $result = array(
            'device_platform_id' => $devicePlatformId,
            'total_transactions' => $totalTransaction,
            'last_activitiy' =>$lastActivity,
            'total_amount' => $totalAmount,
            'last_transaction_time' => $lastTransactionTime,
            'frm_score' => $frmScoreByAppIds,
            //'banting'   => $frmScoreByAppIds['com.daidigames.banting'],
            'count'     => $cnt
        );
        
        $user = array(
            'device_platform_id' => $devicePlatformId,
            'last_activitiy' =>$lastActivity,
            'last_transaction_time' => $lastTransactionTime,
            'total_amount' => $totalAmount,
            'total_transactions' => $totalTransaction
        );
        
        $user_activities = $this->getUserActivities($deviceId);
        
        return new Response(
            json_encode(
                array(
                    'device_transaction_information' => $result,
                    'user' => $user,
                    'user_activities' => $user_activities
                )
            )
        );
    }
    
    public function calculateDeviceFrm($appId,$deviceId,$frmDataByAppId) {
        $now = time();
        $periods[0] = array (
            'from' => strtotime("-30 days"),
            'to' => $now,
            'frequency' => 0,
            'frequency_score' => 0,
            'recency_point' => 5, 
            'recency' => 0,
            'recency_score' => 0,
            'monetary' => 0,
            'monetary_score' => 0
        );
        $periods[1] = array (
            'from' => strtotime("-90 days"),
            'to' => strtotime("-30 days"),
            'frequency' => 0,
            'frequency_score' => 0,
            'recency_point' => 4, 
            'recency' => 0,
            'recency_score' => 0,
            'monetary' => 0,
            'monetary_score' => 0
        );
        $periods[2] = array (
            'from' => strtotime("-180 days"),
            'to' => strtotime("-90 days"),
            'frequency' => 0,
            'frequency_score' => 0,
            'recency_point' => 3, 
            'recency' => 0,
            'recency_score' => 0,
            'monetary' => 0,
            'monetary_score' => 0
        );
        $periods[3] = array (
            'from' => strtotime("-365 days"),
            'to' => strtotime("-180 days"),
            'frequency' => 0,
            'frequency_score' => 0,
            'recency_point' => 2, 
            'recency' => 0,
            'recency_score' => 0,
            'monetary' => 0,
            'monetary_score' => 0
        );
        $periods[4] = array (
            'from' => 0,
            'to' => strtotime("-365 days"),
            'frequency' => 0,
            'frequency_score' => 0,
            'recency_point' => 1, 
            'recency' => 0,
            'recency_score' => 0,
            'monetary' => 0,
            'monetary_score' => 0
        );
        
        $frmByCategory = array();
        $categoryFrm = array();

        foreach ($frmDataByAppId as $frm) {

            foreach ($periods as $key => $period) {
                if ( $frm['eventTime'] < $period['to'] && $frm['eventTime'] >= $period['from'] ) {
                    $periods[$key]['frequency'] += 1;
                    $periods[$key]['monetary'] += $frm['amount'];
                    $periods[$key]['recency_score'] =$period['recency_point'];
                    
                    $categoryFrmByPeriod = $this->setCategoryFrmByPeriod($key,$period['recency_point'],$frm);
                    $categoryCode = key(($categoryFrmByPeriod));
                   
                    if( !isset($categoryFrm[$categoryCode][$key])) {
                        
                        $categoryFrm[$categoryCode] = array(
                            $key => array(
                                'frequency' => $categoryFrmByPeriod[$categoryCode][$key]['frequency'],
                                'monetary' => $categoryFrmByPeriod[$categoryCode][$key]['monetary'],
                                'recency' => $categoryFrmByPeriod[$categoryCode][$key]['recency']
                            )
                        );

                    } else {
                        $categoryFrm[$categoryCode][$key]['frequency'] += $categoryFrmByPeriod[$categoryCode][$key]['frequency'];
                        $categoryFrm[$categoryCode][$key]['monetary'] += $categoryFrmByPeriod[$categoryCode][$key]['monetary'];
                        $categoryFrm[$categoryCode][$key]['recency'] += $categoryFrmByPeriod[$categoryCode][$key]['recency'];
                    }
                }
            }
   
        }
        //die;
        //echo "<hr/> Before";
        //print_r($periods);
        //echo "<hr/>";
        $appIndustry = $this->getAppIndustry($appId);
        $recencyScore = 0;
        $frequencyScore = 0;
        $monetaryScore = 0;
        foreach ($periods as $k => $period) {
            $recencyScore += $period['recency_score'];
            //echo "period $k f: ".$period['frequency']."<hr/>";
            $periods[$k]['frequency_score'] = $this->getFrequencyScore($period['frequency']);
            $frequencyScore += $periods[$k]['frequency_score'];
            //echo "total f score for all periods: " .$frequencyScore."<hr/>";
            $periods[$k]['monetary_score'] = $this->getMonetaryScore($appIndustry,$period['monetary']);;
            $monetaryScore += $periods[$k]['monetary_score'];
            
        }
        // echo "<hr/> After";
        // print_r($periods);
        //echo "<hr/>";
        //echo "<hr/> ";
        //echo '$recencyScore + $frequencyScore + $monetaryScore'."= $recencyScore + $frequencyScore + $monetaryScore";
        //echo "<hr/>";
        $transactionFrmScore = $recencyScore + $frequencyScore + $monetaryScore;
        
        foreach ($categoryFrm as $categoryCode => $aCategoryFrm) {
            $aCategoryFrm['monetary_score'] = 0;
            $aCategoryFrm['frequency_score'] = 0;
            $aCategoryFrm['recency_score'] = 0;
            foreach ($periods as $key => $period) {
                if(isset ($aCategoryFrm[$key]) ) {
                    $aCategoryFrm[$key]['monetary_score'] = $this->getMonetaryScore($appIndustry,$aCategoryFrm[$key]['monetary']);
                    $aCategoryFrm['monetary_score'] += $aCategoryFrm[$key]['monetary_score'];
                
                    $aCategoryFrm[$key]['frequency_score'] = $this->getFrequencyScore($aCategoryFrm[$key]['frequency']);
                    $aCategoryFrm['frequency_score']  += $aCategoryFrm[$key]['frequency_score'];
                
                    $aCategoryFrm['recency_score'] += $aCategoryFrm[$key]['recency'];
                } else {
                    $aCategoryFrm[$key]['monetary_score'] = 0;
                    $aCategoryFrm[$key]['frequency_score'] = 0;
                    $aCategoryFrm[$key]['recency_score'] = 0;
                }
                
                
            }
            $categoryFrm[$categoryCode]['monetary_score'] =  $aCategoryFrm['monetary_score'];
            $categoryFrm[$categoryCode]['frequency_score'] =  $aCategoryFrm['frequency_score'];
            $categoryFrm[$categoryCode]['recency_score'] =  $aCategoryFrm['recency_score'];
            $categoryFrm[$categoryCode]['frm_score'] = $categoryFrm[$categoryCode]['monetary_score']
                                                        + $categoryFrm[$categoryCode]['frequency_score']
                                                        + $categoryFrm[$categoryCode]['recency_score'];
        }
        
        return array(
            
            'transactionFrmScore' => $transactionFrmScore,
            //'transactionFrmScoreByPeriod' => $periods,
            'categoryFrm' => $categoryFrm
        );
    }
    
    public function getAppIndustry($appId) {
        //1 : ecommerce
        //2 : gaming
        if ($appId =='com.bukalapak.android' || $appId =='id1003169137'){
            return 1;
        } else {
            return 2;
        }
    }
    
    public function getMonetaryScore($industryId,$monetary) {
        $monetaryScore = 0;
        if ($industryId == 1 ) {
            if ($monetary > 300) {
                $monetaryScore = 5;
            } elseif ($monetary <= 300 && $monetary >200) {
                $monetaryScore = 4;
            } elseif ($monetary <= 200 && $monetary >100) {
                $monetaryScore = 3;
            } elseif ($monetary <= 100 && $monetary >50) {
                $monetaryScore = 2;
            } elseif ($monetary <= 50) {
                $monetaryScore = 1;
            }
        } elseif ($industryId == 2 ) {
            if ($monetary > 30) {
                $monetaryScore = 5;
            } elseif ($monetary <= 30 && $monetary >20) {
                $monetaryScore = 4;
            } elseif ($monetary <= 20 && $monetary >10) {
                $monetaryScore = 3;
            } elseif ($monetary <= 10 && $monetary >4) {
                $monetaryScore = 2;
            } elseif ($monetary > 0 && $monetary <= 4) {
                $monetaryScore = 1;
            }
        }
        return $monetaryScore;
    }
    
    public function getFrequencyScore($frequency) {
        
        $frequencyScore = 0;
        if ( $frequency == 1) {
            $frequencyScore = 1;
        } elseif ( $frequency > 1 && $frequency <= 4 ) {
            $frequencyScore = 2;
        } elseif ( $frequency > 4 && $frequency <= 9 ) {
            $frequencyScore = 3;
        } elseif ( $frequency > 9 && $frequency <= 15 ) {
            $frequencyScore = 4;
        } elseif ( $frequency > 15 ) {
            $frequencyScore = 5;
        }
        return $frequencyScore;
        
    }
    
    public function setCategoryFrmByPeriod($period,$recencyPoint,$frm){
        //get items
        $frmItemMetas = unserialize($frm['referenceItemCodes']);
        $monetary = 0;
        $frequency = 0;
        $recency = 0;
        if (!empty($frmItemMetas)){
            
            foreach ($frmItemMetas as $itemCode => $frmItemMeta) {
                
                if(isset($frmItemMeta['category_code'])){
                    $categoryCode = $frmItemMeta['category_code'];
                } else {
                    $categoryCode = Frm::UNCATEGORIZED;
                }
                
                $transactedItemAmount = $frmItemMeta['base_curency_transacted_amount'];

                $monetary += $transactedItemAmount;
                $frequency += 1;
                $recency =$recencyPoint;
                
            }
            
        }
        else {
            $categoryCode = Frm::UNCATEGORIZED;
        }
        
        return array(
            $categoryCode => array(
                $period => array(
                    'monetary' => $monetary,
                    'frequency' => $frequency,
                    'recency' => $recency
                )
            )
        );
    }
    
    public function getUserActivities($device_id = null)
    {
        // $device_id = '56337867d3a121.08849502';
        $authRepo  = $this->container->get('auth.controller');
        $this->client = $authRepo->refreshClient();
        
        $ex = explode(",", $this->client);
        $apps = "'" . implode("','", $ex) . "'";
        
        $conn = $this->get('doctrine.dbal.pgsql_connection');
        /* GET CLIENTS FROM client TABLE */
        $sql  = $conn->prepare("SELECT DISTINCT client_app FROM client WHERE id IN($apps);");                      
        $sql->execute();
        
        $app_names = array();

        for($i = 0; $row = $sql->fetch(); $i++) 
        {
            $app_names[] = $row['client_app'];
        }
        
        /* IMPLODE app_ids */
        $apps = "";
        for($x = 0; $x < count($app_names); $x++)
        {
            $apps .= $app_names[$x] . ",";
        }
        
        $apps = rtrim($apps, ",");
        
        /* UNIQUE app_id to fetch in actions table */
        $unique = explode(",", $apps);
        $unique = array_values(array_unique($unique));
        $unique = "'" . implode("','", $unique) . "'";
        
        $aSql = $conn->prepare("SELECT id, device_id, app_id, behaviour_id, created, happened_at FROM actions WHERE device_id = '$device_id' AND app_id IN($unique)");
        $aSql->execute();
        
        $records = array();
        $now = date('Y-m-d');
        $now = date_create($now);
        
        $behaviors = array(
            1 => 'Install',
            2 => 'Add to Wishlist',
            3 => 'Add to Cart',
            4 => 'Purchase',
            5 => 'Launch',
            6 => 'Share Content',
            7 => 'Tutorial',
            8 => 'Search',
            9 => 'View Content',
            10 => 'User Registered'
        );
        
        $tables = array(
            1 => 'install_actions',
            2 => 'add_to_wishlist_actions',
            3 => 'add_to_cart_actions',
            4 => 'transaction_actions',
            5 => 'launch_actions',
            6 => 'share_content_actions',
            7 => 'Tutorial', // to follow
            8 => 'search_actions',
            9 => 'view_content_actions',
            10 => 'User Registered' //to follow
        );
    
        for($c = 0; $rows = $aSql->fetch(); $c++) 
        {
            if($rows['created'])
            {
                $actions_id = $rows['id'];
                $ago  = date_create(date('Y-m-d H:i:s', $rows['happened_at']));
                $diff = date_diff($ago, $now);
                $dev_id = $rows['device_id'];
                $interval = $diff->format('%R%a days');
                $rows['display_date'] = date('Y-m-d H:i:s', $rows['happened_at']);
                $rows['diff'] = ltrim($interval,"+") . " ago";
                $behave = $behaviors[$rows['behaviour_id']];
                $rows['behaviour'] = $behave;
                
                switch ($rows['behaviour_id']) {
                    case 1:
                        $table = $tables[1];
                        $behaveSql = $conn->prepare("SELECT installed_time, created FROM $table WHERE device_id = '$dev_id' and id = '$actions_id'");
                        $behaveSql->execute();
                        $data = $behaveSql->fetch();
                        $rows['added_time']  = date('Y-m-d H:i:s', $data['installed_time']);
                        $rows['total_value'] = "";
                        // $rows['debug'] = "SELECT installed_time FROM $table WHERE device_id = '$dev_id'";
                        break;
                        
                    case 2:
                        $table = $tables[2];
                        $behaveSql = $conn->prepare("SELECT total_items, added_time FROM $table WHERE device_id = '$dev_id' and id = '$actions_id'");
                        $behaveSql->execute();
                        $data = $behaveSql->fetch();
                        $rows['added_time'] = $data['added_time'];
                        $rows['total_value'] = $data['total_items'];
                        break;
                        
                    case 3:
                        $table = $tables[3];
                        $behaveSql = $conn->prepare("SELECT total_items, added_time FROM $table WHERE device_id = '$dev_id' and id = '$actions_id'");
                        $behaveSql->execute();
                        $data = $behaveSql->fetch();
                        $rows['added_time'] = $data['added_time'];
                        $rows['total_value'] = $data['total_items'];
                        break;
                        
                    case 4:
                        $table = $tables[4];
                        $behaveSql = $conn->prepare("SELECT transacted_price, transacted_time FROM $table WHERE device_id = '$dev_id' and id = '$actions_id'");
                        $behaveSql->execute();
                        $data = $behaveSql->fetch();
                        $rows['added_time'] = date('Y-m-d H:i:s', $data['transacted_time']);
                        $rows['total_value'] = "$ " . $data['transacted_price'];
                        break;
                        
                    case 5:
                        $table = $tables[5];
                        $behaveSql = $conn->prepare("SELECT launch_time FROM $table WHERE device_id = '$dev_id' and id = '$actions_id'");
                        $behaveSql->execute();
                        $data = $behaveSql->fetch();
                        $rows['added_time'] = $data['launch_time'];
                        $rows['total_value'] = "";
                        break;
                        
                    case 6:
                        $table = $tables[6];
                        $behaveSql = $conn->prepare("SELECT shared_app, shared_time FROM $table WHERE device_id = '$dev_id' and id = '$actions_id'");
                        $behaveSql->execute();
                        $data = $behaveSql->fetch();
                        $rows['added_time'] = $data['shared_time'];
                        $rows['total_value'] = $data['shared_app'];
                        break;
                        
                    case 7:
                        
                        break;
                        
                    case 8:
                        $table = $tables[8];
                        $behaveSql = $conn->prepare("SELECT search_string, search_time FROM $table WHERE device_id = '$dev_id' and id = '$actions_id'");
                        $behaveSql->execute();
                        $data = $behaveSql->fetch();
                        $rows['added_time'] = $data['search_time'];
                        $rows['total_value'] = $data['search_string'];
                        break;
                    
                    case 9:
                        $table = $tables[9];
                        $behaveSql = $conn->prepare("SELECT metadata, viewed_time FROM $table WHERE device_id = '$dev_id' and id = '$actions_id'");
                        $behaveSql->execute();
                        $data = $behaveSql->fetch();
                        $rows['added_time'] = $data['viewed_time'];
                        $unserialize = unserialize($data['metadata']);
                        $rows['total_value'] = $unserialize['af_description'];
                        break;
                        
                    case 10:
                        
                        break;
                }
            }
            
            $records[] = $rows;
        }
        
        return $records;
        die;
    }
}
