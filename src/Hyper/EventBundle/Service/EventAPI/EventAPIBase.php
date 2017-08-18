<?php
namespace Hyper\EventBundle\Service\EventAPI;

use Symfony\Component\DependencyInjection\ContainerInterface,
    GuzzleHttp\Client as GzClient;

class EventAPIBase 
{

    /**
    * @var ContainerInterface
    */
    protected $container;
    
    /**
    * @var GuzzleHttp\Client
    */
    protected $gzClient;

    /**
    * @var string
    */
    protected $baseApiUrl;
    
    public function __construct(ContainerInterface $container) {
        $this->container = $container;
        $this->initProperties();
    }

    /**
    * Initialize property
    */
    protected function initProperties() {
        $this->baseApiUrl = $this->container->getParameter("event_api_base_api_url");
        $this->gzClient = new GzClient();
    }

    /**
    * Process api
    */
    public function runApi($endpoint, $method, $params, $fistCall = true) {
        $baseApiUrl = $this->baseApiUrl;
        $url = $baseApiUrl . $endpoint;
        
        if ($method == 'post') {
            $resp = $this->doPost($url, $params);
        } elseif ($method == 'get') {
            $resp = $this->doGet($url, $params);
        } else {
            $resp = json_encode(array('message' => 'invalid method request.'));
        }
        
        $resp = json_decode($resp, true);

        return $resp;
    }

    /**
    * Authentication
    * @return array
    */
    protected function authentication() {
        // Implement later
    }

    /**
    * Get session token
    * @return string
    */
    public function getAccessToken()
    {
        // Implement later
    }
    
    public function doPost($url, $params)
    {
        $resp = $this->gzClient->put($url, array(
            'form_params' => $params
        ));
        return $resp->getBody()->getContents();
    }
    
    public function doGet($url, $params)
    {
        $resp = $this->gzClient->get($url, [
            'query' => $params
        ]);
        return $resp->getBody()->getContents();
    }
}
