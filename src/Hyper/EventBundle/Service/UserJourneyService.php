<?php
namespace Hyper\EventBundle\Service;

use \Symfony\Component\DependencyInjection\ContainerInterface,
    Hyper\Domain\Device\Device,
    Hyper\Domain\Application\ApplicationTitle,
    Hyper\Domain\IdentityCapture\IdentityCapture,
    Hyper\Domain\Authentication\Authentication,
    Hyper\Domain\Currency\CurrencyException
    , Hyper\EventBundle\Service\Cached\InappeventConfig\InappeventConfigCached
    , Doctrine\Common\Util\Inflector
    , Hyper\EventBundle\Service\UserjourneyService\Validator\EmailValidator
    , Hyper\EventBundle\Service\UserjourneyService\Validator\EmailValidationHandler
    , Hyper\EventBundle\Service\UserjourneyService\Validator\IDFAValidator
    , Hyper\EventBundle\Service\UserjourneyService\Validator\IDFAValidationHandler
    , Hyper\EventBundle\Service\UserjourneyService\Validator\AndroidIdValidator
    , Hyper\EventBundle\Service\UserjourneyService\Validator\AndroidIdValidationHandler
    , Hyper\EventBundle\Service\UserjourneyService\Validator\DeviceValidator
    , Hyper\EventBundle\Service\UserjourneyService\Validator\DeviceValidationHandler;

class UserJourneyService
{
    const LIST_ACTION_SIZE = 8;
    // validate
    const EMAIL_VALIDATE = 'email';
    const IDFA_VALIDATE = 'idfa';
    const ANDROID_ID_VALIDATE = 'android_id';
    const DEVICE_VALIDATE = 'device';

    protected $container;
    // repo
    protected $deviceRepo;
    protected $appTitleRepo;
    protected $deviceIosRepo;
    protected $deviceAndroidRepo;
    protected $identityCaptureRepo;
    protected $devicepresetFilterRepo;
    protected $actionRepo;
    protected $currencyRepo;
    protected $clientRepo;
    protected $clientAppTitleRepo;
    protected $appPlatformRepo;

	// app title
	protected $listAppId = [];
	protected $listS3Folder = [];

	// cached
	protected $iaeConfigCached;
	protected $listEventTagAsIAP;
    protected $listEventIAP;

    // es
    protected $esClient;
    protected $deviceIndex = 'devices';
    protected $iosDeviceIndex = 'ios_devices';
    protected $androidDeviceIndex = 'android_devices';

    protected $deviceId;
    protected $device;
    protected $deviceLatest;

    protected $listActions;
    protected $eventValueParams = ['af_revenue','af_price','af_level','af_success','af_content_type',
        'af_content_list','af_content_id','af_currency','af_registration_method','af_quantity',
        'af_payment_info_available','af_rating_value','af_max_rating_value','af_search_string',
        'af_description','af_score','af_destination_a','af_destination_b','af_class','af_date_a',
        'af_date_b','af_event_start','af_event_end','af_lat','af_long','af_customer_user_id',
        'af_validated','af_receipt_id','af_param1','af_param2','af_param3','af_param4','af_param5',
        'af_param6','af_param7','af_param8','af_param9','af_param10'];

    public function __construct(ContainerInterface $container, $devideId = '')
    {
        $this->container = $container;
        $this->deviceId = $devideId;

    }

    public function initData()
    {
    // 	$this->listAppId = ['com.mefashionita.android'];
    //     $this->listS3Folder = ['mefashionista'];

    	return $this;
    }

    public function initRepo()
    {
    	$this->appTitleRepo = $this->container->get('application_title_repository');
        $this->deviceRepo = $this->container->get('device_repository');
        $this->deviceIosRepo = $this->container->get('ios_device_repository');
        $this->deviceAndroidRepo = $this->container->get('android_device_repository');
        $this->actionRepo = $this->container->get('action_repository');
        $this->devicepresetFilterRepo = $this->container->get('device_preset_filter_repository');
        $this->currencyRepo = $this->container->get('currency_repository');
        $this->clientRepo = $this->container->get('client_repository');
        $this->clientAppTitleRepo = $this->container->get('client_app_title_repository');
        $this->appPlatformRepo = $this->container->get('application_platform_repository');
        $this->identityCaptureRepo = $this->container->get('identity_capture_repository');

        return $this;
    }

