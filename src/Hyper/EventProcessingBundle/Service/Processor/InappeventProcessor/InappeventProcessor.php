<?php

namespace Hyper\EventProcessingBundle\Service\Processor\InappeventProcessor;

use Symfony\Component\DependencyInjection\ContainerInterface
    , Symfony\Component\Filesystem\Filesystem
    , Hyper\EventBundle\Service\Cached\InappeventConfig\InappeventConfigCached;

class InappeventProcessor
{
    protected $container;
    protected $listMessageBody;

    public function __construct(ContainerInterface $container, $listMessageBody)
    {
        $this->container = $container;
        $this->listMessageBody = $listMessageBody;
        if (!$this->listMessageBody) {
            throw new \Exception('No message body');
        }
        $this->ieaConfigCached = new InappeventConfigCached($this->container);
    }

    public function process()
    {
        $listEventNameByAppId = [];
        foreach ($this->listMessageBody as $messageBody) {
            if (!empty($messageBody['event_value']) && !is_array($messageBody['event_value'])) {
                $eventValue = json_decode($messageBody['event_value'], true);
                if(json_last_error() === JSON_ERROR_NONE) {
                    $messageBody['event_value'] = $eventValue;
                }
            }
            $afContentTypeList = [];
            if(isset($messageBody['event_value']['af_content_type'])) {
                $afContentTypeList[] = $messageBody['event_value']['af_content_type'];
            }
            if (isset($listEventNameByAppId[$messageBody['app_id']][$messageBody['event_name']]['content_types'])) {
                $afContentTypeList = array_merge(
                    $afContentTypeList
                    , $listEventNameByAppId[$messageBody['app_id']][$messageBody['event_name']]['content_types']
                );
            }
            $listEventNameByAppId[$messageBody['app_id']][$messageBody['event_name']] = [
                'event_name' => $messageBody['event_name']
                , 'app_name' => $messageBody['app_name']
                , 'app_platform' => $messageBody['platform']
                , 'content_types' => $afContentTypeList
            ];
        }
        foreach ($listEventNameByAppId as $appId => $listEvent) {
            if (
                empty($appId)
                || !$this->ieaConfigCached->exists()
            ) {
                continue;
            }
            $iaeConfig = $this->ieaConfigCached->hget($appId);
            if (!empty($iaeConfig)) {
                $iaeConfig = json_decode($iaeConfig, true);
            } else {
                $iaeConfig = [];
            }
            foreach ($listEvent as $eventNameK => $event) {

                if (isset($iaeConfig[$eventNameK])) { // overwrite content_types on existed event.
                    $iaeConfig[$eventNameK]['content_types'] = array_unique(
                        array_merge($iaeConfig[$eventNameK]['content_types'], $event['content_types'])
                    );
                } else { // create new event
                    $iaeConfig[$eventNameK] = [
                        'event_name' => $event['event_name']
                        , 'event_friendly_name' => ''
                        , 'tag_as_email' => ''
                        , 'tag_as_iap' => ''
                        , 'color' => ''
                        , 'icon' => ''
                        , 'app_name' => $event['app_name']
                        , 'app_platform' => $event['app_platform']
                        , 'content_types' => array_unique($event['content_types'])
                    ];
                }
            }
            $this->ieaConfigCached->hset($appId, json_encode($iaeConfig));
        }
    }

}