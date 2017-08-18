<?php
namespace Hyper\Adops\APIBundle\Tests\UT\Command;

use Hyper\Adops\APIBundle\Tests\UT\TestDouble;
use Hyper\Adops\APIBundle\Command\SqsContainerAware;

/**
 * @author Carl Pham <vanca.vnn@gmail.com>
 */
class SqsContainerAwareTest extends TestDouble
{
    const CLASS_SQS_CONTAINER = 'Hyper\Adops\APIBundle\Command\SqsContainerAware';
    
    public function setUp()
    {
        //Simple instance objects
        $this->sqsContainer = $this->getInstance(static::CLASS_SQS_CONTAINER);
    }
}