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
class QualityAuditorController extends Controller
{
    /**
     * @Route("/adops/clients/{appId}/quality", name="adops_quality_auditor")
     */
    public function indexAction(Request $request, $appId)
    {
        $securityContext = $this->get('security.context');
        if ($securityContext->isGranted('ROLE_USER_ADMIN')) {
            return $this->redirectToRoute('adops_dashboard');
        }

        $campaignRepo = $this->get('adops.web.campaign.repository');
        $appRepo = $this->get('adops.web.application.repository');
        $reportRepo = $this->get('adops.web.report.repository');
        
        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            $data['app_id'] = $appId;
            $niceParams = $this->prepareParams($data);
            
            $params = $niceParams['params'];
            $days = $niceParams['days'];
            $indicators = $niceParams['indicators'];
            $titles = $niceParams['titles'];
            
            $reports = $reportRepo->genReportQuality($params);
        
            $rsOutput = $this->outPutReport($reports, $days, $indicators, $titles);
            
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json');
            $response->setContent(json_encode($rsOutput));
            return $response;
        }
        
        // Default
        $now = time();
        $data = [
            'app_id' => $appId,
            'created_start' => date('Y-m-d', $now - 3*24*60*60),// 3 days ago
            'created_end' => date('Y-m-d', $now - 24*60*60),// yesterday
            'f_benchmarkdate' => date('Y-m-d', $now - 3*24*60*60),
            'f_campaign' => 'all',
            'f_publisher' => 'all',
            'f_af_sub1' => 'all',
            'f_primary_indicator' => 0,
            'f_secondary_indicator' => 0,
        ];
            
        $niceParams = $this->prepareParams($data);
        $reports = $reportRepo->genReportQuality($niceParams['params']);
        $rsOutput = $this->outPutReport($reports, $niceParams['days'], $niceParams['indicators'], $niceParams['titles']);

        // Get application
        $app = $appRepo->findOneBy(['appId'=>$appId]);
        $applicationId = $app->getId();

        //Get campaigns
        $adopsCampaigns = $campaignRepo->findBy(['application'=>$applicationId]);
        
        //Get publishers
        $publishers = $this->get('adops.web.publisher.repository')->findAll();
        
        //Get event_name
        $inAppEvents = $this->get('adops.web.inappevent.repository')->findBy(['application'=>$applicationId]);
        
        //Get af_adset
        $qb = $reportRepo->createQueryBuilder('rep');
        $afSub1s = $qb->select('rep.afSub1, rep.siteId')
                        ->where(
                            $qb->expr()->eq('rep.appId', ':appId')
                            )
                        ->groupBy('rep.afSub1')
                        ->addGroupBy('rep.siteId')
                        ->setParameter('appId', $appId)
                        ->getQuery()
                        ->getResult();
        
