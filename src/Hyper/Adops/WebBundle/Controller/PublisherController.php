<?php

namespace Hyper\Adops\WebBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Hyper\Adops\WebBundle\Domain\AdopsPublisher;

use GuzzleHttp\Client;

/**
 * @author Carl Pham <vanca.vnn@gmail.com>
 */
class PublisherController extends Controller
{
    /**
     * @Route("/adops/publishers", name="publishers")
     * @Method({"GET", "POST"})
     */
    public function indexAction(Request $request)
    {
        $adopsPublisherRepo = $this->get('adops.web.publisher.repository');
        $adopsPublisher = new AdopsPublisher();
        $form = $this->createFormBuilder($adopsPublisher)
                ->add('name', 'text', ['label' => 'Publisher Name'])
                ->add('save', 'submit', ['label' => 'Add'])
                ->getForm();
        $cloned = clone $form;
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $tmpAdopsPublishers = $adopsPublisherRepo->findBy(array('name' => $adopsPublisher->getName()));
            if (count($tmpAdopsPublishers) > 0) {
                $this->addFlash('notice', 'Fail! Record exists!');
            } else {
                $adopsPublisherRepo->create($adopsPublisher);
                $this->addFlash('notice', 'Create publisher successfully!');
                $form = $cloned;
            }
        }
        $adopsPublishers = $adopsPublisherRepo->findAll();
        return $this->render('adops/publisher.html.twig', ['form' => $form->createView(), 'adops_publishers' => $adopsPublishers]);
    }
    
     /**
     * @Route("/adops/publishers/{id}/edit", name="publishers_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction (AdopsPublisher $adopsPublisher, Request $request)
    {
        $adopsPublisherRepo = $this->get('adops.web.publisher.repository');
        $form = $this->createFormBuilder($adopsPublisher)
                ->add('name', 'text', ['label' => 'Publisher Name'])
                ->add('save', 'submit', ['label' => 'Update'])
                ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $tmpAdopsPublishers = $adopsPublisherRepo->findBy(array('name' => $adopsPublisher->getName()));
            if (count($tmpAdopsPublishers) > 0) {
                $this->addFlash('notice', 'Fail! Record exists!');
            } else {
                $adopsPublisherRepo->update($adopsPublisher);
                $this->addFlash('notice', 'Update publisher successfully!');
            }
        }
        $adopsPublishers = $adopsPublisherRepo->findAll();
        return $this->render('adops/publisher.html.twig', ['form' => $form->createView(), 'adops_publishers' => $adopsPublishers]);
    }
    
    /**
     * @Route("/adops/publishers/{id}/delete", name="publishers_delete")
     * @Method({"GET", "POST"})
     */
    public function deleteAction ($id)
    {
        $adopsPublisherRepo = $this->get('adops.web.publisher.repository');
        $adopsPublisher = $adopsPublisherRepo->find($id);
        if (null == $adopsPublisher) {
            $this->addFlash('notice', "Can not delete Publisher with ID: {$id}");
            return $this->redirectToRoute('publishers');
        }
        
        $cusValid = true;
        
        // Check publisher assign in Campaign
        $campaigns = $this->get('adops.web.campaign.repository')->findBy(['publisher'=>$adopsPublisher]);
        if (null != $campaigns){
            $this->addFlash('notice', 'Fail! Can not delete. Publisher assigned in Campaign!');
            $cusValid = false;
        }
        
        // Check assign in Postback
        $assignInPostbacks = $this->get('adops.web.postback.repository')->findBy(['publisher'=>$adopsPublisher]);
        if (null != $assignInPostbacks) {
            $this->addFlash('notice', 'Fail! Can not delete. Publisher assigned in Postback!');
            $cusValid = false;
        }
        
        if ($cusValid) {
            $adopsPublisherRepo->delete($adopsPublisher);
            $this->addFlash('notice', 'Delete publisher successfully!');
        }
    
        return $this->redirectToRoute('publishers');
    }

}
