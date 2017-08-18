<?php

namespace Hyper\Adops\WebBundle\DomainBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\DBAL\DBALException;

use Hyper\Adops\WebBundle\Domain\AdopsInappevent;

/**
* DTInappeventRepository
*
* @author Carl Pham <vanca.vnn@gmail.com>
*/
class DTInappeventRepository extends EntityRepository
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
     * Create Inappevent
     *
     * @author Carl Pham <vamca.vnn@gmail.com>
     */
    public function setInappevent($params = array())
    {
        $em = $this->_em;
        $inappevent = new AdopsInappevent();
        $inappevent->setApplication($params['application']);
        $inappevent->setName($params['name']);

        $em->persist($inappevent);
    }
    
    public function insertInappevent()
    {
        try {
            $em = $this->_em;
            $em->flush();
            $em->clear();

            return true;
        } catch (DBALException $ex) {
            $message = sprintf('DBALException [%i]: %s', $ex->getCode(), $ex->getMessage());
            echo $message;
        }
    }

}