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

use Hyper\EventBundle\Service\Cached\Currency\CurrencyCached;

class CurrencyCachedUpdateCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('currency_cached:update')
            ->setDescription('Update currency cached')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
		$curencyRepo = $this->getContainer()->get('currency_repository');
		$listCurency = $curencyRepo->findAll();
		$curencyCached = new CurrencyCached($this->getContainer());
		$listCurencyCached = [];
		if (!empty($listCurency)) {
			foreach ($listCurency as $curency) {
				$listCurencyCached[$curency->getName()] = $curency->getRate();
			}
		}
		$curencyCached->hmset($listCurencyCached);
	}
}
