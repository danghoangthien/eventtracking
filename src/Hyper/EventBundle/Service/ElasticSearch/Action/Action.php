<?php
namespace Hyper\EventBundle\Service\ElasticSearch\Action;

use Symfony\Component\DependencyInjection\ContainerInterface,
    Hyper\EventBundle\Service\ElasticSearch\BaseElasticSearch,
    Hyper\Domain\Device\Device;

/**
 * @author Carl Pham <vanca.vnn@gmail.com>
 */
class Action extends BaseElasticSearch
{
    const TOTAL_ROW_JSON_FILE = 5000;

    protected $actionRepo;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->actionRepo = $this->container->get('action_repository');
    }

    public function perform($appTitleS3Folders = [])
    {
        // Get All App Title
        $qb = $this->container->get('application_title_repository')->createQueryBuilder('appTitle')
            ->select('appTitle.s3Folder, appFlatform.appId, app.platform')
            ->leftJoin('Hyper\Domain\Application\ApplicationPlatform', 'appFlatform', 'WITH', 'appFlatform.appTitle = appTitle.id')
            ->leftJoin('Hyper\Domain\Application\Application', 'app', 'WITH', 'app.appId = appFlatform.appId')
            ->groupBy('appTitle.s3Folder, appFlatform.appId, app.platform')
            ->orderBy('appTitle.s3Folder', 'ASC');
        if (!empty($appTitleS3Folders)) {
            $qb->where('appTitle.s3Folder IN(:appTitleS3Folders)')
                ->setParameter('appTitleS3Folders', $appTitleS3Folders);
        }
        $results = $qb->getQuery()->getResult();
        if (empty($results)) {
            echo 'Index action with app title s3: '.implode(',',$appTitleS3Folders).': Empty data index!';
            return;
        }
        foreach ($results as $row) {
            echo "Index app_id: ".$row['appId'].", s3folder: ".$row['s3Folder'].", platform: ".$row['platform']."\n";
            $this->processIndexAction($row['s3Folder'], $row['appId'], $row['platform']);
            // sleep(5);
        }
    }
    
    public function processIndexAction($appTitleS3Folder, $appId, $platform)
    {
        $totalActions = $this->countActionByAppId($appId);
        $numberLoop = ceil($totalActions/self::TOTAL_ROW_JSON_FILE);
        echo "Total number loop: $numberLoop \n";
        for ($i = 0; $i < $numberLoop; $i ++) {
            $actions = $this->container
                ->get('action_repository')
                ->getActionForEsByAppId(
                    $appId,
                    self::TOTAL_ROW_JSON_FILE,
                    $i + $i*self::TOTAL_ROW_JSON_FILE
                );
            if (empty($actions)) {
                continue;
            }
            $jsonESSyntax = '';
            $tmpDeviceIds = [];
            foreach ($actions as $row) {
                $jsonESSyntax .= $this->makeESSyntax($row);
                $tmpDeviceIds[] = $row['device_id'];
            }
            $tmpDeviceIds = array_unique($tmpDeviceIds);
            if (!is_dir($this->tmpDeviceIdDir)) {
                mkdir($this->tmpDeviceIdDir);
            }
            file_put_contents(
                $this->tmpDeviceIdDir.'/'.self::TMP_DEVICE_ID_FILE,
                implode(',',$tmpDeviceIds).',',
                FILE_APPEND
            );
            if ($platform == Device::IOS_PLATFORM_CODE) {
                file_put_contents(
                    $this->tmpDeviceIdDir.'/'.self::TMP_IOS_DEVICE_ID_FILE,
                    implode(',',$tmpDeviceIds).',',
                    FILE_APPEND
                );
            } else {
                file_put_contents(
                    $this->tmpDeviceIdDir.'/'.self::TMP_ANDROID_DEVICE_ID_FILE,
                    implode(',',$tmpDeviceIds).',',
                    FILE_APPEND
                );
            }

            $fs = $this->getFS();
            $pathJson = $this->esDir.'/'.$appTitleS3Folder.'/'.$appTitleS3Folder.'_'.$i.'.json';
            $fs->dumpFile($pathJson, $jsonESSyntax);

            echo "Push file: $pathJson \n";
            $rs = $this->pushDataToElasticSearch($appTitleS3Folder, $appId, $pathJson);
            if ($rs['response_code'] == 200 || $rs['response_code'] == 201) {
                $fs->remove($pathJson);
            } else {
                throw new \Exception('Error push data to ES, response code: '.$rs['response_code']);
            }
        }
    }

    public function getDataForEs($offset)
    {
        $offset = $offset + $offset*self::TOTAL_ROW_JSON_FILE;
        return $this->iosDeviceRepo->createQueryBuilder('iosdev')
            ->select('iosdev')
            ->setFirstResult($offset)
            ->setMaxResults(self::TOTAL_ROW_JSON_FILE)
            ->getQuery()
            ->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
    }
    
    public function countActionByAppId($appId)
    {
        $qb = $this->container->get('action_repository')
            ->createQueryBuilder('act')
            ->select("count(act.id)")
            ->where('act.appId = ?1')
            ->setParameter(1, $appId);
        $count = $qb->getQuery()->getSingleScalarResult();
        return $count;
    }
}