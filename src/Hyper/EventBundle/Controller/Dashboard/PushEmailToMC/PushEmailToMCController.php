<?php

namespace Hyper\EventBundle\Controller\Dashboard\PushEmailToMC;

use Symfony\Bundle\FrameworkBundle\Controller\Controller
    , Symfony\Component\HttpFoundation\Request
    , Symfony\Component\HttpFoundation\JsonResponse
    , Hyper\EventBundle\Service\PushEmailToMCService\CommandHandler\CallbackOauthCommandHandler
    , Hyper\EventBundle\Service\PushEmailToMCService\Command\CallbackOauthCommand
    , Hyper\EventBundle\Service\PushEmailToMCService\CommandHandler\LoadSubscriberListCommandHandler
    , Hyper\EventBundle\Service\PushEmailToMCService\Command\LoadSubscriberListCommand
    , Hyper\EventBundle\Service\PushEmailToMCService\CommandHandler\PushEmailToMCCommandHandler
    , Hyper\EventBundle\Service\PushEmailToMCService\Command\PushEmailToMCCommand
    , Hyper\EventBundle\Service\PushEmailToMCService\Response\LoadSubscriberListResponse
    , Hyper\EventBundle\Service\PushEmailToMCService\Response\OauthAgainResponse
    , Hyper\EventBundle\Service\Cached\User\UserFilterCached
    , Symfony\Component\HttpKernel\Exception\HttpException
    , Symfony\Component\HttpFoundation\Response;

class PushEmailToMCController extends Controller
{

    public function pushEmailToMCAction(Request $request)
    {
        $auth = $this->get('security.context')->getToken()->getUser();
        if (
            $auth->isDemoAccount()
            || $auth->isLimitAccount()
        ) {
            throw new HttpException(Response::HTTP_FORBIDDEN, "Sorry you cannot make changes as this feature is disabled in demo mode.");
        }
        $cardId = $request->query->get('card_id');
        $s3Client = '';
        $userFilterCached = new UserFilterCached($this->container, $auth->getId());
        $s3Client = $this->container->get('hyper_event_processing.s3_wrapper')->getS3Client();
        $subscriberListId = $request->query->get('subscriber_list_id');
        $session = $request->getSession();
        $mcMetadata = $session->get($this->getMCMetadataKey());
        $pushEmailToMCCommandHandler = new PushEmailToMCCommandHandler(
            $this->container->getParameter('mailchimp')['client_id']
            , $this->container->getParameter('mailchimp')['client_secret']
            , $this->generateUrl('dashboard_push_email_to_mc_callback_oauth', [], true)
            , $s3Client
            , $userFilterCached
            , $mcMetadata
        );
        try {
            $pushEmailToMCCommandHandler->execute(
                new PushEmailToMCCommand(
                    $cardId
                    , $subscriberListId
                )
            );

            $json = [
                'error' => 0
            ];
        } catch (\Exception $e) {
            $json = [
                'error' => 1,
                'result' => $e->getMessage()
            ];
        }
        return new JsonResponse($json);
    }

    public function callbackOauthAction(Request $request)
    {
        $auth = $this->get('security.context')->getToken()->getUser();
        if (
            $auth->isDemoAccount()
            || $auth->isLimitAccount()
        ) {
            throw new HttpException(Response::HTTP_FORBIDDEN, "Sorry you cannot make changes as this feature is disabled in demo mode.");
        }
        $callbackOauthCommandHandler = new CallbackOauthCommandHandler(
            $this->container->getParameter('mailchimp')['client_id']
            , $this->container->getParameter('mailchimp')['client_secret']
            , $this->generateUrl('dashboard_push_email_to_mc_callback_oauth', [], true)
        );
        $mcMetadata = $callbackOauthCommandHandler->execute(
            new CallbackOauthCommand(
                $request->query->get('code')
            )
        );
        $hasAccessToken = false;
        $session = $request->getSession();
        $session->set($this->getMCMetadataKey(), $mcMetadata);
        if ($session->get($this->getMCMetadataKey())) {
            $hasAccessToken = true;
        }
        return $this->render('::push_email_to_mc/callback_oauth_mc.html.twig', array('hasAccessToken' => $hasAccessToken));
    }

    public function loadSubscriberListAction(Request $request)
    {
        $auth = $this->get('security.context')->getToken()->getUser();
        if (
            $auth->isDemoAccount()
            || $auth->isLimitAccount()
        ) {
            throw new HttpException(Response::HTTP_FORBIDDEN, "Sorry you cannot make changes as this feature is disabled in demo mode.");
        }
        $session = $request->getSession();
        //$session->set($this->getMCMetadataKey(), '');
        $mcMetadata = $session->get($this->getMCMetadataKey());
        $mailchimpParameter = $this->container->getParameter('mailchimp');
        $loadSubscriberListCommandHandler = new LoadSubscriberListCommandHandler(
            $mailchimpParameter['client_id']
            , $mailchimpParameter['client_secret']
            , $this->generateUrl('dashboard_push_email_to_mc_callback_oauth', [], true)
        );
        $response = $loadSubscriberListCommandHandler->execute(
            new LoadSubscriberListCommand($mcMetadata)
        );
        if ($response instanceof OauthAgainResponse) {
            return new JsonResponse($response->content());
        } else if ($response instanceof LoadSubscriberListResponse) {
            return new JsonResponse($response->content());
        }
    }

    private function getMCMetadataKey()
    {
        $auth = $this->get('security.context')->getToken()->getUser();
        $mcMetadataKey = $auth->getId() . '_mc_metadata';

        return $mcMetadataKey;
    }
}