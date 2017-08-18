<?php
namespace Hyper\EventBundle\Controller\Test;
//Symfony
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\HttpFoundation\File\File;

use Doctrine\Common\Annotations\AnnotationReader;

//Entity&Service
use Hyper\EventBundle\Service\Redshift;
use Hyper\Domain\Device\Device;
use Hyper\Domain\Device\AndroidDevice;
use Hyper\Domain\Category\Category;
use Hyper\Domain\Item\InCategoryItem;
use Hyper\Domain\Application\Application;
use Hyper\Domain\Action\Action;
use Hyper\Domain\Action\InstallAction;


class TestController extends Controller
{
    public $request;

    /**
    * @param ContainerInterface $container
    */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function indexAction(Request $request) {
        $this->request = $request;
        $case = $request->get('case');
        if($case == 'testPersistInstallEventToRedshift'){
            $this->testPersistInstallEventToRedshift();
        }

        if($case == 'testPersistAddToCartEventToRedshift'){
            $this->testPersistAddToCartEventToRedshift();
        }

        if($case == 'testPersistPurchaseEventToRedshift'){
            $this->testPersistPurchaseEventToRedshift();
        }

        if($case == 'testSavePresetFilter'){
            $this->testSavePresetFilter();
        }

        if ($case == 'testFetchActiveCountries') {
            $this->testFetchActiveCountries();
        }
        //testLogin
        if ($case == 'testLogin') {
            $this->testLogin();
        }
        //testLogin
        if ($case == 'testSamplingLayout') {
            return $this->testSamplingLayout();
        }
        //testBatchProcessing
        if ($case == 'testBatchProcessing') {
            return $this->testBatchProcessing();
        }
        //proofTestIterateS3Folder
        if ($case == 'proofTestIterateS3Folder') {
            return $this->proofTestIterateS3Folder();
        }
        //testUnZip
        if ($case == 'testUnZip') {
            return $this->testUnZip();
        }
        //testMinuteLooping
        if ($case == 'testMinuteLooping') {
            return $this->testMinuteLooping();
        }
        //testImplementRedshiftProcessing
        if ($case == 'testImplementRedshiftProcessing') {
            return $this->testImplementRedshiftProcessing();
        }
        //testCategory
        if ($case == 'testCategory') {
            return $this->generateCategory();
        }
        //testInCategoryItem
        if ($case == 'testInCategoryItem') {
            return $this->testInCategoryItem();
        }
        //testSendMail
        if ($case == 'testSendMail') {
            return $this->testSendMail();
        }
        //testGenerateFrm
        if ($case == 'testGeneratePurchaseFrm') {
            return $this->testGeneratePurchaseFrm();
        }
        //testCalculateFRMScore
        if ($case == 'testCalculateFRMScore') {
            return $this->testCalculateFRMScore();
        }
        //testGetDevicePlatformId
        if ($case == 'testGetDevicePlatformId') {
            return $this->testGetDevicePlatformId();
        }

        return $this->{$case}();
    }

    public function testPersistInstallEventToRedshift() {
        echo "Start testing testPersistInstallEventToRedshift"."<hr/>";
        $content = array(
            'device_model' => 'D6503',
            'fb_adgroup_id' => null,
            'operator'  => null,
            'click_time'    => null,
            'agency'    => null,
            'ip'    => '39.255.155.60',
            'cost_per_install' => null,
            'fb_campaign_id'    => null,
            'imei'  => '866038020914217',
            'is_retargeting' => false,
            'app_name' => 'Asian Poker - Big Two',
            're_targeting_conversion_type' => null,
            'android_id' => 'fce36c5bcc6e7ff1',
            'city'  => 'None',
            'af_sub1' => null,
            'af_sub2' => null,
            'event_value' => null,
            'af_sub3' => null,
            'fb_adset_name' => null,
            'af_sub4' => null,
            'customer_user_id' => '0a10a9fb-aae3-4074-bd98-4a55901eb72c',
            'mac' => null,
            'af_sub5' => null,
            'campaign' => null,
            'event_name' => null,
            'currency' => null,
            'install_time' => '2015-07-22 10:07:41',
            'fb_adgroup_name' => null,
            'event_time' => '2015-07-22 10:07:41',
            'platform' => 'android',
            'sdk_version' => '1.17',
            'appsflyer_device_id' => '1437559615170-3984624535998410432',
            'wifi' => false,
            'media_source' => 'Organic',
            'country_code' => 'ID',
            'http_referrer' => null,
            'fb_campaign_name' => null,
            'click_url' => null,
            'carrier' => 'TELKOMSEL',
            'language' => 'Bahasa Indonesia',
            'app_id' => 'com.daidigames.banting',
            'app_version' => '1.0.1',
            'attribution_type' => 'organic',
            'af_siteid' => null,
            'os_version' => '4.2.2',
            'fb_adset_id' => null,
            'device_brand' => 'OPPO',
            'event_type' => 'install'


        );
        try {
            $redshift = $this->get('redshift_service');
            $providerId = 1;
            $redshift->storeLogEventToRedshift($providerId,$content);
        } catch (Exception $ex) {
            echo $ex->getMessage();
        }

    }

