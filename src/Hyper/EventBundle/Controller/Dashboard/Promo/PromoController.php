<?php
namespace Hyper\EventBundle\Controller\Dashboard\Promo;

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
use Hyper\Domain\Promo\Promo;

class PromoController extends Controller
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
    
    /* dashboard/promo/save */
    public function savePromoAction(Request $request)
    {
        $this->validExt  = array("png", "jpg", "jpeg", "JPEG", "JPG", "PNG");
        $this->date = strtotime(date('Y-m-d h:i:s'));
        $this->application = $request->request->get('user_type');
        $this->placement   = $request->request->get('placement_name');
        $this->title       = $request->request->get('title');
        $this->orientation = $request->request->get('BannerOrientation');
        $this->token       = $request->request->get("device_token");
        $this->landing_page = $request->request->get('landing_page');
        $this->form_url    = $request->request->get('url');
        $this->created     = $this->date;
        $this->updated     = $this->date;
        $this->frequency   = (int)$request->get('frequency');
        $this->from        = strtotime($request->get('DateFrom') . "00:00:00");
        $this->to          = strtotime($request->get('DateTo') . "23:59:59");
        // $this->from        = date('F j, Y h:i:s A e', strtotime($request->get('DateFrom')));
        // $this->to          = date('F j, Y h:i:s A e', strtotime($request->get('DateTo')));
        
        // print date('F j, Y H:i:s A e', $this->to); die;
        
        if($this->from > $this->to)
        {
            $message = "Date From is greater than Date To";
            $this->url = $this->generateUrl('dashboard_banner');
            $message = serialize($message);
            $url = $this->url."?oe9u7=$message.";
            return $this->redirect($url, 301);
        }
        
        $this->from = date('F j, Y H:i:s A e', $this->from);
        $this->to   = date('F j, Y H:i:s A e', $this->to);
        
        if(isset($_FILES['fileToUpload']['name']) != null)
        {
            $fileName = $_FILES['fileToUpload']['name'];
            $fileSize = (int)$_FILES['fileToUpload']['size'] / 1048576; // convert to mb
            //$fileSize = (int)$_FILES['csv']['size']; 
            $fileType = $_FILES['fileToUpload']['type'];
            $orgImage = $_FILES['fileToUpload']['tmp_name'];
            $target = "/tmp/";
            $html_target = "/var/www/html/projects/event_tracking/web/tmp_html/";
            
            /* LIMIT FILE SIZE */
            if($_FILES['fileToUpload']['size'] > 1000000)
            {
                $message = "Exceeded the allowed 1mb image size";
                $this->url = $this->generateUrl('dashboard_banner');
                $message = serialize($message);
                $url = $this->url."?oe9u7=$message.";
                return $this->redirect($url, 301);
            }
            
            /* RESTRICT TO LANDSCAPE ONLY */
            $dimension = getimagesize($orgImage); // first index is the width and so on
            $width     = $dimension[0];
            $height    = $dimension[1];
            
            if($this->orientation == "" || $this->orientation == null)
            {
                $message = "No image orientation defined";
                $this->url = $this->generateUrl('dashboard_banner');
                $message = serialize($message);
                $url = $this->url."?oe9u7=$message.";
                return $this->redirect($url, 301);
            }
            else if($this->orientation == "Portrait")
            {
                if($width > $height)
                {
                    $message = "Wrong dimension for a Portrait Orientation";
                    $this->url = $this->generateUrl('dashboard_banner');
                    $message = serialize($message);
                    $url = $this->url."?oe9u7=$message.";
                    return $this->redirect($url, 301);
                }
                else
                {
                    $tag = "Portrait";
                }
            }
            else if($this->orientation == "Landscape")
            {
                if($height > $width)
                {
                    $message = "Wrong dimension for a Landscape Orientation";
                    $this->url = $this->generateUrl('dashboard_banner');
                    $message = serialize($message);
                    $url = $this->url."?oe9u7=$message.";
                    return $this->redirect($url, 301);
                }
                else
                {
                    $tag = "Landscape";
                }
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
                    $authController->uploadFromLocal(new File($target), 'testhasoffer', 'promo_images', 'image/jpeg');
                    
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
                $this->url = $this->generateUrl('dashboard_banner');
                $message = serialize($message);
                $url = $this->url."?oe9u7=$message.";
                return $this->redirect($url, 301);
            }
            
            /* CREATE HTML FILE FROM THE IMAGE */
            $html_name = explode(".", $fileName);
            $html_name = $html_name[0] . ".html";
            $full_html = $html_target . $html_name;
            $handle = fopen($full_html, 'w');
            if(file_exists($full_html))
            {
                $promo_image = $this->getImage() . "/" . $fileName;
                
                if($tag == "Portrait")
                {
                    $div = '<div style="width: 3em; height: 6em;">';
                    $h   = "6em";
                    $w   = "3em";
                }
                else if($tag == "Landscape")
                {
                    $div = '<div style="width: 6em; height: 3em;">';
                    $h   = "3em";
                    $w   = "6em";
                }
                
                $onC = '"HGTrackingKit.bannerClick('. "'$this->form_url '".');"';
                
                $data = "<!DOCTYPE html>
                <html>
                    <head>
                        <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
            
                        <script type='text/javascript' src='//code.jquery.com/jquery-1.12.0.min.js'></script>
                        <script>
                            var HGTrackingKit = HGTrackingKit || {};

                			HGTrackingKit.dismissBanner = function() {
                				alert('HGTrackingKit.dismissBanner');
                			}
                			
                			HGTrackingKit.openUri = function(uri) {
                				alert('HGTrackingKit.openUri('+uri+')');
                			}
                            
                            HGTrackingKit.bannerClick = function(clickUrl) {
                				alert('HGTrackingKit.bannerClick(' + clickUrl + ')');
                			}    			
                            
                            $(document).ready(function()
                            {      
                			
                            });
                        </script>
                        <style>
            			.banner {
            				margin: auto;
            				position: absolute;
            				top: 0;
            				bottom: 0;
            				left: 0;
            				right: 0;
            			}
            
            			img.banner {
            				max-height:100%;
            				max-width:100%;
            			}
            
            			.close-button {
                	        background-color: black;
                	        color: white;
                	        border-color: white;
                	        border-width: 2px;
                	        border-radius: 100px;
                	        width: 40px;
                	        height: 40px;
                	        font-size: 100%;
            			    position: absolute;
            			    top: 4px;
            			    right: 4px;
                	        text-align: center;
                	        z-index: 1000;
                	    }
            		</style>
                    </head>
                    <body>
                        <input type='button'
                			onclick='HGTrackingKit.dismissBanner()'
                			value='X'
                			class='close-button'/>
                
                		 <img class='banner' src='".$promo_image."' onclick=".$onC.">
                    </body>
                </html>";
                fwrite($handle, $data);
                fclose($handle);
                $authController->uploadFromLocal(new File($full_html), 'testhasoffer', 'promo_html', 'text/html');
                                
                $fs->remove($full_html);
            }
            else
            {
                $message = "Unable to create file";
                $this->url = $this->generateUrl('dashboard_banner');
                $message = serialize($message);
                $url = $this->url."?oe9u7=$message.";
                return $this->redirect($url, 301);
            }
        }
        else
        {
            return new Response("TEST FOR IMAGE");
        }
        
        if(($this->application == "" || $this->application == "-1") || ($this->placement == "-1" || $this->placement == "-2") || $this->title == "" 
        || ($this->landing_page == "-1" || $this->landing_page == "-2") ||  $this->form_url == "" || $this->frequency == "" 
        || $this->from == "" || $this->to == "")
        {
            $message = "An invalid data sent";
            $this->url = $this->generateUrl('dashboard_banner');
            $message = serialize($message);
            $url = $this->url."?oe9u7=$message.";
            return $this->redirect($url, 301);
        }
        
        $promo = new Promo();
        $promo->setAppName($this->application);
        $promo->setPlacementName($this->placement);
        $promo->setCampaignTitle($this->title);
        $promo->setOrientation($this->orientation);
        $promo->setImgPath($fileName);
        $promo->setLandingPage($this->landing_page);
        $promo->setUrl($this->form_url);
        $promo->setHtmlPath($html_name);
        $promo->setDateFrom($this->from);
        $promo->setDateTo($this->to);
        $promo->setFrequency($this->frequency);
        $promo->setCreated($this->created);
        $promo->setUpdated($this->updated);
        
        $promoRepo = $this->container->get('promo_repository');
        $promoRepo->save($promo);
        $promoRepo->completeTransaction();
        
        $id   = $promoRepo->getLastRecord()->getId();
        $html = $promoRepo->getLastRecord()->getHtmlPath();
        $fullHtmlPath = $this->getGeneratedHtml() . "/" . $html;
        
        $this->push = array("push_data" => array("url" => "$fullHtmlPath",
                                                "action" => "action_banner_push", 
									            "banner_name" => "$this->title",
									            "placement_name"  => "$this->placement",
									            "orientation" => "$this->orientation",
									            "frequency" => $this->frequency,
									            "start_time" => $this->from,
									            "end_time" => $this->to));

        /* SEND TO GOOGLE SERVER FOR PROMO NOTIFICATION */
        $api_key = $authController->refreshApiKey();
        
        $raw       = explode(',', $this->token);
        $rCount    = count($raw);
        $device_id = array();
        
        for($rI = 0; $rI < $rCount; $rI++)
        {
            $device_id[] = $raw[$rI];
        }
        
        $pushController = $this->get('push.controller');
        
        $this->send = $pushController->sendGoogleCloudMessage(  $this->push, $device_id, $api_key );
        
        $result = json_decode($this->send, true);
        
        $success = $result['success'];
        $failed  = $result['failure'];
        $google_result = $result['results'];
        
        $status  = 0;
        $dbSuccess = 0;
        $dbFailed = 0;
        
        if($success > 0)
        {
            $status = $success;
            $dbSuccess = $success;
        }
        
        if(!isset($failed))
        {
            $status = 0;
            $dbFailed++;
        }
        
        if($failed > 0)
        {
            $status = $failed;
            $dbFailed = $failed;
        }
        
        $update_time = strtotime(date('Y-m-d h:i:s'));
        
        $this->push = json_encode($this->push);
        
        /* UPDATE TABLE STATUS AND UPDATED DATE */
        $promoRepo->updateJsonFile("$id", "$this->push", $dbSuccess, $dbFailed, json_encode($result), $update_time);
        
        $message = $dbSuccess >= 1 ? "$dbSuccess Success: sent to google server at " . date("h:i:s Y-m-d",$update_time) . " JSON: " . $this->push
        : "Authentication to google server failed: " . $google_result[0]['error'];
        
        $this->url = $this->generateUrl('dashboard_banner');
        
        $message = serialize($message);
        $url = $this->url."?oe9u7=$message.";
        
        return $this->redirect($url, 301);									            
    }
    
    public function getImage()
    {
        return $this->container->getParameter('amazon_s3_images') . "/promo_images";
    }
    
    public function getGeneratedHtml()
    {
        return $this->container->getParameter('amazon_s3_images') . "/promo_html";
    }
    
     function refreshImageAction()
    {
        $username = $this->getLoggedAuthenticationUsername();
        
        if($username == "" || $username == null)
        {
            return $this->render('authentication/index_user.html.twig');
        }
        
        $auth = $this->container->get('authentication_repository');
        
        $result = $auth->findbyCriteria("username", $username);
        
        return $result->getImgPath();
    }  
    
    /* /dashboard/promo/lookup */
    public function sendPromoBannerLookUp(Request $request)
    {
        $app_name = $request->get('app_id');
        $placement_name = $request->get('placement_name');
        
        $promoRepo = $this->container->get('promo_repository');
        $record = $promoRepo->findByAppPlacement("$app_name", "$placement_name");
            
        $id   = $record->getId();
        $html = $record->getHtmlPath();
        $fullHtmlPath      = $this->getGeneratedHtml() . "/" . $html;
        $this->title       = $record->getCampaignTitle();
        $this->placement   = $record->getPlacementName();
        $this->orientation = $record->getOrientation();
        $this->frequency   = $record->getFrequency();
        $this->from        = $record->getDateFrom();
        $this->to          = $record->getDateTo();
        
        // $this->push = array("push_data" => array("url" => "$fullHtmlPath",
        //                                         "action" => "action_banner_push", 
								// 	            "banner_name" => "$this->title",
								// 	            "placement_name"  => "$this->placement",
								// 	            "orientation" => "$this->orientation",
								// 	            "frequency" => $this->frequency,
								// 	            "start_time" => $this->from,
								// 	            "end_time" => $this->to));
	   // print "<pre>";
    //     print_r($this->push);
    //     print "</pre>";
    //     die;
        
		$this->push = '
        {
            "push_data":{
                "url": "'.$fullHtmlPath.'",
                "action": "action_banner_push",
                "banner_name": "'.$this->title.'",
                "placement_name": "'.$this->placement.'",
                "orientation": "'.$this->orientation.'",
                "frequency": '.$this->frequency.',
                "start_time": "'.$this->from.'",
                "end_time": "'.$this->to.'"
            }
        }';
		
        echo($this->push); die;
        // return new Response(json_encode(array($this->push)));
    }
}