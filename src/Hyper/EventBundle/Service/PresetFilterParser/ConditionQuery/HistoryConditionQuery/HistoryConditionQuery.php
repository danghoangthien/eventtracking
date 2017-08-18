<?php
namespace Hyper\EventBundle\Service\PresetFilterParser\ConditionQuery\HistoryConditionQuery;

use \Symfony\Component\DependencyInjection\ContainerInterface
    , Hyper\EventBundle\Service\PresetFilterParser\ConditionQuery\ConditionQueryInterface;

class HistoryConditionQuery implements ConditionQueryInterface
{
    protected $query;
    protected $listWhereQuery = [];
    protected $listJoinQuery = [];
    protected $listPlatform;
    protected $listCountryCode;
    protected $listAppId;
    protected $installSinceTime;
    protected $lastHappenedAt;
    protected $installFromTime;
    protected $instalTotime;
    protected $installLastTime;

    public function __construct(
        ContainerInterface $container
        , $listPlatform
        , $listCountryCode
        , $listAppId
        , $installSinceTime
        , $lastHappenedAt
        , $installFromTime
        , $instalToTime
        , $installLastDays
    ){

        $this->listPlatform = $listPlatform;
        $this->listCountryCode = $listCountryCode;
        $this->listAppId = $listAppId;
        $this->installSinceTime = $installSinceTime;
        $this->lastHappenedAt = $lastHappenedAt;
        $this->installFromTime = $installFromTime;
        $this->instalToTime = $instalToTime;
        $this->installLastDays = $installLastDays;
        $this->buildQuery();
    }

    protected function buildQuery()
    {
        $where = $this->buildWhereQuery();
        $join =  $this->buildJoinQuery();
        $select = $this->buildSelectQuery();
        $having = $this->buildHavingQuery();
        $this->query = implode(" ",[
            $select
            , $join
            , $where
            , $having
        ]);
    }


    public function getQuery()
    {
        return $this->query;
    }

    public function buildSelectQuery()
    {
        return "SELECT DISTINCT device_id, listagg(actions.id,'|') AS list_action FROM actions";
    }
    public function buildWhereQuery()
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
        if (!empty($this->installSinceTime)) {
            $dt = \DateTime::createFromFormat('m/d/Y', $this->installSinceTime);
            $installSinceTime = strtotime($dt->format('Y-m-d 00:00:00'));
            $listWhereQuery['install_time_since'] = "install_time >= '{$installSinceTime}'";
            $this->listJoinQuery['left_join'] = "LEFT JOIN devices ON actions.device_id=devices.id";
        }
        if (!empty($this->lastHappenedAt)) {
            $dt = \DateTime::createFromFormat('m/d/Y', $this->lastHappenedAt);
            $lastHappenedAt = strtotime($dt->format('Y-m-d 00:00:00'));
            $listWhereQuery['last_happened_at'] = "happened_at <= '{$lastHappenedAt}'";
        }
        if (!empty($this->installFromTime) && !empty($this->installToTime)) {
            $dt = \DateTime::createFromFormat('m/d/Y', $this->installFromTime);
            $installFromTime = strtotime($dt->format('Y-m-d 00:00:00'));
            $listWhereQuery['install_time_from'] = "install_time >= '{$installFromTime}'";
            $dt = \DateTime::createFromFormat('m/d/Y', $this->installToTime);
            $installToTime = strtotime($dt->format('Y-m-d 00:00:00'));
            $listWhereQuery['install_time_to'] = "install_time <= '{$installToTime}'";
            $this->listJoinQuery['left_join'] = "LEFT JOIN devices ON actions.device_id=devices.id";
        }
        if (!empty($this->installLastDays)) {
            $numberOfDaysAgo = $this->installLastDays;
            $happenedAtFrom = strtotime('- '.$numberOfDaysAgo.' days');
            $listWhereQuery['install_last_days'] = "install_time >= '{$happenedAtFrom}'";
            $this->listJoinQuery['left_join'] = "LEFT JOIN devices ON actions.device_id=devices.id";
        }
        $this->listWhereQuery = $listWhereQuery;

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

    public function buildHavingQuery()
    {
        $havingQuery = 'GROUP BY device_id';

        return $havingQuery;
    }
}