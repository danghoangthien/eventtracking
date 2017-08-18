<?php

namespace Hyper\EventBundle\Controller\Dashboard\AudienceSpotlight;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\JsonResponse,
    Symfony\Component\HttpFoundation\Request,
    Hyper\Domain\Device\Device,
    Hyper\EventBundle\Service\UserJourneyService;

class UserJourneyController extends Controller
{
    public function indexAction(Request $request)
    {
        $auth = $this->get('security.context')->getToken()->getUser();
        if ($auth->isDemoAccount()) {
            $deviceLatest = 'samuel@hypergrowth.co';
        } else {
            $uj = new UserJourneyService($this->container);
            $deviceLatest = $uj->initRepo()
                ->initListAppId()
                ->initData()
                ->initDeviceLatest()
                ->getDeviceLatest();
        }


        return $this->render('::audience_spotlight/user_journey/index.html.twig', array(
            'deviceLatest' => $deviceLatest
        ));
    }

    public function loadProfileAction(Request $request)
    {
        $json = array(
            'status' => 0
        );
        try {
            $deviceId = $request->query->get('user_journey_id');
            if (empty($deviceId)) {
                throw new \Exception('User ID must be a value.');
            }

            if ($deviceId == 'samuel@hypergrowth.co') {
                return new JsonResponse([
                    'status' => 1,
                    'content' => $this->renderView('::audience_spotlight/user_journey/_profile.html.twig', [
                        'device' => [
                            'country_code' => 'ID'
                            , 'platform' => 2
                            , 'model' => 'Samsung Galaxy Note 3'
                            , 'email' => 'samuel@hypergrowth.co'
                        ]
                        , 'listPlatform' => [
                            Device::IOS_PLATFORM_CODE => Device::IOS_PLATFORM_NAME,
                            Device::ANDROID_PLATFORM_CODE => Device::ANDROID_PLATFORM_NAME
                        ],
                    ])
                ]);
            }
            $uj = new UserJourneyService($this->container, $deviceId);
            $uj = $uj->initRepo()
                    ->initListAppId()
                    ->initData()
                    ->initDevice();
            $json['content'] = $this->renderView('::audience_spotlight/user_journey/_profile.html.twig', array(
                'device' => $uj->getDevice()
                , 'listPlatform' => [
                    Device::IOS_PLATFORM_CODE => Device::IOS_PLATFORM_NAME,
                    Device::ANDROID_PLATFORM_CODE => Device::ANDROID_PLATFORM_NAME
                ]
            ));
            $json['status'] = 1;
        } catch(\Exception $e) {
            $json['msg'] = $e->getMessage();
        }

        return new JsonResponse($json);
    }

    public function loadTimelineAction(Request $request)
    {
        $json = array(
            'status' => 0
        );
        try {
            $deviceId = $request->query->get('user_journey_id');
            if (empty($deviceId)) {
                throw new \Exception('User ID must be a value.');
            }
            $pageNumber = $request->query->get('page', 1);
            if ($pageNumber > 1) {
                $view = '_paginate';
            } else {
                $view = '_timeline';
            }

            if ($deviceId == 'samuel@hypergrowth.co') {
                $dummyData = \Hyper\EventBundle\Service\UserJourneyService::dummyData();
                $listTimeline = array_chunk($dummyData, \Hyper\EventBundle\Service\UserJourneyService::LIST_ACTION_SIZE);
                return new JsonResponse([
                    'status' => 1,
                    'isLastPage' => ($pageNumber == count($listTimeline)),
                    'content' => $this->renderView("::audience_spotlight/user_journey/{$view}_dummy_data.html.twig", [
                        'listTimeline' => $listTimeline[$pageNumber - 1],
                        'pageCurrent' => $pageNumber,
                        'isLastPage' => ($pageNumber == count($listTimeline)),
                        'totalPage' => count($listTimeline)
                    ])
                ]);
            }

            $uj = new UserJourneyService($this->container, $deviceId);
            $uj = $uj->initRepo()
                ->initCached()
                ->initListAppId()
                ->initData()
                ->initIAPConfig();

            $paginator = $uj->getTimeline($pageNumber);
            $json['content'] = $this->renderView("::audience_spotlight/user_journey/{$view}.html.twig", array(
                'listTimeline' => $paginator,
                'pageCurrent' => $pageNumber,
                'isLastPage' => ($pageNumber == $paginator->getPageCount())
            ));
            $json['isLastPage'] = ($pageNumber == $paginator->getPageCount());
            $json['status'] = 1;
        } catch(\Exception $e) {
            $json['msg'] = $e->getMessage();
        }

        return new JsonResponse($json);
    }

