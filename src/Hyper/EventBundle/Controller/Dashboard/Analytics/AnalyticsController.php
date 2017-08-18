<?php
namespace Hyper\EventBundle\Controller\Dashboard\Analytics;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// entities
use Hyper\Domain\Analytics\Metadata;
// Doctrine
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\DBAL\Driver\PDOStatement;

class AnalyticsController extends Controller
{

    /**
    * @param ContainerInterface $container
    */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    
    public function getByKeyAction(Request $request)
    {
        $request_identifier = $request->get('identifier');
        $data = null;
        $key = $request->get('key');
        $metadataRepository = $this->get('analytics_metadata_repository');
        $metadata = $metadataRepository->findOneByKey($key);
        //echo $key."<br/>";
        //var_dump($metadata);
        if ($metadata instanceof Metadata) {
           // echo "Instance of metadata <hr/>";
            $data = $metadata->getMetadata();
            $data = json_decode($data,true);
        } else {
            $data = null;
        }
        
        // print "Original: <br />";
        // print_r(json_encode(array("data" => $data)));
        // print "<br /><br />";
        // print "New: <br />";
        
        if(null != $data)
        {
            $count = count($data);
            
            if($request_identifier == 'client')
            {
                $client_id = $request->get('id');
                
                $new_data = $this->getMetadataByClientId(array($client_id), $data, $count, 'app_name');
            
                $response = new Response(
                            json_encode(
                                array(
                                    'data' => $new_data
                                )
                            )
                        );
                $response->headers->set('Access-Control-Allow-Origin', '*');
                return $response;
            }
            else if($request_identifier == "app")
            {
                $app_id = $request->get('id');
                
                $new_data = $this->getMetadataByAppId(array($app_id), $data, $count, 'appId');
                
                $response = new Response(
                            json_encode(
                                array(
                                    'data' => $new_data
                                )
                            )
                        );
                $response->headers->set('Access-Control-Allow-Origin', '*');
                return $response;
            }
            else if($request_identifier == null || $request_identifier == "")
            {
                $response = new Response(
                            json_encode(
                                array(
                                    'data' => $data
                                )
                            )
                        );
                $response->headers->set('Access-Control-Allow-Origin', '*');
                return $response;
            }
        }
        else
        {
            $response = new Response(
                        json_encode(
                            array(
                                'data' => $data
                            )
                        )
                    );
            $response->headers->set('Access-Control-Allow-Origin', '*');
            return $response;
        }
    }
    
    public function executeCommandMetadata($key)
    {
        $rootDir = $this->container->get('kernel')->getRootDir() . '/../';
        $cmd = "cd {$rootDir}";
        $cmd .= " && php app/console analytics_metadata:generate --env=prod --key={$key}";
        $cmd .= " >> app/logs/analytics_metadata_{$key}_$(date +\"%Y_%m_%d\").log 2>&1 &";
        exec($cmd);
    }
    
    public function generateMetadataValues($key)
    {
        $metadataRepository = $this->get('analytics_metadata_repository');
        $metadata = $metadataRepository->findOneByKey($key);
        if (!$metadata) {
            throw new \Exception("Missing {$key} key params in analytics_metadata table.");
        }
        if (!$metadata->getQuery()) {
            throw new \Exception("Missing query of {$key} in analytics_metadata table.");
        }
        $metadata->setIsProcessing(1);
        $metadataRepository->save($metadata);
        $metadataRepository->completeTransaction();
        echo "Processing : ".$metadata->getKey(). "\n";
        $conn = $this->get('doctrine.dbal.pgsql_connection');
        $sql  = $conn->prepare($metadata->getQuery());                      
        $sql->execute();
        for($x = 0; $row = $sql->fetch(); $x++) 
        {
            $data[] = $row;
        }
        $jsonMetadata = json_encode($data);
        $metadata->setMetadata($jsonMetadata);
        $metadata->setIsProcessing(0);
        $metadataRepository->save($metadata);
        $metadataRepository->completeTransaction();
        echo "Done : ".$metadata->getKey(). "\n";
    }
    
