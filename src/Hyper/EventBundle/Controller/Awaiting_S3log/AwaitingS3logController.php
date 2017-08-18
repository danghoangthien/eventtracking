<?php
namespace Hyper\EventBundle\Controller\Awaiting_S3log;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Hyper\EventBundle\Service\EventProcess;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Hyper\Domain\Awaiting_S3log\Awaiting;
use Aws\S3\S3Client;
use \Aws\S3\StreamWrapper;

class AwaitingS3logController extends Controller
{
    /**
    * @param ContainerInterface $container
    */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    
    public $s3;
    
    public function getS3folderList($prefix)
    {
        $this->s3Bucket = 'raw-event-log-v2';
        $this->s3 = $this->connectToS3();
        $s3FolderList = array();
        $objects = $this->s3->listObjects(
            array(
                    'Bucket' => $this->s3Bucket,
                    'Prefix'    => "",
                    'Delimiter' => '/'
            )        
        );
        $objectAsArray = $objects->toArray();
        
        if(isset($objectAsArray['CommonPrefixes'])){
            $FolderByPrefixes = $objectAsArray['CommonPrefixes'];
            
            foreach($FolderByPrefixes as $folderByPrefix) {
                $s3FolderList[] = $folderByPrefix['Prefix'];
                unset($folderByPrefix);
            }
            unset($FolderByPrefixes);
        }
        unset($objects);
        unset($objectAsArray);
        return $s3FolderList;
    }
    
    public function getRawEventFolders()
    {
        return array("asianpoker",
        "bravefrontier",
        "bukalapak",
        "chainchronicle",
        "liputan6",
        "raiderquests",
        "tiket",
        "wakfu",
        "yogrt");
    }
    
    /* /Awaiting_S3log/AwaitingS3log */
    public function checkFileExistsAction(Request $request, $date = null, $bucket_name = null)
    {
        $date = "2016/01/05";
        $bucket_name = "raw-event-log-v2";
        $ex = explode("/", $date);
        $year = $ex[0];
        $month = $ex[1];
        $day = $ex[2];
        
        $this->s3 = $this->connectToS3();
        
        // $folderList = $this->s3->listBuckets(array());
        // foreach ($folderList['Buckets'] as $bucket) {
        //     echo $bucket['Name'], PHP_EOL ."<br />";
        // }
        $folderNames = $this->getRawEventFolders();
        $filename = array();
        $files = array();
        $max_keys = 100;
        $e = 0;
                
        $response = array();
        foreach($folderNames as $folder)
        {
            $response[] = $this->s3->listObjects(array('Bucket' => $bucket_name, 'MaxKeys' => $max_keys, 'Prefix' => "$folder/$year/$month/$day"));
            $files[] = $response[$e]->getPath('Contents');
            $e++;
        }
        
        $count = count($response);
        
        // $st = 1;
        // $nt = 1;
        // $andre = array();
        // $cor = count($files);
        // for($tt = 0; $tt < $cor; $tt++)
        // {
        //     if(is_array($files[$tt]))
        //     {
        //         echo $tt . "array <br />";
        //     }
        //     else
        //     {
        //         $andre[] = $files[$tt];
        //         echo $tt . "<br />";
        //     }
        // }
        
        foreach($files as $ff)
        {
            if(is_array($ff))
            {
                foreach($ff as $fff)
                {
                    $filename[] = $fff['Key'];
                }
            }
            else
            {
                $filename[] = $ff['Key'];
            }
        }
        
        /*
        $response = $this->s3->listObjects(array('Bucket' => $bucket_name, 'MaxKeys' => 1000, 'Prefix' => "asianpoker/$year/$month/$day"));
        $files = $response->getPath('Contents');
        $filename = array();
        foreach ($files as $file) {
            $filename[] = $file['Key'];
        }
        */
        
        $removeEmpty = array_filter($filename);
        $removeEmpty = array_values($removeEmpty);
        $s3FileCount = count($removeEmpty);
        
        $path = "'" .implode("','", $removeEmpty) . "'";
        
        $query = "SELECT s3_log_file, created FROM actions WHERE s3_log_file IN($path);";
        
        // print $sql; die;
        
        $conn = $this->get('doctrine.dbal.pgsql_connection');                                    
        $sql  = $conn->prepare($query);          
        $sql->execute();

        $data = array();

        for($x = 0; $row = $sql->fetch(); $x++) 
        {
            $data[] = $row;
        }
        
        $awaitRepo = $this->container->get('awaiting_repository');
        $this->date = strtotime(date('Y-m-d h:i:s'));
        
        for($i = 0; $i < $s3FileCount; $i++)
        {
            // echo $removeEmpty[$i] . " " . $i . " <br />";
            if(in_array($removeEmpty[$i], $data))
            {
                echo "path existing <br />";
                continue;
            }
            else
            {
                $query_path = $removeEmpty[$i];
                $query2 = "SELECT s3_log_file FROM awaiting_s3_log WHERE s3_log_file = '$query_path'";
                $sql2 = $conn->prepare($query2);
                $sql2->execute();
                
                $data2 = array();

                for($p = 0; $rows = $sql2->fetch(); $p++) 
                {
                    $data2[] = $rows;
                }
                
                if(count($data2) == 0)
                {
                    echo "not exisiting " . $removeEmpty[$i] ." INSERTING TO AWAITING TABLE <br />";
                
                    $s3LogFile = $removeEmpty[$i];
                    $ex = explode("/", $removeEmpty[$i]);
                    $app_id = $ex[0];
                    $app_folder = $ex[0];
                    
                    $awaiting = new Awaiting();
                    $awaiting->setS3LogFile("$s3LogFile");
                    $awaiting->setAppId("$app_id");
                    $awaiting->setS3AppFolder("$app_folder");
                    $awaiting->setEventType(1);
                    $awaiting->setStatus(1);
                    $awaiting->setCreated($this->date);
                    
                    $awaitRepo->save($awaiting);
                    $awaitRepo->completeTransaction();
                }
                else
                {
                    echo "Exisiting in awaiting table " . $removeEmpty[$i] . "<br />";
                }
            }
        }
        
        print "<pre>";
        print count($removeEmpty) . "<br />";
        // print_r(array_unique($filename));
        // print_r($path);
        // print_r($empty);
        print "</pre>";
        die;
    }
    