    public function loadLastTransactionAction(Request $request)
    {
        $json = array(
            'status' => 0
        );
        try {
            $deviceId = $request->query->get('user_journey_id');
            if (empty($deviceId)) {
                throw new \Exception('User ID must be a value.');
            }

            if ($deviceId == 'samuel@hypergrowth.co') {
                return new JsonResponse([
                    'status' => 1,
                    'result' => [
                        'happenedAt' => '2016-04-16 14:00:48',
                        'amountUSD' => '1000'
                    ]
                ]);
            }
            $uj = new UserJourneyService($this->container, $deviceId);
            $uj = $uj->initRepo()
                    ->initListAppId()
                    ->initData();
            $lastTransaction = $uj->getLastTransaction();
            $json['result'] = '';
            if (!empty($lastTransaction)) {
                $happenedAt = $lastTransaction['happened_at'];
                $dt = new \DateTime();
                $dt->setTimestamp($happenedAt);
                $json['result'] = [
                    'happenedAt' => $dt->format('Y-m-d')
                    , 'amountUSD' => $lastTransaction['amount_usd']
                ];
            }
            $json['status'] = 1;
        } catch(\Exception $e) {
            $json['msg'] = $e->getMessage();
        }

        return new JsonResponse($json);
    }

    public function loadLastActivityAction(Request $request)
    {
        $json = array(
            'status' => 0,
            'lastActivity' => ''
        );
        try {
            $deviceId = $request->query->get('user_journey_id');
            if (empty($deviceId)) {
                throw new \Exception('User ID must be a value.');
            }

            if ($deviceId == 'samuel@hypergrowth.co') {
                return new JsonResponse([
                    'status' => 1,
                    'lastActivity' => '2016-04-16 14:00:48'
                ]);
            }

            $uj = new UserJourneyService($this->container, $deviceId);
            $uj = $uj->initRepo()
                    ->initListAppId()
                    ->initData();

            $lastActivity = $uj->getLastActivity();
            if ($lastActivity) {
                $dt = new \DateTime();
                $dt->setTimestamp($lastActivity);
                $json['lastActivity'] = $dt->format('Y-m-d H:i:s');
            }
            $json['status'] = 1;
        } catch(\Exception $e) {
            $json['msg'] = $e->getMessage();
        }

        return new JsonResponse($json);
    }

    public function loadTotalMoneySpentAction(Request $request)
    {
        $json = array(
            'status' => 0
        );
        try {
            $deviceId = $request->query->get('user_journey_id');
            if (empty($deviceId)) {
                throw new \Exception('User ID must be a value.');
            }

            if ($deviceId == 'samuel@hypergrowth.co') {
                return new JsonResponse([
                    'status' => 1,
                    'result' => [
                        'total_amount' => '7624'
                        , 'total_transaction' => 6
                    ]
                ]);
            }

            $uj = new UserJourneyService($this->container, $deviceId);
            $uj = $uj->initRepo()
                    ->initListAppId()
                    ->initData();

            $result = $uj->getTotalMoneySpent();
            $json['result'] = '';
            if (!empty($result)) {
                $json['result'] = [
                    'total_transaction' => $result['numberOfTransaction']
                    , 'total_amount' => $result['totalPriceOfTransaction']
                ];
            }
            $json['status'] = 1;
        } catch(\Exception $e) {
            $json['msg'] = $e->getMessage();
        }

        return new JsonResponse($json);
    }

    public function validateAction(Request $request)
    {
        $json = [];
        $value = $request->query->get('value', '');
        $type = $request->query->get('type', '');

        if ($value == 'samuel@hypergrowth.co') {
            return new JsonResponse(['device_id' => 'samuel@hypergrowth.co']);
        }
        if ($value && $type) {
            $uj = new UserJourneyService($this->container);
            $uj = $uj->initRepo();
            $msg  = '';
            try {
                $json['device_id'] = $uj->validate($value, $type);
            } catch (\Exception $e) {
                echo $e->getMessage();
                exit;
                $json['msg'] = 'User ID not found.';
            }
        }

        return new JsonResponse($json);
    }

    public function loadPresetFilterAction(Request $request)
    {

        $json = array(
            'status' => 0
        );
        try {
            $deviceId = $request->query->get('user_journey_id');
            if (empty($deviceId)) {
                throw new \Exception('User ID must be a value.');
            }
            if ($deviceId == 'samuel@hypergrowth.co') {
                return new JsonResponse([
                    'status' => 1,
                    'result' => $this->renderView("::audience_spotlight/user_journey/_preset_filter_dummy_data.html.twig", [])
                ]);
            }
            $auth = $this->container->get('security.context')->getToken()->getUser();
            $uj = new UserJourneyService($this->container, $deviceId);
            $listPresetFilter = $uj->initRepo()
                    ->getListPresetFilter($auth->getId());
            $json['result'] = $this->renderView("::audience_spotlight/user_journey/_preset_filter.html.twig", [
                'listPresetFilter' => $listPresetFilter
            ]);
            $json['status'] = 1;
        } catch(\Exception $e) {
            $json['msg'] = $e->getMessage();
        }

        return new JsonResponse($json);
    }

