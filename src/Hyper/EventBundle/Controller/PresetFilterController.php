<?php

namespace Hyper\EventBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// DT Repository
use Hyper\DomainBundle\Repository\Filter\DTFilterRepository;

// Entities
use Hyper\Domain\Filter\Filter;
use Hyper\Domain\Device\Device;

class PresetFilterController extends Controller
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

    }
    
    public function showListAction(Request $request)
    {
        $filterRepo = $this->container->get('filter_repository');
        $page = $request->get('page');
        //$page = 0;
        $rpp = 5;
        // TODO - get authentication_id by session
        $authenticationId = !empty($request->get('authentication_id'))?$request->get('authentication_id'):null;
        
        $result = $filterRepo->getResultAndCount($page,$rpp,$authenticationId);
        $rows = $result['rows'];
        $totalCount = $result['total'];
        $paginator = new \lib\Paginator($page, $totalCount, $rpp);
        //var_dump($paginator);
        $pageList = $paginator->getPagesList();
        return $this->render('filter/filter_index.html.twig', 
            array(
                'rows' => $rows, 
                'paginator' => $pageList, 
                'cur' => $page, 
                'total' => $paginator->getTotalPages(), 
                'authentication_id'=>$authenticationId
                )
        );
    }
    
    public function showAddAction(Request $request)
    {
        $deviceRepo = $this->get('device_repository');
        $countries = $deviceRepo->getActiveCountries();
        //\Doctrine\Common\Util\Debug::dump($countries);
        return $this->render('filter/filter_add.html.twig', 
            array(
                'active_country_list' => $countries,
                'active_platform' => array(
                    Device::IOS_PLATFORM_CODE => Device::IOS_PLATFORM_NAME,
                    Device::ANDROID_PLATFORM_CODE => Device::ANDROID_PLATFORM_NAME
                )
            )
        );
    }
    
    public function executeAddAction(Request $request)
    {
        
        try {
            $filterMetadata = array();
            
            //$authenticationId = $request->get('authentication_id');
            $authenticationId = 1;
            $presetName = $request->get('preset_name');
            $filterRepo = $this->get('filter_repository');
            $reservedFilter = $filterRepo->getByIdentifier($authenticationId,$presetName);
            
            if ($reservedFilter instanceof Filter) {
                throw new \Exception("Duplicate Preset name");
            }
            else {
                
                $countryCodes = $request->get('country_codes');
                $platformIds = $request->get('platform_ids');
                $filterRepo = $this->get('filter_repository');
                
                $filter = new Filter();
                $filter->setAuthenticationId($authenticationId);
                $filter->setPresetName($presetName);
                if (!empty($countryCodes)){
                    $filterMetadata['device.countryCode'] = array(
                        'expression' => 'IN',
                        'value' => $countryCodes
                    );
                }
                if (!empty($platformFilter)) {
                    $filterMetadata['device.platformId'] = array(
                        'expression' => 'IN',
                        'value' => $platformIds
                    );
                }
                $filter->setFilterMetadata($filterMetadata);
                $filterRepo->save($filter);
                $filterRepo->completeTransaction();
                return new Response('Preset filter created success fully');
            }
        } catch (\Exception $ex) {
           
            if ($ex->getMessage() == 'Duplicate Preset name'){
                $response = new Response($ex->getMessage());
                $response->setStatusCode(400);
                return $response;
            } else {
                $response = new Response($ex->getMessage());
                $response->setStatusCode(500);
                return $response;
            }
        }
        
        
        
    }
    
    public function executeDeleteAction(Request $request)
    {
        
    }

}
