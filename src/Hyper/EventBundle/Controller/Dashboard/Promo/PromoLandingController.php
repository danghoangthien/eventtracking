<?php
namespace Hyper\EventBundle\Controller\Dashboard\Promo;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Hyper\Domain\Authentication\Authentication;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Hyper\Domain\Promo_landing\PromoLanding;
use Hyper\Domain\Promo_placement\PromoPlacement;

class PromoLandingController extends Controller
{
    /**
    * @param ContainerInterface $container
    */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    
    /* dashboard/banner/landing */
    public function saveLandingPageAction(Request $request)
    {
        try
        {
            $json = $request->get('data');
            
            // $json = '
            // {
            //     "app_id": "com.bukalapak.android",
            //     "deeplink_map": {  
            //         "Google": "http://www.google.com",
            //         "HyperGrowth": "http://hypergrowth.co",
            //         "Dial 911": "tel://911",
            //         "Dial 333": "http://pizzahut.com"
            //     }
            // }';
            
            $this->date = strtotime(date('Y-m-d h:i:s'));
            $decode = json_decode($json, true);
            $app_id = $decode['app_id'];
            $deeplink = $decode['deeplink_map'];
            $this->created = $this->date;
            $this->updated = $this->date;
            
            if("" != $app_id)
            {
                $deeplinkRepo = $this->container->get('promo_landing_repository');
                $data = $deeplinkRepo->findbyCriteria("appId","$app_id");
                
                if("" != $data)
                {
                    $orig_deeplink = $data->getDeeplinkMap();
                    $orig_deeplink2 = rtrim($orig_deeplink,"}");
                    $orig_deeplink2 = ltrim($orig_deeplink2,"{");
                    
                    $to_save = $orig_deeplink2;
                    
                    $orig_deeplink2 = explode(",", $orig_deeplink2);
                    $cnt = count($orig_deeplink2);
                    $existing_link = array();
                    for($i = 0; $i < $cnt; $i++)
                    {
                        $clean = explode(":", $orig_deeplink2[$i]);
                        $existing_link[] = $clean[0];
                    }
                    
                    $new_deeplink = json_encode($deeplink);
                    $new_deeplink = ltrim($new_deeplink, "{");
                    $new_deeplink = rtrim($new_deeplink, "}");
                    $new_deeplink = explode(",", $new_deeplink);
                    $plus = count($new_deeplink);
                    $new_link = array();
                    for($x = 0; $x < $plus; $x++)
                    {
                        $c2 = explode(":", $new_deeplink[$x]);
                        $new_link[] = $c2[0];
                    }
                    
                    $to_add = array();
                    
                    $ct = count($new_link);
                    for($w = 0; $w < $ct; $w++)
                    {
                        if(!in_array($new_link[$w], $existing_link))
                        {
                            // print $new_link[$w];
                            //print $new_deeplink[$w];
                            $to_add[] = $new_deeplink[$w];
                        }
                    }
                    
                    if(count($to_add) > 0)
                    {
                        $to_add = implode(",", $to_add);
                    
                        $update_deeplink = $to_save . "," . $to_add;
                        $oBrace = "{";
                        $cBrace = "}";
                        
                        $update_deeplink = rtrim($update_deeplink, ",");
                        $update_deeplink = "{$oBrace}{$update_deeplink}{$cBrace}";
                        
                        // print $update_deeplink; die;
                        
                        $deeplinkRepo->updateDeepLinkMap("$app_id", "$update_deeplink");
                    }
                    
                    echo http_response_code(200);
                    die;
                }
                else
                {
                    // $landing = $this->container->get('promo_landing_repository');
                    $record = new PromoLanding();
                    $record->setAppId(trim($app_id));
                    $record->setDeeplinkMap(trim(json_encode($deeplink)));
                    $record->setCreated($this->created);
                    $record->setUpdated($this->updated);
                    
                    $deeplinkRepo->save($record);
                    $deeplinkRepo->completeTransaction();
                    
                    echo http_response_code(200);
                    die;
                }
            }
        }
        catch(Exception $e)
        {
            echo http_response_code(500);
            die;
        }
    }
    
