<?php
namespace Hyper\EventBundle\Controller\Dashboard\Push;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Hyper\Domain\Authentication\Authentication;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Hyper\EventBundle\Service\EventProcess;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Hyper\Domain\Push\Push;

class PushController extends Controller
{
    /**
    * @param ContainerInterface $container
    */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    
    public function getImagePath()
    {
        return $this->container->getParameter('amazon_s3_images') . "/push_images";
    }
    
    /* /dashboard/push/save */
    public function savePushAction(Request $request)
    {
        /* GET VALUES AND SAVE */
        $this->date = strtotime(date('Y-m-d h:i:s'));
        $this->app     = $request->request->get("user_app");
        $this->title   = $request->request->get("title");
        $this->message = $request->request->get("message");
        $this->token   = $request->request->get("device_token");
        $this->created = $this->date;
        $this->updated = $this->date;
        
        $ext  = "";
        $valid = false;
        //$this->validExt  = array("png", "jpg", "jpeg", "gif", "JPEG", "JPG", "PNG", "GIF");
        $this->validExt  = array("png", "jpg", "jpeg", "JPEG", "JPG", "PNG");

        if(isset($_FILES['csv']['name']) != null)
        {
            $fileName = $_FILES['csv']['name'];
            $fileSize = (int)$_FILES['csv']['size'] / 1048576; // convert to mb
            //$fileSize = (int)$_FILES['csv']['size']; 
            $fileType = $_FILES['csv']['type'];
            $orgImage = $_FILES['csv']['tmp_name'];
            $target = "/tmp/";
            
            /* LIMIT FILE SIZE */
            if($_FILES['csv']['size'] > 1000000)
            {
                $message = "Exceeded the allowed 1mb image size";
                $this->url = $this->generateUrl('dashboard_push');
                $message = serialize($message);
                $url = $this->url."?oe9u7=$message.";
                return $this->redirect($url, 301);
            }
            
            /* RESTRICT TO LANDSCAPE ONLY */
            $dimension = getimagesize($orgImage); // first index is the width and so on
            $width     = $dimension[0];
            $height    = $dimension[1];
            $allowed   = false;
            
            if($width > $height)
            {
                $allowed = true;  //Landscape
            }
            else if($width < $height)
            {
                $allowed = false;  //Portrait
            }
            else
            {
                $allowed = false;  //Square
            }
            
            if($allowed == false)
            {
                $message = "Image dimension not allowed. Please upload a Landscape image.";
                $this->url = $this->generateUrl('dashboard_push');
                $message = serialize($message);
                $url = $this->url."?oe9u7=$message.";
                return $this->redirect($url, 301);
            }
            
            if($fileName != "")
            {   
                $fileName = explode(".",$fileName);
                $new_name = str_replace(" ", "_", $this->title) . "_" . $this->date;
                $fileName = $new_name.".".end($fileName);
            
                $this->extension = explode("/", $fileType);
                
                if (!in_array(end($this->extension), $this->validExt))
                {
                    $valid = false;
                }
                else
                {
                    $ext   = end($this->extension);
                    $valid = true;
                }
                
                $target = $target . basename( $fileName);
            }
            else
            {
                $fileName = "";
            }
            
            if($valid == true)
            {
                if(move_uploaded_file($orgImage, $target))
                {
                    $authController = $this->get('auth.controller');
                    $authController->uploadFromLocal(new File($target), 'testhasoffer', 'push_images', 'image/jpeg');
                    
                    $fs = new Filesystem();
                    $fs->remove($target);
                }  
                else
                {
                    //return new Response(json_encode(array("msg"=> "Error: Unable to move image file!")));
                }
            }
            else
            {
                $message = "Image extension is not allowed. Please upload only png, jpg or jpeg image...";
                $this->url = $this->generateUrl('dashboard_push');
                $message = serialize($message);
                $url = $this->url."?oe9u7=$message.";
                return $this->redirect($url, 301);
            }
        }
        
        if($this->app == "" || $this->title == "" || $this->message == "" || $this->token == "")
        {
            return new Response(json_encode(array("msg"=> "Error: Please check all the data!")));
        }
        else
        {
            /* ADD VALIDATION TO SUPPORT 1 CLIENT = 1 API KEY
             * 2015-12-05 paul.francisc
             */
            
            $authController = $this->get('auth.controller');
            $checkApi = $authController->refreshApiKey();
            if(null == $checkApi)
            {
                $message = "No API Key assigned.";
                $this->url = $this->generateUrl('dashboard_push');
                $message = serialize($message);
                $url = $this->url."?oe9u7=$message.";
                return $this->redirect($url, 301);
            }
        }
        
        //$file_path = str_replace(" ", "_", $this->title) . "_" . $this->date . "." . $ext;
        
        $record = new Push();
        $record->setAppName("$this->app");
        $record->setTitle("$this->title");
        $record->setMessage("$this->message");
        $record->setDeviceToken("$this->token");
        $record->setImgPath("$fileName");
        $record->setCreated($this->created);
        $record->setUpdated($this->updated);
        
        $pushRepo = $this->container->get('push_repository');
        $pushRepo->save($record);
        $pushRepo->completeTransaction();
        
        $id      = $pushRepo->getLastRecord()->getId();
        $file_name = $pushRepo->getLastRecord()->getImgPath();
        $title   = $pushRepo->getLastRecord()->getTitle();
        $message = $pushRepo->getLastRecord()->getMessage();
        
        if($fileName != "")
        {
            $fullImgPath = $this->getImagePath() . "/" . $file_name;
        
            $this->push = array("push_data" => array("action" => "action_notification_push",
                                                "notification" => array("notification_id" => $id, 
									            "title" => $title,
									            "message" => $message,
									            "poster"  => "$fullImgPath")));
        }
        else
        {
            $this->push = array("push_data" => array("action" => "action_notification_push",
                                                "notification" => array("notification_id" => $id, 
								                "title" => $title,
									            "message" => $message)));
        }
									            
        //$this->push = json_encode($this->push);
        $raw       = explode(',', $this->token);
        $rCount    = count($raw);
        $device_id = array();
        
        for($rI = 0; $rI < $rCount; $rI++)
        {
            $device_id[] = $raw[$rI];
        }
        
        $api_key = $authController->refreshApiKey();
        
        /* SEND TO GOOGLE SERVER FOR PUSH NOTIFICATION */
        $this->send = $this->sendGoogleCloudMessage(  $this->push, $device_id, $api_key );
        
        $result = json_decode($this->send, true);
        
        $success = $result['success'];
        $failed  = $result['failure'];
        $google_result = $result['results'];
        
        $status  = 0;
        $dbSuccess = 0;
        $dbFailed = 0;
        
        $test = "";
        
        if($success > 0)
        {
            $status = $success;
            $dbSuccess = $success;
            $test = "success";
        }
        
        if(!isset($failed))
        {
            $status = 0;
            $dbFailed++;
            $test = "isset";
        }
        
        if($failed > 0)
        {
            $status = $failed;
            $dbFailed = $failed;
            $test  = "failed";
        }
        
        $update_time = strtotime(date('Y-m-d h:i:s'));
        
        $this->push = json_encode($this->push);
        
        /* UPDATE TABLE STATUS AND UPDATED DATE */
        $pushRepo->updateJsonFile("$id", "$this->push", $dbSuccess, $dbFailed, json_encode($result), $update_time);
        
        $message = $dbSuccess >= 1 ? "$dbSuccess Success: sent to google server at " . date("h:i:s Y-m-d",$update_time) . " JSON: " . $this->push
        : "Authentication to google server failed: " . $google_result[0]['error'];
        
        $this->url = $this->generateUrl('dashboard_push');
        
        $message = serialize($message);
        $url = $this->url."?oe9u7=$message.";
        
        return $this->redirect($url, 301);
        
        //return new Response(json_encode(array("msg"=> $message)));
    }
    
