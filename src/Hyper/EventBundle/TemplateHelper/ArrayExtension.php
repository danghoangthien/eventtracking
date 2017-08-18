<?php
namespace Hyper\EventBundle\TemplateHelper;

class ArrayExtension extends \Twig_Extension
{
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('unserialize', array($this, 'unserializeString')),
        );
    }

    public function unserializeString($serializedString){
        return unserialize($serializedString);
    }

    public function getName()
    {
        return 'array_extension';
    }
}