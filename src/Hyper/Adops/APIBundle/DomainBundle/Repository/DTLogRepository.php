<?php

namespace Hyper\Adops\APIBundle\DomainBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\DBAL\DBALException;
use Doctrine\Common\Util\Inflector;
use Doctrine\DBAL\Connection;

use Hyper\Adops\APIBundle\Domain\AdopsLog;

/**
* DTLogRepository
*
* @author Carl Pham <vanca.vnn@gmail.com>
*/
class DTLogRepository extends EntityRepository
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
     * Create Adops Log
     * 
     * @author Carl Pham <vamca.vnn@gmail.com>
     */
    public function createAdopsLog($params = array())
    {
        $adopsLog = new AdopsLog();
        
        if (isset($params['detail'])) {
            $adopsLog->setDetail(json_encode($params['detail']));
        }
        if (isset($params['postback_id'])) {
            $adopsLog->setPostbackId($params['postback_id']);
        }
        if (isset($params['postback_url'])) {
            $adopsLog->setPostbackUrl($params['postback_url']);
        }
        if (isset($params['status'])) {
            $adopsLog->setStatus($params['status']);
        }
        $tNow = time();
        $adopsLog->setCreated($tNow);
        $this->create($adopsLog);
    }
    
    /**
     * Set Adops Log
     * 
     * @author Carl Pham <vamca.vnn@gmail.com>
     */
    public function setAdopsLog($params = array())
    {
        $em = $this->getEntityManager();
        $adopsLog = new AdopsLog();
        
        if (isset($params['detail'])) {
            $adopsLog->setDetail(json_encode($params['detail']));
        }
        if (isset($params['postback_id'])) {
            $adopsLog->setPostbackId($params['postback_id']);
        }
        if (isset($params['postback_url'])) {
            $adopsLog->setPostbackUrl($params['postback_url']);
        }
        if (isset($params['status'])) {
            $adopsLog->setStatus($params['status']);
        }
        $tNow = time();
        $adopsLog->setCreated($tNow);

        $em->persist($adopsLog);
    }
    
    public function insertAdopLog()
    {
        try {
            $em = $this->getEntityManager();
            $em->flush();
            $em->clear();

            return true;
        } catch (DBALException $ex) {
            $message = sprintf('DBALException [%i]: %s', $ex->getCode(), $ex->getMessage());
            echo $message;
        }
    }
}