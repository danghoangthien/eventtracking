<?php
namespace Hyper\EventBundle\Controller\Dashboard\Auth;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Hyper\EventBundle\Service\EventProcess;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Hyper\EventBundle\Event\UserLoginHistoryEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

use Hyper\Domain\Filter\Filter;
use Hyper\Domain\Action\Action;
use Hyper\Domain\Client\Client;
use Hyper\Domain\Authentication\Authentication;
use Hyper\Domain\OAuth\OAuthClientUserAccess;

use Hyper\EventBundle\Service\Cached\AnalyticMetadata\CountDeviceByAppTitleCached;

class AuthenticationController extends Controller
{
    const AUTH_STATUS_LOGOUT = '-1';
    const USER_PROVIDER_KEY = 'user_secured_area';
    /**
    * @param ContainerInterface $container
    */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    /*
     * @Desc Controller to check login credentials
     * @date 2015-09-08
     * @url  /validate_login
     */

    /* /dashboard/auth/index */
    public function indexAction()
    {
        //$authRepo = $this->container->get('authentication_repository');
        //$records  = $authRepo->displayApplication();
        $conn = $this->get('doctrine.dbal.pgsql_connection');
        $sql  = $conn->prepare("SELECT DISTINCT app_id, app_name FROM applications;");
        $sql->execute();

        $records = array();

        for($i = 0; $row = $sql->fetch(); $i++)
        {
            $records[] = $row;
        }

        //return new Response(json_encode(array("records" => \Doctrine\Common\Util\Debug::dump($records))));
        return $this->render('authentication/index.html.twig', array('record' => $records));
//        return $this->render('authentication/index.html.twig');
    }

    /* /dashboard/auth/login */
    public function loginAction()
    {
        if(isset($_SESSION['authentication_id']))
        {
            return $this->render('authentication/login.html.twig',array("user" => $_SESSION['authentication_id']));
        }
        else
        {
            return $this->render('authentication/login.html.twig');
        }
    }

    /* /dashboard/auth/change_password */
    public function changePasswordAction()
    {
        return $this->render('authentication/change_password.html.twig');
    }

    /* /dashboard/auth/validate_login */
    public function validateLoginAction()
    {
        if(session_id() == '' || !isset($_SESSION))
        {
            session_start();
        }
        else
        {
            session_destroy();
            session_start();
        }

        $request = $this->getRequest();

        $this->username  = $request->request->get('username');
        $this->password  = $request->request->get('password');
        $this->access    = $request->request->get('access_type');

        $authRepo = $this->container->get('authentication_repository');
        $authFindByUsername = $authRepo->findOneBy(['username' => $this->username]);
        $records = '';
        if ($authFindByUsername instanceof Authentication) {
            $factory = $this->container->get('security.encoder_factory');
            $encoder = $factory->getEncoder($authFindByUsername);
            $isValidPassword = $encoder->isPasswordValid($authFindByUsername->getPassword(), $this->password, $authFindByUsername->getSalt());
            if ($isValidPassword) {
                $records = $authFindByUsername;
            }
        }
        $count = count($records);

        //echo(json_encode(array("status" => $records->getUserType() . "_" . $this->access, "error" => ""))); die;

        if( $records instanceof Authentication)
        {
            $isActive = $records->getStatus();
            if (!$isActive)
            {
                return new Response(json_encode(array("status" => "failed", "error" => "account is disabled")));
            }

            if($records->getUserType() == 1000 && ($this->access == 1 || $this->access == 2)
             || $records->getUserType() == 1 && $this->access == 2
             || $records->getUserType() == 2 && ($this->access == 0 || $this->access == 2)
            )
            {
                $event = new UserLoginHistoryEvent($this->container, $records);
                $dispatcher = new EventDispatcher();
                $dispatcher->dispatch(UserLoginHistoryEvent::USER_LOGIN_HISTORY_LOGINED);
                $this->id = session_id();
                //$_SESSION['authentication_id'] = $this->username;
                $_SESSION['authentication_id'] = $records->getId();
                $_SESSION['username'] = $records->getUsername();
                $_SESSION['auth'] = $records;
                $_SESSION['created'] = $records->getCreated();
                $_SESSION['access']  = $this->access;
                $_SESSION['client_id'] = $records->getClientId();
                $_SESSION['user_type'] = $records->getUserType();
                $_SESSION['api_key'] = $records->getApiKey();
                // Implement auth of symfony2
                $rolesName = array();
                if ($records->getUserType() == Authentication::USER_TYPE_ADMIN) {
                    $rolesName[] = 'ROLE_AK_ADMIN';
                } else {
                    $rolesName[] = 'ROLE_AK_CLIENT';
                }
                // Implement auth of symfony2
                $token = new UsernamePasswordToken(
                    $records,
                    null,
                    self::USER_PROVIDER_KEY,
                    $rolesName
                );
                $session = $this->get('session');
                $session->set('_security_' . self::USER_PROVIDER_KEY, serialize($token));
                $session->save();
                $this->get('security.context')->setToken($token);
                //now dispatch the login event
                $request = $this->get('request');
                $event = new InteractiveLoginEvent($request, $token);
                $this->get("event_dispatcher")->dispatch("security.interactive_login", $event);
                return new Response(json_encode(array("status" => "success","session_id" => $this->id, "username" => $_SESSION['authentication_id'], "created" => $_SESSION['created'])));
            }

            if($this->access != $records->getUserType())
            {
                return new Response(json_encode(array("status" => "failed", "error" => "Access denied. Your account is not allowed to access this site.")));
            }
            else
            {
                $event = new UserLoginHistoryEvent($this->container, $records);
                $dispatcher = new EventDispatcher();
                $dispatcher->dispatch(UserLoginHistoryEvent::USER_LOGIN_HISTORY_LOGINED);
                $this->id = session_id();
                //$_SESSION['authentication_id'] = $this->username;
                $_SESSION['authentication_id'] = $records->getId();
                $_SESSION['username'] = $records->getUsername();
                $_SESSION['auth'] = $records;
                $_SESSION['created'] = $records->getCreated();
                $_SESSION['access']  = $this->access;
                $_SESSION['client_id'] = $records->getClientId();
                $_SESSION['user_type'] = $records->getUserType();
                $_SESSION['api_key'] = $records->getApiKey();
                $_SESSION['client_info'] = null;
                // Implement auth of symfony2
                $rolesName = array();
                if ($records->getUserType() == Authentication::USER_TYPE_ADMIN) {
                    $rolesName[] = 'ROLE_AK_ADMIN';
                } else {
                    $rolesName[] = 'ROLE_AK_CLIENT';
                    $countDeviceByAppTitleCached = new CountDeviceByAppTitleCached($this->container);
                    //var_dump($_SESSION['client_id']);
                    $countDeviceByAppTitleCachedData = $countDeviceByAppTitleCached->hget($_SESSION['client_id']);
                    $countDeviceByAppTitleCachedData = json_decode($countDeviceByAppTitleCachedData,true);
                    $listAppId = [];
                    foreach ($countDeviceByAppTitleCachedData as $appTitleData) {
                        foreach( $appTitleData as $appTitle ){
                            $listAppId[]=$appTitle['app_id'];
                        }
                    }
                    $_SESSION['client_data']['listAppId']=$listAppId;
                }
                // Implement auth of symfony2
                $token = new UsernamePasswordToken(
                    $records,
                    null,
                    self::USER_PROVIDER_KEY,
                    $rolesName
                );
                $session = $this->get('session');
                $session->set('_security_' . self::USER_PROVIDER_KEY, serialize($token));
                $session->save();
                $this->get('security.context')->setToken($token);
                //now dispatch the login event
                $request = $this->get('request');
                $event = new InteractiveLoginEvent($request, $token);
                $this->get("event_dispatcher")->dispatch("security.interactive_login", $event);
                //var_dump($_SESSION['client_id']);
                //var_dump($_SESSION['client_info']);die;
                return new Response(json_encode(array("status" => "success","session_id" => $this->id, "username" => $_SESSION['authentication_id'], "created" => $_SESSION['created'])));
                //$isActive = $records->getStatus();

                // if (!$isActive){
                //     return new Response(json_encode(array("status" => "failed", "error" => "account is disabled")));
                // }
            }
        }
        else
        {
            return new Response(json_encode(array("status" => "failed", "error" => "Invalid login credentials")));
        }
    }

