<?php

namespace Hyper\DomainBundle\Repository\Device;

use Symfony\Component\DependencyInjection\ContainerInterface;

class ESIOSDeviceRepository
{
    private $client;
    const IOS_DEVICE_INDEX = 'ios_devices_v1';

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->esParameters = $this->container->getParameter('amazon_elasticsearch');
        $this->logger = $this->container->get('logger');
        $elasticaClient = new \Hyper\EventBundle\Service\HyperESClient($this->container);
        $this->client = $elasticaClient->getClient();
    }

    public function findOneByIDFA($idfa)
    {
        $ret = [];
        $search = new \Elastica\Search($this->client);
        $search->addIndex(self::IOS_DEVICE_INDEX);
        $search->addType(self::IOS_DEVICE_INDEX);
        $termQuery = new \Elastica\Query\Term();
        $termQuery->setTerm('idfa', $idfa);
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
