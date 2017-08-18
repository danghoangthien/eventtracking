<?php
namespace Hyper\EventBundle\Service\Cached\AnalyticMetadata;

use \Symfony\Component\DependencyInjection\ContainerInterface,
    Hyper\EventBundle\Service\Cached\CachedInterface,
    Hyper\EventBundle\Service\Cached\Cached;

class CountDeviceByCountryCached extends Cached implements CachedInterface
{
    //protected $analyticPrefix = 'analytic_metadata';
    protected $countDeviceByCountryPrefix = 'ANALYTIC_METADATA_COUNT_DEVICE_BY_COUNTRY';
    //protected $cachedVersion;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
    }

    protected function generateCacheKey()
    {
        return md5($this->countDeviceByCountryPrefix);
    }
}