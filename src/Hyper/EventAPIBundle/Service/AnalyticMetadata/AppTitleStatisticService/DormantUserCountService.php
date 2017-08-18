<?php
namespace Hyper\EventAPIBundle\Service\AnalyticMetadata\AppTitleStatisticService;

use Hyper\EventAPIBundle\Service\AnalyticMetadata\AppTitleStatisticService\ValueObject\GhostUserRequest
    , Hyper\Domain\Filter\Filter
    , Hyper\Domain\Device\Device
    , Hyper\EventBundle\Service\FilterService\Condition\DataType\UsageDataType
    , Hyper\EventBundle\Service\FilterService\FilterService;

class DormantUserCountService
{
    private $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function execute(GhostUserRequest $ghostUserRequest)
    {
        try {
            $dormantUserFilter = $this->initDormantUserFilter($ghostUserRequest);
            $filterService = new FilterService(
                $this->container
            );
            $filterService = $filterService->executeOnlyProfileCount($dormantUserFilter);

            return $filterService->getProfileCount();

        } catch(\Exception $e) {

        }

        return $this;

    }

    private function initDormantUserFilter(GhostUserRequest $ghostUserRequest)
    {
        $appTitle = $ghostUserRequest->appTitle();
        $filter = new Filter();
        $name = "Ghost User Count";
        $desc = "Count of user who install but never perform any in-app-event";
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
                        , 'perform' => UsageDataType::PERFORM_TYPE_NOT_PERFORM
                        , 'behaviour_id' => ''
                        , 'cat_id' => ''
                        , 'happened_at' => [
                            'type' => UsageDataType::HAPPENED_AT_TYPE_LAST
                            , 'value' => [
                                0 => 30,
                                1 => ''
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
            ->setAuthenticationId(0)
            ->setIsDefault(1)
            ->setFilterMetadata([])
            ->setFilterData($filterData)
            ->setCardBgColorCode($color)
            ->setCardTextColorCode($txtColor);

        return $filter;
    }

}