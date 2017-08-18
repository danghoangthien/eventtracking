<?php
namespace Hyper\EventBundle\Service\UserjourneyService\Validator;

use Hyper\EventBundle\Service\UserjourneyService\Validator\ValidationHandler;

class IDFAValidationHandler implements ValidationHandler
{

    public function handleIDFANotFound()
    {
        throw new \Exception('IDFA not found.');
    }

    public function handleIosDeviceIndexNotFound()
    {
        throw new \Exception('ios device index not found.');
    }

}