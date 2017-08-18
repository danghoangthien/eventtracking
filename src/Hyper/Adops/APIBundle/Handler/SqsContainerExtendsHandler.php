<?php
namespace Hyper\Adops\APIBundle\Handler;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Aws\Sqs\SqsClient;
use Hyper\Adops\APIBundle\Handler\SqsHandlerInterface;

class SqsContainerExtendsHandler
{
    public $sqsClient;
    public $container;
    
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->initSqsClient();
    }
    
    public function initSqsClient()
    {
        if (null != $this->sqsClient) {
            return $this->sqsClient;
        }
        $amazonAwsKey = $this->container->getParameter('amazon_aws_key');
        $amazonAwsSecretKey = $this->container->getParameter('amazon_aws_secret_key');
        $sqsClient = SqsClient::factory(array(
            'region'  => 'us-west-2',
            'version' => 'latest',
            'credentials' => array(
                'key' => $amazonAwsKey,
                'secret'  => $amazonAwsSecretKey,
              )
        ));
        $this->sqsClient = $sqsClient;

        return $this->sqsClient;
    }
    
    public function sendToSqs($queueParameter, $data)
    {
        if (empty($data) || empty($queueParameter)) {
            return false;
        }
        $sqsClient = $this->sqsClient;
        $queueName = $this->container->getParameter($queueParameter);
        $result = $sqsClient->createQueue(array(
            'QueueName' => $queueName,
            // 'Attributes' => array(
            //     'MessageRetentionPeriod'=>1209600,
            // ),
        ));
        $queueUrl = $result->get('QueueUrl');
        $sqsClient->sendMessage(array(
            'QueueUrl'    => $queueUrl,
            'MessageBody' => json_encode($data),
        ));// Need detect formart return if has
        
        return true;
    }
    
    public function getMessageFromSQS($queueParameter, $numberLoop = 1)
    {
        $sqsClient = $this->sqsClient;
        $dataOutput = [];
        if (empty($queueParameter)) {
            return $dataOutput;
        }
        $queueName = $this->container->getParameter($queueParameter);
        $result = $sqsClient->createQueue(array('QueueName' => $queueName));
        $queueUrl = $result->get('QueueUrl');
        for ($i = 0; $i < $numberLoop; $i ++) {
            $result = $sqsClient->receiveMessage(array(
                'QueueUrl' => $queueUrl,
            ));
    
            if (isset($result['Messages'])) {
                foreach($result['Messages'] as $message) {
                    $bodyMessages = json_decode($message['Body'], true);
                    if (!empty($bodyMessages))
                        array_push($dataOutput, $bodyMessages);
    
                    $sqsClient->deleteMessage(array(
                        'QueueUrl'      => $queueUrl,
                        'ReceiptHandle' => $message['ReceiptHandle']
                    ));
                }
            }
        }
        
        return $dataOutput;
    }
    
    public function removeAllMessages($queueParameter)
    {
        if (empty($queueParameter)) {
            return false;
        }
        $sqsClient = $this->sqsClient;
        $queueName = $this->container->getParameter($queueParameter);
        $result = $sqsClient->createQueue(array('QueueName' => $queueName));
        $queueUrl = $result->get('QueueUrl');
        
        $result = $sqsClient->purgeQueue(array(
            'QueueUrl' => $queueUrl,
        ));
        
        return true;
    }
}