    /* /dashboard/auth/logout */
    public function logoutAction()
    {
        // session_destroy();
        $this->id = null;
        $_SESSION['username'] = null;
        $_SESSION['authentication_id'] = null;
        $_SESSION['auth'] = null;
        $session = $this->get('session');
        $auth = $this->get('security.context')->getToken()->getUser();
        if (in_array(Authentication::ROLE_ADMIN, $auth->getRoles())) {
            $urlPath = $this->generateUrl('dashboard_admin_login');
        } else {
            $urlPath = $this->generateUrl('dashboard_client_login');
        }

        $session->remove('_security_'. self::USER_PROVIDER_KEY);
        $sessionId = $session->getId();
        $sessionKey = 'show_tutorial_' . $sessionId;
        $session->remove($sessionKey);
        $this->get('security.context')->setToken(null);

        return $this->redirect($urlPath);
        //return new Response(json_encode(array("id" => $this->id, "session" => $_SESSION['username'],"status"=>self::AUTH_STATUS_LOGOUT)));
    }

    /* /dashboard/auth/validate_change_password */
    public function validateChangePasswordAction()
    {
        $request = $this->getRequest();

        $this->username  = $_SESSION['username'];
        $this->password  = $request->request->get('password');
        $this->new_pass  = $request->request->get('new_pass');
        $this->confirm_pass  = $request->request->get('confirm_pass');

        if( $this->new_pass != $this->confirm_pass )
        {
            return new Response(json_encode(array("status" => "failed", "error" => "New password and Confirm password did not match")));
        }

        $authRepo = $this->container->get('authentication_repository');
        $records  = $authRepo->updatePassword("$this->username", md5("$this->password"), "$this->new_pass");
        //$count = count($records);
        if( $records == "success" )
        {
            return new Response(json_encode(array("status" => "success")));
        }
        else
        {
            return new Response(json_encode(array("status" => "failed", "error"=>"Invalid password")));
        }
    }

    /* fetch_record //for testing */
    public function fetchAction()
    {
        //return $this->render('HyperEventBundle:Authentication:index.html.twig');

        $authRepo = $this->container->get('authentication_repository');
        //$records  = $authRepo->retrieveRecords();
        $records   = $authRepo->findbyCriteria('email', 'erdan08@yahoo.com');

        $count = count($records);

        return new Response(json_encode(array("records" => $count)));
        //return new Response(json_encode(array("records" => \Doctrine\Common\Util\Debug::dump($records))));
    }

    function cleanValues($var)
    {
        if(!preg_match('/[^a-zA-Z0-9._\-\s]/', html_entity_decode($var)))
        {
            return $var;
        }
        else
        {
            return "invalid";
        }

        //return preg_replace('/[^a-zA-Z0-9.\s]/', '', strip_tags(html_entity_decode($var)));
    }

    function cleanValuesEmail($var)
    {
        if(!preg_match('/[^a-zA-Z0-9.@_\-\s]/', html_entity_decode($var)))
        {
            return $var;
        }
        else
        {
            return "invalid";
        }

        //return preg_replace('/[^a-zA-Z0-9.@_\s]/', '', strip_tags(html_entity_decode($var)));
    }

    function cleanValuesAppId($var)
    {
        if(!preg_match('/[^a-zA-Z0-9,.\-\s]/', html_entity_decode($var)))
        {
            return $var;
        }
        else
        {
            return "invalid";
        }

        //return preg_replace('/[^a-zA-Z0-9,.\-\s]/', '', strip_tags(html_entity_decode($var)));
    }

