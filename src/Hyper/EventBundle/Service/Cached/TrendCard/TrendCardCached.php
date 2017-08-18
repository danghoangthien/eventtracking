<?php
namespace Hyper\EventBundle\Service\Cached\TrendCard;

use \Symfony\Component\DependencyInjection\ContainerInterface,
    Hyper\EventBundle\Service\Cached\CachedInterface,
    Hyper\EventBundle\Service\Cached\Cached;

class TrendCardCached extends Cached implements CachedInterface
{
    protected $cachedPrefix = 'trend_card';
    protected $cachedVersion;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->cachedVersion = $container->getParameter('cached_version');
    }

    protected function generateCacheKey()
    {
        return implode(':', [
            $this->getCachedVersionByPrefix($this->cachedPrefix),
            $this->cachedPrefix
        ]);
    }
}