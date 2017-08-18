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

use Aws\Sqs\SqsClient;

use Hyper\Adops\APIBundle\Handler\SqsContainerExtendsHandler;
use Hyper\Adops\APIBundle\Domain\AdopsLog;

class PostbackController extends FOSRestController implements ClassResourceInterface
{
    public $sqsContainer = null;
    
    /**
     * 
     * 
     * @Get("/{type}", name = "create", options = {"method_prefix" = false})
     *
     * @param Request $request param request to create new postback
     * @author Carl Pham <vanca.vnn@gmail.com>
     * @return array
     */
    public function getAction(Request $request, $type)
    {
        $data = $request->query->all();
        if (empty($type)) {
            return ['status'=>false];
        }
        $data['type'] = $type;
        $res = $this->getSqsContainerExtendsHandler()->sendToSqs('amazon_sqs_queue_name',$data);
        
        return ['status' => $res];
    }
    
    public function getSqsContainerExtendsHandler()
    {
        if (!empty($this->sqsContainer)) {
            return $this->sqsContainer;
        }
        $this->sqsContainer = new SqsContainerExtendsHandler($this->container);
        
        return $this->sqsContainer;
    }
    
}
