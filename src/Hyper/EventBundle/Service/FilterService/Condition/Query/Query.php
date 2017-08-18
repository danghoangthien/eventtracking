<?php
namespace Hyper\EventBundle\Service\FilterService\Condition\Query;

use Hyper\EventBundle\Service\FilterService\Condition\Query\QueryInterface;
use Hyper\EventBundle\Service\FilterService\Condition\DataType\DataTypeInterface;

class Query implements QueryInterface
{
    protected $connection;
    protected $dataType;
    protected $tableTempPrefix = 'temp_';
    protected $tableTemp;
    protected $listWhereQuery = [];
    protected $listJoinQuery = [];

    public function __construct(
        $connection
        , DataTypeInterface $dataType
    )
    {
        $this->connection = $connection;
        $this->dataType = $dataType;
        $this->tableTemp = $this->tableTempPrefix . md5($this->dataType->serialize());
    }
    public function getTableTemp()
    {
        return $this->tableTemp;
    }

    public function createTableTemp()
    {
        // create temp table
        $query = "
            CREATE TEMP TABLE IF NOT EXISTS {$this->tableTemp}
            (
                id VARCHAR(255) NOT NULL encode zstd,
                device_id VARCHAR(255) NOT NULL encode raw,
                action_type INT NOT NULL encode zstd,
                happened_at INT encode zstd,
                event_name VARCHAR(50) NOT NULL encode zstd,
                af_content_type VARCHAR(2048) NULL encode zstd,
                app_id VARCHAR(255) NULL encode raw,
                amount_usd FLOAT encode zstd
            )
            diststyle key
            distkey (device_id)
            interleaved sortkey (app_id,device_id,event_name,happened_at)
        ";
        $stmt = $this->connection->prepare($query)->execute();
        \Hyper\EventBundle\Service\FilterService\FilterService::logger("create table temp", $query);
        $query = $this->getInsertQuery();
        // insert data into temp table
        $query = "
            INSERT INTO {$this->tableTemp} $query
        ";
        $stmt = $this->connection->prepare($query)->execute();
        \Hyper\EventBundle\Service\FilterService\FilterService::logger("insert data into table temp",$query);

        return $this->tableTemp;
    }

    public function dropTableTemp()
    {
        // drop temp table
        $query = "DROP TABLE {$this->tableTemp}";
        $stmt = $this->connection->prepare($query)->execute();
    }

    public function getQuery()
    {
        $query = $this->buildQuery();
        \Hyper\EventBundle\Service\FilterService\FilterService::logger('condition query',$query);
        return $query;
    }

    public function getInsertQuery()
    {
        $query = $this->buildInsertQuery();
        return $query;
    }
}