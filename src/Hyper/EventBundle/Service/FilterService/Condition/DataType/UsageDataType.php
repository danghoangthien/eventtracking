<?php
namespace Hyper\EventBundle\Service\FilterService\Condition\DataType;

use Hyper\EventBundle\Service\FilterService\Condition\DataType\DataTypeInterface;
use Hyper\EventBundle\Service\FilterService\Condition\Query\PerformUsageQuery;
use Hyper\EventBundle\Service\FilterService\Condition\Query\NotPerformUsageQuery;


class UsageDataType extends DataType implements DataTypeInterface
{
    const PERFORM_TYPE = [
        self::PERFORM_TYPE_PERFORM
        , self::PERFORM_TYPE_NOT_PERFORM
    ];
    const FREQUENT_TYPE = [
        self::FREQUENT_TYPE_EVENT_COUNT
        , self::FREQUENT_TYPE_REVENUE
    ];
    const FREQUENT_EXP_TYPE = [
        self::FREQUENT_EXP_TYPE_EXACTLY
        , self::FREQUENT_EXP_TYPE_BETWEEN
        , self::FREQUENT_EXP_TYPE_LESS_THAN
        , self::FREQUENT_EXP_TYPE_MORE_THAN
    ];
    const HAPPENED_AT_TYPE = [
        self::HAPPENED_AT_TYPE_DURATION
        , self::HAPPENED_AT_TYPE_LAST
        , self::HAPPENED_AT_TYPE_LIFETIME
    ];

    const PERFORM_TYPE_PERFORM = 'perform';
    const PERFORM_TYPE_NOT_PERFORM = 'not_perform';

    const HAPPENED_AT_TYPE_DURATION = 'duration';
    const HAPPENED_AT_TYPE_LAST = 'last';
    const HAPPENED_AT_TYPE_LIFETIME = 'lifetime';

    const FREQUENT_TYPE_EVENT_COUNT = 'event_count';
    const FREQUENT_TYPE_REVENUE = 'revenue';

    const FREQUENT_EXP_TYPE_EXACTLY = '=';
    const FREQUENT_EXP_TYPE_BETWEEN = '+';
    const FREQUENT_EXP_TYPE_LESS_THAN = '<';
    const FREQUENT_EXP_TYPE_MORE_THAN = '>';

    private $connection;
    private $appTitleDataType;
    private $relationDataType;
    private $perform;
    private $eventName;
    private $contentType;
    private $frequentType;
    private $frequentExp;
    private $frequentValue0;
    private $frequentValue1;
    private $happenedAtType;
    private $happenedAtValue0;
    private $happenedAtValue1;
    private $query;

    public function __construct(
        $connection
        , AppTitleDataType $appTitleDataType
        , RelationDataType $relationDataType
        , $perform
        , $eventName
        , $contentType
        , $frequentType
        , $frequentExp
        , $frequentValue0
        , $frequentValue1
        , $happenedAtType
        , $happenedAtValue0
        , $happenedAtValue1
    ) {
        $this->connection = $connection;
        $this->appTitleDataType = $appTitleDataType;
        $this->relationDataType = $relationDataType;
        $this->perform = $perform;
        $this->eventName = $eventName;
        $this->contentType = $contentType;
        $this->frequentType = $frequentType;
        $this->frequentExp = $frequentExp;
        $this->frequentValue0 = $frequentValue0;
        $this->frequentValue1 = $frequentValue1;
        $this->happenedAtType = $happenedAtType;
        $this->happenedAtValue0 = $happenedAtValue0;
        $this->happenedAtValue1 = $happenedAtValue1;
        $this->assertData();
        if ($this->perform == self::PERFORM_TYPE_PERFORM) {
            $this->query = new PerformUsageQuery(
                $connection
                , $this
            );
        } else if ($this->perform == self::PERFORM_TYPE_NOT_PERFORM) {
            $this->query = new NotPerformUsageQuery(
                $connection
                , $this
            );
        }

    }

    public function appTitleDataType()
    {
        return $this->appTitleDataType;
    }

    public function relationDataType()
    {
        return $this->relationDataType;
    }

    public function perform()
    {
        return $this->perform;
    }

    public function eventName()
    {
        return $this->eventName;
    }

    public function contentType()
    {
        return $this->contentType;
    }

    public function frequentType()
    {
        return $this->frequentType;
    }

    public function frequentExp()
    {
        return $this->frequentExp;
    }

    public function frequentValue0()
    {
        return $this->frequentValue0;
    }

    public function frequentValue1()
    {
        return $this->frequentValue1;
    }

    public function happenedAtType()
    {
        return $this->happenedAtType;
    }

    public function happenedAtValue0()
    {
        return $this->happenedAtValue0;
    }

    public function happenedAtValue1()
    {
        return $this->happenedAtValue1;
    }

    public function assertData()
    {
        if (empty($this->perform)) {
            throw new \Exception('The perform in usage must be value.');
        }
        if (
            !in_array(strtolower($this->perform), self::PERFORM_TYPE)
        ) {
            throw new \Exception('The perform in usage only support '. implode(",", self::PERFORM_TYPE) . '.');
        }
        // if (empty($this->eventName)) {
        //     throw new \Exception('The eventName in usage must be value.');
        // }
        if ($this->perform == self::PERFORM_TYPE_PERFORM && empty($this->frequentType)) {
            throw new \Exception('The frequentType in usage must be value.');
        }
        if (
            $this->perform == self::PERFORM_TYPE_PERFORM
             && !in_array(strtolower($this->frequentType), self::FREQUENT_TYPE)
        ) {
            throw new \Exception('The frequentType in usage only support '. implode(",", self::FREQUENT_TYPE) . '.');
        }
        if (
            $this->perform == self::PERFORM_TYPE_PERFORM
            && $this->frequentValue0 != ''
            && !is_numeric($this->frequentValue0)
        ) {
            throw new \Exception('The frequentValue0 in usage must be numeric.');
        }
        if (
            $this->perform == self::PERFORM_TYPE_PERFORM
            && $this->frequentValue1 != ''
            && !is_numeric($this->frequentValue1)
        ) {
            throw new \Exception('The frequentValue1 in usage must be numeric.');
        }
        if (
            !empty($this->happenedAtType) && !in_array(strtolower($this->happenedAtType), self::HAPPENED_AT_TYPE)
        ) {
            throw new \Exception('The happenedAtType in usage only support '. implode(",", self::HAPPENED_AT_TYPE) . '.');
        }
    }

    public function serialize()
    {
        return serialize([
            $this->appTitleDataType->appTitleId()
            , $this->relationDataType->relation()
            , $this->perform
            , $this->eventName
            , $this->contentType
            , $this->frequentType
            , $this->frequentValue0
            , $this->frequentValue1
            , $this->happenedAtType
            , $this->happenedAtValue0
            , $this->happenedAtValue1
        ]);
    }

    public function getQuery()
    {
        return $this->query;
    }
}