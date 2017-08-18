<?php
namespace Hyper\Adops\APIBundle\Handler;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Hyper\Adops\APIBundle\Handler\SqsHandlerInterface;
use Hyper\Adops\APIBundle\Handler\SqsContainerExtendsHandler;

/**
 *
 * @author Carl Pham <vanca.vnn@gmail.com>
 */
class SqsCreateLogHandler extends SqsContainerExtendsHandler implements SqsHandlerInterface
{
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
    }
    
    public function getMessage($queueParameter, $numberLoop)
    {
        return $this->getMessageFromSQS($queueParameter, $numberLoop);
    }
    
    public function perform($messages = [])
    {
        if (empty($messages)) {
            return ['status'=>false];
        }
        $adopsLogRepo = $this->container->get('adops.api.log.repository');
        foreach ($messages as $message) {
            $adopsLogRepo->setAdopsLog($message);
        }
        $adopsLogRepo->insertAdopLog();
        
        return ['status'=>true];
    }
    
}