<?php

namespace Hyper\EventBundle\Controller\Dashboard\InappeventConfig;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpKernel\Exception\HttpException,
    Symfony\Component\HttpFoundation\Request,
    Hyper\Domain\InappeventConfig\InappeventConfig,
    Hyper\EventBundle\Service\Cached\InappeventConfig\InappeventConfigCached as InappeventConfigCached;

/**
 * Page configuation in-app events
 * @author CarlPham <vanca.vnn@gmail.com>
 */
class InappeventConfigController extends Controller
{
    public function indexAction(Request $request)
    {
        $auth = $this->get('security.context')->getToken()->getUser();
        $clientId = $_SESSION['client_id'];

        $client = $this->get('client_repository')->find($clientId);
        if (null == $client) {
            return $this->render('inappevent_config/inappevent_config.html.twig',['applications' => []]);
        }

        //$listClientApp = $client->getClientApp();
        /*
        if (empty($listClientApp)) {
            return $this->render('inappevent_config/inappevent_config.html.twig',['applications' => []]);
        }*/
        //$listAppId = explode(',', $listClientApp);
        $listAppId = $_SESSION['client_data']['listAppId'];
        $applications = $this->get('application_repository')->getListAppByAppId($listAppId);
        if ($request->isMethod('POST')) {
            $rsOutput = ['status'=>false];
            $data = $request->request->all();
            if (empty($data['app_id'])) {
                return $this->responseAjax($rsOutput);
            }
            $rsOutput = $this->getEventByAppId($data['app_id']);
            return $this->responseAjax($rsOutput);
        }

        return $this->render(
            'inappevent_config/inappevent_config.html.twig',
            [
                'applications' => $applications
                , 'isDemoAccount' => $auth->isDemoAccount()
            ]
        );
    }

    public function getEventByAppId($appId)
    {
        $eventColors = [
            ['value'=>'#093145', 'content'=>'#093145'],
            ['value'=>'#0d3d56', 'content'=>'#0d3d56'],
            ['value'=>'#3c6478', 'content'=>'#3c6478'],
            ['value'=>'#107896', 'content'=>'#107896'],
            ['value'=>'#1496bb', 'content'=>'#1496bb'],
            ['value'=>'#43abc9', 'content'=>'#43abc9'],
            ['value'=>'#829356', 'content'=>'#829356'],
            ['value'=>'#a3b86c', 'content'=>'#a3b86c'],
            ['value'=>'#b5c689', 'content'=>'#b5c689'],
            ['value'=>'#bca136', 'content'=>'#bca136'],
            ['value'=>'#ebc944', 'content'=>'#ebc944'],
            ['value'=>'#efd469', 'content'=>'#efd469'],
            ['value'=>'#c2571a', 'content'=>'#c2571a'],
            ['value'=>'#f26d21', 'content'=>'#f26d21'],
            ['value'=>'#f58b4c', 'content'=>'#f58b4c'],
            ['value'=>'#9a2617', 'content'=>'#9a2617'],
            ['value'=>'#c02f1d', 'content'=>'#c02f1d'],
            ['value'=>'#cd594a', 'content'=>'#cd594a'],
            ['value'=>'#f1f3f4', 'content'=>'#f1f3f4'],
        ];
        $rsOutput = [];
        $eventNotIns = ['install'];
        $eventConfigs = $this->get('inappevent_config_repository')->findBy(['appId'=>$appId], ['eventName'=>'ASC']);

        $tmpId = 1;
        if (!empty($eventConfigs)) {
            foreach ($eventConfigs as $eventConfig) {
                $rsOutput['data'][] = [
                    'app_id' => $appId,
                    'event_name' => $eventConfig->getEventName(),
                    'event_friendly_name' => $eventConfig->getEventFriendlyName(),
                    'tas_as_email' => $eventConfig->getTagAsEmail(),
                    'tas_as_iap' => $eventConfig->getTagAsIap(),
                    'color' => $eventConfig->getColor(),
                    'icon' => $eventConfig->getIcon(),
                    'tag_as_email_id' => 'email_id_'.$tmpId,
                    'tag_as_iap_id' => 'iap_id_'.$tmpId,
                    'no_tag_id' => 'no_tag_id_'.$tmpId,
                    'total_event_color' => count($eventColors),
                    'color_options' => $eventColors,
                ];

                if (1 == $eventConfig->getTagAsEmail()) {
                    $rsOutput['tag_as']['tas_as_email'] = 'email_id_'.$tmpId;
                }
                if (1 == $eventConfig->getTagAsIap()) {
                    $rsOutput['tag_as']['tag_as_iap'] = 'iap_id_'.$tmpId;
                }
                $tmpId ++;
                array_push($eventNotIns, $eventConfig->getEventName());
            }
        }
        // $events = $this->get('action_repository')->getEventNames([$appId], $eventNotIns);
        $events = $this->get('action_repository')->getEventNameByAppIds([$appId], $eventNotIns);
        foreach ($events as $event) {
            $rsOutput['data'][] = [
                'event_name'=>$event,
                'app_id'=>$appId,
                'tag_as_email_id' => 'email_id_'.$tmpId,
                'tag_as_iap_id' => 'iap_id_'.$tmpId,
                'no_tag_id' => 'no_tag_id_'.$tmpId,
                'total_event_color' => count($eventColors),
                'color_options' => $eventColors,
            ];
            $tmpId ++;
        }
        return $rsOutput;
    }

