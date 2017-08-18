<?php

namespace Hyper\Adops\APIBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations\Get;

/**
 * @author Carl Pham <vanca.vnn@gmail.com>
 */
 
class GetIpController extends FOSRestController implements ClassResourceInterface
{
    /**
     * Get Ip from request
     * 
     * @Get("/", name = "getip", options = {"method_prefix" = false})
     *
     * @param Request $request
     * @author Carl Pham <vanca.vnn@gmail.com>
     * @return json
     */
    public function getIpAction()
    {
        /*if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }*/
        
        $cmd = "ifconfig eth0 | grep \"inet addr\" | awk -F: '{print $2}' | awk '{print $1}'";
        $ip = trim(shell_exec($cmd));
        
        return ['ip'=>$ip];
    }
}