<?php
namespace Hyper\EventBundle\Service\UserjourneyService\Validator;

use Hyper\EventBundle\Service\UserjourneyService\Validator\IDFAValidationHandler;
use Hyper\Domain\Device\IOSDeviceRepository;

class IDFAValidator
{
    private $client;
    private $iosRepo;
    private $idfa;
    private $iosDeviceIndex;
    private $validationHandler;

    public function __construct(
        $client
        , IOSDeviceRepository $iosRepo
        , $iosDeviceIndex
        , $idfa
        , IDFAValidationHandler $validationHandler
    ) {
        $this->client = $client;
        $this->iosRepo = $iosRepo;
        $this->iosDeviceIndex = $iosDeviceIndex;
        $this->idfa = $idfa;
        $this->validationHandler = $validationHandler;
    }

    public function validate()
    {
        $esSearch = new \Elastica\Search($this->client);
        if (!$this->client->getIndex($this->iosDeviceIndex)->exists()) {
		    $this->validationHandler->handleIosDeviceIndexNotFound();
		}
		$esSearch->addIndex($this->iosDeviceIndex);
        $esSearch->addType($this->iosDeviceIndex);
		$query = new \Elastica\Query();
        $matchQuery = new \Elastica\Query\Match();
        $matchQuery->setField('idfa', $this->idfa);
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
            $iosDevice = $this->iosRepo->findOneBy([
	        	'idfa' => $this->idfa
	        ]);
	        if (empty($iosDevice)) {
	            $this->validationHandler->handleIDFANotFound();
	        } else {
	            $deviceId = $iosDevice->getDevice()->getId();
	        }
        }

        return $deviceId;
    }
}