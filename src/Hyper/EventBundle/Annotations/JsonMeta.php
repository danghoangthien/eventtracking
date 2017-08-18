<?php
namespace Hyper\EventBundle\Annotations;
use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 */
final class JsonMeta extends Annotation
{
    public $field;

    
}