        return $this->render(
            'adops/clients/quality_auditor.html.twig',
            [
                'applications' => $this->getAppAccess(),
                'appId' => $appId,
                'campaigns' => $adopsCampaigns,
                'publishers' => $publishers,
                'inappevents' => $inAppEvents,
                'afSub1s' => $afSub1s,
                'rsOutput' => $rsOutput,
            ]
        );
    }
    
    public function prepareParams($data)
    {
        // Test
        // $data['f_campaign'] = 'all';
        // $data['app_id'] = 'com.woi.liputan6.android';
        // $data['f_af_sub1'] = '1149_1373665043';
        // $data['f_benchmarkdate'] = '03/23/2016';
        //End Test
        $campaignRepo = $this->get('adops.web.campaign.repository');
        $benchmarkDate = date('Y-m-d', strtotime($data['f_benchmarkdate']));
        $now = time();
        // $now = strtotime('03/08/2016');
        $days = [
            'benchmarkdate' => $benchmarkDate,
            'yesterday' => date('Y-m-d', $now - 24*60*60),
            'twoDayAgo' => date('Y-m-d', $now - 2*24*60*60),
            'threeDayAgo' => date('Y-m-d', $now - 3*24*60*60),
            ];
        $params = array(
            'app_id' => $data['app_id'],
            'created_start' => $days['threeDayAgo'],
            'created_end' => $days['yesterday'],
            'benchmarkdate' => $days['benchmarkdate']
        );
        if ('all' != $data['f_campaign']) {
            $campaignEntity = $campaignRepo->find($data['f_campaign']);
            $params['c'] = $campaignEntity->getCode();
        }
        if ('all' != $data['f_publisher']) {
            $params['site_id'] = $data['f_publisher'];
        }
        if ('all' != $data['f_af_sub1']) {
            $params['af_sub1'] = $data['f_af_sub1'];
        }
        
        $indicators['install'] = ['install', -1, 0];
        $titles = [];
        if ('non' != $data['f_primary_indicator']) {
            $params['primary_event_name'] = $data['f_primary_indicator'];
            
            if (!isset($data['f_primary_kpi'])) {
                $data['f_primary_kpi'] = 0;
            }
            $indicators['primary'] = [$data['f_primary_indicator'], $data['f_primary_formula'], $data['f_primary_kpi']];
            
            $tmpTitleNice = ucwords(str_replace('_', ' ', $data['f_primary_indicator']));
            $tmpTitle = $tmpTitleNice . ' %';
            if (!$data['f_primary_formula']) {
                $tmpTitle = $tmpTitleNice . ' Per User';
            }
            $titles['primary'] = $tmpTitle;
        }
        if ('non' != $data['f_secondary_indicator']) {
            $params['secondary_event_name'] = $data['f_secondary_indicator'];
            
            if (!isset($data['f_second_kpi'])) {
                $data['f_second_kpi'] = 0;
            }
            $indicators['second'] = [$data['f_secondary_indicator'], $data['f_second_formula'], $data['f_second_kpi']];
            
            $tmpTitleNice = ucwords(str_replace('_', ' ', $data['f_secondary_indicator']));
            $tmpTitle = $tmpTitleNice . ' %';
            if (!$data['f_second_formula']) {
                $tmpTitle = $tmpTitleNice . ' Per User';
            }
            $titles['second'] = $tmpTitle;
        }
        
        return ['days'=>$days, 'params'=>$params, 'indicators'=>$indicators, 'titles'=>$titles];
    }
    
    public function outPutReport($results = [], $days, $indicators = [], $titles = [])
    {
        
        $dataOutput = [];
        if (empty($results)) {
            return ['status'=>false, 'message'=> 'Empty data!'];
        }
        
        // $days = [
        //         'benchmarkdate' => $benchmarkDate,
        //         'yesterday' => date('Y-m-d', $now - 24*60*60),
        //         'twoDayAgo' => date('Y-m-d', $now - 2*24*60*60),
        //         'threeDayAgo' => date('Y-m-d', $now - 3*24*60*60),
        //         ];
        
        // Group by af_sub1
        foreach ($results as $result) {
            foreach ($indicators as $kIndicator => $vIndicator) {
                if ($result['event_name'] != $vIndicator[0]) {
                    continue;
                }
                foreach ($days as $day => $date) {
                    if ($date != $result['daily']) {
                        continue;
                    }
                    $dataOutput[$result['af_sub1']][$kIndicator][$day] = [
                            'event_name' => $result['event_name'],
                            'count' => $result['count'],
                            'daily' => $result['daily']
                        ];
                }
            }
            
        }
        foreach ($dataOutput as $afSub1 => $data) {
            foreach ($indicators as $kIndicator=>$vIndicator) {
                if (isset($data[$kIndicator])) {
                    continue;
                }
                $dataOutput[$afSub1][$kIndicator] = [
                    'benchmarkdate' => ['count'=>0],
                    'yesterday' => ['count'=>0],
                    'twoDayAgo' => ['count'=>0],
                    'threeDayAgo' => ['count'=>0]
                ];
            }
            foreach ($data as $event => $day) {
                // fix miss current/primary/second
                if (!isset($day['benchmarkdate'])) {
                    $dataOutput[$afSub1][$event]['benchmarkdate'] = [
                        'count' => 0,
                        'daily' => $days['benchmarkdate']
                        ];
                }
                if (!isset($day['yesterday'])) {
                    $dataOutput[$afSub1][$event]['yesterday'] = [
                        'count' => 0,
                        'daily' => $days['yesterday']
                        ];
                }
                if (!isset($day['twoDayAgo'])) {
                    $dataOutput[$afSub1][$event]['twoDayAgo'] = [
                        'count' => 0,
                        'daily' => $days['twoDayAgo']
                        ];
                }
                if (!isset($day['threeDayAgo'])) {
                    $dataOutput[$afSub1][$event]['threeDayAgo'] = [
                        'count' => 0,
                        'daily' => $days['threeDayAgo']
                        ];
                }
            }
        }
        
        foreach ($dataOutput as $afSub1 => $data) {
            $installData = $data['install'];
            $benchMarkInstallCount = $installData['benchmarkdate']['count'];
            $yesterdayInstallCount = $installData['yesterday']['count'];
            $twoDayAgoInstallCount = $installData['twoDayAgo']['count'];
            $threeDayAgoInstallCount = $installData['threeDayAgo']['count'];
            $avg = ($yesterdayInstallCount+$twoDayAgoInstallCount+$threeDayAgoInstallCount)/3;
            $dataOutput[$afSub1]['install']['avg']['count'] = round($avg, 2);
            
            $rowColor = ['white', 'yellow', 'red'];
            $kpi = 0;
            foreach ($data as $event => $day) {
                if ('install' == $event) {
                    continue;
                }
                $formula = $indicators[$event][1];
                if (!isset($day['benchmarkdate'])) {
                    $benchMarkCount = 0;
                } else {
                    $benchMarkCount = $dataOutput[$afSub1][$event]['benchmarkdate']['count'];
                }
                
                if (!isset($day['yesterday'])) {
                    $yesterdayCount = 0;
                } else {
                    $yesterdayCount = $dataOutput[$afSub1][$event]['yesterday']['count'];
                }
                
                if (!isset($day['twoDayAgo'])) {
                    $twoDayAgoCount = 0;
                } else {
                    $twoDayAgoCount = $dataOutput[$afSub1][$event]['twoDayAgo']['count'];
                }
                if (!isset($day['threeDayAgo'])) {
                    $threeDayAgoCount = 0;
                } else {
                    $threeDayAgoCount = $dataOutput[$afSub1][$event]['threeDayAgo']['count'];
                }
                
                if ($formula) { // %:inappevent/install
                    
                    if ($benchMarkInstallCount == 0) {
                        $benchMarkCount = 0;
                    } else {
                        $benchMarkCount = $benchMarkCount/$benchMarkInstallCount;
                    }
                    
                    if ($yesterdayInstallCount == 0) {
                        $yesterdayCount = 0;
                    } else {
                        $yesterdayCount = $yesterdayCount/$yesterdayInstallCount;
                    }
                    
                    if ($twoDayAgoInstallCount == 0) {
                        $twoDayAgoCount = 0;
                    } else {
                        $twoDayAgoCount = $twoDayAgoCount/$twoDayAgoInstallCount;
                    }
                    
                    if ($threeDayAgoInstallCount == 0) {
                        $threeDayAgoCount = 0;
                    } else {
                        $threeDayAgoCount = $threeDayAgoCount/$threeDayAgoInstallCount;
                    }
                    
                    // * 100%
                    $avgCount = ($yesterdayCount+$twoDayAgoCount+$threeDayAgoCount)/3;
                    
                    $avgCount = round($avgCount*100, 2);
                    $benchMarkCount = round($benchMarkCount*100, 2);
                    $yesterdayCount = round($yesterdayCount*100, 2);
                    $twoDayAgoCount = round($twoDayAgoCount*100, 2);
                    $threeDayAgoCount = round($threeDayAgoCount*100, 2);
                    
                    if (
                        $benchMarkCount < $indicators[$event][2] 
                        && $yesterdayCount < $indicators[$event][2]
                        && $avgCount < $indicators[$event][2]
                    ) {
                        $kpi ++;
                    }
                    
                    $avgCount = $avgCount . '%';
                    $benchMarkCount = $benchMarkCount . '%';
                    $yesterdayCount = $yesterdayCount . '%';
                    $twoDayAgoCount = $twoDayAgoCount . '%';
                    $threeDayAgoCount = $threeDayAgoCount . '%';
                    
                } else {// #: install/inappevent
                    if ($benchMarkCount != 0) {
                        $benchMarkCount = $benchMarkInstallCount/$benchMarkCount;
                    }
                    if ($yesterdayCount != 0) {
                        $yesterdayCount = $yesterdayInstallCount/$yesterdayCount;
                    }
                    if ($twoDayAgoCount != 0) {
                        $twoDayAgoCount = $twoDayAgoInstallCount/$twoDayAgoCount;
                    }
                    if ($threeDayAgoCount != 0) {
                        $threeDayAgoCount = $threeDayAgoInstallCount/$threeDayAgoCount;
                    }
                    
                    $avgCount = ($yesterdayCount+$twoDayAgoCount+$threeDayAgoCount)/3;
                    $avgCount = round($avgCount, 2);
                    $benchMarkCount = round($benchMarkCount, 2);
                    $yesterdayCount = round($yesterdayCount, 2);
                    $twoDayAgoCount = round($twoDayAgoCount, 2);
                    $threeDayAgoCount = round($threeDayAgoCount, 2);
                    if (
                        $benchMarkCount < $indicators[$event][2] 
                        && $yesterdayCount < $indicators[$event][2]
                        && $avgCount < $indicators[$event][2]
                    ) {
                        $kpi ++;
                    }
                    
                }
                $dataOutput[$afSub1][$event]['benchmarkdate']['count'] = $benchMarkCount;
                $dataOutput[$afSub1][$event]['yesterday']['count'] = $yesterdayCount;
                $dataOutput[$afSub1][$event]['avg']['count'] = $avgCount;
            }
            $dataOutput[$afSub1]['kpi'] = $rowColor[$kpi];
        }
        return [
            'status'=>true,
            'data'=>$dataOutput,
            'title'=>$titles,
            'debug'=>['results'=>$results],
        ];
    }
    
    public function getAppAccess()
    {
        $userData = $this->getUser();
        $appAccessIds = json_decode($userData->getAppId());
        return $this->get('adops.web.application.repository')->findBy(['id'=>$appAccessIds]);
    }
    
    /**
     * @Route("/adops/clients/ajax/af_sub1", name="adops_ajax_af_sub1")
     */
    public function getAfSub1ByAppIdAndSiteId(Request $request)
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        
        $data = $request->request->all();
        if (!isset( $data['app_id']) || !isset($data['site_id'])) {
            $dataOutput = ['status'=>false];
            $response->setContent(json_encode($dataOutput));
            return $response;
        }
        $appId = $data['app_id'];
        $siteId = $data['site_id'];
        $reportRepo = $this->get('adops.web.report.repository');
        //Get af_adset
        $qb = $reportRepo->createQueryBuilder('rep');
        $qb->select('rep.afSub1, rep.siteId')
            ->where($qb->expr()->eq('rep.appId', ':appId'))
            ->groupBy('rep.afSub1')
            ->addGroupBy('rep.siteId')
            ->setParameter('appId', $appId);
        if ('all' != $data['site_id']) {
            $qb->andWhere($qb->expr()->eq('rep.siteId', ':siteId'))
                ->setParameter('siteId', $siteId);
        }
        $afSub1s = $qb->getQuery()->getResult();
        
        $response->setContent(json_encode(['status'=>true, 'data'=>$afSub1s]));
        return $response;
    }
}