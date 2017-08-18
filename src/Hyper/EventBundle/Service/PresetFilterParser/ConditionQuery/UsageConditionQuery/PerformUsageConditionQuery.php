<?php
namespace Hyper\EventBundle\Service\PresetFilterParser\ConditionQuery\UsageConditionQuery;

use \Symfony\Component\DependencyInjection\ContainerInterface
    , Hyper\EventBundle\Service\PresetFilterParser\ConditionQuery\UsageConditionQuery\UsageConditionQuery
    , Hyper\EventBundle\Service\PresetFilterParser\ConditionQuery\ConditionQueryInterface;

class PerformUsageConditionQuery extends UsageConditionQuery implements ConditionQueryInterface
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

        parent::__construct($container
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
        $this->buildQuery();
    }
}