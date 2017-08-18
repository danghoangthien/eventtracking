<?php
namespace Hyper\Domain\Currency;

class CurrencyException extends \Exception
{
    public static function notExisted($currency){
        return new self(" $currency is not existed ");
    }

}