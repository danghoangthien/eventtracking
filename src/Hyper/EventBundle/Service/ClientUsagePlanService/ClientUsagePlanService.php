<?php
namespace Hyper\EventBundle\Service\ClientUsagePlanService;

use \Symfony\Component\DependencyInjection\ContainerInterface
    , Hyper\Domain\Client\Client
    , Hyper\Domain\Application\ApplicationTitle
    , Hyper\Domain\Client\ClientRepository
    , Hyper\Domain\Client\ClientAppTitleRepository
    , Hyper\Domain\Action\ActionRepository
    , Hyper\Domain\Application\ApplicationPlatformRepository
    , Hyper\EventBundle\Service\Cached\Client\ClientUsagePlanCached;

class ClientUsagePlanService
{
    protected $clientRepo;
    protected $clientAppTitleRepo;
    protected $actionRepo;
    protected $appPlatformRepo;
    protected $clientUsagePlanCached;

    public function __construct(
        ClientRepository $clientRepo
        , ClientAppTitleRepository $clientAppTitleRepo
        , ActionRepository $actionRepo
        , ApplicationPlatformRepository $appPlatformRepo
        , ClientUsagePlanCached $clientUsagePlanCached
    ) {
        $this->clientRepo = $clientRepo;
        $this->clientAppTitleRepo = $clientAppTitleRepo;
        $this->actionRepo = $actionRepo;
        $this->appPlatformRepo = $appPlatformRepo;
        $this->clientUsagePlanCached = $clientUsagePlanCached;
    }

    public function handle()
    {
        $listClient = $this->clientRepo->findAll();
        //$listClient = $this->clientRepo->findBy(['id' => '575161d25c0546.06910473']);
        if (!empty($listClient)) {
            foreach ($listClient as $client) {
                $clientId = $client->getId();
                $listClientAppTitle = $this->clientAppTitleRepo->findByClient($clientId);
                $clientUsagePlanData = [
                    'total_device' => 0
                ];
                if (!empty($listClientAppTitle)) {
                    $listAppId = [];
                    foreach ($listClientAppTitle as $clientAppTitle) {
                        $client = '';
                        $appTitle = '';
                        if ($clientAppTitle->getClient() instanceof Client) {
                            $client = $clientAppTitle->getClient();
                        }
                        if ($clientAppTitle->getAppTitle() instanceof ApplicationTitle) {
                            $appTitle = $clientAppTitle->getAppTitle();
                        }
                        if (empty($client) || empty($appTitle)) {
                            continue;
                        }
                        $appTitleId = $appTitle->getId();
                        $listAppPlatform = $this->appPlatformRepo->findByAppTitle($appTitleId);
                        if (!empty($listAppPlatform)) {
                            foreach ($listAppPlatform as $appPlatform) {
                                $listAppId[] = $appPlatform->getAppId();
                            }
                        }
                    }
                    if (!empty($listAppId)) {
                        $clientUsagePlanData = [
                            'total_device' => $this->actionRepo->countDeviceByListAppId($listAppId)
                        ];
                    }
                    $this->clientUsagePlanCached->hset($clientId, json_encode($clientUsagePlanData));
                }
            }
        }
    }
}