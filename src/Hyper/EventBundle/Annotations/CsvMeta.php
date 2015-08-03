<?php
namespace Hyper\EventBundle\Annotations;
use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 */
final class CsvMeta extends Annotation
{
    public $field;

    
}