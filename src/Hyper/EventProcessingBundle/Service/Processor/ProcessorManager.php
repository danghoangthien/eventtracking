<?php

namespace Hyper\EventProcessingBundle\Service\Processor;

use Symfony\Component\DependencyInjection\ContainerInterface,
    Hyper\EventProcessingBundle\Service\Processor\ProcessorManagerInterface,
    Hyper\EventProcessingBundle\Service\Processor\ProcessorInterface;

class ProcessorManager implements ProcessorManagerInterface
{
    protected $container;
    protected $messagesBody;
    protected $listProcessor = array();
    protected $messagesBodyParsed = array();
    protected $listNewData = array();

    public function __construct($container)
    {
        $this->container = $container;
    }
    
    public function resetProperties()
    {
        $this->messagesBodyParsed = array();
        $this->listNewData = array();
    }
    
    public function resetProcessor()
    {
        $this->listProcessor = array();
    }

    public function addProcessor(ProcessorInterface $processor)
    {
        $this->listProcessor[] = $processor;
    }
    public function setMessagesBody($messagesBody) {
        $this->messagesBody = $messagesBody;
    }

    public function processData()
    {
        $this->resetProperties();
        if (empty($this->listProcessor)) {
            return;
        }
        $this->messagesBodyParsed = $this->messagesBody;
        unset($this->messagesBody);
        foreach ($this->listProcessor as $processor) {
            if ($processor instanceof ProcessorInterface) {
                echo "Processor: ". get_class($processor) . "\n";
                $processor->setMessagesBody($this->messagesBodyParsed);
                list($messagesBodyParsed, $listNewData) = $processor->processData();
                $this->messagesBodyParsed = $messagesBodyParsed;
                $this->listNewData = array_merge($this->listNewData, $listNewData);
                unset($processor);
            }
        }
        
        return array(
            $this->messagesBodyParsed,
            $this->listNewData
        );
    }
}