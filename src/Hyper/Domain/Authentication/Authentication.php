<?php

namespace Hyper\Domain\Authentication;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Authentication
 *
 * @ORM\Table(name="authentication")
 * @ORM\Entity(repositoryClass="Hyper\DomainBundle\Repository\Authentication\DTAuthenticationRepository")
 * @ExclusionPolicy("all")
 */
class Authentication implements UserInterface, \Serializable
{
    const USER_TYPE_ADMIN = 1;
    const USER_TYPE_CLIENT = 0;
    const ROLE_ADMIN = 'ROLE_AK_ADMIN';
    const ROLE_CLIENT = 'ROLE_AK_CLIENT';
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;

    public function __construct()
    {
        $this->id = uniqid('',true);
        $this->updated = strtotime(date('Y-m-d h:i:s'));
    }

    /**
     * @ORM\Column(name="id", type="string", length=255, nullable=false)")
     * @ORM\Id
     * @Expose
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="username", type="string", length=255, nullable=false)
     * @Expose
     */
    private $username;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     * @Expose
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="img_path", type="string", length=255, nullable=true)
     * @Expose
     */
    private $imgPath;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=255, nullable=false)
     * @Expose
     */
    private $password;

    /**
     * @var string
     *
     * @ORM\Column(name="reset_password_token", type="string", length=255, nullable=true)
     * @Expose
     */
    private $resetPasswordToken;

    /**
     * @var string
     *
     * @ORM\Column(name="reset_password_expired", type="integer", nullable=true)
     * @Expose
     */
    private $resetPasswordExpired;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string",nullable=false, length=255)
     * @Expose
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="application_id", type="string", length=13107, nullable=true)
     * @Expose
     */
    private $application_id;

    /**
     * @var string
     *
     * @ORM\Column(name="client_id", type="string", length=13107, nullable=true)
     * @Expose
     */
    private $clientId;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_type", type="integer",options={"default"=0})
     * @Expose
     */
    private $userType;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer",options={"default"=0})
     * @Expose
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="api_key", type="string", length=255, nullable=true)
     * @Expose
     */
    private $apiKey;

    /**
     * @var integer
     *
     * @ORM\Column(name="created", type="integer", nullable=false)
     * @Expose
     */
    private $created;

    /**
     * @var integer
     *
     * @ORM\Column(name="updated", type="integer", nullable=false,)
     * @Expose
     */
    private $updated;

     /**
     * @var string
     *
     * @ORM\Column(name="last_login", type="integer")
     * @Expose
     */
    private $lastLogin;


    /**
     * @var string
     *
     * @ORM\Column(name="ip", type="string")
     * @Expose
     */
    private $ip;


    /**
     * @var string
     *
     * @ORM\Column(name="location", type="string")
     * @Expose
     */
    private $location;

    /**
     * @var integer
     *
     * @ORM\Column(name="browser_name", type="string")
     * @Expose
     */
    private $browserName;

    /**
     * @var integer
     *
     * @ORM\Column(name="browser_version", type="string")
     * @Expose
     */
    private $browserVersion;

    /**
     * @var integer
     *
     * @ORM\Column(name="os_name", type="string")
     * @Expose
     */
    private $osName;

    /**
     * @var integer
     *
     * @ORM\Column(name="os_version", type="string")
     * @Expose
     */
    private $osVersion;

    /**
     * @var integer
     *
     * @ORM\Column(name="device_type", type="string")
     * @Expose
     */
    private $deviceType;

    /**
     * @var integer
     *
     * @ORM\Column(name="show_tutorial", type="integer")
     * @Expose
     */
    private $showTutorial;

    /**
     * List app id of app platform base on client
     * Must not an ORM mapping field
     *
     **/
    private $appId = [];

    /**
     * List s3 folder of app title base on client
     * Must not an ORM mapping field
     *
     **/
    private $s3Folder = [];

    /**
     * Status limit of account
     * Must not an ORM mapping field
     *
     **/
    private $isLimitAccount;

    /**
     * Set id
     *
     * @param string $id
     * @return Authentication
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set username
     *
     * @param string $username
     * @return Authentication
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Authentication
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set imgPath
     *
     * @param string $imgPath
     * @return Authentication
     */
    public function setImgPath($imgPath)
    {
        $this->imgPath = $imgPath;

        return $this;
    }

    /**
     * Get imgPath
     *
     * @return string
     */
    public function getImgPath()
    {
        return $this->imgPath;
    }

    /**
     * Set password
     *
     * @param string $password
     * @return Authentication
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set reset password token
     *
     * @param string $password
     * @return Authentication
     */
    public function setResetPasswordToken($resetPasswordToken)
    {
        $this->resetPasswordToken = $resetPasswordToken;

        return $this;
    }

    /**
     * Get reset password expire
     *
     * @return string
     */
    public function getResetPasswordToken()
    {
        return $this->resetPasswordToken;
    }

    /**
     * Set reset password expired
     *
     * @param string $password
     * @return Authentication
     */
    public function setResetPasswordExpired($resetPasswordExpired)
    {
        $this->resetPasswordExpired= $resetPasswordExpired;

        return $this;
    }

    /**
     * Get reset password expired
     *
     * @return string
     */
    public function getResetPasswordExpired()
    {
        return $this->resetPasswordExpired;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return Authentication
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set application_id
     *
     * @param string $applicationId
     * @return Authentication
     */
    public function setApplicationId($applicationId)
    {
        $this->application_id = $applicationId;

        return $this;
    }

    /**
     * Get application_id
     *
     * @return string
     */
    public function getApplicationId()
    {
        return $this->application_id;
    }

    /**
     * Set clientId
     *
     * @param string $clientId
     * @return Authentication
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;

        return $this;
    }

    /**
     * Get clientId
     *
     * @return string
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * Set userType
     *
     * @param integer $userType
     * @return Authentication
     */
    public function setUserType($userType)
    {
        $this->userType = $userType;

        return $this;
    }

    /**
     * Get userType
     *
     * @return integer
     */
    public function getUserType()
    {
        return $this->userType;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return Authentication
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set apiKey
     *
     * @param string $apiKey
     * @return Authentication
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * Get apiKey
     *
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * Set created
     *
     * @param integer $created
     * @return Authentication
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return integer
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated
     *
     * @param integer $updated
     * @return Authentication
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return integer
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set Last Login
     *
     * @param string $lastLogin
     * @return Authentication
     */
    public function setLastLogin($lastLogin)
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    /**
     * Get Last Login
     *
     * @return string
     */
    public function getLastLogin()
    {
        return $this->lastLogin;
    }

    /**
     * Set IP
     *
     * @param string $ip
     * @return Authentication
     */
    public function setIp($ip)
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * Get IP
     *
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * Set Location
     *
     * @param string $location
     * @return Authentication
     */
    public function setLocation($location)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get Location
     *
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Set Browser Name
     *
     * @param string $browserName
     * @return Authentication
     */
    public function setBrowserName($browserName)
    {
        $this->browserName = $browserName;

        return $this;
    }

    /**
     * Get Browser Name
     *
     * @return string
     */
    public function getBrowserName()
    {
        return $this->browserName;
    }

    /**
     * Set Browser Version
     *
     * @param string $browserName
     * @return Authentication
     */
    public function setBrowserVersion($browserVersion)
    {
        $this->browserVersion = $browserVersion;

        return $this;
    }

    /**
     * Get Browser Version
     *
     * @return string
     */
    public function getBrowserVersion()
    {
        return $this->browserVersion;
    }

    /**
     * Set OS Name
     *
     * @param string $osName
     * @return Authentication
     */
    public function setOsName($osName)
    {
        $this->osName = $osName;

        return $this;
    }

    /**
     * Get OS Name
     *
     * @return string
     */
    public function getOsName()
    {
        return $this->osName;
    }

    /**
     * Set OS Version
     *
     * @param string $osVersion
     * @return Authentication
     */
    public function setOsVersion($osVersion)
    {
        $this->osVersion = $osVersion;

        return $this;
    }

    /**
     * Get Browser Version
     *
     * @return string
     */
    public function getOsVersion()
    {
        return $this->osVersion;
    }

    /**
     * Set Device Type
     *
     * @param string $deviceType
     * @return Authentication
     */
    public function setDeviceType($deviceType)
    {
        $this->deviceType = $deviceType;

        return $this;
    }

    /**
     * Get platform
     *
     * @return integer
     */
    public function getDeviceType()
    {
        return $this->deviceType;
    }

    public function getSalt()
    {
        return null;
    }

    public function getRoles()
    {
        $roleName = '';
        if ($this->userType == self::USER_TYPE_ADMIN) {
            $roleName = 'AK_ADMIN';
        } elseif ($this->userType == self::USER_TYPE_CLIENT) {
            $roleName = 'AK_CLIENT';
        }
        return array('ROLE_'.$roleName);
    }

    public function setShowTutorial($showTutorial)
    {
        $this->showTutorial = $showTutorial;

        return $this;
    }

    public function getShowTutorial()
    {
        return $this->showTutorial;
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

    public function setS3Folder($s3Folder)
    {
        $this->s3Folder = $s3Folder;

        return $this;
    }

    public function getS3Folder()
    {
        return $this->s3Folder;
    }

    public function setLimitAccount($isLimitAccount)
    {
        $this->isLimitAccount = $isLimitAccount;

        return $this;
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

    public function isDemoAccount()
    {
        if ($this->username && in_array($this->username, ['DemoClients'])) {
            return true;
        }

        return false;
    }

    public function isLimitAccount()
    {
        return $this->isLimitAccount;
    }
}
