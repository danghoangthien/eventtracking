<?php

namespace Hyper\EventProcessingBundle\Service\Processor\DeviceProcessor;

use Symfony\Component\DependencyInjection\ContainerInterface,
    Hyper\DomainBundle\Repository\Device\DTAndroidDeviceRepository,
    Hyper\EventProcessingBundle\Service\Processor\Processor,
    Hyper\EventProcessingBundle\Service\Processor\ProcessorInterface,
    Hyper\Domain\Device\Device;

class AndroidDeviceProcessor extends Processor implements ProcessorInterface
{
    protected $androidDeviceRepository;
    
    public function __construct(ContainerInterface $container, DTAndroidDeviceRepository $androidDeviceRepository)
    {
        parent::__construct($container);
        $this->androidDeviceRepository = $androidDeviceRepository;
    }
    
    public function parseMessageBodyToData($messageBody)
    {
        $platform = Device::ANDROID_PLATFORM_CODE;
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
            'advertising_id' => '',
            'android_id' => '',
            'country_code' => '',
            'city' => '',
            'ip' => '',
            'city' => '',
            'wifi' => '',
            'language' => '',
            'operator' => '',
            'os_version' => '',
            'mac' => '',
            'imei' => '',
            'device_brand' => '',
            'device_model' => ''
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
                'mac' => $params['mac'],
                'operator' => $params['operator'],
                'device_os_version' => $params['os_version'],
                'created' => $created
                
            ),
            'android_devices' => array(
                'id' => $deviceId,
                'advertising_id' => $params['advertising_id'],
                'android_id' => $params['android_id'],
                'imei' => $params['imei'],
                'device_brand' => $params['device_brand'],
                'device_model'=> $params['device_model'],
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
            if ($messageBody['platform'] != Device::ANDROID_PLATFORM_NAME) {
                continue;
            }
            $identifier = $this->geIdentifierFromMessageBody($messageBody);
            $extraData = array(
                'device_id' => ''    
            );
            $identifier = $this->isNewIdentifier($identifier);
            if (!$identifier) {
                $newData = $this->parseMessageBodyToData($messageBody);
                $extraData['device_id'] = $newData['android_devices']['id'];
                $this->listNewData[] = $newData;
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
        $identifier = array(
            $messageBody['android_id'],
            $messageBody['advertising_id']
        );
        
        return $this->convertIdentifierToMD5($identifier);
    }
    
    public function parseMessageBodyToIdentifier()
    {
        if (empty($this->messagesBody)) {
            return;
        }
        foreach ($this->messagesBody as $key => $messageBody) {
            if ($messageBody['platform'] != Device::ANDROID_PLATFORM_NAME) {
                continue;
            }
            $identifier = $this->geIdentifierFromMessageBody($messageBody);
            $this->listIdentifier[] = $identifier;
            unset($messageBody);
        }
    }
    
    public function getRepo()
    {
        return $this->androidDeviceRepository;
    }
    
    public function getValueNewData($newData)
    {
        return $newData['android_devices']['id'];
    }
}