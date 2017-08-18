<?php
namespace Hyper\EventBundle\Service\EventAPI;

use Symfony\Component\DependencyInjection\ContainerInterface,
    Symfony\Component\HttpFoundation\Response;

class EventAPIResful extends EventAPIBase
{
    public function __construct(ContainerInterface $container) {
        parent::__construct($container);
    }

    public function analyticCountDeviceByPlatform($params = array())
    {
        $endpoint = "/api/v1/analytic/metadata/count_device_by_platform";
        $apiResult = $this->runApi($endpoint, 'get', $params);
        $result = [];
        if (
            array_key_exists('status_code', $apiResult) &&
            $apiResult['status_code'] == Response::HTTP_OK
        ) {
            $result = $apiResult['result'];
        }

        return $result;
    }

    public function analyticCountDeviceByAppTitle($params = array())
    {
        $endpoint = "/api/v1/analytic/metadata/count_device_by_app_title";
        $apiResult = $this->runApi($endpoint, 'get', $params);
        $result = [];
        if (
            array_key_exists('status_code', $apiResult) &&
            $apiResult['status_code'] == Response::HTTP_OK
        ) {
            $result = $apiResult['result'];
        }

        return $result;
    }

    public function analyticCountDeviceAndEventByAppTitle($params = array())
    {
        $endpoint = "/api/v1/analytic/metadata/count_device_and_event_by_app_title";
        $apiResult = $this->runApi($endpoint, 'get', $params);
        $result = [];
        if (
            array_key_exists('status_code', $apiResult) &&
            $apiResult['status_code'] == Response::HTTP_OK
        ) {
            $result = $apiResult['result'];
        }

        return $result;
    }

    public function analyticRecentInAppEvent($params = array())
    {
        $endpoint = "/api/v1/analytic/metadata/recent_in_app_event";
        $apiResult = $this->runApi($endpoint, 'get', $params);
        $result = [];
        if (
            array_key_exists('status_code', $apiResult) &&
            $apiResult['status_code'] == Response::HTTP_OK
        ) {
            $result = $apiResult['result'];
        }

        return $result;
    }

    public function analyticCountDeviceByCountry($params = array())
    {
        $endpoint = "/api/v1/analytic/metadata/count_device_by_country";
        $apiResult = $this->runApi($endpoint, 'get', $params);
        $result = [];
        if (
            array_key_exists('status_code', $apiResult) &&
            $apiResult['status_code'] == Response::HTTP_OK
        ) {
            $result = $apiResult['result'];
        }

        return $result;
    }
}
