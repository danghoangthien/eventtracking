<?php
namespace Hyper\EventBundle\Service\Cached\User;

use \Symfony\Component\DependencyInjection\ContainerInterface,
    Hyper\EventBundle\Service\Cached\CachedInterface,
    Hyper\EventBundle\Service\Cached\Cached;

class UserFilterCached extends Cached implements CachedInterface
{
    protected $filterPrefix = 'filter';
    protected $userPrefix = 'user';
    protected $userId;

    public function __construct(ContainerInterface $container, $userId)
    {
        parent::__construct($container);
        $this->userId = $userId;
    }

    protected function generateCacheKey()
    {
        return implode(':', [
            $this->getCachedVersionByPrefix($this->filterPrefix),
            $this->userPrefix,
            $this->userId,
            $this->filterPrefix
        ]);
    }
}