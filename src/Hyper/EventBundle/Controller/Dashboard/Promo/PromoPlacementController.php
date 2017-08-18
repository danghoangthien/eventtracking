<?php
namespace Hyper\EventBundle\Controller\Dashboard\Promo;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Hyper\Domain\Authentication\Authentication;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Hyper\Domain\Promo_placement\PromoPlacement;
use Hyper\Domain\Promo_landing\PromoLanding;

class PromoPlacementController extends Controller
{
    /**
    * @param ContainerInterface $container
    */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    
    /* /dashboard/banner/placement */
    public function savePlacementAction(Request $request)
    {
        // $data = $request->query->get('data');
        // $json = '{
        //           "app_id" : "com.bukalapak.android",
        //           "placement_names" : [
        //             "Jetpack",
        //             "Memory",
        //             "Santa",
        //             "Video",
        //             "Gumball"
        //           ]
        //         }';
        $json = $request->get('data');
        
        $this->date = strtotime(date('Y-m-d h:i:s'));
        
        $decode = json_decode($json, true);
        $app_id = $decode['app_id'];
        $placement_name = implode(",",$decode['placement_names']);
        $this->created = $this->date;
        $this->updated = $this->date;
        
        try
        {
            if($app_id != "")
            {
                $placement = $this->container->get('promo_placement_repository');
                $data      = $placement->findbyCriteria("appId","$app_id");
                
                if("" != $data)
                {
                    $orig_placement_name = $data->getPlacementName();
                    
                    $orig_placement_name = explode(",", $orig_placement_name);
                    $placement_name = explode(",", $placement_name);
                    
                    $valid_placement = array();
                    $count = count($placement_name);
                    
                    for($i = 0; $i < $count; $i++)
                    {
                        if(!(in_array($placement_name[$i], $orig_placement_name)))
                        {
                            $valid_placement[] = $placement_name[$i];
                        }
                    }
                    
                    if(count($valid_placement) > 0)
                    {
                        $placement_name = implode(",", $valid_placement);
                        $orig_placement_name = implode(",", $orig_placement_name);
                        
                        $save_placement = $orig_placement_name . "," . $placement_name;
                        
                        $placement->updatePlacement("$app_id", "$save_placement");
                    }
                    
                    echo http_response_code(200);
                    die;
                }
                else 
                {
                    $record = new PromoPlacement();
                    $record->setAppId("$app_id");
                    $record->setPlacementName("$placement_name");
                    $record->setCreated($this->created);
                    $record->setUpdated($this->updated);
                    
                    $placement->save($record);
                    $placement->completeTransaction();
                    
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
}