    public function initCached()
    {
    	$this->iaeConfigCached = new InappeventConfigCached($this->container);

    	return $this;
    }

    public function initListAppId()
    {
    	$auth = $this->container->get('security.context')->getToken()->getUser();
    	$this->listAppId = $auth->getAppId();
    	$this->listS3Folder = $auth->getS3Folder();

        return $this;
    }

    public function initDeviceLatest()
    {
        $existIndex = true;
        $esClient = $this->getEsClient();
		$esSearch = new \Elastica\Search($esClient);
        foreach ($this->listS3Folder as $s3Folder) {
            $esSearch->addIndex($s3Folder);
            if (!$esClient->getIndex($s3Folder)->exists()) {
    		    $existIndex = false;
    		    break;
    		}
        }
        $deviceLatest = [];
        if ($existIndex) {
            foreach ($this->listAppId as $appId) {
                $esSearch->addType($appId);
            }
            $query = new \Elastica\Query();
            $query
                ->setSize(1)
                ->addSort(
                	[
                		'happened_at' => ['order' => 'desc']
                	]
                );
            $esSearch->setQuery($query);
            $resultSet = $esSearch->search();
            try {
            	$result = $resultSet->offsetGet(0);
            	$deviceLatest = $result->getData();
            } catch (\Exception $e) {

            }
        }

    	if (!empty($deviceLatest)) {
    		$this->deviceLatest = $deviceLatest['device_id'];
    	}

    	if (empty($this->deviceLatest)) {
    		$this->deviceLatest = $this->actionRepo->getLatestDevice($this->listAppId);
    	}

    	return $this;
    }

    public function initIAPConfig()
    {
    	if (
    		empty($this->listAppId)
    		|| !$this->iaeConfigCached->exists()
    	) {
    		return;
    	}
    	foreach($this->listAppId as $appId) {
    		if (!$iaeConfig = $this->iaeConfigCached->hget($appId)) {
    			continue;
    		}
    		$iaeConfig = json_decode($iaeConfig, true);
    		$this->listEventIAP[$appId] = $iaeConfig;
    		if (!empty($iaeConfig)) {
    			foreach ($iaeConfig as $eventName => $value) {
    				if (!empty($value['tag_as_iap'])) {
    					$this->listEventTagAsIAP[] = $eventName;
    				}
    			}
    		}
    	}

    	return $this;
    }

    public function getEsClient()
    {
    	if (!$this->esClient) {
    	    $elasticaClient = new \Hyper\EventBundle\Service\HyperESClient($this->container);
            $this->esClient = $elasticaClient->getClient();
    	}

    	return $this->esClient;
    }

    public function initDevice()
    {
        $existIndex = true;
        $esClient = $this->getEsClient();
        if (!$esClient->getIndex($this->deviceIndex)->exists()) {
		    $existIndex = false;
		}
		if ($existIndex) {
		    $esSearch = new \Elastica\Search($esClient);
    		$esSearch->addIndex($this->deviceIndex);
            $esSearch->addType($this->deviceIndex);
            $query = new \Elastica\Query();
    		$matchQuery = new \Elastica\Query\Match();
            $matchQuery->setField('id', $this->deviceId);
            $query
                ->setQuery($matchQuery)
                ->setSize(1);
            $esSearch->setQuery($query);
            $resultSet = $esSearch->search();
            try {
            	$result = $resultSet->offsetGet(0);
            	$device = $result->getData();
            	if (!empty($device)) {
            		$this->device = [
    	        		'country_code' => $device['country_code']
    	        		, 'platform' => $device['platform']
    	        		, 'model' => ''
    	        		, 'email' => $device['email']
    	        	];
            	}
            } catch (\Exception $e) {
            }
		}

        if (empty($this->device)) {
        	$_device = $this->deviceRepo->find($this->deviceId);
        	if (!empty($_device)) {
        		$this->device = [
	        		'country_code' => $_device->getCountryCode()
	        		, 'platform' => $_device->getPlatForm()
	        		, 'model' => ''
	        		, 'email' => ''
	        	];
        	}
        }
        if (
        	!empty($this->device['platform'])
        	&& $this->device['platform'] == Device::IOS_PLATFORM_CODE
        ) {
			$deviceIOS = $this->getDeviceIOS();
        	if (!empty($deviceIOS)) {
        		$this->device['model'] = $deviceIOS['model'];
        	}
        } elseif (
        	!empty($this->device['platform'])
        	&& $this->device['platform'] == Device::ANDROID_PLATFORM_CODE
        ) {
        	$deviceAndroid = $this->getDeviceAndroid();
        	if (!empty($deviceAndroid)) {
        		$this->device['model'] = $deviceAndroid['model'];
        	}
        }
        if (empty($this->device['email'])) {
        	$identityCapture = $this->identityCaptureRepo->findOneBy([
	        	'deviceId' => $this->deviceId
	        ]);
	        if (!empty($identityCapture)) {
	        	$this->device['email'] = $identityCapture->getEmail();
	        }
        }

        return $this;
    }

