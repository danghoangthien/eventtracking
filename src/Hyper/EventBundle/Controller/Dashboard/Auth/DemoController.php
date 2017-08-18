<?php
namespace Hyper\EventBundle\Controller\Dashboard\Auth;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Hyper\Domain\Authentication\Authentication;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DemoController extends Controller
{   
    /**
    * @param ContainerInterface $container
    */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
        
    public function audienceDeckAction()
    {   
        return $this->render('demo/audience_deck.html.twig');
    }   
    
    public function audienceInterestAction()
    {   
        return $this->render('demo/audience_interest.html.twig', array("active" => "audience_interest"));
    }         
    
    public function customAudienceAction()
    {   
        return $this->render('demo/custom_audience.html.twig');
    }
    
    public function eventMappingAction()
    {   
        return $this->render('demo/event_mapping.html.twig', array("active" => "event_mapping"));
    }
    
    public function mainAction()
    {
        /* ADDED TO REDIRECT TO LOGIN IF THERE IS NO SESSION paul.francisco 2015-12-18 */
        $authController    = $this->get('auth.controller');
        $authIdFromSession = $authController->getLoggedAuthenticationId();
        
        if($authIdFromSession == null)
        {
            $this->url = $this->generateUrl('dashboard_logout');
            return $this->redirect($this->url, 301);
        }
        
        return $this->render('demo/main.html.twig', array("active" => "main_dashboard"));
    }       
    
    public function dataAcquisitionAction()
    {   
        return $this->render('demo/data_acquisition.html.twig', array("active" => "data_acquisition"));
    }   
    
    public function clientManagementAction()
    {   
        return $this->render('demo/client_management.html.twig');
    } 
    
    public function logsAction()
    {   
        return $this->render('demo/logs.html.twig', array('active' => 'logs'));
    }
    
    public function userAccessAction()
    {   
        return $this->render('demo/user_access.html.twig');
    }
    
    public function menuTestAction()
    {   
        return $this->render('demo/dl.html.twig');
    }
}
