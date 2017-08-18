<?php
namespace Hyper\EventBundle\Service\FilterService;

use Hyper\Domain\Filter\Filter;
use Hyper\EventBundle\Service\FilterService\Condition\DataType\HistoryDataType;
use Hyper\EventBundle\Service\FilterService\Condition\DataType\UsageDataType;
use Hyper\EventBundle\Service\FilterService\Condition\DataType\RelationDataType;
use Hyper\EventBundle\Service\FilterService\Condition\DataType\AppTitleDataType;
use Hyper\EventBundle\Service\FilterService\Condition\ConditionBuilder;
use Hyper\EventBundle\Service\FilterService\Condition\Generator;
use Hyper\Domain\Device\Device;

class FilterService
{
    private $container;
    private $connection;
    private $conditionBuilder;
    private $listAppTitleDataType;
    private $profileCount;
    private $exportCsvPath;
    private $audienceCsvPath;
    private $emailCsvPath;
    private $filterId;

    public function __construct($container)
    {
        $this->container = $container;
        $this->initConnection();
        $this->conditionBuilder = new ConditionBuilder($this->connection);
    }

    private function initConnection()
    {
        $this->connection = $this->container->get('doctrine')->getManager('pgsql')->getConnection();
    }

    public function execute(Filter $filter) {
        try {
            $this->assertFilter($filter);
            $this->assertDataType($filter->getFilterData());
        } catch(\Exception $e) {
            \Hyper\EventBundle\Service\FilterService\FilterService::logger('error', $e->getMessage());
            throw new \Exception($e->getMessage());
        }
        $this->filterId = $filter->getId();
        try {
            $stmt = $this->connection->prepare("set query_group to 'ak_low_priority_long_processing_time';");
            $stmt->execute();
            $conditionTable = $this->conditionBuilder->build();
            $this->profileCount = $this->executeProfileCount($conditionTable);
            $executeGeneratorFunc = true;
            if (empty($this->profileCount)) {
                $executeGeneratorFunc = false;
            }
            \Hyper\EventBundle\Service\FilterService\FilterService::logger('profile count', $this->profileCount);
            list($this->exportCsvPath, $this->audienceCsvPath, $this->emailCsvPath) = $this->executeGeneratorCSV($conditionTable, $executeGeneratorFunc);
            \Hyper\EventBundle\Service\FilterService\FilterService::logger('export csv', $this->exportCsvPath);
            \Hyper\EventBundle\Service\FilterService\FilterService::logger('audience csv', $this->audienceCsvPath);
            \Hyper\EventBundle\Service\FilterService\FilterService::logger('email csv', $this->emailCsvPath);
            $this->executeDeviceReferToFilter($conditionTable);
            $stmt = $this->connection->prepare("reset query_group;");
            $stmt->execute();
        } catch(\Exception $e) {
            \Hyper\EventBundle\Service\FilterService\FilterService::logger('error', $e->getMessage());
            throw new \Exception($e->getMessage());

        }

        return $this;

    }

    public function executeOnlyProfileCount(Filter $filter)
    {
        try {
            $this->assertFilter($filter);
            $this->assertDataType($filter->getFilterData());
        } catch(\Exception $e) {
            \Hyper\EventBundle\Service\FilterService\FilterService::logger('error', $e->getMessage());
            throw new \Exception($e->getMessage());
        }
        $this->filterId = $filter->getId();
        try {
            $stmt = $this->connection->prepare("set query_group to 'ak_low_priority_long_processing_time';");
            $stmt->execute();
            $conditionTable = $this->conditionBuilder->build();
            $this->profileCount = $this->executeProfileCount($conditionTable);
            \Hyper\EventBundle\Service\FilterService\FilterService::logger('profile count', $this->profileCount);
            $stmt = $this->connection->prepare("reset query_group;");
            $stmt->execute();
        } catch(\Exception $e) {
            \Hyper\EventBundle\Service\FilterService\FilterService::logger('error', $e->getMessage());
            throw new \Exception($e->getMessage());

        }

        return $this;
    }

