<?php

namespace Hyper\EventProcessingBundle\Service\Processor;

interface ProcessorInterface
{
    public function setMessagesBody($messagesBody);
    public function processData();
    public function parseMessageBodyToIdentifier();
    public function setListExistDataFromIdentifier();
    public function addExtraDataIntoMessagesBody();
    public function getIdentifierFromExistData($data);
    public function removeDuplicateNewData();
    public function getRepo();
    public function getValueNewData($newData);
}