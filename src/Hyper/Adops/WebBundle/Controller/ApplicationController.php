<?php

namespace Hyper\Adops\WebBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;

use GuzzleHttp\Client;

use Hyper\Adops\WebBundle\Domain\AdopsApplication;

/**
 * @author Carl Pham <vanca.vnn@gmail.com>
 */
class ApplicationController extends Controller
{
    /**
     * @Route("/adops/applications", name="applications")
     * @Method({"GET", "POST"})
     */
    public function indexAction(Request $request)
    {
        $adopsApplicationRepo = $this->get('adops.web.application.repository');
        $adopsApplication = new AdopsApplication();
        $form = $this->createFormBuilder($adopsApplication)
                ->add('app_name', 'text', ['label' => 'App Name'])
                ->add('app_id', 'text', ['label' => 'App ID'])
                ->add('platform', 'choice', [
                    'choice_list' => new ChoiceList(
                        ['android', 'ios'],
                        ['Android', 'IOS']
                        )
                    ])
                ->add('save', 'submit', ['label' => 'Add'])
                ->getForm();
        $cloned = clone $form;
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $cusValid = true;
            // Check unique App Id
            $tmpAdopsApplications = $adopsApplicationRepo->findBy(array('appId' => $adopsApplication->getAppId()));
            if (count($tmpAdopsApplications) > 0) {
                $this->addFlash('notice', 'Fail! Application Id exists!');
                $cusValid = false;
            }
            
            // Check unique App Name in Platform
            $tmpAdopsApplication = $adopsApplicationRepo->findBy(array(
                'appName' => $adopsApplication->getAppName(), 
                'platform' => $adopsApplication->getPlatform() 
                ));
            if (count($tmpAdopsApplications) > 0) {
                $this->addFlash('notice', 'Fail! Application Name in Platform exists!');
                $cusValid = false;
            }

            if($cusValid) {
                $adopsApplicationRepo->create($adopsApplication);
                $this->addFlash('notice', 'Create application successfully!');
                $form = $cloned;
            }
        }
        $adopsApplications = $adopsApplicationRepo->findAll();
        return $this->render('adops/application.html.twig', ['form' => $form->createView(), 'adops_applications' => $adopsApplications]);
    }
    
    /**
     * @Route("/adops/applications/{id}/edit", name="applications_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction (AdopsApplication $adopsApplication, Request $request)
    {
        $adopsApplicationRepo = $this->get('adops.web.application.repository');
        $form = $this->createFormBuilder($adopsApplication)
                ->add('app_name', 'text', ['label' => 'App Name'])
                ->add('app_id', 'text', ['label' => 'App ID'])
                ->add('platform', 'choice', [
                    'choice_list' => new ChoiceList(
                        ['android', 'ios'],
                        ['Android', 'IOS']
                        )
                    ])
                ->add('save', 'submit', ['label' => 'Update'])
                ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $adopsApplicationRepo->update($adopsApplication);
            $this->addFlash('notice', 'Update application successfully!');
        }
        $adopsApplications = $adopsApplicationRepo->findAll();
        return $this->render('adops/application.html.twig', ['form' => $form->createView(), 'adops_applications' => $adopsApplications]);
    }
    
   /**
     * @Route("/adops/applications/{id}/delete", name="applications_delete")
     * @Method({"GET", "POST"})
     */
    // public function deleteAction (AdopsApplication $adopsApplication, Request $request)
    public function deleteAction ($id)
    {
        $adopsApplicationRepo = $this->get('adops.web.application.repository');
        $adopsApplication = $adopsApplicationRepo->find($id);
        if (null == $adopsApplication) {
            $this->addFlash('notice', "Can not delete application with ID: {$id}");
            return $this->redirectToRoute('applications');
        }
        
        $cusValid = true;

        // Check application assign in In app event
        $inappevents = $this->get('adops.web.inappevent.repository')->findBy(['application'=>$adopsApplication]);
        if (null != $inappevents){
            $this->addFlash('notice', 'Fail! Can not delete. Application assigned in In app event!');
            $cusValid = false;
        }
        
        // Check application assign in Campaign
        $campaigns = $this->get('adops.web.campaign.repository')->findBy(['application'=>$adopsApplication]);
        if (null != $campaigns){
            $this->addFlash('notice', 'Fail! Can not delete. Application assigned in Campaign!');
            $cusValid = false;
        }
        
        // Check assign in Postback
        $assignInPostbacks = $this->get('adops.web.postback.repository')->findBy(['application'=>$adopsApplication]);
        if (null != $assignInPostbacks) {
            $this->addFlash('notice', 'Fail! Can not delete. Application assigned in Postback!');
            $cusValid = false;
        } 
        
        if ($cusValid) {
            $adopsApplicationRepo->delete($adopsApplication);
            $this->addFlash('notice', 'Delete application successfully!');
        }
        
        return $this->redirectToRoute('applications');
    }

}
