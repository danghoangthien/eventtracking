<?php
/*
Usage: BundleName:ControllerName:ClassName
e.g.: EventBundle:CurrencyExchangeRatesController:getRate (
		array(
				'from_currency' => 'sgd', 
				'amount' 		=> 100
			)
	);
*/

namespace Hyper\EventBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

//entities
use Hyper\Domain\Currency\Currency;


class CurrencyExchangeRatesController extends Controller
{
   public function getRateAction($from_currency = 'sgd', $amount = 0) {
		$currency 		= new Currency();
		$date 			= new DateTime(date('Y-m').'-1 00:00:00');
        $from_currency 	= strtolower($from_currency);
		$amount 		= (float)($amount);

		$q = Doctrine::getTable('currency')->createQuery('c')
		  	->where('c.timestamps	>= ?', $date)
			->andWhere('c.name		= ?', $from_currency)
			->limit(1);
		$result = $q->fetchOne();
		
		if($result->name()) {
			$result = (float) ($result->rate * $amount);
			$result = round($result,2);
			return $result;
		}
		else {
			return false;
		}
			
   }
}