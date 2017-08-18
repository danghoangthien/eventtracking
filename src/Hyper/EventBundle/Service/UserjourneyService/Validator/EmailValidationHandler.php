<?php
namespace Hyper\EventBundle\Service\UserjourneyService\Validator;

use Hyper\EventBundle\Service\UserjourneyService\Validator\ValidationHandler;

class EmailValidationHandler implements ValidationHandler
{

    public function handleEmailNotFound()
    {
        throw new \Exception('email not found.');
    }

    public function handleDeviceIndexNotFound()
    {
        throw new \Exception('device index not found.');
    }

}