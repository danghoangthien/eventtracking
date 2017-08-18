<?php
namespace Hyper\EventAPIBundle\Service\User\Registration\ClientInfo;

use Hyper\Domain\Client\Client
    , Hyper\EventAPIBundle\Service\User\Registration\ClientInfo\ClientInfoRequest
    , Hyper\Domain\Application\ApplicationPlatformRepository
    , Hyper\Domain\Client\ClientAppTitleRepository
    , Hyper\Domain\Application\ApplicationTitleRepository;

class ClientInfoHandler
{
    private $appPlatformRepo;
    private $appTitleRepo;
    private $clientRepo;
    private $clientAppTitleRepo;

    public function __construct(
        ApplicationPlatformRepository $appPlatformRepo
        , ApplicationTitleRepository $appTitleRepo
        , ClientAppTitleRepository $clientAppTitleRepo
    ){
        $this->appPlatformRepo = $appPlatformRepo;
        $this->appTitleRepo = $appTitleRepo;
        $this->clientAppTitleRepo = $clientAppTitleRepo;
    }

    public function handle(ClientInfoRequest $clientInfoRequest)
    {
        $listAppId = $clientInfoRequest->listAppId();
        $ret = [];
        $listAppTitleByAppTitleId = $listAppTitleId = [];
        $listAppIdByAppTitle = [];
        $listAppPlatformEntity = $this->appPlatformRepo->findBy(['appId' => $listAppId]);
        if (!empty($listAppPlatformEntity)) {
            foreach ($listAppPlatformEntity as $key => $appPlatformEntity) {
                $appTitleId = $appPlatformEntity->getAppTitle()->getId();
                $listAppTitleId[] = $appTitleId;
                $listAppTitleByAppTitleId[$appTitleId] = $appPlatformEntity->getAppTitle();
                $listAppIdByAppTitle[$appTitleId][] = $appPlatformEntity->getAppId();
            }
            $clientAppTitle = $this->clientAppTitleRepo->findOneBy(['appTitle' => $listAppTitleId]);
            if (!empty($clientAppTitle)) {
                $clientEntity = $clientAppTitle->getClient();
                $ret = [
                    'id' => $clientEntity->getId()
                    , 'client_name' => $clientEntity->getClientName()
                    , 'account_type' => $clientEntity->getAccountType()
                    , 'app_title' => []
                ];
                if (!empty($listAppTitleByAppTitleId)) {
                    foreach ($listAppTitleByAppTitleId as $key => $appTitleEntity) {
                        $ret['app_title'][] = [
                            'id' => $appTitleEntity->getId()
                            , 'title' => $appTitleEntity->getTitle()
                            , 's3_folder' => $appTitleEntity->getS3Folder()
                            , 'app_platform' => $listAppIdByAppTitle[$appTitleEntity->getId()]
                        ];

                    }
                }
            }
        }

        return $ret;
    }
}