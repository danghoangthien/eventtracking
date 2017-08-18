<?php

namespace Hyper\EventBundle\Controller\Dashboard\CsvUpload;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

use Hyper\Domain\CsvUploadLog\CsvUploadLog;

/**
 * @author Carl Pham <vanca.vnn@gmail.com>
 */
class CsvUploadController extends Controller
{
    public function indexAction(Request $request)
    {
        $authRepo  = $this->container->get('auth.controller');
        $authIdFromSession = $authRepo->getLoggedAuthenticationId();
        $userType = $authRepo->getLoggedUserType();
        /* 0 = client access, 2 = client access + clover access */
        if($authIdFromSession == null || $userType == 0 || $userType == 2)
        {
            $this->url = $this->generateUrl('dashboard_logout');
            return $this->redirect($this->url, 301);
        }
        
        $form = $this->createFormBuilder()
                ->add('file', 'file', ['label' => 'File'])
                ->add('application', 'choice', array(
                    'label' => 'Application',
                    'choices' => $this->getChoiceApplication()
                    ))
                ->add('provider', 'choice', [
                    'choice_list' => new ChoiceList(
                        ['appsflyer', 'hasoffer'],
                        ['appsflyer', 'hasoffer']
                        )
                    ])
                ->add('event_type', 'choice', [
                    'choice_list' => new ChoiceList(
                        ['1', '2'],
                        ['install', 'in-app-event']
                        )
                    ])
                ->add('attribute_type', 'choice', [
                    'choice_list' => new ChoiceList(
                        ['0', '1', '2'],
                        ['None', 'Organic', 'Non-Organic']
                        )
                    ])
                ->add('save', 'submit', ['label' => 'Submit'])
                ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $cusValid = true;
            $data = $form->getData();
            $file = $data['file'];
            $appId = $data['application'];
            $provider = $data['provider'];
            $eventType = $data['event_type'];
            $attributeType = $data['attribute_type'];
            
            // Validate file upload
            if (null == $file) {
                $this->addFlash('notice', 'File not empty!');
                return $this->render('csvupload/csvupload.html.twig', ['form' => $form->createView()]);
            }
            /*if ('application/csv' != $file->getMimeType()) {
                $this->addFlash('notice', 'File type is not csv!');
                return $this->render('csvupload/csvupload.html.twig', ['form' => $form->createView()]);
            }*/
            $fileSize = $file->getSize();
            $tmpFilePath = $file->getPathName();
            $totalCsvLine = count(file($tmpFilePath)) -1;
            
            $uploadDir = $this->container->getParameter('kernel.root_dir').'/../web/uploads';
            $fileUploaded = $file->move($uploadDir, $file->getClientOriginalName());
            
            // Write to log
            $csvUploadLog = new CsvUploadLog();
            $timeNow = time();
            $csvUploadLog->setStartTime($timeNow);
            $csvUploadLog->setFileSize($fileSize);
            $csvUploadLog->setTotalCsvRow($totalCsvLine);
            $csvUploadLog->setDetail($provider.'-'.$appId.'-'.$eventType.'-'.$attributeType);

            $csvUploadLogRepo = $this->get('event.csvuploadlog.repository');
            $csvUploadLogRepo->create($csvUploadLog);
            // $this->addFlash('notice', 'Upload successful!');
            
            // Rename file uploaded tmp to id of csvUploadLog
            $newFileName = $uploadDir.'/'.$csvUploadLog->getId().'.'.$fileUploaded->getExtension();
            rename($uploadDir.'/'.$fileUploaded->getBasename(), $newFileName);
            
            // Call command run import to S3
            // http://symfony.com/doc/2.7/cookbook/console/command_in_controller.html
            $kernel = $this->get('kernel');
            $application = new Application($kernel);
            $application->setAutoExit(false);
    
            $input = new ArrayInput(array(
               'command' => 'csv:upload',
               '--file' => $newFileName,
               '--provider' => $provider,
               '--app_id' => $appId,
               '--event_type' => $eventType,
            ));
            // You can use NullOutput() if you don't need the output
            $output = new BufferedOutput();
            $application->run($input, $output);
    
            // return the output, don't use if you used NullOutput()
            $content = $output->fetch();
            $this->addFlash('notice', $content);
            
            return $this->redirectToRoute('event_csvupload_logs_view', array('id'=>$csvUploadLog->getId()));

        }
        
        return $this->render('csvupload/csvupload.html.twig', ['form' => $form->createView()]);
        
    }
    
    public function logAction (Request $request, $id=null)
    {
        $csvUploadLogRepo = $this->get('event.csvuploadlog.repository');
        if(null != $id) {
            $csvUploadLog = $csvUploadLogRepo->find($id);
            if (null == $csvUploadLog) {
                $this->addFlash('notice', "Not found entity by id: '{$id}'");
            }
            $csvUploadLogRepo->delete($csvUploadLog);
            $fileCsvPath = $this->container->getParameter('kernel.root_dir').'/../web/uploads'.$id.'.csv';
            unlink($fileCsvPath);
        }
        $csvUploadLogs = $csvUploadLogRepo->findBy(array(), array('startTime'=>'DESC'), 50, 0);
        return $this->render('csvupload/csvupload_log.html.twig', ['csvupload_logs' => $csvUploadLogs]);
    }
    
    public function viewAction($id)
    {
        $csvUploadLogRepo = $this->get('event.csvuploadlog.repository');
        $csvUploadLog = $csvUploadLogRepo->find($id);
        if (null == $csvUploadLog) {
            $this->addFlash('notice', "Not found entity by id: '{$id}'");
        }
        return $this->render('csvupload/csvupload_log.html.twig', ['csvupload_log_detail'=>1,'csvupload_log' => $csvUploadLog]);
    }
    
    /**
     * Get list applications (app_id, app_name)
     * 
     * @author Carl Pham <vanca.vnn@gmail.com>
     * @return array
     */
    private function getChoiceApplication()
    {
        $flatforms = ['1'=>'IOS', '2'=>'Android'];
        $appRepo = $this->get('application_repository');
        $queryBuilder = $appRepo->createQueryBuilder('ap')
            ->select('DISTINCT ap.appId, ap.appName, ap.platform')
            // ->add('groupBy', 'ap.appName,ap.platform')
            ;
        $appChoices = array();
        $applications = $queryBuilder->getQuery()->getResult();
        foreach ($applications as $application) {
            if ('[App ID Comes Here]' != $application['appId']) {
                $appChoices[$application['appId']] = $application['appName'] . '('.$flatforms[$application['platform']].')';
            }
        }
        return $appChoices;
    }

}
