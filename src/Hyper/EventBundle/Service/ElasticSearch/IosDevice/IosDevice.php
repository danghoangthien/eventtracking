<?php
namespace Hyper\EventBundle\Service\ElasticSearch\IosDevice;

use Symfony\Component\DependencyInjection\ContainerInterface,
    Hyper\EventBundle\Service\ElasticSearch\BaseElasticSearch;

/**
 * @author Carl Pham <vanca.vnn@gmail.com>
 */
class IosDevice extends BaseElasticSearch
{
    const TOTAL_ROW_JSON_FILE = 5000;

    protected $iosDeviceRepo;
    protected $indexName;
    protected $tmpDeviceIdDir;
    protected $tmpDeviceIdFile;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->iosDeviceRepo = $this->container->get('ios_device_repository');
        $this->indexName = 'ios_devices';
        $this->tmpDeviceIdFilePath = $this->tmpDeviceIdDir.'/'.self::TMP_IOS_DEVICE_ID_FILE;
    }

    public function perform()
    {
        if (!file_exists($this->tmpDeviceIdFilePath)) {
            return;
        }
        $deviceIds = file_get_contents($this->tmpDeviceIdFilePath);
        $deviceIds = substr($deviceIds, 0, -1);
        $deviceIds = explode(',', $deviceIds);
        $deviceIds = array_unique($deviceIds);
        if (count($deviceIds) <= self::TOTAL_ROW_JSON_FILE) {
            $devices = $this->getByDeviceId($deviceIds);
            if (empty($devices)) {
                return;
            }
            $this->processIndexIosDevice($devices);
            return;
        }
        $errors = [];
        $deviceIdChunks = array_chunk($deviceIds, self::TOTAL_ROW_JSON_FILE);
        foreach ($deviceIdChunks as $k => $deviceIdChunk) {
            $devices = $this->getByDeviceId($deviceIdChunk);
            if (empty($devices)) {
                return;
            }
            $erros[] = $this->processIndexIosDevice($devices, $k);
        }
        unlink($this->tmpDeviceIdFilePath);
        return;
        
        // Full table: Should be don't use
        $total = $this->iosDeviceRepo->createQueryBuilder('iosdev')
            ->select('COUNT(iosdev.device)')
            ->getQuery()
            ->getSingleScalarResult();

        $numberLoop = ceil($total/self::TOTAL_ROW_JSON_FILE);
        echo "Total number loop: $numberLoop \n";
        for ($i = 0; $i < $numberLoop; $i ++) {
            $iosDevices = $this->getDataForEs($i);
            if (empty($iosDevices)) {
                continue;
            }
            $jsonESSyntax = '';
            foreach ($iosDevices as $row) {
                $jsonESSyntax .= $this->makeESSyntax($row);
            }

            $fs = $this->getFS();
            $pathJson = $this->esDir.'/'.$this->indexName.'/'.$this->indexName.'_'.$i.'.json';
            $fs->dumpFile($pathJson, $jsonESSyntax);

            echo "Push file: $pathJson \n";
            $rs = $this->pushDataToElasticSearch($this->indexName, $this->indexName, $pathJson);
            if ($rs['response_code'] == 200 || $rs['response_code'] == 201) {
                $fs->remove($pathJson);
            } else {
                $this->logger->error(
                    "Elastic search error: "
                    ."\n Request: ".$rs['errors']['request']
                    ."\nResponse: ".$rs['errors']['response']
                    ."\nJson path: $pathJson"
                );
            }
            sleep(5);
        }
    }
    
    public function processIndexIosDevice($devices, $sufixJsonFileName = '')
    {
        $jsonESSyntax = '';
        $fs = $this->getFS();
        foreach ($devices as $row) {
            $jsonESSyntax .= $this->makeESSyntax($row);
        }
        $pathJson = $this->esDir."/".$this->indexName."/".$this->indexName."_$sufixJsonFileName.json";
        $fs->dumpFile($pathJson, $jsonESSyntax);

        echo "Push file: $pathJson \n";
        $rs = $this->pushDataToElasticSearch($this->indexName, $this->indexName, $pathJson);
        if ($rs['response_code'] == 200 || $rs['response_code'] == 201) {
            $fs->remove($pathJson);
        } else {
            throw new \Exception("Error push data to ES:\n Json path: $pathJson\n Response code: ".$rs['response_code']);
        }
    }
    
    public function getByDeviceId($deviceIds = [])
    {
        return $this->iosDeviceRepo->createQueryBuilder('iosdev')
            ->select('iosdev')
            ->where('iosdev.device IN (:deviceIds)')
            ->setParameter('deviceIds', $deviceIds)
            ->getQuery()
            ->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
    }

    public function getDataForEs($offset)
    {
        $offset = $offset + $offset*self::TOTAL_ROW_JSON_FILE;
        return $this->iosDeviceRepo->createQueryBuilder('iosdev')
            ->select('iosdev')
            ->setFirstResult($offset)
            ->setMaxResults(self::TOTAL_ROW_JSON_FILE)
            ->getQuery()
            ->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
    }
}