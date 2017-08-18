<?php

namespace Hyper\EventProcessingBundle\Service\EventHandling;

use Symfony\Component\DependencyInjection\ContainerInterface
    , Aws\S3\S3Client
    , Symfony\Component\Filesystem\Filesystem
    , Symfony\Component\HttpFoundation\File\File
    , Hyper\EventBundle\Service\Cached\Setting\SettingCached
    , Hyper\EventBundle\Service\Cached\App\AppCached
    , Hyper\EventProcessingBundle\Validator\ContainsMessage
    , Hyper\Domain\Action\Action
    , Hyper\Domain\Device\Device;

class ForwardEventHandling
{
    const NUMBER_OF_RECEIVE_MESSAGE = 3000;
    const SETTING_KEY = 'forward_postback_data_to_ak';
    const SETTING_VALUE_START = 'start';
    const SLEEPING = 10;
    const OTHER_FOLDER = 'others';
    protected $container;
    protected $fs;
    protected $queueName;
    protected $queueNameNext;
    protected $s3Bucket;
    protected $proccessId;
    protected $s3Wrapper;
    protected $sqsWrapper;
    protected $s3Client;
    protected $sqsClient;
    protected $settingCached;
    protected $listMessagesBody;
    protected $listForwardEvent;
    protected $listForwardEventStored;
    protected $listForwardEventValid;
    protected $rawLogDir = '/var/www/html/projects/event_tracking/web/raw_event';


    public function __construct(
        ContainerInterface $container,
        $queueName,
        $queueNameNext,
        $bucketName
    ) {
        $this->container = $container;
        $this->queueName = $queueName;
        $this->queueNameNext = $queueNameNext;
        $this->s3Bucket = $bucketName;
        $this->proccessId = uniqid();
        $this->settingCached = new SettingCached($this->container);
        $this->sqsWrapper = $this->container
            ->get('hyper_event_processing.sqs_wrapper');
        $this->s3Wrapper = $this->container
            ->get('hyper_event_processing.s3_wrapper');
        $this->s3Client = $this->s3Wrapper->getS3Client();
        $this->sqsClient = $this->sqsWrapper->getSQSClient();
    }

