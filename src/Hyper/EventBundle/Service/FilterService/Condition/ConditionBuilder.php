<?php
namespace Hyper\EventBundle\Service\FilterService\Condition;

use Hyper\EventBundle\Service\FilterService\Condition\ConditionBuilderInterface;
use Hyper\EventBundle\Service\FilterService\Condition\DataType\DataTypeInterface;
use Hyper\EventBundle\Service\FilterService\Condition\DataType\HistoryDataType;
use Hyper\EventBundle\Service\FilterService\Condition\DataType\UsageDataType;

class ConditionBuilder implements ConditionBuilderInterface
{
    protected $listDataType = [];
    protected $connection;
    protected $listTableExist = [];
    protected $listTableUnion = [];
    protected $tableTempPrefix = 'temp_';

    public function __construct($connection)
    {
        $this->connection = $connection;
    }

    public function add(DataTypeInterface $dataType)
    {
        $this->listDataType[] = $dataType;

        return $this;
    }

    public function build()
    {
        $conditionTable = '';
        if (empty($this->listDataType)) {
            return $conditionTable;
        }
        $condition = '';
        foreach($this->listDataType as $dataType) {
            if (
                $dataType instanceof HistoryDataType
                || $dataType instanceof UsageDataType
            ) {
                if (!in_array($dataType->appTitleDataType()->getQuery()->getTableTemp(), $this->listTableExist)) {
                    $this->listTableExist[] = $dataType->appTitleDataType()->getQuery()->createTableTemp();
                }
                if (!in_array($dataType->getQuery()->getTableTemp(), $this->listTableExist)) {
                    $tableTemp = $dataType->getQuery()->createTableTemp();
                    $this->listTableExist[] = $tableTemp;
                    $this->listTableUnion[] = $tableTemp;
                }
                $relation = $dataType->relationDataType()->relationMapping();
                $query = $dataType->getQuery()->getQuery();
                if (empty($condition) && !empty($relation)) {
                    $relation = '';
                }
                $_condition = " ($query) ";
                if (empty($condition)) {
                    $condition = $_condition;
                } else {
                    $condition .= implode(
                        ' '
                        , [
                            $relation
                            , $_condition
                        ]
                    );
                    $condition = "(".$condition.")";
                }
            }
        }

        if (!empty($condition)) {
            $conditionTable = $this->tableTempPrefix . time();
            $query = "
                CREATE TEMP TABLE {$conditionTable}
                (
                    device_id VARCHAR(255) NOT NULL encode raw
                )
                diststyle key
                distkey (device_id)
                sortkey(device_id)
            ";
            $stmt = $this->connection->prepare($query);
            $stmt->execute();
            \Hyper\EventBundle\Service\FilterService\FilterService::logger('create table condition', $query);
            $this->listTableExist[] = $conditionTable;
            $query = "INSERT INTO {$conditionTable} SELECT device_id FROM ($condition)";
            $stmt = $this->connection->prepare($query);
            $stmt->execute();
            \Hyper\EventBundle\Service\FilterService\FilterService::logger('insert data into table condition', $query);
        }

        return $conditionTable;
    }

    public function getListTableUnion()
    {
        return $this->listTableUnion;
    }
}