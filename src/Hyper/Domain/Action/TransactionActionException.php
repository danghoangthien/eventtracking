<?php
namespace Hyper\Domain\Action;

class TransactionActionException extends \Exception
{
    public static function appIdIsNull($transactionActionId){
        return new self('appId of '.$transactionActionId.' is NULL');
    }

}