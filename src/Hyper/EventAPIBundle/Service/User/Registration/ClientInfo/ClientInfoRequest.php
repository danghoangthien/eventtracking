<?php
namespace Hyper\EventAPIBundle\Service\User\Registration\ClientInfo;

class ClientInfoRequest
{
    private $listAppId;

    public function __construct($listAppId)
    {
        if (!is_array($listAppId)) {
            throw new \Exception('app_ids param must be array.');
        }
        $this->listAppId = $listAppId;
    }

    public function listAppId()
    {
        return $this->listAppId;
    }
}