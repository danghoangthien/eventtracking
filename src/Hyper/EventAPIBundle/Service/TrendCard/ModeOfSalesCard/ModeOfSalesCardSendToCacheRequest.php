<?php
namespace Hyper\EventAPIBundle\Service\TrendCard\ModeOfSalesCard;

use Hyper\EventBundle\Service\Cached\InappeventConfig\InappeventConfigCached;
use Hyper\EventAPIBundle\Service\TrendCard\TrendCardSendToCacheRequest;
use Hyper\EventAPIBundle\Service\TrendCard\TrendCardSendToCacheRequestInterface;

class ModeOfSalesCardSendToCacheRequest
    extends TrendCardSendToCacheRequest
    implements TrendCardSendToCacheRequestInterface
{
    protected $appId;
    protected $inAppPurchase;

    public function __construct(
        InappeventConfigCached $inappeventConfigCached
        , $appId
        , $queueId = ''
        , $timestamp = ''
    )
    {
        $this->setAppId($appId);
        $this->setInAppPurchase($inappeventConfigCached);
        parent::__construct($queueId, $timestamp);
    }

    protected function setInAppPurchase($inappeventConfigCached)
    {
        $inAppPurchase = $this->getInAppPurchase($inappeventConfigCached, $this->appId);
         if (!$inAppPurchase) {
            throw new \InvalidArgumentException('There is no in-app-event configured as in-app-purchase in this app.');
        }
        $this->inAppPurchase = $inAppPurchase;
    }

    protected function setAppId($appId)
    {
        if (empty($appId)) {
            throw new \InvalidArgumentException('app_id must not empty.');
        }
        $this->appId = $appId;
    }

    public function appId()
    {
        return $this->appId;
    }

    public function inAppPurchase()
    {
        return $this->inAppPurchase;
    }

    protected function getEventsByAppId(
        InappeventConfigCached $inappeventConfigCached
        , $appId
    )
    {
        $dataCached = $inappeventConfigCached->hget($appId);
        if ($dataCached) {
            $dataCached = json_decode($dataCached, true);
        } else {
            $dataCached = [];
        }
        return $dataCached;
    }


    protected function getInAppPurchase(
        InappeventConfigCached $inappeventConfigCached
        , $appId
    )
    {
        $inAppPurchase = '';
        $events = $this->getEventsByAppId($inappeventConfigCached, $appId);
        if (!empty($events)) {
            foreach ($events as $event) {
                if (
                    !empty($event['tag_as_iap'])
                    && $event['tag_as_iap'] == 1
                ) {
                    $inAppPurchase = $event['event_name'];
                    break;
                }
            }
        }

        return $inAppPurchase;
    }

    public function serialize()
    {
        return serialize([
            $this->queueId
            , $this->appId
            , $this->inAppPurchase
            , $this->timestamp
        ]);
    }

    public function unserialize($serialized)
    {
        list (
            $this->queueId
            , $this->appId
            , $this->inAppPurchase
            , $this->timestamp
        ) = unserialize($serialized);
    }
}