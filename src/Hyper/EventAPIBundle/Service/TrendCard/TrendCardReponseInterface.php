<?php
namespace Hyper\EventAPIBundle\Service\TrendCard;

interface TrendCardReponseInterface
{
    public function queueId();
    public function queueStatus();
    public function queueBody();
    public function expired();

}