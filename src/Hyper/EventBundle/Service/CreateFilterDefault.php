<?php
namespace Hyper\EventBundle\Service;

use \Symfony\Component\DependencyInjection\ContainerInterface
    , Hyper\Domain\Device\Device
    , Hyper\Domain\Filter\Filter
    , Hyper\Domain\Authentication\Authentication
    , Hyper\EventBundle\Service\FilterService\Condition\DataType\HistoryDataType
    , Hyper\EventBundle\Service\FilterService\Condition\DataType\UsageDataType;

class CreateFilterDefault
{
    protected $container;
    protected $clientRepo;
    protected $clientAppTitleRepo;
    protected $appPlatformRepo;
    protected $actionRepo;

    protected $listAppTitle = [];
    protected $listAppId = [];
    protected $listEventName = [];
    protected $listAppPlatform = [];

    public function __construct(
        ContainerInterface $container
        , Authentication $auth
    ) {
        $this->container = $container;
        $this->auth = $auth;
        $this->init();
    }

    public function init()
    {
        $this->initRepo();
        $this->initAppTitle();
        $this->initEventRandomly();
    }

    protected function initRepo()
    {
        $this->clientRepo = $this->container->get('client_repository');
        $this->clientAppTitleRepo = $this->container->get('client_app_title_repository');
        $this->appPlatformRepo = $this->container->get('application_platform_repository');
        $this->actionRepo = $this->container->get('action_repository');
        $this->em = $this->container->get('doctrine')->getManager('pgsql');
    }

    protected function initAppTitle()
    {
        $client = $this->clientRepo->find($this->auth->getClientId());
        $listClientAppTitle = $this->clientAppTitleRepo->findBy(['client' => $client]);
        $listAppTitle = [];
        if(empty($listClientAppTitle)){
            return;
        }
        foreach ($listClientAppTitle as $clientAppTitle) {
            $this->listAppTitle[] = $clientAppTitle->getAppTitle();
            $listAppTitleId[] = $clientAppTitle->getAppTitle()->getId();
        }
        if(empty($listAppTitleId)){
            return;
        }
        $listAppFlatform = $this->appPlatformRepo->findByAppTitle($listAppTitleId);
        if(empty($listAppFlatform)){
            return;
        }
        $listAppPlatform = [];
        foreach ($listAppFlatform as $appPlatform) {
            $listAppPlatform[] = ['app_id' => $appPlatform->getAppId(), 'app_title_id' => $appPlatform->getAppTitle()->getId()];
        }
        $this->listAppPlatform = $this->groupByAppTitleId($listAppPlatform);
        $this->listAppId = array_column($listAppPlatform,'app_id');
    }

    protected function initEventRandomly()
    {
        if(empty($this->listAppId)){
            return;
        }
        $eventNames = $this->actionRepo->getEventNameAppIdByAppIds($this->listAppId, ['install']);
        $this->listEventName = $this->groupByAppId($eventNames);
    }

    public function handle()
    {
        if(empty($this->listAppTitle)){
            $this->em->flush();
            return;
        }

        foreach ($this->listAppTitle as $appTitle) {
            $card1 = $this->initCard1($appTitle);
            $this->em->persist($card1);
            $listEventRandom = $this->getEventRandomly($appTitle->getId());
            foreach ($listEventRandom as $eventRandom) {
                $card2 = $this->initCard2($appTitle, $eventRandom);
                $this->em->persist($card2);
            }

        }
        $this->em->flush();
    }

    protected function initCard1($appTitle)
    {
        $filter = new Filter();
        $appTitleTitle = $appTitle->getTitle();
        $name = "Recent $appTitleTitle Installs";
        $desc = "All users who installs for the past 30 days";
        $color = "#093145";
        $txtColor = "#f5f5f5";
        $filterData = [
            'preset_name' => $name
            , 'description' => $desc
            , 'country_codes' => []
            , 'platform_ids' => [Device::IOS_PLATFORM_CODE, Device::ANDROID_PLATFORM_CODE]
            , 'filter_type' => 'user_behaviors'
            , 'audience' => [
                [
                    'history' => [
                        'in' => $appTitle->getId()
                        , 'type' => HistoryDataType::TYPE_INSTALL_TIME_LAST
                        , 'value' => [
                            0 => 30
                            , 1 => ''
                        ]
                    ]
                ]
            ]
            , 'card_bg_color_code' => $color
            , 'card_text_color_code' => $txtColor
        ];
        $filter->setPresetName($name)
            ->setDescription($desc)
            ->setAuthenticationId($this->auth->getId())
            ->setIsDefault(1)
            ->setFilterMetadata([])
            ->setFilterData($filterData)
            ->setCardBgColorCode($color)
            ->setCardTextColorCode($txtColor);

        return $filter;

    }

    protected function initCard2($appTitle, $iae)
    {
        $filter = new Filter();
        $appTitleTitle = $appTitle->getTitle();
        $name = "Recent $iae for $appTitleTitle";
        $desc = "All users who perform $iae for the past 30 days";
        $color = "#093145";
        $txtColor = "#f5f5f5";
        $filterData = [
            'preset_name' => $name
            , 'description' => $desc
            , 'country_codes' => []
            , 'platform_ids' => [Device::IOS_PLATFORM_CODE, Device::ANDROID_PLATFORM_CODE]
            , 'filter_type' => 'user_behaviors'
            , 'audience' => [
                [
                    'usage' => [
                        'in' => $appTitle->getId()
                        , 'perform' => UsageDataType::PERFORM_TYPE_PERFORM
                        , 'behaviour_id' => $iae
                        , 'frequent' => [
                            'type' => UsageDataType::FREQUENT_TYPE_EVENT_COUNT
                            , 'expression' => UsageDataType::FREQUENT_EXP_TYPE_MORE_THAN
                            , 'value' => [
                                0 => 0
                                , 1 => ''
                            ]
                        ]
                        , 'cat_id' => ''
                        , 'happened_at' => [
                            'type' => UsageDataType::HAPPENED_AT_TYPE_LAST
                            , 'value' => [
                                0 => 30
                                , 1 => ''
                            ]
                        ]

                    ]
                ]
            ]
            , 'card_bg_color_code' => $color
            , 'card_text_color_code' => $txtColor
        ];
        $filter->setPresetName($name)
            ->setDescription($desc)
            ->setAuthenticationId($this->auth->getId())
            ->setIsDefault(1)
            ->setFilterMetadata([])
            ->setFilterData($filterData)
            ->setCardBgColorCode($color)
            ->setCardTextColorCode($txtColor);

        return $filter;

    }
    private function getEventRandomly($appTitleId){
        if(!isset($this->listAppPlatform[$appTitleId])){
            return [];
        }
        $eventName = [];
        foreach ($this->listAppPlatform[$appTitleId] as $appId) {
            if(!isset($this->listEventName[$appId])){
                continue;
            }
            $eventName = array_merge($eventName,$this->listEventName[$appId]);

        }
        $eventName = array_unique($eventName);
        if(count($eventName) <= 2){
            return $eventName;
        }
        shuffle($eventName);
        $random = array_slice($eventName,0,2);
        return $random;
    }
    private function groupByAppId($array) {
        $return = [];
        foreach($array as $val) {
            $return[$val['appId']][] = $val['eventName'];
        }
        return $return;
    }
    private function groupByAppTitleId($array){
        $return = [];
        foreach($array as $val) {
            $return[$val['app_title_id']][] = $val['app_id'];
        }
        return $return;
    }
}