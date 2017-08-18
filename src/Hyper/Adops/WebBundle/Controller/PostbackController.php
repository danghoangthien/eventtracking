<?php

namespace Hyper\Adops\WebBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Doctrine\ORM\EntityRepository;

use GuzzleHttp\Client;

use Hyper\Adops\WebBundle\Domain\AdopsPostback;

/**
 * @author Carl Pham <vanca.vnn@gmail.com>
 */
class PostbackController extends Controller
{
    /**
     * @Route("/adops/postbacks", name="adops_postbacks")
     * @Method({"GET", "POST"})
     */
    public function indexAction(Request $request)
    {
        $adopsPostbackRepo = $this->get('adops.web.postback.repository');
        $adopsPostback = new AdopsPostback();
        
        $form = $this->createFormBuilder($adopsPostback)
                ->add('publisher', 'entity', array(
                    'class'=>'Hyper\Adops\WebBundle\Domain\AdopsPublisher',
                    'choice_label' => function ($publisher) {
                        return $publisher->getName();
                    },
                    'label' => 'Publisher'
                    ))
                ->add('campaign', 'entity', array(
                    'class'=>'Hyper\Adops\WebBundle\Domain\AdopsCampaign',
                    'query_builder' => function (EntityRepository $er) {
                        $queryBuilder = $er->createQueryBuilder('ac');
                        $expr = $queryBuilder->expr();
                        $queryBuilder->andWhere(
                            $expr->eq('ac.status', ':status')
                        )->setParameter('status', 1);
                        return $queryBuilder;
                    },
                    'choice_label' => function ($campaign) {
                        return $campaign->getName();
                    },
                    'label' => 'Campaign'
                    ))
                ->add('application', 'entity', array(
                    'class'=>'Hyper\Adops\WebBundle\Domain\AdopsApplication',
                    'choice_label' => function ($application) {
                        return $application->getAppName().' '.ucfirst($application->getPlatform());
                    },
                    'label' => 'Application Name'
                    ))
                ->add('postbackUrl', 'url', ['label' => 'Postback URL'])
                ->add('save', 'submit', ['label' => 'Add'])
                ->add('eventType', 'choice', [ 'choice_list' => new ChoiceList(['install', 'in-app-event'], ['Install', 'In-app-event'])])
                ->add('inappevent', 'entity', array(
                    'class'=>'Hyper\Adops\WebBundle\Domain\AdopsInappevent',
                    'choice_label' => function ($inappevent) {
                        return $inappevent->getName();
                    },
                    'label' => 'App Event'
                    )
                );
        $form = $form->getForm();
        $cloned = clone $form;
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ('install' == $adopsPostback->getEventType()) {
               $adopsPostback->setInappevent(null); 
            }
            $adopsPostbackRepo->create($adopsPostback);
            $this->addFlash('notice', 'Create postback successfully!');
            $form = $cloned;
        }
        $adopsPostbacks = $adopsPostbackRepo->findAll();
        // $adopsPostbacks = $adopsPostbackRepo->findBy(array('eventType'=>$eventType));
        return $this->render('adops/postback.html.twig', ['form' => $form->createView(), 'adops_postbacks' => $adopsPostbacks]);
    }
    
    /**
     * @Route("/adops/postbacks/{event_type}", name="postbacks")
     * @Method({"GET", "POST"})
     */
    public function listAction($event_type, Request $request)
    {
        $adopsPostbackRepo = $this->get('adops.web.postback.repository');
        $adopsPostback = new AdopsPostback();
        
        $form = $this->createFormBuilder($adopsPostback)
                ->add('publisher', 'entity', array(
                    'class'=>'Hyper\Adops\WebBundle\Domain\AdopsPublisher',
                    'choice_label' => function ($publisher) {
                        return $publisher->getName();
                    },
                    'label' => 'Publisher'
                    ))
                ->add('campaign', 'entity', array(
                    'class'=>'Hyper\Adops\WebBundle\Domain\AdopsCampaign',
                    'query_builder' => function (EntityRepository $er) {
                        $queryBuilder = $er->createQueryBuilder('ac');
                        $expr = $queryBuilder->expr();
                        $queryBuilder->andWhere(
                            $expr->eq('ac.status', ':status')
                        )->setParameter('status', 1);
                        return $queryBuilder;
                    },
                    'choice_label' => function ($campaign) {
                        return $campaign->getName();
                    },
                    'label' => 'Campaign'
                    ))
                ->add('application', 'entity', array(
                    'class'=>'Hyper\Adops\WebBundle\Domain\AdopsApplication',
                    'choice_label' => function ($application) {
                        return $application->getAppName().' '.ucfirst($application->getPlatform());
                    },
                    'label' => 'Application Name'
                    ))
                ->add('postbackUrl', 'url', ['label' => 'Postback URL'])
                ->add('save', 'submit', ['label' => 'Add']);
        if ('install' == $event_type) {
            $form = $form->add('eventType', 'choice', [ 'choice_list' => new ChoiceList(['install'], ['Install']) ]);
        } else {
            $form = $form->add('eventType', 'choice', [ 'choice_list' => new ChoiceList(['in-app-event'], ['In-app-event'])])
                         ->add('inappevent', 'entity', array(
                            'class'=>'Hyper\Adops\WebBundle\Domain\AdopsInappevent',
                            'choice_label' => function ($inappevent) {
                                return $inappevent->getName();
                            },
                            'label' => 'App Event'
                            ));
        }
        $form = $form->getForm();
        $cloned = clone $form;
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $adopsPostbackRepo->create($adopsPostback);
            $this->addFlash('notice', 'Create postback successfully!');
            $form = $cloned;
        }
        // $adopsPostbacks = $adopsPostbackRepo->findAll();
        $adopsPostbacks = $adopsPostbackRepo->findBy(array('eventType'=>$event_type));
        return $this->render('adops/postback.html.twig', ['form' => $form->createView(), 'adops_postbacks' => $adopsPostbacks]);
    }
    
    /**
     * @Route("/adops/postbacks/{event_type}/{id}/edit", name="postbacks_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction($event_type, AdopsPostback $adopsPostback, Request $request)
    {
        $adopsPostbackRepo = $this->get('adops.web.postback.repository');
        
        $form = $this->createFormBuilder($adopsPostback)
                ->add('publisher', 'entity', array(
                    'class'=>'Hyper\Adops\WebBundle\Domain\AdopsPublisher',
                    'choice_label' => function ($publisher) {
                        return $publisher->getName();
                    },
                    'label' => 'Publisher'
                    ))
                ->add('campaign', 'entity', array(
                    'class'=>'Hyper\Adops\WebBundle\Domain\AdopsCampaign',
                    'query_builder' => function (EntityRepository $er) {
                        $queryBuilder = $er->createQueryBuilder('ac');
                        $expr = $queryBuilder->expr();
                        $queryBuilder->andWhere(
                            $expr->eq('ac.status', ':status')
                        )->setParameter('status', 1);
                        return $queryBuilder;
                    },
                    'choice_label' => function ($campaign) {
                        return $campaign->getName();
                    },
                    'label' => 'Campaign'
                    ))
                ->add('application', 'entity', array(
                    'class'=>'Hyper\Adops\WebBundle\Domain\AdopsApplication',
                    'choice_label' => function ($application) {
                        return $application->getAppName().' '.ucfirst($application->getPlatform());
                    },
                    'label' => 'Application Name'
                    ))
                ->add('postbackUrl', 'url', ['label' => 'Postback URL','required' => false])
                ->add('save', 'submit', ['label' => 'Update']);
        if ('install' == $event_type) {
            $form = $form->add('eventType', 'choice', [ 'choice_list' => new ChoiceList(['install'], ['Install']) ]);
        } else {
            $form = $form->add('eventType', 'choice', [ 'choice_list' => new ChoiceList(['in-app-event'], ['In-app-event'])])
                         ->add('inappevent', 'entity', array(
                            'class'=>'Hyper\Adops\WebBundle\Domain\AdopsInappevent',
                            'choice_label' => function ($inappevent) {
                                return $inappevent->getName();
                            },
                            'label' => 'App Event'
                            ));
        }
        $form = $form->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $adopsPostbackRepo->update($adopsPostback);
            $this->addFlash('notice', 'Update postback successfully!');
        }
        // $adopsPostbacks = $adopsPostbackRepo->findAll();
        $adopsPostbacks = $adopsPostbackRepo->findBy(array('eventType'=>$event_type));
        return $this->render('adops/postback.html.twig', ['form' => $form->createView(), 'adops_postbacks' => $adopsPostbacks]);
    }
    
    /**
     * @Route("/adops/postbacks/{event_type}/{id}/delete", name="postbacks_delete")
     * @Method({"GET", "POST"})
     */
    public function deleteAction($event_type, $id)
    {
        $adopsPostbackRepo = $this->get('adops.web.postback.repository');
        $adopsPostback = $adopsPostbackRepo->find($id);
        if (null == $adopsPostback) {
            $this->addFlash('notice', "Can not delete Postback with ID: {$id}");
            return $this->redirectToRoute('postbacks', array('event_type'=>$event_type));
        }
        
        $adopsPostbackRepo->delete($adopsPostback);
        $this->addFlash('notice', 'Delete postback successfully!');
        return $this->redirectToRoute('postbacks', array('event_type'=>$event_type));
    }

}
