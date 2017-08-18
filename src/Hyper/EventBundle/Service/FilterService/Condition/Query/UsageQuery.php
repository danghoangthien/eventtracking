<?php
namespace Hyper\EventBundle\Service\FilterService\Condition\Query;

use Hyper\EventBundle\Service\FilterService\Condition\Query\QueryInterface;
use Hyper\EventBundle\Service\FilterService\Condition\Query\Query;
use Hyper\EventBundle\Service\FilterService\Condition\DataType\UsageDataType;

class UsageQuery extends Query implements QueryInterface
{
    protected $appTitleTableTemp;

    public function __construct(
        $connection
        , UsageDataType $dataType
    )
    {
       parent::__construct($connection, $dataType);
       $this->appTitleTableTemp = $dataType->appTitleDataType()->getQuery()->getTableTemp();
    }

    protected function buildQuery()
    {
        $tableTemp = $this->getTableTemp();
        $select = "
            SELECT DISTINCT device_id
            FROM $tableTemp
        ";
        $having = $this->buildHavingQuery();
        $query = implode(" ",[
            $select
            , $having
        ]);

        return $query;
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

    protected function buildSelectQuery()
    {
        return "SELECT {$this->appTitleTableTemp}.* FROM {$this->appTitleTableTemp}";
    }

    protected function buildWhereQuery()
    {
        $listWhereQuery = [];
        $eventName = $this->dataType->eventName();
        if (!empty($eventName)) {
            $listWhereQuery['event_name'] = "event_name = '$eventName'";
        } else {
            $listWhereQuery['action_type'] = "action_type = 2";
        }
        $contentType = $this->dataType->contentType();
        if (!empty($contentType)) {
            $listWhereQuery['content_type'] = "af_content_type = '{$contentType}'";
        }
        $happenedAtType = $this->dataType->happenedAtType();
        if (!empty($happenedAtType)) {
            if ($happenedAtType == UsageDataType::HAPPENED_AT_TYPE_DURATION) {
                $happenedAtValue0 = $this->dataType->happenedAtValue0();
                $happenedAtValue1 = $this->dataType->happenedAtValue1();
                if (!empty($happenedAtValue0) && !empty($happenedAtValue1)) {
                    $dt = \DateTime::createFromFormat('m/d/Y', $happenedAtValue0);
                    $happenedAtFrom = strtotime($dt->format('Y-m-d 00:00:00'));
                    $listWhereQuery['happened_at_from'] = "happened_at >= '{$happenedAtFrom}'";
                    $dt = \DateTime::createFromFormat('m/d/Y', $happenedAtValue1);
                    $happenedAtTo = strtotime($dt->format('Y-m-d 00:00:00'));
                    $listWhereQuery['happened_at_to'] = "happened_at <= '{$happenedAtTo}'";
                }
            } elseif ($happenedAtType == UsageDataType::HAPPENED_AT_TYPE_LAST) {
                $numberOfDaysAgo = $this->dataType->happenedAtValue0();
                $happenedAtFrom = strtotime('- '.$numberOfDaysAgo.' days');
                $listWhereQuery['happened_at_from'] = "happened_at >= '{$happenedAtFrom}'";
                $happenedAtTo = strtotime("now");
                $listWhereQuery['happened_at_to'] = "happened_at <= '{$happenedAtTo}'";
            }  elseif ($happenedAtType == UsageDataType::HAPPENED_AT_TYPE_LIFETIME) {
                // do nothing
            }
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

    protected function buildHavingQuery()
    {
        $havingQuery = '';
        $frequentType = $this->dataType->frequentType();
        $frequentExp = $this->dataType->frequentExp();
        $frequentValue0 = $this->dataType->frequentValue0();
        $frequentValue1 = $this->dataType->frequentValue1();
        if ($frequentType == UsageDataType::FREQUENT_TYPE_EVENT_COUNT) {
            if (!empty($frequentExp) && $frequentExp == UsageDataType::FREQUENT_EXP_TYPE_BETWEEN) {
                $havingQuery = "GROUP BY device_id HAVING COUNT(device_id) BETWEEN {$frequentValue0} AND {$frequentValue1}";
            } elseif (!empty($frequentExp)) {
                $havingQuery = "GROUP BY device_id HAVING COUNT(device_id) {$frequentExp} {$frequentValue0}";
            }
        } elseif ($frequentType == UsageDataType::FREQUENT_TYPE_REVENUE) {
            if (!empty($frequentExp) && $frequentExp == UsageDataType::FREQUENT_EXP_TYPE_BETWEEN) {
                $havingQuery = "GROUP BY device_id HAVING SUM(amount_usd) BETWEEN {$frequentValue0} AND {$frequentValue1}";
            } elseif (!empty($frequentExp)) {
                $havingQuery = "GROUP BY device_id HAVING SUM(amount_usd) {$frequentExp} {$frequentValue0}";
            }
        } else {
            $havingQuery = "GROUP BY device_id";
        }

        return $havingQuery;
    }

    public function dropTableTemp()
    {
        // drop temp table
        $query = "DROP TABLE {$this->tableTemp}";
        $stmt = $this->connection->prepare($query)->execute();
        // drop temp table
        $query = "DROP TABLE {$this->appTitleTableTemp}";
        $stmt = $this->connection->prepare($query)->execute();
    }
}