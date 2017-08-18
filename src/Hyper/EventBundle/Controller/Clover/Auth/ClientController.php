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

class ClientController extends Controller
{
    /**
    * @param ContainerInterface $container
    */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    
    /* clover/client/main */
    public function renderMainAction(Request $request)
    {
        $this->user = $request->request->get('l');
        $this->pass = $request->request->get('xyz');
        $this->org  = $request->request->get('pub');
        
        return $this->render('clover/client/main_dashboard.html.twig', array('l'=>$this->user, 'xyz'=>$this->pass, 'pub'=>$this->org));
    }  
    
    /* clover/client/user_history */
    public function renderUserHistoryAction(Request $request)
    {
        return $this->render('clover/client/user_history.html.twig');
    }  
}
