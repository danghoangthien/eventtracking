<?php

namespace Hyper\EventProcessingBundle\Service\SqsWrapper;

use Symfony\Component\DependencyInjection\ContainerInterface,
    Aws\Sqs\SqsClient,
    Hyper\EventProcessingBundle\Service\SqsWrapper\SqsWrapperInterface;

class SqsWrapper implements SqsWrapperInterface
{
    private $sqsClient;
    
    const MAX_NUMBER_OF_MESSAGE = 10;
    
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->initSqsClient();
    }
    
    public function getSQSClient()
    {
        return $this->sqsClient;
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
        if(!$this->sqsClient instanceof SqsClient) {
            throw new \Exception('SqsClient not loaded.');
        }
    }
    
    public function sendMessageToQueue($queueName, $messageBody, array $messageAttr = array())
    {
        if (empty($messageBody) || empty($queueName)) {
            return false;
        }
        
        $queueArgs = array(
            'QueueName' => $queueName
        );
        if (!empty($messageAttr) && is_array($messageAttr)) {
            $queueArgs = array_merge($queueArgs, array(
                'MessageAttributes' => $messageAttr
            ));
        }
        $result = $this->sqsClient->createQueue($queueArgs);
        $queueUrl = $result->get('QueueUrl');
        return $this->sqsClient->sendMessage(array(
            'QueueUrl'    => $queueUrl,
            'MessageBody' => json_encode($messageBody),
        ));
    }
    
    public function sendMessageBatch($queueName, $messagesBody, array $messageAttr = array())
    {
        if (empty($messagesBody) || empty($queueName)) {
            return false;
        }
        $queueArgs = array(
            'QueueName' => $queueName
        );
        $result = $this->sqsClient->createQueue($queueArgs);
        $queueUrl = $result->get('QueueUrl');
        $entries = array();
        $i = 1;
        $ret = array();
        foreach ($messagesBody as $messageBody) {
            $entry = array(
                'Id' => uniqid(),
                'MessageBody' => json_encode($messageBody),
            );
            if (!empty($messageAttr) && is_array($messageAttr)) {
                $entry = array_merge($entry, array(
                    'MessageAttributes' => $messageAttr
                ));
            }
            $entries[] = $entry;
            if ($i % self::MAX_NUMBER_OF_MESSAGE == 0 && $i != 1) {
                $ret[] = $this->sqsClient->sendMessageBatch(array(
                    'QueueUrl'    => $queueUrl,
                    'Entries' => $entries,
                ));
                $entries = array();
            }
            $i++;
        }
        if (!empty($entries)) {
            $ret[] = $this->sqsClient->sendMessageBatch(array(
                'QueueUrl'    => $queueUrl,
                'Entries' => $entries,
            ));
        }
        
        return $ret;
    }
    
    public function receiveMessagesBodyFromQueue($queueName, $maxNumberOfMessages = 10)
    {
        $bodyMessages = array();
        $result = $this->sqsClient->createQueue(array('QueueName' => $queueName));
        $queueUrl = $result->get('QueueUrl');
        $loopTotal = ceil($maxNumberOfMessages / self::MAX_NUMBER_OF_MESSAGE);
        for ($i = 1; $i <= $loopTotal; $i++) {
            $msgToDelete = array();
            $result = $this->sqsClient->receiveMessage(array(
                'QueueUrl' => $queueUrl,
                'MaxNumberOfMessages' => self::MAX_NUMBER_OF_MESSAGE
            ));
            if (!isset($result['Messages'])) {
                break;
            }
            foreach($result['Messages'] as $message) {
                $bodyMessagesResp = json_decode($message['Body'], true);
                if (!empty($bodyMessagesResp)) {
                    $bodyMessages[] = $bodyMessagesResp;
                    $msgToDelete[] = array(
                        'Id' => $message['MessageId'],
                        'ReceiptHandle' => $message['ReceiptHandle']
                    );
                }
            }
            if(!empty($msgToDelete)) {
                $this->sqsClient->deleteMessageBatch(array(
                    'QueueUrl'      => $queueUrl,
                    'Entries'       => $msgToDelete
                ));
            }
        }
        
        return $bodyMessages;
    }
    
    public function countMessageOnQueue($queueUrl)
    {
        $numMessage = 0;
        $result = $this->sqsClient->getQueueAttributes(array(
            'QueueUrl' => $queueUrl,
            'AttributeNames' => array('ApproximateNumberOfMessages')
        ));
        if (!empty($result['Attributes']['ApproximateNumberOfMessages'])) {
            $numMessage = $result['Attributes']['ApproximateNumberOfMessages'];
        }
        
        return $numMessage;
    }
}