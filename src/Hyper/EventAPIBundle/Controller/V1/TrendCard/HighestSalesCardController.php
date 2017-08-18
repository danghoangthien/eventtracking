<?php

namespace Hyper\EventAPIBundle\Controller\V1\TrendCard;

use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\JsonResponse,
    Symfony\Component\HttpFoundation\Response,
    Hyper\EventAPIBundle\Controller\APIBaseController,
    Hyper\EventBundle\Service\Cached\TrendCard\TrendCardCached,
    Hyper\EventBundle\Service\Cached\App\AppCached,
    Hyper\EventBundle\Service\Cached\InappeventConfig\InappeventConfigCached,
    Hyper\EventAPIBundle\Service\TrendCard\SendToCacheHandler,
    Hyper\EventAPIBundle\Service\TrendCard\HighestSalesCard\HighestSalesCardSendToCacheRequest,
    Hyper\EventAPIBundle\Service\TrendCard\RetrieveFromCacheHandler,
    Hyper\EventAPIBundle\Service\TrendCard\TrendCardRetrieveFromCacheRequest;

class HighestSalesCardController extends APIBaseController
{

    public function sendToCacheAction(Request $request)
    {
        $sendToCacheHandler = new SendToCacheHandler(
            new TrendCardCached($this->container)
        );
        try {
            $queueId = $sendToCacheHandler->handle(
                new HighestSalesCardSendToCacheRequest(
                    new InappeventConfigCached($this->container)
                    , $request->request->get('app_id')
                )
            );

            return new JsonResponse(
                [
                    'status_code' => Response::HTTP_OK
                    , 'queue_id' => $queueId
                ]
            );
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(
                [
                    'status_code' => Response::HTTP_BAD_REQUEST
                    , 'error_messsage' => $e->getMessage()
                ]
            );
        } catch(\Exception $e) {
            return new JsonResponse(
                [
                    'status_code' => $e->getCode()
                    , 'error_messsage' => $e->getMessage()
                ]
            );
        }

    }

    public function retrieveFromCacheAction(Request $request)
    {
        $retrieveFromCacheHandler = new RetrieveFromCacheHandler(
            new TrendCardCached($this->container)
        );
        try {
            $data = $retrieveFromCacheHandler->handle(
                new TrendCardRetrieveFromCacheRequest($request->attributes->get('queue_id'))
            );

            return new JsonResponse($data);
        } catch (\InvalidArgumentException $e) {

            return new JsonResponse(
                [
                    'status_code' => Response::HTTP_BAD_REQUEST
                    , 'error_messsage' => $e->getMessage()
                ]
            );
        }


    }
}