    private function assertFilter(Filter $filter)
    {
        $filterData = $filter->getFilterData();
        if (empty($filterData)) {
            throw new \Exception('The filter data must be value.');
        }
    }



    private function assertDataType($filterData)
    {
        $this->assertAppTitleDataType($filterData);
        foreach ($filterData['audience'] as $audienceIndex => $audienceTypeData) {
            $audienceType = '';
            $relation = '';
            if (!empty($audienceTypeData['append_relation'])) {
                $relation = $audienceTypeData['append_relation'];
            }
            $relationDataType = new RelationDataType($relation);
            if (!empty($audienceTypeData['history'])) {
                $appTitleId = '';
                if (isset($audienceTypeData['history']['in'])) {
                    $appTitleId = $audienceTypeData['history']['in'];
                }
                $appTitleDataType = '';
                if (!empty($appTitleId) && !empty($this->listAppTitleDataType[$appTitleId])) {
                    $appTitleDataType = $this->listAppTitleDataType[$appTitleId];
                }
                $historyType = '';
                if (isset($audienceTypeData['history']['type'])) {
                    $historyType = $audienceTypeData['history']['type'];
                }
                $historyValue0 = '';
                if (isset($audienceTypeData['history']['value'][0])) {
                    $historyValue0 = $audienceTypeData['history']['value'][0];
                }
                $historyValue1 = '';
                if (isset($audienceTypeData['history']['value'][1])) {
                    $historyValue1 = $audienceTypeData['history']['value'][1];
                }

                $audienceType = new HistoryDataType(
                    $this->connection
                    , $appTitleDataType
                    , $relationDataType
                    , $historyType
                    , $historyValue0
                    , $historyValue1
                );
            } else if (!empty($audienceTypeData['usage'])) {
                $appTitleId = '';
                if (isset($audienceTypeData['usage']['in'])) {
                    $appTitleId = $audienceTypeData['usage']['in'];
                }
                $appTitleDataType = '';
                if (!empty($appTitleId) && !empty($this->listAppTitleDataType[$appTitleId])) {
                    $appTitleDataType = $this->listAppTitleDataType[$appTitleId];
                }
                $perform = '';
                if (isset($audienceTypeData['usage']['perform'])) {
                    $perform = $audienceTypeData['usage']['perform'];
                }
                $eventName = '';
                if (isset($audienceTypeData['usage']['behaviour_id'])) {
                    $eventName = $audienceTypeData['usage']['behaviour_id'];
                }
                $contentType = '';
                if (isset($audienceTypeData['usage']['cat_id'])) {
                    $contentType = $audienceTypeData['usage']['cat_id'];
                }
                $frequentType = '';
                if (isset($audienceTypeData['usage']['frequent']['type'])) {
                    $frequentType = $audienceTypeData['usage']['frequent']['type'];
                }
                $frequentExp = '';
                if (isset($audienceTypeData['usage']['frequent']['expression'])) {
                    $frequentExp = $audienceTypeData['usage']['frequent']['expression'];
                }
                $frequentValue0 = '';
                if (isset($audienceTypeData['usage']['frequent']['value'][0])) {
                    $frequentValue0 = $audienceTypeData['usage']['frequent']['value'][0];
                }
                $frequentValue1 = '';
                if (isset($audienceTypeData['usage']['frequent']['value'][1])) {
                    $frequentValue1 = $audienceTypeData['usage']['frequent']['value'][1];
                }
                $happenedAtType = '';
                if (isset($audienceTypeData['usage']['happened_at']['type'])) {
                    $happenedAtType = $audienceTypeData['usage']['happened_at']['type'];
                }
                $happenedAtValue0 = '';
                if (isset($audienceTypeData['usage']['happened_at']['value'][0])) {
                    $happenedAtValue0 = $audienceTypeData['usage']['happened_at']['value'][0];
                }
                $happenedAtValue1 = '';
                if (isset($audienceTypeData['usage']['happened_at']['value'][1])) {
                    $happenedAtValue1 = $audienceTypeData['usage']['happened_at']['value'][1];
                }
                $audienceType = new UsageDataType(
                    $this->connection
                    , $appTitleDataType
                    , $relationDataType
                    , $perform
                    , $eventName
                    , $contentType
                    , $frequentType
                    , $frequentExp
                    , $frequentValue0
                    , $frequentValue1
                    , $happenedAtType
                    , $happenedAtValue0
                    , $happenedAtValue1
                );
            }
            if (!empty($audienceType)) {
                $this->conditionBuilder->add($audienceType);
            }
        }
    }

