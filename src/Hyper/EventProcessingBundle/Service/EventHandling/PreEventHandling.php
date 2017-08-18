<?php

namespace Hyper\EventProcessingBundle\Service\EventHandling;

use Symfony\Component\DependencyInjection\ContainerInterface,
    Hyper\EventProcessingBundle\Service\EventHandling\EventHandlingBase,
    Hyper\EventProcessingBundle\Service\EventHandling\EventHandlingInterface,
    Hyper\Domain\Setting\Setting,
    Hyper\EventProcessingBundle\Validator\ContainsMessage;

class PreEventHandling extends EventHandlingBase implements EventHandlingInterface
{
    protected $androidDeviceProcessor;
    protected $iosDeviceProcessor;
    protected $applicationProcessor;

    protected $maxNumberOfReceiveMessage = 3000;
    protected $minNumberOfReceiveMessage = 2000;

    public function __construct(
        ContainerInterface $container,
        $queueName,
        $queueNameNext,
        $bucketName
    ) {
        parent::__construct($container, $queueName, $queueNameNext, $bucketName);
        $this->androidDeviceProcessor = $this->container->get('hyper_event_processing.android_device_processor');
        $this->iosDeviceProcessor = $this->container->get('hyper_event_processing.ios_device_processor');
        $this->applicationProcessor = $this->container->get('hyper_event_processing.application_processor');
    }

    public function processData()
    {
        $this->processorManager->addProcessor($this->androidDeviceProcessor);
        $this->processorManager->addProcessor($this->iosDeviceProcessor);
        $this->processorManager->addProcessor($this->applicationProcessor);
        list($messagesBodyParsed, $listNewData) = $this->processorManager->processData();
        $this->messagesBodyParsed = $messagesBodyParsed;
        $this->listNewData = $listNewData;
    }

    public function validMessageBody()
    {
        $validator = $this->container->get('validator');
        foreach ($this->messagesBody as $key => $messageBody) {
            if (!empty($messageBody['extra_data']['validate']) && $messageBody['extra_data']['validate'] == 1) {
                continue;
            }
            $errors = array();
            $containsMessage = new ContainsMessage();
            $violations = $validator->validate($messageBody, $containsMessage);
            if (count($violations) > 0) {
                foreach ($violations as $violation) {
                    $error = $violation->getMessage();
                    $errors[] = $error;
                }
            }
            $clientName = isset($messageBody['extra_data']['client_name']) ?
                            $messageBody['extra_data']['client_name'] : '';
            if (empty($clientName)) {
                $errors[] = 'client_name must be have a value.';
            }
            $appId = isset($messageBody['extra_data']['app_id']) ?
                            $messageBody['extra_data']['app_id'] : '';
            if (empty($appId)) {
                $errors[] = 'app_id must be have a value.';
            }
            $s3LogFile = isset($messageBody['extra_data']['s3_log_file']) ?
                            $messageBody['extra_data']['s3_log_file'] : '';
            if (empty($s3LogFile)) {
                $errors[] = 's3_log_file must be have a value.';
            }
            $eventType = isset($messageBody['event_type']) ?
                            $messageBody['event_type'] : '';
            $eventName = isset($messageBody['event_name']) ?
                            $messageBody['event_name'] : '';
            if (!empty($errors)) {
                $this->container
                    ->get('hyper_event_processing.logger_wrapper')->logInvalidContent(
                        $errors,
                        $this->container->getParameter('amazon_s3_bucket_pre_event_handling'),
                        'invalid-data',
                        $clientName,
                        $appId,
                        $eventType,
                        $eventName,
                        $messageBody,
                        $s3LogFile
                    );
                unset($this->messagesBody[$key]);
            }
        }
        if (empty($this->messagesBody)) {
            throw new \Exception('No valid message continue to process.');
        }
    }

    public function getSettingKey()
    {
        return Setting::PRE_EVENT_HANDLING_TYPE_KEY;
    }

    public function storeDataToElasticSearch()
    {
        $this->storeDeviceToElasticSearch();
        $this->storeAndroidDeviceToElasticSearch();
        $this->storeIOSDeviceToElasticSearch();
    }

    public function storeDeviceToElasticSearch()
    {
        $devices = [];
        $index = 'devices';
        if (!empty($this->listFileToWrite[$index])) {
            $devices = $this->listFileToWrite[$index];
        }
        if (empty($devices)) {
            return;
        }
        $esIndexEndpoint = $index;
        if (!$this->checkIndexES($esIndexEndpoint)) {
            return;
        }
        $esBulkEndpoint = $esIndexEndpoint.'/'.$index.'/_bulk';
        $esJson = $this->makeJsonES($index, $index, $devices);
        try {
            $response = $this->gzClient->request(
                $esBulkEndpoint,
                'POST',
                $esJson
            );
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $this->log(
                $e
                , 'es-bulk-devices-fail'
                , $esJson
            );
        } catch (\GuzzleHttp\Exception\ServerException $e) {
            $this->log(
                $e
                , 'es-bulk-devices-fail'
                , $esJson
            );
        }
    }

    public function storeAndroidDeviceToElasticSearch()
    {
        $esParameters = $this->container->getParameter('amazon_elasticsearch');
        $devices = [];
        $index = 'android_devices' . '_' . $esParameters['index_version'];
        if (!empty($this->listFileToWrite[$index])) {
            $devices = $this->listFileToWrite[$index];
        }
        if (empty($devices)) {
            return;
        }
        $esIndexEndpoint = $index;
        if (!$this->checkIndexES($esIndexEndpoint)) {
            return;
        }
        $esBulkEndpoint = $esIndexEndpoint.'/'.$index.'/_bulk';
        $esJson = $this->makeJsonES($index, $index, $devices);
        try {
            $response = $this->gzClient->request(
                $esBulkEndpoint
                , 'POST'
                , $esJson
            );
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $this->log(
                $e
                , 'es-bulk-android-devices-fail'
                , $esJson
            );
        } catch (\GuzzleHttp\Exception\ServerException $e) {
            $this->log(
                $e
                , 'es-bulk-android-devices-fail'
                , $esJson
            );
        }
    }

    public function storeIOSDeviceToElasticSearch()
    {
        $esParameters = $this->container->getParameter('amazon_elasticsearch');
        $devices = [];
        $index = 'ios_devices' . '_' . $esParameters['index_version'];
        if (!empty($this->listFileToWrite[$index])) {
            $devices = $this->listFileToWrite[$index];
        }
        if (empty($devices)) {
            return;
        }
        $esIndexEndpoint = $index;
        if (!$this->checkIndexES($esIndexEndpoint)) {
            return;
        }
        $esBulkEndpoint = $esIndexEndpoint.'/'.$index.'/_bulk';
        $esJson = $this->makeJsonES($index, $index, $devices);
        try {
            $response = $this->gzClient->request(
                $esBulkEndpoint
                , 'POST'
                , $esJson
            );
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $this->log(
                $e
                , 'es-bulk-ios-devices-fail'
                , $esJson
            );
        } catch (\GuzzleHttp\Exception\ServerException $e) {
            $this->log(
                $e
                , 'es-bulk-ios-devices-fail'
                , $esJson
            );
        }
    }

    public function log(\Exception $e, $prefix, $content)
    {
         $this->container
            ->get('hyper_event_processing.logger_wrapper')->log(
                $e,
                $this->container->getParameter('amazon_s3_bucket_pre_event_handling'),
                $prefix,
                $content
            );
    }
}