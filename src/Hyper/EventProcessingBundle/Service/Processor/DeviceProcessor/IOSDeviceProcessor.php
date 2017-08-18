<?php

namespace Hyper\EventProcessingBundle\Service\Processor\DeviceProcessor;

use Symfony\Component\DependencyInjection\ContainerInterface,
    Hyper\DomainBundle\Repository\Device\DTIOSDeviceRepository,
    Hyper\EventProcessingBundle\Service\Processor\DeviceProcessor\DeviceProcessorInterface,
    Hyper\EventProcessingBundle\Service\Processor\Processor,
    Hyper\EventProcessingBundle\Service\Processor\ProcessorInterface,
    Hyper\Domain\Device\Device;

class IOSDeviceProcessor extends Processor implements ProcessorInterface
{
    protected $iosDeviceRepository;
    
    public function __construct(ContainerInterface $container, DTIOSDeviceRepository $iosDeviceRepository)
    {
        parent::__construct($container);
        $this->iosDeviceRepository = $iosDeviceRepository;
    }
    
    public function parseMessageBodyToData($messageBody)
    {
        $platform = Device::IOS_PLATFORM_CODE;
        $deviceId = $this->geIdentifierFromMessageBody($messageBody);
        $clickTime = 0;
        if (isset($messageBody['click_time']) && !empty($messageBody['click_time'])) {
            $clickTime = strtotime($messageBody['click_time']);
        }
        $installTime = 0;
        if (isset($messageBody['install_time']) && !empty($messageBody['install_time'])) {
            $installTime = strtotime($messageBody['install_time']);
        }
        $created = time();
        $params = array(
            'device_name' => '',
            'device_type' => '',
            'country_code' => '',
            'city' => '',
            'ip' => '',
            'wifi' => '',
            'language' => '',
            'operator' => '',
            'os_version' => '',
            'mac' => '',
            'idfa' => '',
            'idfv' => '',
            'device_name' => '',
            'device_type' => ''
        );
        
        foreach ($params as $paramKey => $paramValue) {
            if (isset($messageBody[$paramKey])) {
                $params[$paramKey] = $messageBody[$paramKey];
            }
        }
        if (isset($messageBody['wifi'])) {
            $params['wifi'] = var_export($messageBody['wifi'], true);
        }
        unset($messageBody);
        
        return array(
            'devices' => array(
                'id' => $deviceId,
                'platform' => $platform,
                'click_time'=> $clickTime,
                'install_time' => $installTime,
                'country_code' => $params['country_code'],
                'city' => $params['city'],
                'ip' => $params['ip'],
                'wifi' => $params['wifi'],
                'language' => $params['language'],
                'operator' => $params['operator'],
                'device_os_version' => $params['os_version'],
                'created' => $created,
                'mac' => $params['mac']
            ),
            'ios_devices' => array(
                'id' => $deviceId,
                'idfa' => $params['idfa'],
                'idfv' => $params['idfv'],
                'device_name' => $params['device_name'],
                'device_type' => $params['device_type'],
                'created' => $created
            )
        );
    }
    
    public function addExtraDataIntoMessagesBody()
    {
        if (empty($this->messagesBody)) {
            return;
        }
        foreach ($this->messagesBody as $key => $messageBody) {
            if ($messageBody['platform'] != Device::IOS_PLATFORM_NAME) {
                continue;
            }
            $identifier = $this->geIdentifierFromMessageBody($messageBody);
            $extraData = array(
                'device_id' => ''    
            );
            $identifier = $this->isNewIdentifier($identifier);
            if (!$identifier) {
                $newData = $this->parseMessageBodyToData($messageBody);
                $extraData['device_id'] = $newData['ios_devices']['id'];
                $this->listNewData[] = $newData;
                unset($newData);
            } else {
                $extraData['device_id'] = $identifier['id'];
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
        $idfv = isset($messageBody['idfv']) ? $messageBody['idfv'] : '';
        $identifier = array(
            $messageBody['idfa'],
            $idfv
        );
        
        return $this->convertIdentifierToMD5($identifier);
    }
    
    public function parseMessageBodyToIdentifier()
    {
        if (empty($this->messagesBody)) {
            return;
        }
        foreach ($this->messagesBody as $messageBody) {
            if ($messageBody['platform'] != Device::IOS_PLATFORM_NAME) {
                continue;
            }
            $identifier = $this->geIdentifierFromMessageBody($messageBody);
            $this->listIdentifier[] = $identifier;
            unset($messageBody);
        }
    }
    
    public function getRepo()
    {
        return $this->iosDeviceRepository;
    }
    
    public function getValueNewData($newData)
    {
        return $newData['ios_devices']['id'];
    }
}