<?php
namespace Hyper\EventBundle\Service\Cached\AnalyticMetadata;

use \Symfony\Component\DependencyInjection\ContainerInterface,
    Hyper\EventBundle\Service\Cached\CachedInterface,
    Hyper\EventBundle\Service\Cached\Cached;

class CountDeviceByAppTitleCached extends Cached implements CachedInterface
{
    protected $analyticPrefix = 'analytic_metadata';
    protected $countDeviceByAppTitlePrefix = 'count_device_by_app_title';
    protected $cachedVersion;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->cachedVersion = $container->getParameter('cached_version');
    }

    protected function generateCacheKey()
    {
        return implode(':', [
            $this->getCachedVersionByPrefix($this->countDeviceByAppTitlePrefix),
            $this->analyticPrefix,
            $this->countDeviceByAppTitlePrefix,
        ]);
    }
}