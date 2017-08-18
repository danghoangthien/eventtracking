<?php
namespace Hyper\EventAPIBundle\Service\AnalyticMetadata\RecentInAppEventService;

use Hyper\Domain\Client\Client
    , Hyper\EventAPIBundle\Service\AnalyticMetadata\RecentInAppEventService\ValueObject\RecentInAppEventRequest
    , Hyper\EventAPIBundle\Service\AnalyticMetadata\RecentInAppEventService\ValueObject\RecentInAppEventValue
    , Hyper\Domain\InappeventConfig\InappeventConfig
    , Hyper\Domain\Client\ClientRepository
    , Hyper\Domain\Client\ClientAppTitleRepository
    , Hyper\EventBundle\Service\Cached\CachedInterface
    , Hyper\Domain\Application\ApplicationPlatformRepository
    , Hyper\Domain\Application\ApplicationTitleRepository;

class RecentInAppEventService
{
    const RECENT_LIMIT = 5;
    // request
    protected $recentInAppEventRequest;

    // repo
    protected $appTitleRepo;
    protected $clientRepo;
    protected $clientAppTitleRepo;
    protected $appPlatformRepo;

    // cached
    protected $recentInAppEventCached;
    protected $inAppEventConfigCached;

    protected $client;
    protected $listAppId;

    public function __construct(
        RecentInAppEventRequest $recentInAppEventRequest
        , ClientRepository $clientRepo
        , ApplicationTitleRepository $appTitleRepo
        , ClientAppTitleRepository $clientAppTitleRepo
        , ApplicationPlatformRepository $appPlatformRepo
        , CachedInterface $recentInAppEventCached
        , CachedInterface $inAppEventConfigCached
    )
    {
        $this->recentInAppEventRequest = $recentInAppEventRequest;
        $this->clientRepo = $clientRepo;
        $this->appTitleRepo = $appTitleRepo;
        $this->clientAppTitleRepo = $clientAppTitleRepo;
        $this->appPlatformRepo = $appPlatformRepo;
        $this->recentInAppEventCached = $recentInAppEventCached;
        $this->inAppEventConfigCached = $inAppEventConfigCached;
        $this->client = $this->clientRepo
            ->find($this->recentInAppEventRequest->clientId());
        if (!$this->client instanceof Client) {
            throw new \Exception('Client not found.');
        }
        $this->initListAppId();
        if (empty($this->listAppId)) {
            throw new \Exception('App ID not found.');
        }
    }

    private function initListAppId()
    {
        $listClientAppTitle = $this->clientAppTitleRepo
            ->findBy(['client' => $this->client]);
        $listAppTitleId = [];
        if (!empty($listClientAppTitle)) {
            foreach ($listClientAppTitle as $clientAppTitle) {
            	try {
            		$listAppTitleId[] = $clientAppTitle->getAppTitle()->getId();
            	} catch(\Exception $e) {

            	}
            }
        }
        if (!empty($listAppTitleId)) {
            $listAppFlatform = $this->appPlatformRepo->findByAppTitle($listAppTitleId);
            if (!empty($listAppFlatform)) {
                foreach ($listAppFlatform as $appPlatform) {
                    $this->listAppId[] = $appPlatform->getAppId();
                }
            }
        }

        return $this;
    }

    public function execute()
    {
        return $this->getListRecentInAppEventFromListAppId();
    }

    private function getListRecentInAppEventFromListAppId()
    {
        $listRecentInAppEvent = [];
        foreach ($this->listAppId as $appId) {
            $_listRecentInAppEvent = $this->recentInAppEventCached->hget($appId);
            if (!empty($_listRecentInAppEvent)) {
                $_listRecentInAppEvent = json_decode($_listRecentInAppEvent, true);
            }
            if (!empty($_listRecentInAppEvent)) {
                foreach ($_listRecentInAppEvent as $_recentInAppEvent) {
                    $listRecentInAppEvent[] = new RecentInAppEventValue(
                        $_recentInAppEvent['id']
                        , $appId
                        , $_recentInAppEvent['event_name']
                        , $_recentInAppEvent['amount_usd']
                        , ''
                        , ''
                        , ''
                        , ''
                        , $_recentInAppEvent['happened_at']
                        , $_recentInAppEvent['app_name']
                        , $_recentInAppEvent['app_platform']
                    );
                }
            }

        }
        if (!empty($listRecentInAppEvent)) {
            $listRecentInAppEvent = $this->sortByHappendAt($listRecentInAppEvent);
            $listRecentInAppEvent = $this->limitByRecentInAppEvent($listRecentInAppEvent);
            $listRecentInAppEvent = $this->mapIAEConfig($listRecentInAppEvent);
        }
        return $listRecentInAppEvent;
    }

    private function sortByHappendAt($listRecentInAppEvent)
    {
        usort($listRecentInAppEvent, function ($a, $b) {
		    return $b->happenedAt()- $a->happenedAt();
		});

		return $listRecentInAppEvent;
    }

    private function limitByRecentInAppEvent
    (
        $listRecentInAppEvent
        , $limit = self::RECENT_LIMIT
    )
    {
        if (!is_numeric($limit)) {
            throw new Exception('Limit must be a numeric.');
        }
        $listRecentInAppEvent = array_slice($listRecentInAppEvent, 0, $limit);

        return $listRecentInAppEvent;
    }

    private function mapIAEConfig($listRecentInAppEvent)
    {
        $ret = [];
        $listEventIAEConfigByApp = [];
        foreach ($listRecentInAppEvent as $recentInAppEvent) {
            if (empty($listEventIAEConfigByApp[$recentInAppEvent->appId()])) {
                $_listEventIAEConfigByApp = $this->inAppEventConfigCached->hget($recentInAppEvent->appId());
                if (!empty($_listEventIAEConfigByApp)) {
                    $listEventIAEConfigByApp[$recentInAppEvent->appId()] = json_decode($_listEventIAEConfigByApp, true);
                }
            }
            $icon = '';
            $color = '';
            $eventFriendlyName = '';
            $tagAsIAP = '';
            if (
                !empty($listEventIAEConfigByApp[$recentInAppEvent->appId()])
                && !empty($listEventIAEConfigByApp[$recentInAppEvent->appId()][$recentInAppEvent->eventName()])
            ) {
                $eventIAEConfig = $listEventIAEConfigByApp[$recentInAppEvent->appId()][$recentInAppEvent->eventName()];
                $icon = $eventIAEConfig['icon'];
                $color = $eventIAEConfig['color'];
                $eventFriendlyName = $eventIAEConfig['event_friendly_name'];
                if ($eventIAEConfig['tag_as_iap'] == InappeventConfig::TAG_AS_IAP_VALUE
                ) {
                    $tagAsIAP = InappeventConfig::TAG_AS_IAP_VALUE;
                }
            }
            $ret[] = new RecentInAppEventValue(
                $recentInAppEvent->actionId()
                , $recentInAppEvent->appId()
                , $recentInAppEvent->eventName()
                , $recentInAppEvent->amountUsd()
                , $eventFriendlyName
                , $tagAsIAP
                , $icon
                , $color
                , $recentInAppEvent->happenedAt()
                , $recentInAppEvent->appName()
                , $recentInAppEvent->appPlatform()
            );
        }

        return $ret;

    }
}