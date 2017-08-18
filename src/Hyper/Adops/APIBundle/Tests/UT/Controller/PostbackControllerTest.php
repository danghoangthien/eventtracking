<?php
namespace Hyper\Adops\APIBundle\Tests\UT\Controller;

use Hyper\Adops\APIBundle\Tests\UT\TestDouble;
use Hyper\Adops\APIBundle\Controller\PostbackController;

/**
 * @author Carl Pham <vanca.vnn@gmail.com>
 */
class PostbackControllerTest extends TestDouble
{
    const CLASS_PARAMETERBAG = 'Symfony\Component\HttpFoundation\ParameterBag';
    const CLASS_REQUEST = 'Symfony\Component\HttpFoundation\Request';
    
    public function setUp()
    {
    }
    
    public function testGetActionFailStatus()
    {
        $queryStub = $this->getMock(static::CLASS_PARAMETERBAG);
        $queryStub->method('all')
                ->willReturn(['not_type'=>'aff_lsr']);
        
        $requestInstance = $this->getInstance(static::CLASS_REQUEST);
        $this->replaceProperties($requestInstance, ['query'=>$queryStub]);
        
        $postbackController = new PostbackController();
        $rs = $postbackController->getAction($requestInstance, 'aff_lsr');
        $this->assertArrayHasKey('status', $rs);
        $this->assertEquals(false, $rs['status']);
    }
    
    public function testGetAction()
    {
        $queryStub = $this->getMock(static::CLASS_PARAMETERBAG);
        $queryStub->method('all')
                ->willReturn(['type'=>'aff_lsr']);
        
        $requestInstance = $this->getInstance(static::CLASS_REQUEST);
        $this->replaceProperties($requestInstance, ['query'=>$queryStub]);
        
        $postbackController = new PostbackController();
        $rs = $postbackController->getAction($requestInstance, 'aff_lsr');
        $this->assertArrayHasKey('status', $rs);
        $this->assertEquals(true, $rs['status']);
    }
}