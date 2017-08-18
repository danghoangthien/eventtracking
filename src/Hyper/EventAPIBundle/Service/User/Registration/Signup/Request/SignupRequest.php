<?php
namespace Hyper\EventAPIBundle\Service\User\Registration\Signup\Request;

use Hyper\EventAPIBundle\Service\User\Registration\Signup\Request\SignupUserRequest
    , Hyper\EventAPIBundle\Service\User\Registration\Signup\Request\SignupClientRequest;

class SignupRequest
{
    private $signupUserRequest;
    private $signupClientRequest;

    public function __construct(
        array $userInfo
        , array $clientInfo
    )
    {
        $this->signupUserRequest = new SignupUserRequest($userInfo);
        $this->signupClientRequest = new SignupClientRequest($clientInfo);

        return $this;
    }

    public function signupClientRequest()
    {
        return $this->signupClientRequest;
    }

    public function signupUserRequest()
    {
        return $this->signupUserRequest;
    }
}