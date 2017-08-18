<?php
namespace Hyper\EventBundle\Service\PresetFilterParser;

use \Symfony\Component\DependencyInjection\ContainerInterface
    , Aws\S3\S3Client
    , Hyper\EventBundle\Service\PresetFilterParser\Generator
    , Hyper\Domain\Device\Device
    , Hyper\EventBundle\Service\PresetFilterParser\ConditionQuery\HistoryConditionQuery\HistoryConditionQuery
    , Hyper\EventBundle\Service\PresetFilterParser\ConditionQuery\ConditionQueryAdapter;

class PresetFilterParser
{
    protected $filterParams = [];
    protected $countryCodes = [];
    protected $platformIds = [];
    protected $listAudienceType = [];
    protected $em;
    protected $query;
    protected $presetFilterId;
    protected $profileCount = 0;
    protected $exportCsvPath;
    protected $audienceCsvPath;
    protected $conditionQuery = '';
    protected $listRelationSupport = ['and' => 'INTERSECT', 'or' => 'UNION'];
    protected $conditionQueryWith = '';
    protected $listConditionQueryIndex;
    protected $listActionId = [];
    protected $listAppId = [];

    public function __construct(ContainerInterface $container, $presetFilterId, $filterParams = [])
    {
        /*$filterParams = [
            'country_codes' => ['SG','ID']
            , 'platform_ids' => [1,2]
            , 'audience' => [
                [
                    'history' =>  [
                        'type' => 'install_time_since'
                        , 'value' => '09/01/2015'
                        , 'in' => '57709497910bf3.51143334'
                    ]
                ]
                , [
                    'history' =>  [
                        'type' => 'last_happened_at'
                        , 'value' => '08/11/2016'
                        , 'in' => '57709a73831ec1.24000235'
                    ]
                    , 'append_relation' => 'and'
                ]
                , [
                    'usage' =>  [
                        'perform' => 'perform'
                        , 'behaviour_id' => 'purchase'
                        , 'frequent' => [
                            'type' => 'event_count'
                            , 'expression' => '>'
                            , 'value' => [2,'']
                        ]
                        , 'cat_id' => ''
                        , 'happened_at_from' => '11/09/2015'
                        , 'happened_at_to' => '08/13/2016'
                        , 'in' => '57709497910bf3.51143334'
                    ]
                    , 'append_relation' => 'or'
                ]
                , [
                    'usage' =>  [
                        'perform' => 'not_perform'
                        , 'behaviour_id' => 'purchase'
                        , 'frequent' => [
                            'type' => 'amount'
                            , 'expression' => '>'
                            , 'value' => [2,'']
                        ]
                        , 'cat_id' => ''
                        , 'happened_at_from' => '11/09/2015'
                        , 'happened_at_to' => '08/13/2016'
                        , 'in' => '57709497910bf3.51143334'
                    ]
                    , 'append_relation' => 'or'
                ]
            ]
        ];*/
        if (empty($presetFilterId)) {
            throw new \Exception('Filter id must have a value.');
        }
        if (empty($filterParams)) {
            throw new \Exception('Filter data must be a value.');
        }
        $this->container = $container;
        $this->em = $this->container->get('doctrine')->getManager('pgsql');
        $this->presetFilterId = $presetFilterId;
        $this->filterParams = $filterParams;
        try {
            $this->parseFilterParamsToProperties();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
        $this->init();
    }

    private function collectAppTitle($filterParams)
    {
        $listAppTitleId = [];
        if (!empty($this->listAudienceType)) {
            foreach ($this->listAudienceType as $conditionQueryIndex => $audienceType) {
                if (!empty($audienceType['history'])) {
                    $history = $audienceType['history'];
                    $appTitleId = '';
                    if (!empty($history['in'])) {
                       $listAppTitleId[] = $history['in'];
                    }
                } elseif (!empty($audienceType['usage'])) {
                    $usage = $audienceType['usage'];
                    $appTitleId = '';
                    if (!empty($usage['in'])) {
                       $listAppTitleId[] = $usage['in'];
                    }
                }
            }
        }
        if (!empty($listAppTitleId)) {
            $listAppFlatform = $this->em->getRepository('Hyper\Domain\Application\ApplicationPlatform')
                ->findByAppTitle($listAppTitleId);
            if (!empty($listAppFlatform)) {
                foreach ($listAppFlatform as $appPlatform) {
                    $this->listAppId[$appPlatform->getAppTitle()->getId()][] = $appPlatform->getAppId();
                }
            }
        }
    }

    public function parseFilterParamsToProperties()
    {
        $isParsed = false;
        if (
            isset($this->filterParams['country_codes']) &&
            !empty($this->filterParams['country_codes'])
        ) {
            if(!is_array($this->filterParams['country_codes'])) {
                $this->countryCodes = array($this->filterParams['country_codes']);
            } else {
                $this->countryCodes = $this->filterParams['country_codes'];
            }
            $isParsed = true;
        }

        if (
            isset($this->filterParams['platform_ids']) &&
            !empty($this->filterParams['platform_ids'])
        ) {
            $this->platformIds = $this->filterParams['platform_ids'];
            $isParsed = true;
        }
        if (!empty($this->filterParams['audience'])) {
            $this->listAudienceType = $this->filterParams['audience'];
            $isParsed = true;
        }
        if (!$isParsed) {
            throw new \Exception('Filter data is invalid.');
        }
        $this->collectAppTitle($this->filterParams);
    }

    public function getConditionQuery()
    {
        $query = $this->buildContitionQuery();

        return $query;
    }

    public function getConditionQueryWith()
    {
        return $this->conditionQueryWith;
    }

    protected function addConditionQueryWith($conditionQueryIndex, $conditionQuery)
    {
        if (empty($this->conditionQueryWith)) {
            $this->conditionQueryWith = "WITH condition_query_{$conditionQueryIndex} AS ($conditionQuery)";
        } else {
            $this->conditionQueryWith .= ", condition_query_{$conditionQueryIndex} AS ($conditionQuery)";
        }

        $this->conditionQueryWith = $this->conditionQueryWith . PHP_EOL;
    }

    protected function addConditionQuery($conditionQueryIndex, $relation)
    {
        $conditionQuery = "(SELECT DISTINCT device_id FROM condition_query_{$conditionQueryIndex})";
        if ($relation && !empty($this->listRelationSupport[$relation])) {
            $this->conditionQuery = implode(" ", [
                $this->conditionQuery
                , $this->listRelationSupport[$relation]
                , $conditionQuery
            ]);
            $this->conditionQuery = "(".$this->conditionQuery.")";
        } else {
            $this->conditionQuery = $conditionQuery;
        }
    }

    protected function buildContitionQuery()
    {
        if (!empty($this->listAudienceType)) {
            $next = false;
            foreach ($this->listAudienceType as $conditionQueryIndex => $audienceType) {
                if ($next) {
                    $next = false;
                    continue;
                }
                $relation = '';
                if (!empty($audienceType['append_relation'])) {
                    $relation = $audienceType['append_relation'];
                }
                if (!empty($audienceType['history'])) {
                    $history = $audienceType['history'];
                    $listAppId = [];
                    if (
                        !empty($history['in'])
                        && !empty($this->listAppId[$history['in']])
                    ) {
                        $listAppId = $this->listAppId[$history['in']];
                    }
                    // validate history usage
                    if (
                        empty($history['in'])
                        || empty($listAppId)
                        || empty($history['type'])
                        || empty($history['value'])
                    ) {
                        continue;
                    }
                    $installTimeSince = '';
                    $lastHappenedAt = '';
                    $installTimeFrom = '';
                    $installTimeTo = '';
                    $installTimeLast = '';
                    if (!empty($history['type']) && $history['type'] == 'install_time_since') {
                        $installTimeSince = $history['value'][0];
                    } elseif (!empty($history['type']) && $history['type'] == 'last_happened_at') {
                        $lastHappenedAt = $history['value'][0];
                    } elseif (!empty($history['type']) && $history['type'] == 'install_time_duration') {
                        $installTimeFrom = $history['value'][0];
                        $installTimeTo = $history['value'][1];
                    } elseif (!empty($history['type']) && $history['type'] == 'install_time_last') {
                        $installTimeLast = $history['value'][0];
                    }
                    $conditionQueryAdapter = new ConditionQueryAdapter(
                        new HistoryConditionQuery(
                            $this->container
                            , $this->platformIds
                            , $this->countryCodes
                            , $listAppId
                            , $installTimeSince
                            , $lastHappenedAt
                            , $installTimeFrom
                            , $installTimeTo
                            , $installTimeLast
                        )
                    );
                    $this->addConditionQueryWith($conditionQueryIndex, $conditionQueryAdapter->getQuery());
                    $this->addConditionQuery($conditionQueryIndex, $relation);
                    $this->listConditionQueryIndex[] = $conditionQueryIndex;
                    unset($this->listAudienceType[$conditionQueryIndex]);
                } else if (!empty($audienceType['usage'])) {
                    $usage = $audienceType['usage'];
                    $listAppId = [];
                    if (
                        !empty($usage['in'])
                        && !empty($this->listAppId[$usage['in']])
                    ) {
                        $listAppId = $this->listAppId[$usage['in']];
                    }
                    // validate device usage
                    if (
                        empty($usage['in'])
                        || empty($listAppId)
                        || empty($usage['behaviour_id'])
                    ) {
                        continue;
                    }
                    $frequentType = '';
                    if (!empty($usage['frequent']['type'])) {
                        $frequentType = $usage['frequent']['type'];
                    }
                    $frequentExp = '';
                    if (!empty($usage['frequent']['expression'])) {
                        $frequentExp = $usage['frequent']['expression'];
                    }
                    $frequentValue0 = '';
                    if (
                        isset($usage['frequent']['value'][0])
                        && $usage['frequent']['value'][0] != ''
                    ) {
                        $frequentValue0 = $usage['frequent']['value'][0];
                    }
                    $frequentValue1 = '';
                    if (
                        isset($usage['frequent']['value'][1])
                        && $usage['frequent']['value'][1] != ''

                    ){
                        $frequentValue1 = $usage['frequent']['value'][1];
                    }
                    $happenedAtType = '';
                    if (!empty($usage['happened_at_type'])) {
                        $happenedAtType = $usage['happened_at_type'];
                    }
                    $happenedAtValue0 = '';
                    $happenedAtValue1 = '';
                    if (!empty($usage['happened_at']['value'])) {
                        if (!empty($usage['happened_at']['value'][0])) {
                            $happenedAtValue0 = $usage['happened_at']['value'][0];
                        }
                        if (!empty($usage['happened_at']['value'][1])) {
                            $happenedAtValue1 = $usage['happened_at']['value'][1];
                        }
                    }
                    $contentType = '';
                    if (!empty($usage['cat_id'])) {
                        $contentType = $usage['cat_id'];
                    }
                    if (!empty($usage['perform']) && $usage['perform'] == 'not_perform') {
                        $usageConditionQueryClass = 'NotPerformUsageConditionQuery';
                    } else {
                        $usageConditionQueryClass = 'PerformUsageConditionQuery';
                    }
                    $usageConditionQueryClass = "Hyper\\EventBundle\\Service\\PresetFilterParser\\ConditionQuery\\UsageConditionQuery\\".$usageConditionQueryClass;
                    $usageConditionQuery = new $usageConditionQueryClass(
                        $this->container
                        , $this->platformIds
                        , $this->countryCodes
                        , $listAppId
                        , $usage['behaviour_id']
                        , $frequentType
                        , $frequentExp
                        , $frequentValue0
                        , $frequentValue1
                        , $happenedAtType
                        , $happenedAtValue0
                        , $happenedAtValue1
                        , $contentType
                    );
                    $conditionQueryAdapter = new ConditionQueryAdapter(
                        $usageConditionQuery
                    );
                    $this->addConditionQueryWith($conditionQueryIndex, $conditionQueryAdapter->getQuery());
                    $this->addConditionQuery($conditionQueryIndex, $relation);
                    $this->listConditionQueryIndex[] = $conditionQueryIndex;
                    unset($this->listAudienceType[$conditionQueryIndex]);
                }

            }
        }

        return $this->conditionQuery;
    }

    public function init()
    {
        try {
            $listAdvertisingId = [];
            $csvData = [];
            $bath = 10000;
            $i = 0;
            $isFirst = true;
            $this->profileCount = 0;
            $lastDeviceId = '';
            foreach ($this->generator() as $generator) {
                if ($lastDeviceId != $generator->getDeviceId()) {
                    $lastDeviceId = $generator->getDeviceId();
                    $this->profileCount++;
                }

                if (!empty($generator->getAdvertisingId())) {
                  $listAdvertisingId[] = $generator->getAdvertisingId();
                } elseif (!empty($generator->getIDFA())) {
                    $listAdvertisingId[] = $generator->getIDFA();
                }
                $csvData[] = $generator;
                if ($i % $bath == 0 && $i != 0) {
                    $this->putDataIntoExportCSV($csvData, $isFirst);
                    $this->putDataIntoAudienceCSV($csvData, $isFirst);
                    $isFirst = false;
                    $csvData = [];
                }
                $i++;
            }
            $this->putDataIntoExportCSV($csvData, $isFirst);
            $this->putDataIntoAudienceCSV($csvData, $isFirst);
            $this->putDeviceReferToFilter();
        } catch (\Exception $e){
            echo $e->getMessage();
            exit;
        }
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

    protected function getQueryListAction()
    {
        $conditionQuery = $this->getConditionQuery();
        if (!$conditionQuery) {
            return;
        }
        $referenceQuery = $this->getConditionQueryWith();
        if (!$referenceQuery) {
            return;
        }
        $from = [];
        foreach ($this->listConditionQueryIndex as $conditionQueryIndex) {
            $from[] = "(SELECT * FROM condition_query_{$conditionQueryIndex})";
        }
        $from = implode(' UNION ', $from);
        $query = $referenceQuery . "SELECT * FROM ($from) WHERE device_id IN ($conditionQuery)";

        return $query;
    }

    public function getListActionId()
    {
        $query = $this->getQueryListAction();
        $stmtQueryGroup = $this->em->getConnection()->prepare("set query_group to 'ak_low_priority_long_processing_time';");
        $stmtQueryGroup->execute();
        $stmt = $this->em->getConnection()->prepare($query);
        $stmt->execute();
        $stmtQueryGroup = $this->em->getConnection()->prepare("reset query_group;");
        $stmtQueryGroup->execute();
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $listActionId = explode('|', $row['list_action']);
            $this->listActionId = array_merge($this->listActionId, $listActionId);
       }

       return $this->listActionId;
    }

    public function generator()
    {
        $listActionId = $this->getListActionId();
        if (!$listActionId) {
            return;
        }
        $listActionId = array_unique($listActionId);
        $listActionIdStr = implode("','", $listActionId);
        $query = "
            SELECT DISTINCT
                  tmp.device_id
                , devices.country_code
                , devices.platform
                , devices.install_time
                , ( SELECT MAX(actions.happened_at)
                    FROM actions
                    WHERE tmp.device_id = actions.id
                        AND tmp.app_id = actions.app_id
                    LIMIT 1
                ) AS last_activity
                , tmp.happened_at
                , tmp.event_name
                , tmp.af_content_type
                , ios_devices.idfa AS idfa
                , android_devices.advertising_id AS advertising_id
            FROM (
                SELECT device_id,happened_at,event_name,af_content_type,app_id
                FROM actions
                WHERE id IN ('$listActionIdStr')
            ) AS tmp
                LEFT JOIN android_devices ON android_devices.id = tmp.device_id
                LEFT JOIN ios_devices ON ios_devices.id = tmp.device_id
                LEFT JOIN devices ON devices.id = tmp.device_id
            ORDER BY tmp.device_id ASC
        ";
        $stmtQueryGroup = $this->em->getConnection()->prepare("set query_group to 'ak_low_priority_long_processing_time';");
        $stmtQueryGroup->execute();
        $stmt = $this->em->getConnection()->prepare($query);
        $stmt->execute();
        $stmtQueryGroup = $this->em->getConnection()->prepare("reset query_group;");
        $stmtQueryGroup->execute();
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
                $row['advertising_id']
            );
       }
    }

    public function putDataIntoExportCSV($csvData, $isFirst)
    {
        $data = '';
        if ($isFirst) {
            $data = "Hypid,Country,Platform,Install Date,Last Activity,Date,Event,Interest" . PHP_EOL;
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
            $data .= implode(",", [
                $obj->getDeviceId()
                , $obj->getCountryCode()
                , $platform
                , date('Y-m-d H:i:s',$obj->getInstallTime())
                , date('Y-m-d H:i:s',$obj->getLastActivity())
                , date('Y-m-d H:i:s',$obj->getHappenedAt())
                , $obj->getEventName()
                , $obj->getAfContentType()
            ]);
            $data .= PHP_EOL;
        }
        $s3Client = $this->container->get('hyper_event_processing.s3_wrapper')->getS3Client();
        $bucket = $this->container->getParameter('amazon_s3_filter_bucket_name');
        $key = 'export_' . $this->presetFilterId . '.csv';
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

    public function putDataIntoAudienceCSV($csvData, $isFirst)
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
        $key = 'audience_' . $this->presetFilterId . '.csv';
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

    public function putDeviceReferToFilter()
    {
        $query = $this->getQueryListAction();
        if (!$query) {
            return;
        }
        $query1 = "DELETE FROM device_preset_filter WHERE preset_filter_id='{$this->presetFilterId}' AND status = 0 ";
        $query2 = "INSERT INTO device_preset_filter(device_id,preset_filter_id) SELECT device_id, '{$this->presetFilterId}' AS preset_filter_id FROM ($query) WHERE device_id NOT IN ( SELECT device_id from device_preset_filter where  preset_filter_id = {$this->presetFilterId} AND status NOT IN (-1,1)  ) ";
        $stmtQueryGroup = $this->em->getConnection()->prepare("set query_group to 'ak_low_priority_long_processing_time';");
        $stmtQueryGroup->execute();
        $stmt = $this->em->getConnection()->prepare($query1);
        $stmt->execute();
        $stmt = $this->em->getConnection()->prepare($query2);
        $stmt->execute();
        $stmtQueryGroup = $this->em->getConnection()->prepare("reset query_group;");
        $stmtQueryGroup->execute();

    }
}