    public function getDeviceIOS()
    {
        $existIndex = true;
        $index = 'ios_devices';
        $esClient = $this->getEsClient();
        if (!$esClient->getIndex($this->iosDeviceIndex)->exists()) {
		    $existIndex = false;
		}
        $deviceIOS = [];
		if ($existIndex) {
		    $esSearch = new \Elastica\Search($esClient);
    		$esSearch->addIndex($this->iosDeviceIndex);
            $esSearch->addType($this->iosDeviceIndex);
            $query = new \Elastica\Query();
    		$matchQuery = new \Elastica\Query\Match();
            $matchQuery->setField('id', $this->deviceId);
            $query
                ->setQuery($matchQuery)
                ->setSize(1);
            $esSearch->setQuery($query);
            $resultSet = $esSearch->search();
            try {
            	$result = $resultSet->offsetGet(0);
            	$_deviceIOS = $result->getData();
            	if (!empty($_deviceIOS)) {
            		$deviceIOS['model'] = $_deviceIOS['deviceType'];
            	}
            } catch (\Exception $e) {

            }
		}

        if (empty($deviceIOS)) {
        	$_deviceIOS = $this->deviceIosRepo->find($this->deviceId);
        	if (!empty($_deviceIOS)) {
        		$deviceIOS['model'] = $_deviceIOS->getDeviceType();
        	}
        }

        return $deviceIOS;
    }

    public function getDeviceAndroid()
    {
        $existIndex = true;
        $esClient = $this->getEsClient();
        if (!$esClient->getIndex($this->androidDeviceIndex)->exists()) {
		    $existIndex = false;
		}
        $deviceAndroid = [];
		if ($existIndex) {
		    $esSearch = new \Elastica\Search($esClient);
    		$esSearch->addIndex($this->androidDeviceIndex);
            $esSearch->addType($this->androidDeviceIndex);
            $query = new \Elastica\Query();
    		$matchQuery = new \Elastica\Query\Match();
            $matchQuery->setField('id', $this->deviceId);
            $query
                ->setQuery($matchQuery)
                ->setSize(1);
            $esSearch->setQuery($query);
            $resultSet = $esSearch->search();
            try {
            	$result = $resultSet->offsetGet(0);
            	$_deviceAndroid = $result->getData();
            	if (!empty($_deviceAndroid)) {
            		$deviceAndroid['model'] = $_deviceAndroid['deviceModel'];
            	}
            } catch (\Exception $e) {

            }
		}

        if (empty($deviceAndroid)) {
        	$_deviceAndroid = $this->deviceAndroidRepo->find($this->deviceId);
        	if (!empty($_deviceAndroid)) {
        		$deviceAndroid['model'] = $_deviceAndroid->getDeviceModel();
        	}
        }

        return $deviceAndroid;
    }