    public function sendGoogleCloudMessage( $data, $device_ids = null, $apiKey = null )
    {    
        // $apiKey = "AIzaSyAXtkgpMBDByJdGh3sdB3Nv1HSmp_9ZBlg";
    
        $url = 'https://android.googleapis.com/gcm/send';
    
        //------------------------------
        // Set GCM post variables
        // (Device IDs and push payload)
        //------------------------------
    
        $post = array(
                        'registration_ids'  => $device_ids,
                        'data'              => $data,
                        );
    
        $headers = array( 
                            'Authorization: key=' . $apiKey,
                            'Content-Type: application/json'
                        );
      
        $ch = curl_init();
    
        //------------------------------
        // Set URL to GCM endpoint
        //------------------------------
    
        curl_setopt( $ch, CURLOPT_URL, $url );
    
        //------------------------------
        // Set request method to POST
        //------------------------------
    
        curl_setopt( $ch, CURLOPT_POST, true );
    
        //------------------------------
        // Set our custom headers
        //------------------------------
    
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
    
        //------------------------------
        // Get the response back as 
        // string instead of printing it
        //------------------------------
    
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
    
        //------------------------------
        // Set post data as JSON
        //------------------------------
    
        curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $post ) );
    
        //------------------------------
        // Actually send the push!
        //------------------------------
    
        $result = curl_exec( $ch );
    
        //------------------------------
        // Error? Display it!
        //------------------------------
    
        if ( curl_errno( $ch ) )
        {
            echo 'GCM error: ' . curl_error( $ch );
        }
    
        //------------------------------
        // Close curl handle
        //------------------------------
    
        curl_close( $ch );
    
        //------------------------------
        // Debug GCM response
        //------------------------------
    
        return $result;
    }
}