    /* /dashboard/auth/save_auth */
    public function saveAuthAction()
    {
        $this->message = "";
        $this->date = strtotime(date('Y-m-d h:i:s'));

        $request = $this->getRequest();

        $this->username  = $this->cleanValues($request->request->get('username'));
        $this->username  = strtolower($this->username);

        $this->name      = $request->request->get('name');
        $this->password  = $request->request->get('password');
        $this->email     = $this->cleanValuesEmail($request->request->get('email'));
        $this->client_id    = $this->cleanValuesAppId(str_replace(' ', '',$request->request->get('app_id')));
        $this->userType  = $this->cleanValues($request->request->get('user_type'));
        $this->status    = 1;
        $this->created   = $this->date;
        $this->updated   = $this->date;
        $this->clover    = $request->request->get('is_clover');
        $this->api_key   = $request->request->get('api_key');

        if($this->clover == "true" && $this->userType == 0)
        {
            $this->userType = 2;
        }

        if($this->clover == "true" && $this->userType == 1)
        {
            $this->userType = 1000;
        }

        $all_app = array();

        if($this->client_id == "777")
        {
            $conn = $this->get('doctrine.dbal.pgsql_connection');
            $dql  = $conn->prepare("SELECT DISTINCT id FROM client;");
            $dql->execute();

            $display = "";

            for($xx = 0; $q = $dql->fetch(); $xx++)
            {
                $all_app[] = $q['id'];
            }

            $this->client_id = implode(",", $all_app);
        }

        $authRepo = $this->container->get('authentication_repository');
        $records  = $authRepo->findbyCriteria('email', "$this->email");
        $count = count($records);

        $checkEmail = $authRepo->findbyCriteria('username', "$this->username");
        $cnt_user   = count($checkEmail);

        if (
            "invalid" == $this->username
            || "invalid" == $this->password
            || "invalid" == $this->email
            || filter_var($request->request->get('email'),FILTER_VALIDATE_EMAIL) === false
            || ("invalid" == $this->client_id && 0 == $this->userType)
        )
        {
            return new Response(json_encode(array("message" => "Invalid data sent")));
        }
        else if($cnt_user > 0 || $cnt_user != 0)
        {
            return new Response(json_encode(array("message" => "Username already used!")));
        }
        else if( $count > 0 || $count != 0 )
        {
            return new Response(json_encode(array("message" => "Email given already exists!")));
        }
        else
        {
            try
            {
                $auth = new Authentication();
                $auth->setUsername($this->username);
                $auth->setName($this->name);
                $auth->setPassword(md5($this->password));
                $auth->setEmail($this->email);
                $auth->setClientId($this->client_id);
                $auth->setUserType($this->userType);
                $auth->setApiKey($this->api_key);
                $auth->setStatus($this->status);
                $auth->setCreated($this->created);
                $auth->setUpdated($this->updated);

                $authRepo->save($auth);
                if($this->userType == Authentication::USER_TYPE_CLIENT) {
                    $this->saveDefaultPresets($auth);
                }

                $authRepo->completeTransaction();

                return new Response(json_encode(array("message" => "User successfully saved")));
            }
            catch (Exception $exc)
            {
                echo $exc->getTraceAsString();
            }
        }
    }

    /* check_session */
    public function checkSessionAction()
    {
        $this->message = "";
        $this->date = date('Ymdhis');

        $request = $this->getRequest();

        $this->username  = strtolower($request->request->get('username'));
        $this->password  = $request->request->get('password');
        $this->email     = $request->request->get('email');
        $this->app_id    = $request->request->get('app_id');
        $this->created   = $this->date;
        $this->updated   = $this->date;

        return new Response(json_encode(array("username"=>$this->username, "password"=>md5($this->password),
            "email"=>$this->email,"app_id"=>$this->app_id,"created"=>$this->created,"updated"=>$this->updated)));

//        $this->path = session_save_path();
//        return new Response(json_encode(array("Session" => $this->path)));
    }

    public function getLoggedAuthenticationId() {
        $auth = $this->get('security.context')->getToken()->getUser();
        if ($auth instanceof Authentication) {
            return $auth->getId();
        }
        return null;
    }
    public function getLoggedAuthenticationUsername() {
        $auth = $this->get('security.context')->getToken()->getUser();
        if ($auth instanceof Authentication) {
            return $auth->getUsername();
        }
        return null;
    }

    public function getLoggedAuthentication() {
        $auth = $this->get('security.context')->getToken()->getUser();
        if ($auth instanceof Authentication) {
            return $auth;
        }
        return null;
    }

    public function getAccess() {
        $auth = $this->get('security.context')->getToken()->getUser();
        if ($auth instanceof Authentication) {
            return $auth->getAccess();
        }
        throw new \lib\Exception\InvalidAuthenticationException("this module require login");
    }

    public function getClientIds()
    {
        $auth = $this->get('security.context')->getToken()->getUser();
        if ($auth instanceof Authentication) {
            return $auth->getClientId();
        }
        throw new \lib\Exception\InvalidAuthenticationException("this module require login");
    }

    public function getLoggedUserType() {
        $auth = $this->get('security.context')->getToken()->getUser();
        if ($auth instanceof Authentication) {
            return $auth->getUserType();
        }
    }

    public function getLoggedApiKey() {
        $auth = $this->get('security.context')->getToken()->getUser();
        if ($auth instanceof Authentication) {
            return $auth->getApiKey();
        }
        return null;
    }

