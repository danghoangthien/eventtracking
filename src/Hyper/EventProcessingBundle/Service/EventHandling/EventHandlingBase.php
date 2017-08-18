<?php

namespace Hyper\EventProcessingBundle\Service\EventHandling;

use Symfony\Component\DependencyInjection\ContainerInterface,
    Symfony\Component\Filesystem\Filesystem,
    Aws\Result,
    Symfony\Component\HttpFoundation\File\File,
    Hyper\Domain\Setting\Setting,
    Hyper\EventBundle\Service\Cached\App\AppCached,
    Hyper\EventBundle\Service\Cached\Setting\SettingCached,
    GuzzleHttp\Client;

class EventHandlingBase
{
    const SLEEPING = 20;
    protected $container;
    protected $processorManager;
    protected $sqsWrapper;
    protected $s3Wrapper;
    protected $redshiftWrapper;
    protected $queueName;
    protected $queueNameNext;
    protected $messagesBody;
    protected $messagesBodyParsed = array();
    protected $listNewData = array();
    protected $listFileToWrite = array();
    protected $uniqueId;
    protected $s3Bucket;
    protected $s3Folder;
    protected $s3Url;
    protected $listTableName = array();
    protected $errorReturnedFromS3Bucket;
    protected $storedDataToRedshift;
    protected $rootDir;
    protected $dataDir;
    protected $fs;
    protected $sentDataToSqs;
    protected $settingRepo;
    protected $countMessageOnQueue;
    protected $appCached;
    protected $settingCached;
    protected $gzClient;


    public function __construct(
        ContainerInterface $container,
        $queueName,
        $queueNameNext,
        $s3Bucket
    ){
        $this->container = $container;
        $this->queueName = $queueName;
        $this->queueNameNext = $queueNameNext;
        $this->s3Bucket = $s3Bucket;
        $this->appCached = new AppCached($this->container);
        $this->settingCached = new SettingCached($this->container);
        $elasticaClient = new \Hyper\EventBundle\Service\HyperESClient($this->container);
        $this->gzClient = $elasticaClient->getClient();
        $this->initParams();
    }

    public function initParams()
    {
        $this->rootDir = $this->container->get('kernel')->getRootDir() . '/../';
        $dataDir = $this->rootDir.'web';
        $year  = date('Y');
        $month = date('m');
        $day   = date('d');
        $hour  = date('H');
        $minute= date('i');
        $this->uniqueId = uniqid();
        $this->s3Folder = $year.'/'.$month.'/'.$day.'/'.$hour.'/'.$minute.'/'.$this->uniqueId;
        $this->s3Url = 's3://'.$this->s3Bucket.'/'.$this->s3Folder;
        $this->dataDir = $dataDir.'/'.$this->s3Bucket.'/'.$this->s3Folder;
        $this->sqsWrapper = $this->container
            ->get('hyper_event_processing.sqs_wrapper');
        $this->processorManager = $this->container
            ->get('hyper_event_processing.processor_manager');
        $this->s3Wrapper = $this->container
            ->get('hyper_event_processing.s3_wrapper');
        $this->redshiftWrapper = $this->container
            ->get('hyper_event_processing.redshift_wrapper');
        $this->settingRepo = $this->container->get('setting_repository');
        $this->resetProperties();
    }

    public function resetProperties()
    {
        $this->messagesBodyParsed = array();
        $this->listNewData = array();
        $this->listFileToWrite = array();
        $this->listTableName = array();
        $this->errorReturnedFromS3Bucket = '';
        $this->storedDataToRedshift = '';
        $this->sentDataToSqs = false;
    }

