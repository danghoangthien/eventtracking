<?php
namespace Hyper\EventBundle\Service\Cached\App;

use \Symfony\Component\DependencyInjection\ContainerInterface,
    Hyper\EventBundle\Service\Cached\CachedInterface,
    Hyper\EventBundle\Service\Cached\Cached;

class AppCached extends Cached implements CachedInterface
{
    protected $appPrefix = 'app';
    protected $cachedVersion;
    protected $userId;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->cachedVersion = $container->getParameter('cached_version');
    }

    protected function generateCacheKey()
    {
        return implode(':', [
            $this->getCachedVersionByPrefix($this->appPrefix),
            $this->appPrefix
        ]);
    }
}