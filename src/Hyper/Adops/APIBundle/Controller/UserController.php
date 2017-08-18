<?php

namespace Hyper\Adops\APIBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;

use Hyper\Adops\WebBundle\DomainBundle\WebserviceUserProvider;

/**
 * @author Carl Pham <vanca.vnn@gmail.com>
 */
 
class UserController extends FOSRestController implements ClassResourceInterface
{
    /**
     * Authentication user
     * 
     * @Post("/auth", name = "authentication", options = {"method_prefix" = false})
     *
     * @param Request $request
     * @author Carl Pham <vanca.vnn@gmail.com>
     * @return json
     */
    public function authenticationAction(Request $request)
    {
        $encoder = $this->container->get('security.password_encoder');
        $data = $request->request->all();
        if (!isset($data['_username']) || !isset($data['_password'])) {
            return ['status'=>false, 'message'=>'Empty username or password!'];
        }
        $username = $data['_username'];
        $passwordRaw = $data['_password'];
        
        $userProvider = new \Hyper\Adops\WebBundle\DomainBundle\WebserviceUserProvider($this->container);
        $userInterface = $userProvider->loadUserByUsername($username);
        
        if ($userInterface) {
            if (!$encoder->isPasswordValid($userInterface, $passwordRaw)) {
                return ['status'=>false, 'message'=>'Password wrong!']; 
            }
            $appAccess = $this->getAppAccess($userInterface->getAppId());
            $userInterface->setAppId(json_encode($appAccess));
            return $userInterface;
        }
        return ['status'=>false, 'message'=>'User not found!'];
    }
    
    public function getAppAccess($appAccessIds)
    {
        $result = [];
        $appAccessIds = json_decode($appAccessIds, true);
        $applications = $this->get('adops.web.application.repository')->findBy(['id'=>$appAccessIds]);
        if (empty($applications)) {
            return $result;
        }
        foreach ($applications as $application) {
            array_push($result, $application->getAppId());
        }
        return $result;
    }
}