<?php
namespace Hyper\EventAPIBundle\Service\AnalyticMetadata\AppTitleStatisticService\ValueObject;

use Hyper\Domain\Client\Client;
use Hyper\Domain\Application\ApplicationTitle;

class DormantUserRequest
{
    protected $client;
    protected $appTitle;
    protected $listAppId;

    public function __construct(
        Client $client
        , ApplicationTitle $appTitle
        , $listAppId
    )
    {
        $this->setClient($client);
        $this->setAppTitle($appTitle);
        $this->listAppId($listAppId);
    }

    private function setClient(Client $client)
    {
        $this->client = $client;
    }

    private function setAppTitle(ApplicationTitle $appTitle)
    {
        $this->appTitle = $appTitle;
    }

    private function setListAppId(array $listAppId)
    {
        $this->listAppId = $listAppId;
    }

    public function client()
    {
        return $this->client;
    }

    public function appTitle()
    {
        return $this->appTitle;
    }

    public function listAppId()
    {
        return $this->listAppId;
    }
}