    public function generateMetadataRows()
    {   //die;
        
        $metadataRepository = $this->get('analytics_metadata_repository');
        /*
        //profiles_breakdown_by_app_source
        $metadata = new Metadata();
        $metadata->setKey('profiles_breakdown_by_app_source');
        $query = "SELECT
                count(distinct(actions.device_id)) as device_id,
                case 
                when applications.app_name IN ('Chain Chronicle - Line Defense RPG','Chain Chronicle – RPG') then 'Chain Chronicle'
                when applications.app_name = 'Liputan6.com' then 'Liputan6'
                when applications.app_name = 'Asian Poker - Big Two' then 'Asian Poker'
                when applications.app_name = 'Bukalapak - Jual Beli Online' then 'Bukalapak'
                when applications.app_name= 'Yogrt: Meet Friends Nearby' then 'Yogrt'
                else applications.app_name
                end as app_name
                FROM
                applications 
                INNER JOIN actions actions ON applications.app_id = actions.app_id
                WHERE actions.app_id is not null AND app_name != '[App Name Comes Here]'
                GROUP BY app_name";
        $metadata->setQuery($query);
        $metadata->setDataSource(Metadata::DATA_SOURCE_JSON);
        $metadata->setMetadata('');
        $metadataRepository->save($metadata);
        
        //profiles_breakdown_by_platform
        $metadata = new Metadata();
        $metadata->setKey('profiles_breakdown_by_platform');
        $query = "SELECT
                count(distinct(actions.device_id)) as device_id,
                case 
                         when devices.platform =  '1' then 'iOS'
                         when devices.platform =  '2' then 'Android'
                end as platform
                FROM
                     devices 
                INNER JOIN actions actions ON devices.id = actions.device_id
                GROUP BY devices.platform";
        $metadata->setQuery($query);
        $metadata->setDataSource(Metadata::DATA_SOURCE_JSON);
        $metadata->setMetadata('');
        $metadataRepository->save($metadata);
        
        //profiles_breakdown_by_sourceplatform
        $metadata = new Metadata();
        $metadata->setKey('profiles_breakdown_by_sourceplatform');
        $query = "SELECT
             count(distinct(actions.device_id)) as device_id,
             case
                             when devices.platform = '1' then 'iOS'
                        when devices.platform = '2' then 'Android'
                        end as platform,
             case 
             when applications.app_name IN ('Chain Chronicle - Line Defense RPG','Chain Chronicle – RPG') then 'Chain Chronicle'
             when applications.app_name = 'Liputan6.com' then 'Liputan6'
             when applications.app_name = 'Asian Poker - Big Two' then 'Asian Poker'
             when applications.app_name = 'Bukalapak - Jual Beli Online' then 'Bukalapak'
             when applications.app_name = 'Yogrt: Meet Friends Nearby' then 'Yogrt'
             else applications.app_name
             end as app_name
            FROM
                 applications 
            INNER JOIN actions ON applications.app_id = actions.app_id
            INNER JOIN devices ON actions.device_id = devices.id
            WHERE actions.app_id is not null AND app_name != '[App Name Comes Here]'
            GROUP BY app_name, devices.platform";
        $metadata->setQuery($query);
        $metadata->setDataSource(Metadata::DATA_SOURCE_JSON);
        $metadata->setMetadata('');
        $metadataRepository->save($metadata);
        
        
        //profiles_breakdown_by_event_yogrt
        $metadata = new Metadata();
        $metadata->setKey('profiles_breakdown_by_event_yogrt');
        $query = "SELECT
                    count(distinct(actions.device_id)) as device_id,
                    actions.app_id,
                    case
                             when actions.behaviour_id = '1' then 'install'
                             when actions.behaviour_id = '10' then 'register'
                             end as event_name,
                    case
                                when devices.platform = '1' then 'iOS'
                                when devices.platform = '2' then 'Android'
                                end as platform
                FROM
                    applications 
                INNER JOIN actions ON applications.app_id = actions.app_id
                INNER JOIN devices ON actions.device_id = devices.id
                WHERE actions.app_id IN ('com.akasanet.yogrt.android','id950197859')
                GROUP BY devices.platform, actions.app_id, event_name
                ";
        $metadata->setQuery($query);
        $metadata->setDataSource(Metadata::DATA_SOURCE_JSON);
        $metadata->setMetadata('');
        $metadataRepository->save($metadata);
        */
        //profiles_breakdown_by_country
        $metadata = new Metadata();
        $metadata->setKey('profiles_breakdown_by_eventplatform');
        $query ="SELECT
    device_id,
    count(device_id) as pcount,
    app_id,
    date(TIMESTAMP 'epoch' + happened_at * INTERVAL '1 Second ') as event_date,
    behaviour_id    
FROM
     actions
WHERE  date(TIMESTAMP 'epoch' + happened_at * INTERVAL '1 Second ') > (current_date - integer '7')
GROUP BY app_id, behaviour_id, event_date, device_id
                ";
        $metadata->setQuery($query);
        $metadata->setDataSource(Metadata::DATA_SOURCE_JSON);
        $metadata->setMetadata('');
        $metadataRepository->save($metadata);
        $metadataRepository->completeTransaction();
        /*
        $metadata = new Metadata();
        $metadata->setKey('profiles_breakdown_by_country_raidersquest');
        $query ="SELECT 
            count(distinct(id)) as device_id, 
            devices.country_code AS country_name,
            CASE
                  when devices.platform = '1' then 'iOS'
                  when devices.platform = '2' then 'Android'
                  end as platform
            FROM devices 
            WHERE id  in (select device_id from actions where app_id = 'id1049249612')  
            GROUP BY devices.platform, devices.country_code ";
        $metadata->setQuery($query);
        $metadata->setDataSource(Metadata::DATA_SOURCE_JSON);
        $metadata->setMetadata('');
        $metadataRepository->save($metadata);
        $metadataRepository->completeTransaction();
        /*
        //profiles_breakdown_by_country
        $metadata = $metadataRepository->findOneByKey('profiles_breakdown_by_country');

        $query ="
select count(distinct(id)) as device_id, 
			devices.country_code AS country_name,
      case
                 when devices.platform = '1' then 'iOS'
            when devices.platform = '2' then 'Android'
            end as platform
       from devices 
       where id  in (select device_id from actions)  
       GROUP BY devices.platform,devices.country_code;
                ";
        $metadata->setQuery($query);
        $metadataRepository->save($metadata);
        $metadataRepository->completeTransaction();
        */
    }
    
