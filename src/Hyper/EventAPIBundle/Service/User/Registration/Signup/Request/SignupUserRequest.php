<?php
namespace Hyper\EventAPIBundle\Service\User\Registration\Signup\Request;

class SignupUserRequest
{
    private $id;
    private $username;
    private $password;
    private $email;
    private $name;
    private $userType;

    public function __construct(array $userInfo)
    {
        $this->setId($userInfo);
        $this->setUsername($userInfo);
        $this->setPassword($userInfo);
        $this->setEmail($userInfo);
        $this->setName($userInfo);
        $this->setUserType($userInfo);

        return $this;
    }

    protected function setId($userInfo)
    {
        if (empty($userInfo['id'])) {
            throw new \Exception('user_info[id] must be a value.');
        }
        $this->id = $userInfo['id'];

        return $this;
    }

    protected function setUsername($userInfo)
    {
        if (empty($userInfo['username'])) {
            throw new \Exception('user_info[username] must be a value.');
        }
        $this->username = $userInfo['username'];

        return $this;
    }

    protected function setPassword($userInfo)
    {
        if (empty($userInfo['password'])) {
            throw new \Exception('user_info[password] must be a value.');
        }
        $this->password = $userInfo['password'];

        return $this;
    }

    protected function setEmail($userInfo)
    {
        if (empty($userInfo['email'])) {
            throw new \Exception('user_info[email] must be a value.');
        }
        if (filter_var($userInfo['email'], FILTER_VALIDATE_EMAIL) === false) {
            throw new \Exception('user_info[email] is invalid email address.');
        }
        $this->email = $userInfo['email'];

        return $this;
    }

    protected function setName($userInfo)
    {
        if (empty($userInfo['name'])) {
            throw new \Exception('user_info[name] must be a value.');
        }
        $this->name = $userInfo['name'];

        return $this;
    }

    protected function setUserType($userInfo)
    {
        if (empty($userInfo['user_type'])) {
            throw new \Exception('user_info[user_type] must be a value.');
        }
        $this->userType = $userInfo['user_type'];

        return $this;
    }

    public function userId()
    {
        return $this->id;
    }

    public function username()
    {
        return $this->username;
    }

    public function password()
    {
        return $this->password;
    }

    public function email()
    {
        return $this->email;
    }

    public function name()
    {
        return $this->name;
    }

    public function userType()
    {
        return $this->userType;
    }
}