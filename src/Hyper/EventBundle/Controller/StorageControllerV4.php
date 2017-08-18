<?php
namespace Hyper\EventBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\HttpFoundation\File\File;
use Hyper\EventProcessingBundle\Validator\ContainsMessage;
use Hyper\EventBundle\Service\Cached\App\AppCached;

use Hyper\Domain\Device\Device;
use Hyper\Domain\Action\Action;

class StorageControllerV4 extends Controller
{
    const OTHER_FOLDER = 'others';
    public $postBackProvider = null;
    protected $clientName = null;
    protected $appId = null;
    protected $isOtherFolder = true;
    /**
    * @param ContainerInterface $container
    */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function indexAction(Request $request)
    {
        //return new Response('This is postback version 2<hr/>');
        $providerId = $request->get('provider');

        // 2015-08-10 - Ding Dong : to capture the value of app_name (app_id) and event_type
        $appId = $request->get('app_name');
        $eventType = $request->get('event_type');
        $realTime = $request->get('realtime');
        // 2015-08-20 - Ding Dong : Added to capture the hostname of the server; to be used for the status updates
        $host_name = $request->getHttpHost();

        $supportProvider = $this->getPostBackProviders();
        if(!empty($providerId) && array_key_exists($providerId,$supportProvider)) {
            $this->postBackProvider = strtolower($supportProvider[$providerId]);
        }
        $content = array();
        $isPostWithJsonBody = $this->isPostWithJsonBody($request);
        if ($isPostWithJsonBody) {
            $content = $this->getValidContent($request);
            if(!empty($content)){
                $filePath = $this->storeEventS3FromAPI($request);
                //$this->storeEventMemCached($content);
                if(!empty($filePath)){
                    $resp = array(
                        'file_path' => $filePath
                    );
                    if ($this->isOtherFolder) {
                        return new Response(
                            json_encode($resp)
                        );
                    }
                    // https://hyperdev.atlassian.net/browse/BOB-191
                    $tmpContent = $content;
                    $tmpContent['extra_data'] = array(
                        's3_log_file' => $filePath,
                        'provider_id' => $providerId,
                        'provider_name' => $this->postBackProvider,
                        'client_name' => $this->clientName,
                        'app_id' => $this->appId,
                        'validate' => 1
                    );
                    $validator = $this->get('validator');
                    $containsMessage = new ContainsMessage();
                    $violations = $validator->validate($tmpContent, $containsMessage);
                    if (count($violations) > 0) {
                        foreach ($violations as $violation) {
                            $error = $violation->getMessage();
                            $resp['errors'][] = $error;
                        }
                        $eventType = isset($tmpContent['event_type']) ?
                            $tmpContent['event_type'] : '';
                        $eventName = isset($tmpContent['event_name']) ?
                            $tmpContent['event_name'] : '';
                        $this->container
                            ->get('hyper_event_processing.logger_wrapper')->logInvalidContent(
                                $resp['errors'],
                                $this->container->getParameter('amazon_s3_bucket_pre_event_handling'),
                                'invalid-data',
                                $this->clientName,
                                $this->appId,
                                $eventType,
                                $eventName,
                                $content,
                                $filePath
                            );
                    } else {
                        $this->sendRequestToSqs($tmpContent);
                    }
                    if ($realTime) {
                        $em=$this->container->get('doctrine')->getManager('pgsql');
                        $em->getConnection()->beginTransaction(); // suspend auto-commit
                        $metaData = array();
                        $metaData['s3_log_file'] = $filePath;
                        $redshift = $this->get('redshift_service');
                        $redshift->storeLogEventToRedshift($providerId,$content,$metaData);
                        $em->getConnection()->commit();
                    }
                    return new Response(
                        json_encode($resp)
                    );
                }
            }
        } else {
            //improve with Rest API standard later
            return new Response(
                json_encode(
                    array(
                        'error'=>'bad request',
                        'code'=>'400'
                    )
                )
            );
        }

    }

    protected function isPostWithJsonBody(Request $request)
    {
        $contentType = $request->headers->get('Content-Type');
        $method = $request->getMethod();
        //$logger = $this->get('logger');
        //$logger->info('result contentType'.$contentType);
        //$logger->info('result method'.$method);
        return ($contentType == 'application/json' && $method == 'POST');
    }


    protected function isPurchaseEvent($content)
    {
        return (!empty($content['event_name']) && strpos($content['event_name'],'purchase')!==false);
    }

    protected function getValidContent(Request $request,$returnType ='array')
    {
        $rawJsonContent = $request->getContent();
        if ($returnType == 'json') {
            return $rawJsonContent;
        } else {
            $content = json_decode($rawJsonContent,true);
            //$logger = $this->get('logger');
            //$logger->info('result content '.$content);
            //$logger->info('result rawJsonContent '.$rawJsonContent);
            if(is_array($content) && !empty($content)){
                //$logger->info('result valid content ');
                return $content;
            }else{
                return null;
            }
        }

    }

    /**
     * @return Hyper\EventBundle\Upload\EventLogUploader
     */
    protected function getEventLogUploader()
    {
        return $this->get('hyper_event.event_log_uploader');
    }

