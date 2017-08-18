<?php
namespace Hyper\EventBundle\Service\PushEmailToMCService\CommandHandler;

use Hyper\EventBundle\Service\PushEmailToMCService\CommandHandler\CallbackOauthCommandHandler
    , Hyper\EventBundle\Service\PushEmailToMCService\Command\CallbackOauthCommand
    , Hyper\EventBundle\Service\PushEmailToMCService\Command\LoadSubscriberListCommand
    , GuzzleHttp\Client
    , Hyper\EventBundle\Service\PushEmailToMCService\Response\LoadSubscriberListResponse
    , Hyper\EventBundle\Service\PushEmailToMCService\Response\OauthAgainResponse;

class LoadSubscriberListCommandHandler
{
    private $clientId;
    private $clientSecrect;
    private $redirectUri;
    private $gzClient;

    public function __construct(
        $clientId
        , $clientSecrect
        , $redirectUri
    ) {
        $this->clientId = $clientId;
        $this->clientSecrect = $clientSecrect;
        $this->redirectUri = $redirectUri;
    }

    public function execute(LoadSubscriberListCommand $loadSubscriberListCommand)
    {
        $mcMetadata = $loadSubscriberListCommand->mcMetadata();
        if (empty($mcMetadata)) {
            $callbackOauthCommandHandler = new CallbackOauthCommandHandler(
                $this->clientId
                , $this->clientSecrect
                , $this->redirectUri
            );
            $callbackOauthUri = $callbackOauthCommandHandler->execute(
                new CallbackOauthCommand('')
            );

            return new OauthAgainResponse($callbackOauthUri);
        }
        $this->gzClient = new Client([
            'base_uri' => $mcMetadata['api_endpoint']
        ]);
        $request = $this->gzClient->request('GET', 'lists', [
            'headers' => [
                'Authorization' => 'OAuth ' . $mcMetadata['access_token']
            ]
        ]);
        $response = $request->getBody()->getContents();
        if (!empty($response)) {
            $response = json_decode($response, true);
        }
        $lists = [];
        if (!empty($response['lists'])) {
            foreach ($response['lists'] as $list) {
                $lists[$list['id']] = $list['name'];
            }
        }

        return new LoadSubscriberListResponse($lists);
    }
}