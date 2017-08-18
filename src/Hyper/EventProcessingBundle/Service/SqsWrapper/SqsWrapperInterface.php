<?php

namespace Hyper\EventProcessingBundle\Service\SqsWrapper;

interface SqsWrapperInterface
{
    public function initSqsClient();
    public function sendMessageToQueue($queueName, $messageBody, array $messageAttr);
    public function sendMessageBatch($queueName, $messagesBody, array $messageAttr = array());
    public function receiveMessagesBodyFromQueue($queueName, $maxNumberOfMessages);
}