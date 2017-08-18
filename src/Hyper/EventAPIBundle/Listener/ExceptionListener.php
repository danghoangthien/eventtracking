<?php

namespace Hyper\EventAPIBundle\Listener;

use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent,
    Symfony\Component\HttpFoundation\JsonResponse,
    Hyper\EventAPIBundle\Exception\ApiException;

class ExceptionListener 
{
	
	/**
	 * Customize response
	 *
	 * @param  GetResponseForExceptionEvent $event
	 */
	public function onKernelException(GetResponseForExceptionEvent $event)
	{		
		$exception = $event->getException();
		if ($exception instanceof ApiException) {
		    $response = new JsonResponse(json_decode($exception->getMessage()) , $exception->getCode());
		    $event->setResponse($response);
		}
	}
	
}