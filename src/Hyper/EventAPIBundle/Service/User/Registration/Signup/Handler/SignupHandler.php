<?php
namespace Hyper\EventAPIBundle\Service\User\Registration\Signup\Handler;

use Hyper\Domain\Client\Client
    , Hyper\Domain\Client\ClientAppTitle
    , Hyper\Domain\Authentication\Authentication
    , Hyper\Domain\Application\ApplicationTitle
    , Hyper\Domain\Application\ApplicationPlatform
    , Hyper\EventAPIBundle\Service\User\Registration\Signup\Request\SignupRequest
    , Hyper\Domain\Application\ApplicationPlatformRepository
    , Hyper\Domain\Client\ClientRepository
    , Hyper\Domain\Client\ClientAppTitleRepository
    , Hyper\Domain\Application\ApplicationTitleRepository
    , Hyper\Domain\Authentication\AuthenticationRepository
    , Doctrine\ORM\EntityManager
    , Symfony\Component\DependencyInjection\ContainerInterface
    , Hyper\EventBundle\Service\Cached\App\AppCached
    , Hyper\EventBundle\Event\UserCreateEvent;

class SignupHandler
{
    private $container;
    private $appPlatformRepo;
    private $appTitleRepo;
    private $clientRepo;
    private $clientAppTitleRepo;
    private $authRepo;
    private $entityManager;
    private $appCached;

    public function __construct(
        ContainerInterface $container
        , ApplicationPlatformRepository $appPlatformRepo
        , ApplicationTitleRepository $appTitleRepo
        , ClientAppTitleRepository $clientAppTitleRepo
        , ClientRepository $clientRepo
        , AuthenticationRepository $authRepo
        , EntityManager $entityManager
        , AppCached $appCached
    ){
        $this->container = $container;
        $this->appPlatformRepo = $appPlatformRepo;
        $this->appTitleRepo = $appTitleRepo;
        $this->clientAppTitleRepo = $clientAppTitleRepo;
        $this->clientRepo = $clientRepo;
        $this->authRepo = $authRepo;
        $this->entityManager = $entityManager;
        $this->appCached = $appCached;
    }

    public function handle(SignupRequest $signupRequest)
    {
        $clientEntity = '';
        $this->entityManager->getConnection()->beginTransaction();
        if ($signupRequest->signupClientRequest()->clientId()) {
            $clientEntity = $this->clientRepo->find($signupRequest->signupClientRequest()->clientId());
        }
        if (!$clientEntity instanceof Client) {
            $clientEntity = new Client();
            $clientEntity
                ->setClientName($signupRequest->signupClientRequest()->clientName())
                ->setClientApp(json_encode($signupRequest->signupClientRequest()->clientApp()))
                ->setAccountType($signupRequest->signupClientRequest()->accountType())
                ->setCreated(time())
                ->setUsagePlanType($signupRequest->signupClientRequest()->usagePlanType());
            $this->entityManager->persist($clientEntity);
        }
        $authEntity = '';
        if ($signupRequest->signupUserRequest()->userId()) {
            $authEntity = $this->authRepo->find($signupRequest->signupUserRequest()->userId());
        }
        if (!$authEntity instanceof Authentication) {
            $authEntity = new Authentication();
            $authEntity
                ->setId($signupRequest->signupUserRequest()->userId())
                ->setUsername($signupRequest->signupUserRequest()->username())
                ->setName($signupRequest->signupUserRequest()->name())
                ->setPassword($signupRequest->signupUserRequest()->password())
                ->setEmail($signupRequest->signupUserRequest()->email())
                ->setClientId($clientEntity->getId())
                ->setUserType($signupRequest->signupUserRequest()->userType())
                ->setStatus(Authentication::STATUS_ACTIVE)
                ->setCreated(time());
            $this->entityManager->persist($authEntity);

        }
        foreach ($signupRequest->signupClientRequest()->signupClientAppTitleRequest() as $key => $signupClientAppTitleRequest) {
            $clientAppTitleEntity = '';
            if ($signupClientAppTitleRequest->id()) {
                $clientAppTitleEntity = $this->clientAppTitleRepo->findOneBy(
                    [
                        'client' => $clientEntity->getId()
                        , 'appTitle' => $signupClientAppTitleRequest->id()
                    ]
                );
            }
            if (!$clientAppTitleEntity instanceof ClientAppTitle) {
                $appTitleEntity = '';
                if ($signupClientAppTitleRequest->id()) {
                    $appTitleEntity = $this->appTitleRepo->find($signupClientAppTitleRequest->id());
                }
                if (!$appTitleEntity instanceof ApplicationTitle) {
                    $appTitleEntity = new ApplicationTitle();
                    $appTitleEntity->setTitle($signupClientAppTitleRequest->title())
                        ->setS3Folder($signupClientAppTitleRequest->title())
                        ->setStatus(1);
                    $this->entityManager->persist($appTitleEntity);
                    $appPlatformEntity = '';
                    foreach ($signupClientAppTitleRequest->appPlatform() as $key => $appId) {
                        if ($appId) {
                            $appPlatformEntity = $this->appPlatformRepo->findOneBy(
                                [
                                    'appTitle' => $appTitleEntity->getId()
                                    , 'appId' => $appId
                                ]
                            );
                            if (!$appPlatformEntity instanceof ApplicationPlatform) {
                                $appPlatformEntity = new ApplicationPlatform();
                                $appPlatformEntity
                                    ->setAppTitle($appTitleEntity)
                                    ->setAppId($appId);
                                $this->entityManager->persist($appPlatformEntity);
                                $this->appCached->hset($appId, $appTitleEntity->getS3Folder());
                            }
                        }
                    }
                }
                $clientAppTitleEntity = new ClientAppTitle();
                $clientAppTitleEntity
                    ->setClient($clientEntity)
                    ->setAppTitle($appTitleEntity);
                $this->entityManager->persist($clientAppTitleEntity);
            }
        }
        $this->entityManager->flush();
        $this->entityManager->getConnection()->commit();
        $this->container->get("event_dispatcher")->dispatch(
            UserCreateEvent::USER_CREATE
            , new UserCreateEvent(
                $this->container
                , $authEntity
            )
        );
    }
}