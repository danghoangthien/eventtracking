<?php
namespace Hyper\EventBundle\Service\UserjourneyService\Validator;

use Hyper\EventBundle\Service\UserjourneyService\Validator\AndroidIdValidationHandler;
use Hyper\Domain\Device\AndroidDeviceRepository;

class AndroidIdValidator
{
    private $client;
    private $androidRepo;
    private $androidId;
    private $androidDeviceIndex;
    private $validationHandler;

    public function __construct(
        $client
        , AndroidDeviceRepository $androidRepo
        , $androidDeviceIndex
        , $androidId
        , AndroidIdValidationHandler $validationHandler
    ) {
        $this->client = $client;
        $this->androidRepo = $androidRepo;
        $this->androidDeviceIndex = $androidDeviceIndex;
        $this->androidId = $androidId;
        $this->validationHandler = $validationHandler;
    }

    public function validate()
    {
        $esSearch = new \Elastica\Search($this->client);
        if (!$this->client->getIndex($this->androidDeviceIndex)->exists()) {
		    $this->validationHandler->handleAndroidDeviceIndexNotFound();
		}
		$esSearch->addIndex($this->androidDeviceIndex);
        $esSearch->addType($this->androidDeviceIndex);
		$query = new \Elastica\Query();
        $matchQuery = new \Elastica\Query\Match();
        $matchQuery->setField('androidId', $this->androidId);
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
            $androidDevice = $this->androidRepo->findOneBy([
	        	'androidId' => $this->androidId
	        ]);
	        if (empty($androidDevice)) {
	            $this->validationHandler->handleAndroidIdNotFound();
	        } else {
	            $deviceId = $androidDevice->getDevice()->getId();
	        }
        }

        return $deviceId;
    }
}