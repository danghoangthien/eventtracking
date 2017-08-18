<?php
namespace Hyper\EventBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Hyper\EventBundle\Service\EventProcess;


class ExportActionDataController extends Controller
{
    /**
    * @param ContainerInterface $container
    */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    
    public function indexAction(Request $request){
        //$request->request->set('page_num', $newValue);
        $filename = 'action_data_'.time().'.csv';
        $request->request->set('export_data',true);
        $request->request->set('page_num',null);
        $request->request->set('row_number',null);
        $data = $this->get('hyper_event.display_action_data_controller')->indexAction($request);
        $response = $this->render('export_action_data.csv.twig', $data);
        
        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Description', 'Submissions Export');
        $response->headers->set('Content-Disposition', 'attachment; filename='.$filename);
        $response->headers->set('Content-Transfer-Encoding', 'binary');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        
        return $response;
    }

}
