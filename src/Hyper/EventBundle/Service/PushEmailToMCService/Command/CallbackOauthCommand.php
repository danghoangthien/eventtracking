<?php
namespace Hyper\EventBundle\Service\PushEmailToMCService\Command;

final class CallbackOauthCommand
{
    private $code;

    public function __construct($code)
    {
        $this->code = $code;
    }

    public function code()
    {
        return $this->code;
    }
}