    public function getMetadataByClientId($clientIds = array(), $metadata, $count, $field_name)
    {
        $client_repo = $this->get('client_repository');
            
        $client_name = $client_repo->getClientByIds(array($clientIds), "client_name");
        
        $cnt = count($client_name);
        
        $arr = array();
        
        for($v = 0; $v < $cnt; $v++)
        {
            $arr[] = $client_name[$v];
        }
        
        $new_data = array();
        
        for($i = 0; $i < $count; $i++)
        {
            // echo $metadata[$i][$field_name] . "<br />";
            if(in_array($metadata[$i][$field_name], $arr))
            {
                $new_data[] = $metadata[$i];
            }
        }
        
        return $new_data;
    }
    
    public function getMetadataByAppId($appIds = array(), $metadata, $count, $field_name)
    {
        $app_repo = $this->get('application_repository');
            
        $applications = $app_repo->getAppByIds(array($appIds), $field_name);
        
        $cnt = count($applications);
        
        $arr = array();
        
        for($v = 0; $v < $cnt; $v++)
        {
            $arr[] = $applications[$v];
        }
        
        $new_data = array();
        
        for($i = 0; $i < $count; $i++)
        {
            if(in_array($metadata[$i]['app_name'], $arr))
            {
                $new_data[] = $metadata[$i];
            }
        }
        
        return $new_data;
    }
    
