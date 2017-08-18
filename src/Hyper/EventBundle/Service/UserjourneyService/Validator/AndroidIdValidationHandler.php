<?php
namespace Hyper\EventBundle\Service\UserjourneyService\Validator;

use Hyper\EventBundle\Service\UserjourneyService\Validator\ValidationHandler;

class AndroidIdValidationHandler implements ValidationHandler
{

    public function handleAndroidIdNotFound()
    {
        throw new \Exception('Android ID not found.');
    }

    public function handleAndroidDeviceIndexNotFound()
    {
        throw new \Exception('Android device index not found.');
    }

}