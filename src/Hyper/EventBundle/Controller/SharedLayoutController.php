<?php

namespace Hyper\EventBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Hyper\Domain\Authentication\Authentication;

class SharedLayoutController extends Controller
{
    public function renderNavbarTopAction(Request $request)
    {
        $this->auth = $this->get('security.context')->getToken()->getUser();
        $isUserAK = false;
        if (in_array(Authentication::ROLE_CLIENT, $this->auth->getRoles())) {
            $isUserAK = true;
        }
        return $this->render('::layout_ak/_nav_top.html.twig', array(
            'welcome_text' => $this->getWelcomeText(true)
            , 'isUserAK' => $isUserAK
        ));
    }

    public function renderNavbarLeftAction(Request $request)
    {
        $auth = $this->get('security.context')->getToken()->getUser();
        if (in_array(Authentication::ROLE_ADMIN, $auth->getRoles())) {
            $view = '_nav_left_admin.html.twig';
        } else {
            $view = '_nav_left_client.html.twig';
        }
        $active = '';
        $routeName = $request->attributes->get('_route');
        if (preg_match('/^dashboard_main*/', $routeName)) {
            $active = 'dashboard_main';
        } elseif (preg_match('/^dashboard_user_access_management*/', $routeName)) {
           $active = 'user_access_management';
        } elseif (preg_match('/^dashboard_filter_card_builder/', $routeName)) {
            $active = 'audience_card_creator';
        } elseif (preg_match('/^dashboard_filter_audience_deck/', $routeName)) {
            $active = 'audience_deck';
        } elseif (preg_match('/^dashboard_audience_spotlight*/', $routeName)) {
            $active = 'audience_spotlight';
        } elseif (preg_match('/^dashboard_banner/', $routeName)) {
            $active = 'promo_banner';
        } elseif (preg_match('/^dashboard_push/', $routeName)) {
            $active = 'push_notification';
        } elseif (preg_match('/^dashboard_data_acquisition/', $routeName)) {
            $active = 'data_acquisition';
        } elseif (preg_match('/^dashboard_app_title_management/', $routeName)) {
            $active = 'app_title_management';
        } elseif (preg_match('/^dashboard_client_management/', $routeName)) {
            $active = 'client_management';
        } elseif (preg_match('/^dashboard_client_action_show/', $routeName)) {
            $active = 'data_logs';
        } elseif (preg_match('/^dashboard_import_data_csvupload$/', $routeName)) {
            $active = 'csv_upload';
        } elseif (preg_match('/^dashboard_import_data_csvupload_logs*/', $routeName)) {
            $active = 'csv_upload_logs';
        } elseif (preg_match('/^dashboard_server_monitor$/', $routeName)) {
            $active = 'server_monitor';
        } elseif (preg_match('/^dashboard_analytics_display$/', $routeName)) {
            $active = 'analytics_display';
        } elseif (preg_match('/^dashboard_infrastructure_monitor_elb$/', $routeName)) {
            $active = 'infrastructure_monitor_elb';
        } elseif (preg_match('/^dashboard_infrastructure_monitor_ec2$/', $routeName)) {
            $active = 'infrastructure_monitor_ec2';
        } elseif (preg_match('/^dashboard_infrastructure_monitor_sqs$/', $routeName)) {
            $active = 'infrastructure_monitor_sqs';
        } elseif (preg_match('/^dashboard_infrastructure_monitor_rs$/', $routeName)) {
            $active = 'infrastructure_monitor_rs';
        } elseif (preg_match('/^dashboard_inappevent_config$/', $routeName)) {
            $active = 'inappevent_config';
        }

        return $this->render("::layout_ak/{$view}", array(
            'active' =>  $active
        ));
    }

    public function renderProfileAction(Request $request)
    {
        $this->auth = $this->get('security.context')->getToken()->getUser();
        $aksrcBucket = $this->container->getParameter('amazon_s3_aksrc');
        $authImagePath = implode('/', array(
            $aksrcBucket['base_url'],
            $aksrcBucket['folder']['user_images']
        ));
        $authImageUrl = $this->get('templating.helper.assets')
        ->getUrl('/bundles/hyperevent/img/userprofile.png');
        if (!empty($this->auth->getImgPath())) {
            $authImageUrl = $authImagePath . '/' . $this->auth->getImgPath();
        }

        return $this->render('::layout_ak/_profile.html.twig', array(
            'auth' => $this->auth,
            'welcome_text' => $this->getWelcomeText(),
            'auth_image_url' => $authImageUrl
        ));
    }

    public function renderUserTutorialAction(Request $request)
    {
        $auth = $this->get('security.context')->getToken()->getUser();
        $showTutorial = 1;
        if ($auth->isDemoAccount()) {
            $session = $request->getSession();
            $sessionId = $session->getId();
            $sessionKey = 'show_tutorial_' . $sessionId;
            if (!$session->has($sessionKey)) {
                $session->set($sessionKey, 0);
            }
            $showTutorial = $session->get($sessionKey);
        } else {
            if (
                in_array(Authentication::ROLE_CLIENT, $auth->getRoles())
                && $auth->getShowTutorial() != 1
                ) {
                $showTutorial = 0;
            }
        }

        $isUserAK = false;
        if (in_array(Authentication::ROLE_CLIENT, $auth->getRoles())) {
            $isUserAK = true;
        }
        return $this->render('::layout_ak/_show_tutorial.html.twig', array(
            'showTutorial' => $showTutorial
            , 'isUserAK' => $isUserAK
        ));
    }

    private function getWelcomeText($top = '') {
        $welcomeText = '';
        if (in_array(Authentication::ROLE_ADMIN, $this->auth->getRoles())) {
            $welcomeText = 'Warehouse Admin';
        } else {
            $clientId = $this->auth->getClientId();
            if (empty($clientId)) {
                $welcomeText = 'Audience Kit';
            }
            $client = $this->get('client_repository')->find($clientId);
            if (empty($client)) {
                $welcomeText = 'Audience Kit';
            }
            $welcomeText = $client->getClientName() . ($top ? ' Audience Kit' :' Team');
        }

        return $welcomeText;
    }
}
