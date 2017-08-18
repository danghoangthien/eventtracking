<?php 

namespace Hyper\DomainBundle\Repository\OAuth;

use Doctrine\ORM\EntityRepository,
    Hyper\Domain\OAuth\OAuthAuthorizationCode,
    Hyper\Domain\OAuth\OAuthAuthorizationCodeRepository;

class DTOAuthAuthorizationCodeRepository extends EntityRepository implements OAuthAuthorizationCodeRepository 
{
    
}