    public function testPersistAddToCartEventToRedshift() {
        echo "Start testing testPersistAddToCartEventRedshift"."<hr/>";
        $content = array(
            'device_model' => 'SM-G7102',
            'fb_adgroup_id' => null,
            'operator'  => 'T-Sel',
            'click_time'    => null,
            'agency'    => null,
            'ip'    => '114.121.158.124',
            'cost_per_install' => null,
            'fb_campaign_id'    => null,
            'imei'  => '354599061115716',
            'is_retargeting' => false,
            'app_name' => 'Bukalapak - Jual Beli Online',
            're_targeting_conversion_type' => null,
            'android_id' => 'e86c5934d56beb44',
            'city'  => 'None',
            'af_sub1' => null,
            'af_sub2' => null,
            'event_value' => array(
                'af_quantity' => 1,
                'af_price' => 105000,
                'af_currency' => 'IDR',
                'af_content_id' => '4xdww',
                'af_content_type' => 323
            ),
            'af_sub3' => null,
            'fb_adset_name' => null,
            'af_sub4' => null,
            'customer_user_id' => null,
            'mac' => null,
            'af_sub5' => null,
            'campaign' => null,
            'event_name' => 'af_add_to_cart',
            'currency' => 'IDR',
            'install_time' => '2015-07-21 19:44:00',
            'fb_adgroup_name' => null,
            'event_time' => '2015-07-22 08:25:21',
            'platform' => 'android',
            'sdk_version' => '1.17',
            'appsflyer_device_id' => '1437551099992-5323051468612726108',
            'wifi' => false,
            'advertising_id' => '1437551099992-5323051468612726108',
            'media_source' => 'Organic',
            'country_code' => 'ID',
            'http_referrer' => null,
            'fb_campaign_name' => null,
            'click_url' => null,
            'carrier' => 'TELKOMSEL',
            'language' => 'Bahasa Indonesia',
            'app_id' => 'com.bukalapak.android',
            'app_version' => '2.9.3',
            'attribution_type' => 'organic',
            'af_siteid' => null,
            'os_version' => '4.2.2',
            'fb_adset_id' => null,
            'device_brand' => 'samsung',
            'event_type' => 'in-app-event'


        );
        //try {
            $redshift = $this->get('redshift_service');
            $providerId = 1;
            $redshift->storeLogEventToRedshift($providerId,$content);
        //} catch (Exception $ex) {
           // echo $ex->getMessage();
        //}
    }

    public function testPersistPurchaseEventToRedshift() {
        echo "Start testing testPersistPurchaseEventToRedshift"."<hr/>";
        $content = array(
            'device_model' => 'D6503',
            'fb_adgroup_id' => null,
            'operator'  => 'T-Sel',
            'click_time'    => null,
            'agency'    => null,
            'ip'    => '39.255.155.60',
            'cost_per_install' => null,
            'fb_campaign_id'    => null,
            'imei'  => '352876062395105',
            'is_retargeting' => false,
            'app_name' => 'Asian Poker - Big Two',
            're_targeting_conversion_type' => null,
            'android_id' => 'c594da3576fdaccd',
            'city'  => 'None',
            'af_sub1' => null,
            'af_sub2' => null,
            'event_value' => array(
                'af_quantity' => 1,
                'af_revenue' => '3.99',
                'af_currency' => 'IDR',
                'af_content_id' => 'com_daidigames_banting_coin_3',
                'af_content_type' => 'cn',
                'af_validated' => 'true',
                'af_receipt_id' => '12999763169054705758.1397200382047009'
            ),
            'af_sub3' => null,
            'fb_adset_name' => null,
            'af_sub4' => null,
            'customer_user_id' => 'a514838a-845d-4d70-9dab-2e96a634578a',
            'mac' => null,
            'af_sub5' => null,
            'campaign' => null,
            'event_name' => 'af_purchase',
            'currency' => 'SGD',
            'install_time' => '2015-07-21 22:30:00',
            'fb_adgroup_name' => null,
            'event_time' => '2015-07-22 11:04:54',
            'platform' => 'android',
            'sdk_version' => '1.17',
            'appsflyer_device_id' => '1437561037045-4567210348859808302',
            'wifi' => false,
            'advertising_id' => '8d440d5f-89eb-4cf3-8550-e31e688c19be',
            'media_source' => null,
            'country_code' => 'ID',
            'http_referrer' => null,
            'fb_campaign_name' => null,
            'click_url' => null,
            'carrier' => 'TELKOMSEL',
            'language' => 'English',
            'app_id' => 'com.daidigames.banting',
            'app_version' => '2.9.3',
            'attribution_type' => 'organic',
            'af_siteid' => null,
            'os_version' => '5.0.2',
            'fb_adset_id' => null,
            'device_brand' => 'Sony',
            'event_type' => 'in-app-event'
        );
        //try {
            $redshift = $this->get('redshift_service');
            $providerId = 1;
            $redshift->storeLogEventToRedshift($providerId,$content);
        //} catch (Exception $ex) {
           // echo $ex->getMessage();
        //}
    }

    public function testBatchProcessing() {
        $filterRepo = $this->container->get('filter_repository');
        for ($i = 0;$i<10;$i++) {
            $filter = new \Hyper\Domain\Filter\Filter();
            $authenticationId = rand();
            $presetName = 'giaosu@'.$authenticationId;
            $filter->setAuthenticationId($authenticationId);
            $filter->setPresetName($presetName);
            $filter->setFilterMetadata(array());
            $filterRepo->save($filter);
        }
        $filterRepo->completeTransaction();



    }

    public function testRegisterShutdownFunction() {
        $a = array();
        echo $a['giao_su'];
        //$this->notDefinedMethod();
    }

    public function testSavePresetFilter() {
        $filterRepo = $this->container->get('filter_repository');
        $filter = new \Hyper\Domain\Filter\Filter();
        $authenticationId = rand();
        $presetName = 'giaosu@'.$authenticationId;
        $filter->setAuthenticationId($authenticationId);
        $filter->setPresetName($presetName);
        $filter->setFilterMetadata(array());
        $filterRepo->save($filter);
        $filterRepo->completeTransaction();

        $filter = $filterRepo->getByIdentifier($authenticationId,$presetName);
        \Doctrine\Common\Util\Debug::dump($filter);
        die;
    }

