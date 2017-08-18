<?php
namespace Hyper\EventBundle\Service\FilterService\Condition\DataType;

use Hyper\EventBundle\Service\FilterService\Condition\DataType\DataTypeInterface;
use Hyper\EventBundle\Service\FilterService\Condition\DataType\DataType;

class RelationDataType extends DataType implements DataTypeInterface
{
    const RELATION_TYPE = [
        'and' => 'INTERSECT'
        , 'or' => 'UNION'
    ];

    private $relation;

    public function __construct($relation) {
        $this->relation = $relation;
        $this->assertData();
    }

    public function relation()
    {
        return $this->relation;
    }

    public function relationMapping()
    {
        $relationMapping = '';
        if ($this->relation && !empty(self::RELATION_TYPE[$this->relation])) {
            $relationMapping = self::RELATION_TYPE[$this->relation];
        }

        return $relationMapping;
    }

    public function assertData()
    {
        if (
            !empty($this->relation) && !in_array(strtolower($this->relation), array_keys(self::RELATION_TYPE))
        ) {
            throw new \Exception('The relation only support '. implode(",", array_keys(self::RELATION_TYPE)) . '.');
        }
    }
}