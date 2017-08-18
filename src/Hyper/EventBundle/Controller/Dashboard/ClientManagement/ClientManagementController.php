<?php

namespace Hyper\EventBundle\Controller\Dashboard\ClientManagement;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpFoundation\Request,
    Hyper\EventBundle\Form\Type\ClientType,
    Hyper\Domain\Client\Client,
    Hyper\Domain\Client\ClientAppTitle;

class ClientManagementController extends Controller
{
    const LIST_CLIENT_SIZE = 10;

    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager('pgsql');
        $clientRepo = $em->getRepository('Hyper\Domain\Client\Client');
        $clientId = $request->query->get('client_id');
        $mode = 0;
        $listAppTitle = [];
        if (!empty($clientId)) {
            $client = $clientRepo->find($clientId);
            if (!$client instanceof Client) {
                throw new HttpException(Response::HTTP_NOT_FOUND, "The client not found.");
            }
            $mode = 1;
        } else {
            $client = new Client();
        }
        if ($mode) {
            $listClientAppTitle = $em->getRepository('Hyper\Domain\Client\ClientAppTitle')->findByClient($client);
            if (!empty($listClientAppTitle)) {
                foreach ($listClientAppTitle as $clientAppTitle) {
                    $listAppTitle[] = $clientAppTitle->getAppTitle();
                }
            }
        }
        $usagePlanTypeData = '';
        if ($client->getUsagePlanType()) {
            $usagePlanTypeData = $client->getUsagePlanType();
        } else  {
            $usagePlanTypeData = Client::USAGE_PLAN_TYPE_FREE_PLAN;
        }
        $form = $this->createForm(new ClientType(), [
            'name' => $client->getClientName(),
            'appTitle' => $listAppTitle,
            'accountType' => $client->getAccountType(),
            'usagePlanType' => $usagePlanTypeData,
            'userLimit' => $client->getUserLimit()
        ]);
        if( $request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $listAppTitle = $form->get('appTitle')->getData();
                $listAppPlatform = [];
                $listAppId = [];
                if (!empty($listAppTitle)) {
                    $listAppTitleId = [];
                    foreach ($listAppTitle as $appTitle) {
                        $listAppTitleId[] = $appTitle->getId();
                    }
                    $listAppPlatform = $em->getRepository('Hyper\Domain\Application\ApplicationPlatform')->findByAppTitle($listAppTitleId);
                }
                if (!empty($listAppPlatform)) {
                    foreach ($listAppPlatform as $appPlatform) {
                        $listAppId[] = $appPlatform->getAppId();
                    }
                }
                $listAppIdString = '';
                if (!empty($listAppId)) {
                    $listAppIdString = implode(',', $listAppId);
                }
                $client
                    ->setClientName($form->get('name')->getData())
                    ->setAccountType($form->get('accountType')->getData())
                    ->setClientApp($listAppIdString)
                    ->setUsagePlanType($form->get('usagePlanType')->getData())
                    ->setUserLimit($form->get('userLimit')->getData());
                if (empty($mode)) {
                    $client->setCreated(time());
                }
                $em->persist($client);
                $em->flush();
                // Delete reference to client app tile
                $query = $em->createQuery('DELETE FROM Hyper\Domain\Client\ClientAppTitle e WHERE e.client = :clientId');
                $query->setParameter('clientId', $client->getId());
                $query->execute();
                if (!empty($listAppTitle)) {
                    foreach ($listAppTitle as $appTitle) {
                        $clientAppTitle = new ClientAppTitle();
                        $clientAppTitle
                            ->setClient($client)
                            ->setAppTitle($appTitle);
                        $em->persist($clientAppTitle);
                    }
                    $em->flush();
                }
                $msg = 'The client has successfully saved.';
                if ($mode == 1) {
                    $msg = 'The client has successfully updated.';
                }
                $this->get('session')->getFlashBag()->add('notice', array(
                    'status' => 'success',
                    'msg' => $msg
                ));
                return $this->redirect($this->generateUrl('dashboard_client_management', $request->query->all()));

            }
        }
        $pageNumber = $request->query->getInt('page', 1);
        $paginateData = $clientRepo->getPaginateData(
            $pageNumber,
            self::LIST_CLIENT_SIZE
        );
        $paginator = $this->get('knp_paginator');
        $listClient = $paginator->paginate(
            [],
            $pageNumber,
            self::LIST_CLIENT_SIZE
        );
        $listClient->setItems($paginateData['rows']);
        $listClient->setTotalItemCount($paginateData['total']);
        $listClientId = [];
        $listAppTitle = [];
        if (!empty($paginateData['rows'])) {
            foreach ($paginateData['rows'] as $client) {
                $listClientId[] = $client['id'];
            }
            $listClientAppTitle = $em->getRepository('Hyper\Domain\Client\ClientAppTitle')->findByClient($listClientId);
            if (!empty($listClientAppTitle)) {
                foreach ($listClientAppTitle as $clientAppTitle) {
                    $listAppTitle[$clientAppTitle->getClient()->getId()][] = $clientAppTitle->getAppTitle()->getTitle();
                }
            }
        }

        return $this->render('::client_management/index.html.twig', array(
            'listClient' => $listClient,
            'listAppTitle' => $listAppTitle,
            'form' => $form->createView(),
            'mode' => $mode
        ));
    }

    public function deleteAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager('pgsql');
        $clientId = $request->attributes->get('client_id');
        if (!empty($clientId)) {
            $query = $em->createQuery('DELETE FROM Hyper\Domain\Client\Client e WHERE e.id = :clientId');
            $query->setParameter('clientId', $clientId);
            $query->execute();
            $this->get('session')->getFlashBag()->add('notice', array(
                'status' => 'success',
                'msg' => "The client has successfully deleted."
            ));

            return $this->redirect($this->generateUrl('dashboard_client_management', $request->query->all()));
        }

    }
}