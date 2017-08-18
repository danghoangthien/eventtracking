<?php
namespace Hyper\EventAPIBundle\Command\AnalyticMetaData;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Hyper\Domain\Client\Client;
use Hyper\Domain\Device\Device;

class CountDeviceByPlatformCommand extends ContainerAwareCommand
{
    protected $clientRepo;
    protected $cache;

    protected function configure()
    {
        $this
            ->setName('analytic_metadata:count_device_by_platform')
            ->setDescription('Count device by platform.');
    }

    protected function initProperties()
    {
        $this->clientRepo = $this->getContainer()->get('client_repository');
        $this->applicationRepo = $this->getContainer()->get('application_repository');
        $this->cache = $this->getContainer()->get('snc_redis.default');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->initProperties();
            $listClient = $this->clientRepo->findAll();
            $cacheValue = '';
            $cacheKey = $this->generateCacheKey();
            if (empty($listClient)) {
                $this->saveCache($cacheKey, $cacheValue);
            }
            $listCountDeviceByPlatform = $this->applicationRepo->countDeviceByPlatform();
            if (empty($listCountDeviceByPlatform)) {
                $this->saveCache($cacheKey, $cacheValue);
            }
            foreach ($listClient as $client) {
                $clientId = $client->getId();
                $clientName = $client->getClientName();
                $listAppIdString = $client->getClientApp();
                if (empty($listAppIdString)) {
                    continue;
                }
                $listAppId = explode(',', $listAppIdString);
                if (empty($listAppIdString)) {
                    continue;
                }
                $tmpListAppId = [];
                foreach ($listCountDeviceByPlatform as $tmp) {
                    if (!in_array($tmp['app_id'], $listAppId)) {
                        continue;
                    }
                    $tmpListAppId[] = [
                        'client_id' => $clientId,
                        'client_name' => $clientName,
                        'app_id' => $tmp['app_id'],
                        'platform' => $tmp['platform'],
                        'device_count' => $tmp['device_count']
                    ];
                }
                $clientValue = json_encode($tmpListAppId);
                $cacheValue[$clientId] = $clientValue;
                $this->cache->hset($cacheKey, $clientId, $clientValue);
            }
            $this->saveCache($cacheKey, $cacheValue);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    protected function saveCache($cacheKey, $cacheValue)
    {
       return $this->cache->hmset($cacheKey, $cacheValue);
    }

    protected function generateCacheKey()
    {
        return md5('ANALYTIC_METADATA_COUNT_DEVICE_BY_FLATFORM');
    }

}