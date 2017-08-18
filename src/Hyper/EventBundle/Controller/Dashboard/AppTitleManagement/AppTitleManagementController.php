<?php

namespace Hyper\EventBundle\Controller\Dashboard\AppTitleManagement;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\JsonResponse,
    Hyper\EventBundle\Form\Type\ApplicationTitleType,
    Hyper\Domain\Application\ApplicationTitle,
    Hyper\Domain\Application\ApplicationPlatform,
    Hyper\EventBundle\Service\Cached\App\AppCached;

class AppTitleManagementController extends Controller
{
    const LIST_APP_TITLE_SIZE = 10;

    public function indexAction(Request $request)
    {

        $em = $this->getDoctrine()->getManager('pgsql');
        $appTitleRepo = $em->getRepository('Hyper\Domain\Application\ApplicationTitle');
        $appTitleId = $request->query->get('app_title_id');
        $applicationTitle = '';
        $listAppId = [];
        $mode = 0;
        if ($appTitleId) {
            $applicationTitle = $appTitleRepo->find($appTitleId);
            if (!$applicationTitle instanceof ApplicationTitle) {
                throw new HttpException(Response::HTTP_NOT_FOUND, "Application not found.");
            }
            $mode = 1;
            $listAppFlatform = $em->getRepository('Hyper\Domain\Application\ApplicationPlatform')->findBy([
                'appTitle' => $applicationTitle
            ]);
            if (!empty($listAppFlatform)) {
                foreach ($listAppFlatform as $key => $appPlatform) {
                    $listAppId[$appPlatform->getAppId()] = $appPlatform->getAppId();
                }
            }
        } else {
            $applicationTitle = new ApplicationTitle();
        }
        $form = $this->createForm(new ApplicationTitleType(), array(
            'title' => $applicationTitle->getTitle(),
            'folder' => $applicationTitle->getS3Folder(),
            'description' => $applicationTitle->getDescription(),
            'appId' => $listAppId,
            'status' => $applicationTitle->getStatus(),
        ));

        if( $request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $applicationTitle
                    ->setTitle($form->get('title')->getData())
                    ->setS3Folder($form->get('folder')->getData())
                    ->setDescription($form->get('description')->getData())
                    ->setStatus($form->get('status')->getData());
                $em->persist($applicationTitle);

                $em->flush();
                // Delete reference to app tile
                $query = $em->createQuery('DELETE FROM Hyper\Domain\Application\ApplicationPlatform e WHERE e.appTitle = :appTitleId');
                $query->setParameter('appTitleId', $applicationTitle->getId());
                $query->execute();
                $listAppId = $form->get('appId')->getData();

                if (!empty($listAppId)) {
                    foreach ($listAppId as $appId) {
                        $applicationPlatform = new ApplicationPlatform();
                        $applicationPlatform
                            ->setAppId($appId)
                            ->setAppTitle($applicationTitle);
                        $em->persist($applicationPlatform);
                    }
                    $em->flush();
                    $appCached = new AppCached($this->container);
                    if ($applicationTitle->getStatus() == 1) {
                        foreach ($listAppId as $appId) {
                            $appTitle = $appCached->hget($appId);
                            if (
                                !$appCached->exists() || !$appTitle
                            ) {
                                $appCached->hset($appId, $applicationTitle->getS3Folder());
                            }
                        }
                    } else {
                        foreach ($listAppId as $appId) {
                            $appCached->hdel($appId);
                        }
                    }
                }

                //create app title index on elasticsearch
                $esClient =(new \Hyper\EventBundle\Service\HyperESClient($this->container,[], false))->getClient();
                $esIndex = $esClient->getIndex($applicationTitle->getS3Folder().'_'. $this->container->getParameter('amazon_elasticsearch')['index_version']);
                //$esIndex->delete("test_v1");
                if(!$esIndex->exists()){
                    $esIndex->create();
                    $fileMapping = $this->container->getParameter('kernel.root_dir') .'/../tool/mapping/action/mapping.json';
                    $strMapping = file_get_contents($fileMapping);
                    $arrMapping = json_decode($strMapping, true);
                    foreach ($listAppId as $value) {
                       $esType = $esIndex->getType($value);
                        // Define mapping
                        $mapping = new \Elastica\Type\Mapping();
                        $mapping->setType($esType);
                        // Set mapping
                        $mapping->setProperties($arrMapping);
                        $mapping->send();
                    }
                }
                $this->get('session')->getFlashBag()->add('notice', array(
                    'status' => 'success',
                    'msg' => 'The app title has successfully saved.'
                ));
            }
        }

