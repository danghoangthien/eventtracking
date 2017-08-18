<?php

namespace Hyper\EventProcessingBundle\Service\Processor;

use Symfony\Component\DependencyInjection\ContainerInterface;

class Processor
{
    protected $container;
    protected $messagesBody = array();
    protected $listIdentifier = array();
    protected $listExistData = array();
    protected $listNewData = array();

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function setMessagesBody($messagesBody)
    {
        $this->messagesBody = $messagesBody;
    }

    public function processData()
    {
        $this->resetProperties();
        $startTimeParseMessageBodyToIdentifier = microtime(true);
        $this->parseMessageBodyToIdentifier();
        $totalTimeParseMessageBodyToIdentifier = microtime(true) - $startTimeParseMessageBodyToIdentifier;
        $totalTimeParseMessageBodyToIdentifier = number_format($totalTimeParseMessageBodyToIdentifier, 1);
        echo "Total time of parse message body to identifier: {$totalTimeParseMessageBodyToIdentifier}s \n";

        $startListExistDataFromIdentifier = microtime(true);
        $this->setListExistDataFromIdentifier();
        $totalTimeListExistDataFromIdentifier = microtime(true) - $startListExistDataFromIdentifier;
        $totalTimeListExistDataFromIdentifier = number_format($totalTimeListExistDataFromIdentifier, 1);
        echo "Total time of query exist data from identifier: {$totalTimeListExistDataFromIdentifier}s \n";
        $totalExistData = count($this->listExistData);
        echo "Total of exist data: {$totalExistData} \n";

        $startAddExtraDataIntoMessagesBody = microtime(true);
        $this->addExtraDataIntoMessagesBody();
        $totalTimeAddExtraDataIntoMessagesBody = microtime(true) - $startAddExtraDataIntoMessagesBody;
        $totalTimeAddExtraDataIntoMessagesBody = number_format($totalTimeAddExtraDataIntoMessagesBody, 1);
        echo "Total time of add extra data into message body: {$totalTimeAddExtraDataIntoMessagesBody}s \n";

        $totalNewData = count($this->listNewData);
        echo "Before remove duplicate new data: {$totalNewData} \n";
        $startRemoveDuplicateNewData = microtime(true);
        $this->removeDuplicateNewData();
        $totalTimeRemoveDuplicateNewData = microtime(true) - $startRemoveDuplicateNewData;
        $totalTimeRemoveDuplicateNewData = number_format($totalTimeRemoveDuplicateNewData, 1);
        $totalNewData = count($this->listNewData);
        echo "After remove duplicate new data: {$totalNewData} \n";
        echo "Total time of remove duplicate new data: {$totalTimeRemoveDuplicateNewData}s \n";

        return array(
            $this->messagesBody,
            $this->listNewData
        );
    }

    public function addExtraData($originData, $extraData)
    {
        if (isset($originData['extra_data'])) {
            $originData['extra_data'] = array_merge(
                $originData['extra_data'],
                $extraData
            );
        } else {
            $originData['extra_data'] = $extraData;
        }

        return $originData;
    }

    public function resetProperties()
    {
        $this->listExistData = array();
        $this->listNewData = array();
        $this->listIdentifier = array();
    }

    public function isNewIdentifier($identifier)
    {
        if (empty($this->listExistData)) {
            return;
        }
        foreach ($this->listExistData as $key => $data) {
            if ($data['id'] == $identifier) {

                return $data;
            }
        }

        return '';
    }

    public function getIdentifierFromExistData($data)
    {
        return $this->geIdentifierFromMessageBody($data);
    }

    public function convertIdentifierToMD5($identifier)
    {
        return md5(implode("", $identifier));
    }

    public function setListExistDataFromIdentifier()
    {
        $this->listExistData = $this->getRepo()->getListDataFromIdentifier(
            $this->listIdentifier
        );
    }

    public function removeDuplicateNewData()
    {
        if (!$this->listNewData) {
            return;
        }
        $checkArr = array();
        $listNewData = array();
        foreach ($this->listNewData as $newData) {
            $value = $this->getValueNewData($newData);
            if (!in_array($value, $checkArr)) {
                $listNewData[] = $newData;
            }
            $checkArr[] = $value;
        }

        $this->listNewData = $listNewData;
    }

    public function parseMessageBodyToIdentifier()
    {
        if (empty($this->messagesBody)) {
            return;
        }
        foreach ($this->messagesBody as $key => $messageBody) {
            $identifier = $this->geIdentifierFromMessageBody($messageBody);
            $this->listIdentifier[] = $identifier;
            unset($messageBody);
        }
    }

    public static function makeRSCopySyntax($data)
    {
        $jsonOutput = '';
        foreach ($data as $item) {
            $jsonOutput .= json_encode($item);
        }

        $jsonOutput = str_replace('\\u0000', "", $jsonOutput);

        return $jsonOutput;
    }
}