    public function testFetchActiveCountries() {

        $column = null;
        $criteria_key = '\Hyper\Domain\Device\Device.countryCode';
        $criteria_key_parse = explode('.',$criteria_key);
        $entityClass = ucfirst( array_shift($criteria_key_parse) );
        $currentProperty = array_shift($criteria_key_parse);
        echo "property : ".print_r($currentProperty);

        $reflectionClass = new \ReflectionClass(new $entityClass);
		$properties = $reflectionClass->getProperties();
		echo "<pre>";
        $reader = new AnnotationReader();
        foreach ($properties as $property) {
            if($property->name == $currentProperty){
                $column = $reader->getPropertyAnnotation($property,'Doctrine\ORM\Mapping\Column')->name;
                echo "<hr/>";
		        print_r($column);
		        break;
            }


        }

        //print_r($fieldNameByEntity);
        /*
        $deviceRepo = $this->get('device_repository');
        $devices = $deviceRepo->getActiveCountries();
        \Doctrine\Common\Util\Debug::dump($devices);
        */
    }
    public function testLogin() {
        $authController = $this->get('auth.controller');
        $authId = $authController->getLoggedAuthenticationId();
        print_r ($authId);
        $presetFilterRepo = $this->get('filter_repository');
        $presetFilters = $presetFilterRepo->getAllByAuthenticationId(1);
        \Doctrine\Common\Util\Debug::dump($presetFilters);
        throw new \lib\Exception\InvalidAuthenticationException("test custom exception");
    }

    public function testSamplingLayout() {
        return $this->render('sampling/sampling_layout.html.twig');
    }

    public function proofTestIterateS3Folder() {

        $region = $this->container->getParameter('amazon_s3_region');
        $bucket = $this->container->getParameter('amazon_s3_bucket_name');
        $securityKey = $this->container->getParameter('amazon_aws_key');
        $securitySecret = $this->container->getParameter('amazon_aws_secret_key');

        $localFolder = '/var/www/html/projects/event_tracking/web/batch_processing_s3';
        //fetch from app list,date time
        //$prefix = 'bukalapak/2015/09/29/10/';
        //bukalapak\/2015\/10\/01\/07
        $prefix = 'bukalapak/2015/10/01/07';
        $credentials = new \Aws\Credentials\Credentials($securityKey, $securitySecret);
        $options = [
            //'host' => 'standalone-a.s3-website-ap-southeast-1.amazonaws.com',
            'region'            => $region,
            'version'           => '2006-03-01',
            'signature_version' => 'v4',
            'credentials' => $credentials
        ];

        $s3 = new  \Aws\S3\S3Client(
            $options
        );
        //list folder
        $objects = $s3->listObjects(
            array(
                    'Bucket' => $bucket,
                    'Prefix'    => $prefix,
                    'Delimiter' => '/'
            )
        );
        echo "<pre>";
        var_dump($objects->toArray());
        die;

        $objects = $s3->getIterator('ListObjects', array(
            'Bucket'    => $bucket,
            'Prefix'    => $prefix,
            'Delimiter' => '/',
        ));
        $totalObjects = iterator_count($s3->getIterator('ListObjects',
            array(
                'Bucket'    => $bucket,
                'Prefix'    => $prefix,
                //'Delimiter' => '/',
            )
            /*
            ,
            array(
                'limit'     => 1,
                'page_size' => 1
            )
            */
        ));
        //echo "--++--$totalObjects";die;

        $localDestination = $localFolder.'/'.$prefix;
        if (!file_exists($localDestination)) {
            mkdir($localDestination, 0755, true);
        }
        foreach ($objects as $object) {
            echo "<hr/>-----";
            var_dump($object);
            //continue;
            $tmpFile = $localFolder.'/'.$object['Key'];
            $s3->getObject(
                array(
                    'Bucket' => $bucket,
                    'Key' => $object['Key'],
                    'SaveAs' => $tmpFile
                )
            );
            $content = $this->unzip($tmpFile);

        }
    }

    public function testUnZip() {
        $file = '/var/www/html/projects/event_tracking/web/batch_processing_s3/bukalapak/2015/09/29/04/appsflyer_com.bukalapak.android_in-app-event_1435620965_560a49ef791c5.gz';
        $this->unzip($file);
    }

    public function testMinuteLooping() {
        $year = date('y');
        $month = date('m');
        $day = date('d');
        $hour = date('h');
        for ($i = 0; $i < 60; $i++ ) {
            $minute = str_pad($i, 2, '0', STR_PAD_LEFT);
            echo $minute."<hr/>";
        }
    }

    private function unzip($file) {
        // try catch
        $zh = gzopen($file,'r') or die("can't open: $php_errormsg");
        $content = '';
        while ($line = gzgets($zh,1024)) {
            $content .= $line;
        }
        gzclose($zh) or die("can't close: $php_errormsg");
        var_dump(json_decode($content,true));
    }

    public function testImplementRedshiftProcessing(){
        $redshiftBatchProcessing = $this->get('redshift_batch_processing_service');
        $redshiftBatchProcessing->init();
        $redshiftBatchProcessing->process();
        return;
    }

