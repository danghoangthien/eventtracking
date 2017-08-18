<?php

namespace Hyper\EventProcessingBundle\Service\LoggerWrapper;

use Symfony\Component\DependencyInjection\ContainerInterface,
    Symfony\Component\Filesystem\Filesystem,
    Symfony\Component\HttpFoundation\File\File,
    Hyper\EventProcessingBundle\Service\LoggerWrapper\LoggerWrapperInterface;

class LoggerWrapper implements LoggerWrapperInterface
{
    protected $s3Wrapper;
    protected $rootDir;
    protected $dataDir;
    protected $logFolder;
    protected $fs;
    
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->rootDir = $this->container->get('kernel')->getRootDir() . '/../';
        $this->dataDir = $this->rootDir.'web';
        $this->logFolder = implode('/', array(
            date('Y'),
            date('m'),
            date('d'),
            date('H'),
            date('i')
        ));
        $this->initService();
    }
    
    protected function initService()
    {
        $this->s3Wrapper = $this->container
            ->get('hyper_event_processing.s3_wrapper');
    }
    
    protected function getFS()
    {
        if (!$this->fs) {
            $this->fs = new Filesystem();
        }

        return $this->fs;
    }
    
    public function log(\Exception $e, $bucket, $prefix, $content)
    {   
        try {
            $meta = array(
                'error' => substr($e->getMessage(), 0, 100),
            );
            $data = array(
                'error' => $e->getMessage(),
                'data' => $content
            );
            $fs = $this->getFS();
            $fileName = uniqid();
            $logDir = $this->dataDir.'/'.$bucket.'/logs/'.$prefix.'/'.$this->logFolder;
            $pathJson = $logDir.'/'.$fileName.'.json';
            $pathGz = $logDir.'/'.$fileName.'.gz';
            $fs->dumpFile($pathJson, json_encode($data));
            $fileJson = new File($pathJson);
            $jsonFilePathName = $fileJson->getPathname();
            $gzFilePathName = $pathGz;
            file_put_contents($gzFilePathName, gzencode(file_get_contents($jsonFilePathName), 9));
            chmod($gzFilePathName, 0777);
            $gzFile = new File($gzFilePathName);
            $gzFilePathName = $gzFile->getPathname();
            $gzFileName = $gzFile->getBasename();
            $result = '';
            $result = $this->s3Wrapper->putObject(
                $bucket,
                $gzFilePathName,
                'logs/'.$prefix.'/'.$this->logFolder.'/'.$fileName.'.gz',
                $meta
            );
            if (
                empty($result['@metadata']['statusCode']) ||
                $result['@metadata']['statusCode'] != '200'
            ) {
                throw new \Exception('Store log to S3 bucket is failed.');
            }
            $fs->remove($pathJson);
            $fs->remove($pathGz);
        } catch(\Exception $e) {
            throw new \Exception($e);
        }
    }
    
    public function logInvalidContent(
        $errors,
        $bucket, 
        $prefix,
        $clientName,
        $appId,
        $eventType,
        $eventName, 
        $content,
        $s3LogFile
    ){
        
        try {
            $meta = array(
                'client_name' => $clientName,
                'app_id' => $appId,
                'event_type' => $eventType,
                'event_name' => $eventName
            );
            $data = array();
            if (!empty($errors) && is_array($errors)) {
                foreach ($errors as $key => $error) {
                    $data['error'][] = $error;
                    $meta['error-'. $key] = substr($error, 0, 100);
                }
            }
            $data['data'] = $content;
            $fs = $this->getFS();
            $logDir = $this->dataDir.'/'.$bucket.'/logs/'.$prefix;
            if (!$s3LogFile) {
                $s3LogFile = 'others/' . uniqid() . '.gz';
            }
            $pathJson = $logDir.'/'.$s3LogFile.'.json';
            $pathGz = $logDir.'/'.$s3LogFile;
            $fs->dumpFile($pathJson, json_encode($data));
            $fileJson = new File($pathJson);
            $jsonFilePathName = $fileJson->getPathname();
            $gzFilePathName = $pathGz;
            file_put_contents($gzFilePathName, gzencode(file_get_contents($jsonFilePathName), 9));
            chmod($gzFilePathName, 0777);
            $gzFile = new File($gzFilePathName);
            $gzFilePathName = $gzFile->getPathname();
            $gzFileName = $gzFile->getBasename();
            $result = $this->s3Wrapper->putObject(
                $bucket,
                $gzFilePathName,
                'logs/'.$prefix.'/'. $s3LogFile,
                $meta
            );
            $fs->remove($pathJson);
            $fs->remove($pathGz);
        } catch(\Exception $e) {
            throw new \Exception($e);
        }
    }
}