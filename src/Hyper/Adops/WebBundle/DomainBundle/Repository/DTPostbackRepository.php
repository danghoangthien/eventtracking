<?php

namespace Hyper\Adops\WebBundle\DomainBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Connection;

/**
* DTPostbackRepository
*
* @author Carl Pham <vanca.vnn@gmail.com>
*/
class DTPostbackRepository extends EntityRepository
{

     /**
     * Use this function only to create new Entity after passed the validation
     *
     * @param  $entity  Entity
     * @return boolean  True if success
     */
    public function create($entity)
    {
        try {
            $em = $this->getEntityManager();
            $em->persist($entity);
            $em->flush();

            return true;
        } catch (DBALException $ex) {
            $message = sprintf('DBALException [%i]: %s', $ex->getCode(), $ex->getMessage());
            echo $message;
        }
    }

    /**
     * Use this function only to update existed Application after passed the validation
     *
     * @param  $entity  Entity
     * @return boolean  True if success
     */
    public function update($entity)
    {
        try {
            $em = $this->getEntityManager();
            $em->persist($entity);
            $em->flush();

            return true;
        } catch (DBALException $ex) {
            $message = sprintf('DBALException [%i]: %s', $ex->getCode(), $ex->getMessage());
            echo $message;
        }
    }

    /**
     * Use this function only to delete existed Application after passed the validation
     *
     * @param  $entity  Entity
     * @return boolean  True if success
     */
    public function delete($entity)
    {
        try {
            $em = $this->getEntityManager();
            $em->remove($entity);
            $em->flush();

            return true;
        } catch (DBALException $ex) {
            $message = sprintf('DBALException [%i]: %s', $ex->getCode(), $ex->getMessage());
            echo $message;
        }
    }
    
    /**
    *
    * @var Connection
    */
    private $connection;

    public function getConnection (Connection $dbalConnection)  {
        $this->connection = $dbalConnection;    
    }
    
    /**
     * Build query from array params
     *
     * @author Carl Pham <vanca.vnn@gmail.com>
     * @param  array   $params      Params input.
     * @param  boolean $ignoreLimit TRUE if want ignore limit.
     * @return object               Object QueryBuilder
     */
    public function getQueryParams(array $params, $ignoreLimit = false)
    {
        $classMetaInfo = $this->_em->getClassMetadata(
            $this->getClassName()
        );
        $entityProperties = $classMetaInfo->getReflectionProperties();

        $queryBuilder = $this->createQueryBuilder('ac');
        $expr = $queryBuilder->expr();

        $newParams = [];
        foreach ($params as $key => $value) {
            $realFieldName = lcfirst(Inflector::classify($key));
            $newParams[$realFieldName] = $value;
        }

        $orderBy = 'DESC';
        if (isset($newParams['orderBy'])) {
            $lowerOrderBy = strtolower($newParams['orderBy']);
            if (in_array($lowerOrderBy, ['asc', 'desc'])) {
                $orderBy = $lowerOrderBy;
            }
        }
        if (isset($newParams['sortBy'])) {
            $columns = explode(',', $newParams['sortBy']);
            // remove empty columns and dupliate columns
            $columns = array_unique(array_filter($columns));
            if (!empty($columns)) {
                foreach ($columns as $column) {
                    $columnName = lcfirst(Inflector::classify($column));
                    if ($classMetaInfo->hasField($columnName)) {
                        $queryBuilder->addOrderBy('ac.'.$columnName, $orderBy);
                    }
                }
            }
        }
        $queryBuilder->addOrderBy('ac.id', $orderBy);

        if (!$ignoreLimit) {
            if (isset($newParams['limit'])) {
                $limit = $newParams['limit'];
                if (empty($limit) || (int) $limit <= 0) {
                    $limit = 10;
                }
                $queryBuilder->setMaxResults($limit);
            }
            if (isset($newParams['offset'])) {
                $offset = $newParams['offset'];
                if (empty($offset) || (int) $offset <= 0) {
                    $offset = 0;
                }
                $queryBuilder->setFirstResult($offset);
            }
        }
        unset($newParams['limit'],$newParams['offset']);

        if (!empty($newParams['id'])) {
            $value = explode(',', $newParams['id']);
            $queryBuilder->andWhere(
                $expr->in('ac.id', ':value')
            )->setParameter('value', $value);
            unset($newParams['id']);
        }

        foreach ($newParams as $key => $value) {
            if (isset($entityProperties[$key]) && !empty($value) && $value !== NULL) {
                $queryBuilder->andWhere(
                    $expr->eq('ac.'.$key, ':'.$key)
                )->setParameter($key, $value);
            }
        }

        return $queryBuilder;
    }
    
