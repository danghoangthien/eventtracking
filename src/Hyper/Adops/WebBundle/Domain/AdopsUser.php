<?php

namespace Hyper\Adops\WebBundle\Domain;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Util\Inflector;
use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;

/**
* @ORM\Entity(repositoryClass="Hyper\Adops\WebBundle\DomainBundle\Repository\DTUserRepository")
* @ORM\Table(name="adops_users")
*
* @author Carl Pham <vanca.vnn@gmail.com>
*/
class AdopsUser implements UserInterface, \Serializable
{
    const ROLE_USER__ADMIN = 'admin';
    const ROLE_USER_TRANSPARENT = 'transparent';
    const ROLE_USER_LIMITED = 'limited';

    /**
     * @ORM\Column(name="id", type="string", length=255, nullable=false)")
     * @ORM\Id
     */
    private $id;

    /**
     * @ORM\Column(name="username", type="string", length=25, unique=true)
     *
     * @Assert\NotBlank(
     *     message = "Username not blank!"
     * )
     */
    private $username;

    /**
     * @ORM\Column(name="password", type="string", length=64, unique=true)
     *
     * @Assert\NotBlank(
     *     message = "Password not blank!"
     * )
     */
    private $password;

    /**
     * @ORM\Column(name="email", type="string", length=60, unique=true)
     *
     * @Assert\NotBlank(
     *     message = "Email not blank!"
     * )
     */
    private $email;

    /**
     * @ORM\Column(name="fullname", type="string", length=255)
     */
    private $fullname;

    /**
     * @ORM\Column(name="team", type="string", length=255, nullable=true)
     */
    private $team;

    /**
     * @ORM\Column(name="is_active", type="boolean")
     */
    private $isActive;

    /**
     * @ORM\Column(name="type", type="string", length=255)
     *
     * @Assert\NotBlank(
     *     message = "Please choice Client Type!"
     * )
     */
    private $type;

    /**
     * @ORM\Column(name="app_id", type="string", nullable=true, length=65535)
     *
     */
    private $appId;

    /**
     * @ORM\Column(name="avatar", type="string", nullable=true, length=65535)
     *
     */
    private $avatar;

    public function __construct()
    {
        $this->id = uniqid('',true);
        $this->isActive = true;
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setFullname($fullname)
    {
        $this->fullname = $fullname;
        return $this;
    }

    public function getFullname()
    {
        return $this->fullname;
    }

    public function setTeam($team)
    {
        $this->team = $team;
        return $this;
    }

    public function getTeam()
    {
        return $this->team;
    }

    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getIsActive()
    {
        return $this->isActive;
    }

    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setAppId($appId)
    {
        $this->appId = $appId;
        return $this;
    }

    public function getAppId()
    {
        return $this->appId;
    }

    public function setAvatar($avatar)
    {
        $this->avatar = $avatar;
        return $this;
    }

    public function getAvatar()
    {
        return $this->avatar;
    }

    public function getSalt()
    {
        return null;
    }

    public function getRoles()
    {
        return array('ROLE_USER_'.strtoupper($this->type));
    }

    public function eraseCredentials()
    {
    }

     /** @see \Serializable::serialize() */
    public function serialize()
    {
        return serialize(array(
            $this->id,
            $this->username,
            $this->password,
            // see section on salt below
            // $this->salt,
        ));
    }

    /** @see \Serializable::unserialize() */
    public function unserialize($serialized)
    {
        list (
            $this->id,
            $this->username,
            $this->password,
            // see section on salt below
            // $this->salt
        ) = unserialize($serialized);
    }

    public function set($fieldName, $value)
    {
        $realFieldName = lcfirst(Inflector::classify($fieldName));
        $this->$realFieldName = $value;
    }

    public function setData($data)
    {
        if (!empty($data)) {
            foreach ($data as $fieldName => $value) {
                $this->set($fieldName, $value);
            }
        }

        return $this;
    }
}