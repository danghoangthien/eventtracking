<?php
namespace Hyper\EventBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Hyper\Adops\WebBundle\Domain\AdopsRegisterUser;
use Hyper\Adops\WebBundle\Domain\AdopsUser;
use Hyper\Domain\Authentication\Authentication;
use Hyper\Domain\Client\Client;
use Hyper\Domain\Client\ClientAppTitle;
use Hyper\Domain\Application\ApplicationTitle;
use Hyper\Domain\Application\ApplicationPlatform;
use Hyper\EventBundle\Service\Cached\App\AppCached;
use Hyper\EventAPIBundle\Service\User\Registration\ClientInfo\ClientInfoRequest;
use Hyper\EventAPIBundle\Service\User\Registration\ClientInfo\ClientInfoHandler;

class SyncUserCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName('sync_user')
            ->setDescription('Sync User.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        ////echo "Generate new hash for user ak\n";
        //$this->generateNewHashForUser();
        //echo "Sync user from tk to ak\n";
        //$this->syncUserTKtoAK();
        echo "Sync user from ak to tk\n";
        $this->syncUserAKtoTK();
    }

    protected function generateNewHashForUser()
    {
        $container = $this->getContainer();
        $auth = new Authentication();
        $factory = $container->get('security.encoder_factory');
        $encoder = $factory->getEncoder($auth);
        $em = $container->get('doctrine')->getManager('pgsql');
        $userRepo = $em->getRepository('Hyper\Domain\Authentication\Authentication');
        $listUserPass = [
            'admin' => '2x4!oc!0-xh0qr^{'
            , 'hyperdev' => '0938354758'
            , 'Sevva' => 'sevva@hype123'
            , 'Reservasi' => 'reservasi@hype123'
            , 'seekmi' => '3_xTj4'
            , 'DemoClients' => 'letmein'
            , 'goGame' => 'goGame'
            , 'tiket.com' => '123456'
            , 'angie' => '123456'
            , 'Lakupon' => 'lakupon@hype123'
            , 'goGame2' => '123456'
            , 'goGameTest' => '123456'
            , 'Touchten_1' => '123456'
        ];
        foreach ($listUserPass as $username => $password) {
            $password = $encoder->encodePassword($password, $auth->getSalt());
            $userEntity = $userRepo->findOneBy(['username' => $username]);
            if ($userEntity instanceof Authentication) {
                echo $userEntity->getUsername().":" . $password . "\n";
                $userEntity->setPassword($password);
                $em->persist($userEntity);
            }
        }
        $em->flush();
    }

    protected function syncUserTKtoAK()
    {
        $container = $this->getContainer();
        $tkEm = $container->get('doctrine')->getManager('adops_pgsql');
        $akEm = $container->get('doctrine')->getManager('pgsql');
        $tkUserRepo = $tkEm->getRepository('Hyper\Adops\WebBundle\Domain\AdopsUser');
        $tkAppRepo = $tkEm->getRepository('Hyper\Adops\WebBundle\Domain\AdopsApplication');
        $akUserRepo = $akEm->getRepository('Hyper\Domain\Authentication\Authentication');
        $listTKUser = $tkUserRepo->findAll();
        if ($listTKUser) {
            foreach ($listTKUser as $key => $tkUser) {
                $tkUsername = $tkUser->getUsername();
                $akUser = $akUserRepo->findOneBy(['username' => $tkUsername]);
                if (!$akUser instanceof Authentication) {
                    $userType = Authentication::USER_TYPE_CLIENT;
                    if ($tkUser->getType() == AdopsUser::ROLE_USER__ADMIN) {
                        $userType = Authentication::USER_TYPE_ADMIN;
                    }
                    $clientId = '';
                    $appId = $tkUser->getAppId();
                    if ($appId) {
                        $listAppIdId = json_decode($appId, true);
                        $listAppId = [];
                        if (!empty($listAppIdId)) {
                            $listAppEntity = $tkAppRepo->findBy(['id' => $listAppIdId]);
                            if (!empty($listAppEntity)) {
                                foreach ($listAppEntity as $appEntity) {
                                    $listAppId[] = $appEntity->getAppId();
                                }
                            }

                        }
                        $clientInfoHandler = new ClientInfoHandler(
                            $container->get('application_platform_repository')
                            , $container->get('application_title_repository')
                            , $container->get('client_app_title_repository')
                        );
                        $resp = $clientInfoHandler->handle(
                            new ClientInfoRequest($listAppId)
                        );
                        if (isset($resp['id'])) {
                            $clientId = $resp['id'];
                        }
                    }
                    $akUser = new Authentication();
                    $akUser->setId($tkUser->getId())
                        ->setUsername($tkUser->getUsername())
                        ->setName($tkUser->getFullname())
                        ->setPassword($tkUser->getPassword())
                        ->setImgPath($tkUser->getAvatar())
                        ->setEmail($tkUser->getEmail())
                        ->setClientId($clientId)
                        ->setStatus(1)
                        ->setCreated(time())
                        ->setUserType($userType);
                    \Doctrine\Common\Util\Debug::dump($akUser);
                    $akEm->persist($akUser);
                }
            }
            $akEm->flush();
        }
    }

    protected function syncUserAKtoTK()
    {
        $container = $this->getContainer();
        $tkEm = $container->get('doctrine')->getManager('adops_pgsql');
        $akEm = $container->get('doctrine')->getManager('pgsql');
        $tkUserRepo = $tkEm->getRepository('Hyper\Adops\WebBundle\Domain\AdopsUser');
        $tkAppRepo = $tkEm->getRepository('Hyper\Adops\WebBundle\Domain\AdopsApplication');
        $clientAppTitleRepo = $akEm->getRepository('Hyper\Domain\Client\ClientAppTitle');
        $appPlatformRepo = $akEm->getRepository('Hyper\Domain\Application\ApplicationPlatform');
        $akUserRepo = $akEm->getRepository('Hyper\Domain\Authentication\Authentication');
        $listAKUser = $akUserRepo->findAll();
        if ($listAKUser) {
            $listUserPass = [
                'admin' => '2x4!oc!0-xh0qr^{'
                , 'hyperdev' => '0938354758'
                , 'Sevva' => 'sevva@hype123'
                , 'Reservasi' => 'reservasi@hype123'
                , 'seekmi' => '3_xTj4'
                , 'DemoClients' => 'letmein'
                , 'goGame' => 'goGame'
                , 'tiket.com' => '123456'
                , 'angie' => '123456'
                , 'Lakupon' => 'lakupon@hype123'
                , 'goGame2' => '123456'
                , 'goGameTest' => '123456'
                , 'Touchten_1' => '123456'
            ];
            foreach ($listAKUser as $key => $akUser) {
                $akUsername = $akUser->getUsername();
                if ($akUsername == 'admin') {
                    continue;
                }
                if (!isset($listUserPass[$akUsername])) {
                    continue;
                }
                $tkUser = $tkUserRepo->findOneBy(['username' => $akUsername]);
                if ($tkUser instanceof AdopsUser) {
                    $userType = AdopsUser::ROLE_USER_TRANSPARENT;
                    if ($akUser->getUserType() == Authentication::USER_TYPE_ADMIN) {
                        $userType = AdopsUser::ROLE_USER__ADMIN;
                    }
                    $listAppId = [];
                    $clientId = $akUser->getClientId();
                    if ($clientId) {
                        $listClientAppTitleEntity = $clientAppTitleRepo->findBy(['client' => $clientId]);
                        if ($listClientAppTitleEntity) {
                            $clientAppTitleId = [];
                            foreach ($listClientAppTitleEntity as $clientAppTitle) {
                                $appTitle = $clientAppTitle->getAppTitle();
                                $clientAppTitleId[] = $appTitle->getId();
                            }
                            if (!empty($clientAppTitleId)) {
                                $listAppPlatformEntity = $appPlatformRepo->findBy(['appTitle' => $clientAppTitleId]);
                                if($listAppPlatformEntity) {
                                    foreach ($listAppPlatformEntity as $appPlatformEntity) {
                                        $listAppId[] = $appPlatformEntity->getAppId();
                                    }
                                }
                            }
                        }
                    }
                    $listAppIdId = '';
                    if ($listAppId) {
                        $listAppEntity = $tkAppRepo->findBy(['appId' => $listAppId]);
                        if ($listAppEntity) {
                            foreach ($listAppEntity as $appEntity) {
                                $listAppIdId[] = $appEntity->getId();
                            }
                        }
                    }
                    if ($listAppIdId) {
                        $listAppIdId = json_encode($listAppIdId);
                    }
                    $tkUser = new AdopsUser();
                    $tkUser->setId($akUser->getId())
                        ->setUsername($akUser->getUsername())
                        ->setFullname($akUser->getName())
                        ->setPassword($akUser->getPassword())
                        ->setAvatar($akUser->getImgPath())
                        ->setEmail($akUser->getEmail())
                        ->setAppId($listAppIdId)
                        ->setType($userType);
                    \Doctrine\Common\Util\Debug::dump($tkUser);
                    $tkEm->persist($tkUser);

                }
            }
            $tkEm->flush();
        }
    }

}