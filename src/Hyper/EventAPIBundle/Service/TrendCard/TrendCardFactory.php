<?php
namespace Hyper\EventAPIBundle\Service\TrendCard;

use Hyper\EventBundle\Service\Cached\TrendCard\TrendCardCached;
use Hyper\EventBundle\Service\Cached\App\AppCached;
use Hyper\EventBundle\Service\HyperESClient;
use Hyper\EventAPIBundle\Service\TrendCard\IAEStatsCard\IAEStatsCardSendToCacheRequest;
use Hyper\EventAPIBundle\Service\TrendCard\IAEStatsCard\IAEStatsCardResponse;
use Hyper\EventAPIBundle\Service\TrendCard\IAEStatsCard\IAEStatsCardHandler;

use Hyper\EventAPIBundle\Service\TrendCard\HighestSalesCard\HighestSalesCardSendToCacheRequest;
use Hyper\EventAPIBundle\Service\TrendCard\HighestSalesCard\HighestSalesCardResponse;
use Hyper\EventAPIBundle\Service\TrendCard\HighestSalesCard\HighestSalesCardHandler;

use Hyper\EventAPIBundle\Service\TrendCard\PurchaseRateCard\PurchaseRateCardSendToCacheRequest;
use Hyper\EventAPIBundle\Service\TrendCard\PurchaseRateCard\PurchaseRateCardResponse;
use Hyper\EventAPIBundle\Service\TrendCard\PurchaseRateCard\PurchaseRateCardHandler;

use Hyper\EventAPIBundle\Service\TrendCard\DormantRateCard\DormantRateCardSendToCacheRequest;
use Hyper\EventAPIBundle\Service\TrendCard\DormantRateCard\DormantRateCardResponse;
use Hyper\EventAPIBundle\Service\TrendCard\DormantRateCard\DormantRateCardHandler;

use Hyper\EventAPIBundle\Service\TrendCard\GhostRateCard\GhostRateCardSendToCacheRequest;
use Hyper\EventAPIBundle\Service\TrendCard\GhostRateCard\GhostRateCardResponse;
use Hyper\EventAPIBundle\Service\TrendCard\GhostRateCard\GhostRateCardHandler;

use Hyper\EventAPIBundle\Service\TrendCard\ModeOfSalesCard\ModeOfSalesCardSendToCacheRequest;
use Hyper\EventAPIBundle\Service\TrendCard\ModeOfSalesCard\ModeOfSalesCardResponse;
use Hyper\EventAPIBundle\Service\TrendCard\ModeOfSalesCard\ModeOfSalesCardHandler;

use Hyper\EventAPIBundle\Service\TrendCard\TotalSalesCard\TotalSalesCardSendToCacheRequest;
use Hyper\EventAPIBundle\Service\TrendCard\TotalSalesCard\TotalSalesCardResponse;
use Hyper\EventAPIBundle\Service\TrendCard\TotalSalesCard\TotalSalesCardHandler;

use Hyper\EventAPIBundle\Service\TrendCard\InstallCityCard\InstallCityCardSendToCacheRequest;
use Hyper\EventAPIBundle\Service\TrendCard\InstallCityCard\InstallCityCardResponse;
use Hyper\EventAPIBundle\Service\TrendCard\InstallCityCard\InstallCityCardHandler;

class TrendCardFactory
{
    private $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function createHandler($type)
    {
        $handler = '';
        if (
            $type instanceof IAEStatsCardSendToCacheRequest
            || $type instanceof IAEStatsCardResponse
        ) {
            $handler = new IAEStatsCardHandler(
                new TrendCardCached($this->container)
                , new AppCached($this->container)
                , new HyperESClient($this->container)
                , $this->container->getParameter('amazon_elasticsearch')['index_version']
            );
        } else if (
            $type instanceof HighestSalesCardSendToCacheRequest
            || $type instanceof HighestSalesCardResponse
        )
        {
            $handler = new HighestSalesCardHandler(
                new TrendCardCached($this->container)
                , new AppCached($this->container)
                , $this->container->get('action_repository')
                , new HyperESClient($this->container)
                , $this->container->getParameter('amazon_elasticsearch')['index_version']
            );
        } else if (
            $type instanceof PurchaseRateCardSendToCacheRequest
            || $type instanceof PurchaseRateCardResponse
        ){
            $handler = new PurchaseRateCardHandler(
                new TrendCardCached($this->container)
                , new AppCached($this->container)
                , new HyperESClient($this->container, [], false)
                , $this->container->getParameter('amazon_elasticsearch')['index_version']
            );
        } else if (
            $type instanceof DormantRateCardSendToCacheRequest
            || $type instanceof DormantRateCardResponse
        ){
            $handler = new DormantRateCardHandler(
                new TrendCardCached($this->container)
                , new AppCached($this->container)
                , new HyperESClient($this->container, [], false)
                , $this->container->getParameter('amazon_elasticsearch')['index_version']
            );
        } else if (
            $type instanceof GhostRateCardSendToCacheRequest
            || $type instanceof GhostRateCardResponse
        ){
            $handler = new GhostRateCardHandler(
                new TrendCardCached($this->container)
                , new AppCached($this->container)
                , new HyperESClient($this->container, [], false)
                , $this->container->getParameter('amazon_elasticsearch')['index_version']
            );
        } else if (
            $type instanceof ModeOfSalesCardSendToCacheRequest
            || $type instanceof ModeOfSalesCardResponse
        ){
            $handler = new ModeOfSalesCardHandler(
                new TrendCardCached($this->container)
                , new AppCached($this->container)
                , $this->container->get('action_repository')
            );
        } else if (
             $type instanceof TotalSalesCardSendToCacheRequest
            || $type instanceof TotalSalesCardResponse
        )
        {
            $handler = new TotalSalesCardHandler(
                new TrendCardCached($this->container)
                , new AppCached($this->container)
                , $this->container->get('action_repository')
                , $this->container->get('application_title_repository')
                , $this->container->get('application_platform_repository')
                , $this->container->get('client_app_title_repository')
            );
        } else if (
             $type instanceof InstallCityCardSendToCacheRequest
            || $type instanceof InstallCityCardResponse
        )
        {
            $handler = new InstallCityCardHandler(
                new TrendCardCached($this->container)
                , new AppCached($this->container)
                , $this->container->get('action_repository')
            );
        }

        if (empty($handler)) {
            throw new \InvalidArgumentException('Can not instant');
        }

        return $handler;
    }
}