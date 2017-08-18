<?php 

namespace Hyper\DomainBundle\Repository\OAuth;

use Doctrine\ORM\EntityRepository,
    Hyper\Domain\OAuth\OAuthAccessToken,
    Hyper\Domain\OAuth\OAuthAccessTokenRepository;

class DTOAuthAccessTokenRepository extends EntityRepository implements OAuthAccessTokenRepository 
{
    
}