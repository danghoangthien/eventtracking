<?php

namespace Hyper\EventProcessingBundle\Service\Processor\AppLastActivityProcessor;

use Symfony\Component\DependencyInjection\ContainerInterface;

class AppLastActivityProcessor
{
    protected $container;
    protected $s3Wrapper;
    protected $redshiftWrapper;
    protected $uniqueId;
    protected $listMessageBody;
    protected $s3Url;
    protected $s3Bucket;
    protected $data;

    public function __construct(
        $container
        , $s3Wrapper
        , $redshiftWrapper
        , $uniqueId
        , $listMessageBody
        , $s3Url
        , $s3Bucket
    ) {
        $this->container = $container;
        $this->s3Wrapper = $s3Wrapper;
        $this->redshiftWrapper = $redshiftWrapper;
        $this->uniqueId = $uniqueId;
        $this->listMessageBody = $listMessageBody;
        $this->s3Url = $s3Url;
        $this->s3Bucket = $s3Bucket;
    }

    public function handle()
    {
        $this->data = $this->processData();
        if (empty($this->data)) {
            return;
        }
        $this->storeDataToS3Bucket();
        $this->storeDataToRedshift();
    }

    public function processData()
    {
        $listLastActivityByApp = [];
        foreach ($this->listMessageBody as $messageBody) {
            $appId = $messageBody['app_id'];
            $listLastActivityByApp[$appId] = time();
        }
        $listAppLastActivity = [];
        if (!empty($listLastActivityByApp)) {
            foreach($listLastActivityByApp as $appId => $lastActivity) {
                $listAppLastActivity[] = [
                    'app_id' => $appId
                    , 'app_title_id' => 'unknow'
                    , 'status' => 0
                    , 'last_activity' => $lastActivity
                ];
            }
        }

        return $listAppLastActivity;
    }

    public function storeDataToS3Bucket()
    {
        try {
            $this->s3Url = $this->s3Url . '/applications_platform.json';
            $s3Client = $this->s3Wrapper->getS3Client();
            $opts['s3'] = array(
             	'ContentType'=>'application/json',
            	'StorageClass'=>'REDUCED_REDUNDANCY'
            );
            // Register the stream wrapper from a client object
            $s3Client->registerStreamWrapper();
            $context = stream_context_create($opts);
            file_put_contents($this->s3Url, \Hyper\EventProcessingBundle\Service\Processor\Processor::makeRSCopySyntax($this->data), FILE_APPEND, $context);
        } catch(\Exception $e) {
            $this->log(
                $e,
                'store-data-to-s3-bucket',
                $this->listMessageBody
            );
            throw new \Exception($e->getMessage());
        }
    }

    public function storeDataToRedshift()
    {
        try {
            $connection = $this->redshiftWrapper->getConnection();
            $credentials = $this->redshiftWrapper->getCredentials();
            $jsonPath = "s3://{$this->s3Bucket}/jsonpaths/applications_platform.json";
            $connection->beginTransaction();
            $stmt = $connection->prepare("
                CREATE TABLE applications_platform_loaded (LIKE applications_platform);
            ")->execute();
            $stmt = $connection->prepare("
                COPY applications_platform_loaded
                FROM '{$this->s3Url}'
                CREDENTIALS {$credentials}
                JSON '$jsonPath';
            ")->execute();
            $stmt = $connection->prepare("
                UPDATE applications_platform
                    SET last_activity = applications_platform_loaded.last_activity
                FROM applications_platform_loaded
                WHERE applications_platform_loaded.app_id = applications_platform.app_id
                  AND (applications_platform.last_activity IS NULL OR applications_platform.last_activity < applications_platform_loaded.last_activity)
            ")->execute();
            $stmt = $connection->prepare("
                 DROP TABLE applications_platform_loaded;
            ")->execute();
            $connection->commit();
        } catch(\Exception $e) {
            $this->log(
                $e,
                'store-data-to-redshift',
                $this->listMessageBody
            );
            throw new \Exception($e->getMessage());
        }
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