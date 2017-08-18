<?php
namespace Hyper\Adops\WebBundle\EventListener;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;

class AdopsWebListener
{
    private $router;
    private $container;
    
    public function __construct($router, $container)
    {
        $this->router = $router;
        $this->container = $container;
    }
    
    public function onKernelController(FilterControllerEvent $event)
    {
        $controller = $event->getController()[0];
        $validController = $this->validController($controller);
        if ($validController) {
            
            $session = $event->getRequest()->getSession();
            if (!$session->has('user_logined')) {
                $redirectUrl = $controller->generateUrl('adops_login');
                $event->setController(function() use ($redirectUrl) {
                    return new RedirectResponse($redirectUrl);
                });
            }
        }
    }
    
    private function validController($controller)
    {
        if ($controller instanceof \Hyper\Adops\WebBundle\Controller\ApplicationController
        || $controller instanceof \Hyper\Adops\WebBundle\Controller\CampaignController
        || $controller instanceof \Hyper\Adops\WebBundle\Controller\InappeventController
        || $controller instanceof \Hyper\Adops\WebBundle\Controller\PostbackController
        || $controller instanceof \Hyper\Adops\WebBundle\Controller\PublisherController
        || $controller instanceof \Hyper\Adops\WebBundle\Controller\DashboardController
        ) {
            return true;
        }
        return false;
    }
}