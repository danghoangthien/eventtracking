<?php
namespace Hyper\EventAPIBundle\Service\TrendCard;

use Hyper\EventBundle\Service\Cached\TrendCard\TrendCardCached;
use Hyper\EventAPIBundle\Service\TrendCard\TrendCardRequestInterface;
use Hyper\EventAPIBundle\Service\TrendCard\TrendCardRetrieveFromCacheRequest;

class TrendCardProcessor
{
    protected $trencardCached;
    protected $trencardFactory;
    protected $logger;

    public function __construct(
        TrendCardCached $trencardCached
        , TrendCardFactory $trencardFactory
        , $logger
    )
    {
        $this->trencardCached = $trencardCached;
        $this->trencardFactory = $trencardFactory;
        $this->logger = $logger;
    }

    public function handle()
    {
        $trendcardList = $this->trencardCached->hgetall();
        foreach ($trendcardList as $queueId => $value) {
            try {
                $value = unserialize($value);
                $handler = $this->trencardFactory->createHandler($value);
                $handler->handle(
                    new TrendCardRetrieveFromCacheRequest($queueId)
                );
            } catch(\Exception $e) {
                $this->logger->error($e->getMessage());
                continue;
            }

        }
    }
}