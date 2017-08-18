<?php

namespace Hyper\Adops\WebBundle\DomainBundle;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

/**
 * @author Carl Pham <vanca.vnn@gmail.com>
 */
class WebserviceUserProvider implements UserProviderInterface
{
    private $container;
    
    public function __construct($container)
    {
        $this->container = $container;
    }
    
    public function loadUserByUsername($username)
    {
        $userRepo = $this->container->get('adops.web.user.repository');
        $userData = $userRepo->findUserByUserName($username);
        // pretend it returns an array on success, false if there is no user

        if ($userData) {
            // $password = '$2y$13$u5XU6iyjiRXDDSX24toE/e3myPQPif4prNbueuRN2Ml1JEA9hQAYC';
            // $username = 'admin';
            // $salt = null;
            // $roles = ['ROLE_USER'];
            $userId = $userData->getId();
            $password = $userData->getPassword();
            $roles = $userData->getRoles();
            $appAccessIds = $userData->getAppId();
            $avatar = $userData->getAvatar();
            $fullName = $userData->getFullname();
            $team = $userData->getTeam();
            $appAccessIds = $userData->getAppId();

            return new WebserviceUser(
                $userId, $username, $password, null, 
                $roles, $appAccessIds, $avatar, $fullName, 
                $team, $appAccessIds
            );
        }

        return false;
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof WebserviceUser) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', get_class($user))
            );
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    public function supportsClass($class)
    {
        return $class === 'Hyper\Adops\WebBundle\DomainBundle\WebserviceUser';
    }
}