    private function assertAppTitleDataType($filterData)
    {
        $platform = [];
        if (isset($filterData['platform_ids'])) {
            $platform = $filterData['platform_ids'];
        }
        $countryCode = [];
        if (isset($filterData['country_codes'])) {
            $countryCode = $filterData['country_codes'];
        }
        $appTitleId = [];
        $listAppFlatform = [];
        foreach ($filterData['audience'] as $audienceIndex => $audienceTypeData) {
            if (!empty($audienceTypeData['history']['in'])) {
               $appTitleId[] = $audienceTypeData['history']['in'];
            } else if (!empty($audienceTypeData['usage']['in'])) {
                $appTitleId[] = $audienceTypeData['usage']['in'];
            }
        }
        $listAppTitle = [];
        if (!empty($appTitleId)) {
            $_listAppFlatform = $this->container->get('application_platform_repository')->findByAppTitle($appTitleId);
            if (!empty($_listAppFlatform)) {
                foreach ($_listAppFlatform as $appPlatform) {
                    $listAppTitle[$appPlatform->getAppTitle()->getId()][] = $appPlatform->getAppId();
                }
            }
        }
        if (!empty($listAppTitle)) {
            foreach ($listAppTitle as $appTitleId => $appId) {
                $appTitleDataType = new AppTitleDataType(
                    $this->connection
                    , $appTitleId
                    , $appId
                    , $platform
                    , $countryCode
                );
                $this->listAppTitleDataType[$appTitleId] = $appTitleDataType;
            }
        }
    }

    private function executeProfileCount($conditionTable)
    {
        $query = "SELECT COUNT(device_id) AS profile_count FROM $conditionTable";
        \Hyper\EventBundle\Service\FilterService\FilterService::logger('query in profile count',$query);
        $stmt = $this->connection->prepare($query);
        $stmt->execute();

        return $stmt->fetchColumn(0);
    }