    public function getTimeline($pageNumber)
    {
        $existIndex = true;
        $esClient = $this->getEsClient();
		$esSearch = new \Elastica\Search($esClient);
        foreach ($this->listS3Folder as $s3Folder) {
            $esSearch->addIndex($s3Folder);
            if (!$esClient->getIndex($s3Folder)->exists()) {
    		    $existIndex = false;
    		    break;
    		}
        }
        $listAction = [];
        $totalCount = 0;
        if ($existIndex) {
            foreach ($this->listAppId as $appId) {
                $esSearch->addType($appId);
            }
            $query = new \Elastica\Query();
    		$matchQuery = new \Elastica\Query\Match();
            $matchQuery->setField('device_id', $this->deviceId);
            //$matchQuery->setField('device_id', 'cdd9552519c3615655f586a32444efdb');
            $size = self::LIST_ACTION_SIZE;
            $from = ($pageNumber * $size) - $size;
            $query
                ->setQuery($matchQuery)
                ->setFrom($from)
                ->setSize($size)
                ->addSort(
                	[
                		'happened_at' => ['order' => 'desc']
                	]
                );;
            $esSearch->setQuery($query);
            $resultSet = $esSearch->search();

            try {
            	$results = $resultSet->getResults();
            	$totalCount = $resultSet->getTotalHits();
            	if (!empty($results)) {
            		foreach ($results as $key => $result) {
            			$entity = $result->getData();
            			$action = [
            				'app_id' => $entity['app_id']
            				, 'happened_at' => $entity['happened_at']
            				, 'event_name' => $entity['event_name']
            				, 'event_value' => []
            			];
    					if (!empty($entity['event_value_text'])
                        	&& $entity['event_value_text'] != 'NULL'
    	                ) {
    	                    $action['event_value']['event_value'] = $entity['event_value_text'];
    	                } else {
    	                    foreach ($entity as $key => $value) {
    	                        if ((in_array($key, $this->eventValueParams)) && ('' != $value)) {
    	                            $action['event_value'][$key] = $value;
    	                        }
    	                    }
    	                }
    	                $listAction[] = $action;
            		}
            	}
            } catch (\Exception $e) {

            }
        }

        if (empty($totalCount)) {
            $paginateData = $this->actionRepo->getPaginateDataByDevice(
                $this->deviceId,
                $this->listAppId,
                $pageNumber,
                self::LIST_ACTION_SIZE
            );
            if (!empty($paginateData['rows'])) {
            	foreach ($paginateData['rows'] as $key => $value) {
            	    $action = [
        				'app_id' => $value['appId']
        				, 'happened_at' => $value['happenedAt']
        				, 'event_name' => $value['eventName']
        				, 'event_value' => []
        			];
					if (!empty($value['eventValueText'])
                    	&& $value['eventValueText'] != 'NULL'
	                ) {
	                    $action['event_value']['event_value'] = $value['eventValueText'];
	                } else {
	                    foreach ($value as $key => $value) {
	                        $columName = Inflector::tableize($key);
	                        if ((in_array($columName, $this->eventValueParams)) && ('' != $value)) {
	                            $action['event_value'][$columName] = $value;
	                        }
	                    }
	                }
            	    $listAction[] = $action;
            	}
            }
        }
        if (!empty($listAction)) {
        	foreach ($listAction as $key => $value) {
        		$listAction[$key]['event_friendly_name'] = '';
        		$listAction[$key]['icon'] = '';
        		$listAction[$key]['color'] = '';
        		if (!empty($this->listEventIAP[$value['app_id']][$value['event_name']]['event_friendly_name'])) {
        			$listAction[$key]['event_friendly_name'] = $this->listEventIAP[$value['app_id']][$value['event_name']]['event_friendly_name'];
        		}
        		if (!empty($this->listEventIAP[$value['app_id']][$value['event_name']]['icon'])) {
        			$listAction[$key]['icon'] = $this->listEventIAP[$value['app_id']][$value['event_name']]['icon'];
        		}
        		if (!empty($this->listEventIAP[$value['app_id']][$value['event_name']]['color'])) {
        			$listAction[$key]['color'] = $this->listEventIAP[$value['app_id']][$value['event_name']]['color'];
        		}
        	}
        }
        $paginator = $this->container->get('knp_paginator');
        $paginator = $paginator->paginate(
            array(),
            $pageNumber,
            self::LIST_ACTION_SIZE
        );

        $paginator->setItems($listAction);
        $paginator->setTotalItemCount($totalCount);



        return $paginator;
    }

    public function getLastActivity()
    {
        $existIndex = true;
        $esClient = $this->getEsClient();
		$esSearch = new \Elastica\Search($esClient);
        foreach ($this->listS3Folder as $s3Folder) {
            $esSearch->addIndex($s3Folder);
            if (!$esClient->getIndex($s3Folder)->exists()) {
    		    $existIndex = false;
    		    break;
    		}
        }
        $lastActivity = '';
        if ($existIndex) {
            foreach ($this->listAppId as $appId) {
                $esSearch->addType($appId);
            }
            $query = new \Elastica\Query();
    		$matchQuery = new \Elastica\Query\Match();
            $matchQuery->setField('device_id', $this->deviceId);
            //$matchQuery->setField('device_id', 'cdd9552519c3615655f586a32444efdb');
            $query
                ->setQuery($matchQuery)
                ->setSize(1);
            $maxAgg = new \Elastica\Aggregation\Max("max_happened_at");
            $maxAgg->setField("happened_at");
            $query->addAggregation($maxAgg);
            $esSearch->setQuery($query);
            $resultSet = $esSearch->search();
            try {
            	$result = $resultSet->getAggregation("max_happened_at");
            	if (isset($result['value'])) {
            		$lastActivity = $result['value'];
            	}
            } catch (\Exception $e) {

            }
        }


        return $lastActivity;
    }

