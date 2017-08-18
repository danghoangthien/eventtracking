<?php

namespace Hyper\Adops\WebBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;

use Hyper\Adops\WebBundle\Domain\AdopsApplication;
use Hyper\Domain\Authentication\Authentication;

/**
 * @author Carl Pham <vanca.vnn@gmail.com>
 */
class ReportController extends Controller
{

    /**
     * @Route("/adops/dashboard_old", name="adops_dashboard_old")
     * @Method({"GET", "POST"})
     */
    public function generateAction(Request $request)
    {
        $conn = $this->get('doctrine.dbal.pgsql_connection');

        $session = $this->getRequest()->getSession();
        // $clientApps = $session->get('client_apps');

        $userClientIds = $session->get('user_client_id');
        $userClientIds = explode(',', $userClientIds);
        $userClientIds = "'" .implode("','", $userClientIds) ."'";

        // $clientSql  = $conn->prepare("SELECT DISTINCT id, client_app FROM client WHERE id IN ($userClientIds);");
        $clientSql  = $conn->prepare("SELECT DISTINCT id, client_app FROM client");
        $clientSql->execute();
        $clientAppIds = $clientSql->fetchAll();
        $clientAppIds = $clientAppIds[0]['client_app'];

        $clientApps = explode(',', $clientAppIds);
        $clientApps = "'" .implode("','", $clientApps) ."'";

        $sql = 'SELECT * FROM adops_applications';
        if (!$session->get('user_type')) $sql .= " WHERE app_id IN ($clientApps);";
        $cSql = $conn->prepare($sql);
        $cSql->execute();
        $applications = $cSql->fetchAll();
        $apps[''] = 'Please select app';
        foreach ($applications as $application) {
           $apps[$application['app_id']] = $application['app_name'] .' '. ucfirst($application['platform']);
        }

        $adopsReportRepo = $this->get('adops.web.report.repository');

        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            $data = $data['form'];
            $dateRanges = explode('-', $data['date_range']);
            $appRepo = $this->get('adops.web.application.repository');
            $params = array(
                'created_start' => date('Y-m-d', strtotime($dateRanges[0])),
                'created_end' => date('Y-m-d', strtotime($dateRanges[1])),
                'event_type' => $data['eventType'],
                'app_id' => $data['application'],
                // 'site_id' => $data['publisher'],
            );
            if (!$session->get('user_type')) {
                if (isset($data['publisher'])) {
                    $params['site_id'] = $data['publisher'];
                }

            }
            if (isset($data['campaign']) && !empty($data['campaign'])) {
                $params['c'] = $data['campaign'];
            }
            $adopsReports = $adopsReportRepo->generateReport($params);
            $publisherRepo = $this->get('adops.web.publisher.repository');
            if (null != $adopsReports && !empty($adopsReports)) {
                foreach ($adopsReports as $key => $adopsReport) {
                    $apps = $appRepo->findBy(['appId'=>$adopsReport['app_id']]);
                    $adopsReports[$key]['app_id'] = $apps[0]->getAppName();
                    $adopsReports[$key]['site_id'] = $publisherRepo->find($adopsReport['site_id'])->getName();
                }
            }

            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($adopsReports));
            return $response;
        }

        $form = $this->createFormBuilder()
            ->add('date_range', 'text', ['label' => 'Date Range'])
            /*->add('application', 'entity', array(
                'class'=>'Hyper\Adops\WebBundle\Domain\AdopsApplication',
                'choice_label' => function ($application) {
                    return $application->getAppName().' '.ucfirst($application->getPlatform());
                },
                'choice_value'=>function($application){
                    return $application->getAppId();
                },
                'query_builder' => function(\Hyper\Adops\WebBundle\DomainBundle\Repository\DTApplicationRepository $app, $clientApps) {
                     return $app->createQueryBuilder('app')
                                 ->select('app')
                                 ->where("app.id IN ($clientApps)");
                  },
                'label' => 'Mobile App'
                ))*/
            ->add('application', 'choice', [
                'choices' => $apps
                ])
            // ->add('publisher', 'entity', array(
            //     'class'=>'Hyper\Adops\WebBundle\Domain\AdopsPublisher',
            //     'choice_label' => function ($publisher) {
            //         return $publisher->getName();
            //     },
            //     'label' => 'Publisher'
            //     ))
            ->add('campaign', 'entity', array(
                'class'=>'Hyper\Adops\WebBundle\Domain\AdopsCampaign',
                'choice_label'=>function($campaign){
                    return $campaign->getCode();
                },
                'choice_value'=>function($campaign){
                    return $campaign->getCode();
                },
                'label'=>'Campaign'
                ))
            ->add('eventType', 'choice', [
                'choices' => ['install'=>'Install', 'in-app-event'=>'In-app Event']
                ])
            ->add('generate', 'submit', ['label' => 'Generate'])
            ->getForm();

        if ($session->get('user_type')) {
            $form->add('publisher', 'entity', array(
                'class'=>'Hyper\Adops\WebBundle\Domain\AdopsPublisher',
                'choice_label' => function ($publisher) {
                    return $publisher->getName();
                },
                'label' => 'Publisher'
                ));
        }
        // $form->getForm();
        $form->handleRequest($request);

        return $this->render('adops/dashboard.html.twig', ['form' => $form->createView()]);
    }

    /**
     * @Route("/adops/dashboard", name="adops_dashboard")
     */
    public function indexAction(Request $request)
    {
        $securityContext = $this->get('security.context');
        if (!$securityContext->isGranted('ROLE_USER_ADMIN')) {
            return $this->redirectToRoute('adops_clients_dashboard');
        }

        return $this->render('adops/dashboard.html.twig');
    }

    /**
     * @Route("/adops/clients/dashboard", name="adops_clients_dashboard")
     */
    public function indexClientAction(Request $request)
    {
        return $this->render('adops/clients/select_app.html.twig', ['applications'=>$this->getAppAccess()]);
    }

    /**
     * @Route("/adops/clients/{appId}/report", name="adops_clients_gen_report")
     */
    public function genReportAction(Request $request, $appId)
    {
        $securityContext = $this->get('security.context');
        if ($securityContext->isGranted('ROLE_ADMIN')) {
            return $this->render('adops/dashboard.html.twig');
        }

        $reportRepo = $this->get('adops.web.report.repository');
        $campaignRepo = $this->get('adops.web.campaign.repository');

        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            $dateRanges = explode('-', $data['f_daterange']);
            $params = array(
                'created_start' => date('Y-m-d', strtotime($dateRanges[0])),
                'created_end' => date('Y-m-d', strtotime($dateRanges[1])),
                'app_id' => $appId,
                'quality_benchmark' => $data['f_quality_benchmark']
            );

            if ('all' != $data['f_campaign']) {
                $campaignEntity = $campaignRepo->find($data['f_campaign']);
                $params['c'] = $campaignEntity->getCode();
            }
            if ('all' != $data['f_af_adset']) {
                $params['af_adset'] = $data['f_af_adset'];
            }
            if (isset($data['f_publisher']) && ('all' != $data['f_publisher'])) {
                $params['site_id'] = $data['f_publisher'];
            }

            $reports = $reportRepo->genReport($params);
            $rsOutput = $this->outPutReport($reports);

            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($rsOutput));
            return $response;
        }
        // Get application
        $app = $this->get('adops.web.application.repository')->findOneBy(['appId'=>$appId]);
        $applicationId = $app->getId();

        //Get campaigns
        $adopsCampaigns = $campaignRepo->findBy(['application'=>$applicationId]);

        //Get af_adset
        $qb = $reportRepo->createQueryBuilder('rep');
        $afAdsets = $qb->select('DISTINCT rep.afAdset')
                        ->where(
                            $qb->expr()->eq('rep.appId', ':appId')
                            )
                        ->setParameter('appId', $appId)
                        ->getQuery()
                        ->getResult();

        //Get event_name
        $inAppEvents = $this->get('adops.web.inappevent.repository')->findBy(['application'=>$applicationId]);

        //Get publishers
        $publishers = $this->get('adops.web.publisher.repository')->findAll();

        //Default report
        $now = time();
        $defaultParams = array(
            'created_start' => date('Y-m-d', $now - 604800),// 7 days
            'created_end' => date('Y-m-d', $now),
            'app_id' => $appId,
            'quality_benchmark' => 'all'
        );
        $reports = $reportRepo->genReport($defaultParams);
        $rsOutput = $this->outPutReport($reports);

        return $this->render(
            'adops/clients/gen_report.html.twig',
            [
                'applications' => $this->getAppAccess(),
                'campaigns' => $adopsCampaigns,
                'afAdsets' => $afAdsets,
                'publishers' => $publishers,
                'inappevents' => $inAppEvents,
                'appId' => $appId,
                'reports' => $rsOutput
            ]
        );
    }

    public function outPutReport($reports)
    {
        
        $rsOutput = $titles = [];
        foreach ($reports as $report) {
            $eventName = $report['event_name'];
            if (empty(trim($report['event_name']))) {
                $eventName = $report['event_type'];
            }
            array_push($titles, $eventName);
            $rsOutput['data'][$report['daily']][] = [
                'event_name'=>$eventName,
                'count'=>$report['count'],
                'created'=>strtotime($report['daily'])
            ];
        }
        $rsOutput['title'] = array_values(array_unique($titles));

        return $rsOutput;
    }

    public function getAppAccess()
    {
        $userData = $this->getUser();
        $appAccessIds = json_decode($userData->getAppId());
        return $this->get('adops.web.application.repository')->findBy(['id'=>$appAccessIds]);
    }

    /**
     * @Route("/adops/campaigns/ajax", name="ajax_campaign_by_app")
     * @Method({"POST"})
     */
    public function ajaxGetCampaignByApplication(Request $request)
    {
        $data = $request->request->all();
        $appId = $data['app_id'];
        $apps = $this->get('adops.web.application.repository')->findBy(['appId'=>$data['app_id']]);
        $adopsCampaignRepo = $this->get('adops.web.campaign.repository');
        $adopsCampaigns = $adopsCampaignRepo->findBy(array('application'=>$apps[0]->getId()));

        $output = array();
        foreach ($adopsCampaigns as $adopsCampaign) {
            $output[] = array(
            'id' => $adopsCampaign->getCode(),
            'code' => $adopsCampaign->getCode(),
            );
        }
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent(json_encode($output));
        return $response;
    }

}
