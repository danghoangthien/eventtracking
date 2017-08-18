<?php
namespace Hyper\EventBundle\Service\PushEmailToMCService\CommandHandler;

use Hyper\EventBundle\Service\PushEmailToMCService\Command\PushEmailToMCCommand
    , GuzzleHttp\Client;

final class PushEmailToMCCommandHandler
{
    private $clientId;
    private $clientSecrect;
    private $redirectUri;
    private $s3Client;
    private $userFilterCached;
    private $mcMetadata;

    public function __construct(
        $clientId
        , $clientSecrect
        , $redirectUri
        , $s3Client
        , $userFilterCached
        , $mcMetadata
    ) {
        $this->clientId = $clientId;
        $this->clientSecrect = $clientSecrect;
        $this->redirectUri = $redirectUri;
        $this->s3Client = $s3Client;
        $this->userFilterCached = $userFilterCached;
        $this->mcMetadata = $mcMetadata;
    }

    public function execute(PushEmailToMCCommand $pushEmailToMCCommand)
    {
        $presetFilter = $this->userFilterCached->hget($pushEmailToMCCommand->filterId());
        if (
            !$this->userFilterCached->exists() || !$presetFilter
        ) {
            throw new \Exception('The filter not found.');
        }
        $presetFilter = json_decode($presetFilter, true);
        if (empty($presetFilter['email_csv_path'])) {
            throw new \Exception('List email csv not found.');
        }
        $rows = [];
        $batch = 500;
        $i = 0;
        $batchOperations = [];
        $subscriberListId = $pushEmailToMCCommand->subscriberListId();
        $emailCsvPath = $presetFilter['email_csv_path'];
        // Register the stream wrapper from a client object
        $this->s3Client->registerStreamWrapper();
        if (($fp = fopen($emailCsvPath, "r")) !== false) {
            while (($row = fgetcsv($fp)) !== false) {
                if (!empty($row[0])) {
                     $rows[] = $row[0];
                }
            }
            if (!empty($rows)) {
                $rows = array_unique($rows);
            }
            $countRow = count($rows);
            foreach ($rows as $record) {
                $subscriber = [
                    'email_address' => $record
                    , 'status_if_new' => 'subscribed'
                ];
                $operation = [
                    'method' => 'PUT',
                    'path' => 'lists/'.$subscriberListId.'/members/'.md5(strtolower($record)),
                    'body' => json_encode($subscriber)
                ];
                $batchOperations[] = $operation;
                $i++;
                if (
                    (($i % $batch) == 0 && $i != 1)
                    || ($i == $countRow)
                ) {
                    $this->gzClient = new Client([
                        'base_uri' => $this->mcMetadata['api_endpoint']
                    ]);
                    $json = json_encode(['operations' => $batchOperations]);
                    $request = $this->gzClient->request('POST', 'batches', [
                            'headers' => [
                                'Authorization' => 'OAuth ' . $this->mcMetadata['access_token']
                                , 'Content-Type' => 'application/json'
                            ]
                            , 'body' => $json
                        ]
                    );
                    $response = $request->getBody()->getContents();
                    $batchOperations = [];
                }
            }
        }
    }
}