    public function getLastTransaction()
    {
    	$existIndex = true;
        $esClient = $this->getEsClient();
		$esSearch = new \Elastica\Search($esClient);
        foreach ($this->listS3Folder as $s3Folder) {
            $esSearch->addIndex($s3Folder);
            if (!$esClient->getIndex($s3Folder)->exists()) {
    		    $existIndex = false;
    		    break;
    		}
        }
        $lastTransaction = [];
        if ($existIndex) {
            foreach ($this->listAppId as $appId) {
                $esSearch->addType($appId);
            }
            $query = new \Elastica\Query();
            $matchQuery = new \Elastica\Query\Match();
            $matchQuery->setField('device_id', $this->deviceId);
            //$matchQuery->setField('device_id', 'd206a91ab44fe168ed8c0704c0b65964');
            $existFilter = new \Elastica\Filter\Exists('amount_usd');
            $termFilter = new \Elastica\Filter\Term();
            $termFilter->setTerm('amount_usd', "0");
            $boolFilter = new \Elastica\Filter\Bool();
            $boolFilter->addShould($existFilter);
            $boolFilter->addMustNot($termFilter);
            $constantScoreQuery = new \Elastica\Query\ConstantScore($boolFilter);
            $boolQuery = new \Elastica\Query\Bool();
            $boolQuery
                ->addMust($matchQuery)
                ->addMust($constantScoreQuery);
            $query
                ->setSize(1)
                ->setQuery($boolQuery)
                ->addSort(
                	[
                		'happened_at' => ['order' => 'desc']
                	]
                );
            $esSearch->setQuery($query);
            $resultSet = $esSearch->search();
            try {
            	$result = $resultSet->offsetGet(0)->getData();
            	if (!empty($result)) {
            		$lastTransaction = [
            			'happened_at' => $result['happened_at']
            			, 'amount_usd' => $result['amount_usd']
            		];
            	}
            } catch (\Exception $e) {

            }
        }

        return $lastTransaction;
    }

    public function getTotalMoneySpent()
    {
        $existIndex = true;
        $esClient = $this->getEsClient();
		$esSearch = new \Elastica\Search($esClient);
        foreach ($this->listS3Folder as $s3Folder) {
            $esSearch->addIndex($s3Folder);
            if (!$esClient->getIndex($s3Folder)->exists()) {
    		    $existIndex = false;
    		    break;
    		}
        }
        $result = [
        	'numberOfTransaction' => ''
        	, 'totalPriceOfTransaction' => ''
        ];
        if ($existIndex) {
            foreach ($this->listAppId as $appId) {
                $esSearch->addType($appId);
            }
            $query = new \Elastica\Query();
            $matchQuery = new \Elastica\Query\Match();
            $matchQuery->setField('device_id', $this->deviceId);
            //$matchQuery->setField('device_id', 'd206a91ab44fe168ed8c0704c0b65964');
            $boolFilter = new \Elastica\Filter\Bool();
            $existFilter = new \Elastica\Filter\Exists('amount_usd');
            $existFilter->setField('amount_usd');
            $termFilter = new \Elastica\Filter\Term();
            $termFilter->setTerm('amount_usd', "0");
            $boolFilter->addShould($existFilter);
            $boolFilter->addMustNot($termFilter);
            $constantScoreQuery = new \Elastica\Query\ConstantScore($boolFilter);
            $boolQuery = new \Elastica\Query\Bool();
            $boolQuery
                ->addMust($matchQuery)
                ->addMust($constantScoreQuery);
            $query
                ->setSize(0)
                ->setQuery($boolQuery);
            $sumAgg = new \Elastica\Aggregation\Sum("sum_amount_usd");
            $sumAgg->setField("amount_usd");
            $query->addAggregation($sumAgg);
            $esSearch->setQuery($query);
            $resultSet = $esSearch->search();

            try {
            	$result['numberOfTransaction'] = $resultSet->getTotalHits();
            	$bucket = $resultSet->getAggregation("sum_amount_usd");
            	if (isset($bucket['value'])) {
            		$result['totalPriceOfTransaction'] = $bucket['value'];
            	}
            } catch (\Exception $e) {

            }
        }


        return $result;
    }

