<?php

namespace Hyper\EventProcessingBundle\Validator;

use Symfony\Component\Validator\Constraint;

class ContainsMessage extends Constraint
{
    public function validatedBy()
    {
        return get_class($this).'Validator';
    }
}