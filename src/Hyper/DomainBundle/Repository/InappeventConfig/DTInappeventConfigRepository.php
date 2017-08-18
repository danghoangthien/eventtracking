<?php

namespace Hyper\DomainBundle\Repository\InappeventConfig;

use Doctrine\ORM\EntityRepository;
use Hyper\Domain\InappeventConfig\InappeventConfig;

/**
 * InappeventConfigRepository
 *
 * @author CarlPham <vanca.vnn@gmail.com>
 */
class DTInappeventConfigRepository extends EntityRepository
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
            $em = $this->_em;
            $em->persist($entity);
            $em->flush();

            return true;
        } catch (DBALException $ex) {
            $message = sprintf('DBALException [%i]: %s', $ex->getCode(), $ex->getMessage());
            $this->log($message);
            return false;
        }
    }

    /**
     * Use this function only to update existed Entity after passed the validation
     *
     * @param  $entity  Entity
     * @return boolean  True if success
     */
    public function update($entity)
    {
        try {
            $em = $this->_em;
            $em->persist($entity);
            $em->flush();

            return true;
        } catch (DBALException $ex) {
            $message = sprintf('DBALException [%i]: %s', $ex->getCode(), $ex->getMessage());
            $this->log($message);
            return false;
        }
    }

    /**
     * Use this function only to delete existed Entity after passed the validation
     *
     * @param  $entity  Entity
     * @return boolean  True if success
     */
    public function delete($entity)
    {
        try {
            $em = $this->_em;
            $em->remove($entity);
            $em->flush();

            return true;
        } catch (DBALException $ex) {
            $message = sprintf('DBALException [%i]: %s', $ex->getCode(), $ex->getMessage());
            $this->log($message);
            return false;
        }
    }

    public function log($message)
    {
        global $kernel;

        if ('AppCache' == get_class($kernel)) {
            $kernel = $kernel->getKernel();
        }
        $container = $kernel->getContainer();
        $logger = $container->get('logger');
        $logger->error($message);
    }
    
}