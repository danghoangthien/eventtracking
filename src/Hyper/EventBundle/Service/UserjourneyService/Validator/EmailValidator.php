<?php
namespace Hyper\EventBundle\Service\UserjourneyService\Validator;

use Hyper\EventBundle\Service\UserjourneyService\Validator\EmailValidationHandler;
use Hyper\Domain\IdentityCapture\IdentityCaptureRepository;

class EmailValidator
{
    private $client;
    private $icRepo;
    private $email;
    private $deviceIndex;
    private $validationHandler;

    public function __construct(
        $client
        , IdentityCaptureRepository $icRepo
        , $deviceIndex
        , $email
        , EmailValidationHandler $validationHandler
    ) {
        $this->client = $client;
        $this->icRepo = $icRepo;
        $this->deviceIndex = $deviceIndex;
        $this->email = $email;
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
        $matchQuery->setField('email', $this->email);
        $query
            ->setSize(1)
            ->setQuery($matchQuery);
        $esSearch->setQuery($query);
        $resultSet = $esSearch->search();
        $email = '';
        $deviceId = '';
        try {
        	$result = $resultSet->offsetGet(0)->getData();
        	if (!empty($result)) {
        		$email = $result['email'];
        	}
        } catch (\Exception $e) {

        }
        if (empty($email)) {
            $identityCapture = $this->icRepo->findOneBy([
	        	'email' => $this->email
	        ]);
	        if (empty($identityCapture)) {
	            $this->validationHandler->handleEmailNotFound();
	        } else {
	            $email = $identityCapture->getEmail();
	        }
        }
        if (!empty($email)) {
            $deviceId = $this->icRepo->getDeviceLatestByEmail($email);
        }

        return $deviceId;
    }
}