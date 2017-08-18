<?php
namespace Hyper\EventBundle\Service\Cached\AnalyticMetadata;

use \Symfony\Component\DependencyInjection\ContainerInterface,
    Hyper\EventBundle\Service\Cached\CachedInterface,
    Hyper\EventBundle\Service\Cached\Cached;

class RecentInAppEventCached extends Cached implements CachedInterface
{
    protected $analyticPrefix = 'analytic_metadata';
    protected $recentInAppEventPrefix = 'recent_in_app_event';
    protected $cachedVersion;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->cachedVersion = $container->getParameter('cached_version');
    }

    protected function generateCacheKey()
    {
        return implode(':', [
            $this->getCachedVersionByPrefix($this->recentInAppEventPrefix),
            $this->analyticPrefix,
            $this->recentInAppEventPrefix,
        ]);
    }
}