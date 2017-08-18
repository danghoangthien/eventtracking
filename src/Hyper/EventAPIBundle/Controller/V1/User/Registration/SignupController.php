<?php

namespace Hyper\EventAPIBundle\Controller\V1\User\Registration;

use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\JsonResponse,
    Symfony\Component\HttpFoundation\Response,
    Hyper\EventAPIBundle\Controller\APIBaseController,
    Hyper\EventBundle\Service\Cached\App\AppCached,
    Hyper\EventAPIBundle\Service\User\Registration\Signup\Request\SignupRequest,
    Hyper\EventAPIBundle\Service\User\Registration\Signup\Handler\SignupHandler;

class SignupController extends APIBaseController
{

    public function indexAction(Request $request)
    {
        $logger = $this->get('monolog.logger.event_api');
        $signupHandler = new SignupHandler(
            $this->container
            , $this->container->get('application_platform_repository')
            , $this->container->get('application_title_repository')
            , $this->container->get('client_app_title_repository')
            , $this->container->get('client_repository')
            , $this->container->get('authentication_repository')
            , $this->container->get('doctrine')->getManager('pgsql')
            , new AppCached($this->container)
        );
        try {
            $signupHandler->handle(
                new SignupRequest(
                    $request->request->get('user_info')
                    , $request->request->get('client_info')
                )
            );
            $dataOutput = [
                'status_code '=> Response::HTTP_OK,
                'client_access' => true
            ];
            return new JsonResponse($dataOutput, Response::HTTP_OK);
        } catch(\Exception $e) {
            $infoValue = [
                'params' => [
                    'POST' => $request->request->all(), 'GET' => $request->query->all()
                ]
            ];
            $logger->info(json_encode($infoValue));
            $logger->error($e->getMessage());
            $dataOutput = [
                'status_code' => Response::HTTP_BAD_REQUEST,
                'error_message'=> $e->getMessage()
            ];

            return new JsonResponse($dataOutput, Response::HTTP_BAD_REQUEST);
        }
    }
}
