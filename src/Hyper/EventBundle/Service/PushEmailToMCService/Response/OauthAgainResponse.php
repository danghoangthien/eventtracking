<?php
namespace Hyper\EventBundle\Service\PushEmailToMCService\Response;

final class OauthAgainResponse
{
    private $callbackOauthUri;

    public function __construct($callbackOauthUri)
    {
        $this->callbackOauthUri = $callbackOauthUri;
    }

    public function content()
    {
        return [
            'error' => 1
            , 'result' => '<a href="'.$this->callbackOauthUri.'" target="_blank">Please <strong>login</strong> with MailChimp first and then press push to MailChimp again!</a>'
        ];
    }
}