    public function generateCategory(){
        $em=$this->container->get('doctrine')->getEntityManager('pgsql');

        $itemRepository = $this->get('item_repository');
        $categoryRepository = $this->get('category_repository');
        $inCategoryItemRepository = $this->get('in_category_item_repository');

        $results = $itemRepository->getCategoryIdentifiers();
        $categoryIdentifiers = $results['category_identifiers'];
        $commitPoint = 3000;
        /*
        $i = 0;
        $commitPoint = 1000;

        $em->getConnection()->beginTransaction(); // suspend auto-commit
        echo "total categoryIdentifiers: ".count($categoryIdentifiers)."\n";
        foreach ($categoryIdentifiers as $categoryIdentifier) {
            $category = new Category();
            $category->setName('');
            $category->setCode($categoryIdentifier['code']);
            $category->setAppId($categoryIdentifier['app_id']);
            $category->setParentId('0');
            $em->persist($category);

            if ($i%$commitPoint == 0 && $i!=0) {
                $em->flush();//flush
                $em->clear();//clear
                echo "commit category \n";
                $em->getConnection()->commit();
                $em->getConnection()->beginTransaction();
            }
            $i++;
        }
        $em->flush();//flush
        $em->clear();//clear
        $em->getConnection()->commit();
        echo "final commit category \n";
        */
        $results['category_identifiers'] = null;
        unset($results['category_identifiers']);
        $itemsInCategory = $results['items_in_category'];
        $results['items_in_category'] = null;
        unset($results['items_in_category']);
        unset($results);
        echo "total itemsInCategory: ".count($itemsInCategory)."\n";
        $em->getConnection()->beginTransaction(); // suspend auto-commit
        $j=0;
        foreach ($itemsInCategory as $k=>$itemInCategory) {
            //fetch category by code and app_id
            $categoryIdentifier = array();
            $categoryIdentifier['app_id'] = $itemInCategory['app_id'];
            $categoryIdentifier['code'] = $itemInCategory['code'];
            $category=$categoryRepository->getByIdentifier($categoryIdentifier);
            if($category instanceof Category) {
                $inCategoryItemIdentifier = array();
                $inCategoryItemIdentifier['item_code'] = $itemInCategory['item_code'];
                $inCategoryItemIdentifier['category_id'] = $category->getId();
                //$inCategoryItem =$inCategoryItemRepository->getByIdentifier($inCategoryItemIdentifier);
                //if (!$inCategoryItem instanceof InCategoryItem) {
                    //echo $itemInCategory['item_code'];
                $inCategoryItem = new InCategoryItem();
                $inCategoryItem->setItemCode($itemInCategory['item_code']);
                $inCategoryItem->setCategory($category);
                $em->persist($inCategoryItem);
                $itemsInCategory[$k]=null;
                unset($itemsInCategory[$k]);
                if ($j%$commitPoint == 0 && $j!=0) {
                    $em->flush();//flush
                    $em->clear();
                    echo "commit in category item \n";
                    $em->getConnection()->commit();
                    $em->getConnection()->beginTransaction();
                }
                $j++;
                    /*
                } else {
                    //working arround by placing unset to free up memory
                    //$inCategoryItem->__destruct();
                    $inCategoryItem = null;
                    unset($inCategoryItem);
                    continue;
                }
                */
            }
        }

        $em->flush();//flush
        $em->clear();
        echo "final commit in category item \n";
        $em->getConnection()->commit();

        //echo "<pre>";
        //print_r($results);
        //die;

        //create category
        //create in category items
    }

    public function testInCategoryItem() {
        //com_daidigames_banting_coin_3
        //561cb874a82ca2.84551250
        $inCategoryItemRepository = $this->get('in_category_item_repository');
        $inCategoryItemIdentifier['item_code'] = 'com_daidigames_banting_coin_3____';
        $inCategoryItemIdentifier['category_id'] = '561cb874a82ca2.84551250';
        $inCategoryItem =$inCategoryItemRepository->getByIdentifier($inCategoryItemIdentifier);
        die(var_dump($inCategoryItem instanceof InCategoryItem));
    }

    public function testInCategoryItems(){
        $em=$this->container->get('doctrine')->getEntityManager('pgsql');

        $itemRepository = $this->get('item_repository');
        $categoryRepository = $this->get('category_repository');
        $inCategoryItemRepository = $this->get('in_category_item_repository');

        $results = $itemRepository->getCategoryIdentifiers();
        $categoryIdentifiers = $results['items_in_category'];
        echo count($categoryIdentifiers);die;

    }

    public function testSendMail(){
         $message = \Swift_Message::newInstance()
        ->setSubject('Hello Email')
        ->setFrom('danghoangthien@gmail.com')
        ->setTo('thien@hypergrowth.co')
        ->setBody('hello Paul');
        $this->get('mailer')->send($message);
    }

