<?php
namespace Hyper\EventAPIBundle\Command\AnalyticMetaData;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Hyper\Domain\Client\Client;
use Hyper\Domain\Application\ApplicationTitle;
use Hyper\EventBundle\Service\Cached\AnalyticMetadata\CountDeviceByAppTitleCached;
use Hyper\EventAPIBundle\Service\AnalyticMetadata\AppTitleStatisticService\ValueObject\GhostUserRequest;
use Hyper\EventAPIBundle\Service\AnalyticMetadata\AppTitleStatisticService\GhostUserCountService;
use Hyper\EventAPIBundle\Service\AnalyticMetadata\AppTitleStatisticService\ValueObject\DormantUserRequest;
use Hyper\EventAPIBundle\Service\AnalyticMetadata\AppTitleStatisticService\DormantUserCountService;

class CountDeviceByAppTitleCommand extends ContainerAwareCommand
{
    protected $clientRepo;
    protected $clientAppTitleRepo;
    protected $appPlatformRepo;
    protected $applicationRepo;
    protected $actionRepo;

    protected function configure()
    {
        $this
            ->setName('analytic_metadata:count_device_by_app_title')
            ->setDescription('Count device by App Title.');
    }

    protected function initProperties()
    {
        $em = $this->getContainer()->get('doctrine')->getManager('pgsql');
        $this->clientRepo = $em->getRepository('Hyper\Domain\Client\Client');
        $this->clientAppTitleRepo = $em->getRepository('Hyper\Domain\Client\ClientAppTitle');
        $this->appPlatformRepo = $em->getRepository('Hyper\Domain\Application\ApplicationPlatform');
        $this->applicationRepo = $this->getContainer()->get('application_repository');
        $this->actionRepo = $this->getContainer()->get('action_repository');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->initProperties();
            $countDeviceByAppTitleCached = new CountDeviceByAppTitleCached($this->getContainer());
            $listCountDeviceByPlatform = $this->applicationRepo->countDeviceByPlatform();
            $listClient = $this->clientRepo->findAll();
            foreach ($listClient as $client) {
                $listClientAppTitle = $this->clientAppTitleRepo->findByClient($client->getId());
                if (!empty($listClientAppTitle)) {
                    $listAppTitle = [];
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
                        $clientId = $client->getId();
                        $appTitleId = $appTitle->getId();
                        $listAppPlatform = $this->appPlatformRepo->findByAppTitle($appTitleId);
                        $listAppId = [];
                        if (!empty($listAppPlatform)) {
                            foreach ($listAppPlatform as $appPlatform) {
                                $listAppId[] = $appPlatform->getAppId();
                            }
                        }
                        if (!empty($listAppId)) {
                            $ghostUserCountService = new GhostUserCountService($this->getContainer());
                            $ghostUserCount = $ghostUserCountService->execute(
                                new GhostUserRequest(
                                    $client
                                    , $appTitle
                                    , $listAppId
                                )
                            );
                            $dormantUserCountService = new DormantUserCountService($this->getContainer());
                            $dormantUserCount = $dormantUserCountService->execute(
                                new GhostUserRequest(
                                    $client
                                    , $appTitle
                                    , $listAppId
                                )
                            );
                            foreach ($listCountDeviceByPlatform as $tmp) {
                                if (!in_array($tmp['app_id'], $listAppId)) {
                                    continue;
                                }
                                // count event by app_id
                                $eventCountByAppId = $this->actionRepo->countByAppId($tmp['app_id']);
                                $distinctEventList = [];
                                $listAppTitle[$appTitleId][] = [
                                    'client_name' => $client->getClientName(),
                                    'app_title' => $appTitle->getTitle(),
                                    'app_title_id' => $appTitle->getId(),
                                    'app_id' => $tmp['app_id'],
                                    'platform' => $tmp['platform'],
                                    'device_count' => $tmp['device_count'],
                                    'event_count' => $eventCountByAppId,
                                    'distinct_event_list' => $distinctEventList,
                                    'ghost_user_count' => $ghostUserCount,
                                    'dormant_user_count' => $dormantUserCount
                                ];
                            }
                        }
                    }
                    $countDeviceByAppTitleCached->hset($clientId, json_encode($listAppTitle));
                }
            }

        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

}