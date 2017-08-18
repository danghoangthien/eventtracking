<?php

namespace Hyper\DomainBundle\Repository\Action;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Elastic Search Action Repository
 * @author Carl Pham <vanca.vnn@gmail.com>
 */
class ESActionRepository
{
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->esParameters = $this->container->getParameter('amazon_elasticsearch');
        $this->logger = $this->container->get('logger');
    }

    /**
     * Get actions by device and date range from elastic search
     * @author Carl Pham <vanca.vnn@gmail.com>
     */
    public function getActionByDeviceDateRangeFromEs($deviceId, $startTime, $endTime, $appId)
    {
        $dtActionRepo = $this->container->get('action_repository');
        $appTitleS3Folders = $dtActionRepo->getAppTitleS3FolderByAppId($appId);
        $esService = $this->container->get('elasticsearch_service');
        $condition = ['device_id' => $deviceId, 'happened_at' => ['gte'=>$startTime, 'lte'=>$endTime]];
        $rsOutput = $this->searchActions($appTitleS3Folders, [$appId], $condition);
        return $rsOutput;
    }

    public function searchActions($indexs, $types, $condition)
    {
        $elasticaClient = new \Hyper\EventBundle\Service\HyperESClient($this->container);
        $elasticaClient = $elasticaClient->getClient();
        if (!$this->checkIndices($elasticaClient, $indexs)) {
            return [];
        }
        $search = new \Elastica\Search($elasticaClient);
        foreach ($indexs as $index) {
            $search->addIndex($index);
        }
        foreach ($types as $type) {
            $search->addType($type);
        }
        $query = new \Elastica\Query();

        /*
        $condition = ['device_id'=>'32b003949ba5ca16952ae10a0d662c02', 'happened_at'=>[['lte'=>1453694754,'gte'=>1425295458]]]
        */
        $matchQuery = new \Elastica\Query\Match();
        $matchQuery->setField('device_id', $condition['device_id']);

        $rangeQuery = new \Elastica\Query\Range('happened_at', $condition['happened_at']);

        $boolQuery = new \Elastica\Query\Bool();
        $boolQuery
            ->addMust($matchQuery)
            ->addMust($rangeQuery);

        $query
            ->setQuery($boolQuery)
            ->setSize(30)
            ->setSort(['happened_at' => 'desc']);

        $search->setQuery($query);

        $resultSet = $search->search();
        $results = $resultSet->getResults();
        $rsOutput = [];
        foreach ($results as $entity) {
            $rsOutput[] = $entity->getData();
        }
        return $rsOutput;
    }

    public function checkIndices($elasticaClient, $indices)
    {
        foreach ($indices as $indexName) {
            if (!$elasticaClient->getIndex($indexName)->exists()) {
                return false;
            }
        }
        return true;
    }

    public function updateAmountUSD($index, $type, $listAction = [])
    {
        $esClient = new \Hyper\EventBundle\Service\HyperESClient($this->container);
        $esClient = $esClient->getClient();
        if (!$this->checkIndices($esClient, $index)) {
            return [];
        }
        $documents = [];
        if (!empty($listAction)) {
            foreach ($listAction as $action) {
                $document = new \Elastica\Document($action['id'], ['amount_usd' => (float) $action['amount_usd']]);
                $document->setOpType(\Elastica\Bulk\Action::OP_TYPE_UPDATE);
                $documents[] = $document;
            }
        }
        //var_dump($documents);
        if (!empty($documents)) {
            $bulk = new \Elastica\Bulk($esClient);
            $bulk->setIndex($index[0]);
            $bulk->setType($type);
            $bulk->addDocuments($documents);
            $bulk->send();
        }

    }
}
