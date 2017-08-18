<?php
namespace Hyper\EventBundle\Service;

use Hyper\Domain\Setting\Setting;

class S3ToGlacier
{
    private $container;

    private $s3Region;
    private $s3Bucket;
    private $s3SecurityKey;
    private $s3SecuritySecret;

    private $s3;

    private $tempFolder;
    private $s3BucketArchive;

    public function __construct(\Symfony\Component\DependencyInjection\ContainerInterface $container){
        $this->container = $container;

    }

    public function init(){
        $this->s3Region = $this->container->getParameter('amazon_s3_region');
        $this->s3Bucket = 'raw-event-log-v2';
        $this->s3BucketArchive = "raw-event-log-archive";
        $this->s3SecurityKey = $this->container->getParameter('amazon_aws_key');
        $this->s3SecuritySecret = $this->container->getParameter('amazon_aws_secret_key');
        $this->tempFolder = '/var/www/html/projects/event_tracking/web/s3_to_glacier';
        $this->s3 = $this->connectToS3();
    }

    public function connectToS3(){
        $credentials = new \Aws\Credentials\Credentials($this->s3SecurityKey, $this->s3SecuritySecret);
        $options = [
            'region'            => $this->s3Region,
            'version'           => '2006-03-01',
            'signature_version' => 'v4',
            'credentials' => $credentials
        ];
        return new \Aws\S3\S3Client(
            $options
        );
    }
    public function process($app, $from, $to, $delete) {
        //$appFolderList = $this->getS3folderList("");
        $appFolderList = explode(",", $app);
        list($fromYear, $fromMonth) = split('[-]', $from);
        list($toYear, $toMonth) = split('[-]', $to);
        //loop by app_title

        foreach ($appFolderList as $appTitle) {
            //loop by year
            $yearList = $this->getS3folderList($appTitle . '/');
            foreach ($yearList as $year) {
                list($t, $y) = split('[/]', $year);
                if($y > $toYear || $y < $fromYear || ($app != null && $app != $t)){
                    continue;
                }
                //loop by month
                $monthList = $this->getS3folderList($year);
                foreach($monthList as $month){
                    //loop by date
                    $dateList = $this->getS3folderList($month);
                    foreach ($dateList as $date) {
                        //loop by hour
                        $hourList = $this->getS3folderList($date);
                        foreach($hourList as $hour){
                            list($t, $y, $m, $d, $h) = split('[/]', $hour);
                            $tmpMonth = $y . '-' . $m;
                            if(strtotime($tmpMonth) > strtotime($to) || strtotime($tmpMonth) < strtotime($from)){
                                continue;
                            }
                            if($app != null && $app != $t){
                                continue;
                            }
                            $fileName = $h . ".tar.gz";
                            $tmpDir = $this->tempFolder."/".$hour;
                            echo "downloading $hour" ."-->" . date('d-m-Y H:i:s') ."\n";
                            $this->s3->downloadBucket($tmpDir, $this->s3Bucket, $hour, []);
                            echo "processing $hour" . "-->". date('d-m-Y H:i:s') ."\n";
                            $gzipFolder = $this->tempFolder . "/" . $t . "/" . $y . "/" . $m . "/" . $d . "/";
                            exec("cd $gzipFolder && tar -zcvf $fileName ./$h/*");
                            $key = $t . "/" . $y . "/" . $m . "/" . $d . "/" . $fileName;
                            $this->s3->putObject([
                                'Bucket' => $this->s3BucketArchive,
                                'Key' => $key,
                                'SourceFile' =>$gzipFolder . $fileName,
                            ]);//put object to s3
                            exec("rm -rf {$this->tempFolder}/*");//remove temp folder
                            if($delete == 'delete' && $this->s3->doesObjectExist($this->s3BucketArchive, $key)){//delete object
                                echo "deleting  $hour". "-->" . date('d-m-Y H:i:s') . "\n";
                                exec("aws s3 rm s3://$this->s3Bucket/$hour --recursive");
                                //$this->s3->deleteMatchingObjects($this->s3Bucket, $hour);
                            }
                        }
                    }
                }
            }
        }

        echo "finish processing"."\n";
        return;

    }

    public function getS3folderList($prefix = ""){

        $objects = $this->s3->listObjects([
                    'Bucket' => $this->s3Bucket,
                    'Prefix'    => $prefix,
                    'Delimiter' => '/',
                ]
        );
        $objectAsArray = $objects->toArray();
        if(!isset($objectAsArray['CommonPrefixes'])){
            return [];
        }
        return array_column($objectAsArray['CommonPrefixes'], 'Prefix');
    }



    private function log(\Exception $ex){

    }

}