    /* dashboard/ajax/landing */
    public function ajaxLandingPageAction(Request $request)
    {
        $this->app_id = $request->request->get('app_id');
        // $this->app_id = "com.bukalapak.android";
        
        // $promo_landing = $this->container->get('promo_landing_repository');
        // $data = $promo_landing->getLastRecord();
        
        $conn = $this->get('doctrine.dbal.pgsql_connection');    
        
        /* LANDING PAGE */
        $sql = $conn->prepare("SELECT * FROM promo_landing WHERE app_id = '" . $this->app_id . "';");                      
        $sql->execute();

        $data = array();

        for($x = 0; $row = $sql->fetch(); $x++) 
        {
            $data[] = $row;
        }  
        
        $count = count($data);
        
        /* PLACEMENT */
        $sql_place = $conn->prepare("SELECT * FROM promo_placement WHERE app_id = '" . $this->app_id . "';");                      
        $sql_place->execute();

        $data_place = array();

        for($pl = 0; $row_pl = $sql_place->fetch(); $pl++) 
        {
            $data_place[] = $row_pl;
        }  
        
        $count_pl = count($data_place);
        
        if($count > 0 && $count_pl < 1)
        {
            return new Response(json_encode(array("msg" => "landing", "data" => $data)));
        }
        else if($count < 1 && $count_pl > 0)
        {
            return new Response(json_encode(array("msg" => "placement", "data" => $data_place)));
        }
        else if($count > 0 && $count_pl > 0)
        {
            return new Response(json_encode(array("msg" => "success", "data" => $data, "data_pl" => $data_place)));
        }
        else
        {
            return new Response(json_encode(array("msg" => "null")));
        }
    }
    
    /* /dashboard/banner/saveAll */
    public function saveLandingAndPlacementAction(Request $request)
    {
        // $data = '
        // {
        //     "app_id": "com.bukalapak.android",
        //     "deeplink_map": {  
        //         "Google": "http://www.google.com",
        //         "HyperGrowth": "http://hypergrowth.co",
        //         "Dial 911": "tel://911"
        //     },
        //     "placement": {
        //         "placement_names" : [
        //         "Jetpack",
        //         "Memory",
        //         "Santa",
        //         "Video",
        //         "Gumball"
        //       ]
        //     }
        // }';
        
        $data = $request->get('data');
        $decode = json_decode($data, true);
        
        $app_id = $decode['app_id'];
        $placement_name = implode(",",$decode['placement']['placement_names']);
        
        $deeplink = $decode['deeplink_map'];
        $this->date = strtotime(date('Y-m-d h:i:s'));
        $this->created = $this->date;
        $this->updated = $this->date;
        
        try
        {
            /* PLACEMENT */
            $placement = $this->container->get('promo_placement_repository');
            $data      = $placement->findbyCriteria("appId","$app_id");
            
            if(count($data) > 0)
            {
                $orig_placement_name = $data->getPlacementName();
                $save_placement = $orig_placement_name . "," . $placement_name;
                
                $placement->updatePlacement("$app_id", "$save_placement");
            }
            else
            {
                /* PLACEMENT */
                $record = new PromoPlacement();
                $record->setAppId("$app_id");
                $record->setPlacementName("$placement_name");
                $record->setCreated($this->created);
                $record->setUpdated($this->updated);
                
                $placement = $this->container->get('promo_placement_repository');
                $placement->save($record);
                $placement->completeTransaction();
            }
            
            /* LANDING */
            $deeplinkRepo = $this->container->get('promo_landing_repository');
            $data2 = $deeplinkRepo->findbyCriteria("appId","$app_id");
            
            if(count($data2) > 0)
            {
                $orig_deeplink = $data2->getDeeplinkMap();
                $orig_deeplink = rtrim($orig_deeplink,"}");
                
                $new_deeplink = json_encode($deeplink);
                $new_deeplink = ltrim($new_deeplink, "{");
                
                $update_deeplink = $orig_deeplink . "," . $new_deeplink;
                $deeplinkRepo->updateDeepLinkMap("$app_id", "$update_deeplink");
            }
            else
            {
                /* LANDING */
                $landing = new PromoLanding();
                $landing->setAppId(trim($app_id));
                $landing->setDeeplinkMap(trim(json_encode($deeplink)));
                $landing->setCreated($this->created);
                $landing->setUpdated($this->updated);
                
                $deeplinkRepo = $this->container->get('promo_landing_repository');
                $deeplinkRepo->save($landing);
                $deeplinkRepo->completeTransaction();
            }
            
            echo http_response_code(200);
            die;
            
        }
        catch(Exception $e)
        {
            echo http_response_code(500);
            die;
        }
    }
}