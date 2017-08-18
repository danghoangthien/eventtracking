<?php
namespace Hyper\Adops\APIBundle\Tests\FT\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Hyper\Adops\APIBundle\Controller\PostbackController;

/**
 * @author Carl Pham <vanca.vnn@gmail.com>
 */
class PostbackControllerTest extends WebTestCase
{
    const CLASS_PARAMETERBAG = 'Symfony\Component\HttpFoundation\ParameterBag';
    const CLASS_REQUEST = 'Symfony\Component\HttpFoundation\Request';
    
    public function setUp()
    {
        $this->client = static::createClient();
        $this->container = static::$kernel->getContainer();
    }
    
    public function testGetAction()
    {
        // Empty all message on queue
        $sqsContainer = new \Hyper\Adops\APIBundle\Handler\SqsContainerExtendsHandler($this->container);
        $sqsContainer->removeAllMessages('amazon_sqs_queue_name');

        $url = '/adops/api/v1/post/aff_lsr?transaction_id=123';
        $this->client->request('GET', $url, array('ACCEPT' => 'application/json'));
        $response = $this->client->getResponse();
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('status', $content);
        $this->assertEquals(true, $content['status']);
        
        $messages = $sqsContainer->getMessageFromSQS('amazon_sqs_queue_name');
        $this->assertArrayHasKey('transaction_id', $messages[0]);
        $this->assertEquals(123, $messages[0]['transaction_id']);
    }
}