    public function run()
    {

        $this->initParams();
        echo "\nRunning id: {$this->uniqueId} \n";
        $start = date('d-m-Y H:i:s');
        if(!$this->checkRunning()) {
            throw new \Exception('Processing is stoping.');
            exit;
        };
        $minMessage = $this->minNumberOfReceiveMessage;
        $maxMessage = $this->maxNumberOfReceiveMessage;
        echo "Min of received message: {$minMessage} \n";
        echo "Max of received message: {$maxMessage} \n";
        if (!$this->checkReceivedMessageOnQueue()) {
            sleep(self::SLEEPING);
            $this->nextRunning();
        }
        $totalTimeHorizontalProcess = 0;
        echo "Start time of horizontal process: {$start} \n";

        try {
            $startTimeReceiveMessage = microtime(true);
            $messageCount = $this->receiveMessagesBodyFromQueue();
            $totalTimeReceiveMessage = microtime(true) - $startTimeReceiveMessage;
            $totalTimeReceiveMessage = number_format($totalTimeReceiveMessage, 1);
            $totalMessageBody = count($this->messagesBody);
            echo "Total of received message: {$totalMessageBody} \n";
            echo "Total time of receive message: {$totalTimeReceiveMessage}s \n";
            $totalTimeHorizontalProcess += $totalTimeReceiveMessage;
        } catch(\Exception $e) {
            throw new \Exception($e->getMessage());
            exit;
        }

        try {
            $startTimeValidMessageBody = microtime(true);
            $this->validMessageBody();
            $totalTimeValidMessageBody = microtime(true) - $startTimeValidMessageBody;
            $totalTimeValidMessageBody = number_format($totalTimeValidMessageBody, 1);
            echo "Total time of valid received message: {$totalTimeValidMessageBody}s \n";
            $totalMessageBody = count($this->messagesBody);
            echo "Total of valid message need to process: {$totalMessageBody} \n";
            $totalTimeHorizontalProcess += $totalTimeValidMessageBody;
        } catch(\Exception $e) {
            echo $e->getMessage();
            $this->nextRunning();
            return;
        }

        $startTimeProcessor = microtime(true);
        $this->processorManager->setMessagesBody($this->messagesBody);
        $this->processorManager->resetProcessor();
        $this->processData();
        $totalTimeProcessor = microtime(true) - $startTimeProcessor;
        $totalTimeProcessor = number_format($totalTimeProcessor, 1);
        echo "Total time of process data: {$totalTimeProcessor}s \n";
        $totalTimeHorizontalProcess += $totalTimeProcessor;

        try {
            $startTimeStoreDataToS3 = microtime(true);
            $this->storeDataToS3Bucket();
            $totalTimeStoreDataToS3 = microtime(true) - $startTimeStoreDataToS3;
            $totalTimeStoreDataToS3 = number_format($totalTimeStoreDataToS3, 1);
            echo "Total time of store data to AWS S3: {$totalTimeStoreDataToS3}s \n";
            $totalNewData = count($this->listNewData);
            echo "Total of data to need to store to AWS Redshift: {$totalNewData} \n";
            $totalTimeHorizontalProcess += $totalTimeStoreDataToS3;

            $startTimeStoreDataToRedshift = microtime(true);
            $this->storeDataToRedshift();
            $totalTimeStoreDataToRedshift = microtime(true) - $startTimeStoreDataToRedshift;
            $totalTimeStoreDataToRedshift = number_format($totalTimeStoreDataToRedshift, 1);
            echo "Total time of store data to AWS Redshift: {$totalTimeStoreDataToRedshift}s \n";
            $totalTimeHorizontalProcess += $totalTimeStoreDataToRedshift;

            $startTimeStoreDataToES = microtime(true);
            $this->storeDataToElasticSearch();
            $totalTimeStoreDataToES = microtime(true) - $startTimeStoreDataToES;
            $totalTimeStoreDataToES = number_format($totalTimeStoreDataToES, 1);
            echo "Total time of store data to AWS Elastic Search: {$totalTimeStoreDataToES}s \n";
            $totalTimeHorizontalProcess += $totalTimeStoreDataToES;


            $startTimeStoreDataToSqs = microtime(true);
            $this->sendDataToSqs();
            $totalTimeStoreDataToSqs = microtime(true) - $startTimeStoreDataToSqs;
            $totalTimeStoreDataToSqs = number_format($totalTimeStoreDataToSqs, 1);
            echo "Total time of send data to AWS SQS: {$totalTimeStoreDataToSqs}s \n";
            $totalMessageBodyParsed = count($this->messagesBodyParsed);
            echo "Total of data to need to send to AWS SQS: {$totalMessageBodyParsed} \n";
            $this->afterSendDataToSqs();
            $totalTimeHorizontalProcess += $totalTimeStoreDataToSqs;

        } catch(\Exception $e) {
            echo $e->getMessage();
            $this->nextRunning();
            return;
        }

        echo "Total time of horizontal process: {$totalTimeHorizontalProcess}s \n";
        $end = date('d-m-Y H:i:s');
        echo "End time of horizontal process: {$end} \n";
        $memoryUsed = number_format(memory_get_usage() / 1048576, 1);
        $memoryPeakBefore = number_format(memory_get_peak_usage() / 1048576, 1);
        echo "Memory usage/peak before: " . $memoryUsed . " / " . $memoryPeakBefore . " MB \n";
        echo "-----------------------------\n";
        $this->nextRunning();

    }

    public function checkRunning()
    {
        if (!$this->settingCached->exists()) {
            return false;
        }
        if (!$status = $this->settingCached->hget($this->getSettingKey())) {
            return false;
        }
        if ($status != Setting::STATUS_START_VALUE) {
            return false;
        }

        return true;
    }

    public function receiveMessagesBodyFromQueue()
    {
        try {
            $this->messagesBody = $this->sqsWrapper->receiveMessagesBodyFromQueue(
                $this->queueName,
                $this->maxNumberOfReceiveMessage
            );
        } catch(\Exception $e) {
            $this->log(
                $e,
                'messages-received',
                $this->messagesBody
            );
            throw new \Exception($e->getMessage());
        }

        if (empty($this->messagesBody)) {
            throw new \Exception('No message body receive.');
        }
    }

