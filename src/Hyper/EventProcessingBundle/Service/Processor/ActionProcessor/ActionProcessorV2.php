<?php

namespace Hyper\EventProcessingBundle\Service\Processor\ActionProcessor;

use Symfony\Component\DependencyInjection\ContainerInterface,
    Hyper\Domain\Action\Action,
    Hyper\EventBundle\Service\Cached\InappeventConfig\InappeventConfigCached,
    Hyper\EventBundle\Service\Cached\Currency\CurrencyCached,
    Hyper\EventBundle\Service\Cached\App\AppCached;

class ActionProcessorV2
{
    const USD_CURRENCY = 'USD';
    protected $actionRepository;
    protected $ieaConfigCached;
    protected $currencyCached;

    protected $container;
    protected $s3Wrapper;
    protected $redshiftWrapper;
    protected $uniqueId;
    protected $listMessageBody;
    protected $s3Url;
    protected $s3Bucket;
    protected $data;
    protected $esClient;
    protected $appCached;

    public function __construct(
        ContainerInterface $container
        , $s3Wrapper
        , $redshiftWrapper
        , $uniqueId
        , $listMessageBody
        , $s3Url
        , $s3Bucket
        , $esClient
        , $actionRepository
    ) {
        $this->container = $container;
        $this->s3Wrapper = $s3Wrapper;
        $this->redshiftWrapper = $redshiftWrapper;
        $this->uniqueId = $uniqueId;
        $this->listMessageBody = $listMessageBody;
        $this->s3Url = $s3Url;
        $this->s3Bucket = $s3Bucket;
        $this->esClient = $esClient;
        $this->ieaConfigCached = new InappeventConfigCached($this->container);
        $this->currencyCached = new CurrencyCached($this->container);
        $this->actionRepository = $actionRepository;
        $this->appCached = new AppCached($this->container);
    }

    public function handle()
    {
        $totalTimeHorizontalProcess = 0;
        $startTimeProcessor = microtime(true);
        $this->data = $this->processData();
        if (empty($this->data)) {
            return;
        }
        $totalTimeProcessor = microtime(true) - $startTimeProcessor;
        $totalTimeProcessor = number_format($totalTimeProcessor, 1);
        echo "Total time of process data: {$totalTimeProcessor}s \n";
        $totalTimeHorizontalProcess += $totalTimeProcessor;
        $startTimeStoreDataToS3 = microtime(true);
        $this->storeDataToS3Bucket();
        $totalTimeStoreDataToS3 = microtime(true) - $startTimeStoreDataToS3;
        $totalTimeStoreDataToS3 = number_format($totalTimeStoreDataToS3, 1);
        echo "Total time of store data to AWS S3: {$totalTimeStoreDataToS3}s \n";
        $totalNewData = count($this->data);
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

        return [$this->listMessageBody, $this->data];

    }

    protected function processData()
    {
        $listAction = [];
        foreach ($this->listMessageBody as $key => $messageBody) {
            $identifier = $this->geIdentifierFromMessageBody($messageBody);
            $extraData = array(
                'action_id' => ''
            );
            $newData = $this->parseMessageBodyToData($messageBody);
            $extraData['action_id'] = $newData['id'];
            $extraDataMerged = $this->addExtraData($messageBody, $extraData);
            $this->listMessageBody[$key] = $extraDataMerged;
            $listAction[] = $newData;
        }

        $listAction = $this->removeDuplicateNewData($listAction);

        return $listAction;
    }

    public function storeDataToS3Bucket()
    {
        try {
            $this->s3Url = $this->s3Url . '/actions.gz';
            $s3Client = $this->s3Wrapper->getS3Client();
            $opts['s3'] = array(
            //  	'ContentType'=>'application/gzip',
            //  	'header' => [
            //  	    'Content-Encoding' => 'gzip'
            //  	],
            	'StorageClass'=>'REDUCED_REDUNDANCY'
            );
            // Register the stream wrapper from a client object
            $s3Client->registerStreamWrapper();
            $context = stream_context_create($opts);
            file_put_contents($this->s3Url, gzencode(\Hyper\EventProcessingBundle\Service\Processor\Processor::makeRSCopySyntax($this->data), 9), FILE_APPEND, $context);
        } catch(\Exception $e) {
            $this->log(
                $e,
                'store-data-to-s3-bucket',
                $this->listMessageBody
            );
            throw new \Exception($e->getMessage());
        }
    }