    public function generatePurchaseFrm() {
        $em=$this->container->get('doctrine')->getEntityManager('pgsql');
        $em2=$this->container->get('doctrine')->getEntityManager('pgsql');

        $frmRepository = $this->get('frm_repository');
        $transactionActionRepo = $this->get('transaction_action_repository');
        //transacted_item_repository
        $transactedItemRepo = $this->get('transacted_item_repository');
        echo "PURCHASE_BEHAVIOUR_ID".\Hyper\Domain\Action\Action::BEHAVIOURS['PURCHASE_BEHAVIOUR_ID'];
        $subQb = $frmRepository->createQueryBuilder('frm')
                    ->select('frm.referenceEventId')
                    ->where('frm.eventType = '.\Hyper\Domain\Action\Action::BEHAVIOURS['PURCHASE_BEHAVIOUR_ID'])
                    ->distinct();


        $qb = $transactionActionRepo->createQueryBuilder('ta');
        $query = $qb->select('ta')
                 ->addSelect('IDENTITY(ta.action) as actionId')
                 ->addSelect('(SELECT applications.appId FROM Hyper\Domain\Application\Application applications WHERE applications.id = ta.applicationId ) AS appId')
                 ->where($qb->expr()->notIn('ta.action', $subQb->getDQL()))
                 //->where($qb->expr()->eq('IDENTITY(ta.action)','?1'))
                 ->orderBy('ta.transacted_time','ASC')
                 //->setParameter(1,"5630762493a249.45763871")
                 ->getQuery();
        $iterableResult = $query->setFirstResult('0')->setMaxResults('500000')->iterate();

        $i = 0;
        $commitPoint = 1000;

        $em->getConnection()->beginTransaction(); // suspend auto-commit

        $iterableResult = $query->iterate();
        //echo "<pre>";
        echo "persist :";
        foreach ($iterableResult as $key => $row) {
            //\Doctrine\Common\Util\Debug::dump($row);//continue;
            try {
                $transactionAction = $row[0][0];

                $transactionCurrency = $transactionAction->getCurrency();

                //\Doctrine\Common\Util\Debug::dump($transactionAction);//die;
                //$em2->getConnection()->beginTransaction();
                $transactedItems = $transactionAction->getTransactedItems();
                //$em2->clear();
                //$em2->getConnection()->commit();
                //$em2->getConnection()->close();

                if ($transactedItems instanceof \Doctrine\Common\Collections\ArrayCollection) {
                    $transactedItems = $transactedItems->toArray();
                } else {
                    $transactedItems = array();
                }

                $itemMeta = array();
                if( count($transactedItems) > 0) {
                    foreach ($transactedItems as $transactedItem) {
                        $item = $transactedItem->getItem();
                        $code = $item->getCode();
                        $metaData = $item->getMetadata();
                        $metaData = json_decode($metaData,true);
                        $itemMeta[$code]['item_code'] = $code;
                        if (isset($metaData['content_type'])) {
                            $itemMeta[$code]['category_code'] = $metaData['content_type'];
                        } elseif (isset($metaData['af_content_type'])) {
                            $itemMeta[$code]['category_code'] = $metaData['af_content_type'];
                        }
                        if ($transactionCurrency == 'USD') {
                            $baseCurrencyAmount = $transactedItem->getTransactedPrice();
                        } else {
                            $baseCurrencyAmount = $this->getBaseCurrencyAmount($transactionCurrency,$transactedItem->getTransactedPrice());
                        }
                        //$baseCurrencyAmount = $this->getBaseCurrencyAmount($transactionCurrency,$transactedItem->getTransactedPrice());
                        $itemMeta[$code]['base_curency_transacted_amount'] = $baseCurrencyAmount;
                        unset($item);
                    }
                    //return;
                }
                /*
                //die;

                //\Doctrine\Common\Util\Debug::dump($transactedItems);
                var_dump($transactedItems);
                return;
                */

                $frm = new \Hyper\Domain\Frm\Frm();

                $deviceId = $transactionAction->getDeviceId();
                $frm->setDeviceId($deviceId);
                $action = $transactionAction->getAction();
                $actionId = $action->getId();
                $appId = $row[$key]['appId'];
                if ($appId == null){
                    throw \Hyper\Domain\Action\TransactionActionException::appIdIsNull($actionId);
                }
                $frm->setAppId($appId);

                $eventType = $action->getBehaviourId();
                $frm->setEventType($eventType);//action behaviour id
                $frm->setAccountType(1);//hard coding

                $frm->setReferenceEventId($actionId);//transaction_id
                //$transactedItems = $transactionAction->getTransactedItems();
                $frm->setReferenceItemCodes($itemMeta);
                $transactionAmount = $transactionAction->getTransactedPrice();
                if ($transactionCurrency == 'USD'){
                    $baseCurrencyAmount = $transactionAmount;
                } else {
                    $baseCurrencyAmount = $this->getBaseCurrencyAmount($transactionCurrency,$transactionAmount);
                }
                $frm->setAmount($baseCurrencyAmount);
                $frm->setBaseCurrency('USD');
                $transactedTime = $transactionAction->getTransactedTime();
                $frm->setEventTime($transactedTime);
                $em->persist($frm);
                unset($frm);
                unset($action);
                unset($transactionAction);
                unset($transactedItems);

                if ($i%$commitPoint == 0 && $i!=0) {
                    echo $i.",";
                    $em->flush();//flush
                    $em->clear();//clear
                    //echo "commit category \n";
                    $em->getConnection()->commit();
                    $em->getConnection()->beginTransaction();
                }

            } catch (\Hyper\Domain\Currency\CurrencyException $ex) {
                $em->detach($frm);
                unset($row);
                echo  "\n".$ex->getMessage()."\n";
                continue;
            } catch (\Hyper\Domain\Action\TransactionActionException $transactionEx){
                $em->detach($frm);
                unset($row);
                echo "\n".$transactionEx->getMessage()."\n";
                continue;
            }


            unset($row);
            $i++;
            //*/

        }

        $em->flush();//flush
        $em->clear();
        echo "final commit in frm \n";
        $em->getConnection()->commit();
        $em->getConnection()->close();

    }

    public function generatePurchaseFrmV2() {
        $em=$this->container->get('doctrine')->getEntityManager('pgsql');

        $frmRepository = $this->get('frm_repository');
        $transactionActionRepo = $this->get('transaction_action_repository');
        //transacted_item_repository
        $transactedItemRepo = $this->get('transacted_item_repository');
        echo "PURCHASE_BEHAVIOUR_ID".\Hyper\Domain\Action\Action::BEHAVIOURS['PURCHASE_BEHAVIOUR_ID'];
        $subQb = $frmRepository->createQueryBuilder('frm')
                    ->select('frm.referenceEventId')
                    ->where('frm.eventType = '.\Hyper\Domain\Action\Action::BEHAVIOURS['PURCHASE_BEHAVIOUR_ID'])
                    ->distinct();


        $qb = $transactionActionRepo->createQueryBuilder('ta');
        $query = $qb->select('ta')
                 ->addSelect('IDENTITY(ta.action) as actionId')
                 ->addSelect('(SELECT applications.appId FROM Hyper\Domain\Application\Application applications WHERE applications.id = ta.applicationId ) AS appId')
                 ->where($qb->expr()->notIn('ta.action', $subQb->getDQL()))
                 //->where($qb->expr()->eq('IDENTITY(ta.action)','?1'))
                 ->orderBy('ta.transacted_time','ASC')
                 //->setParameter(1,"5630762493a249.45763871")
                 ->getQuery();
        $iterableResult = $query->setFirstResult('0')->setMaxResults('120000')->iterate();

        $i = 0;
        $commitPoint = 1000;

        $em->getConnection()->beginTransaction(); // suspend auto-commit

        $iterableResult = $query->iterate();
        //echo "<pre>";
        echo "persist :";
        foreach ($iterableResult as $key => $row) {
            //\Doctrine\Common\Util\Debug::dump($row);//continue;
            try {
                $transactionAction = $row[0][0];
                $transactionCurrency = $transactionAction->getCurrency();
                //\Doctrine\Common\Util\Debug::dump($transactionAction);//die;
                try {
                $transactedItems = $transactionAction->getTransactedItems();
                } catch (\Exception $ex) {
                    echo $ex->getMessage();
                    $transactedItems = array();
                }

                if ($transactedItems instanceof \Doctrine\Common\Collections\ArrayCollection) {
                    $transactedItems = $transactedItems->toArray();
                } else {
                    $transactedItems = array();
                }

                $itemMeta = array();
                if( count($transactedItems) > 0) {
                    foreach($transactedItems as $transactedItem) {
                        $item = $transactedItem->getItem();
                        $code = $item->getCode();
                        $metaData = $item->getMetadata();
                        $metaData = json_decode($metaData,true);
                        $itemMeta[$code]['item_code'] = $code;
                        if(isset($metaData['content_type'])) {
                            $itemMeta[$code]['category_code'] = $metaData['content_type'];
                        } elseif (isset($metaData['af_content_type'])) {
                            $itemMeta[$code]['category_code'] = $metaData['af_content_type'];
                        }
                        $baseCurrencyAmount = $this->getBaseCurrencyAmount($transactionCurrency,$transactedItem->getTransactedPrice());
                        $itemMeta[$code]['base_curency_transacted_amount'] = $baseCurrencyAmount;
                        unset($item);
                    }
                    //return;
                }
                /*
                //die;

                //\Doctrine\Common\Util\Debug::dump($transactedItems);
                var_dump($transactedItems);
                return;
                */

                $frm = new \Hyper\Domain\Frm\Frm();

                $deviceId = $transactionAction->getDeviceId();
                $frm->setDeviceId($deviceId);
                $appId = $row[$key]['appId'];
                $frm->setAppId($appId);
                $action = $transactionAction->getAction();
                $eventType = $action->getBehaviourId();
                $frm->setEventType($eventType);//action behaviour id
                $frm->setAccountType(1);//hard coding
                $actionId = $action->getId();
                $frm->setReferenceEventId($actionId);//transaction_id
                //$transactedItems = $transactionAction->getTransactedItems();
                $frm->setReferenceItemCodes($itemMeta);
                $transactionAmount = $transactionAction->getTransactedPrice();
                if ($transactionCurrency == 'USD'){
                    $baseCurrencyAmount = $transactionAmount;
                } else {
                    $baseCurrencyAmount = $this->getBaseCurrencyAmount($transactionCurrency,$transactionAmount);
                }
                $frm->setAmount($baseCurrencyAmount);
                $frm->setBaseCurrency('USD');
                $transactedTime = $transactionAction->getTransactedTime();
                $frm->setEventTime($transactedTime);
                $em->persist($frm);
                unset($frm);
                unset($action);
                unset($transactionAction);
                unset($transactedItems);
                echo $i.",";
                if ($i%$commitPoint == 0 && $i!=0) {
                    $em->flush();//flush
                    $em->clear();//clear
                    //echo "commit category \n";
                    $em->getConnection()->commit();
                    $em->getConnection()->beginTransaction();
                }

            } catch (\Hyper\Domain\Currency\CurrencyException $ex) {
                    $em->detach($frm);
                    unset($row);
                    echo $ex->getMessage()."\n";
                    continue;
            }

            catch (\Exception $ex) {
                    unset($row);
                    echo $ex->getMessage()."\n";
                    continue;
            }

            unset($row);
            $i++;
            //*/

        }

        $em->flush();//flush
        $em->clear();
        echo "final commit in frm \n";
        $em->getConnection()->commit();
        $em->getConnection()->close();

    }

