<?php
namespace Hyper\Adops\APIBundle\Handler;

/**
 * @author Carl Pham <vanca.vnn@gmail.com>
 */
interface SqsHandlerInterface
{
    public function getMessage($queueName, $numberLoop);
    public function perform($data);
}