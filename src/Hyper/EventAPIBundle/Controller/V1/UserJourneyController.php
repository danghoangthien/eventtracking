<?php

namespace Hyper\EventAPIBundle\Controller\V1;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Doctrine\Common\Util\Inflector;
use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\JsonResponse,
    Symfony\Component\HttpFoundation\Response,
    Hyper\EventAPIBundle\Controller\APIBaseController;

class UserJourneyController extends APIBaseController
{
    private $eventValueParams = ['af_revenue','af_price','af_level','af_success','af_content_type',
        'af_content_list','af_content_id','af_currency','af_registration_method','af_quantity',
        'af_payment_info_available','af_rating_value','af_max_rating_value','af_search_string',
        'af_description','af_score','af_destination_a','af_destination_b','af_class','af_date_a',
        'af_date_b','af_event_start','af_event_end','af_lat','af_long','af_customer_user_id',
        'af_validated','af_receipt_id','af_param1','af_param2','af_param3','af_param4','af_param5',
        'af_param6','af_param7','af_param8','af_param9','af_param10'];

    public function indexAction(Request $request)
    {
        $dateRange = $request->get('date_range');
        $identity = $request->get('hypid');
        if (empty($identity)) {
            $identity = $request->get('identity');
        }
        if (!empty($identity) && $identity == 'samuel@hypergrowth.co') {
            $routeOptions['check_access_app'] = false;
            $listData = \Hyper\EventBundle\Service\UserJourneyService::dummyData();
            $dataOutput = [
                'status'=>true,
                'client_access'=>true,
                'data'=> $listData
            ];
            return new JsonResponse($dataOutput, Response::HTTP_OK);
        }
        if (
            empty($dateRange) && empty($deviceId)

        ) {
            $dataOutput = [
                'status'=>true,
                'client_access'=>true,
                'message'=>'Miss arguments.'
           ];
            return new JsonResponse($dataOutput, Response::HTTP_BAD_REQUEST);
        }
        $dateRanges = explode(' - ', $dateRange);
        $timeRanges = [strtotime($dateRanges[0].' 00:00:00'), strtotime($dateRanges[1].' 23:59:59')];
        $deviceId = '';
        if ($this->isEmail($identity)) {
            $resultSet = $this->container->get('es_device_repository')->findOneByEmail($identity);
            if (!empty($resultSet)) {
                $deviceId = $resultSet['id'];
            }
            if (empty($deviceId)) {
                $identityCapture = $this->container->get('identity_capture_repository')->findOneBy([
	        	    'email' => $identity
	            ]);
	            if (!empty($identityCapture)) {
	                $deviceId = $identityCapture->getDeviceId();
	            }
            }
        } elseif ($this->isIDFA($identity)) {
            $resultSet = $this->container->get('es_ios_device_repository')->findOneByIDFA($identity);
            if (!empty($resultSet)) {
                $deviceId = $resultSet['id'];
            }
            if (empty($deviceId)) {
                $iosDevice = $this->container->get('ios_device_repository')->findOneBy(['idfa' => $identity]);
                if (!empty($iosDevice)) {
                    $deviceId = $iosDevice->getDevice()->getId();
                }
            }
        } elseif ($this->isAndroidId($identity)) {
            $resultSet = $this->container->get('es_android_device_repository')->findOneByAndroidId($identity);
            if (!empty($resultSet)) {
                $deviceId = $resultSet['id'];
            }
            if (empty($deviceId)) {
                $androidDevice = $this->container->get('android_device_repository')->findOneBy(['androidId' => $identity]);
                if (!empty($androidDevice)) {
                    $deviceId = $androidDevice->getDevice()->getId();
                }
            }
        } else {
            $deviceId = $identity;
        }
        $esActionRepo = $this->get('es_action_repository');
        $actions = $esActionRepo->getActionByDeviceDateRangeFromEs(
            $deviceId,
            $timeRanges[0],
            $timeRanges[1],
            $this->appId
        );
        if (!empty($actions)) {
            $rsOutput = $this->formatOutput($actions);
            return new JsonResponse($rsOutput, Response::HTTP_OK);
        }
        $actionRepo = $this->get('action_repository');
        $listAction = $actionRepo->getActionByDeviceDateRange(
            $deviceId,
            $timeRanges[0],
            $timeRanges[1],
            $this->appId
        );
        $listData = array();
        if (!empty($listAction)) {
            foreach ($listAction as $action) {
                $data = [
                    'timestamp' => $action['happenedAt'],
                    'event_name' => $action['eventName'],
                    'event_value' => ''
                ];
                if (
                    !empty($action['eventValueText']) &&
                    $action['eventValueText'] != 'NULL'
                ) {
                    $data['event_value'] = $action['eventValueText'];
                } else{
                    foreach ($action as $key => $value) {
                        $columName = Inflector::tableize($key);
                        if ((in_array($columName, $this->eventValueParams)) && ('' != $value)) {
                            $data['event_value'][$columName] = $value;
                        }
                    }
                }
                $listData[] = $data;
            }
        }
        $dataOutput = [
            'status'=>true,
            'client_access'=>true,
            'data'=> $listData
        ];

        return new JsonResponse($dataOutput, Response::HTTP_OK);
    }

    /**
     * Format output before response to api.
     * @author Carl Pham <vanca.vnn@gmail.com>
     */
    public function formatOutput($actions)
    {
        $listData = [];
        foreach ($actions as $action) {
            $data = [
                'timestamp' => $action['happened_at'],
                'event_name' => $action['event_name'],
                'event_value' => ''
            ];
            if (!empty($action['event_value_text']) && $action['event_value_text'] != 'NULL') {
                $data['event_value'] = $action['event_value_text'];
            } else {
                foreach ($action as $key => $value) {
                    if ((in_array($key, $this->eventValueParams)) && ('' != $value)) {
                        $data['event_value'][$key] = $value;
                    }
                }
            }
            $listData[] = $data;
        }

        return [ 'status'=>true, 'client_access'=>true, 'data'=> $listData];
    }

    private function isEmail($identity)
    {
        $ret = false;
        if (filter_var($identity, FILTER_VALIDATE_EMAIL)) {
            $ret = true;
        }

        return $ret;
    }

    private function isIDFA($identity)
    {
        $ret = false;
        if (preg_match("/^[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}$/", $identity, $match)) {
            $ret = true;
        }

        return $ret;
    }

    private function isAndroidId($identity)
    {
        $ret = false;
        if (strlen($identity) === 16) {
            $ret = true;
        }

        return $ret;
    }
}
