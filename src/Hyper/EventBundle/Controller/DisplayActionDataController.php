<?php
namespace Hyper\EventBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Hyper\EventBundle\Service\EventProcess;


class DisplayActionDataController extends Controller
{

    public $action_types = array();
    public $transaction_days = array();
    public $common_record_field = array();

    /**
    * @param ContainerInterface $container
    */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->setActionTypes();
        $this->setTransactionDays();
        $this->setRecordFields();

    }
    
    public function indexAction(Request $request)
    {
        //$result_data = array();
        //$offset = 0;

        $action_type = $request->get('action_type');
        $last_action_type = $request->get('last_action_type');
        $last_sort_field = $request->get('last_sort_field');
        $transaction_day = $request->get('transaction_day');
        $row_number = $request->get('row_number');
        $page_num = $request->get('page_num');
        $sort_field = $request->get('sort_field');
        $sort_order = $request->get('sort_order');
        $search_field = $request->get('search_field');
        $search_string = $request->get('search_string');
        $export_data = $request->get('export_data');

        // If new action type is selected, reset page number, and criteria for sort and search
        if ((null !== $last_action_type) && ($action_type != $last_action_type)) {
            $page_num = null;
            $sort_field = null;
            $sort_order = null;
            $search_field = null;
            $search_string = null;
        }

        if (null == $page_num) {
            $offset = 0;
        } else {
            $offset = ($row_number * ($page_num - 1));
        }

        $action_types = $this->getActionTypes();
        if ((null != $action_type) && (isset($action_types[$action_type]))) {
            $action_types[$action_type]["is_selected"] = 1;
        }

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

        $form_data["action_types"] = $action_types;
        $form_data["transaction_days"] = $transaction_days;

        $table_field = null;
        $table_order = null;
        $table_search_field = null;
        $table_search_string = null;

        if (null != $action_type) {
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

            $result_data = $this->getRedShiftData($action_type, $from_date, $row_number, $offset, $table_field, $table_order, $table_search_field, $table_search_string);

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

        $form_data['last_action_type'] = $action_type;
        $form_data['last_sort_field']  = $sort_field;

        foreach($form_data["column_names"] as $key => $columns) {
            if (false === strpos($columns, "Date")) {
                $search_columns[] = $columns;
            }
        }

        $form_data['search_fields'] = $search_columns;

        // add by Thien to return data only without render to the template
        if($export_data == true){
            return $form_data;
        }

        return $this->render('display_action_data.html.twig', $form_data);
    }

    public function setActionTypes() {

        $this->action_type = array(
            "1" => array("name" => "Install", "is_selected" => 0),
            "2" => array("name" => "Search", "is_selected" => 0),
            "3" => array("name" => "Add to Wishlist", "is_selected" => 0),
            "4" => array("name" => "Add to Cart", "is_selected" => 0),
            "5" => array("name" => "Purchase", "is_selected" => 0)
        );
    }

    public function getActionTypes() {
        return $this->action_type;
    }

    public function setTransactionDays() {
        $this->transaction_days = array(
            "1" => array("name" => "24 hours", "is_selected" => 0),
            "2" => array("name" => "3 days", "is_selected" => 0),
            "3" => array("name" => "1 week", "is_selected" => 0)
        );
    }

    public function getTransactionDays() {
        return $this->transaction_days;
    }

    public function getRedShiftData($action_type, $from_date = null, $limit = null, $offset = null, $sort_field = null, $sort_order = null, $search_field = null, $search_string = null) {
        $index = 0;
        $date_field = "";
        $column_names = array();

        $conn = $this->get('doctrine.dbal.pgsql_connection');

        if ("id" == $search_field) {
            $search_field = "A.id";
        }

        switch ($action_type) {
            case 1:
                $table = "install_actions";
                $date_field = "installed_time";
                //$columns = " A.id as hypid, A.device_id as device_id, B.app_id as application_id, B.app_name as application_name, B.app_version as application_version, A.installed_time as install_date ";
                $columns = " A.id, A.device_id, B.app_id, B.app_name, B.app_version, A.installed_time ";
                break;
            case 2:
                $table = "search_actions";
                $date_field = "searched_time";
                //$columns = " A.id as hypid, A.device_id as device_id, B.app_id as application_id, B.app_name as application_name, B.app_version as application_version, A.search_string as search_string, A.searched_time as searched_date ";
                $columns = " A.id, A.device_id, B.app_id, B.app_name, B.app_version, A.search_string, A.searched_time ";
                break;
            case 3:
                $table = "add_to_wishlist_actions";
                $date_field = "added_time";
                //$columns = " A.id as hypid, A.device_id as device_id, B.app_id as application_id, B.app_name as application_name, B.app_version as application_version, A.total_items as total_items, A.quantity as quantity, A.added_time as add_date ";
                $columns = " A.id, A.device_id, B.app_id, B.app_name, B.app_version, A.total_items, A.quantity, A.added_time ";
                break;
            case 4:
                $table = "add_to_cart_actions";
                $date_field = "added_time";
                //$columns = " A.id as hypid, A.device_id as device_id, B.app_id as application_id, B.app_name as application_name, B.app_version as application_version, A.total_items as total_items, A.added_time as add_date";
                $columns = " A.id, A.device_id, B.app_id, B.app_name, B.app_version, A.total_items, A.added_time ";
                break;
            case 5:
                $table = "transaction_actions";
                $date_field = "transacted_time";
                //$columns = " A.id as hypid, A.device_id as device_id, B.app_id as application_id, B.app_name as application_name, B.app_version as application_version, A.transacted_price as transact_price, A.quantity as quantity, A.currency as currency, A.transacted_time as transaction_date";
                $columns = " A.id, A.device_id, B.app_id, B.app_name, B.app_version, A.transacted_price, A.quantity, A.currency, A.transacted_time ";
                break;
        }

        $page_info = $this->getRedShiftDataCount($conn, $table, $date_field, $from_date, $limit, $search_field, $search_string);

        // Ensures that is the user switch from a high "Date Within" to a low value it will not cause problems
        if ($offset >= $page_info["record_count"]) {
            $offset = 0;
        }

        $sql = 'select ' . $columns . ' from '.$table.' A left join applications B on A.application_id=B.id';
        if (null != $from_date) {
            $sql .= " where " . $date_field . " >= " . $from_date . " ";
        }

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

        $result_data = $conn->query($sql);

        $column_names = $this->getRecordFields(array());
        if (count($result_data)) {
            $tmp = array();
            while ($row = $result_data->fetch()) {
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

        }

        return $rs_data;
    }

    public function getRedShiftDataCount($conn, $table, $date_field, $from_date = null, $rows_per_page = null, $search_field = null, $search_string = null) {
        $page_count["record_count"] = 0;
        $page_count["page_count"] = 0;

        $sql = 'select count(1) as cnt from '.$table.' A left join applications B on A.application_id=B.id';

        if (null != $from_date) {
            $sql .= " where " . $date_field . " >= " . $from_date . " ";
        }

        if ((null != $search_field) && (null !== $search_string)) {
            $sql .= " and " . $search_field . " LIKE '%" . $search_string . "%' ";
        }

        $rs_data = $conn->query($sql);

        if (count($rs_data)) {

            $row = $rs_data->fetch();

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
            "id"               => "HypID",
            "device_id"        => "Device ID",
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
                "id"             => "HypID",
                "device_id"      => "Device ID",
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
}