    public function getPostback($params)
    {
        $queryBuilder = $this->getQueryParams($params);
        var_dump($queryBuilder->getQuery()->getSql());
        var_dump($queryBuilder->getQuery()->getParameters());
        die; // FOR DEMO
        return $queryBuilder->getQuery()->getResult();
    }
    
    public function count($params)
    {
        $queryBuilder = $this->getQueryParams($params, true);
        $queryBuilder->select('count(ac.id)');

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }
    
    /**
     * Exclusion strategy by JMS group name
     *
     * @author Carl Pham <vanca.vnn@gmail.com>
     *
     * @param  Entity|Collection $data     Entity or array collection of entity
     * @param  string            $JMSGroup Name of JMS group
     *
     * @return array                       Array after the exclusion was done
     */
    public function getFinalResultByJMSGroup($data, $JMSGroup)
    {
        $serializer = SerializerBuilder::create()->build();
        $json = $serializer->serialize(
            $data,
            'json',
            SerializationContext::create()->setGroups(
                [$JMSGroup]
            )->setSerializeNull(true)->enableMaxDepthChecks()
        );
        $arr = json_decode($json, true);
        return $arr;
    }
    
    public function getPostbackCustom($params)
    {
        if (isset($params['event_name'])) {
            $sql = "SELECT pb.id, pb.event_type, pb.postback_url, pb.application_id, pb.publisher_id, pb.campaign_id, pb.inappevent_id, cp.payout 
            FROM adops_postbacks pb
            LEFT JOIN adops_applications app ON pb.application_id = app.id
            LEFT JOIN adops_campaigns cp ON pb.campaign_id = cp.id
            LEFT JOIN adops_publishers pub ON pb.publisher_id = pub.id
            LEFT JOIN adops_inappevents inae ON pb.application_id = inae.application_id
            WHERE pb.event_type = :event_type AND app.app_id = :app_id AND cp.status = 1 
            AND pub.id = :af_siteid AND inae.name = :event_name";
            if (isset($params['code'])) $sql .= ' AND cp.code = :code';
            $sql .= ' ORDER BY pb.id DESC';
            
            $statement = $this->connection->prepare($sql);
            if (isset($params['code'])) $statement->bindValue('code', $params['code']);
            $statement->bindValue('event_type', $params['event_type']);
            $statement->bindValue('app_id', $params['app_id']);
            $statement->bindValue('af_siteid', $params['af_siteid']);
            $statement->bindValue('event_name', $params['event_name']);
        } else {
            $sql = "SELECT pb.id, pb.event_type, pb.postback_url, pb.application_id, pb.publisher_id, pb.campaign_id, pb.inappevent_id, cp.payout 
            FROM adops_postbacks pb
            LEFT JOIN adops_applications app ON pb.application_id = app.id
            LEFT JOIN adops_campaigns cp ON pb.campaign_id = cp.id
            LEFT JOIN adops_publishers pub ON pb.publisher_id = pub.id
            WHERE pb.event_type = :event_type AND app.app_id = :app_id 
            AND cp.status = 1 AND pub.id = :af_siteid AND cp.code = :code
            ORDER BY pb.id DESC";
            $statement = $this->connection->prepare($sql);
            $statement->bindValue('event_type', $params['event_type']);
            $statement->bindValue('app_id', $params['app_id']);
            $statement->bindValue('af_siteid', $params['af_siteid']);
            $statement->bindValue('code', $params['code']);
        }
        $statement->execute();
        $results = $statement->fetchAll();
        
        return $results;
    }
}