    public function getDeviceLatest()
    {
    	return $this->deviceLatest;
    }

    public function getDevice()
    {
    	return $this->device;
    }

    public function getListPresetFilter($authId)
    {
		return $this->devicepresetFilterRepo->findByDeviceId(
            $this->deviceId,
            $authId
        );
    }

    public function getUnAttachedPresetFilter($authId)
    {
		return $this->devicepresetFilterRepo->findNotContainsDeviceId(
            $this->deviceId,
            $authId
        );
    }

    public function deletePresetFilter($presetFilterId)
    {
    	return $this->devicepresetFilterRepo->deleteByDeviceIdPresetFilterId(
            $this->device->getId(),
            $presetFilterId
        );
    }

    public function detachPresetFilter($presetFilterId)
    {
    	return $this->devicepresetFilterRepo->detach(
            $this->deviceId,
            $presetFilterId
        );
    }

    public function addAttachPresetFilter($presetFilterId)
    {
    	return $this->devicepresetFilterRepo->add(
            $this->deviceId,
            $presetFilterId
        );
    }

    public function attachPresetFilter($presetFilterId)
    {
    	return $this->devicepresetFilterRepo->attach(
            $this->deviceId,
            $presetFilterId
        );
    }

    public function validate($value, $type)
    {
        if ($type == self::EMAIL_VALIDATE) {
            $validator = new EmailValidator(
                $this->getEsClient()
                , $this->identityCaptureRepo
                , $this->deviceIndex
                , $value
                , new EmailValidationHandler()
            );
        } else if ($type == self::IDFA_VALIDATE) {
            $validator = new IDFAValidator(
                $this->getEsClient()
                , $this->deviceIosRepo
                , $this->iosDeviceIndex
                , $value
                , new IDFAValidationHandler()
            );
        } else if ($type == self::ANDROID_ID_VALIDATE) {
            $validator = new AndroidIdValidator(
                $this->getEsClient()
                , $this->deviceAndroidRepo
                , $this->androidDeviceIndex
                , $value
                , new AndroidIdValidationHandler()
            );
        } else if ($type == self::DEVICE_VALIDATE) {
            $validator = new DeviceValidator(
                $this->getEsClient()
                , $this->deviceRepo
                , $this->deviceIndex
                , $value
                , new DeviceValidationHandler()
            );
        }
        $deviceId = $validator->validate();

        return $deviceId;
    }

