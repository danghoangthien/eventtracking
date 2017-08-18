<?php
namespace Hyper\EventBundle\Service\FilterService\Condition\Query;

use Hyper\EventBundle\Service\FilterService\Condition\Query\UsageQuery;
use Hyper\EventBundle\Service\FilterService\Condition\DataType\UsageDataType;

class PerformUsageQuery extends UsageQuery
{

    public function __construct(
        $connection
        , UsageDataType $dataType
    )
    {
       parent::__construct($connection, $dataType);
    }
}