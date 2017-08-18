<?php
namespace Hyper\EventBundle\Service\Cached;

interface CachedInterface
{
    public function hset($key, $value);
    public function hget($key);
    public function hmset(array $value);
    public function exists();
}