<?php

namespace Hyper\EventBundle\Service;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Hyper\Domain\Authentication\Authentication;
use Hyper\Domain\Client\Client;
use Hyper\EventBundle\Service\Cached\Client\ClientUsagePlanCached;

class UserProvider implements UserProviderInterface
{
    private $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function loadUserByUsername($username)
    {
        $authRepo = $this->container->get('authentication_repository');
        $authFoundByUsername = $authRepo->loadUserByUsername($username);
        if ($authFoundByUsername instanceof Authentication) {
            list($appId, $s3Folder) = $this->getAppIdS3Folder($authFoundByUsername);
            $authFoundByUsername->setAppId($appId)->setS3Folder($s3Folder);
            $authFoundByUsername->setLimitAccount($this->checkLimitAccount($authFoundByUsername));

            return $authFoundByUsername;
        }

        throw new UsernameNotFoundException(
            sprintf('Username "%s" does not exist.', $username)
        );
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof Authentication) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', get_class($user))
            );
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    public function supportsClass($class)
    {
        return $class === 'Hyper\Domain\Authentication\Authentication';
    }

    private function getAppIdS3Folder($authFoundByUsername)
    {
        $clientId = array($authFoundByUsername->getClientId());
        if (!empty($clientId)) {
            $clientId = $clientId[0];
        }
        $listAppId = [];
        $listS3Folder = [];
        $client = $this->container->get('client_repository')->find($clientId);
        $listClientAppTitle = $this->container->get('client_app_title_repository')->findBy(['client' => $clientId]);
        $listAppTitleId = [];
        if (!empty($listClientAppTitle)) {
            foreach ($listClientAppTitle as $clientAppTitle) {
            	try {
            		$listAppTitleId[] = $clientAppTitle->getAppTitle()->getId();
	                $listS3Folder[] = $clientAppTitle->getAppTitle()->getS3Folder();
            	} catch(\Exception $e) {

            	}
            }
        }
        if (!empty($listAppTitleId)) {
            $listAppFlatform = $this->container->get('application_platform_repository')->findByAppTitle($listAppTitleId);
            if (!empty($listAppFlatform)) {
                foreach ($listAppFlatform as $appPlatform) {
                    $listAppId[] = $appPlatform->getAppId();
                }
            }
        }

        return [$listAppId, $listS3Folder];
    }

    private function checkLimitAccount($authFoundByUsername)
    {
        $isLimitAccount = false;
        $client = $this->container->get('client_repository')->findOneBy(['id' => $authFoundByUsername->getClientId()]);
        if ($client instanceof Client) {
            $clientUsagePlanCached = new ClientUsagePlanCached($this->container);
            $clientUsagePlanData = $clientUsagePlanCached->hget($client->getId());
            if (!empty($clientUsagePlanData)) {
                $clientUsagePlanData = json_decode($clientUsagePlanData, true);
            } else {
                $clientUsagePlanData = [];
            }
            if (
                !empty($client->getUsagePlanType())
                && $client->getUsagePlanType() == Client::USAGE_PLAN_TYPE_FREE_PLAN
            ) {
                if (empty($client->getUserLimit())) {
                    $totalUser = 5000;
                } else {
                    $totalUser = $client->getUserLimit();
                }
            } elseif (
                !empty($client->getUsagePlanType())
                && $client->getUsagePlanType() == Client::USAGE_PLAN_TYPE_STUDIO_PLAN
            ) {
                if (empty($client->getUserLimit())) {
                    $totalUser = 20000;
                } else {
                    $totalUser = $client->getUserLimit();
                }
            } elseif (
                !empty($client->getUsagePlanType())
                && $client->getUsagePlanType() == Client::USAGE_PLAN_TYPE_BUSINESS_PLAN
            ) {
                if (empty($client->getUserLimit())) {
                    $totalUser = 100000;
                } else {
                    $totalUser = $client->getUserLimit();
                }
            } else {
                $totalUser = 5000;
            }
            if (
                $clientUsagePlanData['total_device'] > $totalUser
            ) {
                $isLimitAccount = true;
            }
        }

        return $isLimitAccount;
    }
}