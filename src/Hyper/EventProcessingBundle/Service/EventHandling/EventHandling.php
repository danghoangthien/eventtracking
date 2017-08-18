<?php

namespace Hyper\EventProcessingBundle\Service\EventHandling;

use Symfony\Component\DependencyInjection\ContainerInterface,
    Hyper\EventProcessingBundle\Service\EventHandling\EventHandlingBase,
    Hyper\EventProcessingBundle\Service\EventHandling\EventHandlingInterface,
    Hyper\Domain\Setting\Setting,
    Hyper\EventAPIBundle\Service\AnalyticMetadata\RecentInAppEventService\RecentInAppEventService,
    Hyper\EventBundle\Service\Cached\AnalyticMetadata\RecentInAppEventCached,
    Hyper\EventProcessingBundle\Service\Processor\IdentityCaptureProcessor\IdentityCaptureProcessor,
    Hyper\EventProcessingBundle\Service\Processor\ActionProcessor\ActionProcessorV2;

class EventHandling extends EventHandlingBase implements EventHandlingInterface
{
    protected $maxNumberOfReceiveMessage = 3000;
    protected $minNumberOfReceiveMessage = 2000;

    public function __construct(
        ContainerInterface $container,
        $queueName,
        $queueNameNext,
        $bucketName
    ) {
        parent::__construct($container, $queueName, $queueNameNext, $bucketName);
        //$this->actionProcessor = $this->container->get('hyper_event_processing.action_processor');
    }

    public function processData()
    {
        // $this->processorManager->addProcessor($this->actionProcessor);
        // list($messagesBodyParsed, $listNewData) = $this->processorManager->processData();
        // $this->messagesBodyParsed = $messagesBodyParsed;
        // $this->listNewData = $listNewData;
        $actionProcessor = new ActionProcessorV2(
            $this->container
            , $this->s3Wrapper
            , $this->redshiftWrapper
            , $this->uniqueId
            , $this->messagesBody
            , $this->s3Url
            , $this->s3Bucket
            , $this->gzClient
            , $this->container->get('action_repository')
        );
        echo "Processor: ". get_class($actionProcessor) . "\n";
        list($messagesBodyParsed, $listNewData) = $actionProcessor->handle();
        $this->messagesBodyParsed = $messagesBodyParsed;
        $this->listNewData = $listNewData;
        $this->storeRecentEvent();

        $identityCaptureProcessor = new IdentityCaptureProcessor($this->container, $this->messagesBodyParsed);
        echo "Processor: ". get_class($identityCaptureProcessor) . "\n";
        $identityCaptureProcessor->process();
    }

    public function validMessageBody()
    {

    }

    public function storeDataToS3Bucket()
    {

    }

    public function storeDataToRedshift()
    {

    }

    public function getSettingKey()
    {
        return Setting::EVENT_HANDLING_TYPE_KEY;
    }

    public function storeDataToElasticSearch()
    {
        // $esParameters = $this->container->getParameter('amazon_elasticsearch');
        // $actions = [];
        // if (!empty($this->listFileToWrite['actions'])) {
        //     $actions = $this->listFileToWrite['actions'];
        // }
        // if (empty($actions)) {
        //     return;
        // }
        // // collect index
        // $indices = [];
        // foreach ($actions as $action) {
        //     if (empty($this->appCached->hget($action['app_id']))) {
        //         continue;
        //     }
        //     $index = $this->appCached->hget($action['app_id']);
        //     $indices[$index][$action['app_id']][] = $action;
        // }
        // if (!empty($indices)) {
        //     foreach ($indices as $index => $listAppId) {
        //         $index = strtolower($index) . '_'. $esParameters['index_version'];
        //         $esIndexEndpoint = $index;
        //         if (!$this->checkIndexES($esIndexEndpoint)) {
        //             continue;
        //         }
        //         foreach ($listAppId as $appId => $listAction) {
        //             if (empty($listAction)) {
        //                 continue;
        //             }
        //             $esBulkEndpoint = $esIndexEndpoint.'/'.$appId.'/_bulk';
        //             $esJson = $this->makeJsonES($index, $appId, $listAction);
        //             try {
        //                 $response = $this->gzClient->request(
        //                     $esBulkEndpoint
        //                     , 'POST'
        //                     , $esJson
        //                 );
        //             } catch (\GuzzleHttp\Exception\ClientException $e) {
        //                 $this->log(
        //                     $e
        //                     , 'es-bulk-actions-fail'
        //                     , $esJson
        //                 );
        //             } catch (\GuzzleHttp\Exception\ServerException $e) {
        //                 $this->log(
        //                     $e
        //                     , 'es-bulk-actions-fail'
        //                     , $esJson
        //                 );
        //             }
        //         }
        //     }
        // }
    }

    public function afterSendDataToSqs()
    {

    }

    private function storeRecentEvent()
    {
        $actions = $this->listNewData;
        if (empty($actions)) {
            return;
        }
        $listRecentInAppEvent = [];
        foreach ($actions as $action) {
            if ($action['action_type'] != 1) {
                 $listRecentInAppEvent[$action['app_id']][] = [
                    'id' => $action['id']
                    , 'happened_at' => $action['happened_at']
                    , 'event_name' => $action['event_name']
                    , 'amount_usd' => $action['amount_usd']
                ];
            }
        }
        if (!empty($listRecentInAppEvent)) {
            $recentInAppEventCached  = new RecentInAppEventCached($this->container);
            foreach ($listRecentInAppEvent as $appId => $listRecentInAppEventByApp) {
                $listRecentInAppEventFromCached = $recentInAppEventCached->hget($appId);
                if (empty($listRecentInAppEventFromCached)) {
                    $listRecentInAppEventFromCached = [];
                } else {
                    $listRecentInAppEventFromCached = json_decode($listRecentInAppEventFromCached, true);
                }
                $listRecentInAppEventMerged = array_merge($listRecentInAppEventByApp, $listRecentInAppEventFromCached);
                usort($listRecentInAppEventMerged, function ($a, $b) {
        		    return $b['happened_at'] - $a['happened_at'];
        		});
        		$listRecentInAppEventPushIntoCached = array_slice($listRecentInAppEventMerged, 0, RecentInAppEventService::RECENT_LIMIT);
        		$recentInAppEventCached->hset($appId, json_encode($listRecentInAppEventPushIntoCached));
            }
        }
    }

    public function log(\Exception $e, $prefix, $content)
    {
         $this->container
            ->get('hyper_event_processing.logger_wrapper')->log(
                $e,
                $this->container->getParameter('amazon_s3_bucket_event_handling'),
                $prefix,
                $content
            );
    }
}