<?php
namespace Hyper\EventBundle\Service\FilterService\Condition\Query;

interface QueryInterface
{
    public function getTableTemp();
    public function createTableTemp();
    public function dropTableTemp();
    public function getQuery();
    public function getInsertQuery();
}