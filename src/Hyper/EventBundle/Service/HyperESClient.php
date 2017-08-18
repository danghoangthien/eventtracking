<?php

namespace Hyper\EventBundle\Service;

use \Elastica\Client;

class HyperESClient
{
    protected $client;
    protected $container;

    public function __construct($container, $config = [], $postWithRequestBody = true)
    {
        $this->container = $container;
        if (empty($config)) {
            $esParams = $this->container->getParameter('amazon_elasticsearch');
            $config = [
                'host' => $esParams['endpoint']
                , 'port' => $esParams['port']
                , 'transport' => ['type' => 'AwsAuthV4', 'postWithRequestBody' => $postWithRequestBody]
                , 'aws_access_key_id' => $this->container->getParameter('amazon_aws_key')
                , 'aws_secret_access_key' => $this->container->getParameter('amazon_aws_secret_key')
                , 'aws_region' => $this->container->getParameter('amazon_s3_region')
            ];
        }

        if (!$this->client instanceof Client) {
            $this->client = new Client($config);
        }

        return $this;
    }

    public function getClient()
    {
        return $this->client;
    }
}