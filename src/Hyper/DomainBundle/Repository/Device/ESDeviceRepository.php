<?php

namespace Hyper\DomainBundle\Repository\Device;

use Symfony\Component\DependencyInjection\ContainerInterface;

class ESDeviceRepository
{
    const DEVICE_INDEX = 'devices_v1';
    private $client;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->esParameters = $this->container->getParameter('amazon_elasticsearch');
        $this->logger = $this->container->get('logger');
        $elasticaClient = new \Hyper\EventBundle\Service\HyperESClient($this->container);
        $this->client = $elasticaClient->getClient();
    }

    public function findOneByEmail($email)
    {
        $ret = [];
        $search = new \Elastica\Search($this->client);
        $search->addIndex(self::DEVICE_INDEX);
        $search->addType(self::DEVICE_INDEX);
        $termQuery = new \Elastica\Query\Term();
        $termQuery->setTerm('email', $email);
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
