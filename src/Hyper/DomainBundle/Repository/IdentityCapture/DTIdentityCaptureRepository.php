<?php

namespace Hyper\DomainBundle\Repository\IdentityCapture;

use Doctrine\ORM\EntityRepository;
use Hyper\Domain\IdentityCapture\IdentityCaptureRepository;

/**
 * IdentityCaptureRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class DTIdentityCaptureRepository extends EntityRepository implements IdentityCaptureRepository
{
    public function getDeviceLatestByEmail($email)
    {
        $query = "
            SELECT identity_capture.device_id
            FROM identity_capture
                INNER JOIN actions ON (actions.device_id = identity_capture.device_id)
            WHERE identity_capture.email = :email
            ORDER BY happened_at DESC
            LIMIT 1;
        ";
        $stmt = $this->_em->getConnection()->prepare($query);
        $stmt->bindValue('email', $email);
        $stmt->execute();

        return $stmt->fetchColumn(0);
    }
}