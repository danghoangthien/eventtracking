<?php

namespace Hyper\DomainBundle\Repository\Jasper;

use Doctrine\ORM\EntityRepository;
use Hyper\Domain\Jasper\JasperRepository;
use Hyper\Domain\Jasper\Jasper;

/**
 * JasperRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class DTJasperRepository extends EntityRepository implements JasperRepository
{
    public function save(Jasper $jasper){
        $this->_em->persist($jasper);
    }
    
    public function completeTransaction(){        
        $this->_em->flush();        
    }
        
    public function findUser($user, $email)
    {
        $user = $this->findOneBy(
            array('username' => $user, 'email' => $email)
         );
        
        return $user;
    }
    
    public function findbyCriteria($field, $value)
    {
        $record = $this->findOneBy(array($field => $value));
        return $record;
    }
    
    public function getResultAndCount($page, $rpp)
    {
        
        //$countQuery = $this->createQueryBuilder('au')->select('count(au.id)')->where($where)->getQuery();
        $countQuery = $this->createQueryBuilder('au')->select('count(au.id)')->getQuery();
        $totalRows = $countQuery->getSingleScalarResult();

        // $query = $this->createQueryBuilder('au')->select('au')->where($where)->getQuery();
        $query = $this->createQueryBuilder('au')->select('au')->orderBy('au.created', 'DESC')->getQuery();
        $offset = $rpp*($page-1);
        $rows = $query->setFirstResult($offset)->setMaxResults($rpp)->getResult(\Doctrine\ORM\Query::HYDRATE_OBJECT);
        return array(
            'rows' => $rows,
            'total' => $totalRows
        );
    }
}