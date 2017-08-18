<?php
namespace Hyper\EventAPIBundle\Service\TrendCard;

interface TrendCardSendToCacheRequestInterface
{
    public function queueId();
    public function timestamp();
    public function serialize();
    public function unserialize($serialized);
}