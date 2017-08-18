<?php

namespace Hyper\EventBundle\Upload;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\File;

use Gaufrette\Filesystem;

class EventLogUploader
{
    private $filesystem;
    private $s3;

    /*
    private static $allowedMimeTypes = array(
        'image/jpeg',
        'image/png',
        'image/gif'
    );
    */

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function upload(UploadedFile $file)
    {
        // Check if the file's mime type is in the list of allowed mime types.
        /*
        if (!in_array($file->getClientMimeType(), self::$allowedMimeTypes)) {
            throw new \InvalidArgumentException(sprintf('Files of type %s are not allowed.', $file->getClientMimeType()));
        }
        */

        // Generate a unique filename based on the date and add file extension of the uploaded file
        $filename = sprintf('%s/%s/%s/%s.%s', date('Y'), date('m'), date('d'), uniqid(), $file->getClientOriginalExtension());

        $adapter = $this->filesystem->getAdapter();
        $adapter->setMetadata($filename, array('contentType' => $file->getClientMimeType()));
        $adapter->write($filename, file_get_contents($file->getPathname()));

        return $filename;
    }

    public function uploadFromLocal(File $file)
    {
        // Check if the file's mime type is in the list of allowed mime types.
        /*
        if (!in_array($file->getClientMimeType(), self::$allowedMimeTypes)) {
            throw new \InvalidArgumentException(sprintf('Files of type %s are not allowed.', $file->getClientMimeType()));
        }
        */

        // Generate a unique filename based on the date and add file extension of the uploaded file
        $extension = $file->getExtension();
        if(empty($extension)){
            $mime = $file->getMimeType();
            // work arround when could not get extension gz
            if($mime=='application/x-gzip'){
                $extension = 'gz';
            }
        }
        $filename = $file->getBasename();

        $adapter = $this->filesystem->getAdapter();
        $adapter->setMetadata($filename, array('contentType' => $file->getMimeType()));
        $adapter->write($filename, file_get_contents($file->getPathname()));

        return $filename;
    }

    public function uploadFromLocalV2(File $file,$folder = '')
    {
        // Check if the file's mime type is in the list of allowed mime types.
        /*
        if (!in_array($file->getClientMimeType(), self::$allowedMimeTypes)) {
            throw new \InvalidArgumentException(sprintf('Files of type %s are not allowed.', $file->getClientMimeType()));
        }
        */

        // Generate a unique filename based on the date and add file extension of the uploaded file
        //$extension = $file->getExtension();
        $fileName = $file->getBasename();
        $fileNamePart = explode('.',$fileName);
        $lastPart = end($fileNamePart);
        $extension = $lastPart;
        if(empty($extension)){
            /*
            $mime = $file->getMimeType();
            // work arround when could not get extension gz
            if($mime=='application/x-gzip'){
                $extension = 'gz';
            }
            */
        }
        $filename = $file->getBasename();

        $adapter = $this->filesystem->getAdapter();
        if($extension == 'gz'){
            $adapter->setMetadata($filename, array('contentType' => 'application/x-gzip'));
        }


        if($folder !== ''){
            $filename = $folder.'/'.$filename;
        }

        $adapter->write($filename, file_get_contents($file->getPathname()));

        return $filename;
    }
    /**
     * @author thien@hypergrowth.co
     *
     * Improving upload function using aws php sdk v3
     */
    public function uploadFromLocalV3(File $file,$folder = '',$region,$bucket,$securityKey,$securitySecret,$metaData = array())
    {
        $fileName = $file->getBasename();
        $fileNamePart = explode('.',$fileName);
        $lastPart = end($fileNamePart);
        $extension = $lastPart;
        if(empty($extension)){
            /*
            $mime = $file->getMimeType();
            // work arround when could not get extension gz
            if($mime=='application/x-gzip'){
                $extension = 'gz';
            }
            */
        }
        $filename = $file->getBasename();
        if($folder !== ''){
            $filename = $folder.'/'.$filename;
        }
        $filepath = $file->getPathname();

        $s3 = $this->getS3Connection($securityKey, $securitySecret, $region);

        // Upload a file.
        $result = $s3->putObject(array(
            'Bucket'       => $bucket,
            'Key'          => $filename,
            'SourceFile'   => $filepath,
            'ContentType'  => 'application/json',
            'ACL'          => 'public-read',
            'StorageClass' => 'REDUCED_REDUNDANCY',
            'Metadata'     => $metaData
        ));

        if(isset($result['@metadata']['statusCode'])){
            if($result['@metadata']['statusCode'] == '200'){
                return $filename;
            } else {
                return $result['@metadata']['statusCode'];
            }
        }
        else{
            return false;
        }

    }

    /**
     * Get S3 Connection
     *
     * @author Carl Pham <vanca.vnn@gmail.com>
     * @return \Aws\S3\S3Client
     */
    private function getS3Connection($securityKey, $securitySecret, $region)
    {
        if (!empty($this->s3)) {
            return $this->s3;
        }
        $credentials = new \Aws\Credentials\Credentials($securityKey, $securitySecret);
        $options = [
            'region'            => $region,
            'version'           => '2006-03-01',
            'signature_version' => 'v4',
            'credentials' =>$credentials
        ];
        $this->s3 = new \Aws\S3\S3Client($options);

        return $this->s3;
    }

}