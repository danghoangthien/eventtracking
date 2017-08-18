<?php

namespace Hyper\Domain\CsvUploadLog;

use Doctrine\ORM\EntityRepository;
use Doctrine\DBAL\DBALException;
use Doctrine\Common\Util\Inflector;
use Doctrine\DBAL\Connection;


/**
* DTCsvUploadLogRepository
*
* @author Carl Pham <vanca.vnn@gmail.com>
*/
class DTCsvUploadLogRepository extends EntityRepository
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