    public function refreshClient()
    {
        // findUser
        $logged = $this->getLoggedAuthentication();
        $username = $logged->getUsername();
        $email    = $logged->getEmail();

        if(($username == "" || $username == null) || ($email == "" || $email == null) )
        {
            return $this->render('authentication/index_user.html.twig');
        }

        $auth = $this->container->get('authentication_repository');

        $result = $auth->findUser("$username","$email");

        return $result->getClientId();
        /*
        $username = $this->getLoggedAuthenticationUsername();

        if($username == "" || $username == null)
        {
            return $this->render('authentication/index_user.html.twig');
        }

        $auth = $this->container->get('authentication_repository');

        $result = $auth->findbyCriteria("username", $username);

        return $result->getClientId();
        */
    }


    /* dashboard/auth/account_listing */
    public function showAccountListingAction()
    {
        $authRepo = $this->container->get('authentication_repository');
        //$records  = $authRepo->displayListingAction();
        $records  = $authRepo->retrieveRecords();

//        $count = count($records);
//
//        for($i = 0; $i < $count; $i++)
//        {
//            if($records[$i]->getCreated() > 99991231)
//            {
//                $epoch = $records[$i]->getCreated();
//                $display = new DateTime("@$epoch");
//                $records[$i]->getCreated() = $display->format('Y-m-d H:i:s');
//            }
//        }

        //$date     = $records[0]->getCreated();

        //return new Response(json_encode(array("records" => \Doctrine\Common\Util\Debug::dump($date))));

        return $this->render('authentication/account_management.html.twig',
            array('list' => $records));
    }

