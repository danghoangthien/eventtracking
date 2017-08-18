<?php

namespace Hyper\EventProcessingBundle\Service\EventHandling;


interface EventHandlingInterface
{
    public function checkRunning();
    public function getSettingKey();
    public function run();
    public function receiveMessagesBodyFromQueue();
    public function processData();
    public function storeDataToS3Bucket();
    public function storeDataToRedshift();
    public function sendDataToSqs();
    public function nextRunning();
}