    /**
     * Load a list of preset filter belong to current dashboard user but not being assigned to current device
     */
    public function loadUnAttachedPresetFilterAction(Request $request) {
        $json = array(
            'status' => 0
        );
        try {
            $deviceId = $request->query->get('user_journey_id');
            if (empty($deviceId)) {
                throw new \Exception('User ID must be a value.');
            }
            $auth = $this->container->get('security.context')->getToken()->getUser();
            $uj = new UserJourneyService($this->container, $deviceId);
            $listUnattachedPresetFilter = $uj->initRepo()
                    ->getUnAttachedPresetFilter($auth->getId(),$deviceId);
            //echo "<pre>";
            //var_dump($listUnattachedPresetFilter);die;
            $json['result'] = $this->renderView("::audience_spotlight/user_journey/_unattached_preset_filter.html.twig", [
                'listUnattachedPresetFilter' => $listUnattachedPresetFilter
            ]);
            $json['status'] = 1;
        } catch(\Exception $e) {
            $json['msg'] = $e->getMessage();
        }

        return new JsonResponse($json);
    }



    public function deletePresetFilterAction(Request $request)
    {
        $json = array(
            'status' => 0
        );
        try {
            $deviceId = $request->query->get('user_journey_id');
            if (empty($deviceId)) {
                throw new \Exception('User ID must be a value.');
            }
            $presetFilterId = $request->query->get('preset_filter_id');
            if (empty($presetFilterId)) {
                throw new \Exception('Preset filter id must be a value.');
            }
            $deviceRepo = $this->get('device_repository');
            $device = $deviceRepo->find($deviceId);
            if (!$device instanceof Device) {
                throw new \Exception('User ID id not found.');
            }
            $uj = new UserJourneyService($this->container, $device);
            $deleted = $uj->deletePresetFilter($presetFilterId);
            $json['result'] = $deleted;
            $json['status'] = 1;
        } catch(\Exception $e) {
            $json['msg'] = $e->getMessage();
        }

        return new JsonResponse($json);
    }

    public function detachPresetFilterAction(Request $request)
    {
        $json = array(
            'status' => 0
        );
        try {
            $deviceId = $request->request->get('user_journey_id');
            if (empty($deviceId)) {
                throw new \Exception('User ID must be a value.');
            }
            $presetFilterId = $request->request->get('preset_filter_id');
            if (empty($presetFilterId)) {
                throw new \Exception('Preset filter id must be a value.');
            }
            $deviceRepo = $this->get('device_repository');
            $device = $deviceRepo->find($deviceId);
            if (!$device instanceof Device) {
                throw new \Exception('User ID id not found.');
            }
            $uj = new UserJourneyService($this->container, $deviceId);
            $uj = $uj->initRepo();
            $detached = $uj->detachPresetFilter($presetFilterId);
            $json['result'] = $detached;
            $json['status'] = 1;
        } catch(\Exception $e) {
            $json['msg'] = $e->getMessage();
        }

        return new JsonResponse($json);
    }


    public function attachPresetFilterAction(Request $request)
    {
        $json = array(
            'status' => 0
        );
        try {
            $deviceId = $request->request->get('user_journey_id');
            if (empty($deviceId)) {
                throw new \Exception('User ID must be a value..');
            }
            $presetFilterIds = $request->request->get('preset_filter_ids');
            if (empty($presetFilterIds)) {
                throw new \Exception('Preset filter id must be a value.');
            }
            $deviceRepo = $this->get('device_repository');
            $device = $deviceRepo->find($deviceId);
            if (!$device instanceof Device) {
                throw new \Exception('User ID id not found.');
            }
            //print_r($deviceId);die;
            $uj = new UserJourneyService($this->container, $deviceId);
            $uj = $uj->initRepo();
            foreach($presetFilterIds as $presetFilterId){
                $devicePresetFilter = $this->container->get('device_preset_filter_repository')->findOneBy(
                    array(
                        'deviceId' =>$deviceId,
                        'presetFilterId' => $presetFilterId
                    )
                );
                if ($devicePresetFilter) {
                    $activated = $uj->attachPresetFilter($presetFilterId);
                } else {
                    $activated = $uj->addAttachPresetFilter($presetFilterId);
                }
            }
            // todo implement throw exception in foreach and rollback in cactch block
            $activated = true;
            $json['result'] = $activated;
            $json['status'] = 1;
        } catch(\Exception $e) {
            $json['msg'] = $e->getMessage();
             //echo "The exception was created on line: " . $e->getLine();
             //echo $e->getFile();

        }

        return new JsonResponse($json);
    }
}