    /* dashboard/auth/show_app_account */
    public function showAppAccountAction()
    {
        $request = $this->getRequest();
        $this->id = $request->query->get('id');
        $this->client = $request->query->get('client_name');

        $authRepo = $this->container->get('authentication_repository');
        $record   = $authRepo->findbyCriteria('id', $this->id);
//        $app_id   = $record->getApplicationId();
//        $app_ids  = explode(",", $app_id);
//        $app_ids  = "'" .implode("','", $app_ids) ."'";

        $conn = $this->get('doctrine.dbal.pgsql_connection');
        $sql  = $conn->prepare("SELECT DISTINCT id, client_name FROM client WHERE client_name = '$this->client';");
        $sql->execute();

        $rs = array();

        for($i = 0; $row = $sql->fetch(); $i++)
        {
            $rs[] = $row;
        }

        /* GET LIST OF APPLICATIONS */
        $sql2  = $conn->prepare("SELECT DISTINCT id, client_name FROM client ORDER BY created;");
        $sql2->execute();

        $data = array();

        for($x = 0; $rows = $sql2->fetch(); $x++)
        {
            $data[] = $rows;
        }
        $this->listCUA = $this->getListClientUserAccess($record);



        //return new Response(json_encode(array("records" => \Doctrine\Common\Util\Debug::dump($app_ids))));
        return $this->render('authentication/edit_account.html.twig',
            array('record' => $record, "app_name" => $rs, "apps" => $data, 'active' => 'user_access', 'list_cua' => $this->listCUA));
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
                    $cuaObj->setClient($clientObj);
                }
                $cuaRepo->save($cuaObj);
                $cuaRepo->completeTransaction();
            }
        }
    }

    /* dashboard/auth/update_user_app */
    public function updateUserAppAction(Request $request)
    {
        $this->username  = $request->request->get('username');
        $this->password  = $request->request->get('password');
        $this->name      = $request->request->get('name');
        $this->email     = $request->request->get('email');

        $this->client_id = "";
        $this->user_id   = $request->request->get('user_id');
        $this->userType  = $request->request->get('user_type');
        $this->host      = $this->getRequest()->getHost();
        $this->url       = $this->generateUrl('dashboard_show_App_Account');
        $this->clover    = $request->request->get('is_clover');
        $this->api_key   = $request->request->get('api_key');

        //$routeName = $request->get('_route');
        if($request->request->get('client_name') != "")
        {
            $this->client_id = $request->request->get('client_name');
        }

        if($this->clover == "true" && $this->userType == 0)
        {
            $this->userType = 2;
        }

        if($this->clover == "true" && $this->userType == 1)
        {
            $this->userType = 1000;
        }

        $ext  = "";
        $valid = false;
        $this->validExt  = array("png", "jpg", "jpeg", "gif", "JPEG", "JPG", "PNG", "GIF");

        $fileName = $_FILES['csv']['name'];
        $fileSize = (int)$_FILES['csv']['size'] / 1048576;
        $fileType = $_FILES['csv']['type'];
        $orgImage = $_FILES['csv']['tmp_name'];

        // $fileName = $request->request->get('fileName');
        // $fileSize = (int)$request->request->get('size')/ 1048576;
        // $fileType = $request->request->get('type');
        // $orgImage = $request->request->get('image');

        if($fileName != "")
        {
            $fileName = explode(".",$fileName);
            $fileName = $this->username.".".$fileName[1];

            $this->extension = explode("/", $fileType);

            if (!in_array($this->extension[1], $this->validExt))
            {
                $valid = false;
            }
            else
            {
                $ext   = $this->extension[1];
                $valid = true;
            }
        }

        $target = "/tmp/";
        $target = $target . basename( $fileName);

        if($this->username == "")
        {
            //return new Response(json_encode(array("status" => "invalid")));
            $url = $this->url.'?id='.$this->user_id.'&client_name='. $this->client_name.'&msg=Error occurred';

            return $this->redirect($url, 301);
        }
        else
        {
            if($valid == true)
            {
                if(move_uploaded_file($orgImage, $target))
                {
                    $aksrcBucket = $this->container->getParameter('amazon_s3_aksrc');
                    $this->uploadFromLocal(new File($target), $aksrcBucket['bucket_name'], $aksrcBucket['folder']['user_images'], $fileType);

                    $fs = new Filesystem();
                    $fs->remove($target);
                }
                else
                {
                    // = $this->url.'?id='.$this->user_id.'&client_name='.$this->client_id.'&msg=Failed to upload file to EC2';

                    //return $this->redirect($url, 301);
                }
            }
            else
            {
                //$url = $this->url.'?id='.$this->user_id.'&client_name='.$this->client_id.'&msg=Invalid image file';

                //return $this->redirect($url, 301);
            }

            $all_app = array();

            if($this->client_id == "777")
            {
                $conn = $this->get('doctrine.dbal.pgsql_connection');
                $dql  = $conn->prepare("SELECT DISTINCT id FROM client;");
                $dql->execute();

                $display = "";

                for($xx = 0; $q = $dql->fetch(); $xx++)
                {
                    $all_app[] = $q['id'];
                }

                $this->client_id = implode(",", $all_app);
            }

            //return new Response(\Doctrine\Common\Util\Debug::dump($new_id));

            $authRepo = $this->container->get('authentication_repository');
            $user     = $authRepo->updatePasswordAppId("$this->username", "$this->name", "$fileName","$this->password", "$this->client_id", "$this->email","$this->userType", "$this->api_key");

            //return new Response(\Doctrine\Common\Util\Debug::dump($user));

            if($user == "email_used")
            {
                $url = $this->url.'?id='.$this->user_id.'&client_name='.$this->client_id.'&msg=Cannot update email. Email already used.';

                return $this->redirect($url, 301);
            }
            else if($user == "success")
            {
                $extraData = $request->request->all();
                $auth = new Authentication();
                $auth->setUsername($this->username);
                $this->listCUA   = $this->getListClientUserAccess($auth);
                $listNewCUA = [];
                foreach ($this->listCUA as $cua) {
                    $oauthClientId = $cua['client_id'];
                    if (!empty($extraData['cua']) && in_array($oauthClientId, $extraData['cua'])) {
                        $listNewCUA[$oauthClientId] = 1;
                    } else {
                        $listNewCUA[$oauthClientId] = 0;
                    }
                }
                $this->createListCUA($listNewCUA, $auth);

                $url = $this->url.'?id='.$this->user_id.'&client_name='.$this->client_id.'&msg=User account has been updated';

                return $this->redirect($url, 301);
                // return $this->render('authentication/edit_account.html.twig',
                //     array('status' => "success", "message" => "success"));
            }
            else
            {
                $url = $this->url.'?id='.$this->user_id.'&client_name='.$this->client_id.'&msg=Error saving data.';

                return $this->redirect($url, 301);

                // return $this->render('authentication/edit_account.html.twig',
                //     array('status' => "failed", "message" => "failed"));
            }
        }
    }

    /* /dashboard/auth/render_lte */
    public function renderLteAction()
    {
        $clientRepo = $this->container->get('client_repository');
        $clientids  = $clientRepo->getClientAppsByIds(array('5625c241709703.38985473','562048bacf7d40.91349663'));

        print_r($clientids); die;

        return $this->render('authentication/test.html.twig');
    }

    /* /dashboard/auth/accounts/{page} */
    public function showUsersAction(Request $request)
    {
        // $request = $this->getRequest();
        $auth = $this->container->get('authentication_repository');
        $page = $request->get('page');
        $dataPerPage = 10;

        $result = $auth->getResultAndCount($page,$dataPerPage);
        $rows = $result['rows'];
        $totalCount = $result['total'];

        $paginator = new \lib\Paginator($page, $totalCount, $dataPerPage);
        $pageList = $paginator->getPagesList();

        //return new Response(json_encode(array("records" => \Doctrine\Common\Util\Debug::dump($rows))));
        return $this->render('authentication/account_management.html.twig',
            array(
                'list' => $rows,
                'paginator' => $pageList,
                'cur' => $page,
                'total' => $paginator->getTotalPages() -1,
                'per' => $dataPerPage
                )
        );
    }

    /* dashboard/auth/admin_login */
    public function renderAdminLoginAction()
    {
        $ssesion = $this->container->get('session');
        $targetPath = $ssesion->get('_security.user_secured_area.target_path');
        if(isset($_SESSION['authentication_id']))
        {
            return $this->render('authentication/index_admin.html.twig',array("user" => $_SESSION['authentication_id'], 'target_path' => $targetPath));
        }
        else
        {
            return $this->render('authentication/index_admin.html.twig', ['target_path' => $targetPath]);
        }
    }

    /* dashboard/auth/login */
    public function renderClientLoginAction(Request $request)
    {
        $ssesion = $this->container->get('session');
        $targetPath = $ssesion->get('_security.user_secured_area.target_path');
        if(isset($_SESSION['authentication_id']))
        {
            return $this->render('authentication/index_user.html.twig',array("user" => $_SESSION['authentication_id'], 'target_path' => $targetPath));
        }
        else
        {
            return $this->render('authentication/index_user.html.twig', ['target_path' => $targetPath]);
        }
    }

    /* /dashboard/access/user_access/{page} */
    public function renderUserAccessAction()
    {
        if(isset($_SESSION['authentication_id']))
        {
            /* ADDED TO REDIRECT TO LOGIN IF THERE IS NO SESSION OR LOGGED USER IS CLIENT paul.francisco 2015-12-18 */
            $authRepo  = $this->container->get('auth.controller');
            $authIdFromSession = $authRepo->getLoggedAuthenticationId();
            $user_type = $authRepo->getLoggedUserType();

            /* 0 = client access, 2 = client access + clover access */
            if($authIdFromSession == null || $user_type == 0 || $user_type == 2)
            {
                $this->url = $this->generateUrl('dashboard_logout');
                return $this->redirect($this->url, 301);
            }

            $request = $this->getRequest();
            $conn = $this->get('doctrine.dbal.pgsql_connection');

            $sql  = $conn->prepare("SELECT DISTINCT id, client_name FROM client;");
            $sql->execute();

            $data = array();

            for($x = 0; $row = $sql->fetch(); $x++)
            {
                $data[] = $row;
            }

            $auth = $this->container->get('authentication_repository');
            $page = $request->get('page');
            $dataPerPage = 10;

            $result = $auth->getResultAndCount($page,$dataPerPage);
            $rows = $result['rows'];
            $totalCount = $result['total'];

            $paginator = new \lib\Paginator($page, $totalCount, $dataPerPage);
            $pageList = $paginator->getPagesList();

            $cnt = count($rows);
            $dnt = count($data);
            $app_name = array();
            $test = "";
            $display = array();
            $client_name="";

            for($i = 0; $i < $cnt; $i++)
            {
                $client_id = $rows[$i]->getClientId();
                $app_ids  = explode(",", $rows[$i]->getClientId());
                $app_ids  = "'" .implode("','", $app_ids) ."'";
                $dql  = $conn->prepare("SELECT DISTINCT id, client_name FROM client WHERE id IN ($app_ids);");
                $dql->execute();

                $d = array();

                for($xx = 0; $q = $dql->fetch(); $xx++)
                {
                    $d[] = $q['client_name'];
                }

                $xnt = count($d);

                if($dnt == $xnt)
                {
                    $rows[$i]->setClientId("All Clients");
                }
                else
                {
                    $app_name  = implode(",", $d);
                    $rows[$i]->setClientId($app_name);
                }
            }

            /* ADD ANOTHER QUERY HERE IF CLIENT CAN HAVE ACCESS IN EDIT USER, TO DISPLAY ONLY AVAILABLE CLIENTS FOR THEM
            *  2015-12-08 paul.francisco
            */

            return $this->render('access_management/user_access.html.twig',
                    array("user" => $_SESSION['authentication_id'],
                          "apps" => $data,
                          'list' => $rows,
                          'paginator' => $pageList,
                          'cur' => $page,
                          'total' => $paginator->getTotalPages() -1,
                          'per' => $dataPerPage,
                          'active' => 'user_access'));
        }
        else
        {
            $this->url = $this->generateUrl('dashboard_logout');
            return $this->redirect($this->url, 301);
            //return $this->render('access_management/user_access.html.twig');
        }
    }

    /* /dashboard/access/delete_user */
    public function deleteAction()
    {
        $request = $this->getRequest();
        $this->id = $request->query->get('id');

        if(null != $this->id && "" != $this->id)
        {
            $auth = $this->container->get('authentication_repository');
            $delete = $auth->deleteUser($this->id);

            if($delete == "success")
            {
                return $this->render('access_management/user_access.html.twig',array("status" => "success"));
            }
            else
            {
                return $this->render('access_management/user_access.html.twig',array("status" => "failed"));
            }
        }
        else
        {
            return $this->render('access_management/user_access.html.twig',array("status" => "null"));
        }
    }

    /* test for active page only */
    public function testClientAction()
    {
        return $this->render('authentication/test.html.twig',array("active" => "test"));
    }

    public function getLoggedAuthenticationCreated() {
        if(isset($_SESSION['authentication_id'])) {
            return $_SESSION['auth'];
        } else {
            throw new \lib\Exception\InvalidAuthenticationException("this module require login");
        }
    }

    /* From eventLogUploader. Add some validations on extension
       Date: 2015-10-31
       Paul
       /dashboard/user/upload_image
    */
    public function uploadFromLocal(File $file, $bucket_name, $folder_name, $content_type)
    {
        $region = $this->container->getParameter('amazon_s3_region');
        //$bucket = $this->container->getParameter('amazon_s3_bucket_name');
        $bucket = $bucket_name;
        $securityKey = $this->container->getParameter('amazon_aws_key');
        $securitySecret = $this->container->getParameter('amazon_aws_secret_key');
        //userDefined metadata
        $metaData = array(
            'x-amz-meta-event_type' => "sample",
            'x-amz-meta-event_name' => "sample1"
        );

        //$file = new File("/var/www/html/projects/event_tracking/web/assets/img/samuelchan.jpg");
        $fileName = $file->getBasename();
        $fileNamePart = explode('.',$fileName);
        $lastPart = end($fileNamePart);
        $extension = $lastPart;

        if(empty($extension)){
            /*
            $mime = $file->getMimeType();
            // work arround when could not get extension gz
            if($mime=='application/x-gzip'){
                $extension = 'gz';
            }
            */
        }

        $filename = $file->getBasename();
        $folder   = $folder_name ;
        if($folder !== ''){
            $filename = $folder.'/'.$filename;
        }
        $filepath = $file->getPathname();

        // $filepath should be absolute path to a file on disk

        $credentials = new \Aws\Credentials\Credentials($securityKey, $securitySecret);
        $options = [
            //'host' => 'standalone-a.s3-website-ap-southeast-1.amazonaws.com',
            'region'            => $region,
            'version'           => '2006-03-01',
            'signature_version' => 'v4',
            'credentials' =>$credentials
        ];

        $s3 = new  \Aws\S3\S3Client(
            $options
        );

        // Upload a file.
        $result = $s3->putObject(array(
            'Bucket'       => $bucket,
            'Key'          => $filename,
            'SourceFile'   => $filepath,
            'ContentType'  => $content_type,
            'ACL'          => 'public-read',
            'StorageClass' => 'REDUCED_REDUNDANCY',
            'Metadata'     => $metaData
        ));

        if(isset($result['@metadata']['statusCode'])){
            if($result['@metadata']['statusCode'] == '200'){

                $fs = new Filesystem();

                return new Response($filename);
            } else {
                return new Response($result['@metadata']['statusCode']);
            }
        }
        else{
            return false;
        }

    }

    public function getImagePath()
    {
        $aksrcBucket = $this->container->getParameter('amazon_s3_aksrc');

        return implode('/', array($aksrcBucket['base_url'], $aksrcBucket['folder']['user_images']));
    }

    public function migrate()
    {
        $uri = $this->get('router')->generate('dashboard_update_user_app', array(
            'key' => 'value'
        ));

        $url = $request->get('_route');

        return $this->redirect($uri, 301);

        $conn = $this->get('doctrine.dbal.pgsql_connection');
        $prod = $this->get('doctrine.dbal.pgsql_prod_connection');

        $sql  = $conn->prepare("SELECT * FROM client;");
        $sql->execute();

        $records = array();
        $cnt = 0;
        $stats = "";

        for($i = 0; $row = $sql->fetch(); $i++)
        {
            $records[] = $row;
            $cnt++;
        }

        for($x = 0; $x < $cnt; $x++)
        {
            $sql_prod  = $prod->prepare("INSERT INTO client VALUES ('".$records[$x]['id']."','".$records[$x]['client_name']."',
            '".$records[$x]['client_app']."','".$records[$x]['account_type']."','".$records[$x]['created']."','".$records[$x]['updated']."')");
            $sql_prod->execute();

            $stats .= "added record " . $x+1 . "<br />";
        }

        return new Response($stats);
        //for($x = 0; )
    }

    public function refreshImageAction()
    {
        $username = $this->getLoggedAuthenticationUsername();

        if($username == "" || $username == null)
        {
            return $this->render('authentication/index_user.html.twig');
        }

        $auth = $this->container->get('authentication_repository');

        $result = $auth->findbyCriteria("username", $username);

        return $result->getImgPath();
    }

    public function refreshNameAction()
    {
        $username = $this->getLoggedAuthenticationUsername();

        if($username == "" || $username == null)
        {
            return $this->render('authentication/index_user.html.twig');
        }

        $auth = $this->container->get('authentication_repository');

        $result = $auth->findbyCriteria("username", $username);

        return $result->getName();
    }

    public function getClientName()
    {
        $conn = $this->get('doctrine.dbal.pgsql_connection');

        $sql  = $conn->prepare("SELECT DISTINCT id, client_name FROM client;");
        $sql->execute();

        $data = array();

        for($x = 0; $row = $sql->fetch(); $x++)
        {
            $data[] = $row;
        }

        $dnt = count($data);
        $cnt = 0;
        $app_name = array();

        $display = "";
        $client_name="";


        //return new Response(\Doctrine\Common\Util\Debug::dump($this->getLoggedAuthentication()->getClientId()));
        $client_id = $this->getLoggedAuthentication()->getClientId();
        $app_ids  = explode(",", $this->getLoggedAuthentication()->getClientId());
        $app_ids  = "'" .implode("','", $app_ids) ."'";
        $dql  = $conn->prepare("SELECT DISTINCT id, client_name FROM client WHERE id IN ($app_ids);");
        $dql->execute();

        for($xx = 0; $q = $dql->fetch(); $xx++)
        {
            $display = $q['client_name'];
            $cnt++;
        }

        $xnt = count($display);

        if($dnt == $cnt)
        {
            $display = "All Clients";
        }
        else
        {
            //$app_name  = implode(",", $d);
            //$rows[$i]->setClientId($app_name);
        }

        return $display;
    }

    /* /dashboard/server_monitor */
    public function renderServerMonitor()
    {
        /* ADDED TO REDIRECT TO LOGIN IF THERE IS NO SESSION OR LOGGED USER IS CLIENT paul.francisco 2015-12-18 */
        $authIdFromSession = $this->getLoggedAuthenticationId();
        $user_type = $this->getLoggedUserType();

        /* 0 = client access, 2 = client access + clover access */
        if($authIdFromSession == null || $user_type == 0 || $user_type == 2)
        {
            $this->url = $this->generateUrl('dashboard_logout');
            return $this->redirect($this->url, 301);
        }
        return $this->render('server_monitor/aws-dashboard.html.twig', array("active" => "server_monitor"));
    }

    public function renderDataAcquisitionAction()
    {
        /* ADDED TO REDIRECT TO LOGIN IF THERE IS NO SESSION OR LOGGED USER IS CLIENT paul.francisco 2015-12-18 */
        $authIdFromSession = $this->getLoggedAuthenticationId();
        $user_type = $this->getLoggedUserType();

        /* 0 = client access, 2 = client access + clover access */
        if($authIdFromSession == null || $user_type == 0 || $user_type == 2)
        {
            $this->url = $this->generateUrl('dashboard_logout');
            return $this->redirect($this->url, 301);
        }

        $jasper = $this->refreshJasperAction();

        return $this->render('authentication/data_acquisition.html.twig', array("active" => "data_acquisition", "jasper" => $jasper));
    }

    public function refreshJasperAction()
    {
        $auth = $this->getLoggedAuthentication();
        $user = $auth->getUsername();
        $mail = $auth->getEmail();

        if($user == "" || $mail == "")
        {
            return null;
        }

        $jasper = $this->container->get('jasper_repository');

        $result = $jasper->findUser("$user", "$mail");

        return $result;
    }

    public function saveDefaultPresets(Authentication $auth) {
        $filterRepo = $this->get('filter_repository');
        $clientRepo = $this->get('client_repository');
        // 'recent_install' for all client type
        $filter = new Filter();
        $filter->setAuthenticationId($auth->getId());
        $filter->setPresetName('Recent Install');
        $filter->setDescription('Recent Install');
        $filter->setIsDefault(1);
        $filterMetadata['intent_metadata'] = array(
                            'behaviour_id' => Action::BEHAVIOURS['INSTALL_BEHAVIOUR_ID'],
                            'category_ids' => array(),
                            'intent_key' => 'recent_install'
                        );
        $filter->setFilterMetadata(
            $filterMetadata
        );
        $filterRepo->save($filter);
        $clientId = $auth->getClientId();
        $client = $clientRepo->find($clientId);
        $accountType = '';
        if ($client) {
            $accountType = $client->getAccountType();
        }
        if ($accountType  == Client::ACCOUNT_TYPE['E-commerce']) {
            //Recent Purchase
            $filter = new Filter();
            $filter->setAuthenticationId($auth->getId());
            $filter->setPresetName('Recent Purchase');
            $filter->setDescription('Recent Purchase');
            $filter->setIsDefault(1);
            $filterMetadata['intent_metadata'] = array(
                                'behaviour_id' => Action::BEHAVIOURS['PURCHASE_BEHAVIOUR_ID'],
                                'category_ids' => array(),
                                'intent_key' => 'recent_purchase'
                            );
            $filter->setFilterMetadata(
                $filterMetadata
            );
            $filterRepo->save($filter);
            //Potential Spender
            $filter = new Filter();
            $filter->setAuthenticationId($auth->getId());
            $filter->setPresetName('Potential Spender');
            $filter->setDescription('Potential Spender');
            $filter->setIsDefault(1);
            $filterMetadata['intent_metadata'] = array(
                                'behaviour_id' => Action::BEHAVIOURS['ADD_TO_WISHLIST_BEHAVIOUR_ID'],
                                'category_ids' => array(),
                                'intent_key' => 'potential_spenders'
                            );
            $filter->setFilterMetadata(
                $filterMetadata
            );
            $filterRepo->save($filter);
            //High Spender
            $filter = new Filter();
            $filter->setAuthenticationId($auth->getId());
            $filter->setPresetName('High Spender');
            $filter->setDescription('High Spender');
            $filter->setIsDefault(1);
            $filterMetadata['intent_metadata'] = array(
                                'behaviour_id' => Action::BEHAVIOURS['PURCHASE_BEHAVIOUR_ID'],
                                'category_ids' => array(),
                                'intent_key' => 'high_spender'
                            );
            $filter->setFilterMetadata(
                $filterMetadata
            );
            $filterRepo->save($filter);
        } elseif ($accountType  == Client::ACCOUNT_TYPE['Gaming']) {
            //Recent Purchase
            $filter = new Filter();
            $filter->setAuthenticationId($auth->getId());
            $filter->setPresetName('Recent Purchase');
            $filter->setDescription('Recent Purchase');
            $filter->setIsDefault(1);
            $filterMetadata['intent_metadata'] = array(
                                'behaviour_id' => Action::BEHAVIOURS['PURCHASE_BEHAVIOUR_ID'],
                                'category_ids' => array(),
                                'intent_key' => 'recent_purchase'
                            );
            $filter->setFilterMetadata(
                $filterMetadata
            );
            $filterRepo->save($filter);

            $filter = new Filter();
            $filter->setAuthenticationId($auth->getId());
            $filter->setPresetName('Hardcore Gamer');
            $filter->setDescription('Hardcore Gamer');
            $filter->setIsDefault(1);
            $filterMetadata['intent_metadata'] = array(
                                'behaviour_id' => Action::BEHAVIOURS['PURCHASE_BEHAVIOUR_ID'],
                                'category_ids' => array(),
                                'intent_key' => 'hardcore_gamer'
                            );
            $filter->setFilterMetadata(
                $filterMetadata
            );
            $filterRepo->save($filter);


        } elseif ($accountType  == Client::ACCOUNT_TYPE['Branding']) {
            // todo
        }
        //get $auth, get client, check client type

    }

    /* /dashboard/server_key */
    /*
    public function getServerKey(Request $request)
    {
        $data = $request->query->get('data');
        $json = '{
                "app_id"     : "com.bukalapak.android",
                "server_key" : "ad989031kedaisj09fdkj3"
                }';

        $decode = json_decode($data);

        $app_id = $decode->{'app_id'};
        $server = $decode->{'server_key'};

        print $app_id;
        print "<br />";
        print $server;
        die;
    }
    */

    public function refreshApiKey()
    {
        $this->id = $this->getLoggedAuthentication()->getId();
        // $this->apiKey = $this->getLoggedApiKey();

        // if($this->apiKey == "" || $this->apiKey == null)
        // {
        //     return null;
        // }

        $auth = $this->container->get('authentication_repository');

        $result = $auth->findbyCriteria("id","$this->id");

        return $result->getApiKey();
    }

    public function redirectLogOut()
    {
        return $this->generateUrl('dashboard_logout');
    }

    /* /dashboard/admin/analytics_display/{page} */
    public function displayMetadataAction(Request $request)
    {
        $authRepo  = $this->container->get('auth.controller');
        $authIdFromSession = $authRepo->getLoggedAuthenticationId();
        $user_type = $authRepo->getLoggedUserType();
        /* 0 = client access, 2 = client access + clover access */
        if($authIdFromSession == null || $user_type == 0 || $user_type == 2)
        {
            $this->url = $this->generateUrl('dashboard_logout');
            return $this->redirect($this->url, 301);
        }
        else
        {
            $analyticsRepo = $this->container->get('analytics_metadata_repository');
            $page = $request->get('page');
            $dataPerPage = 10;

            $result = $analyticsRepo->getResultAndCount($page,$dataPerPage);
            $rows = $result['rows'];
            $totalCount = $result['total'];

            $paginator = new \lib\Paginator($page, $totalCount, $dataPerPage);
            $pageList = $paginator->getPagesList();

            $this->mode = $request->get('mode');
            $this->id   = $request->get('to_edit');
            if("" != $this->mode && null != $this->mode)
            {
                $analyticsRepo = $this->container->get('analytics_metadata_repository');

                $record = $analyticsRepo->findbyCriteria("id", "$this->id");

                return $this->render('analytics/analytics.html.twig',
                    array(
                        'list' => $rows,
                        'paginator' => $pageList,
                        'cur' => $page,
                        'total' => $paginator->getTotalPages() -1,
                        'per' => $dataPerPage,
                        'active' => 'analytics',
                        'selected_record' => $record,
                        'mode' => $this->mode
                        )
                );
            }
            else
            {
                return $this->render('analytics/analytics.html.twig',
                    array(
                        'list' => $rows,
                        'paginator' => $pageList,
                        'cur' => $page,
                        'total' => $paginator->getTotalPages() -1,
                        'per' => $dataPerPage,
                        'active' => 'analytics'
                        )
                );
            }
        }
    }
}