<?php

namespace Hyper\Adops\WebBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;

use Hyper\Adops\WebBundle\Domain\AdopsUser;

/**
 * @author Carl Pham <vanca.vnn@gmail.com>
 */
class SecurityController extends Controller
{
    /**
     * @Route("/adops/login", name="adops_login")
     */
    public function indexAction(Request $request)
    {
        // $user = new AdopsUser();
        // $plainPassword = 'devteam';
        // $encoder = $this->container->get('security.password_encoder');
        // $encoded = $encoder->encodePassword($user, $plainPassword);

        // $user->setPassword($encoded);
        // $user->setUsername('admin');
        // $user->setEmail('vanca.vnn@gmail.com');
        // $user->setIsActive(true);
        // $user->setFullname('Carl Pham');
        // $user->setType(1);

        // $userRepo = $this->get('adops.web.user.repository');
        // $userRepo->create($user);
        // var_dump($user);die;

        $authenticationUtils = $this->get('security.authentication_utils');
        $securityContext = $this->get('security.context');
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        if ($securityContext->isGranted('ROLE_USER_ADMIN')) {
            return $this->redirectToRoute('adops_dashboard');
        }
        if ($securityContext->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('adops_clients_dashboard');
        }
        $targetPath = '/adops/login';
        return $this->render(
            'adops/login.html.twig',
            ['error' => $error, 'target_path' => $targetPath]
        );

    }

    /**
     * @Route("/adops/login_check", name="adops_login_check")
     * @Method("POST")
     */
    public function loginCheckAction(Request $request)
    {
    }

    /**
     * @Route("/adops/logout", name="adops_logout")
     * @Method({"GET", "POST"})
     */
    public function logoutAction(Request $request)
    {
        $token = new AnonymousToken($providerKey, 'anon.');
        $this->get('security.context')->setToken($token);
        $this->get('request')->getSession()->invalidate();
    }

}
