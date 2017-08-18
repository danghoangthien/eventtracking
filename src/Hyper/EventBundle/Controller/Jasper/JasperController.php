<?php
namespace Hyper\EventBundle\Controller\Jasper;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Hyper\Domain\Client\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\HttpFoundation\File\File;
use Hyper\Domain\Jasper\Jasper;

class JasperController extends Controller
{    
    /**
    * @param ContainerInterface $container
    */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }   
    
    /* /jasper/add */    
    public function renderAddUserAction(Request $request)
    {                              
        $jasper = $this->container->get('jasper_repository');
        $page   = $request->get('page');
        $dataPerPage = 10;
        
        $result = $jasper->getResultAndCount($page,$dataPerPage);
        $rows   = $result['rows'];
        $totalCount = $result['total'];
        
        $paginator = new \lib\Paginator($page, $totalCount, $dataPerPage);
        $pageList = $paginator->getPagesList();
        
        //return new Response(json_encode(array("records" => \Doctrine\Common\Util\Debug::dump($rows))));
        return $this->render('jasper/add_user.html.twig', 
            array(
                'list' => $rows, 
                'paginator' => $pageList, 
                'cur' => $page, 
                'total' => $paginator->getTotalPages() -1,
                'per' => $dataPerPage
                )
        );
        
        //return $this->render('jasper/add_user.html.twig', array('active' => 'jasper_add'));
    }    
    
    /* /jasper/save */
    public function saveJasperAccountAction()
    {                        
        $this->date = strtotime(date('Y-m-d h:i:s'));
        
        $request = $this->getRequest();                
        
        $this->username  = $request->request->get('username');
        $this->password  = $request->request->get('password');
        $this->org       = $request->request->get('org');
        $this->email     = !filter_var($request->request->get('email'), FILTER_VALIDATE_EMAIL) === false ? $request->request->get('email') : null;
        $this->created   = $this->date;
        $this->updated   = $this->date;              
        
        if("" == $this->username || "" == $this->password || "" == $this->org || null == $this->email)
        {
            return new Response(json_encode(array("status"=>"failed", "message"=>"An invalid data was sent")));
        }
        else
        {
            try 
            {       
                $jasperRepo = $this->container->get('jasper_repository');
                
                $jasper = new Jasper();                
                $jasper->setUsername($this->username);                
                $jasper->setPassword($this->password);
                $jasper->setOrganization($this->org);
                $jasper->setEmail($this->email);  
                $jasper->setCreated($this->created);  
                $jasper->setUpdated($this->updated);  
                
                $jasperRepo->save($jasper); 
                $jasperRepo->completeTransaction();                                                          
                
                return new Response(json_encode(array("status"=>"success","message" => "Account successfully saved")));
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
}
