<?php

namespace Hyper\DomainBundle\Repository\Action;

use Doctrine\ORM\EntityRepository;
use Hyper\Domain\Action\TransactionActionRepository;
use Hyper\Domain\Action\TransactionAction;

/**
 * AddToWishlistActionRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class DTTransactionActionRepository extends EntityRepository implements TransactionActionRepository
{
    public function save(TransactionAction $transactionAction) {
        $this->_em->persist($transactionAction);
        //$this->_em->flush();
    }
    
    public function completeTransaction() {
        $this->_em->flush();
        $this->_em->clear();
    }
    
    public function closeConnection() {
        $this->_em->close();
    }
    
    public function getDeviceTransactionsByAppID() {
        /*
        $applicationRepo = $this->_em->getRepository('Hyper\Domain\Application\Application');
        $applicationIds = $applicationRepo->getApplicationByAppIds();
        $qb = $this->createQueryBuilder('ta');
        $query = $qb->select('ta')
                    ->where($qb->expr()->in('application_id'));*/
    }
}