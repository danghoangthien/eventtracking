<?php

namespace Hyper\EventBundle\Event;

use Symfony\Component\EventDispatcher\Event,
    Hyper\EventBundle\Service\CreateFilterDefault,
    Hyper\Domain\Authentication\Authentication;

/**
 * The user_login_history.logined event is dispatched each time which user logged
 * in the system.
 */
class UserCreateEvent extends Event
{
    const USER_CREATE = 'user.create';

    protected $container;
    protected $auth;

    public function __construct(
        $container
        , Authentication $auth
    )
    {
        $this->container = $container;
        $this->auth = $auth;
        $createFilterDefault = new CreateFilterDefault(
            $this->container
            , $this->auth
        );
        $createFilterDefault->handle();
    }
}