        $pageNumber = $request->query->getInt('page', 1);
        $paginateData = $appTitleRepo->getPaginateData(
            $pageNumber,
            self::LIST_APP_TITLE_SIZE
        );
        $paginator = $this->get('knp_paginator');
        $listAppTitle = $paginator->paginate(
            [],
            $pageNumber,
            self::LIST_APP_TITLE_SIZE
        );
        $listAppTitle->setItems($paginateData['rows']);
        $listAppTitle->setTotalItemCount($paginateData['total']);
        $listAppId = [];
        $listAppTitleId = [];
        if (!empty($paginateData['rows'])) {
            foreach ($paginateData['rows'] as $appTitle) {
                $listAppTitleId[] = $appTitle['id'];
            }
            $listAppFlatform = $em->getRepository('Hyper\Domain\Application\ApplicationPlatform')->findByAppTitle($listAppTitleId);
            if (!empty($listAppFlatform)) {
                foreach ($listAppFlatform as $appPlatform) {
                    $listAppId[$appPlatform->getAppTitle()->getId()][] = $appPlatform->getAppId();
                }
            }

        }

        return $this->render('::app_title_management/index.html.twig', array(
            'listAppTitle' => $listAppTitle,
            'listAppId' => $listAppId,
            'form' => $form->createView(),
            'mode' => $mode
        ));
    }

    public function deleteAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager('pgsql');
        $appTitleId = $request->attributes->get('app_title_id');
        $listAppId = [];
        if (!empty($appTitleId)) {
            $listAppFlatform = $em->getRepository('Hyper\Domain\Application\ApplicationPlatform')->findByAppTitle($appTitleId);
            if (!empty($listAppFlatform)) {
                foreach ($listAppFlatform as $appPlatform) {
                    $listAppId[] = $appPlatform->getAppId();
                }
            }
            if (!empty($listAppId)) {
                $appCached = new AppCached($this->container);
                foreach ($listAppId as $appId) {
                    $appCached->hdel($appId);
                }
            }
            $query = $em->createQuery('DELETE FROM Hyper\Domain\Application\ApplicationTitle e WHERE e.id = :appTitleId');
            $query->setParameter('appTitleId', $appTitleId);
            $query->execute();
            // Delete reference to app title
            $query = $em->createQuery('DELETE FROM Hyper\Domain\Application\ApplicationPlatform e WHERE e.appTitle = :appTitleId');
            $query->setParameter('appTitleId', $appTitleId);
            $query->execute();
            $query = $em->createQuery('DELETE FROM Hyper\Domain\Client\ClientAppTitle e WHERE e.appTitle = :appTitleId');
            $query->setParameter('appTitleId', $appTitleId);
            $query->execute();
            $this->get('session')->getFlashBag()->add('notice', array(
                'status' => 'success',
                'msg' => "The app title has successfully deleted."
            ));

            return $this->redirect($this->generateUrl('dashboard_app_title_management', $request->query->all()));
        }

    }

    public function checkFolderDuplicationAction(Request $request)
    {
        $folder = $request->query->get('folder');
        $folderExist = $this->checkFolderDuplication($folder);

        if (!$folderExist) {
            $msg = '';
        } else {
            $msg = 'The folder does exist.';
        }

        $resp = [
            'msg' => $msg
        ];


        return new JsonResponse($resp);
    }


    private function checkFolderDuplication($folder)
    {
        $s3Wrapper = $this->container->get('hyper_event_processing.s3_wrapper');
        $s3Client = $s3Wrapper->getS3Client();
        // Register the stream wrapper from a client object
        $s3Client->registerStreamWrapper();
        $s3Uri = 's3://'
                . $this->container->getParameter('amazon_s3_bucket_name')
                . '/' . $folder
                . '/';

        return file_exists($s3Uri);
    }
}