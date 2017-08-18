<?php

namespace Hyper\EventProcessingBundle\Service\Processor\IdentityCaptureProcessor;

use Symfony\Component\DependencyInjection\ContainerInterface
    , Symfony\Component\Filesystem\Filesystem
    , Hyper\EventBundle\Service\Cached\InappeventConfig\InappeventConfigCached;

class IdentityCaptureProcessor
{
    protected $container;
    protected $listMessageBody;
    protected $listData;
    protected $fs;
    protected $s3Path;

    public function __construct(ContainerInterface $container, $listMessageBody)
    {
        $this->container = $container;
        $this->listMessageBody = $listMessageBody;
        if (!$this->listMessageBody) {
            throw new \Exception('No message body in identity capture processor.');
        }
        $this->fs = new Filesystem();
        $this->ieaConfigCached = new InappeventConfigCached($this->container);
    }

    public function process()
    {
        $this->captureEmail();
        $this->sendDataToSqs();
    }

    protected function captureEmail()
    {
        foreach($this->listMessageBody as $messageBody) {
            $appId = '';
            if (!empty($messageBody['app_id'])) {
                $appId = $messageBody['app_id'];
            }
            $eventName = '';
            if (!empty($messageBody['event_name'])) {
                $eventName = $messageBody['event_name'];
            }
            if (!$this->checkEventTagAsEmail($appId, $eventName)) {
                continue;
            }
            if (!empty($messageBody['event_value'])) {
                $eventValue = $messageBody['event_value'];
            }
            $deviceId = '';
            if (!empty($messageBody['extra_data']['device_id'])) {
                $deviceId = $messageBody['extra_data']['device_id'];
            }
            $email = $this->captureEmailFromEventValue($eventValue);
            if ($deviceId && $email) {
                $this->listData[] = [
                    'device_id' => $deviceId
                    , 'email' => $email
                ];
            }
        }
    }

    protected function sendDataToSqs()
    {
        if (empty($this->listData)) {
            return;
        }
        $result = $this->container
            ->get('hyper_event_processing.sqs_wrapper')->sendMessageBatch(
            $this->container->getParameter('amazon_sqs_queue_identity_capture'),
            $this->listData
        );
    }

    protected function getFS()
    {
        return $this->fs;
    }

    protected function makeRSCopySyntax($data)
    {
        $jsonOutput = '';
        foreach ($data as $item) {
            $jsonOutput .= json_encode($item);
        }
        return $jsonOutput;
    }

    private function checkEventTagAsEmail($appId, $eventName)
    {
        if (!$this->ieaConfigCached->exists()) {
            return false;
        }
        $iaeConfig = $this->ieaConfigCached->hget($appId);
        if (!$iaeConfig) {
            return false;
        }
        $iaeConfig = json_decode($iaeConfig, true);
        if (empty($iaeConfig) || empty($iaeConfig[$eventName]['tag_as_email'])) {
            return false;
        }

        return true;
    }

    private function captureEmailFromEventValue($eventValue)
    {
        if (empty($eventValue)) {
            return [];
        }
        $listEmail = [];
        if (!is_array($eventValue)) {
            $eventValue = json_decode($eventValue, true);
            //$eventValue['af_content_id'] = 'abc@gmail.com';
            if(json_last_error() === JSON_ERROR_NONE) {
                $listEmail = $this->captureEmailFromArray($eventValue);
            }
        } else {
            $listEmail = $this->captureEmailFromString($eventValue);
        }

        return $listEmail;
    }

    private function captureEmailFromArray(array $arr) {
        $email = '';
        $iterator = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($arr));
        foreach($iterator as $key => $value) {
            $email = $this->captureEmailFromString($value);
            if ($email) {
                break;
            }
        }

        return $email;
    }

    private function captureEmailFromString($string)
    {
        $pattern = '/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b/i';
        preg_match_all($pattern, $string, $matches);
        $email = '';
        if (!empty($matches[0][0])) {
            $email = $matches[0][0];
        }

        return $email;
    }

}