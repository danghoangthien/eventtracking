<?php
namespace Hyper\Adops\APIBundle\Handler;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Hyper\Adops\APIBundle\Handler\SqsHandlerInterface;
use Hyper\Adops\APIBundle\Handler\SqsContainerExtendsHandler;

/**
 *
 * @author Carl Pham <vanca.vnn@gmail.com>
 */
class SqsCreateReportHandler extends SqsContainerExtendsHandler implements SqsHandlerInterface
{
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
    }
    
    public function getMessage($queueParameter, $numberLoop)
    {
        return $this->getMessageFromSQS($queueParameter, $numberLoop);
    }
    
    public function perform($messages = [])
    {
        if (empty($messages)) {
            return ['status'=>false];
        }
        $adopsReportRepo = $this->container->get('adops.web.report.repository');
        $adopsProfileRepo = $this->container->get('adops.web.profile.repository');
        $inappParams = [];
        
        foreach ($messages as $index => $message) {
            if (isset($message['event_type']) && ('install' == $message['event_type'])) {
                $messages[$index]['event_name'] = 'install';
            }
            $issetEvent = (isset($messages[$index]['event_name']) && !empty($messages[$index]['event_name'])) ? true : false;
            $issetAppId = (isset($message['app_id']) && !empty($message['app_id'])) ? true : false;
            if ($issetEvent && $issetAppId) {
                array_push($inappParams, [$message['app_id'] => $messages[$index]['event_name']]);
            }
            $newParams = $adopsReportRepo->setReportAndProfile($messages[$index]);
            $adopsProfileRepo->setAdopsProfile($newParams);
        }
        $adopsReportRepo->insertAdopReport();
        $adopsProfileRepo->insertAdopProfile();
        
        $this->storeEventNameToInappevent($inappParams);
        
        return ['status'=>true];
    }
    
    public function storeEventNameToInappevent($inappParams)
    {
        $inappeventRepo = $this->container->get('adops.web.inappevent.repository');
        $applicationRepo = $this->container->get('adops.web.application.repository');
        if (empty($inappParams)) {
            return false;
        }

        // Remove duplication
        $inappParams = $this->removeDuplication($inappParams);

        $inappDb = [];
        foreach ($inappParams as $inappParam) {
            foreach ($inappParam as $applicationId=>$name) {
                $application = $applicationRepo->findOneBy(['appId'=>$applicationId]);
                if (null == $application) {
                    continue;
                }
                $inappevent = $inappeventRepo->findOneBy([
                    'application'=>$application,
                    'name'=>$name
                ]);
                if (null == $inappevent) {
                    $inappeventRepo->setInappevent([
                        'application'=>$application,
                        'name'=>$name
                    ]);
                }
            }
        }
        $inappeventRepo->insertInappevent();
        
        return true;
    }

    public function removeDuplication($arrayInput)
    {
        $serialized = array_map('serialize', $arrayInput);
        $unique = array_unique($serialized);
        return array_intersect_key($arrayInput, $unique);
    }
    
}