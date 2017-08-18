<?php

namespace Hyper\EventProcessingBundle\Service\Processor\ActionProcessor;

use Symfony\Component\DependencyInjection\ContainerInterface,
    Hyper\DomainBundle\Repository\Action\DTActionRepository,
    Hyper\EventProcessingBundle\Service\Processor\Processor,
    Hyper\EventProcessingBundle\Service\Processor\ProcessorInterface,
    Hyper\Domain\Action\Action,
    Hyper\EventBundle\Service\Cached\InappeventConfig\InappeventConfigCached,
    Hyper\EventBundle\Service\Cached\Currency\CurrencyCached;

class ActionProcessor extends Processor implements ProcessorInterface
{
    const USD_CURRENCY = 'USD';
    protected $actionRepository;
    protected $ieaConfigCached;
    protected $currencyCached;

    public function __construct(ContainerInterface $container, DTActionRepository $actionRepository)
    {
        parent::__construct($container);
        $this->actionRepository = $actionRepository;
        $this->ieaConfigCached = new InappeventConfigCached($this->container);
        $this->currencyCached = new CurrencyCached($this->container);
    }

    public function parseMessageBodyToData($messageBody)
    {
        $deviceId = $messageBody['extra_data']['device_id'];
        $applicationId = $messageBody['extra_data']['application_id'];
        $appId = $messageBody['extra_data']['app_id'];
        $providerId = $messageBody['extra_data']['provider_id'];
        $actionType = $this->getActionType($messageBody);
        $happenedAt = strtotime($messageBody['event_time']);
        $actionId = $this->geIdentifierFromMessageBody($messageBody);
        if (!empty($messageBody['event_value']) && !is_array($messageBody['event_value'])) {
            $eventValue = json_decode($messageBody['event_value'], true);
            if(json_last_error() === JSON_ERROR_NONE) {
                $messageBody['event_value'] = $eventValue;
            }
        }
        $s3LogFile = '';
        if (isset($messageBody['extra_data']['s3_log_file'])) {
            $s3LogFile = $messageBody['extra_data']['s3_log_file'];
        }
        $created = time();

        $eventValueParams = array(
            'af_revenue' => '',
            'af_price' => '',
            'af_level' => '',
            'af_success' => '',
            'af_content_type' => '',
            'af_content_list' => '',
            'af_content_id' => '',
            'af_currency' => '',
            'af_registration_method' => '',
            'af_quantity' => '',
            'af_payment_info_available' => '',
            'af_rating_value' => '',
            'af_max_rating_value' => '',
            'af_search_string' => '',
            'af_description' => '',
            'af_score' => '',
            'af_destination_a' => '',
            'af_destination_b' => '',
            'af_class' => '',
            'af_date_a' => '',
            'af_date_b' => '',
            'af_event_start' => '',
            'af_event_end' => '',
            'af_lat' => '',
            'af_long' => '',
            'af_customer_user_id' => '',
            'af_validated' => '',
            'af_receipt_id' => '',
            'af_param_1' => '',
            'af_param_2' => '',
            'af_param_3' => '',
            'af_param_4' => '',
            'af_param_5' => '',
            'af_param_6' => '',
            'af_param_7' => '',
            'af_param_8' => '',
            'af_param_9' => '',
            'af_param_10' => '',
            'event_value_text' => ''
        );
        foreach ($eventValueParams as $key => $value) {
            if (isset($messageBody['event_value'][$key])) {
                $eventValueParams[$key] = $messageBody['event_value'][$key];
            }
        }
        if (is_bool($eventValueParams['af_success'])) {
            $eventValueParams['af_success'] = (int) $eventValueParams['af_success'];
        }
        if (is_bool($eventValueParams['af_payment_info_available'])) {
            $eventValueParams['af_payment_info_available'] = (int) $eventValueParams['af_payment_info_available'];
        }
        if(!is_array($messageBody['event_value'])) {
            $eventValueParams['event_value_text'] = var_export($messageBody['event_value'], true);
        }

        if (!empty($eventValueParams['af_param_2'])) {
            $eventValueParams['af_param_2'] = substr($eventValueParams['af_param_2'], 0, 255);
        }
        if (!empty($eventValueParams['af_receipt_id'])) {
            $eventValueParams['af_receipt_id'] = substr($eventValueParams['af_receipt_id'], 0, 255);
        }

        /**
         * https://hyperdev.atlassian.net/browse/BOB-223
         **/
        if (
            empty($eventValueParams['af_currency']) &&
            (
                !empty($eventValueParams['af_price']) ||
                !empty($eventValueParams['af_revenue'])
            )
        ) {
            $eventValueParams['af_currency'] = $messageBody['currency'];
            if (empty($eventValueParams['af_currency'])) {
                $eventValueParams['af_currency'] = self::USD_CURRENCY;
            }
        }
        $amountUSD = 0;
        $appId = '';
        if (!empty($messageBody['app_id'])) {
            $appId = $messageBody['app_id'];
        }
        $eventName = '';
        if (!empty($messageBody['event_name'])) {
            $eventName = $messageBody['event_name'];
            //Hotfix trim message :
            $eventName = substr($eventName,0,45);
        }
        if ($this->checkEventTagAsIAP($appId, $eventName)) {
            $currency = '';
            if (!empty($eventValueParams['af_currency'])) {
                $currency = $eventValueParams['af_currency'];
            }
            $amount = 0;
            if (!empty($eventValueParams['af_revenue'])) {
                $amount = $eventValueParams['af_revenue'];
            }
            if ($currency && $amount) {
                $amountUSD = $this->convertToUSD($currency, $amount);
            }
        }
        $ret = array(
            'actions' => array(
                'id' => $actionId,
                'device_id' => $deviceId,
                'application_id' => $applicationId,
                'action_type' => $actionType,
                'behaviour_id' => 0,
                'provider_id'=> $providerId,
                's3_log_file' => $s3LogFile,
                'happened_at'=> $happenedAt,
                'created' => $created,
                'app_id' => $appId
            )
        );
        $ret['actions'] = array_merge($ret['actions'], $eventValueParams);
        $ret['actions']['event_name'] = $eventName;
        $ret['actions']['amount_usd'] = $amountUSD;
        return $ret;
    }

