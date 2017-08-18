<?php
namespace Hyper\Adops\APIBundle\Tests\FT\Handler;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @author Carl Pham <vanca.vnn@gmail.com>
 */
class SqsCreateReportHandlerTest extends WebTestCase
{
    const CLASS_PARAMETERBAG = 'Symfony\Component\HttpFoundation\ParameterBag';
    const CLASS_REQUEST = 'Symfony\Component\HttpFoundation\Request';
    
    public function setUp()
    {
        $this->client = static::createClient();
        $this->container = static::$kernel->getContainer();
        $this->appRepo = $this->container->get('adops.web.application.repository');
        $this->inappeventRepo = $this->container->get('adops.web.inappevent.repository');
    }
    
    public function createApplication($condition = [])
    {
        if (
            empty($condition) ||
            !isset($condition['app_name']) ||
            !isset($condition['app_id']) ||
            !isset($condition['platform'])
        ) {
            return false;
        }
        $application = new \Hyper\Adops\WebBundle\Domain\AdopsApplication();
        $application->setAppName($condition['app_name']);
        $application->setAppId($condition['app_id']);
        $application->setPlatform($condition['platform']);
        $this->appRepo->create($application);
        
        return $application;
    }
    
    public function deleteApplication($condition = [])
    {
        if (
            empty($condition) ||
            !isset($condition['app_id'])
        ) {
            return false;
        }
        $applications = $this->appRepo->findBy(['appId'=>$condition['app_id']]);
        if (!empty($applications)) {
            foreach ($applications as $application) {
                $this->appRepo->delete($application);
            }
        }
        
        return true;
    }
    
    public function deleteInappevent($condition = [])
    {
        if (
            empty($condition) ||
            !isset($condition['application']) ||
            !isset($condition['name'])
        ) {
            return false;
        }
        $inappevents = $this->inappeventRepo->findBy([
            'application' => $condition['application'],
            'name' => $condition['name']
        ]);
        if (!empty($inappevents)) {
            foreach ($inappevents as $inappevent) {
                $this->inappeventRepo->delete($inappevent);
            }
        }
        
        return true;
    }
    
    public function testStoreEventNameToInappevent()
    {
        $applicationCondition = [
            'app_name'=>'Test',
            'app_id'=>'test.com.ca',
            'platform'=>'android'
        ];
        $inappeventParams[] = [
            $applicationCondition['app_id']=>'install',
        ];
        $inappeventParams[] = [
            $applicationCondition['app_id']=>'af_content_view',
        ];
        
        // Insert application test
        $this->deleteApplication($applicationCondition);
        $application = $this->createApplication($applicationCondition);
        
        $inappeventCond = [
            'application' => $application,
            'name' => 'install'
        ];
        $this->deleteInappevent($inappeventCond);
        
        $reportHandler = new \Hyper\Adops\APIBundle\Handler\SqsCreateReportHandler($this->container);
        $result = $reportHandler->storeEventNameToInappevent($inappeventParams);
        $this->assertEquals(true, $result);
        
        $inappevent = $this->inappeventRepo->findOneBy([
            'application' => $application,
            'name' => $inappeventCond['name']
        ]);
        $this->assertEquals($inappeventCond['application']->getAppId(), $inappevent->getApplication()->getAppId());
        $this->assertEquals($inappeventCond['name'], $inappevent->getName());
        
        // Delete record test
        $this->deleteInappevent($inappeventCond);
        $this->deleteApplication($applicationCondition);
    }
}