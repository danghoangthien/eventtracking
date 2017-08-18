<?php
namespace Hyper\EventBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Hyper\EventBundle\Service\Cached\InappeventConfig\InappeventConfigCached;

class InappeventContentTypeCachedGenerateCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('iae_content_type_cached:generate')
            ->setDescription('Generate Inappevent content type cached')
            ->addOption(
                'type',
                null,
                InputOption::VALUE_REQUIRED,
                'type'
            )
            ->addOption(
                'app_id',
                null,
                InputOption::VALUE_OPTIONAL,
                'app_id'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
		
		$type       = $input->getOption('type');
		$connection = $this->getContainer()->get('doctrine')->getManager('pgsql')->getConnection();
		$appIds = [];
		if ($type == 'all'){
			$sql = "SELECT app_id FROM applications_platform";
			$stmt = $connection->query($sql); 
			while ($row = $stmt->fetch()) {
			    $appIds[] = $row['app_id'];
			}
			
		} elseif ($type = 'single_app'){
			$appIds[] = $input->getOption('app_id');
		}
		//print_r ($appIds); die;
		foreach ($appIds as $appId){
			$iaeConigCached = new InappeventConfigCached($this->getContainer());
	        $iaeConfig = $iaeConigCached->hget($appId);
	        if (empty($iaeConfig)) {
	        	echo "in-app-event configure for app_id < $appId > is not cached"."\n"; 
	        	continue;
	        }
	        else {
	            $iaeConfig = json_decode($iaeConfig, true);
	            echo "start query: ".time()."\n";
	            
	            $stmt = $connection->prepare("set query_group to 'ak_high_concurrency_short_processing_time';");
	            $stmt->execute();
	            $actionRepo = $this->getContainer()->get('action_repository');
				$actionQb = $actionRepo->createQueryBuilder('act');
				$statement = $actionQb->select('act.afContentType,act.eventName')
		                    ->where($actionQb->expr()->eq('act.appId','?1'))
		                    ->andWhere( $actionQb->expr()->isNotNull('act.afContentType') )
		                    ->andWhere( $actionQb->expr()->not($actionQb->expr()->eq('act.afContentType', '?2')) )
		                    ->addGroupBy('act.appId')
		                    ->addGroupBy('act.eventName')
		                    ->addGroupBy('act.afContentType')
		                    ->setParameter(1,$appId)
		                    ->setParameter(2,'');
		        $query = $statement->getQuery();
		        $iterableResult = $query->iterate();
		        $stmt = $connection->prepare("reset query_group;");
	            $stmt->execute();
	            echo "end query: ".time()."\n";
		        $afContentTypeByEvent = array();
		        
		        foreach ($iterableResult as $i => $row) {
		        	$data = $row[$i];
		        	$afContentTypeByEvent[$data['eventName']][] = $data['afContentType'];
		        	
		        }
		        $eventInContentType = array_keys($afContentTypeByEvent);
		        foreach ( $iaeConfig as $eventName => $eventData ){
		        	
		        	if( in_array($eventName,$eventInContentType) ){
		        		$iaeConfig[$eventName]['content_types'] = array();
		        		$iaeConfig[$eventName]['content_types'] = $afContentTypeByEvent[$eventName];
		        	}
		        }
	        } 
			$iaeConigCached->hset($appId, json_encode($iaeConfig));
			//print_r($iaeConfig);
		}
		
        
        
		
		
	}
	
	protected function execute_bk(InputInterface $input, OutputInterface $output)
    {
		$appId       = $input->getOption('app_id');
		$iaeConigCached = new InappeventConfigCached($this->getContainer());
        $iaeConfig = $iaeConigCached->hget($appId);
        if (empty($iaeConfig)) {
        	echo "in-app-event configure for app_id< $appId > is not cached"; 
        	die;
        }
        else {
            $iaeConfig = json_decode($iaeConfig, true);
            echo "start query: ".time()."\n";
            //$connection = $this->getContainer()->get('doctrine')->getManager('pgsql')->getConnection();
            $stmt = $connection->prepare("set query_group to 'ak_high_concurrency_short_processing_time';");
            $stmt->execute();
            $actionRepo = $this->getContainer()->get('action_repository');
			$actionQb = $actionRepo->createQueryBuilder('act');
			$statement = $actionQb->select('act.afContentType,act.eventName')
	                    ->where($actionQb->expr()->eq('act.appId','?1'))
	                    ->andWhere( $actionQb->expr()->isNotNull('act.afContentType') )
	                    ->andWhere( $actionQb->expr()->not($actionQb->expr()->eq('act.afContentType', '?2')) )
	                    ->addGroupBy('act.appId')
	                    ->addGroupBy('act.eventName')
	                    ->addGroupBy('act.afContentType')
	                    ->setParameter(1,$appId)
	                    ->setParameter(2,'');
	        $query = $statement->getQuery();
	        $iterableResult = $query->iterate();
	        $stmt = $connection->prepare("reset query_group;");
            $stmt->execute();
            echo "end query: ".time()."\n";
	        $afContentTypeByEvent = array();
	        
	        foreach ($iterableResult as $i => $row) {
	        	$data = $row[$i];
	        	$afContentTypeByEvent[$data['eventName']][] = $data['afContentType'];
	        	
	        }
	        $eventInContentType = array_keys($afContentTypeByEvent);
	        foreach ( $iaeConfig as $eventName => $eventData ){
	        	
	        	if( in_array($eventName,$eventInContentType) ){
	        		$iaeConfig[$eventName]['content_types'] = array();
	        		$iaeConfig[$eventName]['content_types'] = $afContentTypeByEvent[$eventName];
	        	}
	        }
        } 
		$iaeConigCached->hset($appId, json_encode($iaeConfig));
		//print_r($iaeConfig);
        
        
		
		
	}
}
