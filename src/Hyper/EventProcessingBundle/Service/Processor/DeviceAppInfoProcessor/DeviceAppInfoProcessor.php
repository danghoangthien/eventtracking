<?php

namespace Hyper\EventProcessingBundle\Service\Processor\DeviceAppInfoProcessor;

use Symfony\Component\DependencyInjection\ContainerInterface
    , Symfony\Component\Filesystem\Filesystem;

class DeviceAppInfoProcessor
{
    protected $container;
    protected $listMessageBody;

    public function __construct(ContainerInterface $container, $listMessageBody)
    {
        $this->container = $container;
        $this->listMessageBody = $listMessageBody;
        if (!$this->listMessageBody) {
            throw new \Exception('No message body');
        }
    }

    public function process()
    {
        $listDeviceByApp = [];
        foreach ($this->listMessageBody as $messageBody) {
            $appId = $messageBody['app_id'];
            $deviceId = $messageBody['extra_data']['device_id'];
            // last install time
            $installTime = '';
            if (empty($listDeviceByApp[$appId][$deviceId])) {
                $listDeviceByApp[$appId][$deviceId]['install_time'] = 0;
            }
            if (!empty($messageBody['install_time'])) {
                $installTime = strtotime($messageBody['install_time']);
                $installTimeCurr = $listDeviceByApp[$appId][$deviceId]['install_time'];
                if ($installTime > $installTimeCurr) {
                    $listDeviceByApp[$appId][$deviceId]['install_time'] = $installTime;
                }
            }
            // last activity
            if (empty($listLastActivity[$appId][$deviceId])) {
                $listDeviceByApp[$appId][$deviceId]['last_activity'] = 0;
            }
            if (!empty($messageBody['event_time'])) {
                $happenedAt = strtotime($messageBody['event_time']);
                $happenedAtCurr = $listDeviceByApp[$appId][$deviceId]['last_activity'];
                if ($happenedAt > $happenedAtCurr) {
                    $listDeviceByApp[$appId][$deviceId]['last_activity'] = $happenedAt;
                }
            }
        }
        $listDeviceAppInfo = [];
        if (!empty($listDeviceByApp)) {
            foreach($listDeviceByApp as $appId => $listDevice) {
                foreach($listDevice as $deviceId => $deviceAppInfo) {
                    $listDeviceAppInfo[] = [
                        'device_id' => $deviceId
                        , 'app_id' => $appId
                        , 'install_time' => $deviceAppInfo['install_time']
                        , 'last_activity' => $deviceAppInfo['last_activity']
                    ];
                }
            }
        }

        return $listDeviceAppInfo;
    }

}