    public function getBaseCurrencyAmount($fromCurrency,$amount) {
        $currencyRepo = $this->container->get('currency_repository');
        $convertedAmount = $currencyRepo->convert($fromCurrency,$amount);
        if($convertedAmount === false){
            throw new \Exception("Cannot convert amount");
        }
        return $convertedAmount;
    }

    public function testCalculateFRMScore(){
        //get Frm records by device IDs and appIds
        //select * from frm where appIds in () and $device Ids in () group by AppId,deviceId
        /*
        $appIds = array('com.daidigames.banting');
        $deviceIds = array('56419cbf14a634.99380760');
        $frmRepository = $this->get('frm_repository');
        $qb = $frmRepository->createQueryBuilder('frm');
        $qb->select('frm')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->in('frm.appId', $appIds),
                    $qb->expr()->in('frm.deviceId', $deviceIds)
                )
            )
        ->orderBy('frm.appId','DESC')
        ->addOrderBy('frm.deviceId','DESC');
        $query = $qb->getQuery();
        $iterableResult = $query->iterate();
        foreach ($iterableResult as $row) {
            // \Doctrine\Common\Util\Debug::dump($row);
        }
        */
        $audienceData = $this->get('dashboard_client_show_action_data');
        $audienceData->showHypidData();

    }
    public function testGetDevicePlatformId() {
        $deviceId = '55d2db4d135657.80557355';
        $deviceRepo = $this->get('device_repository');
        $result = $deviceRepo->getPlatformId($deviceId);
        var_dump($result);die;
    }
    public function testDeviceFRM() {
        $deviceId = '55d2b54cabe2a7.49177732';
        $frmRepository = $this->get('frm_repository');
        $appIds = array ('com.bukalapak.android');
        $result = $frmRepository->getDeviceFrmByAppIds($deviceId,$appIds);
        var_dump($result);die;
    }
    public function testDeviceLastActivity() {
        $deviceId = '55d2b54cabe2a7.49177732';
        $actionRepo = $this->get('action_repository');
        $appIds = array ('com.bukalapak.android');
        $result = $actionRepo->getLastActivityTime($deviceId,$appIds);
        var_dump($result);die;
    }

