<?php
namespace Hyper\EventBundle\Service\FilterService\Condition\Query;

use Hyper\EventBundle\Service\FilterService\Condition\Query\UsageQuery;
use Hyper\EventBundle\Service\FilterService\Condition\DataType\UsageDataType;

class NotPerformUsageQuery extends UsageQuery
{

    public function __construct(
        $connection
        , UsageDataType $dataType
    )
    {
       parent::__construct($connection, $dataType);
    }

    protected function buildInsertQuery()
    {
        $where = $this->buildNotPerformWhereQuery();
        $join =  $this->buildJoinQuery();
        $select = $this->buildSelectQuery();
        $query = implode(" ",[
            $select
            , $join
            , $where
        ]);

        return $query;
    }

    protected function buildNotPerformWhereQuery()
    {
        $listWhereQuery = [];
        $performUsageQuery = $this->buildPerformUsageQuery();
        $listWhereQuery['device_id'] = "device_id NOT IN ({$performUsageQuery})";

        return "WHERE " . implode(" AND ", array_values($listWhereQuery));
    }

    protected function buildPerformUsageQuery()
    {
        $where = $this->buildWhereQuery();
        $join =  $this->buildJoinQuery();
        $select = "SELECT DISTINCT device_id FROM {$this->appTitleTableTemp}";
        $having = $this->buildHavingQuery();
        $query = implode(" ",[
            $select
            , $join
            , $where
            , $having
        ]);

        return $query;
    }
}