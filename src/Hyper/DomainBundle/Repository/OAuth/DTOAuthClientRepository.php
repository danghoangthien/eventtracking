<?php 

namespace Hyper\DomainBundle\Repository\OAuth;

use Doctrine\ORM\EntityRepository,
    Hyper\Domain\OAuth\OAuthClient,
    Hyper\Domain\OAuth\OAuthClientRepository;

class DTOAuthClientRepository extends EntityRepository implements OAuthClientRepository 
{
    
}