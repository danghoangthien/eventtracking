<?php

namespace Hyper\Adops\WebBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;

use GuzzleHttp\Client;

use Hyper\Adops\WebBundle\Domain\AdopsCampaign;

/**
 * @author Carl Pham <vanca.vnn@gmail.com>
 */
class CampaignController extends Controller
{
    
    /**
     * @Route("/adops/campaigns", name="campaigns")
     * @Method({"GET", "POST"})
     */
    public function indexAction(Request $request)
    {
        $adopsCampaignRepo = $this->get('adops.web.campaign.repository');
        $adopsCampaign = new AdopsCampaign();
        $form = $this->createFormBuilder($adopsCampaign)
                ->add('name', 'text', ['label' => 'Campaign Name'])
                ->add('code', 'text', ['label' => 'Campaign Code'])
                ->add('trackingUrl', 'url', ['label' => 'Tracking URL','required' => false])
                ->add('payout', 'number', ['label' => 'Payouts'])
                ->add('application', 'entity', array(
                    'class'=>'Hyper\Adops\WebBundle\Domain\AdopsApplication',
                    'choice_label' => function ($application) {
                        return $application->getAppName().' '.ucfirst($application->getPlatform());
                    },
                    'label' => 'Mobile App'
                    ))
                ->add('publisher', 'entity', array(
                    'class'=>'Hyper\Adops\WebBundle\Domain\AdopsPublisher',
                    'choice_label' => function ($publisher) {
                        return $publisher->getName();
                    },
                    'label' => 'Publisher'
                    ))
                ->add('status', 'choice', [
                    'choice_list' => new ChoiceList(
                        ['1', '0'],
                        ['Active', 'InActive']
                        )
                    ])
                ->add('save', 'submit', ['label' => 'Add'])
                ->getForm();
        $cloned = clone $form;
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $cusValid = true;
            
            if($cusValid) {
                $adopsCampaignRepo->create($adopsCampaign);
                $this->addFlash('notice', 'Create campaign successfully!');
                $form = $cloned;
            }
        }
        $adopsCampaigns = $adopsCampaignRepo->findAll();
        return $this->render('adops/campaign.html.twig', ['form' => $form->createView(), 'adops_campaigns' => $adopsCampaigns]);
    }
    
    /**
     * @Route("/adops/campaigns/{id}/edit", name="campaigns_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction (AdopsCampaign $adopsCampaign, Request $request)
    {
        $adopsCampaignRepo = $this->get('adops.web.campaign.repository');
        // Status inactive: not allow change tracking url
        $allow = ['label' => 'Tracking URL','required' => false];
        if(!$adopsCampaign->getStatus()) {
            $allow = ['label' => 'Tracking URL','required' => false, 'read_only' => true];
        }
        $form = $this->createFormBuilder($adopsCampaign)
                ->add('name', 'text', ['label' => 'Campaign Name'])
                ->add('code', 'text', ['label' => 'Campaign Code'])
                ->add('trackingUrl', 'url', $allow)
                ->add('payout', 'number', ['label' => 'Payouts'])
                ->add('application', 'entity', array(
                    'class'=>'Hyper\Adops\WebBundle\Domain\AdopsApplication',
                    'choice_label' => function ($application) {
                        return $application->getAppName().' '.ucfirst($application->getPlatform());
                    },
                    'label' => 'Mobile App'
                    ))
                ->add('publisher', 'entity', array(
                    'class'=>'Hyper\Adops\WebBundle\Domain\AdopsPublisher',
                    'choice_label' => function ($publisher) {
                        return $publisher->getName();
                    },
                    'label' => 'Publisher'
                    ))
                ->add('status', 'choice', [
                    'choice_list' => new ChoiceList(
                        ['1', '0'],
                        ['Active', 'InActive']
                        )
                    ])
                ->add('save', 'submit', ['label' => 'Update'])
                ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $cusValid = true;
            
            if($cusValid) {
                $adopsCampaignRepo->update($adopsCampaign);
                $this->addFlash('notice', 'Update campaign successfully!');
            }
        }
        $adopsCampaigns = $adopsCampaignRepo->findAll();
        return $this->render('adops/campaign.html.twig', ['form' => $form->createView(), 'adops_campaigns' => $adopsCampaigns]);
    }
    
   /**
     * @Route("/adops/campaigns/{id}/delete", name="campaigns_delete")
     * @Method({"GET", "POST"})
     */
    public function deleteAction ($id)
    {
        $adopsCampaignRepo = $this->get('adops.web.campaign.repository');
        $adopsCampaign = $adopsCampaignRepo->find($id);
        if (null == $adopsCampaign) {
            $this->addFlash('notice', "Can not delete campaign with ID: {$id}");
            return $this->redirectToRoute('campaigns');
        }
        $cusValid = true;
        // Check status
        if ($adopsCampaign->getStatus() == 0) {
            $cusValid = false;
            $this->addFlash('notice', 'Fail! Can not delete. Campaign actived!');
        }
        // Check assign in Postback
        $assignInPostbacks = $this->get('adops.web.postback.repository')->findBy(['campaign'=>$adopsCampaign]);
        if ($assignInPostbacks != null) {
            $cusValid = false;
            $this->addFlash('notice', 'Fail! Can not delete. Campaign assigned in Postback!');
        }
        if($cusValid) {
            $adopsCampaignRepo->delete($adopsCampaign);
            $this->addFlash('notice', 'Delete campaign successfully!');
        }
        
        return $this->redirectToRoute('campaigns');
    }

}
