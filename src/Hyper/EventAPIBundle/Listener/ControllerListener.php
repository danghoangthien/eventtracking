<?php

namespace Hyper\EventAPIBundle\Listener;

use Symfony\Component\EventDispatcher\Event,
    Symfony\Component\HttpKernel\HttpKernelInterface,
    Symfony\Component\HttpFoundation\JsonResponse,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpKernel\Event\FilterControllerEvent;

class ControllerListener
{

	/**
	 * Inject [preExecute] method on all Actions
	 *
	 */
	public function onKernelController(FilterControllerEvent  $event)
	{
		if (HttpKernelInterface::MASTER_REQUEST === $event->getRequestType())
		{
			$controllers = $event->getController();
			if(is_array($controllers))
			{
				$controller = $controllers[0];

				if (is_object($controller) && method_exists($controller, 'preExecuteAPI'))
				{
					$controller->preExecuteAPI($event->getRequest());
				}
			}
		}
	}

}