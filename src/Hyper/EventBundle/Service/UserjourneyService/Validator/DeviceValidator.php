<?php
namespace Hyper\EventBundle\Service\UserjourneyService\Validator;

use Hyper\EventBundle\Service\UserjourneyService\Validator\DeviceValidationHandler;
use Hyper\Domain\Device\DeviceRepository;

class DeviceValidator
{
    private $client;
    private $deviceRepo;
    private $deviceId;
    private $deviceIndex;
    private $validationHandler;

    public function __construct(
        $client
        , DeviceRepository $deviceRepo
        , $deviceIndex
        , $deviceId
        , DeviceValidationHandler $validationHandler
    ) {
        $this->client = $client;
        $this->deviceRepo = $deviceRepo;
        $this->deviceIndex = $deviceIndex;
        $this->deviceId = $deviceId;
        $this->validationHandler = $validationHandler;
    }

    public function validate()
    {
        $esSearch = new \Elastica\Search($this->client);
        if (!$this->client->getIndex($this->deviceIndex)->exists()) {
		    $this->validationHandler->handleDeviceIndexNotFound();
		}
		$esSearch->addIndex($this->deviceIndex);
        $esSearch->addType($this->deviceIndex);
		$query = new \Elastica\Query();
        $matchQuery = new \Elastica\Query\Match();
        $matchQuery->setField('id', $this->deviceId);
        $query
            ->setSize(1)
            ->setQuery($matchQuery);
        $esSearch->setQuery($query);
        $resultSet = $esSearch->search();
        $deviceId = '';
        try {
        	$result = $resultSet->offsetGet(0)->getData();
        	if (!empty($result)) {
        		$deviceId = $result['id'];
        	}
        } catch (\Exception $e) {

        }
        if (empty($deviceId)) {
            $device = $this->deviceRepo->findOneBy([
	        	'id' => $this->deviceId
	        ]);
	        if (empty($device)) {
	            $this->validationHandler->handleDeviceNotFound();
	        } else {
	            $deviceId = $device->getId();
	        }
        }

        return $deviceId;
    }
}