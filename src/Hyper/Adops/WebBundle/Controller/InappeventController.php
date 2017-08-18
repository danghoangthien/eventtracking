<?php

namespace Hyper\Adops\WebBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;

use GuzzleHttp\Client;

use Hyper\Adops\WebBundle\Domain\AdopsInappevent;
use Hyper\Adops\WebBundle\Domain\AdopsApplication;

/**
 * @author Carl Pham <vanca.vnn@gmail.com>
 */
class InappeventController extends Controller
{
    /**
     * @Route("/adops/inappevents", name="inappevents")
     * @Method({"GET", "POST"})
     */
    public function indexAction(Request $request)
    {
        $adopsInappeventRepo = $this->get('adops.web.inappevent.repository');
        $adopsInappevent = new AdopsInappevent();
        $form = $this->createFormBuilder($adopsInappevent)
                ->add('application', 'entity', array(
                    'class'=>'Hyper\Adops\WebBundle\Domain\AdopsApplication',
                    'choice_label' => function ($application) {
                        return $application->getAppName().' '.ucfirst($application->getPlatform());
                    },
                    'label' => 'Mobile App'
                    ))
                ->add('name', 'text', ['label' => 'In App Event Name'])
                ->add('save', 'submit', ['label' => 'Add'])
                ->getForm();
        $cloned = clone $form;
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $tmpAdopInappevents = $adopsInappeventRepo->findBy(array(
                'name' => $adopsInappevent->getName(),
                'application' => $adopsInappevent->getApplication()
                ));
                
            if (count($tmpAdopInappevents) > 0) {
                $this->addFlash('notice', 'Fail! Record exists!');
            } else {
                $adopsInappeventRepo->create($adopsInappevent);
                $this->addFlash('notice', 'Create in app event successfully!');
                $form = $cloned;
            }
        }
        $adopsInappevents = $adopsInappeventRepo->findAll();
        return $this->render('adops/in_app_event.html.twig', ['form' => $form->createView(), 'adops_inappevents' => $adopsInappevents]);
    }
    
    /**
     * @Route("/adops/inappevents/{id}/edit", name="inappevents_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction (AdopsInappevent $adopsInappevent, Request $request)
    {
        $adopsInappeventRepo = $this->get('adops.web.inappevent.repository');
        $form = $this->createFormBuilder($adopsInappevent)
                ->add('application', 'entity', array(
                    'class'=>'Hyper\Adops\WebBundle\Domain\AdopsApplication',
                    'choice_label' => function ($application) {
                        return $application->getAppName().' '.ucfirst($application->getPlatform());
                    },
                    'label' => 'Mobile App'
                    ))
                ->add('name', 'text', ['label' => 'In App Event Name'])
                ->add('save', 'submit', ['label' => 'Update'])
                ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $tmpAdopInappevents = $adopsInappeventRepo->findBy(array(
                'name' => $adopsInappevent->getName(),
                'application' => $adopsInappevent->getApplication()
                ));
                
            if (count($tmpAdopInappevents) > 0) {
                $this->addFlash('notice', 'Fail! Record exists!');
            } else {
                $adopsInappeventRepo->update($adopsInappevent);
                $this->addFlash('notice', 'Update in app event successfully!');
            }
        }
        $adopsInappevents = $adopsInappeventRepo->findAll();
        return $this->render('adops/in_app_event.html.twig', ['form' => $form->createView(), 'adops_inappevents' => $adopsInappevents]);
    }
    
    /**
     * @Route("/adops/inappevents/{id}/delete", name="inappevents_delete")
     * @Method({"GET", "POST"})
     */
    public function deleteAction ($id)
    {
        $adopsInappeventRepo = $this->get('adops.web.inappevent.repository');
        $adopsInappevent = $adopsInappeventRepo->find($id);
        if (null == $adopsInappevent) {
            $this->addFlash('notice', "Can not delete In App Event with ID: {$id}");
            return $this->redirectToRoute('inappevents');
        }
        // Check assign in Postback
        $assignInPostbacks = $this->get('adops.web.postback.repository')->findBy(['inappevent'=>$adopsInappevent]);
        if ($assignInPostbacks != null) {
            $this->addFlash('notice', 'Fail! Can not delete. In-app event assigned in Postback!');
        } else {
            $adopsInappeventRepo->delete($adopsInappevent);
            $this->addFlash('notice', 'Delete in app event successfully!');
        }
        
        return $this->redirectToRoute('inappevents');
    }

    /**
     * @Route("/adops/inappevents/application", name="inappevents_by_application")
     * @Method({"POST"})
     */
    public function ajaxGetEventType(Request $request)
    {
        $data = $request->request->all();
        $appId = $data['app_id'];
        $app = $this->get('adops.web.application.repository')->find($appId);
        $adopsInappeventRepo = $this->get('adops.web.inappevent.repository');
        $adopsInappevents = $adopsInappeventRepo->findBy(array('application'=>$app));

        $output = array();
        foreach ($adopsInappevents as $adopsInappevent) {
            $output[] = array(
            'id' => $adopsInappevent->getId(),
            'name' => $adopsInappevent->getName(),
            );
        }
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent(json_encode($output));
        return $response;
    }
}
