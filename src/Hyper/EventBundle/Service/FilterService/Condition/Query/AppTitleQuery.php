<?php
namespace Hyper\EventBundle\Service\FilterService\Condition\Query;

use Hyper\EventBundle\Service\FilterService\Condition\Query\QueryInterface;
use Hyper\EventBundle\Service\FilterService\Condition\Query\Query;
use Hyper\EventBundle\Service\FilterService\Condition\DataType\AppTitleDataType;

class AppTitleQuery extends Query implements QueryInterface
{

    public function __construct(
        $connection
        , AppTitleDataType $dataType
    )
    {
       parent::__construct($connection, $dataType);
    }


    protected function buildQuery()
    {

    }

    protected function buildInsertQuery()
    {
        $where = $this->buildWhereQuery();
        $join =  $this->buildJoinQuery();
        $select = $this->buildSelectQuery();
        $query = implode(" ",[
            $select
            , $join
            , $where
        ]);

        return $query;
    }

    private function buildSelectQuery()
    {
        $query = "
            SELECT
                actions.id
                , actions.device_id
                , actions.action_type
        		, actions.happened_at
        		, actions.event_name
        		, actions.af_content_type
        		, actions.app_id
        		, actions.amount_usd
    		FROM actions
        ";
        return $query;
    }

    private function buildWhereQuery()
    {
        $listWhereQuery = [];
        $listAppIdStr = implode("','", $this->dataType->appId());
        $listWhereQuery['app_id'] = "app_id IN ('{$listAppIdStr}')";
        if (!empty($this->dataType->platform())) {
            $listPlatformStr = implode("','", $this->dataType->platform());
            $listWhereQuery['platform'] = "platform IN ('{$listPlatformStr}')";
            $this->listJoinQuery['left_join'] = "LEFT JOIN devices ON actions.device_id=devices.id";
        }
        if (!empty($this->dataType->countryCode())) {
            $listCountryCodeStr = implode("','", $this->dataType->countryCode());
            $listWhereQuery['country_code'] = "country_code IN ('{$listCountryCodeStr}')";
            $this->listJoinQuery['left_join'] = "LEFT JOIN devices ON actions.device_id=devices.id";
        }
        $this->listWhereQuery = $listWhereQuery;

        return "WHERE " . implode(" AND ", array_values($listWhereQuery));
    }

    protected function buildJoinQuery()
    {
        $joinQuery = '';
        if (!empty($this->listJoinQuery)) {
            $joinQuery = implode("", array_values($this->listJoinQuery));
        }
        return $joinQuery;
    }
}