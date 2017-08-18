<?php
namespace Hyper\EventBundle\Service\FilterService\Condition\DataType;

use Hyper\EventBundle\Service\FilterService\Condition\DataType\DataTypeInterface;
use Hyper\EventBundle\Service\FilterService\Condition\DataType\DataType;
use Hyper\EventBundle\Service\FilterService\Condition\DataType\AppTitleDataType;
use Hyper\EventBundle\Service\FilterService\Condition\Query\HistoryQuery;

class HistoryDataType extends DataType implements DataTypeInterface
{
    const TYPE_INSTALL_TIME_SINCE  = 'install_time_since';
    const TYPE_LAST_HAPPENED_AT  = 'last_happened_at';
    const TYPE_INSTALL_TIME_DURATION  = 'install_time_duration';
    const TYPE_INSTALL_TIME_LAST  = 'install_time_last';
    const TYPE_SUPPORT  = [
        self::TYPE_INSTALL_TIME_SINCE
        , self::TYPE_LAST_HAPPENED_AT
        , self::TYPE_INSTALL_TIME_DURATION
        , self::TYPE_INSTALL_TIME_LAST
    ];
    private $connection;
    private $appTitleDataType;
    private $relationDataType;
    private $type;
    private $value0;
    private $value1;
    private $query;

    public function __construct(
        $connection
        , AppTitleDataType $appTitleDataType
        , RelationDataType $relationDataType
        , $type
        , $value0
        , $value1
    ) {
        $this->connection = $connection;
        $this->appTitleDataType = $appTitleDataType;
        $this->relationDataType = $relationDataType;
        $this->type = $type;
        $this->value0 = $value0;
        $this->value1 = $value1;
        $this->assertData();
        $this->query = new HistoryQuery(
                $connection
                , $this
        );
    }

    public function appTitleDataType()
    {
        return $this->appTitleDataType;
    }

    public function relationDataType()
    {
        return $this->relationDataType;
    }

    public function type()
    {
        return $this->type;
    }

    public function value0()
    {
        return $this->value0;
    }

    public function value1()
    {
        return $this->value1;
    }

    public function assertData()
    {
        if (empty($this->type)) {
            throw new \Exception('The type in history must be value.');
        }
        if (
            !in_array(strtolower($this->type), self::TYPE_SUPPORT)
        ) {
            throw new \Exception('The type in history only support '. implode(",", self::TYPE_SUPPORT) . '.');
        }
        if (
            $this->type == self::TYPE_INSTALL_TIME_DURATION
        ) {
            if (
                empty($this->value0) || empty($this->value1)
            ) {
                throw new \Exception('The value from and to in history must be value.');
            }
        } else {
            if (empty($this->value0)) {
                throw new \Exception('The value from in history must be value.');
            }
        }
    }

    public function serialize()
    {
        return serialize([
            $this->appTitleDataType->appTitleId()
            , $this->relationDataType->relation()
            , $this->type
            , $this->value0
            , $this->value1
        ]);
    }

    public function getQuery()
    {
        return $this->query;
    }
}