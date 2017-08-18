<?php

namespace Hyper\EventProcessingBundle\Service\LoggerWrapper;

interface LoggerWrapperInterface
{
    public function log(\Exception $e, $bucket, $prefix, $content);
    public function logInvalidContent($error, $bucket, $prefix, $clientName, $appId, $eventType, $eventName, $content, $s3LogFile);
}