    public function ajaxSaveEventAction(Request $request)
    {
        $auth = $this->get('security.context')->getToken()->getUser();
        if ($auth->isDemoAccount()) {
            throw new HttpException(Response::HTTP_FORBIDDEN, "Sorry you cannot make changes as this feature is disabled in demo mode.");
        }
        $data = $request->request->all();
        if (empty($data['app_id']) || empty($data['event_name'])) {
            return $this->responseAjax(['status'=>false]);
        }
        $iaeConfigRepo = $this->get('inappevent_config_repository');
        $iaeConfig = $iaeConfigRepo->findOneBy(['appId'=>$data['app_id'], 'eventName'=>$data['event_name']]);
        $convert = true;
        if (null == $iaeConfig) {
            $iaeConfig = new InappeventConfig();
            $iaeConfig->setAppId($data['app_id'])
                ->setEventName($data['event_name']);
        } else {
            $convert = false;
        }
        if (!empty($data['event_tag']) && 'tag_as_email' == $data['event_tag']) {
            $iaeConfig->setTagAsEmail(1);
        }
        if (!empty($data['event_tag']) && 'tag_as_iap' == $data['event_tag']) {
            $iaeConfig->setTagAsIap(1);
        } else {
            $convert = false;
        }
        $iaeConfig->setEventFriendlyName($data['event_friendly_name'])
                  ->setColor($data['event_color'])
                  ->setIcon($data['icon']);

        $rs = $iaeConfigRepo->update($iaeConfig);
        if ($rs == true) {
            $this->updateCache($data['app_id']);
        }
        if ($convert) {
            $sqsWraper = $this->container->get('hyper_event_processing.sqs_wrapper');
            $sqsWraper->sendMessageToQueue(
                $this->container->getParameter('amazon_sqs_queue_inappevent_config')
                , [
                    'event_name' => $iaeConfig->getEventName()
                    , 'app_id' => $iaeConfig->getAppId()
                ]
            );

        }
        $rsOutput = ['status'=>$rs, 'data'=>$data];
        return $this->responseAjax($rsOutput);
    }

    public function responseAjax($bodyData)
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent(json_encode($bodyData));
        return $response;
    }

    public function updateCache($appId)
    {
        $iaeConfigRepo = $this->get('inappevent_config_repository');
        $iaeConfigs = $iaeConfigRepo->findBy(['appId'=>$appId]);
        $iaeConfigCache = new InappeventConfigCached($this->container);
        $iaeConfigData = $iaeConfigCache->hget($appId);
        if (!empty($iaeConfigData)) {
            $iaeConfigData = json_decode($iaeConfigData, true);
        } else {
            $iaeConfigData = [];
        }

        if (!empty($iaeConfigs)) {
            foreach ($iaeConfigs as $iaeConfig) {
                $afContentTypeList = [];
                if (isset($iaeConfigData[$iaeConfig->getEventName()]['content_types'])) {
                    $afContentTypeList = $iaeConfigData[$iaeConfig->getEventName()]['content_types'];
                }
                $iaeConfigData[$iaeConfig->getEventName()] = [
                    'event_name' => $iaeConfig->getEventName(),
                    'event_friendly_name' => $iaeConfig->getEventFriendlyName(),
                    'tag_as_email' => $iaeConfig->getTagAsEmail(),
                    'tag_as_iap' => $iaeConfig->getTagAsIap(),
                    'color' => $iaeConfig->getColor(),
                    'icon' => $iaeConfig->getIcon(),
                    'content_types' => $afContentTypeList
                ];
            }

            $iaeConfigCache->hset($appId, json_encode($iaeConfigData));
        }
    }
}