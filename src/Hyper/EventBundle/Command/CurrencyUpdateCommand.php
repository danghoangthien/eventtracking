<?php
/*
Usage: php currency:update  Forex_URL APIKey
e.g.:  php currency:update  http://openexchangerates.org/api/latest.json adb858daf80445adb139f7a4990a5d04
*/

namespace Hyper\EventBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

//entities
use Hyper\Domain\Currency\Currency;

class CurrencyUpdateCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('currency:update')
            ->setDescription('Update reference currency table')
            ->addArgument(
                'forex_url',
                null,
                InputArgument::OPTIONAL,
				'http://openexchangerates.org/api/latest.json',
				'Forex Source URL'
            )
            ->addArgument(
                'forex_api_id',
                null,
                InputArgument::OPTIONAL,
				'041a23517284410cbe38a37769457d4f',
				'Forex API ID'
            )
			//Available option only for paid accounts
			//default to USD only
           // ->addArgument(
           //     'base_currency',
           //     InputArgument::OPTIONAL,
           //     'Convert Base Currency',
			//	'usd'
           // )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Get parameters passed
        $forex_url			= $input->getArgument('forex_url');
        $forex_api_id		= $input->getArgument('forex_api_id');
        //$base_currency		= $input->getArgument('base_currency');
		
        $forex_exchange		= $forex_url
								.'?app_id='.
								$forex_api_id;
								//Option for paid accounts only
								//Disabled in purpose
								//.'&base='.
								//$base_currency;
		echo "URL : $forex_exchange \n";						
		$result = $this->_getUrl($forex_exchange);
		
		$json = json_decode($result['content']);
				 
		if((json_last_error() == JSON_ERROR_NONE)) {
			$this->_saveRates($json);
		}
		else {
			return false;
		}
	}

	protected function _saveRates($json_file) {
		//var_dump($json_file);die;
		foreach($json_file->rates as $key => $val) {
			$curencyRepo = $this->getContainer()->get('currency_repository');
			$currency = $curencyRepo->findOneBy(
				array('name' => strtolower($key))
			);
			if(!$currency instanceof Currency) {
				$currency 	= new Currency();
			}
			
			$currency->setTimestamps($json_file->timestamp);
			$currency->setName(strtolower($key));
			$currency->setRate($val);
			
			$em = $this->getContainer()->get('doctrine')->getEntityManager('pgsql');
			$em->persist($currency);
			$em->flush();		
		}
	}
	
	protected function _getUrl($url)
		{
			$options = array(
			CURLOPT_RETURNTRANSFER => true,     // return web page
			CURLOPT_HEADER         => false,    // don't return headers
			CURLOPT_FOLLOWLOCATION => true,     // follow redirects
			CURLOPT_ENCODING       => "",       // handle all encodings
			CURLOPT_USERAGENT      => "spider", // who am i
			CURLOPT_AUTOREFERER    => true,     // set referer on redirect
			CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
			CURLOPT_TIMEOUT        => 120,      // timeout on response
			CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
			CURLOPT_SSL_VERIFYPEER => false     // Disabled SSL Cert checks
			);
			
			$ch      = curl_init( $url );
			curl_setopt_array( $ch, $options );
			$content = curl_exec( $ch );
			$err     = curl_errno( $ch );
			$errmsg  = curl_error( $ch );
			$header  = curl_getinfo( $ch );
			curl_close( $ch );
			
			$header['errno']   = $err;
			$header['errmsg']  = $errmsg;
			$header['content'] = $content;
			
			return $header;
		}	
}