    public function testShowHypidData(){

        // TODO - fiter app by platform,parse platform as a parameter
        //$request->get('action_type');
        $deviceId = '5646720db7bfb8.15429629';

        //$deviceId = $request->get('device_id');
        //echo $deviceId;die;
        /*
        $authController = $this->get('auth.controller');
        $authIdInSession = $authController->getLoggedAuthenticationId();

        $authenticationRepo = $this->container->get('authentication_repository');
        $authentication = $authenticationRepo->findbyCriteria('id', "$authIdInSession");
        // print $authIdInSession ." -- " . $authentication->getId(); die;
        if (!$authentication instanceof Authentication) {
                throw new \Exception('invalid authentication');
        }
        //"app_id1,app_id2,app_id3...""



        $clientIdsByAuthentication = $authentication->getClientId();
        $client_ids  = explode(",", $clientIdsByAuthentication);
        $client_ids  = "'" .implode("','", $client_ids) ."'";
        $conn = $this->get('doctrine.dbal.pgsql_connection');
        $sql  = $conn->prepare("SELECT client_app FROM client WHERE id IN ($client_ids);");
        $sql->execute();
        $data = array();
        for($x = 0; $row = $sql->fetch(); $x++)
        {
            $data[] = $row;
        }
        //$deviceId = $request->request->get('device_id');

        $appIdsByAuthentication = $data[0]['client_app'];
        */
        $appIdsByAuthentication = 'sg.gumi.bravefrontier';
        $deviceRepo = $this->get('device_repository');
        $devicePlatformId = $deviceRepo->getPlatformId($deviceId);
        //print_r($devicePlatformId);die;
        //get device platform
        //get device google ads id or idfv
        $frmRepository = $this->get('frm_repository');
        $appIds = explode(',',$appIdsByAuthentication);
        //echo $appIds;die;
        //$appIds  = "'" .implode("','", $appIds) ."'";
        $frmData = $frmRepository->getDeviceFrmByAppIds($deviceId,$appIds);
        //print_r($frmData);die;
        //get total transaction by app ids
        $totalTransaction = count($frmData);
        //get total transacted amount of device by app ids
        $totalAmount = 0;
        $frmDataByAppIds = array();
        foreach ($frmData as $frm){
            $key = $frm['appId'];
            $frmDataByAppIds[$key][] = $frm;
            $totalAmount += $frm['amount'];
        }
        //get last transaction by app ids
        $lastTransaction = end($frmData);
        $lastTransactionTime = date('Y/m/d H:i:s',$lastTransaction['eventTime']);
        die;
        //get last actions(event)
        $actionRepo = $this->get('action_repository');
        $result = $actionRepo->getLastActivityTime($deviceId,$appIds);
        if (isset($result['happenedAt'])){
            $lastActivity = date('Y/m/d H:i:s',$result['happenedAt']);
        } else {
            $lastActivity = null;
        }

        $frmScoreByAppIds = array();

        foreach ($appIds as $appId){
            if(isset ($frmDataByAppIds[$appId]))
            {
                $frmScoreByAppIds[$appId] = $this->calculateDeviceFrm($appId,$deviceId,$frmDataByAppIds[$appId]);
            }
        }

        //return new Response(json_encode(array("data" => $frmScoreByAppIds['com.daidigames.banting'])));

        $cnt = count($frmScoreByAppIds);

        $result = array(
            'device_platform_id' => $devicePlatformId,
            'total_transactions' => $totalTransaction,
            'last_activitiy' =>$lastActivity,
            'total_amount' => $totalAmount,
            'last_transaction_time' => $lastTransactionTime,
            'frm_score' => $frmScoreByAppIds,
            //'banting'   => $frmScoreByAppIds['com.daidigames.banting'],
            'count'     => $cnt
        );

        $user = array(
            'device_platform_id' => $devicePlatformId,
            'last_activitiy' =>$lastActivity,
            'last_transaction_time' => $lastTransactionTime,
            'total_amount' => $totalAmount,
            'total_transactions' => $totalTransaction
        );

        return new Response(
            json_encode(
                array(
                    'device_transaction_information' => $result,
                    'user' => $user
                )
            )
        );

    }

    public function testActiveCategory() {

        $categoryRepository = $this->get('category_repository');
        $appIds = array('com.bukalapak.android');
        $categories = $categoryRepository->getActiveCategories($appIds);
        print_r($categories[0]);die;
    }

    public function doctrinePerformance() {
        $androidDeviceRepository = $this->get('android_device_repository');
        $identifier['android_id'] ='309bf7bc510efe46--';
        $identifier['advertising_id'] = '19717d43-5730-4fc6-9cac-61dd9fae9fa5--';
        $m1 = round(microtime(true) * 1000);
        $androidDevice = $androidDeviceRepository->getByIdentifier($identifier);
        $m2 = round(microtime(true) * 1000);
        $total_m = $m2-$m1;
         echo "<hr/>$m2 - $m1 : ".$total_m   ."<hr/>";
        die;
    }
    public function doctrinePerformance2() {
        $androidDeviceRepository = $this->get('android_device_repository');
        $identifier['android_id'] ='dceb6782977e12b9';
        //$identifier['android_id'] ='';
        //$identifier['android_id'] =null;
        $identifier['advertising_id'] = '3922da4d-3688-4692-aa61-9e1d71db8c31';
        //$identifier['advertising_id'] = null;
        $m1 = round(microtime(true) * 1000);
        $androidDevice = $androidDeviceRepository->getByIdentifier($identifier);
        $m2 = round(microtime(true) * 1000);
        $total_m = $m2-$m1;
        echo "<hr/>$m2 - $m1 : ".$total_m  ."<hr/>";
        var_dump($androidDevice);
        die;
    }

    public function doctrinePerformance3() {
        $appRepository = $this->get('application_repository');
        $identifier['app_id'] = 'com.bukalapak.android';
        $identifier['app_name'] = 'Bukalapak - Jual Beli Online';
        $identifier['app_version'] = '2.8.0fgd';
        $m1 = round(microtime(true) * 1000);
        $application = $appRepository->getByIdentifier($identifier);
        $m2 = round(microtime(true) * 1000);
        $total_m = $m2-$m1;
         echo "<hr/>$m2 - $m1 : ".$total_m   ."<hr/>";
        die;
    }

    public function generateAnalytics()
    {
        $analyticsController = $this->get('analytics.controller');
        $analyticsController->generateMetadataRows();
        die;
    }
    public function generateMetadataValues()
    {
        $analyticsController = $this->get('analytics.controller');
        $analyticsController->generateMetadataValues();
        die;
    }

    public function getAndroidByIdentifier(){

        $androidDeviceRepository = $this->get('android_device_repository');
        $identifier['android_id'] = '000';
        $identifier['advertising_id'] ='---';
        $ad = $androidDeviceRepository->getByIdentifier($identifier);
        //print_r($identifier);
        print_r($ad);
        die;
    }

    public function processExists() {
        $processName = 'php app/console analytics_metadata:generate --env=prod';
        $exists= false;
        exec("ps -fC 'php'", $psList);
        if (count($psList) > 1) {
            foreach($psList as $ps){
                if(strpos($ps,$processName)!==false){
                    $exists = true;
                }
            }
        }
        return $exists;
    }

