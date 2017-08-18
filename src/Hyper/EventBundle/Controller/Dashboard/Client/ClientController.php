<?php
namespace Hyper\EventBundle\Controller\Dashboard\Client;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Hyper\Domain\Client\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\HttpFoundation\File\File;

class ClientController extends Controller
{    
    /**
    * @param ContainerInterface $container
    */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }   
    
    /* /dashboard/client/client_access */    
    public function indexAction(Request $request)
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
            
            $conn = $this->get('doctrine.dbal.pgsql_connection');                                    

            $sql  = $conn->prepare("SELECT DISTINCT app_id FROM applications;");                      
            $sql->execute();

            $data = array();

            for($x = 0; $row = $sql->fetch(); $x++) 
            {
                $data[] = $row;
            }  
            
            $cl = $this->container->get('client_repository');
            $page = $request->get('page');
            $dataPerPage = 7;

            $result = $cl->getResultAndCount($page,$dataPerPage);
            $rows = $result['rows'];
            $totalCount = $result['total'];                        
            
            $paginator = new \lib\Paginator($page, $totalCount, $dataPerPage);
            $pageList = $paginator->getPagesList();
            
            return $this->render('access_management/client_access.html.twig',
                    array("user" => $_SESSION['authentication_id'], 
                          "apps" => $data,
                          'list' => $rows, 
                          'paginator' => $pageList, 
                          'cur' => $page, 
                          'total' => $paginator->getTotalPages() -1,
                          'per' => $dataPerPage,
                          'active' => 'client_access'));
        }
        else
        {
            $this->url = $this->generateUrl('dashboard_logout');
            return $this->redirect($this->url, 301);
            // return $this->render('access_management/client_access.html.twig');
        }
    }    
    
    /* /dashboard/client/save */
    public function saveClientAction()
    {                        
        $this->date = strtotime(date('Y-m-d h:i:s'));
        
        $request = $this->getRequest();                
        
        $this->client_name  = $request->request->get('client_name');
        $this->client_app   = str_replace(" ", "", $request->request->get('client_app'));
        $this->account_type = $request->request->get('account_type');
        $this->created   = $this->date;
        $this->updated   = $this->date;              
                
//        return new Response(json_encode(array("name" => $this->client_name,
//                "app" => $this->client_app,
//                "account" => $this->account_type)));
        
        $clientRepo = $this->container->get('client_repository');
        $records  = $clientRepo->findbyCriteria('client_name', "$this->client_name");        
        $count = count($records);                                        
        
        if ("" == $this->client_name || "" == $this->client_app || "" == $this->account_type || "" == $this->created || "" == $this->updated)
        {            
            return new Response(json_encode(array("message" => "Invalid data sent")));
        }    
        else if( $count > 0 || $count != 0 )
        {
            return new Response(json_encode(array("message" => "Client name already exists!")));
        }  
        else
        {            
            try 
            {                   
                $client = new Client();                
                $client->setClientName($this->client_name);                
                $client->setClientApp($this->client_app);
                $client->setAccountType(Client::ACCOUNT_TYPE["$this->account_type"]);
                $client->setCreated($this->created);  
                $client->setUpdated($this->updated);  
                
                $clientRepo->save($client); 
                $clientRepo->completeTransaction();                                                          
                
                return new Response(json_encode(array("message" => "Client successfully saved")));
            }
            catch (Exception $exc)
            {
                echo $exc->getTraceAsString();
            }
        }
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
    
    /* dashboard/auth/render_edit_client */
    public function renderEditClientAction()
    {
        return $this->render('authentication/edit_client.html.twig', array('active' => 'client_access'));
    }
    
    /* dashboard/auth/edit_client */
    public function editClientAction()
    {   
        $request = $this->getRequest();
        $this->id = $request->query->get('id');
        
        $authRepo = $this->container->get('client_repository');                                                    
        $record   = $authRepo->findbyCriteria('id', $this->id);
        $app_id   = $record->getClientApp();
        $app_ids  = explode(",", $app_id);
        $app_ids  = "'" .implode("','", $app_ids) ."'";
        
        //$conn = $this->get('doctrine.dbal.pgsql_connection');
        $conn=$this->container->get('doctrine')->getEntityManager('pgsql')->getConnection();
        $sql  = $conn->prepare("SELECT DISTINCT app_id FROM applications WHERE app_id IN($app_ids);");                      
        $sql->execute();
        
        $client_apps = array();

        for($i = 0; $row = $sql->fetch(); $i++) 
        {
            $client_apps[] = $row;
        }  
        
        /* GET LIST OF APPLICATIONS */
        $sql2  = $conn->prepare("SELECT DISTINCT app_id FROM applications group by app_id, app_name;");                      
        $sql2->execute();
        
        $all_apps = array();

        for($x = 0; $rows = $sql2->fetch(); $x++) 
        {
            $all_apps[] = $rows;
        }  
   
        //return new Response(json_encode(array("records" => \Doctrine\Common\Util\Debug::dump($app_ids))));
        return $this->render('authentication/edit_client.html.twig', 
            array('record' => $record, "client_apps" => $client_apps, "all_apps" => $all_apps, 'active' => 'client_access'));
    }
    
    /* dashboard/auth/update_client */
    public function updateClientAction()
    {
        $request = $this->getRequest();
        $this->client_id = $request->request->get('client_id');
        $this->client    = $request->request->get('client');
        $this->account_type  = $request->request->get('account_type');
        $this->account_type = Client::ACCOUNT_TYPE["$this->account_type"];
        $this->app_id    = $request->request->get('app_id');
        
//        return new Response(json_encode(array("client_id" => $this->client_id, "client" => $this->client,
//                "account_type" => $this->account_type, "app_id" => $this->app_id)));
        
        if($this->client == "" || $this->account_type == "" || $this->app_id == "")
        {
            return new Response(json_encode(array("status" => "invalid")));
        }
        else
        {
            $clientRepo = $this->container->get('client_repository');            
            $client     = $clientRepo->updateClient("$this->client_id","$this->client", "$this->account_type", "$this->app_id");                        
            
            if($client == "success")
            {
                return new Response(json_encode(array("status" => "success")));
            }
            else
            {
                return new Response(json_encode(array("status" => "failed")));
            }
        }
    }
    
    public function randomString($length) 
    {
        $str = "";
        $characters = array_merge(range('A', 'Z'), range('a', 'z'), range('0', '9'));
        $max = count($characters);
        $max -= 1;

        for ($i = 0; $i < $length; $i++) {
            $rand = mt_rand(0, $max);
            $str .= $characters[$rand];
        }
        return $str;
    }

    /* /dashboard/user/email */
    public function renderResetEmailAction()
    {
        return $this->render('email/reset_password.html.twig');
    }
    
    private function sendEmailReset($email, $newPass) {
        $message = \Swift_Message::newInstance()
                ->setSubject('Password Reset')
                ->setFrom(array('paul@hypergrowth.co' => "HyperGrowth Admin"))
                ->setTo("$email")
                ->setBody(
                    $this->renderView('email/rp_email_template.html.twig', array(
                        'new_pass'=> $newPass
                    )), 
                    'text/html'
                );
                
                return $this->get('mailer')->send($message);
                
    }
    
    /* /dashboard/user/link */
    public function createLinkAction(Request $request)
    {                
        $email = $request->query->get('email');
        $message = $validateEmail = '';
        if ($email) {
            if (filter_var($email,FILTER_VALIDATE_EMAIL) === false) {
                $message = 'Invalid email format.';
            } else {
                $validateEmail = true;
            }
        }
        if ($validateEmail) {
            $authRepo = $this->container->get('authentication_repository');
            $auth = $authRepo->findbyCriteria("email", "$email");
            $reset = $newPass = $send = '';
            if ($auth) {
                $newPass = $this->randomString(10);
                $reset = $authRepo->resetPassword("$email", "$newPass");
            } else {
                $message = "Supplied email doesn't exist.";
            }
            if ($reset == "success") {
                $send = $this->sendEmailReset($email, $newPass);
            }
            if ($auth && $send) {
                $message = "Password reset successful. Please check your email for your temporary password.";
            } elseif ($auth && !$send) {
                $message = "An unknown error occurred. Please try again.";
            }
        }
        
        return $this->render('email/reset_password.html.twig', array(
            "message" => $message,
            "email" => $email
        ));      
    }        
    
    /* /dashboard/user/reset */
    public function resetPasswordAction(Request $request)
    {
        return $this->render('email/reset_password.html.twig');
    }
    
    /* /dashboard/access/delete_client */
    public function deleteAction()
    {
        $request = $this->getRequest();
        $this->id = $request->query->get('id');
        
        if(null != $this->id && "" != $this->id)
        {
            $auth = $this->container->get('client_repository');
            $delete = $auth->deleteUser($this->id);                        
            
            if($delete == "success")
            {                   
                return $this->render('access_management/client_access.html.twig',array("message" => "success"));
            }
            else
            {                             
                return $this->render('access_management/client_access.html.twig',array("message" => "failed"));
            }
        }
        else
        {            
            return $this->render('access_management/client_access.html.twig',array("message" => "null"));
        }
    }       
    
    /* function to fetch the apps based on the logged in user. Used by one or more modules in AK 
    *  2015-12-10 paul.francisco
    *  /dashboard/get_app
    */
    public function getApplicationsByLoggedInUser()
    {
        $authController = $this->get('auth.controller');
        $appIdsByAuthentication = $authController->refreshClient();
        
        $client_ids  = explode(",", $appIdsByAuthentication);
        $client_ids  = "'" .implode("','", $client_ids) ."'";
        
        $conn = $this->get('doctrine.dbal.pgsql_connection');
        $sql  = $conn->prepare("SELECT DISTINCT id, client_app FROM client WHERE id IN ($client_ids);");
        $sql->execute();
        $c_app = "";
        for($i = 0; $row = $sql->fetch(); $i++)
        {
            $c_app .= $row['client_app'] . ",";
        }
        
        $c_app = substr($c_app, 0, -1);
        $app_ids  = explode(",", $c_app);
        $app_ids  = "'" .implode("','", $app_ids) ."'";
        
        $app_sql  = $conn->prepare("SELECT DISTINCT app_id, app_name FROM applications WHERE app_id IN ($app_ids);");                      
        $app_sql->execute();
        $data = array();
        for($x = 0; $app_row = $app_sql->fetch(); $x++) 
        {
            $data[] = $app_row;
        }
        
        return $data;
    }
    
    /* /dashboard/client/push */
    public function renderPushNotifAction(Request $request)
    {
        /* ADDED TO REDIRECT TO LOGIN IF THERE IS NO SESSION paul.francisco 2015-12-18 */
        $authRepo = $this->container->get('auth.controller');
        $authIdFromSession = $authRepo->getLoggedAuthenticationId();
        if($authIdFromSession == null)
        {
            $this->url = $this->generateUrl('dashboard_logout');
            return $this->redirect($this->url, 301);
        }
        
        $apps = $this->getApplicationsByLoggedInUser();
        
        /* DISPLAY RECORDS IN GRID */
        $pushRepo = $this->container->get('push_repository');
        $page = $request->get('page');
        $dataPerPage = 10;
        
        $result = $pushRepo->getResultAndCount($page,$dataPerPage);
        $rows = $result['rows'];
        $totalCount = $result['total'];
        
        $paginator = new \lib\Paginator($page, $totalCount, $dataPerPage);
        $pageList = $paginator->getPagesList();
        
        return $this->render('notification/push_notification.html.twig', 
            array(
                'list' => $rows, 
                'paginator' => $pageList, 
                'cur' => $page, 
                'total' => $paginator->getTotalPages() -1,
                'per' => $dataPerPage,
                'applications' => $apps,
                'active' => 'notifications',
                "circle"=>"push"
                )
        );
        
        //return $this->render('notification/push_notification.html.twig',array("applications" => $apps, "active"=>"notifications", "circle"=>"push"));
    }
    
    /* /dashboard/client/banner */
    public function renderBannerAction(Request $request)
    {
        $apps2 = $this->getApplicationsByLoggedInUser();
        
        /* DISPLAY RECORDS IN GRID */
        $promoRepo = $this->container->get('promo_repository');
        $page = $request->get('page');
        $dataPerPage = 10;
        
        $result = $promoRepo->getResultAndCount($page,$dataPerPage);
        $rows = $result['rows'];
        $totalCount = $result['total'];
        
        $paginator = new \lib\Paginator($page, $totalCount, $dataPerPage);
        $pageList = $paginator->getPagesList();
        
        $landingRepo = $this->container->get('promo_landing_repository');
        $landingData = $landingRepo->getAllRecords();
        
        return $this->render('notification/promo_banners.html.twig', 
            array(
                'list' => $rows, 
                'paginator' => $pageList, 
                'cur' => $page, 
                'total' => $paginator->getTotalPages() -1,
                'per' => $dataPerPage,
                'applications' => $apps2,
                'landing_page' => $landingData,
                'active' => 'notifications',
                "circle"=>"banner"
                )
        );
        
        // return $this->render('notification/promo_banners.html.twig',array("applications" => $apps2, "active"=>"notifications", "circle"=>"banner"));
    }
    
    public function renderMainDashboard()
    {
        /* ADDED TO REDIRECT TO LOGIN IF THERE IS NO SESSION paul.francisco 2015-12-29 */
        $authController    = $this->get('auth.controller');
        $authIdFromSession = $authController->getLoggedAuthenticationId();
        
        if($authIdFromSession == null)
        {
            $this->url = $this->generateUrl('dashboard_logout');
            return $this->redirect($this->url, 301);
        }
        
        return $this->render('authentication/main_dashboard.html.twig', array("active" => "main_dashboard"));
    }
    
    /* dashboard/client/user_journey */
    public function renderUserJourneyAction(Request $request)
    {
        $device_id = $request->get('device_id');
        return $this->render('user_journey/user_journey.html.twig', array("device_id" => $device_id));
    }
    
    /* dashboard/client/advance */
    public function renderAdvancedAudienceCard()
    {
        return $this->render('audience_card/advance.html.twig');
    }
}
