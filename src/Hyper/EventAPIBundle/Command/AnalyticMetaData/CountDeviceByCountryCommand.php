<?php
namespace Hyper\EventAPIBundle\Command\AnalyticMetaData;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Hyper\Domain\Client\Client;
use Hyper\Domain\Device\Device;

use Hyper\EventBundle\Service\Cached\AnalyticMetadata\CountDeviceByAppTitleCached;

class CountDeviceByCountryCommand extends ContainerAwareCommand
{
    protected $clientRepo;
    protected $cache;

    protected function configure()
    {
        $this
            ->setName('analytic_metadata:count_device_by_country')
            ->setDescription('Count device by country.');
    }

    protected function initProperties()
    {
        $this->clientRepo = $this->getContainer()->get('client_repository');
        $this->deviceRepo = $this->getContainer()->get('device_repository');
        $this->cache = $this->getContainer()->get('snc_redis.default');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->initProperties();
            $cacheValue = [];
            $cacheKey = $this->generateCacheKey();
            $countDeviceByAppTitleCached = new CountDeviceByAppTitleCached($this->getContainer());
            $allCountDeviceByAppTitleCachedData = $countDeviceByAppTitleCached->hgetall();
            echo "done fetch allCountDeviceByAppTitleCachedData"."\n";
            $cacheValue = [];
            foreach ($allCountDeviceByAppTitleCachedData as $clientId => $clientData) {
                $clientData = json_decode($clientData,true);
                foreach( $clientData as $appTitleId => $appTitleData ) {
                    echo "going on clientData $clientId"."\n";
                    $listAppId = null;
                    foreach ($appTitleData as $data) {
                        $listAppId[] = $data['app_id'];
                    }
                    echo "list app id of $clientId \n";
                    var_dump($listAppId);
                    echo "\n";
                    $listCountDeviceByCountry = $this->deviceRepo->countDeviceByCountry($listAppId);

                    if (empty($listCountDeviceByCountry)) {
                        continue;
                    }

                    $tmpListCountry = [];
                    foreach ($listCountDeviceByCountry as $country) {
                        $tmpListCountry[] = [
                            'client_id' => $clientId,
                            'country_code' => $country['country_code'],
                            Device::IOS_PLATFORM_CODE => $country['ios_count'],
                            Device::ANDROID_PLATFORM_CODE => $country['android_count']
                        ];
                    }
                    $cacheValue[$clientId] = json_encode($tmpListCountry);
                    echo "done preparing caching value of $clientId"."\n";
                }
            }

            $cacheValue['all'] = $this->getCacheValueAllClient();
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
        return md5('ANALYTIC_METADATA_COUNT_DEVICE_BY_COUNTRY');
    }

    protected function getCacheValueAllClient()
    {
        $listCountDeviceByCountry = $this->deviceRepo->countDeviceByCountry();
        $tmpListCountry = [];
                foreach ($listCountDeviceByCountry as $country) {
                    $tmpListCountry[] = [
                        'country_code' => $country['country_code'],
                        Device::IOS_PLATFORM_CODE => $country['ios_count'],
                        Device::ANDROID_PLATFORM_CODE => $country['android_count']
                    ];
                }
        return  json_encode($tmpListCountry);
    }

}