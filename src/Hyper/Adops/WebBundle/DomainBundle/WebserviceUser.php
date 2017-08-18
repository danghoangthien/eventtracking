<?php

namespace Hyper\Adops\WebBundle\DomainBundle;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;

/**
 * @author Carl Pham <vanca.vnn@gmail.com>
 */
class WebserviceUser implements UserInterface, EquatableInterface
{
    private $id;
    private $username;
    private $password;
    private $salt;
    private $roles;
    private $avatar;
    private $fullName;
    private $team;
    private $appId;

    public function __construct(
        $id,
        $username,
        $password,
        $salt,
        array $roles,
        $appAccessIds = null,
        $avatar = null,
        $fullName = null,
        $team = null,
        $appAccessIds = null)
    {
        $this->id = $id;
        $this->username = $username;
        $this->password = $password;
        $this->salt = $salt;
        $this->roles = $roles;
        $this->appId = $appAccessIds;
        $this->avatar = $avatar;
        $this->fullName = $fullName;
        $this->team = $team;
    }

    public function getRoles()
    {
        return $this->roles;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getSalt()
    {
        return $this->salt;
    }

    public function getUsername()
    {
        return $this->username;
    }
    
    public function getId()
    {
        return $this->id;
    }
    
    public function getAvatar()
    {
        return $this->avatar;
    }
    
    public function setAppId($appIds)
    {
        $this->appId = $appIds;
    }
    
    public function getAppId()
    {
        return $this->appId;
    }
    
    public function getFullName()
    {
        return $this->fullName;
    }
    
    public function getTeam()
    {
        return $this->team;
    }

    public function eraseCredentials()
    {
    }

    public function isEqualTo(UserInterface $user)
    {
        if (!$user instanceof WebserviceUser) {
            return false;
        }

        if ($this->password !== $user->getPassword()) {
            return false;
        }

        if ($this->salt !== $user->getSalt()) {
            return false;
        }

        if ($this->username !== $user->getUsername()) {
            return false;
        }

        return true;
    }
}