    /*
    public function checkFileExistsAction(Request $request, $date = null, $bucket_name = null)
    {
        $date = "2016/01/19";
        $bucket_name = "raw-event-log-v2";
        
        $conn = $this->get('doctrine.dbal.pgsql_connection');                                    
        $sql  = $conn->prepare("SELECT s3_log_file, created FROM actions WHERE s3_log_file LIKE '%$date%' LIMIT 1000;");          
        $sql->execute();

        $data = array();

        for($x = 0; $row = $sql->fetch(); $x++) 
        {
            $data[] = $row;
        }
        
        $count = count($data);
        $this->s3 = $this->connectToS3();
        
        $awaitRepo = $this->container->get('awaiting_repository');
        $this->date = strtotime(date('Y-m-d h:i:s'));
        $awaiting = new Awaiting();
        $o = 0;
        
        for($i = 0; $i < $count; $i++)
        {
            $record = $data[$i];
            $result = $this->s3->doesObjectExist( $bucket_name, $record['s3_log_file']);
            if($result == false)
            {
                $awaiting->setS3LogFile($record['s3_log_file']);
                $awaiting->setAppId("Bungol");
                $awaiting->setS3AppFolder("Folder");
                $awaiting->setEventType(1);
                $awaiting->setStatus(1);
                $awaiting->setCreated($this->date);
                
                $awaitRepo->save($awaiting);
                $awaitRepo->completeTransaction();
                // print $record['s3_log_file'] . "<br />";
                $o++;
            }
        }
                
        if($o == 0)
        {
            print "No missing file";
        }
        die;
    }
    */
    
    public function connectToS3()
    {
        $this->s3SecurityKey = $this->container->getParameter('amazon_aws_key');
        $this->s3SecuritySecret = $this->container->getParameter('amazon_aws_secret_key');
        $this->s3Region = $this->container->getParameter('amazon_s3_region');
        
        $credentials = new \Aws\Credentials\Credentials($this->s3SecurityKey, $this->s3SecuritySecret);
        $options = [
            'region'            => $this->s3Region,
            'version'           => '2006-03-01',
            'signature_version' => 'v4',
            'credentials' => $credentials
        ];
       
        return new  \Aws\S3\S3Client(
            $options
        );
    }
    
    public function getS3Path()
    {
        
    }
}