    public static function dummyData()
	{
	    $data = [
	    	[
	    		'timestamp' => strtotime('15-01-2016 00:19:33'),
	    		'event_name' => 'add_to_cart',
	    		'event_value' => [
	    			'af_content_type' => 'Beauty',
	    			'af_content_id' => 'Addidas deodorant Blue Sport',
	    			'af_price' => 10
	    		]
	    	],
	    	[
	    		'timestamp' => strtotime('15-01-2016 09:10:56'),
	    		'event_name' => 'add_to_wishlist',
	    		'event_value' => [
	    			'af_content_type' => 'Shoes',
	    			'af_content_id' => 'YMK Brown sandal',
	    			'af_price' => 12
	    		]
	    	],
	    	[
	    		'timestamp' => strtotime('16-01-2016 00:30:24'),
	    		'event_name' => 'add_to_cart',
	    		'event_value' => [
	    			'af_content_type' => 'Beauty',
	    			'af_content_id' => 'Haircurler',
	    			'af_price' => 81
	    		]
	    	],
	    	[
	    		'timestamp' => strtotime('20-01-2016 19:26:48'),
	    		'event_name' => 'add_to_cart',
	    		'event_value' => [
	    			'af_content_type' => 'Beauty',
	    			'af_content_id' => 'Korean snail mask 12ds81',
	    			'af_price' => 20
	    		]
	    	],
	    	[
	    		'timestamp' => strtotime('25-01-2016 20:36:11'),
	    		'event_name' => 'add_to_cart',
	    		'event_value' => [
	    			'af_content_type' => 'Beauty',
	    			'af_content_id' => 'MAC Lipstick Matte',
	    			'af_price' => 56
	    		]
	    	],
	    	[
	    		'timestamp' => strtotime('02-02-2016 10:59:48'),
	    		'event_name' => 'add_to_wishlist',
	    		'event_value' => [
	    			'af_content_type' => 'Beauty',
	    			'af_content_id' => 'Korean snail mask 12ds81',
	    			'af_price' => 20
	    		]
	    	],
	    	[
	    		'timestamp' => strtotime('08-02-2016 01:09:28'),
	    		'event_name' => 'add_to_wishlist',
	    		'event_value' => [
	    			'af_content_type' => 'Shoes',
	    			'af_content_id' => 'Charles & Keith red heel',
	    			'af_price' => 64
	    		]
	    	],
	    	[
	    		'timestamp' => strtotime('09-02-2016 19:18:29'),
	    		'event_name' => 'purchase',
	    		'event_value' => [
	    			'af_content_type' => 'Beauty',
	    			'af_content_id' => 'Addidas deodorant Blue Sport',
	    			'af_revenue' => 10
	    		]
	    	],
	    	[
	    		'timestamp' => strtotime('15-02-2016 05:51:55'),
	    		'event_name' => 'add_to_cart',
	    		'event_value' => [
	    			'af_content_type' => 'Shoes',
	    			'af_content_id' => 'Sneaker White Adidas',
	    			'af_price' => 120
	    		]
	    	],
	    	[
	    		'timestamp' => strtotime('18-02-2016 14:25:11'),
	    		'event_name' => 'add_to_cart',
	    		'event_value' => [
	    			'af_content_type' => 'Shoes',
	    			'af_content_id' => 'Sneaker White Adidas',
	    			'af_price' => 120
	    		]
	    	],
	    	[
	    		'timestamp' => strtotime('20-02-2016 02:03:45'),
	    		'event_name' => 'add_to_cart',
	    		'event_value' => [
	    			'af_content_type' => 'Shoes',
	    			'af_content_id' => 'YMK Brown sandal',
	    			'af_price' => 12
	    		]
	    	],
	    	[
	    		'timestamp' => strtotime('22-02-2016 15:02:16'),
	    		'event_name' => 'add_to_cart',
	    		'event_value' => [
	    			'af_content_type' => 'Beauty',
	    			'af_content_id' => 'Korean snail mask 12ds81',
	    			'af_price' => 20
	    		]
	    	],
	    	[
	    		'timestamp' => strtotime('24-02-2016 13:26:48'),
	    		'event_name' => 'add_to_cart',
	    		'event_value' => [
	    			'af_content_type' => 'Shoes',
	    			'af_content_id' => 'Sneaker White Adidas',
	    			'af_price' => 120
	    		]
	    	],
	    	[
	    		'timestamp' => strtotime('09-03-2016 05:04:13'),
	    		'event_name' => 'add_to_cart',
	    		'event_value' => [
	    			'af_content_type' => 'Handbags & Accessories',
	    			'af_content_id' => 'Birkin Pink 173S',
	    			'af_price' => 1000
	    		]
	    	],
	    	[
	    		'timestamp' => strtotime('13-03-2016 19:41:46'),
	    		'event_name' => 'add_to_wishlist',
	    		'event_value' => [
	    			'af_content_type' => 'Watches',
	    			'af_content_id' => 'Ferrari 91',
	    			'af_price' => 400
	    		]
	    	],
            [
	    		'timestamp' => strtotime('15-03-2016 20:14:15'),
	    		'event_name' => 'add_to_cart',
	    		'event_value' => [
	    			'af_content_type' => 'Handbags & Accessories',
	    			'af_content_id' => 'Longchamp Brown U23',
	    			'af_price' => 401
	    		]
	    	],
	    	[
	    		'timestamp' => strtotime('17-03-2016 02:48:16'),
	    		'event_name' => 'add_to_wishlist',
	    		'event_value' => [
	    			'af_content_type' => 'Watches',
	    			'af_content_id' => 'Luminox GT234',
	    			'af_price' => 520
	    		]
	    	],
	    	[
	    		'timestamp' => strtotime('23-03-2016 15:26:30'),
	    		'event_name' => 'add_to_wishlist',
	    		'event_value' => [
	    			'af_content_type' => 'Handbags & Accessories',
	    			'af_content_id' => 'Birkin Pink 173S',
	    			'af_price' => 1000
	    		]
	    	],
	    	[
	    		'timestamp' => strtotime('27-03-2016 00:51:35'),
	    		'event_name' => 'add_to_wishlist',
	    		'event_value' => [
	    			'af_content_type' => 'Watches',
	    			'af_content_id' => 'Casio Shock GT',
	    			'af_price' => 150
	    		]
	    	],
	    	[
	    		'timestamp' => strtotime('27-03-2016 15:03:37'),
	    		'event_name' => 'add_to_wishlist',
	    		'event_value' => [
	    			'af_content_type' => 'Watches',
	    			'af_content_id' => 'Luminox GT234',
	    			'af_price' => 520
	    		]
	    	],
	    	[
	    		'timestamp' => strtotime('29-03-2016 14:45:14'),
	    		'event_name' => 'purchase',
	    		'event_value' => [
	    			'af_content_type' => 'Handbags & Accessories',
	    			'af_content_id' => 'Longchamp Brown U23',
	    			'af_revenue' => 401
	    		]
	    	],
	    	[
	    		'timestamp' => strtotime('30-03-2016 00:00:28'),
	    		'event_name' => 'add_to_wishlist',
	    		'event_value' => [
	    			'af_content_type' => 'Handbags & Accessories',
	    			'af_content_id' => 'Longchamp Brown U23',
	    			'af_price' => 401
	    		]
	    	],
	    	[
	    		'timestamp' => strtotime('01-04-2016 19:29:53'),
	    		'event_name' => 'purchase',
	    		'event_value' => [
	    			'af_content_type' => 'Handbags & Accessories',
	    			'af_content_id' => 'Birkin Pink 173S',
	    			'af_revenue' => 1000
	    		]
	    	],
	    	[
	    		'timestamp' => strtotime('04-04-2016 21:17:37'),
	    		'event_name' => 'purchase',
	    		'event_value' => [
	    			'af_content_type' => 'Handbags & Accessories',
	    			'af_content_id' => 'Longchamp Brown U23',
	    			'af_revenue' => 401
	    		]
	    	],
	    	[
	    		'timestamp' => strtotime('10-04-2016 19:30:52'),
	    		'event_name' => 'add_to_wishlist',
	    		'event_value' => [
	    			'af_content_type' => 'Handbags & Accessories',
	    			'af_content_id' => 'Birkin Pink 173S',
	    			'af_price' => 1000
	    		]
	    	],
	    	[
	    		'timestamp' => strtotime('14-04-2016 21:48:53'),
	    		'event_name' => 'add_to_wishlist',
	    		'event_value' => [
	    			'af_content_type' => 'Handbags & Accessories',
	    			'af_content_id' => 'Laviosa Necklace Gems',
	    			'af_price' => 35
	    		]
	    	],
	    	[
	    		'timestamp' => strtotime('15-04-2016 01:59:31'),
	    		'event_name' => 'purchase',
	    		'event_value' => [
	    			'af_content_type' => 'Handbags & Accessories',
	    			'af_content_id' => 'Birkin Pink 173S',
	    			'af_revenue' => 1000
	    		]
	    	],
	    	[
	    		'timestamp' => strtotime('16-04-2016 06:50:50'),
	    		'event_name' => 'add_to_wishlist',
	    		'event_value' => [
	    			'af_content_type' => 'Handbags & Accessories',
	    			'af_content_id' => 'Laviosa Necklace Gems',
	    			'af_price' => 35
	    		]
	    	],
	    	[
	    		'timestamp' => strtotime('16-04-2016 07:29:08'),
	    		'event_name' => 'add_to_cart',
	    		'event_value' => [
	    			'af_content_type' => 'Handbags & Accessories',
	    			'af_content_id' => 'Birkin Pink 173S',
	    			'af_price' => 1000
	    		]
	    	],
	    	[
	    		'timestamp' => strtotime('16-04-2016 14:00:48'),
	    		'event_name' => 'purchase',
	    		'event_value' => [
	    			'af_content_type' => 'Handbags & Accessories',
	    			'af_content_id' => 'Birkin Pink 173S',
	    			'af_revenue' => 1000
	    		]
	    	],
	    ];
        usort($data, function($a, $b) {
	        if($a['timestamp']==$b['timestamp']) return 0;
            return $a['timestamp'] < $b['timestamp']?1:-1;
	    });

	    return $data;
	}
}