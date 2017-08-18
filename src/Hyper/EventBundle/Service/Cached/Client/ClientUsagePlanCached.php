<?php
namespace Hyper\EventBundle\Service\Cached\Client;

use \Symfony\Component\DependencyInjection\ContainerInterface,
    Hyper\EventBundle\Service\Cached\CachedInterface,
    Hyper\EventBundle\Service\Cached\Cached;

class ClientUsagePlanCached extends Cached implements CachedInterface
{
    protected $prefix = 'client_usage_plan';
    protected $cachedVersion;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->cachedVersion = $container->getParameter('cached_version');
    }

    protected function generateCacheKey()
    {
        return implode(':', [
            $this->getCachedVersionByPrefix($this->prefix),
            $this->prefix
        ]);
    }
}