<?php
namespace Hyper\EventBundle\Service\PresetFilterParser\ConditionQuery;

use Hyper\EventBundle\Service\PresetFilterParser\ConditionQuery\ConditionQueryInterface;

class ConditionQueryAdapter
{
    protected $conditionQuery;

    public function __construct(
        ConditionQueryInterface $conditionQuery
    ){
        $this->conditionQuery = $conditionQuery;
    }


    public function getQuery()
    {
        return $this->conditionQuery->getQuery();
    }
}