    public function handle()
    {
        // check process
        if(!$this->checkRunning()) {
            throw new \Exception('Processing is stoping.');
        };
        $totalTimeForwardEventHandling = 0;
        $start = date('d-m-Y H:i:s');
        self::logger('Start time of forward event handling', $start);
        $totalMessageOnQueue = $this->countMessageOnQueue();
        self::logger('Total of message on queue', $totalMessageOnQueue);
        if (!$this->checkCountMessageOnQueue($totalMessageOnQueue)) {
            $this->nextRunning();
            // ram benchmark
            self::logger('End time of forward event handling', $end);
            $memoryUsed = number_format(memory_get_usage() / 1048576, 1);
            $memoryPeakBefore = number_format(memory_get_peak_usage() / 1048576, 1);
            self::logger('Memory usage/peak before', $memoryUsed . " / " . $memoryPeakBefore);

            $end = date('d-m-Y H:i:s');
            self::logger('End time of forward event handling', $end);
        }

        // received message
        $startTimeReceiveMessage = microtime(true);
        $this->receiveMessagesBodyFromQueue();
        $totalTimeReceiveMessage = microtime(true) - $startTimeReceiveMessage;
        $totalTimeForwardEventHandling += $totalTimeReceiveMessage;
        $totalTimeReceiveMessage = number_format($totalTimeReceiveMessage, 1);
        $totalMessageBody = count($this->listMessagesBody);
        self::logger('Total of received message', $totalMessageBody);
        self::logger('Total time of receive message', $totalTimeReceiveMessage. 's');

        // parse message
        $startTimeParseMessage = microtime(true);
        $this->parseListMessageBodyToListForwardEvent();
        $totalTimeParseMessage = microtime(true) - $startTimeParseMessage;
        $totalTimeForwardEventHandling += $totalTimeParseMessage;
        $totalTimeParseMessage = number_format($totalTimeParseMessage, 1);
        self::logger('Total time of parse message', $totalTimeParseMessage. 's');

        // store list forward event to local disk
        $startTimeStoreForwardEventToLocal = microtime(true);
        $this->storeListForwardEventToLocal();
        $totalTimeStoreForwardEventToLocal = microtime(true) - $startTimeStoreForwardEventToLocal;
        $totalTimeForwardEventHandling += $totalTimeStoreForwardEventToLocal;
        $totalTimeStoreForwardEventToLocal = number_format($totalTimeStoreForwardEventToLocal, 1);
        self::logger('Total time of store list forward event to local disk', $totalTimeStoreForwardEventToLocal. 's');

        // store list forward event from local disk to amazon s3
        $startTimeStoreForwardEventFromLocalToS3 = microtime(true);
        $this->storeListForwardEvenStoredToS3();
        $totalTimeStoreForwardEventFromLocalToS3 = microtime(true) - $startTimeStoreForwardEventFromLocalToS3;
        $totalTimeForwardEventHandling += $totalTimeStoreForwardEventFromLocalToS3;
        $totalTimeStoreForwardEventFromLocalToS3 = number_format($totalTimeStoreForwardEventFromLocalToS3, 1);
        self::logger('Total time of store list forward event from local disk to amazon s3', $totalTimeStoreForwardEventFromLocalToS3. 's');
        // valid forward event
        $this->validListForwardEventStored();
        $totalCountForwardEventValid = count($this->listForwardEventValid);
        self::logger('Total of forward event valid', $totalCountForwardEventValid);

        // send forward event to next queue
        // store list forward event from local disk to amazon s3
        $startTimeSendForwardEventToSqs = microtime(true);
        $this->sendForwardEventValidToSqs();
        $totalTimeSendForwardEventToSqs = microtime(true) - $startTimeSendForwardEventToSqs;
        $totalTimeSendForwardEventToSqs = number_format($totalTimeStoreForwardEventFromLocalToS3, 1);
        self::logger('Total time of send forward event to next queue', $totalTimeSendForwardEventToSqs. 's');

        $totalTimeForwardEventHandling = number_format($totalTimeForwardEventHandling, 1);
        self::logger('Total time of forward event handling', $totalTimeForwardEventHandling . 's');

        // ram benchmark
        $memoryUsed = number_format(memory_get_usage() / 1048576, 1);
        $memoryPeakBefore = number_format(memory_get_peak_usage() / 1048576, 1);
        self::logger('Memory usage/peak before', $memoryUsed . " / " . $memoryPeakBefore);

        $end = date('d-m-Y H:i:s');
        self::logger('End time of forward event handling', $end);
        $this->nextRunning();
    }

    private function nextRunning() {
        $sleepingNumber = self::SLEEPING;
        //self::logger('sleeping', $sleepingNumber);
        //sleep(self::SLEEPING);
        $cmd = 'php app/console event_processing:forward_event_handling --env=prod >> app/logs/forward_event_handling.log 2>&1 &';
        shell_exec($cmd);
    }

    private function checkRunning()
    {
        if (!$this->settingCached->exists()) {
            return false;
        }
        if (!$status = $this->settingCached->hget(self::SETTING_KEY)) {
            return false;
        }
        if ($status != self::SETTING_VALUE_START) {
            return false;
        }

        return true;
    }

    private function checkCountMessageOnQueue($countMessageOnQueue)
    {
        if (
            !empty($countMessageOnQueue)
            && self::NUMBER_OF_RECEIVE_MESSAGE <= $countMessageOnQueue
        ) {
            return true;
        }

        return false;
    }

    private function receiveMessagesBodyFromQueue()
    {
        try {
            $this->listMessagesBody = $this->sqsWrapper->receiveMessagesBodyFromQueue(
                $this->queueName
                , self::NUMBER_OF_RECEIVE_MESSAGE
            );
        } catch(\Exception $e) {
            throw new \Exception($e->getMessage());
        }
        if (empty($this->listMessagesBody)) {
            throw new \Exception('No message body receive.');
        }
    }

    private function parseListMessageBodyToListForwardEvent()
    {
        foreach ($this->listMessagesBody as $key => $messageBody) {
            $this->listForwardEvent[] = $this->parseMessageBodyToForwardEvent($messageBody);
        }
    }

