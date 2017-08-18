<?php
namespace Hyper\Adops\APIBundle\Handler;

use Symfony\Component\DependencyInjection\ContainerInterface;

use GuzzleHttp\Client;
use Hyper\Adops\APIBundle\Handler\SqsHandlerInterface;

/**
 *
 * @author Carl Pham <vanca.vnn@gmail.com>
 */
class SqsSendToPublisherHandler extends SqsContainerExtendsHandler implements SqsHandlerInterface
{
    
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
    }
    
    public function getMessage($queueParameter, $numberLoop)
    {
        return $this->getMessageFromSQS($queueParameter, $numberLoop);
    }
    
    public function perform($data = [])
    {
        if (empty($data)) {
            return ['status'=>false];
        }
        $dataLog['detail'] = ['data'=>$data];
        
        $reportParams = $this->parseReportQueryParam($data);
        $data = array_merge($reportParams, $data);
        $adopsPostbackRepo = $this->container->get('adops.web.postback.repository');
        $postbacks = $adopsPostbackRepo->getPostbackCustom($reportParams);
        if (count($postbacks) > 0) {
            foreach ($postbacks as $postback) {
                $data['postback_url'] = $postback['postback_url'];
                $pbUrlForward = $this->replacePostbackUrlForward($data);
                $status = $this->sendDataToPublisher($data, $pbUrlForward);

                $data['status'] = $status;
                $data['postback_url'] = $pbUrlForward;
                $data['campaign_payout'] = $postback['payout'];
                $dataSendSQS = $this->prepareDataSendToSQS($data);
                $this->sendToSqs('amazon_sqs_report_queue',$data);
            }
        } else {
            $this->sendToSqs('amazon_sqs_report_queue',$data);
            $dataLog['detail'] = ['error'=>'Not found Postback Entity', 'param'=>$reportParams, 'data'=>$data];
        }
        
        // Send to SQS log
        $this->sendToSqs('amazon_sqs_log_queue', $dataLog);
        
        return ['status'=>true];
    }
    
    /**
     * Parse and map params for query to postback in database.
     * 
     * @param  array $data
     * @return array
     */
    public function parseReportQueryParam($data)
    {
        $queryParams = [];
        $queryParams['app_id'] = $data['app_id'];
        $queryParams['af_siteid'] = $data['af_siteid'];

        $queryParams['event_type'] = 'aff_lsr' == $data['type'] ? 'install' : 'in-app-event';
        if (isset($data['goal_id'])) {
            $queryParams['event_name'] = $data['goal_id'];
        }
        if (isset($data['c'])) {
            $queryParams['code'] = $data['c'];
        }

        return $queryParams;
    }
    
    /**
     * Replace and prepare postback url forward to publisher.
     * 
     * @param  array $data
     * @return string
     */
    public function replacePostbackUrlForward($data)
    {
        $pbUrl = $data['postback_url'];
        if (isset($data['token']) && !empty($data['token'])) {
            $pos = strpos($pbUrl, 'token={aff_sub}');
            if ($pos !== false) {
                $pbUrl = str_replace("token={aff_sub}",'token='.$data['token'],$pbUrl);
            }
        }
        if (isset($data['transaction_id']) && !empty($data['transaction_id'])) {
            $pos = strpos($pbUrl, '{Click_id}');
            if ($pos !== false) {
                $pbUrl = str_replace("{Click_id}",$data['transaction_id'],$pbUrl);
            }
        }
        if (isset($data['af_adset']) && !empty($data['af_adset'])) {
            $pos = strpos($pbUrl, '{af_adset}');
            if ($pos !== false) {
                $pbUrl = str_replace("{af_adset}",$data['af_adset'],$pbUrl);
            }
        }
        if (isset($data['af_sub1']) && !empty($data['af_sub1'])) {
            $pos = strpos($pbUrl, '{af_sub1}');
            if ($pos !== false) {
                $pbUrl = str_replace("{af_sub1}",$data['af_sub1'],$pbUrl);
            }
        }
        if (isset($data['c']) && !empty($data['c'])) {
            $pos = strpos($pbUrl, '{campaign_code}');
            if ($pos !== false) {
                $pbUrl = str_replace("{campaign_code}",$data['c'],$pbUrl);
            }
        }

        return $pbUrl;
    }
    
    public function sendDataToPublisher($data, $pbUrl)
    {
        $client = new Client([
            'timeout'  => 10.0,
        ]);
        try {
            $response = $client->request('POST', $pbUrl, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept'     => '*/*',
                ],
                'body' => json_encode($data)
            ]);
            return [
                'status'=>true,
                'data'=>[
                    'response_code'=>$response->getStatusCode(),
                ]
            ];
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            return [
                'status'=>true,
                'data'=>[
                    'response_code'=>0,
                    'message'=>'Caught response: ' . $e->getResponse()->getStatusCode()
                ]
            ];
        }
    }
    
    public function prepareDataSendToSQS($params)
    {
        $paramReport = array(
            'event_type' => $params['event_type'],
            'app_id' => $params['app_id'],
            'site_id' => $params['af_siteid']
        );
        if (isset($params['payout'])) $paramReport['campaign_payout'] = $params['payout'];
        if (isset($params['postback_url'])) $paramReport['postback_url'] = $params['postback_url'];
        if (isset($params['status'])) $paramReport['status'] = $params['status'];
        if (isset($params['c']) && !empty($params['c'])) $paramReport['c'] = $params['c'];
        if (isset($params['af_adset']) && !empty($params['af_adset'])) $paramReport['af_adset'] = $params['af_adset'];
        if (isset($params['af_sub1']) && !empty($params['af_sub1'])) $paramReport['af_sub1'] = $params['af_sub1'];
        if (isset($params['event_name']) && !empty($params['event_name'])) $paramReport['event_name'] = $params['event_name'];

        return $paramReport;
    }
}