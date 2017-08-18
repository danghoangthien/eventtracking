<?php
namespace Hyper\EventAPIBundle\Service\AnalyticMetadata\AppTitleStatisticService;

use Hyper\EventAPIBundle\Service\AnalyticMetadata\AppTitleStatisticService\ValueObject\GhostUserRequest
    , Hyper\Domain\Filter\Filter
    , Hyper\Domain\Device\Device
    , Hyper\EventBundle\Service\FilterService\Condition\DataType\UsageDataType
    , Hyper\EventBundle\Service\FilterService\FilterService;

class GhostUserCountService
{
    private $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function execute(GhostUserRequest $ghostUserRequest)
    {
        try {
            $ghostUserFilter = $this->initGhostUserFilter($ghostUserRequest);
            $filterService = new FilterService(
                $this->container
            );
            $filterService = $filterService->executeOnlyProfileCount($ghostUserFilter);

            return $filterService->getProfileCount();

        } catch(\Exception $e) {

        }

        return 0;

    }

    private function initGhostUserFilter(GhostUserRequest $ghostUserRequest)
    {
        $appTitle = $ghostUserRequest->appTitle();
        $ghostUserFilter = new Filter();
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
                            'type' => UsageDataType::HAPPENED_AT_TYPE_LIFETIME
                            , 'value' => [
                                0 => '',
                                1 => ''
                            ]
                        ]

                    ]
                ]
            ]
            , 'card_bg_color_code' => $color
            , 'card_text_color_code' => $txtColor
        ];
        $ghostUserFilter->setPresetName($name)
            ->setDescription($desc)
            ->setAuthenticationId(0)
            ->setIsDefault(1)
            ->setFilterMetadata([])
            ->setFilterData($filterData)
            ->setCardBgColorCode($color)
            ->setCardTextColorCode($txtColor);

        return $ghostUserFilter;
    }

}