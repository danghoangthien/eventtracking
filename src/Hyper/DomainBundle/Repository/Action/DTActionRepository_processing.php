<?php

namespace Hyper\DomainBundle\Repository\Action;

use Doctrine\ORM\EntityRepository;
use Hyper\Domain\Action\ActionRepository;
use Hyper\Domain\Action\Action;

/**
 * ActionRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class DTActionRepository extends EntityRepository implements ActionRepository
{
    public function save(Action $action){
        $this->_em->persist($action);
        //$this->_em->flush();
    }
    
    public function getByIdentifier($identifier) {
        if (
            !array_key_exists('device_id',$identifier) || 
            !array_key_exists('application_id',$identifier) ||
            !array_key_exists('behaviour_id',$identifier) ||
            !array_key_exists('happened_at',$identifier)
        ) {
            throw new \Exception('invalid action identifier');
        }
         $application = $this->getByActionInfo(
            $identifier['device_id'],
            $identifier['application_id'],
            $identifier['behaviour_id'],
            $identifier['happened_at']
        );
        return $application;
    }
    
    public function getByActionInfo(
        $deviceId,$applicationId,$behaviourId,$happenedAt
    ){
         return $this->findOneBy(
            array(
                'device' => $deviceId,
                'application' => $applicationId,
                'behaviourId' => $behaviourId,
                'happenedAt' => $happenedAt
            )  
        );
    }
    
    public function getLastActivityTime($deviceId,$appIds) {
        $applicationRepo = $this->_em->getRepository('Hyper\Domain\Application\Application');
        $sqb = $applicationRepo->createQueryBuilder('app');
        $subQueryDQL = $sqb->select('app.id')
                    ->where($sqb->expr()->in('app.appId','?1'))
                    ->getDQL();//die;
        $qb = $this->createQueryBuilder('action');
        $query = $qb->select('action.happenedAt')
                    ->where(
                        $qb->expr()->andX(
                            $qb->expr()->in('action.application',$subQueryDQL),
                            $qb->expr()->in('action.device','?2')
                        )
                    )
                    ->orderBy('action.happenedAt','DESC')
                    ->setFirstResult(0)
                    ->setMaxResults(1)
                    ->setParameter(1,$appIds)
                    ->setParameter(2,$deviceId)
                    ->getQuery();
        try
        {
            $result = $query->getSingleResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);             
        }
        catch (\Doctrine\ORM\NoResultException $e) {
            $result = array();
        }                    
        
        //print_r($result);
        return $result;
                    
    }
    
    public function getBehaviourIdsByAppIds(
        array $appIds
    ){
        $qb = $this->createQueryBuilder('action');
        $query = $qb->select('behaviourId')
                ->where(
                    $qb->expr()->in(
                        'action.behaviourId','?1'
                    )
                )
                ->setParameter(1,$appIds)
                ->getQuery();
        $result = $query->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);      
    }
    
    
}