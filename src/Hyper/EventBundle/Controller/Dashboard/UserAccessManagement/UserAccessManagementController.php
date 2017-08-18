<?php

namespace Hyper\EventBundle\Controller\Dashboard\UserAccessManagement;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\JsonResponse,
    Symfony\Component\HttpFoundation\Response,
    Hyper\Domain\Authentication\Authentication,
    Symfony\Component\Form\FormEvents,
    Symfony\Component\Form\FormError,
    Symfony\Component\Validator\Constraints\Email,
    Symfony\Component\Validator\Constraints\Length,
    Doctrine\ORM\EntityRepository,
    Symfony\Component\HttpFoundation\Request,
    Hyper\Domain\OAuth\OAuthClientUserAccess,
    Symfony\Component\HttpKernel\Exception\HttpException,
    Hyper\EventBundle\Event\UserCreateEvent;

class UserAccessManagementController extends Controller
{
    const RECENT_LOGIN_SIZE = 3;
    const LIST_AUTH_SIZE = 10;
    const ACT_PAGINATE = 'paginate';

    public $listCUA;

    public function indexAction(Request $request)
    {

        $authRepo = $this->get('authentication_repository');
        $userId = $request->attributes->get('user_id');
        $user = null;
        if (!empty($userId)) {
            $user = $authRepo->find($userId);
            if (!$user instanceof Authentication) {
                throw new HttpException(Response::HTTP_NOT_FOUND, "User not found.");
            }
            $this->listCUA = $this->getListClientUserAccess($user);
            $formAuthRet = $this->updateFormAuth($request, $user);

        } else {
            $this->listCUA = $this->getListClientUserAccess();
            $formAuthRet = $this->createFormAuth($request);
        }
        $formAuth = $formAuthRet['formAuth'];
        $redirectUrl = $formAuthRet['redirectUrl'];
        if ($redirectUrl) {
            return $this->redirect($redirectUrl);
        }
        $clientRepo = $this->get('client_repository');
        $pageNumber = $request->query->getInt('page', 1);
        $sort = $request->query->get('sort', '');
        $direction = $request->query->get('direction', '');
        $searchterm = $request->query->get('search_term', '');
        $paginateData = $authRepo->getPaginateData(
            $pageNumber,
            self::LIST_AUTH_SIZE,
            $searchterm,
            $sort,
            $direction
        );
        $totalClient = $clientRepo->getTotalClient();
        $listClientIds = $this->getClientIdsInList($paginateData);
        $listClient = $this->getListClient($listClientIds);
        $paginator = $this->get('knp_paginator');
        $paginator = $paginator->paginate(
            array(),
            $pageNumber,
            self::LIST_AUTH_SIZE
        );
        $paginator->setItems($paginateData['rows']);
        $paginator->setTotalItemCount($paginateData['total']);

        if ($request->isXmlHttpRequest()) {
            $act = $request->request->get('act', '');
            if ($act == self::ACT_PAGINATE) {
                $json = array(
                    'status' => 1,
                    'content' => $this->renderView('::user_access_management/_paginate.html.twig', array(
                        'list_auth' => $paginator,
                        'list_client' => $listClient,
                        'total_client'=> $totalClient,
                    ))
                );

                return new JsonResponse($json);
            }
        }

        return $this->render('::user_access_management/index.html.twig', array(
            'list_auth' => $paginator,
            'list_client' => $listClient,
            'total_client'=> $totalClient,
            'form_auth' => $formAuth->createView(),
            'list_cua' => $this->listCUA
        ));
    }