    /* /dashboard/analytics/save */
    public function saveAnalyticsAction(Request $request)
    {
        $this->key = $request->get('key');
        $this->query = $request->get('query');
        $this->meta = $request->get('meta');
        $this->date = strtotime(date('Y-m-d h:i:s'));
        $this->created = $this->date;
        
        if("" == $this->key || "" == $this->query)
        {
            $message = "Null or invalid data sent";
            $this->url = $this->generateUrl('dashboard_analytics_display');
            $message = serialize($message);
            $url = $this->url."?qwerty=$message.";
            return $this->redirect($url, 301);
        }
        else
        {
            $analyticsRepo = $this->container->get('analytics_metadata_repository');
            
            $data = $analyticsRepo->findbyCriteria("key", "$this->key");
            
            if(count($data) > 0)
            {
                $message = "Key already exists";
                $this->url = $this->generateUrl('dashboard_analytics_display');
                $message = serialize($message);
                $url = $this->url."?qwerty=$message.";
                return $this->redirect($url, 301);
            }
            else
            {
                $metadata = new Metadata();
                $metadata->setKey($this->key);
                $metadata->setQuery($this->query);
                $metadata->setDataSource(1);
                $metadata->setIsProcessing(1);
                $metadata->setMetadata("");
                $metadata->setCreated($this->created);
                $analyticsRepo->save($metadata);
                $analyticsRepo->completeTransaction();
                $this->executeCommandMetadata($this->key);
                
                $message = "Analytics saved and processing in the background!";
                $this->url = $this->generateUrl('dashboard_analytics_display');
                $message = serialize($message);
                $url = $this->url."?qwerty=$message.";
                return $this->redirect($url, 301);    
            }
        }
    }
    
    /* /dashboard/analytics/update */
    public function updateAnalyticsAction(Request $request)
    {
        $this->id    = $request->get('id');
        $this->key   = $request->get('key');
        $this->query = $request->get('query');
        
        if("" != $this->id && "" != $this->key && "" != $this->query)
        {
            $analyticsRepo = $this->container->get('analytics_metadata_repository');
            $update = $analyticsRepo->updateMetadata($this->id, $this->key, $this->query);
            return new Response(json_encode(array("status" => $update)));
        }
        else
        {
            return new Response(json_encode(array("status" => "failed")));
        }
    }
    
    /* /dashboard/analytics/delete */
    public function deleteAnalyticsAction(Request $request)
    {
        $this->id  = $request->get('id');
        $this->key = $request->get('key');
        
        if("" != $this->id && null != $this->id)
        {
            $analyticsRepo = $this->container->get('analytics_metadata_repository');
            $delete = $analyticsRepo->deleteMetadata($this->id);
            
            return new Response(json_encode(array("status" => $delete)));
        }
    }
    
    /* /dashboard/analytics/create_metadata */
    public function ajaxGenerateMetadataAction(Request $request)
    {
        $this->id = $request->get('id');
        $this->key = $request->get('key');
        
        $metadataRepository = $this->get('analytics_metadata_repository');
        $metadata = $metadataRepository->findbyCriteria("id", "$this->id");
        $this->query = $metadata->getQuery();
        
        try
        {
            if("" != $this->query)
            {
                $this->executeCommandMetadata($metadata->getKey());
                
                return new Response(json_encode(array("status" => "success")));
            }
            else
            {
                return new Response(json_encode(array("status" => "blank")));
            }
        }
        catch(Exception $e)
        {
            return new Response(json_encode(array("status" => "failed")));
        }
    }
}
