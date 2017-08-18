<?php

namespace Hyper\EventProcessingBundle\Service\EventHandling;

use Symfony\Component\DependencyInjection\ContainerInterface,
    Hyper\EventProcessingBundle\Service\EventHandling\EventHandlingBase,
    Hyper\EventProcessingBundle\Service\EventHandling\EventHandlingInterface,
    Hyper\Domain\Setting\Setting,
    Hyper\EventProcessingBundle\Service\Processor\InappeventProcessor\InappeventProcessor,
    Hyper\EventProcessingBundle\Service\Processor\AppLastActivityProcessor\AppLastActivityProcessor,
    Hyper\EventProcessingBundle\Service\Processor\DeviceAppInfoProcessor\DeviceAppInfoProcessor;

class PostEventHandling extends EventHandlingBase implements EventHandlingInterface
{
    protected $maxNumberOfReceiveMessage = 3000;
    protected $minNumberOfReceiveMessage = 2000;
    protected $listDeviceAppInfo;

    public function __construct(
        ContainerInterface $container,
        $queueName
        , $bucketName
    ) {
        parent::__construct($container, $queueName, '', $bucketName);
    }

    public function processData()
    {
        $inappeventProcessor = new InappeventProcessor($this->container, $this->messagesBody);
        echo "Processor: ". get_class($inappeventProcessor) . "\n";
        $inappeventProcessor->process();
        $deviceAppInfoProcessor = new DeviceAppInfoProcessor($this->container, $this->messagesBody);
        echo "Processor: ". get_class($deviceAppInfoProcessor) . "\n";
        $this->listDeviceAppInfo = $deviceAppInfoProcessor->process();
        $appLastActivityProcessor = new AppLastActivityProcessor(
            $this->container
            , $this->s3Wrapper
            , $this->redshiftWrapper
            , $this->uniqueId
            , $this->messagesBody
            , $this->s3Url
            , $this->s3Bucket
        );
        echo "Processor: ". get_class($appLastActivityProcessor) . "\n";
        $appLastActivityProcessor->handle();
    }

    public function storeDataToS3Bucket()
    {
        try {
            $this->s3Url = $this->s3Url . '/device_app_information.json';
            $s3Client = $this->s3Wrapper->getS3Client();
            $opts['s3']=array(
             	'ContentType'=>'application/json',
            	'StorageClass'=>'REDUCED_REDUNDANCY'
            );
            // Register the stream wrapper from a client object
            $s3Client->registerStreamWrapper();
            $context = stream_context_create($opts);
            file_put_contents($this->s3Url, $this->makeRSCopySyntax($this->listDeviceAppInfo), FILE_APPEND, $context);
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
        if (empty($this->listDeviceAppInfo)) {
            return;
        }
        try {
            $connection = $this->redshiftWrapper->getConnection();
            $credentials = $this->redshiftWrapper->getCredentials();
            $jsonPath = "s3://{$this->s3Bucket}/jsonpaths/device_app_information.json";
            $connection->beginTransaction();
            $stmt = $connection->prepare("
                CREATE TABLE device_app_information_loaded (LIKE device_app_information);
            ")->execute();
            $stmt = $connection->prepare("
                COPY device_app_information_loaded
                FROM '{$this->s3Url}'
                CREDENTIALS {$credentials}
                JSON '$jsonPath';
            ")->execute();
            $stmt = $connection->prepare("
                UPDATE device_app_information
                    SET install_time = dail.install_time
                FROM device_app_information_loaded dail
                WHERE device_app_information.device_id = dail.device_id
                    AND device_app_information.app_id = dail.app_id
                    AND (device_app_information.install_time IS NULL OR device_app_information.install_time < dail.install_time)
            ")->execute();
            $stmt = $connection->prepare("
                UPDATE device_app_information
                    SET last_activity = dail.last_activity
                FROM device_app_information_loaded dail
                WHERE device_app_information.device_id = dail.device_id
                    AND device_app_information.app_id = dail.app_id
                    AND (device_app_information.last_activity IS NULL OR device_app_information.last_activity < dail.last_activity)
            ")->execute();
            $stmt = $connection->prepare("
                INSERT INTO device_app_information
                SELECT dail.* FROM device_app_information_loaded dail LEFT JOIN device_app_information
                ON (device_app_information.device_id = dail.device_id AND device_app_information.app_id = dail.app_id)
                WHERE device_app_information.device_id IS NULL;
            ")->execute();
            $stmt = $connection->prepare("
                 DROP TABLE device_app_information_loaded;
            ")->execute();
            $connection->commit();
        } catch(\Exception $e) {
            $this->log(
                $e,
                'store-data-to-redshift',
                $this->messagesBody
            );
            throw new \Exception($e->getMessage());
        }
    }

    public function validMessageBody()
    {

    }

    public function getSettingKey()
    {
        return Setting::POST_EVENT_HANDLING_TYPE_KEY;
    }

    public function log(\Exception $e, $prefix, $content)
    {
         $this->container
            ->get('hyper_event_processing.logger_wrapper')->log(
                $e,
                $this->s3Bucket,
                $prefix,
                $content
            );
    }
}