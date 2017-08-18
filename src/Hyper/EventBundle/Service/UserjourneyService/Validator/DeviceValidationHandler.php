<?php
namespace Hyper\EventBundle\Service\UserjourneyService\Validator;

use Hyper\EventBundle\Service\UserjourneyService\Validator\ValidationHandler;

class DeviceValidationHandler implements ValidationHandler
{

    public function handleDeviceNotFound()
    {
        throw new \Exception('device not found.');
    }

    public function handleDeviceIndexNotFound()
    {
        throw new \Exception('device index not found.');
    }

}