    public function createFormAuth(Request $request)
    {
        $form = $this->get('form.factory')
            ->createNamedBuilder('form_create_auth', 'form', array(
                'mode' => 'create'
            ))
            ->add('name', 'text')
            ->add('email', 'email', array(
                'constraints' => array(
                    new \Symfony\Component\Validator\Constraints\Email()
            )))
            ->add('username', 'text')
            ->add('password', 'password', array(
                'constraints' => array(
                    new \Symfony\Component\Validator\Constraints\Length(array('min'=>6))
            )))
            ->add('retypePassword', 'password', array(
                'constraints' => array(
                    new \Symfony\Component\Validator\Constraints\Length(array('min'=>6))
            )))
            ->add('userType', 'choice', array(
                    'choices' => array(
                    '' => 'Select User Type',
                    Authentication::USER_TYPE_CLIENT => 'Client',
                    Authentication::USER_TYPE_ADMIN => 'Admin',
            )))
            ->add('clientId', 'entity', array(
                'multiple' => true,
                'class' => 'Hyper\Domain\Client\Client',
                'choice_label' => 'client_name'
            ))
            ->add('apiKey', 'text' , array(
                'required' => false
            ))
            ->add('mode', 'hidden', array('read_only' => true));
        $extraData = $request->request->all();
        $form->addEventListener(FormEvents::POST_SUBMIT, function ($event) {
            $data = $event->getData();
            $form = $event->getForm();
            if (null === $data) {
                return;
            }
            $email = $form->get('email')->getData();
            if ($email && $msg = $this->validateContent('email', $email)) {
                $form->get('email')->addError(new FormError($msg));
            }
            $username =  $form->get('username')->getData();
            if ($username && $msg = $this->validateContent('username', $username)) {
                $form->get('username')->addError(new FormError($msg));
            }
        });
        $form = $form->getForm();
        $formReset = clone $form;
        $redirectUrl = '';
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $clients = $form->get('clientId')->getData();
                $clientIds = '';
                if ($clients) {
                    foreach ($clients as $client) {
                        $clientIds[] = $client->getId();
                    }
                }
                if (is_array($clientIds)) {
                    $clientIds = implode(',', $clientIds);
                }
                $authRepo = $this->get('authentication_repository');
                $auth = new Authentication();
                $auth->setName($form->get('name')->getData());
                $auth->setLastLogin(null);
                $auth->setEmail($form->get('email')->getData());
                $auth->setUsername($form->get('username')->getData());
                $auth->setPassword(md5($form->get('password')->getData()));
                $auth->setUserType($form->get('userType')->getData());
                $auth->setClientId($clientIds);
                $auth->setApiKey($form->get('apiKey')->getData());
                $auth->setStatus(1);
                $auth->setCreated(strtotime('now'));
                $authRepo->save($auth);
                $authRepo->completeTransaction();

                $listNewCUA = [];
                foreach ($this->listCUA as $cua) {
                    $oauthClientId = $cua['client_id'];
                    /*
                    if (!empty($extraData['cua']) && in_array($oauthClientId, $extraData['cua'])) {
                        $listNewCUA[$oauthClientId] = 1;
                    } else {
                        $listNewCUA[$oauthClientId] = 0;
                    }*/
                    $listNewCUA[$oauthClientId] = 1;
                }
                $this->createListCUA($listNewCUA, $auth);

                $event = new UserCreateEvent(
                    $this->container
                    , $auth
                );
                $this->get("event_dispatcher")->dispatch(UserCreateEvent::USER_CREATE, $event);

                $this->get('session')->getFlashBag()->add('notice', array(
                    'status' => 'success',
                    'msg' => 'The account has successfully created.'
                ));
                $redirectUrl = $this->generateUrl('dashboard_user_access_management');
            }
        }

        return [
            'redirectUrl' => $redirectUrl,
            'formAuth' => $formReset
        ];
    }

    public function updateFormAuth(Request $request, $user)
    {
        $listClientId = explode(',', $user->getClientId());
        $clientRepo = $this->get('client_repository');
        $listClient = $clientRepo->findBy(array(
            'id' => $listClientId
        ));
        $form = $this->get('form.factory')
            ->createNamedBuilder('form_update_auth', 'form', array(
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'username' => $user->getUsername(),
                'userType' => $user->getUserType(),
                'clientId' => $listClient,
                'apiKey' => $user->getApiKey(),
                'mode' => 'update',

            ))
            ->add('name', 'text')
            ->add('email', 'email', array('read_only' => true))
            ->add('username', 'text', array('read_only' => true))
            ->add('password', 'password', array(
                'constraints' => array(
                    new \Symfony\Component\Validator\Constraints\Length(array('min'=>6))
            )))
            ->add('retypePassword', 'password', array(
                'constraints' => array(
                    new \Symfony\Component\Validator\Constraints\Length(array('min'=>6))
            )))
            ->add('userType', 'choice', array(
                    'choices' => array(
                    '' => 'Select User Type',
                    Authentication::USER_TYPE_CLIENT => 'Client',
                    Authentication::USER_TYPE_ADMIN => 'Admin',
            )))
            ->add('clientId', 'entity', array(
                'multiple' => true,
                'class' => 'Hyper\Domain\Client\Client',
                'choice_label' => 'client_name'
            ))
            ->add('apiKey', 'text' , array(
                'required' => false
            ))
            ->add('mode', 'hidden', array('read_only' => true));
        $form = $form->getForm();
        $extraData = $request->request->all();
        $redirectUrl = '';
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $clients = $form->get('clientId')->getData();
                $clientIds = '';
                if ($clients) {
                    foreach ($clients as $client) {
                        $clientIds[] = $client->getId();
                    }
                }
                if (is_array($clientIds)) {
                    $clientIds = implode(',', $clientIds);
                }
                $user->setName($form->get('name')->getData());
                $user->setEmail($form->get('email')->getData());
                $user->setUsername($form->get('username')->getData());
                if ($form->get('password')->getData()) {
                    $user->setPassword(md5($form->get('password')->getData()));
                }
                $user->setUserType($form->get('userType')->getData());
                $user->setClientId($clientIds);
                $user->setApiKey($form->get('apiKey')->getData());
                $authRepo = $this->get('authentication_repository');
                $authRepo->save($user);
                $authRepo->completeTransaction();

                $listNewCUA = [];
                foreach ($this->listCUA as $cua) {
                    $oauthClientId = $cua['client_id'];
                    /*
                    if (!empty($extraData['cua']) && in_array($oauthClientId, $extraData['cua'])) {
                        $listNewCUA[$oauthClientId] = 1;
                    } else {
                        $listNewCUA[$oauthClientId] = 0;
                    }*/
                     $listNewCUA[$oauthClientId] = 1;
                }
                $this->createListCUA($listNewCUA, $user);

                $this->get('session')->getFlashBag()->add('notice', array(
                    'status' => 'success',
                    'msg' => 'The account has successfully updated.'
                ));
                $redirectUrl = $this->generateUrl('dashboard_user_access_management_update', [
                    'user_id' => $user->getId()
                ]);
            }
        }

        return [
            'redirectUrl' => $redirectUrl,
            'formAuth' => $form
        ];
    }

    private function getClientIdsInList($paginateData)
    {
        $clientIds = array();
        if ($paginateData['rows']) {
            foreach ($paginateData['rows'] as $row) {
                if ($row['clientId']) {
                    $clientIdsArr = explode(',', $row['clientId']);
                    foreach ($clientIdsArr as $clientId) {
                        $clientIds[] = $clientId;
                    }
                }

            }
        }
        if (!empty($clientIds)) {
            $clientIds = array_unique($clientIds);
        }

        return $clientIds;
    }

    public function getListClient($listClientIds)
    {
        $clientRepo = $this->get('client_repository');
        $listClient = $clientRepo->getListClientByIds($listClientIds);

        return $this->parseListClient($listClient);
    }

    /**
     * Parse Client to format:
     *  array(
     *  '566f78e1697177.59317768' => 'Liputan6',
     *  ...
     * )
     **/
    public function parseListClient($listClient)
    {
        $list = array();
        if ($listClient) {
            foreach ($listClient as $client) {
                $list[$client['id']] = $client['clientName'];
            }
        }

        return $list;
    }

    public function renderRecentLoginAction(Request $request)
    {
        $ulhRepo = $this->get('user_login_history_repository');
        $listRecentLogin = $ulhRepo->getListRecentLogin(self::RECENT_LOGIN_SIZE);

        return $this->render('::user_access_management/_recent_login.html.twig', array(
            'list_recent_login' => $listRecentLogin
        ));
    }

    public function deleteAction(Request $request)
    {
        $json = array(
            'status' => 0
        );
        $authId = $request->request->get('auth_id');
        if($authId) {
            $authRepo = $this->get('authentication_repository');
            $deleted = $authRepo->deleteAuth($authId);
            if ($deleted) {
                $json['status'] = 1;
            }
        }

        return new JsonResponse($json);
    }

    public function validateAction(Request $request)
    {
        $json = array(
            'msg' => ''
        );
        $field = $request->query->get('field', '');
        $value = $request->query->get('value', '');
        if ($value && $field) {
            $json['msg'] = $this->validateContent($field, $value);
        }

        return new JsonResponse($json);
    }

    private function validateContent($field, $value)
    {
        $msg  = '';
        $authRepo = $this->get('authentication_repository');
        $authFound = $authRepo->findbyCriteria($field, $value);
        if ($authFound instanceof Authentication) {
            if ($field == 'username') {
                $msg = 'An account already exists for this username.';
            } else if ($field == 'email') {
               $msg = 'An account already exists for this email address.';
            }
        }

        return $msg;
    }

    /**
     * Get list oauth clients
     */
    public function getListClientUserAccess($user = null)
    {
        $rsOutput = [];
        $oauthClientRepo = $this->get('oauth_client_repository');
        $oauthClients = $oauthClientRepo->findAll();
        foreach ($oauthClients as $oauthClient) {
            $rsOutput[] = [
                   'name' => $oauthClient->getName(),
                   'client_id' => $oauthClient->getId()
               ];
        }
        if (null == $user) {
            return $rsOutput;
        }

        $username = $user->getUsername();
        $userType = OAuthClientUserAccess::USER_TYPE_AK;
        $clientUserAccess = $this->getClientUserAccessByUser($username, $userType);
        if (empty($clientUserAccess)) {
            return $rsOutput;
        }
        foreach ($rsOutput as $index => $oauthClient) {
            foreach ($clientUserAccess as $clientUser) {
                if (
                    $oauthClient['client_id'] == $clientUser->getClient()->getId() &&
                    $clientUser->getStatus() == 1
                ) {
                    $allowBot = 1;
                } else {
                    $allowBot = 0;
                }
                $rsOutput[$index]['allow'] = $allowBot;
            }
        }
        return $rsOutput;
    }

    private function getClientUserAccessByUser($username, $userType)
    {
        $clientUserAccessRepo = $this->get('oauth_client_user_access_repository');
        return $clientUserAccessRepo->findBy(['username'=>$username, 'userType'=>$userType]);
    }

    private function createListCUA($listCUA, Authentication $auth)
    {
        $userType = OAuthClientUserAccess::USER_TYPE_AK;
        $cuaRepo = $this->get('oauth_client_user_access_repository');
        if (!empty($listCUA)) {
            foreach ($listCUA as $cua => $status) {
                $oauthClientObj = $this->get('oauth_client_repository')
                          ->find($cua);
                $cuaObj = $cuaRepo->findOneBy([
                    'username' => $auth->getUsername(),
                    'userType' => $userType,
                    'client'=> $oauthClientObj
                ]);
                if ($cuaObj && $cuaObj->getId()) {
                    $cuaObj->setStatus($status);
                } else {
                    $cuaObj = new OAuthClientUserAccess();
                    $cuaObj->setUsername($auth->getUsername());
                    $cuaObj->setUserType($userType);
                    $cuaObj->setStatus($status);
                    $cuaObj->setClient($oauthClientObj);
                }
                $cuaRepo->save($cuaObj);
                $cuaRepo->completeTransaction();
            }
        }
    }

}