    protected function storeEventS3FromAPI(Request $request){
        $amazonBaseURL = $this->container->getParameter('hyper_event.amazon_s3.base_url');
        $rootDir = $this->get('kernel')->getRootDir();// '/var/www/html/projects/event_tracking/app'
        $rawLogDir = '/var/www/html/projects/event_tracking/web/raw_event';

        $fs = new Filesystem();
        $rawContent = $this->getValidContent($request,'json');
        $content = $this->getValidContent($request,'array');
        $appId=$content['app_id'];
        $eventType = $content['event_type'];
        $s3FolderMappping = $this->getS3FolderMapping();

        $result = $this->storeEventS3(
            $rawContent,
            $content,
            $amazonBaseURL,
            $rawLogDir,
            $s3FolderMappping
        );
        return $result;

    }

    public function storeEventS3(
        $rawContent,
        $content,
        $amazonBaseURL,
        $rawLogDir,
        $s3FolderMappping
    ) {
        $year  = date('Y');
        $month = date('m');
        $day   = date('d');
        $hour  = date('H');
        $minute= date('i');

        $fs = new Filesystem();
        $appId=$content['app_id'];
        $eventType = $content['event_type'];
        $s3BucketFolder = '';
        $appIdMapping = self::OTHER_FOLDER;
        if(array_key_exists($appId,$s3FolderMappping) ) {
            $appIdMapping = $s3FolderMappping[$appId];
            $this->clientName = $appIdMapping;
            $this->appId = $appId;
            $this->isOtherFolder = false;
        }
        $s3BucketFolder = $appIdMapping."/". $year ."/". $month ."/". $day ."/". $hour ."/". $minute;
        $eventTime = $content['event_time'];
        $eventTimeStamp = strtotime($eventTime);
        $postBackProvider = ($this->postBackProvider!== null)?$this->postBackProvider:'';
        $uniqueId = uniqid();
        $path = $rawLogDir.'/'.$postBackProvider.'_'.$appId.'_'.$eventType.'_'.$eventTimeStamp.'_'.$uniqueId;
        $pathJson = $path.'.json';
        $fs->dumpFile($pathJson,$rawContent);

        $file = new File($pathJson);


        $filePathName = $file->getPathname();
        chmod($filePathName, 0777);
        $logger = $this->get('logger');
        $logger->info('file exist? '.$filePathName.':'.file_exists($filePathName));

        $eventUploader = $this->getEventLogUploader();
        $region = $this->container->getParameter('amazon_s3_region');
        $bucket = $this->container->getParameter('amazon_s3_bucket_name');
        $securityKey = $this->container->getParameter('amazon_aws_key');
        $securitySecret = $this->container->getParameter('amazon_aws_secret_key');
        //userDefined metadata
        /*
        $metaData = array(
            'x-amz-meta-event_type' => $content['event_type'],
            'x-amz-meta-event_name' => $content['event_name']
        );
        */
        $metaData = array();
        foreach ($content as $key=>$value) {
            /*
            if ($value === null) {
                $value = '';
            } elseif( is_bool($value)) {
                $value = ($value)?"1":"0";
            } elseif (is_array($value)) {
                $value = json_encode($value);
            }
            */
            //
            $keyArray = array (
                'event_name',
                'event_type'
                //'platform',
                //'event_time',
                //'advertising_id',
                //'android_id',
                //'idfa',
                //'idfv',
                //'app_id',
                //'country_code'
            );
            if(in_array($key,$keyArray)){
                if ($value === null) {
                $value = '';
                }
                $metaData['x-amz-meta-'.$key] = (string)$value;
            } else {
                continue;
            }

        }
        //$fileName = $eventUploader->uploadFromLocalV2($gzFile,$s3BucketFolder);
        $fileName = $eventUploader->uploadFromLocalV3($file,$s3BucketFolder,$region,$bucket,$securityKey,$securitySecret,$metaData);
        if (!empty($fileName)) {
            $filePath = $amazonBaseURL.'/'.$fileName;

            //echo $fileName."<hr/>";
            $fs->remove($pathJson);

            // 2015-08-06  - Ding Dong: Commented line below so it won't be included in the result of CsvImportCommand::parseCsvContent()
            //echo $filePath;

            //return $filePath;
            return $fileName;

        } else {
            return null;
        }

    }

    public function S3FolderMapping(){
        $appCached = new AppCached($this->container);

        return $appCached->hgetall();
    }

    public function getS3FolderMapping() {
        return $this->S3FolderMapping();
    }

    // 2015-08-10 - Ding Dong : Removed CSV and added hasoffer
    public function postBackProviders() {
        // return array(
        //     '1' => 'appsflyer',
        //     '2' => 'hasoffer',
        //     '3' => 'hypergrowth',
        //     '4' => 'trackingkit'
        // );
        return array_flip(Action::PROVIDERS);
    }

    public function getPostBackProviders(){
        return $this->postBackProviders();
    }

    public function getAmazonBaseURL(){
        return $this->container->getParameter('hyper_event.amazon_s3.base_url');
    }

    public function sendRequestToSqs($content)
    {
        return $this->getSqsWrapper()
                ->sendMessageToQueue(
                    $this->container->getParameter('amazon_sqs_queue_pre_event_handling'),
                    $content
        );
    }

    public function getSqsWrapper()
    {
        return $this->container->get('hyper_event_processing.sqs_wrapper');
    }
}
