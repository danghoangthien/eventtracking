<?php
namespace Hyper\EventBundle\Controller\Dashboard\Category;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Hyper\Domain\Authentication\Authentication;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Hyper\Domain\Category\Category;

class CategoryController extends Controller
{
    /**
    * @param ContainerInterface $container
    */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    
    public function indexAction(Request $request)
    {   
        $ctr  = $this->container->get('auth.controller');
        
        /* ADDED TO REDIRECT TO LOGIN IF THERE IS NO SESSION paul.francisco 2015-12-18 */
        $authIdFromSession = $ctr->getLoggedAuthenticationId();
        if($authIdFromSession == null)
        {
            $this->url = $this->generateUrl('dashboard_logout');
            return $this->redirect($this->url, 301);
        }
            
        $logged = $ctr->getLoggedAuthenticationUsername();
        $auth = $this->container->get('authentication_repository');
        $user = $auth->findbyCriteria('username', $logged);
        $client_id = $user->getClientId();
        
        $c_ids  = explode(",", $client_id);
        $c_ids  = "'" .implode("','", $c_ids) ."'";
        
        $conn = $this->get('doctrine.dbal.pgsql_connection');    
        $sql  = $conn->prepare("SELECT DISTINCT id, client_name, client_app FROM client WHERE id IN ($c_ids);");                      
        $sql->execute();
        
        $rs = array();

        for($i = 0; $row = $sql->fetch(); $i++) 
        {
            $rs[] = $row['client_app'];
        }
        
        $app_ids  = explode(",", $rs[0]);
        $app_ids  = "'" .implode("','", $app_ids) ."'";
        
        $appRepo = $this->container->get('application_repository');
        $records = $appRepo->getDistinctAppId($app_ids);
        $cnt     = count($records);
        
        $data    = array();
        for($i = 0; $i < $cnt; $i++)
        {
            $data[] = $records[$i];
        }
        
        $categoryRepo = $this->container->get('category_repository');
        $cat_all      = $categoryRepo->getAll();
        
        $page = $request->get('page');
        $dataPerPage = 10;
        
        $result = $categoryRepo->getResultAndCount($page,$dataPerPage);
        $rows   = $result['rows'];
        $totalCount = $result['total'];
        
        $paginator = new \lib\Paginator($page, $totalCount, $dataPerPage);
        $pageList  = $paginator->getPagesList();
        
        //$tree      = $categoryRepo->getAll();
        
        //return new Response(json_encode(array("records" => \Doctrine\Common\Util\Debug::dump($rows))));
        return $this->render('category/category_tree.html.twig', 
            array(
                'list' => $rows, 
                'paginator' => $pageList, 
                'cur' => $page, 
                'total' => $paginator->getTotalPages() -1,
                'per' => $dataPerPage,
                'app' => $data,
                'cat' => $cat_all,
                'tree'=> $cat_all,
                'active' => 'category_tree'
                )
        );
        
        //return $this->render('category/category_tree.html.twig', array('app' => $data, 'cat' => $cat_data));
    } 
    
    /* /dashboard/category/refresh_code */
    public function refreshCode(Request $request)
    {
        $app_id =  $request->request->get('app_id');
        
        $categoryRepo = $this->container->get('category_repository');
        $records      = $categoryRepo->getAjaxCodeByAppId($app_id);
        
        return new Response(json_encode($records));
    }
    
    /* /dashboard/category/save_code */
    public function saveCodeAction(Request $request)
    {
        $id     = $request->request->get('id');
        $app_id = $request->request->get('app_id');
        $code   = $request->request->get('code');
        $name   = $request->request->get('name');
        $parent_id = $request->request->get('parent_id');
        if($parent_id == null || $parent_id == "")
        {
            $parent_id = 0;
        }
        
        $categoryRepo = $this->container->get('category_repository');      
        $categoryRepo->updateCategoryTree($id, $app_id, $code, $name, $parent_id, "Add");
        
        // $category = new Category();
        // $category->setAppId($app_id);
        // $category->setCode($code);
        // $category->setName($name);
        // $category->setParentId($parent_id);
        
        // $categoryRepo->save($category);
        // $categoryRepo->completeTransaction();
        
        return new Response(json_encode(array("status" => "success")));
    }
    
    /* /dashboard/category/delete_node
    *  DELETE NODE IS ACTUALLY UPDATING ONLY. SETTING parent_id to 0 and name to blank
    *  Paul 2015-12-03
    */
    public function deleteNodeAction(Request $request)
    {
        $id     = $request->request->get('id');
        $app_id = $request->request->get('app_id');
        $code   = $request->request->get('code');
        
        //print $id . " " . $app_id . " " . $code; die;
        
        if($id != "" && $app_id != "" && $code != "")
        {
            $categoryRepo = $this->container->get('category_repository');
            
            $update = $categoryRepo->updateCategoryTree($id, $app_id, $code, null, null, "Delete");
            
            if($update == "success")
            {                                
                return new Response(json_encode(array("status" => "success")));
            }
            else
            {
                return new Response(json_encode(array("status" => "failed", "error"=> "Failed to delete category")));
            }
        }
    }
}
