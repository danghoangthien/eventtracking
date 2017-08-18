<?php
namespace Hyper\EventAPIBundle\Service\AnalyticMetadata\RecentInAppEventService\ValueObject;

class RecentInAppEventRequest
{
    protected $clientId;

    public function __construct($clientId)
    {
        $this->setClientId($clientId);
    }

    private function setClientId($clientId)
    {
        if (empty($clientId)) {
            throw new \Exception('client_id must be value.');
        }
        $this->clientId = $clientId;
    }

    public function clientId()
    {
        return $this->clientId;
    }
}