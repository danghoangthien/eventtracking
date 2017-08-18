<?php

namespace Hyper\EventAPIBundle\Controller\V1\User\Registration;

use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\JsonResponse,
    Symfony\Component\HttpFoundation\Response,
    Hyper\EventAPIBundle\Controller\APIBaseController,
    Hyper\EventAPIBundle\Service\User\Registration\ClientInfo\ClientInfoRequest,
    Hyper\EventAPIBundle\Service\User\Registration\ClientInfo\ClientInfoHandler;

class ClientInfoController extends APIBaseController
{

    public function indexAction(Request $request)
    {
        $clientInfoHandler = new ClientInfoHandler(
            $this->container->get('application_platform_repository')
            , $this->container->get('application_title_repository')
            , $this->container->get('client_app_title_repository')
        );
        try {
            $resp = $clientInfoHandler->handle(
                new ClientInfoRequest($request->request->get('app_ids'))
            );
            $dataOutput = [
                'status_code'=> Response::HTTP_OK,
                'client_access'=>true,
                'result'=> $resp
            ];
            return new JsonResponse($dataOutput, Response::HTTP_OK);
        } catch(\Exception $e) {
            $dataOutput = [
                'status_code'=>Response::HTTP_BAD_REQUEST,
                'client_access'=>true,
                'error'=> $e->getMessage()
            ];
            return new JsonResponse($dataOutput, Response::HTTP_BAD_REQUEST);
        }
    }
}
