<?php

namespace Hyper\Adops\WebBundle\DomainBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\DBAL\DBALException;

/**
* DTPublisherRepository
*
* @author Carl Pham <vanca.vnn@gmail.com>
*/
class DTPublisherRepository extends EntityRepository
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
}