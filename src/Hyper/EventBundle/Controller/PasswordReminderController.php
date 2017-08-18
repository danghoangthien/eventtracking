<?php

namespace Hyper\EventBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Hyper\Domain\Authentication\Authentication,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\JsonResponse,
    Symfony\Component\DependencyInjection\ContainerInterface;

class PasswordReminderController extends Controller
{
    public function indexAction(Request $request)
    {
        return $this->render('::password_reminder/index.html.twig');
    }
    
    public function validateAction(Request $request)
    {
        $resp = array(
            'msg' => '',
            'content' => ''
        );
        $email = $request->request->get('email');
        $message = $validEmail = '';
        if ($email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                $resp['msg'] = 'Please enter a valid email address.';
            } else {
                $validEmail = true;
            }
        }
        if ($validEmail) {
            $authRepo = $this->container->get('authentication_repository');
            $authFoundByEmail = $authRepo->findbyCriteria("email", $email);
            $passwordToken = $send = $authFoundByUpdate = '';
            if ($authFoundByEmail instanceof Authentication) {
                $passwordToken = md5(uniqid($email, true));
                $passwordTokenExpired = strtotime('+1 hour');
                $authFoundByUpdate = $authRepo->updateResetPasswordToken(
                    $email, 
                    $passwordToken, 
                    $passwordTokenExpired
                );
                if ($authFoundByUpdate instanceof Authentication) {
                    $send = $this->sendPasswordTokenToEmail(
                        $email, 
                        $authFoundByUpdate->getName(), 
                        $passwordToken
                    );
                }
                if ($send) {
                    $resp['content']= $this->renderView('::password_reminder/_validate.html.twig');
                } else {
                    $resp['msg'] = "An unknown error occurred. Please try again.";
                }
            } else {
                $resp['msg'] = "We don't have an account with the email address you have provided.";
            }
        }
        
        return new JsonResponse($resp);
    }
    
    private function sendPasswordTokenToEmail($email, $name, $passwordToken)
    {
        $from = $this->container->getParameter('mailer_from');
        $fromName = $this->container->getParameter('mailer_from_name');
        $message = \Swift_Message::newInstance()
                ->setSubject('Password Reset')
                ->setFrom(array($from => $fromName))
                ->setTo($email)
                ->setBody(
                    $this->renderView(
                        '::password_reminder/email_template/_send_password_token.html.twig', 
                        array(
                            'name' => $name,
                            'password_token'=> $passwordToken
                    )), 
                    'text/html'
                );
                
        return $this->get('mailer')->send($message);
    }
    
    public function resetPasswordAction(Request $request)
    {
        $tokenKey= $request->query->get('token');
        $form = $this->createFormBuilder()
            ->add('password', 'repeated', array(
                'type' => 'password', 'invalid_message' => 'Please enter the same value again.'))
            ->getForm();
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $authRepo = $this->container->get('authentication_repository');
                $newPassword = $form->get('password')->getData();
                $authFoundByToken = $authRepo->resetPasswordByToken(
                    $tokenKey,
                    $newPassword
                );
                if ($authFoundByToken instanceof Authentication) {
                    $this->setLogin($authFoundByToken);
                    return $this->redirectRouteRight($authFoundByToken);
                } 
            }
        }
        $code = $this->validPasswordToken($tokenKey);
        
        return $this->render(
            '::password_reminder/reset_password.html.twig', array(
                'code' => $code,
                'form' => $form->createView()
            )
        );
    }
    
    /**
     * Validate Password Token
     * 
     * @author minh@hypergorwth.co
     * @param string $tokenKey 
     * @return int 
     *      0: token valid
     *      1: token empty and token not found, 
     *      2: token expired, 
     * 
     **/
    private function validPasswordToken($tokenKey)
    {
        if (!$tokenKey) {
            return 1;
        }
        $authRepo = $this->container->get('authentication_repository');
        $authFoundByToken = $authRepo->findbyCriteria("resetPasswordToken", $tokenKey);
        if ($authFoundByToken instanceof Authentication) {
            if (strtotime('now') > $authFoundByToken->getResetPasswordExpired()) {
                return 2;
            } else {
                return 0;
            } 
        } else {
            return 1;
        }
    }
    
    /**
     * Redirect right way for user login
     * 
     * @author minh@hypergorwth.co
     * @param Hyper\Domain\Authentication\Authentication $auth 
     * @return Symfony\Component\HttpFoundation\RedirectResponse
     * 
     **/
    private function setLogin(Authentication $auth)
    {
        $_SESSION['authentication_id'] = $auth->getId();
        $_SESSION['username'] = $auth->getUsername();
        $_SESSION['auth'] = $auth;
        $_SESSION['created'] = $auth->getCreated();
        $_SESSION['access']  = $auth->getUserType();
        $_SESSION['client_id'] = $auth->getClientId();
        $_SESSION['user_type'] = $auth->getUserType();
        $_SESSION['api_key'] = $auth->getApiKey();
    }
    
    private function redirectRouteRight(Authentication $auth)
    {
        if ($auth->getUserType() == Authentication::USER_TYPE_ADMIN) {
            return $this->redirect($this->generateUrl('data_acquisition'));
        } else {
            return $this->redirect($this->generateUrl('main_dashboard'));
        }
    }
}