    /* client/apps/folders */
    public function showArrays()
    {
        $clientRepo = $this->container->get('client_repository');
        $records = $clientRepo->getAllClient();

        $cnt = count($records);
        $clientS3Folders = array();

        for($i = 0; $i < $cnt; $i++)
        {
            $apps = explode(',', $records[$i]->getClientApp());
            $head = $records[$i]->getS3Folder();
            foreach($apps as $k => $v)
            {
                $clientS3Folders["$v"] = $head;
            }
        }

        $hardcodedS3Folders = array(
            'com.bukalapak.android' => 'bukalapak',
            'id1003169137' => 'bukalapak',
            'com.daidigames.banting' => 'asianpoker',
            'id961876128' => 'asianpoker',
            'sg.gumi.bravefrontier' => 'bravefrontier',
            'id694609161' => 'bravefrontier',
            'sg.gumi.chainchronicleglobal' => 'chainchronicle',
            'id935189878' => 'chainchronicle',
            'sg.gumi.wakfu' => 'wakfu',
            'id942908715'   => 'wakfu',
            '_test' => '_test',
            'com.apn.mobile.browser' => 'askbrowser',
            'com.woi.liputan6.android' => 'liputan6',
            'id1048856462' => 'liputan6',
            'com.google.android.apps.santatracker' => 'santatracker',
            'com.akasanet.yogrt.android' => 'yogrt',
            'id950197859' => 'yogrt',
            'id1049249612' => 'raiderquests',
            'com.tiket.gits' => 'tiket',
            'id890405921' => 'tiket'
            );

        $mergedS3Folders  = array();
        $mergedS3Folders  = array_merge($hardcodedS3Folders, $clientS3Folders);

        print "<pre>";
        print "From Client table: <br />";
        print_r($clientS3Folders);

        print "<br /> Hard coded array: <br />";
        print_r($hardcodedS3Folders);

        print "<br /> Merged arrays: <br />";
        print_r($mergedS3Folders );

        print "</pre";
    }

    public function testPostArray(){
        /*
        try {
         throw new \Exception("Duplicate Preset name");
        }
        catch (\Exception $ex) {
             $response = new Response($ex->getMessage());
                $response->setStatusCode(400);
                return $response;
        }
        */
        $post = $this->request->request->all();
        echo "<pre>";
        var_dump($post);
        die;
    }

    public function testBatchInsert(){
        $deviceRepository = $this->container->get('device_repository');
        $androidDeviceRepository = $this->container->get('android_device_repository');
        $applicationRepository = $this->container->get('application_repository');
        $actionRepository = $this->container->get('action_repository');
        $installActionRepository = $this->container->get('install_action_repository');

        $em=$this->container->get('doctrine')->getManager('pgsql');
        $em->getConnection()->beginTransaction(); // suspend auto-commit
        $commitPoint = 100;
        $j = 3;
        for ($i = 0; $i < 1000; $i++) {
            $deviceId = 'test_dev_'.$j.'.'.$i;


            $identifier = array();
            $identifier['android_id'] ='111';
            $identifier['advertising_id'] = '0';
            $result = $androidDeviceRepository->getByIdentifier($identifier);
            if (isset($result['device_id'])) {
                $deviceId = $result['device_id'];
                echo "device id existed"."\n";
                continue;
            } else {
                echo "go ahead". "\ns";
            }

            $device = new Device($deviceId);
            $device->setPlatform(2);
            $device->setClickTime('1');
            $device->setInstallTime('1');
            $device->setCountryCode('vn');
            $device->setCity('hcm');
            $device->setIp('127.0.0.1');
            $device->setWifi('no wifi');
            $device->setLanguage('Vietnamese');
            $device->setMac('mac');
            $device->setOperator('win7');
            $device->setDeviceOsVersion('0.1');
            $deviceRepository->save($device);
            $androidDevice = new AndroidDevice();
            $androidDevice->setAdvertisingId('1');
            $androidDevice->setAndroidId('111');
            $androidDevice->setImei('143');
            $content['device_brand'] = 'Sony';
            $content['device_model'] = 'Z3';
            $androidDevice->setDeviceModel($content['device_model']);
            $androidDevice->setDeviceBrand($content['device_brand']);
            $androidDevice->setDevice($device);
            $androidDeviceRepository->save($androidDevice);

            $deviceRepository->save($device);
            $this->deviceId = $device->getId();
            // save application
            $applicationId = 'test_app_'.$j.'.'.$i;
            $application = new Application($applicationId);
            $application->setAppId('test_app_id');
            $application->setAppName('Test App');

            $application->setAppVersion('1.0');
            $application->setPlatform(1);
            $application->setId($applicationId);
            $applicationRepository->save($application);
            $this->application = $application;

            $actionId = 'test_action_'.$j.'.'.$i;
            $action = new Action($actionId);
            $action->setBehaviourId('1');
            $action->setActionType('1');
            $action->setProviderId('1');
            $action->setHappenedAt('110');
            $action->setApplication($this->application);
            $action->setAppId($this->application->getAppId());
            $action->setDevice($em->getReference('Hyper\Domain\Device\Device', $this->deviceId));
            $actionRepository->save($action);
            $this->action = $action;

            $installAction = new InstallAction();
            $action = $this->action;
            $installAction->setAction($action);
            $installAction->setDeviceId($this->deviceId);
            $installAction->setAppId($this->application->getAppId());
            $installAction->setApplicationId($this->application->getId());
            $installAction->setInstalledTime('100');
            $installActionRepository->save($installAction);
            $em->flush();
            if ( $i%100 == 0 && $i>0 ) {
             // code...

             $em->getConnection()->commit();
             $em->getConnection()->beginTransaction();
            }
        }


    }

    public function testHorizontalProcess(){
       for($i=0;$i<50;$i++){
        $workers[$i]=new \Hyper\EventBundle\Service\Vertical($i);
        $workers[$i]->start();
        }
        $AndroidContents = array(

        );
        //select * from android device where idfa in
    }

    public function testCountActionsByAppId(){
        $actionRepo = $this->container->get('action_repository');
        $result = $actionRepo->countByAppId('com.woi.liputan6.android');
        \Doctrine\Common\Util\Debug::dump($result);die;
    }

}
