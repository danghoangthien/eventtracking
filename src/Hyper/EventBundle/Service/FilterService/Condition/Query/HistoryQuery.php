<?php
namespace Hyper\EventBundle\Service\FilterService\Condition\Query;

use Hyper\EventBundle\Service\FilterService\Condition\Query\QueryInterface;
use Hyper\EventBundle\Service\FilterService\Condition\Query\Query;
use Hyper\EventBundle\Service\FilterService\Condition\DataType\HistoryDataType;

class HistoryQuery extends Query implements QueryInterface
{
    protected $appTitleTableTemp;

    public function __construct(
        $connection
        , HistoryDataType $dataType
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
        if ($this->dataType->type() == HistoryDataType::TYPE_INSTALL_TIME_SINCE) {
            $dt = \DateTime::createFromFormat('m/d/Y', $this->dataType->value0());
            $installSinceTime = strtotime($dt->format('Y-m-d 00:00:00'));
            $listWhereQuery['install_time_since'] = "install_time >= '{$installSinceTime}'";
            $this->listJoinQuery['left_join'] = "LEFT JOIN devices ON {$this->appTitleTableTemp}.device_id=devices.id";
        } else if ($this->dataType->type() == HistoryDataType::TYPE_LAST_HAPPENED_AT) {
            $dt = \DateTime::createFromFormat('m/d/Y', $this->dataType->value0());
            $lastHappenedAt = strtotime($dt->format('Y-m-d 00:00:00'));
            $listWhereQuery['last_happened_at'] = "happened_at <= '{$lastHappenedAt}'";
        } else if ($this->dataType->type() == HistoryDataType::TYPE_INSTALL_TIME_DURATION) {
            $dt = \DateTime::createFromFormat('m/d/Y', $this->dataType->value0());
            $installFromTime = strtotime($dt->format('Y-m-d 00:00:00'));
            $listWhereQuery['install_time_from'] = "install_time >= '{$installFromTime}'";
            $dt = \DateTime::createFromFormat('m/d/Y', $this->dataType->value1());
            $installToTime = strtotime($dt->format('Y-m-d 00:00:00'));
            $listWhereQuery['install_time_to'] = "install_time <= '{$installToTime}'";
            $this->listJoinQuery['left_join'] = "LEFT JOIN devices ON {$this->appTitleTableTemp}.device_id=devices.id";
        } else if ($this->dataType->type() == HistoryDataType::TYPE_INSTALL_TIME_LAST) {
            $numberOfDaysAgo = $this->dataType->value0();
            $happenedAtFrom = strtotime('- '.$numberOfDaysAgo.' days');
            $listWhereQuery['install_last_days'] = "install_time >= '{$happenedAtFrom}'";
            $this->listJoinQuery['left_join'] = "LEFT JOIN devices ON {$this->appTitleTableTemp}.device_id=devices.id";
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
        $havingQuery = 'GROUP BY device_id';

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