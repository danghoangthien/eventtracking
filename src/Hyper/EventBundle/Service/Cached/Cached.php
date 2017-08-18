<?php
namespace Hyper\EventBundle\Service\Cached;

use \Symfony\Component\DependencyInjection\ContainerInterface,
    Hyper\EventBundle\Service\Cached\CachedInterface;

class Cached implements CachedInterface
{
    protected $cached;
    protected $cachedVersion;
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->cached = $this->container->get('snc_redis.default');
        $this->cachedVersion = $container->getParameter('cached_version');
    }

    public function getCachedVersionByPrefix($prefix)
    {
        return (!empty($this->cachedVersion[$prefix]) ? $this->cachedVersion[$prefix] : '');
    }

    public function hset($key, $value)
    {
        $this->cached->hset($this->generateCacheKey(), $key, $value);

        return $this;
    }

    public function hget($key)
    {
        return $this->cached->hget($this->generateCacheKey(), $key);
    }

    public function hdel($key)
    {
        return $this->cached->hdel($this->generateCacheKey(), $key);
    }

    public function hmset(array $value)
    {
        return $this->cached->hmset($this->generateCacheKey(), $value);
    }


    public function hgetall()
    {
        return $this->cached->hgetall($this->generateCacheKey());
    }

    public function exists()
    {
        return $this->cached->exists($this->generateCacheKey());
    }

    public function set($value)
    {
        $this->cached->set($this->generateCacheKey(), $value);

        return $this;
    }

    public function get()
    {
        return $this->cached->get($this->generateCacheKey());
    }
}