<?php
namespace Hyper\EventBundle\Service\PushEmailToMCService\Response;

final class LoadSubscriberListResponse
{
    private $subscriberList;

    public function __construct($subscriberList)
    {
        $this->subscriberList = $subscriberList;
    }

    public function content()
    {
        return [
            'error' => 0
            , 'result' => $this->subscriberList
        ];
    }
}