<?php 

namespace Hyper\DomainBundle\Repository\UserLoginHistory;

use Doctrine\ORM\EntityRepository,
    Hyper\Domain\UserLoginHistory\UserLoginHistory,
    Hyper\Domain\UserLoginHistory\UserLoginHistoryRepository;

class DTUserLoginHistoryRepository extends EntityRepository implements UserLoginHistoryRepository 
{
    
    public function storeUserLoginHistory(UserLoginHistory $userLoginHistory)
    {
        $this->_em->persist($userLoginHistory);
        
        return $userLoginHistory;
    }
    
    public function getListRecentLogin($size = 3)
    {
        $query = "
            SELECT * 
            FROM (
              SELECT ulh1.id,
                     ulh1.auth_id,
                     ulh1.last_login,
                     ulh1.ip,
                     ulh1.location,
                     ulh1.browser_name,
                     ulh1.os_name,
                     ulh1.os_version,
                     ulh1.device_type,
                     au.username,
                     row_number() OVER (PARTITION BY ulh1.auth_id ORDER BY ulh1.last_login DESC) AS rn 
              FROM   user_login_history ulh1
              INNER JOIN authentication au ON au.id = ulh1.auth_id
              ORDER BY ulh1.last_login DESC
            ) AS ulh
            WHERE ulh.rn = 1
            LIMIT {$size};
        ";
        $stmt = $this->_em->getConnection()->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}