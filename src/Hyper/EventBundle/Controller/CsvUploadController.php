<?php
namespace Hyper\EventBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Intl\Intl;
use Hyper\EventBundle\Document\Person;
use Hyper\EventBundle\Document\Transaction;
use Hyper\EventBundle\Annotations\CsvMetaReader;
use Hyper\EventBundle\Service\EventProcess;

use Hyper\Domain\Device\Device;

class CsvUploadController extends Controller
{
    private $attribution_types = null;
    private $application_names = null;
    private $application_ids = null;
    private $postback_providers = null;
    private $event_types = null;

    /**
    * @param ContainerInterface $container
    */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        $this->setAttributionTypes();
        $this->setApplicationNames();
        $this->setApplicationIDs();
        $this->setPostbackProviders();
        $this->setEventTypes();
    }
    
    public function indexAction(Request $request)
    {
        /* ADDED TO REDIRECT TO LOGIN IF THERE IS NO SESSION OR LOGGED USER IS CLIENT paul.francisco 2015-12-18 */
        $authRepo  = $this->container->get('auth.controller');
        $authIdFromSession = $authRepo->getLoggedAuthenticationId();
        $user_type = $authRepo->getLoggedUserType();
        
        /* 0 = client access, 2 = client access + clover access */
        if($authIdFromSession == null || $user_type == 0 || $user_type == 2)
        {
            $this->url = $this->generateUrl('dashboard_logout');
            return $this->redirect($this->url, 301);
        }
        
        $is_param_ok = 0;
        $error = "";
        $upload_id = $request->query->get('upload_id');
        $host_name = $request->getHttpHost();
        
        if (null == $upload_id) {
            // If Upload ID is empty initialize page for file uploading or start processing submitted paramters
            
            // Get Application ID
            $appId = $request->get('app_id');

            if (null == $appId) {
                // Application ID is empty set to initialize page for file uploading
                $is_param_ok = 0;
            } else {
                // Get other paramaters
                $appFolder       = $this->getApplicationFolderValue($appId);
                $appPlatform     = $this->getApplicationPlatformValue($appId);
                $appId           = $this->getApplicationIDValue($appId);
                $csv             = $request->files->get('csv');
                $appName         = $this->getApplicationNameValue($request->get('app_name'));
                $providerId      = $this->getPostBackProvidersValue($request->get('provider'));
                $eventType       = $this->getEventTypeValue($request->get('event_type'));
                $attributionType = $this->getAttributionTypeValue($request->get('attribution_type'));
    
                if ((null == $csv) || ("" == trim($csv))) {
                    // Filename is empty set to initialize page for file uploading with error
                    $is_param_ok = 0;
                    $error = "No file specified";
                } else {
                    // Get absolute path of uploaded file
                    $csvRealPath = $csv->getRealPath();
                    $is_param_ok = 1;
                }
            }

            if (0 == $is_param_ok) {
                // Render default page with error message
                
                return $this->render('csv_upload.html.twig',
                    array(
                        'error'            => $error,
                        'app_name'         => $this->getApplicationNameLabelList(),
                        //'app_name'         => $all_apps,
                        'attribution_type' => $this->getAttributionTypeLabelList(),
                        'app_id'           => $this->getApplicationFolderLabelList(),
                        'provider'         => $this->getPostBackProvidersLabelList(),
                        'event_type'       => $this->getEventTypeLabelList(),
                        'active'           => 'csv'
                    )
                );
            }

            // Assign unique ID for upload process
            $upload_id = uniqid('', true);

            // Set default values for status/process summary file
            $progress_summary['total_records']          = 0;
            $progress_summary['total_processed']        = 0;
            $progress_summary['total_success']          = 0;
            $progress_summary['total_failed']           = 0;
            $progress_summary['process_start_datetime'] = " --- ";
            $progress_summary['process_end_datetime']   = " --- ";
            $progress_summary['timeDiff']               = " --- ";
            $progress_summary['host_name']              = $host_name;
            $progress_summary['upload_id']              = $upload_id;
            $progress_summary['status']                 = 1;
            $progress_summary['status_msg']             = "Process to upload to S3 started. Please wait.";

            // Copy uploaded file to retain data after web transaction is complete. Copy will be processed by
            // CSVImport command
            $proc_file = new Filesystem();
            $proc_file->copy($csvRealPath, $csvRealPath.".tmp");
            $csvRealPath = $csvRealPath.".tmp";

            //return new Response(json_encode(array('path' => $csvRealPath, 'app_id' => $appId, 'app_name' => $appName, 'event_type' => $eventType, 'provider_id' => $providerId, 'upload_id' => $upload_id, 'attribution_type' => $attributionType, 'app_folder' => $appFolder, 'platform' => $appPlatform)));
            $cmd ="php /var/www/html/projects/event_tracking/app/console csv:import --file='".$csvRealPath."' --child_id='-1' --app_id='".$appId."' --app_name='".$appName."' --event_type='".$eventType."' --provider_id='".$providerId."' --upload_id='".$upload_id."' --attribution_type='".$attributionType."' --app_folder='".$appFolder."' --platform='".$appPlatform."'> /dev/null 2>/dev/null &";
            //echo $cmd;die;
            // Call CSVImport command via exec(). This forks the process of preparing and uploading data from CSV file
            exec("php /var/www/html/projects/event_tracking/app/console csv:import --file='".$csvRealPath."' --child_id='-1' --app_id='".$appId."' --app_name='".$appName."' --event_type='".$eventType."' --provider_id='".$providerId."' --upload_id='".$upload_id."' --attribution_type='".$attributionType."' --app_folder='".$appFolder."' --platform='".$appPlatform."'> /dev/null 2>/dev/null &");

            // Render status/progress summary files using default values
            return $this->render('csv_upload_progress.html.twig', $progress_summary);

        } else {
            // If Upload ID is not empty, prepare status/progress summary page. Page refreshes every 5 minutes.

            // Determine status/progress summary file based on the Upload ID
            $progress_summary_log = "/tmp/upload_".$upload_id."_log.json";

            if (file_exists($progress_summary_log)) {
                // Open file if it exists
                if (($handle = fopen($progress_summary_log, "r")) !== false) {
                    while(($line = fgets($handle)) !== false) {
                        $progress_summary = json_decode($line, 1);

                        $progress_summary['host_name'] = $host_name;

                        $progress_summary['process_start_datetime'] = date("Y-m-d H:i:s", $progress_summary['process_start_datetime']);
                        if (" --- " != $progress_summary['process_end_datetime']) {
                            $progress_summary['process_end_datetime'] = date("Y-m-d H:i:s", $progress_summary['process_end_datetime']);
                        }
                    }
                    fclose($handle);
                }
            } else {
                // Set default values for status/progress summary page with error if file does not exist. Assumed the
                // upload process terminated abruptly
                $progress_summary['total_records']          = 0;
                $progress_summary['total_processed']        = 0;
                $progress_summary['total_success']          = 0;
                $progress_summary['total_failed']           = 0;
                $progress_summary['process_start_datetime'] = " --- ";
                $progress_summary['process_end_datetime']   = " --- ";
                $progress_summary['timeDiff']               = " --- ";
                $progress_summary['host_name']              = $host_name;
                $progress_summary['upload_id']              = $upload_id;
                $progress_summary['status']                 = 0;
                $progress_summary['status_msg']             = "Upload log file doesn't exist. Upload data might have expired.";
            }

            // Render status/progress summary file
            return $this->render('csv_upload_progress.html.twig', $progress_summary);
        }
    }

    /**
     * @return Hyper\EventBundle\Upload\EventLogUploader
     */
    protected function getEventLogUploader()
    {
        return $this->get('hyper_event.event_log_uploader');
    }

    // Function that will upload JSON file to S3 bucket folder
    public function storeEventS3(
        $rawContent,
        $content,
        $amazonBaseURL,
        $rawLogDir,
        $app_folder,
        $postBackProvider
    ) {

        $fs = new Filesystem();
        
        $year  = date('Y');
        $month = date('m');
        $day   = date('d');
        $hour  = date('H');
        $minute= date('i');

        $appId          = $content['app_id'];
        
        $eventType      = $content['event_type'];
        //append time based path into app_folder 
        $app_folder = $app_folder."/". $year ."/". $month ."/". $day ."/". $hour ."/". $minute;
        $eventTimeStamp = strtotime($content['event_time']);
        
        $path = $rawLogDir.'/'.$postBackProvider.'_'.$appId.'_'.$eventType.'_'.$eventTimeStamp.'_'.uniqid();
        //$path = $rawLogDir.'/'.$year.'/'.$month.'/'.$day.'/'.$hour.'/'.$minute.'/'.$postBackProvider.'_'.$appId.'_'.$eventType.'_'.$eventTimeStamp.'_'.uniqid();
        $pathJson = $path.'.json';
        $pathGz = $path.'.gz';
        $fs->dumpFile($pathJson,$rawContent);

        $file = new File($pathJson);

        $filePathName = $file->getPathname();
        $gzFilePathName = $pathGz;
        file_put_contents($gzFilePathName, gzencode( file_get_contents($filePathName),9));
        chmod($gzFilePathName, 0777);
        $logger = $this->get('logger');
        $logger->info('file exist? '.$gzFilePathName.':'.file_exists($gzFilePathName));
        $gzFile = new File($gzFilePathName);

        $eventUploader = $this->getEventLogUploader();
        $region = $this->container->getParameter('amazon_s3_region');
        $bucket = $this->container->getParameter('amazon_s3_bucket_name');
        $securityKey = $this->container->getParameter('amazon_aws_key');
        $securitySecret = $this->container->getParameter('amazon_aws_secret_key');
        //userDefined metadata
        $metaData = array(
            'x-amz-meta-event_type' => $content['event_type'],
            'x-amz-meta-event_name' => $content['event_name']
        );
        //$fileName = $eventUploader->uploadFromLocalV2($gzFile,$app_folder);
        $logger = $this->get('logger');
        $logger->info('$app_folder '.$app_folder);
        $logger->info('$gzFile '.$gzFile);
        $fileName = $eventUploader->uploadFromLocalV3($gzFile,$app_folder,$region,$bucket,$securityKey,$securitySecret);
        if (!empty($fileName)) {
            $filePath = $amazonBaseURL.'/'.$fileName;

            //echo $fileName."<hr/>";
            $fs->remove($pathGz);
            $fs->remove($pathJson);

            //return $filePath;
            return $fileName;

        } else {
            return null;
        }

    }

    public function getAmazonBaseURL(){
        return $this->container->getParameter('hyper_event.amazon_s3.base_url');
    }

    // Function used to map CSV data with JSON Schema for Appsflyer
    public function mapToSchemaAppsflyer($csv_row) {
        $content['device_model']                    = $csv_row['device_model'];
        $content['fb_adgroup_id']                   = null;
        $content['operator']                        = $csv_row['operator'];
        $content['click_time']                      = $csv_row['click time'];
        $content['agency']                          = $csv_row['agency/pmd (af_prt)'];
        $content['ip']                              = $csv_row['ip'];
        $content['cost_per_install']                = $csv_row['cost per install (af_cpi)'];
        $content['fb_campaign_id']                  = null;
        $content['imei']                            = $csv_row['imei'];
        $content['is_retargeting']                  = false;
        $content['app_name']                        = $csv_row['app_name'];
        $content['re_targeting_conversion_type']    = null;
        $content['android_id']                      = $csv_row['android id'];
        $content['city']                            = $csv_row['city'];
        $content['af_sub1']                         = $csv_row['sub param 1 (af_sub1)'];
        $content['af_sub2']                         = $csv_row['sub param 2 (af_sub2)'];
        $content['event_value']                     = $csv_row['event value'];
        $content['af_sub3']                         = $csv_row['sub param 3 (af_sub3)'];
        $content['fb_adset_name']                   = null;
        $content['af_sub4']                         = $csv_row['sub param 4 (af_sub4)'];
        $content['customer_user_id']                = $csv_row['customer user id'];
        $content['mac']                             = $csv_row['mac'];
        $content['af_sub5']                         = $csv_row['sub param 5 (af_sub5)'];
        $content['campaign']                        = $csv_row['campaign (c)'];
        $content['event_name']                      = $csv_row['event name'];
        $content['currency']                        = $csv_row['currency'];
        $content['install_time']                    = $csv_row['install time'];
        $content['fb_adgroup_name']                 = null;
        $content['event_time']                      = $csv_row['event time'];
        $content['platform']                        = $csv_row['platform'];
        $content['sdk_version']                     = $csv_row['sdk version'];
        $content['appsflyer_device_id']             = $csv_row['appsflyer device id'];
        $content['wifi']                            = $csv_row['wifi'];
        $content['advertising_id']                  = $csv_row['advertising id'];
        $content['media_source']                    = $csv_row['media source (pid)'];
        $content['country_code']                    = $csv_row['country code'];
        $content['http_referrer']                   = null;
        $content['fb_campaign_name']                = null;
        $content['click_url']                       = $csv_row['click url'];
        $content['carrier']                         = $csv_row['carrier'];
        $content['language']                        = $csv_row['language'];
        $content['app_id']                          = $csv_row['app_id'];
        $content['app_version']                     = $csv_row['app version'];
        $content['attribution_type']                = $csv_row['attribution_type'];
        $content['af_siteid']                       = $csv_row['site id (af_siteid)'];
        $content['os_version']                      = $csv_row['os version'];
        $content['fb_adset_id']                     = null;
        $content['device_brand']                    = $csv_row['device_brand'];
        $content['event_type']                      = $csv_row['event_type'];

        return $content;
    }
    
    /* function for HasOffer
    * Purpose : convert csv row from Hasoofer provider to standardized array that could be stored on Redshift DB
    * 2015-10-21 Paul Francisco
    */
    public function mapToSchemaHasOffer($csv_row) {
        
        $content = array();
        
        $content['app_id']                          = $csv_row['app_id'];
        $content['app_name']                        = $csv_row['app_name'];
        $content['platform']                        = $csv_row['platform'];
        $content['event_type']                      = $csv_row['event_type'];
        $content['attribution_type']                = $csv_row['attribution_type'];
        
        /* event_type based condition */
        if($csv_row['event_type'] == 'install'){
             $content['event_name'] = null;
             $content['event_value'] = null;
             $content['currency'] = null;
             $content['install_time'] = $csv_row['created'];
             $content['event_time'] = $csv_row['created'];
        }
        if ($csv_row['event_type'] == 'in-app-event'){
            //in-app-event : currently only support for purchase
            $content['event_name'] = 'purchase';
            $content['event_value'] = $csv_row['revenue_usd'];
            $content['currency'] = $csv_row['currency_code'];
            $content['install_time'] = $csv_row['install_created'];
            $content['event_time'] = $csv_row['created'];
        }
        /* end event_type based condition */
        
        /* platform base condition*/
        //echo $csv_row['platform'];
        if($csv_row['platform'] == 'android') {
            $content['advertising_id'] = $csv_row['google_aid'];
            $content['android_id'] = $csv_row['os_id'];
            $content['device_brand'] = $csv_row['device_brand'];
            $content['device_model'] = $csv_row['device_model'];
        }
        
        if($csv_row['platform'] == 'ios') {
            $content['idfv'] = $csv_row['ios_ifv'];
            $content['idfa'] = $csv_row['ios_ifa'];
            $content['device_type'] = $csv_row['device_model'];
            $content['device_name'] = "";
        }
        /* end platform base condition */
        $countries = Intl::getRegionBundle()->getCountryNames('en');
        $code = array_search($csv_row['country.name'],$countries);
        if(isset($code)){
            $content['country_code']                    = $code;
        } else {
            $content['country_code']                    = 'ID';
        }
        
        $content['language']                        = $csv_row['language'];
        $content['app_version']                     = $csv_row['app_version'];
        $content['os_version']                      = $csv_row['os_version'];
        $content['ip']                              = $csv_row['device_ip'];
        $content['operator']                        = $csv_row['device_carrier'];
        $content['carrier']                         = $csv_row['device_carrier'];
        $content['imei']                            = '';
        $content['media_source']                    = '';
        $content['campaign']                        = '';
        $content['fb_campaign_name']                = '';
        $content['fb_campaign_id']                  = '';
        $content['fb_adgroup_name']                 = '';
        $content['fb_adgroup_id']                   = '';
        $content['fb_adset_name']                   = '';
        $content['fb_adset_id']                     = '';
        $content['af_siteid']                       = '';
        $content['click_url']                       = '';
        $content['http_referrer']                   = '';
        $content['af_sub1']                         = '';
        $content['af_sub2']                         = '';
        $content['af_sub3']                         = '';
        $content['af_sub4']                         = '';
        $content['af_sub5']                         = '';
        $content['customer_user_id']                = '';
        $content['appsflyer_device_id']             = '';
        $content['is_retargeting']                  = '';
        $content['re_targeting_conversion_type']    = '';
        $content['cost_per_install']                = '';
        $content['agency']                          = '';
        $content['sdk_version']                     = '';
        $content['click_time']                      = '';
        $content['city']                            = '';
        $content['mac']                             = '';
        $content['wifi']                            = '';
        echo "ready \n";
        //var_dump($event_id);
        
        // return new Response(json_encode(array("records" => \Doctrine\Common\Util\Debug::dump($content))));
        return $content;
    }

    // Function to split CSV field 'Device Type' in Appsflyer JSON fields 'device_brand' and 'device_model'
    public function getDeviceInfo($device) {
        $device_parts = explode("-", $device);
        $device_info['device_brand'] = array_shift($device_parts);
        $device_info['device_model'] = implode("-", $device_parts);

        return $device_info;
    }

    // S3 Folder Mapping
    public function setApplicationIDs() {
        $this->application_ids = array(
            '1' => array('id' => 'com.daidigames.banting',       'folder' => 'asianpoker', 'label' => 'Asian Poker (Android)', 'platform' => 'android'),
            '2' => array('id' => 'id961876128',                  'folder' => 'asianpoker', 'label' => 'Asian Poker (IOS)', 'platform' => 'ios'),

            '3' => array('id' => 'sg.gumi.bravefrontier',        'folder' => 'bravefrontier', 'label' => 'Brave Frontier (Android)', 'platform' => 'android'),
            '4' => array('id' => 'id694609161',                  'folder' => 'bravefrontier', 'label' => 'Brave Frontier (IOS)', 'platform' => 'ios'),

            '5' => array('id' => 'com.bukalapak.android',        'folder' => 'bukalapak', 'label' => 'Bukalapak (Android)', 'platform' => 'android'),
            '6' => array('id' => 'id1003169137',                 'folder' => 'bukalapak', 'label' => 'Bukalapak (IOS)', 'platform' => 'ios'),

            '7' => array('id' => 'sg.gumi.chainchronicleglobal', 'folder' => 'chainchronicle', 'label' => 'Chain Chronicle (Android)', 'platform' => 'android'),
            '8' => array('id' => 'id935189878',                  'folder' => 'chainchronicle', 'label' => 'Chain Chronicle (IOS)', 'platform' => 'ios'),

            '9' => array('id' => 'sg.gumi.wakfu',                'folder' => 'wakfu', 'label' => 'Wakfu (Android)', 'platform' => 'android'),
            '10' => array('id' => 'id942908715',                 'folder' => 'wakfu', 'label' => 'Wakfu (IOS)', 'platform' => 'ios'),

            '11' => array('id' => '_test',                       'folder' => '_test', 'label' => '')
        );
    }

    public function getApplicationIDValue($app_id) {
        return $this->application_ids[$app_id]['id'];
    }

    public function getApplicationFolderValue($app_id) {
        return $this->application_ids[$app_id]['folder'];
    }

    public function getApplicationPlatformValue($app_id) {
        return $this->application_ids[$app_id]['platform'];
    }

    public function getApplicationFolderLabelList() {
        $tmp_list = array();

        foreach ($this->application_ids as $key => $value) {
            if ('' != $value['label']) {
                $tmp_list[$key] = $value['label'];
            }
        }

        return $tmp_list;
    }
    // S3 Folder Mapping

    // Postback Providers
    public function setPostbackProviders() {
        $this->postback_providers = array(
            '1' => array('label' => 'Appsflyer', 'value' => 'appsflyer'),
            '2' => array('label' => 'Hasoffer', 'value' => 'hasoffer'),
            '3' => array('label' => 'Custom', 'value' => 'custom')
        );
    }

    public function getPostBackProvidersValue ($provider_id) {
        return $this->postback_providers[$provider_id]['value'];
    }

    public function getPostBackProvidersLabelList() {
        $tmp_list = array();

        foreach ($this->postback_providers as $key => $value) {
            $tmp_list[$key] = $value['label'];
        }

        return $tmp_list;
    }
    
    public function getAppNames()
    {
        $conn = $this->get('doctrine.dbal.pgsql_connection');
        $sql2  = $conn->prepare("SELECT DISTINCT app_id, app_name FROM applications;");                      
        $sql2->execute();
        
        $all_apps = array();

        for($x = 0; $rows = $sql2->fetch(); $x++) 
        {
            $pre = substr($rows['app_id'],0,2);
            if($pre == "id")
            //if(strpos($rows['app_id'], "id", 1) == 0)
            {
                $rows['app_name'] = $rows['app_name'] . ' - iOS';
            }
            else
            {
                 $rows['app_name'] = $rows['app_name'] . ' - Android';
            }
            
            $all_apps[] = $rows;
        }
        
        return $all_apps;
    }

    // Application Names
    public function setApplicationNames() {
        
        $data = $this->getAppNames();
        $this->application_names = array();
        $cnt = count($data);
        
        for($i = 0; $i < $cnt; $i++)
        {
        	$this->application_names[] = array('label' => $data[$i]['app_name'], 'value' => $data[$i]['app_name']);
        }
        
        /*$this->application_names = array(
            '1' => array('label' => 'Chain Chronicle  RPG - Android', 'value' => 'Chain Chronicle  RPG - Android'),
            '2' => array('label' => 'Wakfu Raiders - Android', 'value' => 'Wakfu Raiders - Android'),
            '3' => array('label' => 'Brave Frontier - Android', 'value' => 'Brave Frontier - Android'),
            '4' => array('label' => 'Bukalapak - Jual Beli Online - Android', 'value' => 'Bukalapak - Jual Beli Online - Android'),
            '5' => array('label' => 'Asian Poker - Big Two - iOS', 'value' => 'Asian Poker - Big Two - iOS'),
            '6' => array('label' => 'Bukalapak - Jual Beli Online - iOS', 'value' => 'Bukalapak - Jual Beli Online - iOS'),
            '7' => array('label' => 'Chain Chronicle - Line Defense RPG - iOS', 'value' => 'Chain Chronicle - Line Defense RPG - iOS'),
            '8' => array('label' => 'Brave Frontier - iOS', 'value' => 'Brave Frontier - iOS'),
            '9' => array('label' => 'Wakfu Raiders - iOS', 'value' => 'Wakfu Raiders - iOS'),
            '10' => array('label' => 'Asian Poker - Big Two - Android', 'value' => 'Asian Poker - Big Two - Android')
        );*/
    }

    public function getApplicationNameValue($app_name_id) {
        return $this->application_names[$app_name_id]['value'];
    }

    public function getApplicationNameLabelList() {
        $tmp_list = array();

        foreach ($this->application_names as $key => $value) {
            $tmp_list[$key] = $value['label'];
        }

        return $tmp_list;
    }
    // Application Names


    // Attribution Types
    public function setAttributionTypes() {
        $this->attribution_types = array(
            '0' => array('label' => 'None', 'value' => 'none'),
            '1' => array('label' => 'Organic', 'value' => 'organic'),
            '2' => array('label' => 'Non-organic', 'value' => 'regular')
        );
    }

    public function getAttributionTypeValue($attribution_type_id) {
        return $this->attribution_types[$attribution_type_id]['value'];
    }

    public function getAttributionTypeLabelList() {
        $tmp_list = array();

        foreach ($this->attribution_types as $key => $value) {
            $tmp_list[$key] = $value['label'];
        }

        return $tmp_list;
    }
    // Attribution Types


    // Event Types
    public function setEventTypes() {
        $this->event_types = array(
            '1' => array('label' => 'Install', 'value' => 'install'),
            '2' => array('label' => 'In App Event', 'value' => 'in-app-event')
        );
    }

    public function getEventTypeValue($event_type_id) {
        return $this->event_types[$event_type_id]['value'];
    }

    public function getEventTypeLabelList() {
        $tmp_list = array();

        foreach ($this->event_types as $key => $value) {
            $tmp_list[$key] = $value['label'];
        }

        return $tmp_list;
    }
    // Event Types
    
    public function storeLogEventToRedshift($providerId,$filePath,$content) {
        $metaData = array();
        $metaData['s3_log_file'] = $filePath;
        $redshift = $this->get('redshift_service');
        $redshift->storeLogEventToRedshift($providerId,$content,$metaData);
        return new Response(
            json_encode(
                array(
                    'file_path' => $filePath
                )
            )
        );
        
    }
    
}