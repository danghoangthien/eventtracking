<?php

namespace Hyper\EventBundle\Upload;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\File;

use Gaufrette\Filesystem;

class EventLogUploader
{
    private $filesystem;
    
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

}