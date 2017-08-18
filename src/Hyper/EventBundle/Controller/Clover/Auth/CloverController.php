<?php
namespace Hyper\EventBundle\Controller\Clover\Auth;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Hyper\Domain\Authentication\Authentication;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Hyper\EventBundle\Service\EventProcess;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Hyper\Domain\Jasper\Jasper;

class CloverController extends Controller
{
    const AUTH_STATUS_LOGOUT = '-1';
    /**
    * @param ContainerInterface $container
    */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    
    /* clover/auth/login */
    public function renderClientLoginAction(Request $request)
    {
        if(isset($_SESSION['authentication_id']))
        {
            return $this->render('clover/client/main_dashboard.html.twig',array("user" => $_SESSION['authentication_id']));
        }
        else
        {
            return $this->render('clover/login/index_client.html.twig');
        }
    }  
    
    /* clover/auth/validate_login */
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
        $records  = $authRepo->findUserPassword("$this->username", md5("$this->password"));              
        $count = count($records);                
        if( $count > 0 )
        {      
            $isActive = $records->getStatus();
            if (!$isActive)
            {
                return new Response(json_encode(array("status" => "failed", "error" => "account is disabled")));
            }
            
            if($records->getUserType() == 1000 && $this->access == 2 
            || $records->getUserType() == 1 && $this->access == 2
            || $records->getUserType() == 2 && $this->access == 2)
            {
                $this->id = session_id();
                //$_SESSION['authentication_id'] = $this->username;
                $_SESSION['authentication_id'] = $records->getId();
                $_SESSION['username'] = $records->getUsername();
                $_SESSION['auth'] = $records;
                $_SESSION['created'] = $records->getCreated();
                $_SESSION['access']  = $this->access;
                
                $username   = $records->getUsername();
                $email      = $records->getEmail();
                // $jasperRepo = $this->container->get('jasper_repository'); 
                // $jRecord    = $jasperRepo->findUser("$username", "$email");
                $jCon  = $this->get('doctrine.dbal.pgsql_connection');
                $jSql  = $jCon->prepare("SELECT * FROM jasper_auth WHERE username = '$username' AND email = '$email';");                      
                $jSql->execute();
                
                $jData = array();
    
                for($jX = 0; $rX = $jSql->fetch(); $jX++) 
                {
                    $jData[] = $rX;
                }
                
                if(count($jData) > 0)
                {
                    // return new Response(json_encode(array("user" => $jData[0]['username'], "pass" => $jData[0]['password'], "org" => $jData[0]['organization'])));
                    return new Response(json_encode(array("status" => "jasper_success","session_id" => $this->id, 
                    "username" => $_SESSION['authentication_id'], 
                    "created" => $_SESSION['created'],
                    "jUser" => $jData[0]['username'], "jPass" => $jData[0]['password'], "jOrg" => $jData[0]['organization'])));
                }
                else
                {
                    return new Response(json_encode(array("status" => "success","session_id" => $this->id, "username" => $_SESSION['authentication_id'], 
                    "created" => $_SESSION['created'])));   
                }
            }
            
            if($this->access != $records->getUserType())
            {
                return new Response(json_encode(array("status" => "failed", "error" => "Access denied. You are not allowed for this module.")));
            }
            else
            {
                $this->id = session_id();
                //$_SESSION['authentication_id'] = $this->username;
                $_SESSION['authentication_id'] = $records->getId();
                $_SESSION['username'] = $records->getUsername();
                $_SESSION['auth'] = $records;
                $_SESSION['created'] = $records->getCreated();
                $_SESSION['access']  = $this->access;
                return new Response(json_encode(array("status" => "success","session_id" => $this->id, "username" => $_SESSION['authentication_id'], "created" => $_SESSION['created'])));
                $isActive = $records->getStatus();
            }                        
        }
        else
        {
            return new Response(json_encode(array("status" => "failed", "error" => "Invalid login credentials")));
        }
    }
    
    public function logoutAction()
    {
        session_destroy();
        $this->id = null;
        $_SESSION['username'] = null;
        $_SESSION['authentication_id'] = null;
        $_SESSION['auth'] = null;                
        
        return $this->render('clover/login/index_client.html.twig');
    }  
    
    public function changePasswordAction()
    {
        return $this->render('authentication/change_password.html.twig');
    }
    
    public function getLoggedAuthenticationId() 
    {
        if(isset($_SESSION['authentication_id'])) 
        {
            return $_SESSION['authentication_id'];
        }else
        {
            throw new \lib\Exception\InvalidAuthenticationException("this module require login");
        }
    }
    
    public function getLoggedAuthenticationUsername()
    {
        if (isset($_SESSION['username']))
        {
            return $_SESSION['username'];
        }else
        {
            return null;
        }
    }
    
    public function getLoggedAuthentication()
    {
        if (isset($_SESSION['auth']))
        {
            return $_SESSION['auth'];
        } else
        {
            return null;
        }
    }
    
    public function getLoggedAuthenticationCreated()
    {
        if(isset($_SESSION['authentication_id']))
        {
            return $_SESSION['created'];
        } else
        {
            throw new \lib\Exception\InvalidAuthenticationException("this module require login");
        }
    }
    
    public function getAccessType() {
        if(isset($_SESSION['access'])) {
            return $_SESSION['access'];
        } else {
            throw new \lib\Exception\InvalidAuthenticationException("this module require login");
        }
    }
    
    public function refreshNameAction()
    {
        $username = $this->getLoggedAuthenticationUsername();
        
        if($username == "" || $username == null)
        {
            return $this->render('clover/login/index_client.html.twig');
        }
        
        $auth = $this->container->get('authentication_repository');
        
        $result = $auth->findbyCriteria("username", $username);
        
        return $result->getName();
    } 
    
    public function getImagePath()
    {
        return $this->container->getParameter('amazon_s3_images');
    }
    
    public function refreshImageAction()
    {
        $username = $this->getLoggedAuthenticationUsername();
        
        if($username == "" || $username == null)
        {
            return $this->render('clover/login/index_client.html.twig');
        }
        
        $auth = $this->container->get('authentication_repository');
        
        $result = $auth->findbyCriteria("username", $username);
        
        return $result->getImgPath();
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
    }
}
