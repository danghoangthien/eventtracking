<?php

namespace Hyper\DomainBundle\Repository\Application;

use Doctrine\ORM\EntityRepository;

use Hyper\Domain\Application\ApplicationPlatformRepository;


/**
 * DTApplicationPlatformRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class DTApplicationPlatformRepository extends EntityRepository implements ApplicationPlatformRepository
{
    public function countLastActivityByAppTitleId($appTitleIds)
    {
        if(!is_array($appTitleIds) || count($appTitleIds) == 0){
            throw new \Exception("missing param : appTitleIds");
        }
        $appTitleIds = implode("','", $appTitleIds);
        $end = time();
        $start = $end - 24*60*60;
        $query = "
            SELECT COUNT(*) AS total
            FROM applications_platform
            WHERE app_title_id IN ('$appTitleIds') AND last_activity between $start and $end
        ";
        $stmtQueryGroup = $this->_em->getConnection()->prepare("set query_group to 'ak_high_concurrency_short_processing_time';");
        $stmtQueryGroup->execute();
        $stmt = $this->_em->getConnection()->prepare($query);
        $stmt->execute();
        $stmtQueryGroup = $this->_em->getConnection()->prepare("reset query_group;");
        $stmtQueryGroup->execute();
        return $stmt->fetchColumn(0);
    }
}