<?php

namespace Hyper\EventProcessingBundle\Service\Processor;

use Hyper\EventProcessingBundle\Service\Processor\ProcessorInterface;

interface ProcessorManagerInterface
{
    public function setMessagesBody($messagesBody);
    public function addProcessor(ProcessorInterface $processor);
    public function processData();
}