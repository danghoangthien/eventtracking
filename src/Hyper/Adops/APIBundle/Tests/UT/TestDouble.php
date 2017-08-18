<?php
namespace Hyper\Adops\APIBundle\Tests\UT;

class TestDouble extends \PHPUnit_Framework_TestCase
{

    protected function dummy($FQClassName, array $methodsOverride = [], $disableOriginalConstructor = true)
    {
        $mockBuilder = $this->getMockBuilder($FQClassName);
        if ($disableOriginalConstructor === true) {
            $mockBuilder->disableOriginalConstructor();
        }

        if (empty($methodsOverride)) {
            return $mockBuilder->getMock();
        }

        return $mockBuilder
            ->setMethods($methodsOverride)
            ->getMock()
        ;
    }

    protected function stub(&$mock, $method, $returnValue)
    {
        $mock
            ->expects($this->any())
            ->method($method)
            ->will($this->returnValue($returnValue))
        ;
    }

    protected function fake(&$mock, $method, $callback)
    {
        $mock
            ->expects($this->any())
            ->method($method)
            ->will($this->returnCallback($callback))
        ;
    }

    protected function stubAt(&$mock, $method, $returnValue, $time, array $params = [])
    {
        if (empty($params)) {
            $mock
                ->expects($this->at($time))
                ->method($method)
                ->will($this->returnValue($returnValue))
            ;
        } else {
            $mock
                ->expects($this->at($time))
                ->method($method)
                ->with(...$params)
                ->will($this->returnValue($returnValue))
            ;
        }
    }

    /**
     * Call protected/private method of a class.
     *
     * @param  object &$object    Instantiated object that we will run method on.
     * @param  string $methodName Method name to call
     * @param  array  $parameters Array of parameters to pass into method.
     *
     * @return mixed              Method return.
     */
    protected function invokeMethod(&$object, $methodName)
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method;
    }

    /**
     * Set protected/private property of a class.
     *
     * @param  object &$object    Instantiated object that we will run method on.
     * @param  string $property   Property name to call
     * @param  array  $value      Value of property to set
     *
     * @return void
     */
    protected function invokeProperty(&$object, $property, $value = 'IDONTWANNASETVALUE', $ignoreParentClass = false)
    {
        $parentClass = get_parent_class($object);
        if ($parentClass && !$ignoreParentClass) {
            $reflection = new \ReflectionClass($parentClass);
        } else {
            $reflection = new \ReflectionClass(get_class($object));
        }

        $property = $reflection->getProperty($property);
        $property->setAccessible(true);

        if ($value !== 'IDONTWANNASETVALUE') {
            $property->setValue($object, $value);
        }
    }

    protected function getInstance($FQClassName, $disableOriginalConstructor = true)
    {
        if ($disableOriginalConstructor === false) {
            return new $FQClassName();
        }

        $reflectionClass = new \ReflectionClass($FQClassName);
        return $reflectionClass->newInstanceWithoutConstructor();
    }

    protected function replaceProperties(&$object, array $data = [])
    {
        if (empty($data)) {
            return;
        }

        foreach ($data as $property => $value) {
            $this->invokeProperty($object, $property, $value);
        }
    }
}