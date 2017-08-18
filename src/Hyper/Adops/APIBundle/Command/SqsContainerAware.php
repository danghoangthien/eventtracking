<?php
namespace Hyper\Adops\APIBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Aws\Sqs\SqsClient;
use GuzzleHttp\Client;

/**
 * SQS container aware with common functions.
 *
 * @author Carl Pham <vanca.vnn@gmail.com>
 */
class SqsContainerAware extends ContainerAwareCommand
{
    public function checkIp()
    {
        /*$client = new Client();
        $res = $client->request('GET', 'https://api.ipify.org');
        $ip = $res->getBody();
        if ('52.26.113.47' == $ip) {
            return true;
        }*/
        $checkIpParams = $this->getContainer()->getParameter('check_ip');
        $url = $checkIpParams['domain'].'/adops/api/v1/getip/';
        $internalIp = $checkIpParams['internal_ip'];
        
        $client = new Client();
        $res = $client->request('GET', $checkIpParams['domain'].'/adops/api/v1/getip/');
        $ipRes = json_decode($res->getBody(), true);
        $ip = $ipRes['ip'];
        if ($ip == $checkIpParams['internal_ip']) {
            return true;
        }
        return false;
    }
}