    public function storeDataToS3Bucket()
    {
        if (empty($this->listNewData)) {
            return;
        }
        try {
            foreach($this->listNewData as $newData) {
                foreach($newData as $tableName => $data) {
                    $this->listFileToWrite[$tableName][] = $data;
                }
            }
            foreach($this->listFileToWrite as $tableName => $data) {
                $fs = $this->getFS();
                $path = $this->dataDir.'/'. $tableName;
                $pathJson = $path.'.json';
                $pathGz = $path.'.gz';
                $fs->dumpFile($pathJson, \Hyper\EventProcessingBundle\Service\Processor\Processor::makeRSCopySyntax($data));
                $file = new File($pathJson);
                $filePathName = $file->getPathname();
                $gzFilePathName = $pathGz;
                file_put_contents($gzFilePathName, gzencode(file_get_contents($filePathName), 9));
                chmod($gzFilePathName, 0777);
                $gzFile = new File($gzFilePathName);
                $gzFileName = $gzFile->getBasename();
                if ($gzFileName) {
                    // remove json data
                    $fs->remove($filePathName);
                    $this->listTableName[$tableName] = array(
                        'source' => $this->s3Url.'/'.$gzFileName,
                        'jsonpath' => 's3://'.$this->s3Bucket.'/jsonpaths/'.$tableName.'.json'
                    );
                }

            }
            $this->errorReturnedFromS3Bucket = $this->s3Wrapper
            ->transfer($this->dataDir, 's3://'.$this->s3Bucket.'/'.$this->s3Folder);
            if ($this->errorReturnedFromS3Bucket) {
                throw new \Exception($this->errorReturnedFromS3Bucket);
            }

        } catch(\Exception $e) {
            $this->log(
                $e,
                'store-data-to-s3-bucket',
                $this->messagesBody
            );
            throw new \Exception($e->getMessage());
        }
    }

    public function storeDataToRedshift()
    {
        if (empty($this->listTableName)) {
            return;
        }
        try {
            $this->storedDataToRedshift = $this->redshiftWrapper->copyDataSource($this->listTableName);
            // delete folder data stored
            $this->getFS()->remove($this->rootDir.'web'.'/'.$this->s3Bucket);
        } catch(\Exception $e) {
            $this->sendAlertToEmail('[Horizontal Process][Alert] COPY TO RS ' . date('Y-m-d H:i:s'), $e->getMessage());
            $this->log(
                $e,
                'store-data-to-redshift',
                $this->messagesBody
            );
            throw new \Exception($e->getMessage());
        }

    }

    public function storeDataToElasticSearch()
    {

    }

    public function sendDataToSqs()
    {
        if (empty($this->queueNameNext)) {
            return;
        }
        if (empty($this->messagesBodyParsed)) {
            throw new \Exception('No message body to send to sqs.');
        }
        try {
            $result = $this->sqsWrapper->sendMessageBatch(
                $this->queueNameNext,
                $this->messagesBodyParsed
            );
        } catch(\Exception $e) {
            $this->log(
                $e,
                'send-data-to-sqs',
                $this->messagesBody
            );
            throw new \Exception($e->getMessage());
        }

        $this->sentDataToSqs = true;
    }

    protected function getFS()
    {
        if (!$this->fs) {
            $this->fs = new Filesystem();
        }

        return $this->fs;
    }

    public function nextRunning() {
        try {
            $this->run();
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    protected function makeRSCopySyntax($data)
    {
        $jsonOutput = '';
        foreach ($data as $item) {
            $jsonOutput .= json_encode($item);
        }
        $jsonOutput = str_replace('\\u0000', "", $jsonOutput);

        return $jsonOutput;
    }

    private function countMessageOnQueue()
    {
        $result = $this->sqsWrapper->getSQSClient()->createQueue(array('QueueName' => $this->queueName));
        $queueUrl = $result->get('QueueUrl');
        $countMessageOnQueue = $this->sqsWrapper->countMessageOnQueue($queueUrl);
        echo "Total of message on queue: {$this->countMessageOnQueue} \n";

        return $countMessageOnQueue;
    }

    public function checkReceivedMessageOnQueue()
    {
        $this->countMessageOnQueue = $this->countMessageOnQueue();
        if (
            $this->minNumberOfReceiveMessage <= $this->countMessageOnQueue &&
            $this->minNumberOfReceiveMessage < $this->maxNumberOfReceiveMessage
        ) {
            return true;
        }

        return false;
    }

    public function afterSendDataToSqs()
    {

    }

    protected function makeJsonES($index, $type, $documents)
    {
        $json = '';
        foreach ($documents as $document) {
            $entry = json_encode(
                [
                    'index' => [
                        '_index' => $index
                        , '_type' => $type
                        , '_id' => $document['id']
                    ]
                ]

            ) . PHP_EOL;
            $json .= $entry . json_encode($document) . PHP_EOL;
        }

        return $json;
    }

    protected function checkIndexES($esIndexEndpoint)
    {
        if (!$this->gzClient->getIndex($esIndexEndpoint)->exists()) {
            return false;
        }

        return true;
    }

    protected function sendAlertToEmail($subject, $alert)
    {
        $from = $this->container->getParameter('mailer_from');
        $fromName = $this->container->getParameter('mailer_from_name');
        $message = \Swift_Message::newInstance()
                ->setSubject($subject)
                ->setFrom(array($from => $fromName))
                ->setTo($from)
                ->setBody($alert,'text/plain');

        return $this->container->get('mailer')->send($message);
    }
}