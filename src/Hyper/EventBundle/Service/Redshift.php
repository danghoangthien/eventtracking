<?php
namespace Hyper\EventBundle\Service;

use Hyper\Domain\Action\Action;

class Redshift
{
    private $container;
    
    public function __construct(\Symfony\Component\DependencyInjection\ContainerInterface $container){
        $this->container = $container;
        
    }
    
    public function storeLogEventToRedshift($providerId,$content,array $metaData = array()){
        //Appsflyer
        //originally use foe Appsflyer now could be used for Hasoffer provider also
        if ($providerId == '1' || $providerId == '2' || $providerId == '3'){
            //Install
            if ($content['event_type'] == 'install'){
                try {
                    $appsflyerInstallService = $this->container->get('appsflyer_install_service');
                    $appsflyerInstallService->init($content,$metaData);
                    $appsflyerInstallService->execute();
                    unset($appsflyerInstallService);
                } catch (\Exception $ex) {
                    $this->log($ex);
                    echo $ex->getMessage(). " ".$ex->getFile(). " ". $ex->getLine() ;
                    
                }
            }
            
            if ($content['event_type'] == 'in-app-event'){
                
                // TODO - standardize event_name,event_value
                //AddToWishlist
                 if ($content['event_name'] =='add_to_wishlist' ||$content['event_name'] =='af_add_to_wishlist') {
                    try {
                        $appsflyerAddToWishlistService = $this->container->get('appsflyer_add_to_wishlist_service');
                        $appsflyerAddToWishlistService->init($content,$metaData);
                        $appsflyerAddToWishlistService->execute();
                        unset($appsflyerAddToWishlistService);
                    } catch (\Exception $ex) {
                        $this->log($ex);
                        echo $ex->getMessage(). " ".$ex->getFile(). " ". $ex->getLine() ;
                    }
                }
                //AddToCart
                if ($content['event_name'] =='add_to_cart' ||$content['event_name'] =='af_add_to_cart') {
                    try {
                        $appsflyerAddToCartService = $this->container->get('appsflyer_add_to_cart_service');
                        $appsflyerAddToCartService->init($content,$metaData);
                        $appsflyerAddToCartService->execute();
                        unset($appsflyerAddToCartService);
                    } catch (\Exception $ex) {
                        $this->log($ex);
                        echo $ex->getMessage(). " ".$ex->getFile(). " ". $ex->getLine() ;
                    }
                }
                //Purchase
                if ($content['event_name'] =='purchase' || $content['event_name'] =='af_purchase') {
                    try {
                        $appsflyerTransactionService = $this->container->get('appsflyer_transaction_service');
                        $appsflyerTransactionService->init($content,$metaData);
                        $appsflyerTransactionService->execute();
                        unset($appsflyerTransactionService);
                    } catch (\Exception $ex) {
                        $this->log($ex);
                        echo $ex->getMessage(). " ".$ex->getFile(). " ". $ex->getLine() ;
                    }
                }
                //Search
                if ($content['event_name'] == 'search' || $content['event_name']  == 'af_search') {
                    try {
                        $appsflyerSearchService = $this->container->get('appsflyer_search_service');
                        $appsflyerSearchService->init($content,$metaData);
                        $appsflyerSearchService->execute();
                        unset($appsflyerSearchService);
                    } catch (\Exception $ex) {
                        $this->log($ex);
                        echo $ex->getMessage(). " ".$ex->getFile(). " ". $ex->getLine() ;
                    }
                }
                //Launch
                if ($content['event_name'] == 'launch') {
                    try {
                        $appsflyerLaunchService = $this->container->get('appsflyer_launch_service');
                        $appsflyerLaunchService->init($content,$metaData);
                        $appsflyerLaunchService->execute();
                        unset($appsflyerLaunchService);
                    } catch (\Exception $ex) {
                        $this->log($ex);
                        echo $ex->getMessage(). " ".$ex->getFile(). " ". $ex->getLine() ;
                    }
                }
                //Misc : ex tutorial completion
                if (
                    $content['event_name'] == 'af_tutorial_completion' || 
                    strtolower($content['event_name']) == 'user registered' ||
                    $content['event_name'] == 'af_add_payment_info' ||
                    $content['event_name'] == 'af_login' ||
                    $content['event_name'] == 'af_complete_registration'
                    ) {
                    
                    try {
                        $appsflyerMiscService = $this->container->get('appsflyer_misc_service');
                        if ($content['event_name'] == 'af_tutorial_completion') {
                            $metaData['behaviour_id'] = Action::BEHAVIOURS['TUTORIAL_BEHAVIOUR_ID'];
                        } elseif (
                            (strtolower($content['event_name']) == 'user registered') ||
                            ($content['event_name'] == 'af_complete_registration')
                        ) {
                            $metaData['behaviour_id'] = Action::BEHAVIOURS['USER_REGISTERED_BEHAVIOUR_ID'];
                        } elseif( $content['event_name'] == 'af_add_payment_info' ) {
                            $metaData['behaviour_id'] = Action::BEHAVIOURS['ADD_PAYMENT_INFO_BEHAVIOUR_ID'];
                        } elseif( $content['event_name'] == 'af_login' ) {
                            $metaData['behaviour_id'] = Action::BEHAVIOURS['LOGIN_BEHAVIOUR_ID'];
                        }
                        
                        $appsflyerMiscService->init($content,$metaData);
                        $appsflyerMiscService->execute();
                        unset($appsflyerMiscService);
                    } catch (\Exception $ex) {
                        $this->log($ex);
                        echo $ex->getMessage(). " ".$ex->getFile(). " ". $ex->getLine() ;
                    }
                }
                //Share 
                if ($content['event_name'] == 'af_share') {
                    try {
                        $appsflyerShareService = $this->container->get('appsflyer_share_content_service');
                        $appsflyerShareService->init($content,$metaData);
                        $appsflyerShareService->execute();
                        unset($appsflyerShareService);
                    } catch (\Exception $ex) {
                        $this->log($ex);
                        echo $ex->getMessage(). " ".$ex->getFile(). " ". $ex->getLine() ;
                    }
                }
                //view 
                if ($content['event_name'] == 'af_content_view') {
                    try {
                        $appsflyerViewService = $this->container->get('appsflyer_view_content_service');
                        $appsflyerViewService->init($content,$metaData);
                        $appsflyerViewService->execute();
                        unset($appsflyerViewService);
                    } catch (\Exception $ex) {
                        $this->log($ex);
                        echo $ex->getMessage(). " ".$ex->getFile(). " ". $ex->getLine() ;
                    }
                }
            }
            
        }
        
        //Hasoffer
    }
    
    private function log(\Exception $ex){
    }
    
}
