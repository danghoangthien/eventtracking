<?php
namespace Hyper\EventBundle\Service\PresetFilterParser\ConditionQuery\UsageConditionQuery;

use \Symfony\Component\DependencyInjection\ContainerInterface
    , Hyper\EventBundle\Service\PresetFilterParser\ConditionQuery\UsageConditionQuery\UsageConditionQuery
    , Hyper\EventBundle\Service\PresetFilterParser\ConditionQuery\ConditionQueryInterface;

class NotPerformUsageConditionQuery extends UsageConditionQuery implements ConditionQueryInterface
{
    public function __construct(
        ContainerInterface $container
        , $listPlatform
        , $listCountryCode
        , $listAppId
        , $eventName
        , $frequentType
        , $frequentExp
        , $frequentValue0
        , $frequentValue1
        , $happenedAtType
        , $happenedAtValue0
        , $happenedAtValue1
        , $contentType
    ){

        parent::__construct(
            $container
            , $listPlatform
            , $listCountryCode
            , $listAppId
            , $eventName
            , $frequentType
            , $frequentExp
            , $frequentValue0
            , $frequentValue1
            , $happenedAtType
            , $happenedAtValue0
            , $happenedAtValue1
            , $contentType
        );
        $where = $this->buildNotPerformWhereQuery();
        $join =  $this->buildJoinQuery();
        $select = $this->buildNotPerformSelectQuery();
        $having = $this->buildNotPerformHavingQuery();
        $this->query = implode(" ",[
            $select
            , $join
            , $where
            , $having
        ]);
    }

    public function buildNotPerformSelectQuery()
    {
        return "SELECT DISTINCT device_id, listagg(actions.id,'|') AS list_action FROM actions";
    }

    public function buildSelectQuery()
    {
        return "SELECT DISTINCT device_id FROM actions";
    }

    protected function buildNotPerformWhereQuery()
    {
        $listWhereQuery = [];
        if (!empty($this->listPlatform)) {
            $listPlatformStr = implode("','", $this->listPlatform);
            $listWhereQuery['platform'] = "platform IN ('{$listPlatformStr}')";
            $this->listJoinQuery['left_join'] = "LEFT JOIN devices ON actions.device_id=devices.id";
        }
        if (!empty($this->listCountryCode)) {
            $listPlatformStr = implode("','", $this->listCountryCode);
            $listWhereQuery['country_code'] = "country_code IN ('{$listPlatformStr}')";
            $this->listJoinQuery['left_join'] = "LEFT JOIN devices ON actions.device_id=devices.id";
        }
        $listAppIdStr = implode("','", $this->listAppId);
        $listWhereQuery['app_id'] = "app_id IN ('{$listAppIdStr}')";

        $listWhereQueryStr = "AND " . implode(" AND ", array_values($listWhereQuery));
        if (!empty($this->query)) {
            $listWhereQuery['device_id'] = "device_id NOT IN ({$this->query})";
        }

        return "WHERE " . implode(" AND ", array_values($listWhereQuery));
    }
    public function buildJoinQuery()
    {
        $joinQuery = '';
        if (!empty($this->listJoinQuery)) {
            $joinQuery = implode("", array_values($this->listJoinQuery));
        }
        return $joinQuery;
    }

    public function buildNotPerformHavingQuery()
    {
        $havingQuery = 'GROUP BY device_id';

        return $havingQuery;
    }
}