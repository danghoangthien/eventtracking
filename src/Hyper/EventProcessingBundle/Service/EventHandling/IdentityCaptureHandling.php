<?php

namespace Hyper\EventProcessingBundle\Service\EventHandling;

use Symfony\Component\DependencyInjection\ContainerInterface,
    Hyper\EventProcessingBundle\Service\EventHandling\EventHandlingBase,
    Hyper\EventProcessingBundle\Service\EventHandling\EventHandlingInterface,
    Hyper\Domain\Setting\Setting,
    Hyper\EventProcessingBundle\Service\Processor\DeviceProcessor\EmailDeviceProcessor;

class IdentityCaptureHandling extends EventHandlingBase implements EventHandlingInterface
{

    private $esClient;
    protected $maxNumberOfReceiveMessage = 200;
    protected $minNumberOfReceiveMessage = 100;

    public function __construct(
        ContainerInterface $container,
        $queueName,
        $bucketName
    ) {
        parent::__construct($container, $queueName, '', $bucketName);
    }

    public function getSettingKey()
    {
        return Setting::IDENTITY_CAPTURE_HANDLING_TYPE_KEY;
    }

    public function processData()
    {

    }

    public function validMessageBody()
    {

    }

    public function storeDataToS3Bucket()
    {
        try {
            $this->s3Url = $this->s3Url . '/identity_capture.json';
            $s3Client = $this->s3Wrapper->getS3Client();
            $opts['s3']=array(
             	'ContentType'=>'application/json',
            	'StorageClass'=>'REDUCED_REDUNDANCY'
            );
            // Register the stream wrapper from a client object
            $s3Client->registerStreamWrapper();
            $context = stream_context_create($opts);
            file_put_contents($this->s3Url, $this->makeRSCopySyntax($this->messagesBody), FILE_APPEND, $context);
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
        try {
            $connection = $this->redshiftWrapper->getConnection();
            $credentials = $this->redshiftWrapper->getCredentials();
            $jsonPath = "s3://{$this->s3Bucket}/jsonpaths/identity_capture.json";
            $connection->beginTransaction();
            $stmt = $connection->prepare("
                CREATE TABLE identity_capture_loaded (LIKE identity_capture);
            ")->execute();
            $stmt = $connection->prepare("
                 COPY identity_capture_loaded
                FROM '{$this->s3Url}'
                CREDENTIALS {$credentials}
                JSON '$jsonPath';
            ")->execute();
            $stmt = $connection->prepare("
                INSERT INTO identity_capture
                    SELECT icl.device_id,icl.email
                    FROM (
                            SELECT *
                            FROM (
                                SELECT
                                    identity_capture_loaded.device_id
                                    , identity_capture_loaded.email
                                    , row_number() OVER (PARTITION BY identity_capture_loaded.email) AS rn
                                    FROM identity_capture_loaded
                            ) WHERE rn = 1

                    ) icl
                        LEFT JOIN identity_capture ON icl.device_id = identity_capture.device_id

                    WHERE identity_capture.device_id IS NULL;
            ")->execute();
            $stmt = $connection->prepare("
                 DROP TABLE identity_capture_loaded;
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

    public function storeDataToElasticSearch()
    {
        if (empty($this->messagesBody)) {
            return;
        }
        $client = $this->getEsClient();
        $index = 'devices';
        if (!$client->getIndex($index)->exists()) {
            return;
        }
        $indexObj = $client->getIndex($index);
        $typeObj = $indexObj->getType($index);
        $path = $indexObj->getName() . '/' . $typeObj->getName() . '/_mget';
        $params = [
            'docs' => []
        ];
        foreach ($this->messagesBody as $key => $value) {
            $params['docs'][] = [
                '_index' => $indexObj->getName()
                , '_type' => $typeObj->getName()
                , '_id' => $value['device_id']
            ];
        }
        $response = $client->request($path, \Elastica\Request::GET, $params);
        $resData = $response->getData();
        $listDeviceUpdate = [];
        if (!empty($resData['docs'])) {
            foreach ($resData['docs'] as $key => $value) {
                if (
                    !empty($value['found'])
                    && empty($value['_source']['email'])
                    ) {
                    $listDeviceUpdate[] = $value['_id'];
                }
            }
        }
        $documents = [];
        foreach ($this->messagesBody as $key => $value) {
            if (!empty($value['device_id']) && in_array($value['device_id'], $listDeviceUpdate)) {
                $document = new \Elastica\Document($value['device_id'], ['email' => $value['email']]);
                $document->setOpType(\Elastica\Bulk\Action::OP_TYPE_UPDATE);
                $documents[] = $document;
            }
        }
        if (!empty($documents)) {
            $bulk = new \Elastica\Bulk($client);
            $bulk->setIndex($indexObj);
            $bulk->setType($typeObj);
            $bulk->addDocuments($documents);
            $bulk->send();
        }
    }

    private function getEsClient()
    {
    	if (!$this->esClient) {
    		$esParameters = $this->container->getParameter('amazon_elasticsearch');
    		return new \Elastica\Client(array(
	            'host' => $esParameters['endpoint'],
	            'port' => $esParameters['port']
	        ));
    	}

    	return $this->esClient;
    }

    public function sendDataToSqs()
    {

    }

    public function log(\Exception $e, $prefix, $content)
    {
         $this->container
            ->get('hyper_event_processing.logger_wrapper')->log(
                $e,
                $this->container->getParameter('amazon_s3_bucket_identity_capture'),
                $prefix,
                $content
            );
    }
}