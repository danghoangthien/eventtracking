<?php
namespace Hyper\EventBundle\Service\PresetFilterParser\ConditionQuery\UsageConditionQuery;

use \Symfony\Component\DependencyInjection\ContainerInterface;

class UsageConditionQuery
{
    const EXP_BETWEEN = '+';
    protected $container;
    protected $query;
    protected $listWhereQuery = [];
    protected $listJoinQuery = [];
    protected $listPlatform;
    protected $listCountryCode;
    protected $listAppId;
    protected $eventName;
    protected $frequentType;
    protected $frequentExp;
    protected $frequentValue;
    protected $happenedAtType;
    protected $happenedAtValue0;
    protected $happenedAtValue1;
    //protected $happenedAtFrom;
    //protected $happenedAtTo;
    protected $contentType;

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
        $this->container = $container;
        $this->listPlatform = $listPlatform;
        $this->listCountryCode = $listCountryCode;
        $this->listAppId = $listAppId;
        $this->eventName = $eventName;
        $this->frequentType = $frequentType;
        $this->frequentExp = $frequentExp;
        $this->frequentValue0 = $frequentValue0;
        $this->frequentValue1 = $frequentValue1;
        $this->happenedAtType = $happenedAtType;
        $this->happenedAtValue0 = $happenedAtValue0;
        $this->happenedAtValue1 = $happenedAtValue1;
        $this->contentType = $contentType;
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
        if (!empty($this->happenedAtType)) {
            if ($this->happenedAtType == 'duration') {
                if (!empty($this->happenedAtValue0) && !empty($this->happenedAtValue1)) {
                $dt = \DateTime::createFromFormat('m/d/Y', $this->happenedAtValue0);
                $happenedAtFrom = strtotime($dt->format('Y-m-d 00:00:00'));
                $listWhereQuery['happened_at_from'] = "happened_at >= '{$happenedAtFrom}'";
                $dt = \DateTime::createFromFormat('m/d/Y', $this->happenedAtValue1);
                $happenedAtTo = strtotime($dt->format('Y-m-d 00:00:00'));
                $listWhereQuery['happened_at_to'] = "happened_at <= '{$happenedAtTo}'";
            }

            } elseif ($this->happenedAtType == 'last') {
                $numberOfDaysAgo = $this->happenedAtValue0;
                $happenedAtFrom = strtotime('- '.$numberOfDaysAgo.' days');
                $listWhereQuery['happened_at_from'] = "happened_at >= '{$happenedAtFrom}'";
                $happenedAtTo = strtotime("now");
                $listWhereQuery['happened_at_to'] = "happened_at <= '{$happenedAtTo}'";
            }  elseif ($this->happenedAtType == 'lifetime') {
                // do nothing
            }
        }
        /*
        if (!empty($this->happenedAtFrom) && !empty($this->happenedAtTo)) {
            $dt = \DateTime::createFromFormat('m/d/Y', $this->happenedAtFrom);
            $happenedAtFrom = strtotime($dt->format('Y-m-d 00:00:00'));
            $listWhereQuery['happened_at_from'] = "happened_at >= '{$happenedAtFrom}'";
            $dt = \DateTime::createFromFormat('m/d/Y', $this->happenedAtTo);
            $happenedAtTo = strtotime($dt->format('Y-m-d 00:00:00'));
            $listWhereQuery['happened_at_to'] = "happened_at <= '{$happenedAtTo}'";
        }
        */
        $listWhereQuery['event_name'] = "event_name = '{$this->eventName}'";
        if (!empty($this->contentType)) {
            $listWhereQuery['content_type'] = "af_content_type = '{$this->contentType}'";
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
        $havingQuery = '';
        if ($this->frequentType == 'event_count') {
            if (!empty($this->frequentExp) && $this->frequentExp == self::EXP_BETWEEN) {
                $havingQuery = "GROUP BY device_id HAVING COUNT(device_id) BETWEEN {$this->frequentValue0} AND {$this->frequentValue1}";
            } elseif (!empty($this->frequentExp)) {
                $havingQuery = "GROUP BY device_id HAVING COUNT(device_id) {$this->frequentExp} {$this->frequentValue0}";
            }
        } elseif ($this->frequentType == 'amount') {
            if (!empty($this->frequentExp) && $this->frequentExp == self::EXP_BETWEEN) {
                $havingQuery = "GROUP BY device_id HAVING SUM(amount_usd) BETWEEN {$this->frequentValue0} AND {$this->frequentValue1}";
            } elseif (!empty($this->frequentExp)) {
                $havingQuery = "GROUP BY device_id HAVING SUM(amount_usd) {$this->frequentExp} {$this->frequentValue0}";
            }
        } else {
            $havingQuery = "GROUP BY device_id";
        }

        return $havingQuery;
    }
}