    private function executeGeneratorFunc($conditionTable)
    {
        $unionTableTempCreated = [];
        foreach ($this->conditionBuilder->getListTableUnion() as $tableTempCreated) {
            $unionTableTempCreated[] = "(SELECT * FROM $tableTempCreated)";
        }
        $unionTableTempCreated = implode(" UNION ", $unionTableTempCreated);
        $query = "
                SELECT
                    tmp.device_id
                    , devices.country_code
                    , devices.platform
                    , devices.install_time
                    , (
                        SELECT MAX(actions.happened_at)
                        FROM actions
                        WHERE tmp.device_id = actions.device_id
                            AND tmp.app_id = actions.app_id
                    ) AS last_activity
                    , tmp.happened_at
                    , tmp.event_name
                    , tmp.af_content_type
                    , ios_devices.idfa AS idfa
                    , android_devices.advertising_id AS advertising_id
                    , android_devices.android_id AS android_id
                    , identity_capture.email
                FROM (
                    SELECT device_id
                        , happened_at
                        , event_name
                        , af_content_type
                        , app_id
                    FROM ($unionTableTempCreated) WHERE device_id IN (SELECT device_id FROM $conditionTable)
                ) tmp
                LEFT JOIN android_devices ON android_devices.id = tmp.device_id
                LEFT JOIN ios_devices ON ios_devices.id = tmp.device_id
                LEFT JOIN devices ON devices.id = tmp.device_id
                LEFT JOIN identity_capture ON identity_capture.device_id = tmp.device_id
            "
        ;
        \Hyper\EventBundle\Service\FilterService\FilterService::logger('query in generator csv',$query);
        $stmt = $this->connection->prepare($query);
        $stmt->execute();
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            yield new Generator(
                $row['device_id'],
                $row['country_code'],
                $row['platform'],
                $row['install_time'],
                $row['last_activity'],
                $row['happened_at'],
                $row['event_name'],
                $row['af_content_type'],
                $row['idfa'],
                $row['advertising_id'],
                $row['android_id'],
                $row['email']
            );
        }
    }

    private function executeGeneratorCSV($conditionTable, $executeGeneratorFunc)
    {
        $csvData = [];
        $bath = 10000;
        $i = 0;
        $isFirst = true;
        if ($executeGeneratorFunc) {
            foreach ($this->executeGeneratorFunc($conditionTable) as $generator) {
                $csvData[] = $generator;
                if ($i % $bath == 0 && $i != 0) {
                    $this->putDataIntoExportCSV($csvData, $isFirst);
                    $this->putDataIntoAudienceCSV($csvData, $isFirst);
                    $this->putDataIntoEmailCSV($csvData, $isFirst);
                    $isFirst = false;
                    $csvData = [];
                }
                $i++;
            }
        }
        $this->putDataIntoExportCSV($csvData, $isFirst);
        $this->putDataIntoAudienceCSV($csvData, $isFirst);
        $this->putDataIntoEmailCSV($csvData, $isFirst);
        return [
            $this->exportCsvPath
            , $this->audienceCsvPath
            , $this->emailCsvPath
        ];
    }

    public function getProfileCount()
    {
        return $this->profileCount;
    }

    public function getExportCsvPath()
    {
        return $this->exportCsvPath;
    }

    public function getAudienceCsvPath()
    {
        return $this->audienceCsvPath;
    }

    public function getEmailCsvPath()
    {
        return $this->emailCsvPath;
    }

    private function putDataIntoExportCSV($csvData, $isFirst)
    {
        $data = '';
        if ($isFirst) {
            $data = "Hypid,Advertising ID,idfa,Country,Platform,Install Date,Last Activity,Date,Event,Interest" . PHP_EOL;
        }
        $listPlatform = [
            Device::ANDROID_PLATFORM_CODE  => Device::ANDROID_PLATFORM_NAME,
            Device::IOS_PLATFORM_CODE  => Device::IOS_PLATFORM_NAME
        ];
        foreach ($csvData as $obj) {
            $platform = '';
            if (!empty($listPlatform[$obj->getPlatform()])) {
                $platform = $listPlatform[$obj->getPlatform()];
            }
            $installTime = '';
            if (!empty($obj->getInstallTime())) {
                $installTime = date('Y-m-d H:i:s',$obj->getInstallTime());
            }
            $lastActivity = '';
            if (!empty($obj->getLastActivity())) {
                $lastActivity = date('Y-m-d H:i:s',$obj->getLastActivity());
            }
            $happenedAt = '';
            if (!empty($obj->getHappenedAt())) {
                $happenedAt = date('Y-m-d H:i:s',$obj->getHappenedAt());
            }
            // $androidId = '';
            // if ($obj->getAndroidId()) {
            //     $androidId = $obj->getAndroidId();
            // }
            $advertisingId = '';
            if ($obj->getAdvertisingId()) {
                $advertisingId = $obj->getAdvertisingId();
            }
            $idfa  = '';
            if ($obj->getIDFA()) {
                $idfa = $obj->getIDFA();
            }
            $data .= implode(",", [
                $obj->getDeviceId()
                , $advertisingId
                , $idfa
                , $obj->getCountryCode()
                , $platform
                , $installTime
                , $lastActivity
                , $happenedAt
                , $obj->getEventName()
                , $obj->getAfContentType()
            ]);
            $data .= PHP_EOL;
        }
        $s3Client = $this->container->get('hyper_event_processing.s3_wrapper')->getS3Client();
        $bucket = $this->container->getParameter('amazon_s3_filter_bucket_name');
        $key = 'export_' . $this->filterId . '.csv';
        $this->exportCsvPath = "s3://{$bucket}/{$key}";
        $opts['s3']=array(
         	'ContentType'=>'text/csv',
        	'StorageClass'=>'REDUCED_REDUNDANCY'
        );
        // Register the stream wrapper from a client object
        $s3Client->registerStreamWrapper();
        $context = stream_context_create($opts);
        if ($isFirst) {
            file_put_contents($this->exportCsvPath, '', 0, $context);
        }
        file_put_contents($this->exportCsvPath, $data, FILE_APPEND, $context);
    }

    private function putDataIntoAudienceCSV($csvData, $isFirst)
    {
        $data = '';
        foreach ($csvData as $obj) {
            if ($obj->getIDFA()) {
                $data .= $obj->getIDFA();
                $data .= PHP_EOL;
            } elseif ($obj->getAdvertisingId()) {
                $data .= $obj->getAdvertisingId();
                $data .= PHP_EOL;
            }
        }
        $s3Client = $this->container->get('hyper_event_processing.s3_wrapper')->getS3Client();
        $bucket = $this->container->getParameter('amazon_s3_filter_bucket_name');
        $key = 'audience_' . $this->filterId . '.csv';
        $this->audienceCsvPath = "s3://{$bucket}/{$key}";
        $opts['s3']=array(
         	'ContentType'=>'text/csv',
        	'StorageClass'=>'REDUCED_REDUNDANCY'
        );
        // Register the stream wrapper from a client object
        $s3Client->registerStreamWrapper();
        $context = stream_context_create($opts);
        if ($isFirst) {
            file_put_contents($this->audienceCsvPath, '', 0, $context);
        }
        file_put_contents($this->audienceCsvPath, $data, FILE_APPEND, $context);
    }

    private function putDataIntoEmailCSV($csvData, $isFirst)
    {
        $data = '';
        foreach ($csvData as $obj) {
            if ($obj->getEmail()) {
                $data .= $obj->getEmail();
                $data .= PHP_EOL;
            }
        }
        $s3Client = $this->container->get('hyper_event_processing.s3_wrapper')->getS3Client();
        $bucket = $this->container->getParameter('amazon_s3_filter_bucket_name');
        $key = 'email_' . $this->filterId . '.csv';
        $this->emailCsvPath = "s3://{$bucket}/{$key}";
        $opts['s3']=array(
         	'ContentType'=>'text/csv',
        	'StorageClass'=>'REDUCED_REDUNDANCY'
        );
        // Register the stream wrapper from a client object
        $s3Client->registerStreamWrapper();
        $context = stream_context_create($opts);
        if ($isFirst) {
            file_put_contents($this->emailCsvPath, '', 0, $context);
        }
        file_put_contents($this->emailCsvPath, $data, FILE_APPEND, $context);
    }

    private function executeDeviceReferToFilter($conditionTable)
    {
        $query = "DELETE FROM device_preset_filter WHERE preset_filter_id='{$this->filterId}' AND status = 0";
        \Hyper\EventBundle\Service\FilterService\FilterService::logger('query delete in executeDeviceReferToFilter',$query);
        $stmt = $this->connection->prepare($query)->execute();
        $query = "
            INSERT INTO device_preset_filter(device_id,preset_filter_id)
            SELECT device_id, '{$this->filterId}' AS preset_filter_id
            FROM $conditionTable
            WHERE device_id NOT IN (
                SELECT device_id
                FROM device_preset_filter
                WHERE preset_filter_id = '{$this->filterId}'
                    AND status NOT IN (-1,1)
            )";
        \Hyper\EventBundle\Service\FilterService\FilterService::logger('query insert in executeDeviceReferToFilter',$query);
        $stmt = $this->connection->prepare($query)->execute();
    }

    public static function logger($type, $msg)
    {
        if (!empty($type) && $type == 'error') {
            echo $type. ": ".$msg. "\n";
        } else {
            echo $type. ": ".$msg. "\n";
        }
    }

}