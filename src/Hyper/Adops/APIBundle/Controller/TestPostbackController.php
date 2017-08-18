<?php

namespace Hyper\Adops\APIBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Controller\Annotations\RequestParam;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\Options;

use GuzzleHttp\Client;
use Hyper\Adops\APIBundle\Domain\AdopsLog;

class TestPostbackController extends FOSRestController implements ClassResourceInterface
{

    /**
     * 
     * 
     * @Post("", name = "create", options = {"method_prefix" = false})
     *
     *
     * @param Request $request param request to create new postback
     * @author Carl Pham <vanca.vnn@gmail.com>
     * @return array
     */
    public function postAction(Request $request)
    {
        $dataJson = $request->request->all();
        $dataJson['request_url'] = $request->getUri();
        $adopsLog = new AdopsLog();
        $adopsLogRepo = $this->get('adops.api.log.repository');
        $adopsLogRepo->createAdopsLog(array(
            'detail' => $dataJson,
            'postback_id' => '123',
            'postback_url' => 'testpostbacks',
            'status' => 200
        ));
        
        return ['status' => true, 'data' => $dataJson];
    }
    
}
