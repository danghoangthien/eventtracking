<?php
namespace Hyper\EventBundle\Service\PushEmailToMCService\Command;

final class PushEmailToMCCommand
{
    private $filterId;
    private $subscriberListId;

    public function __construct(
        $filterId
        , $subscriberListId
    ) {
        $this->filterId = $filterId;
        $this->subscriberListId = $subscriberListId;
    }

    public function filterId()
    {
        return $this->filterId;
    }

    public function subscriberListId()
    {
        return $this->subscriberListId;
    }
}