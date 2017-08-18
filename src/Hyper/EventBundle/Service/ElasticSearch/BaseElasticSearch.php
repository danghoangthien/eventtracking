<?php
namespace Hyper\EventBundle\Service\ElasticSearch;

use Symfony\Component\DependencyInjection\ContainerInterface,
    Symfony\Component\Filesystem\Filesystem,
    Symfony\Component\HttpFoundation\File\File,
    Doctrine\Common\Collections;

use GuzzleHttp\Client;

/**
 * @author Carl Pham <vanca.vnn@gmail.com>
 */
class BaseElasticSearch
{
    const TOTAL_ROW_EACH_FILE = 5000;
    const TMP_DEVICE_ID_FILE = 'device_ids.txt';
    const TMP_IOS_DEVICE_ID_FILE = 'ios_device_ids.txt';
    const TMP_ANDROID_DEVICE_ID_FILE = 'android_device_ids.txt';

    protected $container;
    protected $elasticaClient;
    protected $rootDir;
    protected $fs;
    protected $esParameters;
    protected $esInstance;

    protected $tmpDeviceIdDir;
    protected $tmpDeviceIdFile;
    protected $tmpIosDeviceIdFile;
    protected $tmpAndroidDeviceIdFile;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->rootDir = $this->container->get('kernel')->getRootDir() . '/../';
        $this->esDir = $this->rootDir.'web/uploads/elasticsearch';
        $this->esParameters = $this->container->getParameter('amazon_elasticsearch');
        $this->logger = $this->container->get('logger');
        $this->tmpDeviceIdDir = $this->esDir.'/tmp_deviceids';
        $this->initElasticaClient();
    }
    protected function initElasticaClient(){
    	$config = array(
    		'host' => $this->esParameters['endpoint']
            , 'port' => $this->esParameters['port']
            , 'transport' => ['type' => 'AwsAuthV4', 'postWithRequestBody' => true]
            , 'aws_access_key_id' => $this->container->getParameter('amazon_aws_key')
            , 'aws_secret_access_key' => $this->container->getParameter('amazon_aws_secret_key')
            , 'aws_region' => $this->container->getParameter('amazon_s3_region')
    	);
    	$this->elasticaClient =  new \Elastica\Client($config);
    }

    public function makeESSyntax($row = [])
    {
        return json_encode(['index' => ['_id'=>$row['id']]]).PHP_EOL.json_encode($row).PHP_EOL;
    }

    public function pushDataToElasticSearch($index, $type, $pathFile)
    {
        $esPath = $index . '/' . $type . '/_bulk';
        $client = $this->elasticaClient;
        try {
            $response = $client->request(
                $esPath,
                'POST',
                fopen($pathFile, 'r')
            );

           return ['response_code' => $response->getStatus(), 'contents'=>$response->getData()];
        } catch (\Exception $e) {
            return ['response_code' => 500, 'message' => $e->getMessage()];
        }
    }

    public function deleteBulkElasticSearch($index = '', $type = '')
    {
        if (empty($index)) {
            $esEndpoint = 'http://'.$this->esParameters['endpoint'].'/_all';
        } else {
            $esEndpoint = 'http://'.$this->esParameters['endpoint'].'/'.$index;
            if (!empty($type)) {
                $esEndpoint .= '/'.$type;
            }
        }

        $client = new Client();
        //$client = $this->elasticaClient;
        try {
            $response = $client->request('DELETE', $esEndpoint);
            return ['response_code' => $response->getStatusCode()];
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            return [
                'response_code' => 0,
                'errors'=> [
                    'request'=> \GuzzleHttp\Psr7\str($e->getRequest()),
                    'response'=> \GuzzleHttp\Psr7\str($e->getResponse())
                ],
            ];
        }
    }

    protected function getFS()
    {
        if (!$this->fs) {
            $this->fs = new Filesystem();
        }

        return $this->fs;
    }
}