    public function addExtraDataIntoMessagesBody()
    {
        if (empty($this->messagesBody)) {
            return;
        }
        foreach ($this->messagesBody as $key => $messageBody) {
            $identifier = $this->geIdentifierFromMessageBody($messageBody);
            $extraData = array(
                'action_id' => ''
            );
            $identifier = $this->isNewIdentifier($identifier);
            if (!$identifier) {
                $newData = $this->parseMessageBodyToData($messageBody);
                $extraData['action_id'] = $newData['actions']['id'];
                $this->listNewData[] = $newData;
            } else {
                $extraData['action_id'] = $identifier;
            }
            $extraDataMerged = $this->addExtraData($messageBody, $extraData);
            $this->messagesBody[$key] = $extraDataMerged;
            unset($identifier);
            unset($extraData);
            unset($extraDataMerged);
        }
    }

    public function geIdentifierFromMessageBody($messageBody)
    {
        $happenedAt = strtotime($messageBody['event_time']);
        $identifier = array(
            $messageBody['extra_data']['device_id'],
            $messageBody['extra_data']['application_id'],
            $messageBody['event_name'],
            $happenedAt
        );

        return $this->convertIdentifierToMD5($identifier);
    }

    protected function getActionType($messageBody)
    {
        $actionType = '';
        $behaviourId = '';
        $providerId = '';
        if ($messageBody['event_type'] == 'install') {
            $actionType = Action::ACTION_TYPES['INSTALL_ACTION_TYPE'];
        } else if ($messageBody['event_type'] == 'in-app-event') {
            $actionType = Action::ACTION_TYPES['IN_APP_EVENT_ACTION_TYPE'];
        }

        return $actionType;
    }

    public function getRepo()
    {
        return $this->actionRepository;
    }

    public function getValueNewData($newData)
    {
        return $newData['actions']['id'];
    }

    private function checkEventTagAsIAP($appId, $eventName)
    {
        if (!$this->ieaConfigCached->exists()) {
            return false;
        }
        $iaeConfig = $this->ieaConfigCached->hget($appId);
        if (!$iaeConfig) {
            return false;
        }
        $iaeConfig = json_decode($iaeConfig, true);
        if (empty($iaeConfig) || empty($iaeConfig[$eventName]['tag_as_iap'])) {
            return false;
        }

        return true;
    }

    public function convertToUSD($currency, $amount)
    {
        if (!$this->currencyCached->exists()) {
            return 0;
        }
        $rate = $this->currencyCached->hget(strtolower($currency));
        $money = (float) ($amount/$rate);
        $money = round($money, 2);

        return $money;
    }
}