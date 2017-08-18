<?php

namespace Hyper\DomainBundle\Repository\Analytics;

use Doctrine\ORM\EntityRepository;
use Hyper\Domain\Analytics\MetadataRepository;
use Hyper\Domain\Analytics\Metadata;

/**
 * MetadataRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class DTMetadataRepository extends EntityRepository implements MetadataRepository
{
    public function save(Metadata $metadata){
        $this->_em->persist($metadata);
        //echo "saved";
        //$this->_em->flush();
    }
    
    public function completeTransaction(){
        //echo "flushing";
        $this->_em->flush();
        //echo "flushed";
    }
    
    public function getResultAndCount($page, $rpp)
    {
        $countQuery = $this->createQueryBuilder('au')->select('count(au.id)')->getQuery();
        $totalRows = $countQuery->getSingleScalarResult();

        $query = $this->createQueryBuilder('an')->select('an')->orderBy('an.created', 'DESC')->getQuery();
        $offset = $rpp*($page-1);
        $rows = $query->setFirstResult($offset)->setMaxResults($rpp)->getResult(\Doctrine\ORM\Query::HYDRATE_OBJECT);
        return array(
            'rows' => $rows,
            'total' => $totalRows
        );
    }
    
    public function findbyCriteria($field, $value)
    {
        $record = $this->findOneBy(array($field => $value));
        return $record;
    }
    
    public function deleteMetadata($id)
    {
        $meta = $this->findOneBy(
            array('id' => $id)
         );
        
        $count = count($meta);
        
        if($count > 0)
        {
            $qb = $this->_em->createQueryBuilder();
            $qb->delete('Hyper\Domain\Analytics\Metadata', 'a');
            $qb->andWhere($qb->expr()->eq('a.id', ':id'));
            $qb->setParameter(':id', $id);
            $qb->getQuery()->execute();
            
            return "success";
        }
        else
        {
            return "failed";
        }                        
    }
    
    public function updateMetadata($id, $key, $query)
    {
        $meta = $this->findOneBy(
            array('id' => $id)
         );
        
        $count = count($meta);
        
        if($count > 0)
        {
            $meta->setKey("$key");
            $meta->setQuery("$query");
            $this->_em->flush();
            
            return "success";
        }        
        else
        {
            return "failed";
        }
    }
    
    public function findAllKey()
    {
        $qb = $this->createQueryBuilder('ana')
                ->select('ana.key');
        //echo $qb->getQuery()->getSql();exit;
        
        return $qb->getQuery()->getResult();
    }
}