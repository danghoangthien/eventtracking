<?php

namespace Hyper\Adops\WebBundle\DomainBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\DBAL\DBALException;
use Doctrine\Common\Util\Inflector;
use Doctrine\DBAL\Connection;

use Hyper\Adops\WebBundle\Domain\AdopsProfile;

/**
* DTProfileRepository
*
* @author Carl Pham <vanca.vnn@gmail.com>
*/
class DTProfileRepository extends EntityRepository
{
    /**
    *
    * @var Connection
    */
    private $connection;

    public function getConnection (Connection $dbalConnection)  {
        $this->connection = $dbalConnection;
    }

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
    
    public function setAdopsProfile($params = array())
    {
        $em = $this->_em;
        $adopsProfile = new AdopsProfile();
        
        // Override Id
        if (isset($params['profile_id']) && !empty($params['profile_id'])) {
            $adopsProfile->setId($params['profile_id']);
        }

        $adopsProfile = $this->setAdopProfileData($adopsProfile, $params);
        $em->persist($adopsProfile);
    }

    public function insertAdopProfile()
    {
        try {
            $em = $this->_em;
            $em->flush();
            $em->clear();

            return true;
        } catch (DBALException $ex) {
            $message = sprintf('DBALException [%i]: %s', $ex->getCode(), $ex->getMessage());
            return $message;
        }
    }
    
    public function setAdopProfileData(AdopsProfile $adopsProfile, $params)
    {
        if (empty($params)) {
            return $adopsProfile;
        }
        if (isset($params['report_id'])) $adopsProfile->setReportId($params['report_id']);
        if (isset($params['idfa'])) $adopsProfile->setIdfa($params['idfa']);
        if (isset($params['advertising_id'])) $adopsProfile->setAdvertisingId($params['advertising_id']);
        if (isset($params['android_id'])) $adopsProfile->setAndroidId($params['android_id']);
        if (isset($params['wifi'])) $adopsProfile->setWifi($params['wifi']);
        if (isset($params['click_time'])) $adopsProfile->setClickTime((int)$params['click_time']);
        if (isset($params['install_time'])) $adopsProfile->setInstallTime((int)$params['install_time']);
        if (isset($params['country_code'])) $adopsProfile->setCodeCountry($params['country_code']);
        if (isset($params['city'])) $adopsProfile->setCity($params['city']);
        if (isset($params['device-branch'])) $adopsProfile->setDeviceBrand($params['device-branch']);
        if (isset($params['device_carrier'])) $adopsProfile->setDeviceCarrier($params['device_carrier']);
        if (isset($params['device_id'])) $adopsProfile->setDeviceId($params['device_id']);
        if (isset($params['device_model'])) $adopsProfile->setDeviceModel($params['device_model']);
        if (isset($params['language'])) $adopsProfile->setLanguage($params['language']);
        if (isset($params['sdk_version'])) $adopsProfile->setSdkVersion($params['sdk_version']);
        if (isset($params['version'])) $adopsProfile->setVersion($params['version']);
        if (isset($params['ua'])) $adopsProfile->setUa($params['ua']);
        if (isset($params['revenue'])) $adopsProfile->setRevenue($params['revenue']);
        if (isset($params['currency'])) $adopsProfile->setCurrency($params['currency']);
        if (isset($params['json'])) $adopsProfile->setJson($params['json']);
        
        return $adopsProfile;
    }

}