    protected function storeDataToRedshift()
    {
        try {
            $connection = $this->redshiftWrapper->getConnection();
            $credentials = $this->redshiftWrapper->getCredentials();
            $jsonPath = "s3://{$this->s3Bucket}/jsonpaths/actions.json";
            $connection->beginTransaction();
            $stmt = $connection->prepare("
                set query_group to 'ak_low_priority_long_processing_time';
            ")->execute();
            $stmt = $connection->prepare("
                DROP TABLE IF EXISTS actions_loaded;
            ")->execute();
            $stmt = $connection->prepare("
                CREATE TABLE IF NOT EXISTS actions_loaded (LIKE actions);
            ")->execute();
            $stmt = $connection->prepare("
                COPY actions_loaded
                FROM '{$this->s3Url}'
                CREDENTIALS {$credentials}
                JSON '$jsonPath' gzip
                ACCEPTINVCHARS
                TRUNCATECOLUMNS;
            ")->execute();
            $stmt = $connection->prepare("
                DELETE FROM actions WHERE id IN (SELECT id FROM actions_loaded);
            ")->execute();
            $stmt = $connection->prepare("
                 INSERT INTO actions SELECT * FROM actions_loaded;
            ")->execute();
            $stmt = $connection->prepare("
                 DROP TABLE actions_loaded;
            ")->execute();

            $stmt = $connection->prepare("
            reset query_group;
            ")->execute();
            $connection->commit();
        } catch(\Exception $e) {
            $connection->rollback();
            $this->sendAlertToEmail('[Horizontal Process][Alert] COPY TO RS ' . date('Y-m-d H:i:s'), $e->getMessage());
            $this->log(
                $e,
                'store-data-to-redshift',
                $this->listMessageBody
            );
            throw new \Exception($e->getMessage());
        }
    }

    protected function storeDataToElasticSearch()
    {
        try {
            $esParameters = $this->container->getParameter('amazon_elasticsearch');
            // collect index
            $indices = [];
            foreach ($this->data as $action) {
                if (empty($this->appCached->hget($action['app_id']))) {
                    continue;
                }
                $index = $this->appCached->hget($action['app_id']);
                $indices[$index][$action['app_id']][] = $action;
            }
            if (!empty($indices)) {
                foreach ($indices as $index => $listAppId) {
                    $index = strtolower($index) . '_'. $esParameters['index_version'];
                    if (!$this->esClient->getIndex($index)->exists() ) {
                        continue;
                    }
                    foreach ($listAppId as $appId => $listAction) {
                        if (empty($listAction)) {
                            continue;
                        }
                        $countRow = count($listAction);
                        $documents = [];
                        $batch = 500;
                        $i = 0;
                        foreach ($listAction as $action) {
                            $i++;
                            $document = new \Elastica\Document(
                                $action['id']
                                , $action
                            );
                            $documents[] = $document;
                            if (
                                (($i % $batch) == 0 && $i != 0)
                                || ($i + 1 == $countRow)
                            ) {
                                $bulk = new \Elastica\Bulk($this->esClient);
                                $bulk->setIndex($index);
                                $bulk->setType($appId);
                                $bulk->addDocuments($documents);
                                $bulk->send();
                                $documents = [];
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->log(
                $e
                , 'es-bulk-android-devices-fail'
                , $this->data
            );
        }
    }

    public function parseMessageBodyToData($messageBody)
    {
        $deviceId = $messageBody['extra_data']['device_id'];
        $applicationId = $messageBody['extra_data']['application_id'];
        $appId = $messageBody['extra_data']['app_id'];
        $providerId = $messageBody['extra_data']['provider_id'];
        $actionType = $this->getActionType($messageBody);
        $happenedAt = strtotime($messageBody['event_time']);
        $actionId = $this->geIdentifierFromMessageBody($messageBody);
        if (!empty($messageBody['event_value']) && !is_array($messageBody['event_value'])) {
            $eventValue = json_decode($messageBody['event_value'], true);
            if(json_last_error() === JSON_ERROR_NONE) {
                $messageBody['event_value'] = $eventValue;
            }
        }
        $s3LogFile = '';
        if (isset($messageBody['extra_data']['s3_log_file'])) {
            $s3LogFile = $messageBody['extra_data']['s3_log_file'];
        }
        $created = time();

        $eventValueParams = array(
            'af_revenue' => '',
            'af_price' => '',
            'af_level' => '',
            'af_success' => '',
            'af_content_type' => '',
            'af_content_list' => '',
            'af_content_id' => '',
            'af_currency' => '',
            'af_registration_method' => '',
            'af_quantity' => '',
            'af_payment_info_available' => '',
            'af_rating_value' => '',
            'af_max_rating_value' => '',
            'af_search_string' => '',
            'af_description' => '',
            'af_score' => '',
            'af_destination_a' => '',
            'af_destination_b' => '',
            'af_class' => '',
            'af_date_a' => '',
            'af_date_b' => '',
            'af_event_start' => '',
            'af_event_end' => '',
            'af_lat' => '',
            'af_long' => '',
            'af_customer_user_id' => '',
            'af_validated' => '',
            'af_receipt_id' => '',
            'af_param_1' => '',
            'af_param_2' => '',
            'af_param_3' => '',
            'af_param_4' => '',
            'af_param_5' => '',
            'af_param_6' => '',
            'af_param_7' => '',
            'af_param_8' => '',
            'af_param_9' => '',
            'af_param_10' => '',
            'event_value_text' => ''
        );
        foreach ($eventValueParams as $key => $value) {
            if (isset($messageBody['event_value'][$key])) {
                $eventValueParams[$key] = $messageBody['event_value'][$key];
            }
        }
        if (is_bool($eventValueParams['af_success'])) {
            $eventValueParams['af_success'] = (int) $eventValueParams['af_success'];
        }
        if (is_bool($eventValueParams['af_payment_info_available'])) {
            $eventValueParams['af_payment_info_available'] = (int) $eventValueParams['af_payment_info_available'];
        }
        if(!is_array($messageBody['event_value'])) {
            $eventValueParams['event_value_text'] = var_export($messageBody['event_value'], true);
        }

        if (!empty($eventValueParams['af_param_2'])) {
            $eventValueParams['af_param_2'] = substr($eventValueParams['af_param_2'], 0, 255);
        }
        if (!empty($eventValueParams['af_receipt_id'])) {
            $eventValueParams['af_receipt_id'] = substr($eventValueParams['af_receipt_id'], 0, 255);
        }

        /**
         * https://hyperdev.atlassian.net/browse/BOB-223
         **/
        if (
            empty($eventValueParams['af_currency']) &&
            (
                !empty($eventValueParams['af_price']) ||
                !empty($eventValueParams['af_revenue'])
            )
        ) {
            $eventValueParams['af_currency'] = $messageBody['currency'];
            if (empty($eventValueParams['af_currency'])) {
                $eventValueParams['af_currency'] = self::USD_CURRENCY;
            }
        }
        $amountUSD = 0;
        $appId = '';
        if (!empty($messageBody['app_id'])) {
            $appId = $messageBody['app_id'];
        }
        $eventName = '';
        if (!empty($messageBody['event_name'])) {
            $eventName = $messageBody['event_name'];
            //Hotfix trim message :
            $eventName = substr($eventName,0,45);
        }
        if ($this->checkEventTagAsIAP($appId, $eventName)) {
            $currency = '';
            if (!empty($eventValueParams['af_currency'])) {
                $currency = $eventValueParams['af_currency'];
            }
            $amount = 0;
            if (!empty($eventValueParams['af_revenue'])) {
                $amount = $eventValueParams['af_revenue'];
            }
            if ($currency && $amount) {
                $amountUSD = $this->convertToUSD($currency, $amount);
            }
        }
        $ret = array(
            'id' => $actionId,
            'device_id' => $deviceId,
            'application_id' => $applicationId,
            'action_type' => $actionType,
            'behaviour_id' => 0,
            'provider_id'=> $providerId,
            's3_log_file' => $s3LogFile,
            'happened_at'=> $happenedAt,
            'created' => $created,
            'app_id' => $appId
        );
        $ret = array_merge($ret, $eventValueParams);
        $ret['event_name'] = $eventName;
        $ret['amount_usd'] = $amountUSD;

        return $ret;
    }

    protected function addExtraDataIntoMessagesBody()
    {
        if (empty($this->messagesBody)) {
            return;
        }
        foreach ($this->messagesBody as $key => $messageBody) {
            $identifier = $this->geIdentifierFromMessageBody($messageBody);
            $extraData = array(
                'action_id' => ''
            );
            $identifier = $this->isNewIdentifier($identifier);
            if (!$identifier) {
                $newData = $this->parseMessageBodyToData($messageBody);
                $extraData['action_id'] = $newData['actions']['id'];
                $this->listNewData[] = $newData;
            } else {
                $extraData['action_id'] = $identifier;
            }
            $extraDataMerged = $this->addExtraData($messageBody, $extraData);
            $this->messagesBody[$key] = $extraDataMerged;
            unset($identifier);
            unset($extraData);
            unset($extraDataMerged);
        }
    }

    protected function geIdentifierFromMessageBody($messageBody)
    {
        $happenedAt = strtotime($messageBody['event_time']);
        $identifier = array(
            $messageBody['extra_data']['device_id'],
            $messageBody['extra_data']['application_id'],
            $messageBody['event_name'],
            $happenedAt
        );

        return $this->convertIdentifierToMD5($identifier);
    }

    protected function convertIdentifierToMD5($identifier)
    {
        return md5(implode("", $identifier));
    }

    protected function getActionType($messageBody)
    {
        $actionType = '';
        $behaviourId = '';
        $providerId = '';
        if ($messageBody['event_type'] == 'install') {
            $actionType = Action::ACTION_TYPES['INSTALL_ACTION_TYPE'];
        } else if ($messageBody['event_type'] == 'in-app-event') {
            $actionType = Action::ACTION_TYPES['IN_APP_EVENT_ACTION_TYPE'];
        }

        return $actionType;
    }

    private function checkEventTagAsIAP($appId, $eventName)
    {
        if (!$this->ieaConfigCached->exists()) {
            return false;
        }
        $iaeConfig = $this->ieaConfigCached->hget($appId);
        if (!$iaeConfig) {
            return false;
        }
        $iaeConfig = json_decode($iaeConfig, true);
        if (empty($iaeConfig) || empty($iaeConfig[$eventName]['tag_as_iap'])) {
            return false;
        }

        return true;
    }

    public function convertToUSD($currency, $amount)
    {
        if (!$this->currencyCached->exists()) {
            return 0;
        }
        $rate = $this->currencyCached->hget(strtolower($currency));
        $money = (float) ($amount/$rate);
        $money = round($money, 2);

        return $money;
    }

    public function addExtraData($originData, $extraData)
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

    protected function log(\Exception $e, $prefix, $content)
    {
         $this->container
            ->get('hyper_event_processing.logger_wrapper')->log(
                $e,
                $this->s3Bucket,
                $prefix,
                $content
            );
    }

    protected function removeDuplicateNewData($listAction)
    {
        if (!$listAction) {
            return;
        }
        $checkArr = array();
        $listNewData = array();
        foreach ($listAction as $newData) {
            $value = $newData['id'];
            if (!in_array($value, $checkArr)) {
                $listNewData[] = $newData;
            }
            $checkArr[] = $value;
        }

        return $listNewData;
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