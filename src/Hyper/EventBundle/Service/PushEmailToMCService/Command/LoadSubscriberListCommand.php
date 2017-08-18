<?php
namespace Hyper\EventBundle\Service\PushEmailToMCService\Command;

final class LoadSubscriberListCommand
{
    private $mcMetadata;

    public function __construct($mcMetadata)
    {
        $this->mcMetadata = $mcMetadata;
    }

    public function mcMetadata()
    {
        return $this->mcMetadata;
    }
}