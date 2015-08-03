<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/app/example", name="homepage")
     */
    public function indexAction()
    {
        return $this->render('default/index.html.twig');
    }
    
    /**
     * @Route("/app/postback", name="event_postback")
     */
    public function postbackAction(Request $request)
    {
        echo "start to work with postback";
        
        $contentType = $request->headers->get('Content-Type');
        if ($request->getMethod() == 'POST' && $contentType=='application/json') {
            var_dump(json_decode($request->getContent()));
            $logger = $this->get('logger');
            $logger->info('content from appfly:');
            $logger->info($request->getContent());
        }
        
       // $client = new \GuzzleHttp\Client(['base_uri' => 'http://http://ec2-52-26-255-227.us-west-2.compute.amazonaws.com/event_tracking/web/app.php/']);
        //$client = new \GuzzleHttp\Client(['base_uri' => 'http://stackoverflow.com/questions/29258037/curl-cannot-resolve-valid-aws-host-in-vagrant']);
        // Send a request to https://foo.com/api/test
        //$response = $client->get('');
        //print_r($response);
        //echo "---";
        die;
        //return 'aa';
        //return $this->render('default/index.html.twig');
    }
    
}
