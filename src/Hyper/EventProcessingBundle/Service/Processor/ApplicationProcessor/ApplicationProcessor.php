<?php

namespace Hyper\EventProcessingBundle\Service\Processor\ApplicationProcessor;

use Symfony\Component\DependencyInjection\ContainerInterface,
    Hyper\DomainBundle\Repository\Application\DTApplicationRepository,
    Hyper\EventProcessingBundle\Service\Processor\Processor,
    Hyper\EventProcessingBundle\Service\Processor\ProcessorInterface,
    Hyper\Domain\Device\Device;

class ApplicationProcessor extends Processor implements ProcessorInterface
{
    protected $applicationRepository;
    
    public function __construct(ContainerInterface $container, DTApplicationRepository $applicationRepository)
    {
        parent::__construct($container);
        $this->applicationRepository = $applicationRepository;
    }
    
    public function parseMessageBodyToData($messageBody)
    {
        $applicationId = $this->geIdentifierFromMessageBody($messageBody);
        if(empty($messageBody['app_name'])) {
            if($messageBody['app_id'] == 'id1049249612') {
                $messageBody['app_name'] = 'Raiders Quest';
            }
        }
        $platform = '';
        if ($messageBody['platform'] == Device::ANDROID_PLATFORM_NAME) {
            $platform = Device::ANDROID_PLATFORM_CODE; 
        } elseif ($messageBody['platform'] == Device::IOS_PLATFORM_NAME) {
            $platform = Device::IOS_PLATFORM_CODE; 
        }
        $params = array(
            'app_id' => '',    
            'app_name' => '',    
            'app_version' => ''
        );
        foreach ($params as $paramKey => $paramValue) {
            if (isset($messageBody[$paramKey])) {
                $params[$paramKey] = $messageBody[$paramKey];
            }
        }
        
        unset($messageBody);
        
        return array(
            'applications' => array(
                'id' => $applicationId,
                'app_id' => $params['app_id'],
                'app_name' => $params['app_name'],
                'app_version'=> $params['app_version'],
                'created' => time(),
                'platform' => $platform
            )
        );
    }
    
    public function addExtraDataIntoMessagesBody()
    {
        if (empty($this->messagesBody)) {
            return;
        }
        foreach ($this->messagesBody as $key => $messageBody) {
            $identifier = $this->geIdentifierFromMessageBody($messageBody);
            $extraData = array(
                'application_id' => '',
                'app_id' => ''    
            );
            $identifier = $this->isNewIdentifier($identifier);
            if (!$identifier) {
                $newData = $this->parseMessageBodyToData($messageBody);
                $extraData['application_id'] = $newData['applications']['id'];
                $extraData['app_id'] = $newData['applications']['app_id'];
                $this->listNewData[] = $newData;
                unset($newData);
            } else {
                $extraData['application_id'] = $identifier['id'];
                $extraData['app_id'] = $identifier['app_id'];
            }
            $extraDataMerged = $this->addExtraData($messageBody, $extraData);
            $this->messagesBody[$key] = $extraDataMerged;
            unset($identifier);
            unset($extraData);
            unset($extraDataMerged);
        }
    }
    
    public function geIdentifierFromMessageBody($messageBody)
    {
        $identifier = array(
            $messageBody['app_id'],
            $messageBody['app_name'],
            $messageBody['app_version']
        );
        
        return $this->convertIdentifierToMD5($identifier);
    }
    
    public function getRepo()
    {
        return $this->applicationRepository;
    }
    
    public function getValueNewData($newData)
    {
        return $newData['applications']['id'];
    }
}