<?php

namespace Hyper\EventBundle\Controller\Dashboard;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response,
    GuzzleHttp\Client as GzClient;

class InfraMonitorController extends Controller
{

    public function elbAction(Request $request)
    {
        return $this->render('::infra_monitor/elb_monitor.html.twig');
    }

    public function ec2Action(Request $request)
    {
        return $this->render('::infra_monitor/ec2_monitor.html.twig');
    }

    public function sqsAction(Request $request)
    {
        return $this->render('::infra_monitor/sqs_monitor.html.twig');
    }

    public function rsAction(Request $request)
    {
        return $this->render('::infra_monitor/rs_monitor.html.twig');
    }

    public function cloudvizAction(Request $request)
    {
        try {
            $cloudvizServer = $this->container->getParameter('cloudviz_server');
            $gzClient = new GzClient();
            $resp = $gzClient->get($cloudvizServer, [
                'query' => $request->query->all()
            ]);
            $content = $resp->getBody()->getContents();
        } catch (\Exception $e) {
             $content = '';
        }

        return new Response($content);
    }
}