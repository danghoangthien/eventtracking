<?php
namespace Hyper\EventBundle\Service;

use Hyper\Domain\Setting\Setting;
use Symfony\Component\Filesystem\Filesystem;

class EsReIndex
{
    private $container;
    private $em;
    private $conn;
    private $awsCredentials;
    public $fs;

    const S3BUCKET = 's3://elasticsearch-reindex/';
    const UPLOAD_DIR = '/var/www/html/projects/event_tracking/web/elasticsearch-reindex';

    public function __construct(\Symfony\Component\DependencyInjection\ContainerInterface $container){
        $this->container = $container;
        $this->s3Wrapper = $this->container->get('hyper_event_processing.s3_wrapper');
        $this->em = $this->container->get('doctrine')->getManager('pgsql');
        $this->conn = $this->em->getConnection();
        $this->fs = new Filesystem();
        $this->awsCredentials = $this->getCredentials();

    }
    public function process($appTitle, $from, $to, $delete) {
        $appTitleInfo = $this->conn->executeQuery("select * from applications_title where title = '$appTitle'")->fetch();
        $appPlatform = $this->conn->executeQuery("select * from applications_platform where app_title_id = '{$appTitleInfo['id']}'")->fetchAll();
        $index = strtolower($appTitleInfo['s3_folder']).'_'. $this->container->getParameter('amazon_elasticsearch')['index_version'];
        $types = array_column($appPlatform,'app_id');
        $this->createIndex($index, $types, $delete);
        foreach($appPlatform as $value){
            $appId = $value['app_id'];
            $start = date('Y-m-d H:i:s');
            echo "start re-index $index, $appId, $start","\n";
            $tmpTable = 'actions' . '_' . $appId . '_' .'tmp';
            $tmpTable = str_replace('.','_', $tmpTable);
            $this->createTmpTable($tmpTable);//create tmp table
            $this->dumpDataTmpTable($tmpTable, $appId, $from, $to);
            $this->createJsonFile($tmpTable, $index, $appId);
            $this->deleteTmpTable($tmpTable);
            $end = date('Y-m-d H:i:s');
            echo "end re-index $index, $appId, $end","\n";
        }
        echo "finish processing"."\n";
        return;

    }
    private function createIndex($index, $types, $delete){
        //create app title index on elasticsearch
        $esClient =(new \Hyper\EventBundle\Service\HyperESClient($this->container,[], false))->getClient();
        $esIndex = $esClient->getIndex($index);
        if($esIndex->exists() && $delete == 0){
           return;
        }
        if($esIndex->exists() && $delete == 1){
            $esIndex->delete($index);
        }
        $esIndex->create();
        $fileMapping = $this->container->getParameter('kernel.root_dir') .'/../tool/mapping/action/mapping.json';
        $strMapping = file_get_contents($fileMapping);
        $arrMapping = json_decode($strMapping, true);
        foreach ($types as $type) {
           $esType = $esIndex->getType($type);
            // Define mapping
            $mapping = new \Elastica\Type\Mapping();
            $mapping->setType($esType);
            // Set mapping
            $mapping->setProperties($arrMapping);
            $mapping->send();
        }
    }

    private function createTmpTable($tmpTable){
        $this->conn->prepare("drop table if exists $tmpTable")->execute();
        $this->conn->prepare("create table $tmpTable(like actions)")->execute();
    }
    private function dumpDataTmpTable($tmpTable, $appId, $from, $to){
        $credential = $this->awsCredentials;
        //$this->s3Wrapper->getS3Client()->createBucket(['Bucket' => self::BUCKET]);
        $s3Path = self::S3BUCKET . $tmpTable;
        $this->conn->beginTransaction();
        //redshift to s3
        $sqlUnload = "
            UNLOAD ('
                SELECT * FROM actions
                WHERE actions.app_id=\'{$appId}\'
                AND actions.created BETWEEN $from AND $to
            ')
            TO '{$s3Path}'
            CREDENTIALS {$credential}
            GZIP
            PARALLEL ON
            ESCAPE
            ALLOWOVERWRITE;
        ";
        $stmt = $this->conn->prepare($sqlUnload);
        $rs = $stmt->execute();
        $this->conn->commit();
        //s3 to redshift
        $this->conn->beginTransaction();
        $sqlCopy = "
            COPY {$tmpTable}
            FROM '{$s3Path}'
            CREDENTIALS {$credential}
            GZIP
            ESCAPE
        ";
        $stmt = $this->conn->prepare($sqlCopy);
        $rs = $stmt->execute();
        $this->conn->commit();

    }
    private function createJsonFile($tmpTable, $index, $appId){
        $sql = "SELECT * FROM $tmpTable LIMIT 5000";
        $stmt = $this->conn->executeQuery($sql);
        $i = 1;
        while($result = $this->conn->executeQuery($sql)->fetchAll()){
            $ids = array_column($result,'id');
            $jsString = "";
            foreach($result as $value){
                $jsString .= $this->makeJsonString($value);
            }
            $now = microtime();
            $pathJson = self::UPLOAD_DIR.'/' . md5($now) . '.json';
            $this->fs->dumpFile($pathJson, $jsString);
            //delete record already create json string
            $ids = implode("','", $ids);
            $sqlDeleteReport = "DELETE FROM $tmpTable
            WHERE id IN ('$ids')";
            $stmt = $this->conn->executeQuery($sqlDeleteReport);
            $i++;
            if($i%20 == 0 || count($result) < 5000){
                $this->bulkJsonToEs($index, $appId);
            }
        }
    }
    private function deleteTmpTable($tmpTable){
        $this->conn->executeQuery("DROP TABLE $tmpTable");
    }
    private function bulkJsonToEs($index, $type){
        $baseEs = new \Hyper\EventBundle\Service\ElasticSearch\BaseElasticSearch($this->container);
        $files = scandir(self::UPLOAD_DIR);
        foreach ($files as $filename) {
            if (in_array($filename,array(".",".."))) {
                continue;
            }
            $pathJson = self::UPLOAD_DIR . '/' . $filename;
            echo "Push file: $pathJson \n";
            $baseEs->pushDataToElasticSearch($index, $type, $pathJson);
            $this->fs->remove(self::UPLOAD_DIR . '/' . $filename);

        }
    }
    private function getCredentials(){

        $amazonAwsKey = $this->container->getParameter('amazon_aws_key');
        $amazonAwsSecretKey = $this->container->getParameter('amazon_aws_secret_key');
        return "'aws_access_key_id={$amazonAwsKey};aws_secret_access_key={$amazonAwsSecretKey}'";
    }
     public function makeJsonString($row){
        return json_encode(['index' => ['_id'=>$row['id']]]).PHP_EOL.json_encode($row).PHP_EOL;
    }

    private function log(\Exception $ex){

    }

}
