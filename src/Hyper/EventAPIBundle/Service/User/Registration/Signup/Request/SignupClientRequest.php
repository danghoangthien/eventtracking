<?php
namespace Hyper\EventAPIBundle\Service\User\Registration\Signup\Request;

use Hyper\EventAPIBundle\Service\User\Registration\Signup\Request\SignupClientAppTitleRequest;

class SignupClientRequest
{
    private $clientId;
    private $clientName;
    private $clientApp;
    private $accountType;
    private $usagePlanType;
    private $signupClientAppTitleRequest;

    public function __construct(array $clientInfo)
    {
        $this->setClientId($clientInfo);
        $this->setClientName($clientInfo);
        $this->setClientApp($clientInfo);
        $this->setAccountType($clientInfo);
        $this->setUsagePlanType($clientInfo);
        $this->setClientAppTitle($clientInfo);

        return $this;
    }

    protected function setClientId($clientInfo)
    {
        $this->clientId = $clientInfo['id'];

        return $this;
    }

    protected function setClientName($clientInfo)
    {
        if (empty($clientInfo['client_name'])) {
            throw new \Exception('client_info[client_name] must be a value.');
        }
        $this->clientName = $clientInfo['client_name'];

        return $this;
    }

    protected function setClientApp($clientInfo)
    {
        if (empty($clientInfo['client_app'])) {
            throw new \Exception('client_info[client_app] must be a value.');
        }
        $this->clientApp = $clientInfo['client_app'];

        return $this;
    }

    protected function setAccountType($clientInfo)
    {
        if (empty($clientInfo['account_type'])) {
            throw new \Exception('client_info[account_type] must be a value.');
        }
        $this->accountType = $clientInfo['account_type'];

        return $this;
    }

    protected function setUsagePlanType($clientInfo)
    {
        if (empty($clientInfo['usage_plan_type'])) {
            throw new \Exception('client_info[usage_plan_type] must be a value.');
        }
        $this->usagePlanType = $clientInfo['usage_plan_type'];

        return $this;
    }

    protected function setClientAppTitle($clientInfo)
    {
        if (!is_array($clientInfo['app_title'])) {
            throw new \Exception('client_info[app_title] must be a array.');
        }
        if (empty($clientInfo['app_title'])) {
            throw new \Exception('client_info[app_title] must be a value.');
        }
        foreach ($clientInfo['app_title'] as $key => $value) {
            $this->signupClientAppTitleRequest[] = new SignupClientAppTitleRequest($value);
        }
    }

    public function clientId()
    {
        return $this->clientId;
    }

    public function clientName()
    {
        return $this->clientName;
    }

    public function clientApp()
    {
        return $this->clientApp;
    }

    public function accountType()
    {
        return $this->accountType;
    }

    public function usagePlanType()
    {
        return $this->usagePlanType;
    }

    public function signupClientAppTitleRequest()
    {
        return $this->signupClientAppTitleRequest;
    }
}