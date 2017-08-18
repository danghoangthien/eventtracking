<?php

namespace Hyper\Adops\WebBundle\DomainBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\DBAL\DBALException;
use Doctrine\Common\Util\Inflector;
use Doctrine\DBAL\Connection;

use Hyper\Adops\WebBundle\Domain\AdopsReport;
use Hyper\Adops\WebBundle\Domain\AdopsProfile;

/**
* DTReportRepository
*
* @author Carl Pham <vanca.vnn@gmail.com>
*/
class DTReportRepository extends EntityRepository
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
            $em = $this->_em;
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
            $em = $this->_em;
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
            $em = $this->_em;
            $em->remove($entity);
            $em->flush();

            return true;
        } catch (DBALException $ex) {
            $message = sprintf('DBALException [%i]: %s', $ex->getCode(), $ex->getMessage());
            echo $message;
        }
    }

    /**
     * Create Adops Report
     *
     * @author Carl Pham <vamca.vnn@gmail.com>
     */
    public function createAdopsReport($params = array())
    {
        $adopsReport = new AdopsReport();

        if (isset($params['event_type'])) {
            $adopsReport->setEventType($params['event_type']);
        }
        if (isset($params['app_id'])) {
            $adopsReport->setAppId($params['app_id']);
        }
        if (isset($params['site_id'])) {
            $adopsReport->setSiteId($params['site_id']);
        }
        if (isset($params['c'])) {
            $adopsReport->setC($params['c']);
        }
        if (isset($params['campaign_payout'])) {
            $adopsReport->setCampaignPayout($params['campaign_payout']);
        }
        if (isset($params['postback_url'])) {
            $adopsReport->setPostbackUrl($params['postback_url']);
        }
        if (isset($params['status'])) {
            $adopsReport->setStatus($params['status']);
        }
        if (isset($params['af_adset'])) {
            $adopsReport->setAfAdset($params['af_adset']);
        }
        if (isset($params['af_sub1'])) {
            $adopsReport->setAfSub1($params['af_sub1']);
        }
        $tNow = time();
        $adopsReport->setCreated($tNow);
        $this->create($adopsReport);
    }

    public function generateReport($params)
    {
        if (
            // !isset($params['event_type']) || !isset($params['site_id'])
            !isset($params['event_type'])
            || !isset($params['app_id']) || !isset($params['c'])
            || !isset($params['created_start']) || !isset($params['created_end'])
            ) {
            return null;
        }
        $sql = "SELECT date(TIMESTAMP 'epoch' + created * INTERVAL '1 Second ') as daily, app_id, event_type, c, site_id, af_adset, af_sub1, campaign_payout, count(status)
                FROM adops_reports
                WHERE date(TIMESTAMP 'epoch' + created * INTERVAL '1 Second ') BETWEEN :created_start AND :created_end
                AND app_id = :app_id AND event_type = :event_type AND c = :c";
        if (isset($params['site_id'])) {
            $sql .= " AND site_id = :site_id ";
        }
        $sql .= " GROUP BY daily, event_type, site_id, c, af_adset, af_sub1, app_id, campaign_payout ORDER BY daily ASC";

        $statement = $this->connection->prepare($sql);
        $statement->bindValue('created_start', $params['created_start']);
        $statement->bindValue('created_end', $params['created_end']);
        $statement->bindValue('app_id', $params['app_id']);
        if (isset($params['site_id'])) {
            $statement->bindValue('site_id', $params['site_id']);
        }
        $statement->bindValue('c', $params['c']);
        $statement->bindValue('event_type', $params['event_type']);
        $statement->execute();
        $results = $statement->fetchAll();

        return $results;
    }

    public function genReport($params)
    {
        if (
            !isset($params['created_start'])
            || !isset($params['created_end'])
            || !isset($params['app_id'])
            ) {
            return null;
        }
        $sql = "SELECT date(TIMESTAMP 'epoch' + created * INTERVAL '1 Second ') as daily,
                app_id, count(app_id), event_type, event_name
                FROM adops_reports
                WHERE date(TIMESTAMP 'epoch' + created * INTERVAL '1 Second ')
                BETWEEN :created_start AND :created_end AND app_id = :app_id";

        if ('all' != $params['quality_benchmark']) {
            $sql .= " AND event_name =:event_name";
            $eventName = trim($params['quality_benchmark']);
        }
        $groupBy = " GROUP BY daily, app_id, event_type, event_name";
        if (isset($params['c'])) {
            $sql .= " AND c = :c ";
            $groupBy .= ', c';
        }
        if (isset($params['site_id'])) {
            $sql .= " AND site_id = :site_id ";
            $groupBy .= ', site_id';
        }
        if (isset($params['af_adset'])) {
            $sql .= " AND af_adset = :af_adset ";
            $groupBy .= ', af_adset';
        }
        $orderBy = ' ORDER BY daily DESC, event_name DESC';
        $sql .= $groupBy.$orderBy;

        $statement = $this->connection->prepare($sql);
        $statement->bindValue('created_start', $params['created_start']);
        $statement->bindValue('created_end', $params['created_end']);
        $statement->bindValue('app_id', $params['app_id']);
        if (isset($params['c'])) {
            $statement->bindValue('c', $params['c']);
        }
        if (isset($params['site_id'])) {
            $statement->bindValue('site_id', $params['site_id']);
        }
        if (isset($params['af_adset'])) {
            $statement->bindValue('af_adset', $params['af_adset']);
        }
        if (isset($eventName)) {
            $statement->bindValue('event_name', $eventName);
        }

        $statement->execute();
        return $statement->fetchAll();
    }
    
    public function genReportQuality($params)
    {
        if (
            !isset($params['created_start'])
            || !isset($params['created_end'])
            || !isset($params['benchmarkdate'])
            || !isset($params['app_id'])
            ) {
            return null;
        }
        $sql = "SELECT date(TIMESTAMP 'epoch' + created * INTERVAL '1 Second ') as daily,
                app_id, count(app_id), event_type, event_name, af_sub1
                FROM adops_reports
                WHERE ((date(TIMESTAMP 'epoch' + created * INTERVAL '1 Second ')
                BETWEEN :created_start AND :created_end) OR (date(TIMESTAMP 'epoch' + created * INTERVAL '1 Second ') = :benchmarkdate)) AND app_id = :app_id";
        $groupBy = " GROUP BY daily, app_id, event_type, event_name, af_sub1";
        
        $sql .= " AND (event_name = 'install'";
        if (isset($params['primary_event_name'])) {
            $sql .= " OR event_name = :primary_event_name";
        }
        if (isset($params['secondary_event_name'])) {
            $sql .= " OR event_name = :secondary_event_name";
        }
        $sql .= ")";
        
        if (isset($params['c'])) {
            $sql .= " AND c = :c ";
            $groupBy .= ', c';
        }
        if (isset($params['site_id'])) {
            $sql .= " AND site_id = :site_id ";
            $groupBy .= ', site_id';
        }
        if (isset($params['af_sub1'])) {
            $sql .= " AND af_sub1 = :af_sub1 ";
            // $groupBy .= ', af_sub1';
        }
        $orderBy = ' ORDER BY daily DESC, event_name DESC';
        $sql .= $groupBy.$orderBy;

        $statement = $this->connection->prepare($sql);
        $statement->bindValue('created_start', $params['created_start']);
        $statement->bindValue('created_end', $params['created_end']);
        $statement->bindValue('benchmarkdate', $params['benchmarkdate']);
        $statement->bindValue('app_id', $params['app_id']);
        if (isset($params['c'])) {
            $statement->bindValue('c', $params['c']);
        }
        if (isset($params['site_id'])) {
            $statement->bindValue('site_id', $params['site_id']);
        }
        if (isset($params['af_sub1'])) {
            $statement->bindValue('af_sub1', $params['af_sub1']);
        }
        if (isset($params['primary_event_name'])) {
            $statement->bindValue('primary_event_name', $params['primary_event_name']);
        }
        if (isset($params['secondary_event_name'])) {
            $statement->bindValue('secondary_event_name', $params['secondary_event_name']);
        }

        $statement->execute();
        return $statement->fetchAll();
    }

    public function setAdopReportData(AdopsReport $adopsReport, $params = array())
    {
        if (isset($params['event_type'])) {
            $adopsReport->setEventType($params['event_type']);
        }
        if (isset($params['app_id'])) {
            $adopsReport->setAppId($params['app_id']);
        }
        if (isset($params['site_id'])) {
            $adopsReport->setSiteId($params['site_id']);
        }
        if (isset($params['c'])) {
            $adopsReport->setC($params['c']);
        }
        if (isset($params['campaign_payout'])) {
            $adopsReport->setCampaignPayout($params['campaign_payout']);
        }
        if (isset($params['postback_url'])) {
            $adopsReport->setPostbackUrl($params['postback_url']);
        }
        if (isset($params['status'])) {
            $status = 0;
            if (isset($params['status']['status']) && $params['status']['status']) {
                $status = $params['status']['data']['response_code'];
            }
            $adopsReport->setStatus($status);
        }
        if (isset($params['af_adset'])) {
            $adopsReport->setAfAdset($params['af_adset']);
        }
        if (isset($params['af_sub1'])) {
            $adopsReport->setAfSub1($params['af_sub1']);
        }
        if (isset($params['event_name'])) {
            $adopsReport->setEventName($params['event_name']);
        }
        if (isset($params['created'])) {
            $adopsReport->setCreated($params['created']);
        }
        if (isset($params['profile_id'])) {
            $adopsReport->setProfileId($params['profile_id']);
        }
        
        return $adopsReport;
    }
    
    public function insertAdopReport()
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
    
    public function setReportAndProfile($params = [])
    {
        $em = $this->_em;
        $adopsReport = new AdopsReport();
        $adopsProfile = new AdopsProfile();

        if (!isset($params['created'])) {
            $tNow = time();
            $params['created'] = $tNow;
        }
        
        $params['report_id'] = $adopsReport->getId();
        $params['profile_id'] = $adopsProfile->getId();
        $adopsReport = $this->setAdopReportData($adopsReport, $params);
        $em->persist($adopsReport);
        
        return $params;
    }
    
}