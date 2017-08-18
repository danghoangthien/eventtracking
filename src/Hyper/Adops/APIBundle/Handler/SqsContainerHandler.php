<?php
namespace Hyper\Adops\APIBundle\Handler;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Hyper\Adops\APIBundle\Handler\SqsHandlerInterface;

class SqsContainerHandler
{
    public $sqsContainerHandler;
    
    public function __construct(SqsHandlerInterface $sqsHandlerInterface)
    {
        $this->sqsContainerHandler = $sqsHandlerInterface;
    }
    
    public function getMessage($queueName, $numberLoop)
    {
        return $this->sqsContainerHandler->getMessage($queueName, $numberLoop);
    }
    
    public function perform($data)
    {
        return $this->sqsContainerHandler->perform($data);
    }
}