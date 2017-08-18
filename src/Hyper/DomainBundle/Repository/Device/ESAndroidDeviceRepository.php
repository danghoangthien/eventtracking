<?php

namespace Hyper\DomainBundle\Repository\Device;

use Symfony\Component\DependencyInjection\ContainerInterface;

class ESAndroidDeviceRepository
{
    private $client;
    const ANDROID_DEVICE_INDEX = 'android_devices_v1';

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->esParameters = $this->container->getParameter('amazon_elasticsearch');
        $this->logger = $this->container->get('logger');
        $elasticaClient = new \Hyper\EventBundle\Service\HyperESClient($this->container);
        $this->client = $elasticaClient->getClient();
    }

    public function findOneByAndroidId($androidId)
    {
        $ret = [];
        $search = new \Elastica\Search($this->client);
        $search->addIndex(self::ANDROID_DEVICE_INDEX);
        $search->addType(self::ANDROID_DEVICE_INDEX);
        $termQuery = new \Elastica\Query\Term();
        $termQuery->setTerm('android_id', $androidId);
        $search->setQuery($termQuery);
        $resultSet = $search->search();
        try {
        	$result = $resultSet->offsetGet(0);
        	$ret = $result->getData();
        } catch (\Exception $e) {

        }

        return $ret;
    }
}
