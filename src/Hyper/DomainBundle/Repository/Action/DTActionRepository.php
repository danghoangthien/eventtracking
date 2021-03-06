<?php

namespace Hyper\DomainBundle\Repository\Action;

use Doctrine\ORM\EntityRepository;
use Hyper\Domain\Action\ActionRepository;
use Hyper\Domain\Action\Action;

/**
 * ActionRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class DTActionRepository extends EntityRepository implements ActionRepository
{
    public function save(Action $action){
        $this->_em->persist($action);
        //$this->_em->flush();
    }

    public function getByIdentifier($identifier) {
        if (
            !array_key_exists('device_id',$identifier) ||
            !array_key_exists('application_id',$identifier) ||
            !array_key_exists('behaviour_id',$identifier) ||
            !array_key_exists('happened_at',$identifier)
        ) {
            throw new \Exception('invalid action identifier');
        }
         $application = $this->getByActionInfo(
            $identifier['device_id'],
            $identifier['application_id'],
            $identifier['behaviour_id'],
            $identifier['happened_at']
        );
        return $application;
    }

    public function getByActionInfo(
        $deviceId,$applicationId,$behaviourId,$happenedAt
    ){
         return $this->findOneBy(
            array(
                'device' => $deviceId,
                'application' => $applicationId,
                'behaviourId' => $behaviourId,
                'happenedAt' => $happenedAt
            )
        );
    }

    public function getLastActivityTime($deviceId,$appIds) {
        $applicationRepo = $this->_em->getRepository('Hyper\Domain\Application\Application');
        $sqb = $applicationRepo->createQueryBuilder('app');
        $subQueryDQL = $sqb->select('app.id')
                    ->where($sqb->expr()->in('app.appId','?1'))
                    ->getDQL();//die;
        $qb = $this->createQueryBuilder('action');
        $query = $qb->select('action.happenedAt')
                    ->where(
                        $qb->expr()->andX(
                            $qb->expr()->in('action.application',$subQueryDQL),
                            $qb->expr()->in('action.device','?2')
                        )
                    )
                    ->orderBy('action.happenedAt','DESC')
                    ->setFirstResult(0)
                    ->setMaxResults(1)
                    ->setParameter(1,$appIds)
                    ->setParameter(2,$deviceId)
                    ->getQuery();
        try
        {
            $result = $query->getSingleResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);
        }
        catch (\Doctrine\ORM\NoResultException $e) {
            $result = array();
        }

        //print_r($result);
        return $result;

    }

    public function getBehaviourIdsByAppIds(
        array $appIds
    ){
        //return null;
        $qb = $this->createQueryBuilder('action');
        $query = $qb->select('action.behaviourId')
                ->where(
                    $qb->expr()->in(
                        'action.appId','?1'
                    )
                )
                ->groupBy('action.behaviourId')
                ->setParameter(1,$appIds)
                ->getQuery();
        return $result = $query->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);
    }

    public function findbyCriteria($field, $value)
    {
        $record = $this->findOneBy(array($field => $value));
        return $record;
    }

    public function findByS3($date)
    {
        // $conn = $this->get('doctrine.dbal.pgsql_connection');

        // $sql  = $conn->prepare("SELECT s3_log_file, created FROM actions WHERE s3_log_file LIKE '%$date%' ;");
        // $sql->execute();

        // $data = array();

        // for($x = 0; $row = $sql->fetch(); $x++)
        // {
        //     $data[] = $row;
        // }

        // return $row;
    }

    public function getResultAndCount($page, $rpp, $device_id, $app_id)
    {
        $countQuery = $this->createQueryBuilder('ac')->select('count(ac.id)')->getQuery();
        $totalRows = $countQuery->getSingleScalarResult();

        $qb = $this->createQueryBuilder('ac');
        $query = $qb->select('ac')
            ->where('ac.device = ?1')
            ->andWhere(
                $qb->expr()->andX(
                    $qb->expr()->in('ac.appId',$app_id)
                )
            )
            ->setParameter(1, $device_id)
            ->orderBy('ac.created', 'DESC')
            ->getQuery();
        $offset = $rpp*($page-1);
        $rows = $query->setFirstResult($offset)->setMaxResults($rpp)->getResult(\Doctrine\ORM\Query::HYDRATE_OBJECT);
        return array(
            'rows' => $rows,
            'total' => $totalRows
        );
    }

    public function getListDataFromIdentifier($listIdentifier = array())
    {
        if (empty($listIdentifier)) {
            return;
        }
        $qb = $this->createQueryBuilder('act')
                ->select('act.id AS id')
                ->distinct();
        $listIdentifier = array_unique($listIdentifier);
        $qb->where($qb->expr()->in('act.id', $listIdentifier));

        return $qb->getQuery()->getResult();
    }

    public function getContentTypeByAppIds($appIds =array()) {
        if (empty($appIds)) {
            return;
        }
        $qb = $this->createQueryBuilder('act');
        $query = $qb->select('act.afContentType')
                    ->distinct()
                    ->where($qb->expr()->in('act.appId',$appIds))
                    ->andWhere("act.afContentType <> ''")
                    ->orderBy('act.afContentType', 'ASC')
                    ->getQuery();
        $result = $query->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);
        return array_column($result, 'afContentType');

        return $qb->getQuery()->getResult();
    }

    public function getTotalTransactionByDevice($deviceId, $listAppId)
    {
        if (is_array($listAppId)) {
            $listAppId = implode("','", $listAppId);
        }
        $query = "
            SELECT COUNT(*) AS total
            FROM actions
            WHERE device_id = '{$deviceId}'
                AND (event_name = 'purchase' OR event_name = 'af_purchase')
                AND app_id IN ('$listAppId')
        ";
        $stmt = $this->_em->getConnection()->prepare($query);
        $stmt->execute();

        return $stmt->fetchColumn(0);
    }

    public function getLastTransactionByDevice($deviceId, $listAppId)
    {
        if (is_array($listAppId)) {
            $listAppId = implode("','", $listAppId);
        }
        $query = "
            SELECT happened_at,amount_usd
            FROM actions
            WHERE device_id = '{$deviceId}'
                AND app_id IN ('$listAppId')
                AND amount_usd <> 0
            ORDER BY happened_at DESC
            LIMIT 1
        ";
        $stmt = $this->_em->getConnection()->prepare($query);
        $stmt->execute();

        return $stmt->fetch();
    }

    public function getLastActivityByDevice($deviceId, $listAppId)
    {
        if (is_array($listAppId)) {
            $listAppId = implode("','", $listAppId);
        }
        $query = "
            SELECT MAX(happened_at) AS last_activity
            FROM actions
            WHERE device_id = '{$deviceId}'
                AND app_id IN ('$listAppId')
        ";
        $stmt = $this->_em->getConnection()->prepare($query);
        $stmt->execute();

        return $stmt->fetchColumn(0);
    }

    public function getPaginateDataByDevice(
        $deviceId,
        $listAppId,
        $pageNumber,
        $perpageNumber = 10
    ) {
        if (is_array($listAppId)) {
            $listAppId = implode("','", $listAppId);
        }
        $qbTotal = $this->createQueryBuilder('act')
                        ->select('COUNT(act.id)');

        $offset = $perpageNumber * ($pageNumber - 1);
        $qb = $this->createQueryBuilder('act')
                    ->select('act.id,act.happenedAt')
                    ->addSelect('act.appId')
                    ->addSelect('act.afRevenue,act.afPrice,act.afLevel,act.afSuccess')
                    ->addSelect('act.afContentType,act.afContentList,act.afContentId')
                    ->addSelect('act.afCurrency,act.afRegistrationMethod,act.afQuantity')
                    ->addSelect('act.afPaymentInfoAvailable,act.afRatingValue,act.afMaxRatingValue')
                    ->addSelect('act.afSearchString,act.afDescription,act.afScore,act.afDestinationA')
                    ->addSelect('act.afDestinationB,act.afClass,act.afDateA,act.afDateB')
                    ->addSelect('act.afEventStart,act.afEventEnd,act.afLat,act.afLong')
                    ->addSelect('act.afCustomerUserId,act.afValidated,act.afReceiptId,act.afParam1')
                    ->addSelect('act.afParam2,act.afParam3,act.afParam4,act.afParam5')
                    ->addSelect('act.afParam6,act.afParam7,act.afParam8,act.afParam9,act.afParam10,act.eventValueText')
                    ->addSelect('act.eventName')
                    ->setFirstResult($offset)
                    ->setMaxResults($perpageNumber);
        $qbTotal->where('act.device = ?1')->setParameter(1, $deviceId);
        $qbTotal->andWhere("act.appId IN('$listAppId')");
        $qb->where('act.device = ?1')->setParameter(1, $deviceId);
        $qb->andWhere("act.appId IN('$listAppId')");
        $qb->orderBy('act.happenedAt', 'DESC');
        $rows = $qb->getQuery()
                    //->getSQL();
                    ->getResult();
        //echo $rows;exit;

        $totalRows =  $qbTotal->getQuery()->getSingleScalarResult();

        return array(
            'rows' => $rows,
            'total' => $totalRows
        );
    }

    public function getTotalMoneySpentByDevice($deviceId, $listAppId)
    {
        if (is_array($listAppId)) {
            $listAppId = implode("','", $listAppId);
        }
        $query = "
            SELECT SUM(amount_usd) AS total_amount, COUNT(*) AS total_transaction
            FROM actions
            WHERE device_id = '{$deviceId}'
            AND app_id IN ('$listAppId')
            AND amount_usd <> 0
        ";
        $stmt = $this->_em->getConnection()->prepare($query);
        $stmt->execute();

        return $stmt->fetch();
    }

    public function getEventNameByAppIds($appIds = [], $eventNotIns = [])
    {
        if (empty($appIds)) {
            return;
        }
        $qb = $this->createQueryBuilder('action');
        $qb->select('action.eventName')
                ->where(
                    $qb->expr()->in(
                        'action.appId','?1'
                    )
                )
                ->andWhere("action.eventName <> ''")
                ->groupBy('action.eventName')
                ->setParameter(1,$appIds);

        if (!empty($eventNotIns)) {
            $qb->andWhere(
                    $qb->expr()->notIn(
                        'action.eventName','?2'
                    )
                )
                ->setParameter(2,$eventNotIns);
        }
        $qb->orderBy('action.eventName', 'ASC');
        $query = $qb->getQuery();
        $result = $query->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);
        return array_column($result, 'eventName');
    }

    public function getEventNameAppIdByAppIds($appIds = [], $eventNotIns = [])
    {
        if (empty($appIds)) {
            return;
        }
        $qb = $this->createQueryBuilder('action');
        $qb->select('action.eventName,action.appId')
                ->where(
                    $qb->expr()->in(
                        'action.appId','?1'
                    )
                )
                ->andWhere("action.eventName <> ''")
                ->groupBy('action.eventName,action.appId')
                ->setParameter(1,$appIds);

        if (!empty($eventNotIns)) {
            $qb->andWhere(
                    $qb->expr()->notIn(
                        'action.eventName','?2'
                    )
                )
                ->setParameter(2,$eventNotIns);
        }
        $qb->orderBy('action.eventName', 'ASC');
        $query = $qb->getQuery();
        $result = $query->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);

        return $result;
    }

    public function getActionByDeviceDateRange(
        $deviceId,
        $startDate,
        $endDate,
        $appId
    ){
        $qb = $this->createQueryBuilder('act')
            ->select('act.id,act.happenedAt')
            ->addSelect('act.afRevenue,act.afPrice,act.afLevel,act.afSuccess')
            ->addSelect('act.afContentType,act.afContentList,act.afContentId')
            ->addSelect('act.afCurrency,act.afRegistrationMethod,act.afQuantity')
            ->addSelect('act.afPaymentInfoAvailable,act.afRatingValue,act.afMaxRatingValue')
            ->addSelect('act.afSearchString,act.afDescription,act.afScore,act.afDestinationA')
            ->addSelect('act.afDestinationB,act.afClass,act.afDateA,act.afDateB')
            ->addSelect('act.afEventStart,act.afEventEnd,act.afLat,act.afLong')
            ->addSelect('act.afCustomerUserId,act.afValidated,act.afReceiptId,act.afParam1')
            ->addSelect('act.afParam2,act.afParam3,act.afParam4,act.afParam5')
            ->addSelect('act.afParam6,act.afParam7,act.afParam8,act.afParam9,act.afParam10,act.eventValueText')
            ->addSelect('act.eventName')
            ->setFirstResult(0)
            ->setMaxResults(30);
        $qb->where('act.device = ?1')->setParameter(1, $deviceId);
        $qb->andWhere('act.appId = ?2')->setParameter(2, $appId);
        $qb->andWhere('act.happenedAt >= ?3')->setParameter(3, $startDate);
        $qb->andWhere('act.happenedAt <= ?4')->setParameter(4, $endDate);
        $qb->orderBy('act.happenedAt', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Get action by app id from DB for elastic search.
     * @author Carl Pham <vanca.vnn@gmail.com>
     */
    public function getActionForEsByAppId($appId, $limit = 1000, $offset = 0)
    {
        $sql = "SELECT act.*, idcap.email
                FROM actions AS act
                LEFT JOIN identity_capture as idcap ON act.device_id = idcap.device_id
                WHERE act.app_id = :app_id
                LIMIT :limit
                OFFSET :offset";
        $em = $this->_em;
        $statement = $em->getConnection()->prepare($sql);
        $statement->bindValue('app_id', $appId);
        $statement->bindValue('limit', $limit);
        $statement->bindValue('offset', $offset);
        $statement->execute();
        $rs = $statement->fetchAll();
        return $rs;
    }

    /**
     * Get s3 folder name from application title by app id
     * @author Carl Pham <vanca.vnn@gmail.com>
     */
    public function getAppTitleS3FolderByAppId($appId)
    {
        $sql = "SELECT s3_folder FROM applications_title
            WHERE id IN (SELECT app_title_id FROM applications_platform WHERE app_id = :app_id)";
        $em = $this->_em;
        $statement = $em->getConnection()->prepare($sql);
        $statement->bindValue('app_id', $appId);
        $statement->execute();
        $rs = $statement->fetchAll(\PDO::FETCH_COLUMN);
        return $rs;
    }

    public function getLatestDevice($listAppId)
    {
        if (empty($listAppId)) {
            return '';
        }
        if (is_array($listAppId)) {
            $listAppId = implode("','", $listAppId);
        }
        $query = "
            SELECT device_id
            FROM actions
            WHERE app_id IN ('$listAppId')
            ORDER BY happened_at DESC
            LIMIT 1
        ";
        $stmt = $this->_em->getConnection()->prepare($query);
        $stmt->execute();

        return $stmt->fetchColumn(0);
    }



    public function updateAmountUSD($eventName, $appId)
    {
        $tempTable = "actions_tmp_iaeconfig_".time();
        $listAction = [];
        $connection = $this->_em->getConnection();
        $connection->beginTransaction();
        $stmt = $connection->prepare("set query_group to 'ak_low_priority_long_processing_time';");
        $stmt->execute();
        $stmt = $connection->prepare("
             DROP TABLE IF EXISTS $tempTable;
        ")->execute();
        // create temp table
        $stmt = $connection->prepare("
            CREATE TABLE $tempTable (
                id VARCHAR(255) PRIMARY KEY NOT NULL,
                amount_usd FLOAT NOT NULL
            );
        ")->execute();

        // insert data into temp table
        $stmt = $connection->prepare("
            INSERT INTO $tempTable
                SELECT id, Round((af_revenue/rate), 5) AS amount_usd
                FROM (
                    SELECT
                        actions.id
                        , af_currency
                        , (CASE WHEN af_revenue IS NULL THEN 0 ELSE af_revenue END)
                        ,currency.rate
                    FROM actions
                        LEFT JOIN currency ON currency.name = LOWER(actions.af_currency)
                    WHERE af_currency <> '' AND event_name = '$eventName' AND app_id = '$appId'
                )

        ")->execute();
        $stmt = $connection->prepare("
            UPDATE actions
                SET amount_usd = actions_tmp.amount_usd
            FROM (SELECT * FROM (SELECT * FROM $tempTable) AS actions_tmp1) AS actions_tmp
            WHERE actions.id = actions_tmp.id;
        ")->execute();
        // fetch all data for next process.
        $stmt = $connection->prepare("
            SELECT * FROM $tempTable;
        ");
        $stmt->execute();
        $listAction = $stmt->fetchAll();
        // drop temp table
        $stmt = $connection->prepare("
             DROP TABLE $tempTable;
        ")->execute();
        $stmt = $connection->prepare("reset query_group;");
        $stmt->execute();
        $connection->commit();

        return $listAction;
    }

    public function countByAppId($appId) {
        if (!$appId){
            throw new \Exception("missing param : appId");
        }
        $query = "
            SELECT COUNT(id) as total
                FROM actions
                WHERE app_id='".$appId."'
        ";
        //echo $query;
        $stmtQueryGroup = $this->_em->getConnection()->prepare("set query_group to 'ak_low_priority_long_processing_time';");
        $stmtQueryGroup->execute();
        $stmt = $this->_em->getConnection()->prepare($query);
        $stmt->execute();
        $stmtQueryGroup = $this->_em->getConnection()->prepare("reset query_group;");
        $result = $stmt->fetchAll();
        return $result[0]['total'];
    }

    public function getRecentInAppEventByAppId ($appId) {
        if (!$appId){
            throw new \Exception("missing param : appId");
        }
        $query = "
            SELECT  act.id,act.event_name,act.amount_usd,iac.event_friendly_name,iac.tag_as_iap,iac.icon,iac.color,act.happened_at
            FROM actions act left join inappevent_configs iac on act.event_name = iac.event_name
            WHERE act.app_id='".$appId."' AND act.action_type <> 1 order by act.happened_at limit 5 offset 0
        ";
        //echo $query;
        $stmtQueryGroup = $this->_em->getConnection()->prepare("set query_group to 'ak_low_priority_long_processing_time';");
        $stmtQueryGroup->execute();
        $stmt = $this->_em->getConnection()->prepare($query);
        $stmt->execute();
        $stmtQueryGroup = $this->_em->getConnection()->prepare("reset query_group;");
        $result = $stmt->fetchAll();
        return $result;
    }

    public function getDistinctEventNameByAppId ($appId) {
        if (!$appId){
            throw new \Exception("missing param : appId");
        }
        $query = "
            SELECT  DISTINCT act.event_name,iac.event_friendly_name
            FROM actions act left join inappevent_configs iac on act.event_name = iac.event_name
            WHERE act.app_id='".$appId."' AND act.action_type <> 1 order by act.happened_at
        ";
        //echo $query;
        $stmtQueryGroup = $this->_em->getConnection()->prepare("set query_group to 'ak_low_priority_long_processing_time';");
        $stmtQueryGroup->execute();
        $stmt = $this->_em->getConnection()->prepare($query);
        $stmt->execute();
        $stmtQueryGroup = $this->_em->getConnection()->prepare("reset query_group;");
        $result = $stmt->fetchAll();
        return $result;
    }

    public function countDeviceByListAppId(array $listAppId)
    {
        if (!$listAppId){
            throw new \Exception("missing param : appId");
        }
        $listAppIdStr = implode("','", $listAppId);
        $query = "
            SELECT APPROXIMATE COUNT(DISTINCT device_id) AS total
            FROM actions
            WHERE app_id IN ('$listAppIdStr')
        ";
        $stmtQueryGroup = $this->_em->getConnection()->prepare("set query_group to 'ak_low_priority_long_processing_time';");
        $stmtQueryGroup->execute();
        $stmt = $this->_em->getConnection()->prepare($query);
        $stmt->execute();
        $stmtQueryGroup = $this->_em->getConnection()->prepare("reset query_group;");
        $stmtQueryGroup->execute();

        return $stmt->fetchColumn(0);
    }

    public function highestAmount($appId, $timestamp, $inAppPurchase)
    {
        $dtNow = new \DateTime();
        $dt = new \DateTime('@'.$timestamp);
        $dt->setTimezone($dtNow->getTimezone());
        $gteDt = clone $dt;
        $lteDt = clone $dt;
        $gteDt->modify('first day of last month');
        $lteDt->modify('last day of last month');
        $query = "
            SELECT MAX(amount_usd) AS highest_amount
            FROM actions
            WHERE app_id=:appId
                AND event_name=:eventName
                AND happened_at >= :gte
                AND happened_at <= :lte
        ";
        $stmt = $this->_em->getConnection()->prepare($query);
        $stmt->bindValue('appId', $appId);
        $stmt->bindValue('eventName', $inAppPurchase);
        $stmt->bindValue('gte', $gteDt->getTimestamp());
        $stmt->bindValue('lte', $lteDt->getTimestamp());
        $stmt->execute();

        return $stmt->fetchColumn(0);
    }

    public function totalSales($listAppId, $timestamp)
    {
        if (!$listAppId){
            throw new \Exception("missing param : appId");
        }
        $dtNow = new \DateTime();
        $dt = new \DateTime('@'.$timestamp);
        $dt->setTimezone($dtNow->getTimezone());
        $gteDt = clone $dt;
        $lteDt = clone $dt;
        $gteDt->modify('first day of this month');
        $lteDt->modify('last day of this month');
        $listAppIdStr = implode("','", $listAppId);
        $query = "
            SELECT MAX(amount_usd) AS total_sales
            FROM actions
            WHERE app_id IN ('$listAppIdStr')
                AND happened_at >= :gte
                AND happened_at <= :lte
        ";
        $stmt = $this->_em->getConnection()->prepare($query);
        $stmt->bindValue('gte', $gteDt->getTimestamp());
        $stmt->bindValue('lte', $lteDt->getTimestamp());
        $stmt->execute();

        return $stmt->fetchColumn(0);
    }

    public function mostPopularAmount($appId, $timestamp, $inAppPurchase)
    {
        $dtNow = new \DateTime();
        $dt = new \DateTime('@'.$timestamp);
        $dt->setTimezone($dtNow->getTimezone());
        $gteDt = clone $dt;
        $lteDt = clone $dt;
        $gteDt->modify('first day of this month');
        $lteDt->modify('last day of this month');
        $query = "
            SELECT device_id, amount_usd, COUNT(*) AS freq
            FROM actions
            WHERE app_id=:appId
                AND event_name=:eventName
                AND happened_at >= :gte
                AND happened_at <= :lte
            GROUP BY device_id, amount_usd
            ORDER BY freq DESC
            LIMIT 1
        ";
        $stmt = $this->_em->getConnection()->prepare($query);
        $stmt->bindValue('appId', $appId);
        $stmt->bindValue('eventName', $inAppPurchase);
        $stmt->bindValue('gte', $gteDt->getTimestamp());
        $stmt->bindValue('lte', $lteDt->getTimestamp());
        $stmt->execute();
        $result = $stmt->fetch();
        $amountUsd = 0;
        if (!empty($result)) {
            $amountUsd = $result['amount_usd'];
        }

        return $amountUsd;
    }

    public function top1CountryInstall(
        $appId
        , $timestamp
    )
    {
        $dtNow = new \DateTime();
        $dt = new \DateTime('@'.$timestamp);
        $dt->setTimezone($dtNow->getTimezone());
        $gteDt = clone $dt;
        $lteDt = clone $dt;
        $gteDt->modify('first day of last month');
        $lteDt->modify('last day of last month');
        $query = "
            SELECT country_code, COUNT(*) AS install_count
            FROM actions
              INNER JOIN devices ON (actions.device_id = devices.id)
            WHERE app_id=:appId
                AND action_type = 1
                AND happened_at >= :gte
                AND happened_at <= :lte
            GROUP BY country_code
            ORDER BY install_count DESC
            LIMIT 1
        ";
        $stmt = $this->_em->getConnection()->prepare($query);
        $stmt->bindValue('appId', $appId);
        $stmt->bindValue('gte', $gteDt->getTimestamp());
        $stmt->bindValue('lte', $lteDt->getTimestamp());
        $stmt->execute();

        return $stmt->fetch();
    }

    public function top3CityInstallFromCountry(
         $appId
        , $timestamp
        , $countryCode
    )
    {
        $dtNow = new \DateTime();
        $dt = new \DateTime('@'.$timestamp);
        $dt->setTimezone($dtNow->getTimezone());
        $gteDt = clone $dt;
        $lteDt = clone $dt;
        $gteDt->modify('first day of last month');
        $lteDt->modify('last day of last month');
        $query = "
            SELECT city, COUNT(*) AS install_count
            FROM actions
              INNER JOIN devices ON (actions.device_id = devices.id)
            WHERE app_id=:appId
                AND action_type = 1
                AND happened_at >= :gte
                AND happened_at <= :lte
                AND country_code = :countryCode
            GROUP BY city
            ORDER BY install_count DESC
            LIMIT 3
        ";
        $stmt = $this->_em->getConnection()->prepare($query);
        $stmt->bindValue('appId', $appId);
        $stmt->bindValue('gte', $gteDt->getTimestamp());
        $stmt->bindValue('lte', $lteDt->getTimestamp());
        $stmt->bindValue('countryCode', $countryCode);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
