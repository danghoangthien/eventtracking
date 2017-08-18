<?php

use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;
use Composer\Autoload\ClassLoader;
use Doctrine\DBAL\Types\Type;



/**
 * @var ClassLoader $loader
 */
$loader = require __DIR__.'/../vendor/autoload.php';
AnnotationRegistry::registerFile( __DIR__.'/../src/Hyper/EventBundle/Annotations/CsvMeta.php');
//AnnotationDriver::registerAnnotationClasses();
Type::addType('serial', 'Hyper\CustomDoctrineDataType\Serial');
AnnotationRegistry::registerLoader(array($loader, 'loadClass'));
return $loader;
