<?php
namespace Hyper\Adops\APIBundle\Tests\FT\Command;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

use Hyper\Adops\APIBundle\Command\SqsSendToPublisherCommand;

/**
 * @author Carl Pham <vanca.vnn@gmail.com>
 */
 class SqsSendToPublisherCommandTest extends CommandTestCase
 {
    public function emptyQueue($queueName)
    {
        // Check queue name careful.
        $queueValue = $this->container->getParameter($queueName);
        if (
            (strpos($queueValue, 'dev-') === false) &&
            (strpos($queueValue, 'test-') === false)
        ) {
            echo 'Please check queue name in config. Only accept with prefix "dev" or "test".';
            $this->assertEquals(0, 1);
        }
        // Empty all message on queue
        $sqsContainer = new \Hyper\Adops\APIBundle\Handler\SqsContainerExtendsHandler($this->container);
        $sqsContainer->removeAllMessages($queueName);
    }
    
    public function pushMessageToQueue($numberMessage, $args=[])
    {
        $url = '/adops/api/v1/post/aff_lsr?transaction_id={transaction_id}&af_siteid={site_id}
        &app_id='.$args['app_id'].'&token={token}&af_adset={adset_name}&af_sub1={af_sub1}
        &c={campaign_name}&idfa={idfa}&advertising_id={advertiserId}&android_id={android-id}
        &wifi={wifi}&click_time={click_time}&install_time={install_time}
        &country_code='.$args['country_code'].'&city='.$args['city'].'&device-brand={device-brand}
        &device_carrier={carrier}&device_ip={ip}&device_model={device-model}&language=vn
        &sdk_version={sdk-version}&version={app-version-name}&ua={user-agent}';

        for ($i = 0; $i < $numberMessage; $i ++) {
            $this->client->request('GET', $url, array('ACCEPT' => 'application/json'));
            // $response = $this->client->getResponse();
            // $content = json_decode($response->getContent(), true);
        }
    }
    
    public function testWithOneMessages()
    {
        // Empty queue
        $this->emptyQueue('amazon_sqs_queue_name');
        $this->emptyQueue('amazon_sqs_report_queue');
        
        // Push 100 messages to queue
        $now = time();
        $appId = 'test.com.ca.'.$now;
        $args = [
            'app_id' => $appId,
            'city' => 'Ho Chi Minh',
            'country_code' => 'VN',
        ];
        
        $this->pushMessageToQueue(1, $args);
        
        // Run command
        $output1 = $this->runCommand($this->client, "sqs:send-to-publisher --skip-check-ip=1 --env=prod");
        $this->assertEquals('Completed!', $output1);
        
        $output2 = $this->runCommand($this->client, "sqs:create-report --skip-check-ip=1 --env=prod");
        $this->assertEquals('Completed!', $output2);
        
        $queryReport = $this->_em->createQuery(
            'SELECT rep FROM Hyper\Adops\WebBundle\Domain\AdopsReport rep
            WHERE rep.appId = :app_id 
            ORDER BY rep.created DESC'
        )->setParameter('app_id', $appId);
        $reports = $queryReport->getResult();
        $reportId = $reports[0]->getId();
        $this->assertEquals($args['app_id'], $reports[0]->getAppId());
        
        $queryProfile = $this->_em->createQuery(
            'SELECT pro FROM Hyper\Adops\WebBundle\Domain\AdopsProfile pro
            WHERE pro.reportId = :report_id'
        )->setParameter('report_id', $reportId);
        $profile = $queryProfile->getResult();
        $profileId = $profile[0]->getId();
        $this->assertEquals($args['city'], $profile[0]->get('city'));
        $this->assertEquals($args['country_code'], $profile[0]->get('code_country'));
        
        // Delete record test
        $queryDeleteReport = $this->_em->createQuery(
            'DELETE FROM Hyper\Adops\WebBundle\Domain\AdopsReport rep
            WHERE rep.id = :report_id '
        )->setParameter('report_id', $reportId);
        $queryDeleteReport->getResult();
        
        $queryDeleteProfile = $this->_em->createQuery(
            'DELETE FROM Hyper\Adops\WebBundle\Domain\AdopsProfile pro
            WHERE pro.id = :profile_id '
        )->setParameter('profile_id', $profileId);
        $queryDeleteProfile->getResult();
        
    }
 }