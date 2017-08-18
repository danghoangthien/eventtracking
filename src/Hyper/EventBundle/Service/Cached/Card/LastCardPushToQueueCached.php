<?php
namespace Hyper\EventBundle\Service\Cached\Card;

use \Symfony\Component\DependencyInjection\ContainerInterface,
    Hyper\EventBundle\Service\Cached\CachedInterface,
    Hyper\EventBundle\Service\Cached\Cached;

class LastCardPushToQueueCached extends Cached implements CachedInterface
{
    protected $prefix = 'last_card_push_to_queue';
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