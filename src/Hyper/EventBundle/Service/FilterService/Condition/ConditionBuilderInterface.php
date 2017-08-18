<?php
namespace Hyper\EventBundle\Service\FilterService\Condition;

use Hyper\EventBundle\Service\FilterService\Condition\DataType\DataTypeInterface;

interface ConditionBuilderInterface
{

    public function add(DataTypeInterface $dataType);
    public function build();
}