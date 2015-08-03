<?php
namespace Hyper\EventBundle\Annotations;
use Doctrine\Common\Annotations\AnnotationReader;

class CsvMetaReader {
    
    public function csvMongoDbIndex($entityClass) {
        $reader = new AnnotationReader();
        $reflectionClass = new \ReflectionClass(new $entityClass);
		$reflectionObj = new \ReflectionObject(new $entityClass);
		$properties = $reflectionClass->getProperties();
		$csvMongoDbIndex = array();
		foreach ($properties as $property) {
		    $csvMetaAnnotation = $reader->getPropertyAnnotation($property,'Hyper\\EventBundle\\Annotations\\CsvMeta');
		    $mongoDbIndexAnnotation = $reader->getPropertyAnnotation($property,'Doctrine\\ODM\MongoDB\\Mapping\\Annotations\\Field');
		    
		    if ($csvMetaAnnotation && $mongoDbIndexAnnotation) {
		    	$columnName = $csvMetaAnnotation->field;
		    	$mongoDbIndex = $mongoDbIndexAnnotation->name;
		    	$csvMongoDbIndex[$mongoDbIndex] = strtolower($columnName);
		    }
		    
		}
		return $csvMongoDbIndex;
    }
    
}
