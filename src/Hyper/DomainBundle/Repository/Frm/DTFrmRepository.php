<?php

namespace Hyper\DomainBundle\Repository\Frm;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;

use Hyper\Domain\Frm\FrmRepository;
use Hyper\Domain\Frm\Frm;


/**
 * ApplicationRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class DTFrmRepository extends EntityRepository implements FrmRepository
{
    public function save(Frm $frm){
        $this->_em->persist($frm);
        //$this->_em->flush();
    }
    
    public function getByIdentifier(array $identifier) {
        return null;
    }
    
    public function getDeviceFrmByAppIds($deviceId,$appIds) {
        $qb = $this->createQueryBuilder('frm');
        $query = $qb->select('frm')
                    ->where($qb->expr()->andX(
                        ($qb->expr()->eq('frm.deviceId','?1')),
                        ($qb->expr()->in('frm.appId','?2'))
                    ))
                    ->setParameter(1,$deviceId)
                    ->setParameter(2,$appIds)
                    ->getQuery();
                    //echo $query;die;
        try
        {
            $result = $query->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);          
        }
        catch (\Doctrine\ORM\NoResultException $e) {
            $result = array();
        }
        //print_r($result);
        /*
        Array ( [0] => Array ( [id] => 563c1e5e0d86a1.43653059 [deviceId] => 55d2b54cabe2a7.49177732 [appId] => com.bukalapak.android [eventType] => 4 [accountType] => 1 [referenceEventId] => 563c1e5e0d6ca5.15494443 [referenceItemCodes] => a:0:{} [amount] => 5918547369 [baseCurrency] => USD [eventTime] => 1446777379 [created] => 1446780510 ) [1] => Array ( [id] => 563c20b3c5e508.11865142 [deviceId] => 55d2b54cabe2a7.49177732 [appId] => com.bukalapak.android [eventType] => 4 [accountType] => 1 [referenceEventId] => 563c20b3c5cb19.66902853 [referenceItemCodes] => a:0:{} [amount] => 5918547369 [baseCurrency] => USD [eventTime] => 1446777380 [created] => 1446781107 ) [2] => Array ( [id] => 563c24c73ce153.40748010 [deviceId] => 55d2b54cabe2a7.49177732 [appId] => com.bukalapak.android [eventType] => 4 [accountType] => 1 [referenceEventId] => 563c24c73cc4d8.39126726 [referenceItemCodes] => a:0:{} [amount] => 32.126501141973 [baseCurrency] => USD [eventTime] => 1446777381 [created] => 1446782151 ) ) NULL
        */
        return $result;
    }
    
    //frm score
    public function calculateFrmScore(){
        
    }

}