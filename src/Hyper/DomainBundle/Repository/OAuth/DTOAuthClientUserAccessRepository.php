<?php 

namespace Hyper\DomainBundle\Repository\OAuth;

use Doctrine\ORM\EntityRepository,
    Hyper\Domain\OAuth\OAuthClientUserAccess,
    Hyper\Domain\OAuth\OAuthClientUserAccessRepository;

class DTOAuthClientUserAccessRepository extends EntityRepository implements OAuthClientUserAccessRepository 
{
    public function save(OAuthClientUserAccess $cua){
        $this->_em->persist($cua);
        //$this->_em->flush();
    }
    
    public function completeTransaction(){
        //echo "flushing";
        $this->_em->flush();
        $this->_em->clear();
        //echo "flushed";
    }
}