    private function parseMessageBodyToForwardEvent($messageBody)
    {
        $platform = '';
        if (preg_match("/id(\d+)/", $messageBody['app_id']) > 0) {
            $platform = Device::IOS_PLATFORM_NAME;
        } elseif (strpos($messageBody['app_id'], ".") !== false) {
            $platform = Device::ANDROID_PLATFORM_NAME;
        }
        $messageBody['event_type'] = 'aff_lsr' == $messageBody['type'] ? 'install' : 'in-app-event';
        $messageBody['app_version'] = isset($messageBody['version']) ? $messageBody['version'] : '';
        if (empty($messageBody['app_name'])) {
            $messageBody['app_name'] = $messageBody['app_id'];
        }
        $messageBody['platform'] = $platform;
        $messageBody['event_name'] = 'install';
        if (!empty($messageBody['goal_id'])) {
            $messageBody['event_name'] = $messageBody['goal_id'];
        }
        $messageBody['event_value'] = '';
        if (isset($messageBody['json'])) {
            $messageBody['event_value'] = $messageBody['json'];
        }
        if (!empty($messageBody['click_time'])) {
            if (is_numeric($messageBody['click_time'])) {
                $messageBody['click_time'] = date('Y-m-d H:i:s', $messageBody['click_time']);
            } elseif (is_string($messageBody['click_time'])) {
                $messageBody['click_time'] = date('Y-m-d H:i:s', strtotime($messageBody['click_time']));
            }
        }
        if (!empty($messageBody['install_time'])) {
            if (is_numeric($messageBody['install_time'])) {
                $messageBody['install_time'] = date('Y-m-d H:i:s', $messageBody['install_time']);
            } elseif (is_string($messageBody['install_time'])) {
                $messageBody['install_time'] = date('Y-m-d H:i:s', strtotime($messageBody['install_time']));
            }
        }
        if (!empty($messageBody['event_time'])) {
            if (is_numeric($messageBody['event_time'])) {
                $messageBody['event_time'] = date('Y-m-d H:i:s', $messageBody['event_time']);
            } elseif (is_string($messageBody['event_time'])) {
                $messageBody['event_time'] = date('Y-m-d H:i:s', strtotime($messageBody['event_time']));
            }
        }
        $messageBody['provider'] = $this->setProvider($messageBody);

        return $messageBody;
    }

    private function setProvider($messageBody)
    {
        $provider = 'APPSFLYER';
        if (!empty($messageBody['track_partner'])) {
            $trackPartner = strtoupper($messageBody['track_partner']);
            if (in_array($trackPartner, array_keys(Action::PROVIDERS))) {
                $provider = $trackPartner;
            }
        }
        return strtolower($provider);
    }

    private function storeListForwardEventToLocal()
    {
        foreach ($this->listForwardEvent as $key => $forwardEvent) {
            $forwardEventStored = $this->storeForwardEventToLocal($forwardEvent);
            $this->listForwardEventStored[$forwardEventStored['extra_data']['s3_log_file']] = $forwardEventStored;
        }
    }

    private function storeForwardEventToLocal($forwardEvent)
    {
        $appId = $forwardEvent['app_id'];
        $eventType = $forwardEvent['event_type'];
        $s3FolderMappping = $this->getS3FolderMapping();
        $dtEventTime = new \DateTime($forwardEvent['event_time']);
        $year  = $dtEventTime->format('Y');
        $month = $dtEventTime->format('m');
        $day   = $dtEventTime->format('d');
        $hour  = $dtEventTime->format('H');
        $minute= $dtEventTime->format('i');
        $appIdMapping = self::OTHER_FOLDER;
        if(array_key_exists($appId,$s3FolderMappping) ) {
            $appIdMapping = $s3FolderMappping[$appId];
        }
        $s3BucketFolder = $appIdMapping."/". $year ."/". $month ."/". $day ."/". $hour ."/". $minute;
        $eventTimeStamp = $dtEventTime->getTimestamp();
        $uniqueId = uniqid();
        $pathOnS3 = $s3BucketFolder.'/'.$forwardEvent['provider'].'_'.$appId.'_'.$eventType.'_'.$eventTimeStamp.'_'.$uniqueId;
        $path = $this->rawLogDir.'/'.$this->proccessId.'/'.$pathOnS3;
        $pathJson = $path.'.json';
        $pathJsonOnS3 = $pathOnS3.'.json';
        $this->getFS()->dumpFile($pathJson, json_encode($forwardEvent));
        $file = new File($pathJson);
        $filePathName = $file->getPathname();
        chmod($filePathName, 0777);
        self::logger('file exist', $filePathName.':'.file_exists($filePathName));

        $forwardEvent = $this->addExtraData(
            $forwardEvent
            , [
                's3_log_file' => $pathJsonOnS3
                , 'provider_id' => Action::PROVIDERS[strtoupper($forwardEvent['provider'])]
                , 'provider_name' => $forwardEvent['provider']
                , 'client_name' => $appIdMapping
                , 'app_id' => $appId
            ]
        );

        return $forwardEvent;
    }

