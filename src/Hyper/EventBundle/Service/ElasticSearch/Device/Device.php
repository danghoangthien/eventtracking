<?php
namespace Hyper\EventBundle\Service\ElasticSearch\Device;

use Symfony\Component\DependencyInjection\ContainerInterface,
    Hyper\EventBundle\Service\ElasticSearch\BaseElasticSearch;

/**
 * @author Carl Pham <vanca.vnn@gmail.com>
 */
class Device extends BaseElasticSearch
{
    const TOTAL_ROW_JSON_FILE = 5000;

    protected $deviceRepo;
    protected $tmpDeviceIdDir;
    protected $tmpDeviceIdFile;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->deviceRepo = $this->container->get('device_repository');
        $this->indexName = 'devices';
    }

    public function perform($fullTable = false)
    {
        if ($fullTable) {
            // TODO: Need split for each file like actions
            $devices = $this->deviceRepo->findAll();
            $this->processIndexDevice($devices);
        } else {
            $deviceIds = file_get_contents($this->tmpDeviceIdDir.'/'.self::TMP_DEVICE_ID_FILE);
            $deviceIds = substr($deviceIds, 0, -1);
            $deviceIds = explode(',', $deviceIds);
            $deviceIds = array_unique($deviceIds);
            if (count($deviceIds) <= self::TOTAL_ROW_EACH_FILE) {
                $devices = $this->deviceRepo->getDeviceForEs($deviceIds);
                $this->processIndexDevice($devices);
                return;
            }
            $deviceIdChunks = array_chunk($deviceIds, self::TOTAL_ROW_EACH_FILE);

            $errors = [];
            foreach ($deviceIdChunks as $k => $deviceIdChunk) {
                $devices = $this->deviceRepo->getDeviceForEs($deviceIdChunk);
                $this->processIndexDevice($devices, $k);
                sleep(5);
            }
            unlink($this->tmpDeviceIdDir.'/'.self::TMP_DEVICE_ID_FILE);
        }
    }
    
    public function processIndexDevice($devices, $sufixJsonFileName = '')
    {
        $jsonESSyntax = '';
        $fs = $this->getFS();
        foreach ($devices as $row) {
            $jsonESSyntax .= $this->makeESSyntax($row);
        }
        $pathJson = $this->esDir."/".$this->indexName."/".$this->indexName."_$sufixJsonFileName.json";
        $fs->dumpFile($pathJson, $jsonESSyntax);

        echo "Push file: $pathJson \n";
        $rs = $this->pushDataToElasticSearch('devices', 'devices', $pathJson);
        if ($rs['response_code'] == 200 || $rs['response_code'] == 201) {
            $fs->remove($pathJson);
        } else {
            throw new \Exception("Error push data to ES:\n Json path: $pathJson\n Response code: ".$rs['response_code']);
        }
    }
    
}