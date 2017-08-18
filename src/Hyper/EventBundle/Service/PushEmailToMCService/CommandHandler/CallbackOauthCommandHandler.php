<?php
namespace Hyper\EventBundle\Service\PushEmailToMCService\CommandHandler;

use Hyper\EventBundle\Service\PushEmailToMCService\Command\CallbackOauthCommand
    , GuzzleHttp\Client
    , GuzzleHttp\TransferStats;

final class CallbackOauthCommandHandler
{
    private $gzClient;
    private $clientId;
    private $clientSecrect;
    private $redirectUri;
    private $baseUri = 'https://login.mailchimp.com/oauth2/';
    private $mcVersion = '3.0';

    public function __construct(
        $clientId
        , $clientSecrect
        , $redirectUri
    ) {
        $this->clientId = $clientId;
        $this->clientSecrect = $clientSecrect;
        $this->redirectUri = $redirectUri;
        $this->gzClient = new Client([
            'base_uri' => $this->baseUri
        ]);
    }

    public function execute(CallbackOauthCommand $callbackOauthCommand)
    {
        $code = $callbackOauthCommand->code();
        $ret = '';
        if ($code) {
            $accessToken = '';
            $request = $this->gzClient->request('POST', 'token'
                , [
                    'form_params' => [
                        'grant_type' => 'authorization_code'
                        , 'client_id' => $this->clientId
                        , 'client_secret' => $this->clientSecrect
                        , 'redirect_uri' => $this->redirectUri
                        , 'code' => $code
                    ]
                ]
            );
            $response = $request->getBody()->getContents();
            if (!empty($response)) {
                $response = json_decode($response, true);
            }
            if (!empty($response['access_token'])) {
                $accessToken = $response['access_token'];
            }
            if (!empty($accessToken)) {
                $request = $this->gzClient->request('POST', 'metadata', [
                        'headers' => [
                            'Authorization' => 'OAuth ' . $accessToken
                        ]
                    ]
                );
                $response = $request->getBody()->getContents();
                if (!empty($response)) {
                    $response = json_decode($response, true);
                    $response['access_token'] = $accessToken;
                    $response['api_endpoint'] = $response['api_endpoint'] .'/' . $this->mcVersion . '/';
                    $ret = $response;
                }
            }
        } else {
            $ret = implode("?",[
                $this->baseUri . 'authorize'
                , http_build_query([
                    'response_type' => 'code'
                    , 'client_id' => $this->clientId
                    , 'client_secret' => $this->clientSecrect
                    , 'redirect_uri' => $this->redirectUri
                ])
            ]);
        }

        return $ret;
    }
}