<?php
namespace Hyper\EventBundle\Service\Cached\InappeventConfig;

use \Symfony\Component\DependencyInjection\ContainerInterface,
    Hyper\EventBundle\Service\Cached\CachedInterface,
    Hyper\EventBundle\Service\Cached\Cached;

class InappeventConfigCached extends Cached implements CachedInterface
{
    protected $cachePrefix = 'inappevent_config';

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
    }

    protected function generateCacheKey()
    {
        return implode(':', [
            $this->getCachedVersionByPrefix($this->cachePrefix),
            $this->cachePrefix
        ]);
    }
}