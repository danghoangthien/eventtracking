<?php

namespace Hyper\EventProcessingBundle\Validator;

use Symfony\Component\Validator\Constraint,
    Symfony\Component\Validator\ConstraintValidator,
    Hyper\Domain\Device\Device;

class ContainsMessageValidator extends ConstraintValidator
{

    public function validate($content, Constraint $constraint)
    {
        if (!isset($content['platform'])) {
            $this->context->buildViolation('Platform does not exist.')->addViolation();
        }

        // validate device
        if (!isset($content['platform'])) {
            $this->context->buildViolation('Platform does not exist.')->addViolation();
        }

        if (
            !in_array($content['platform'], array(Device::ANDROID_PLATFORM_NAME, Device::IOS_PLATFORM_NAME))
        ) {
            $this->context->buildViolation('Platform does not support.')->addViolation();
        }

        if (
            $content['platform'] == Device::ANDROID_PLATFORM_NAME
        ) {

            if (
                !array_key_exists('android_id', $content) &&
                !array_key_exists('advertising_id', $content)
            ) {
                $this->context->buildViolation('android_id or advertising_id doest not exist.')->addViolation();
            }

            if (
                empty($content['android_id']) &&
                empty($content['advertising_id'])
            ) {
                $this->context->buildViolation('android_id or advertising_id must be have a value.')->addViolation();
            }
        }

        if (
            $content['platform'] == Device::IOS_PLATFORM_NAME
        ) {

            if (
                !array_key_exists('idfa', $content)
            ) {
                $this->context->buildViolation('idfa doest not exist.')->addViolation();
            }

            if (
                empty($content['idfa'])
            ) {
                $this->context->buildViolation('idfa must be have a value.')->addViolation();
            }
        }

        if (
            isset($content['click_time'])
            && !empty($content['click_time'])
            && !$this->validateDate($content['click_time'])
        ) {
            $this->context->buildViolation('click_time must be specified in the "Y-m-d H:i:s" format.')->addViolation();
        }

        if (
            isset($content['install_time'])
            && !empty($content['install_time'])
            && !$this->validateDate($content['install_time'])
        ) {
            $this->context->buildViolation('install_time must be specified in the "Y-m-d H:i:s" format.')->addViolation();
        }
        if (
            isset($content['install_time'])
            && !empty($content['install_time'])
            && (strtotime($content['install_time']) > 2147483647)
        ) {
            $this->context->buildViolation('install_time must be more than or equals 2147483647.')->addViolation();
        }
        // validate application
        if (
            !array_key_exists('app_id', $content)
        ) {
            $this->context->buildViolation('app_id doest not exist.')->addViolation();
        }

        if (
            empty($content['app_id'])
        ) {
            $this->context->buildViolation('app_id must be have a value.')->addViolation();
        }

        if (
            !array_key_exists('app_name', $content)
        ) {
            $this->context->buildViolation('app_name doest not exist.')->addViolation();
        }

        if (
            empty($content['app_name'])
        ) {
            $this->context->buildViolation('app_name must be have a value.')->addViolation();
        }

        if (
            !array_key_exists('app_version',$content)
        ) {
            $this->context->buildViolation('app_version doest not exist.')->addViolation();
        }

        if (
            empty($content['app_version'])
        ) {
            $this->context->buildViolation('app_version must be have a value.')->addViolation();
        }
        // validate event
        if (!array_key_exists('event_type', $content)) {
            $this->context->buildViolation('event_type does not exist.')->addViolation();
        }

        if (empty($content['event_type'])) {
            $this->context->buildViolation('event_type must be have a value.')->addViolation();
        }

        if (
            !in_array($content['event_type'], array(
                'install',
                'in-app-event'
            ))
        ) {
            $this->context->buildViolation("event_type {$content['event_type']} does not support.")->addViolation();
        }
        if ($content['event_type'] == 'install') {
            $content['event_name'] = 'install';
        }

        if (
            !array_key_exists('event_name', $content)
        ) {
            $this->context->buildViolation("event_name does not exist.")->addViolation();
        }

        if (empty($content['event_name'])) {
            $this->context->buildViolation('event_name must be have a value.')->addViolation();
        }

        if (!array_key_exists('event_time', $content)) {
            $this->context->buildViolation('event_time does not exist.')->addViolation();
        }

        if (empty($content['event_time'])) {
            $this->context->buildViolation('event_time must be have a value.')->addViolation();
        }

        if (!$this->validateDate($content['event_time'])) {
            $this->context->buildViolation('event_time must be specified in the "Y-m-d H:i:s" format.')->addViolation();
        }

        if (
            isset($content['event_value']) &&
            !empty($content['event_value']) &&
            !is_array($content['event_value']) &&
            !is_bool($content['event_value'])
        ) {
            $content['event_value'] = json_decode($content['event_value'], true);
        }

        if (
            isset($content['event_value']['af_revenue']) &&
            !is_numeric($content['event_value']['af_revenue'])
        ) {
            $this->context->buildViolation('af_revenue must be a numberic.')->addViolation();
        }

        if (
            isset($content['event_value']['af_price']) &&
            !is_numeric($content['event_value']['af_price'])
        ) {
            $this->context->buildViolation('af_price must be a numberic.')->addViolation();
        }

        if (
            isset($content['event_value']['af_level']) &&
            !is_numeric($content['event_value']['af_level'])
        ) {
            $this->context->buildViolation('af_level must be a numberic.')->addViolation();
        }


        if (
            isset($content['event_value']['af_success']) &&
            !is_bool($content['event_value']['af_success'])
        ) {
            $this->context->buildViolation('af_success must be a boolean.')->addViolation();
        }
        if (
            isset($content['event_value']['af_quantity']) &&
            !is_numeric($content['event_value']['af_quantity'])
        ) {
            $this->context->buildViolation('af_quantity must be a numberic.')->addViolation();
        }

        if (
            isset($content['event_value']['af_payment_info_available']) &&
            !is_bool($content['event_value']['af_payment_info_available'])
        ) {
            $this->context->buildViolation('af_payment_info_available must be a boolean.')->addViolation();
        }

        if (
            isset($content['event_value']['af_rating_value']) &&
            !is_numeric($content['event_value']['af_rating_value'])
        ) {
            $this->context->buildViolation('af_rating_value must be a numberic.')->addViolation();
        }

        if (
            isset($content['event_value']['af_max_rating_value']) &&
            !is_numeric($content['event_value']['af_max_rating_value'])
        ) {
            $this->context->buildViolation('af_max_rating_value must be a numberic.')->addViolation();
        }

        if (
            isset($content['event_value']['af_score']) &&
            !is_numeric($content['event_value']['af_score'])
        ) {
            $this->context->buildViolation('af_score must be a numberic.')->addViolation();
        }

        if (
            isset($content['event_value']['af_lat']) &&
            !is_numeric($content['event_value']['af_lat'])
        ) {
            $this->context->buildViolation('af_lat must be a numberic.')->addViolation();
        }

        if (
            isset($content['event_value']['af_long']) &&
            !is_numeric($content['event_value']['af_long'])
        ) {
            $this->context->buildViolation('af_long must be a numberic.')->addViolation();
        }

        return $content;
    }

    protected function validateDate($date, $format = 'Y-m-d H:i:s')
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }
}