    private function validListForwardEventStored()
    {
        foreach ($this->listForwardEventStored as $forwardEventStored) {
            $this->validForwardEventStored($forwardEventStored);
        }
    }


    private function validForwardEventStored($forwardEventStored)
    {
        $validator = $this->container->get('validator');
        $containsMessage = new ContainsMessage();
        $violations = $validator->validate($forwardEventStored, $containsMessage);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = $violation->getMessage();
            }
        } else {
            $forwardEventStored = $this->addExtraData($forwardEventStored, ['validate' => 1]);
            $this->listForwardEventValid[] = $forwardEventStored;
        }
    }

    private function sendForwardEventValidToSqs()
    {
        if (empty($this->queueNameNext)) {
            return;
        }
        if (empty($this->listForwardEventValid)) {
            throw new \Exception('No forward event need send to sqs.');
        }
        $this->sqsWrapper->sendMessageBatch(
            $this->queueNameNext,
            $this->listForwardEventValid
        );
    }

    private function storeListForwardEvenStoredToS3()
    {
        $manager = new \Aws\S3\Transfer($this->s3Client, $this->rawLogDir."/".$this->proccessId, 's3://'.$this->s3Bucket, [
            'before' => function (\Aws\Command $command) {
                if (in_array($command->getName(), ['PutObject', 'CreateMultipartUpload'])) {
                    $medata = [];
                    $forwardEventStored = $this->listForwardEventStored[$command['Key']];
                    foreach ($forwardEventStored as $key => $value) {
                        $medataKey = [
                            'event_name',
                            'event_type'
                            //'platform',
                            //'event_time',
                            //'advertising_id',
                            //'android_id',
                            //'idfa',
                            //'idfv',
                            //'app_id',
                            //'country_code'
                        ];
                        if(in_array($key,$medataKey)){
                            if ($value === null) {
                                $value = '';
                            }
                            $medata['x-amz-meta-'.$key] = (string)$value;
                        } else {
                            continue;
                        }
                    }
                    $command['Metadata'] = $medata;
                }
            },
        ]);
        $promise = $manager->promise();
        $self = $this;
        $promise->then(function () {
            $processLogDir = $this->rawLogDir.'/'.$this->proccessId;
            $fs = $this->getFS();
            $fs->remove($processLogDir);
            self::logger('delete local log folder', $processLogDir);
        });
        $metaPromise = \GuzzleHttp\Promise\all([$promise])->wait();
    }

    private function S3FolderMapping(){
        $appCached = new AppCached($this->container);

        return $appCached->hgetall();
    }

    private function getS3FolderMapping() {
        return $this->S3FolderMapping();
    }

    private function countMessageOnQueue()
    {
        $result = $this->sqsWrapper->getSQSClient()->createQueue(array('QueueName' => $this->queueName));
        $queueUrl = $result->get('QueueUrl');

        return $this->sqsWrapper->countMessageOnQueue($queueUrl);
    }

    private function getFS()
    {
        if (!$this->fs) {
            $this->fs = new Filesystem();
        }

        return $this->fs;
    }

    private function addExtraData($originData, $extraData)
    {
        if (isset($originData['extra_data'])) {
            $originData['extra_data'] = array_merge(
                $originData['extra_data'],
                $extraData
            );
        } else {
            $originData['extra_data'] = $extraData;
        }

        return $originData;
    }

    public static function logger($msg, $value, $type = 'info')
    {
        if (!empty($type) && $type == 'error') {
            echo $msg. ": ".$value. "\n";
        } else {
            //echo $msg. ": ".$value. "\n";
        }
    }
}