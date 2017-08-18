<?php

namespace Hyper\EventProcessingBundle\Service\S3Wrapper;

use Symfony\Component\HttpFoundation\File\File,
    Symfony\Component\DependencyInjection\ContainerInterface,
    Aws\S3\S3Client,
    Aws\S3\Exception\S3Exception,
    Aws\S3\Transfer,
    Hyper\EventProcessingBundle\Service\SqsWrapper\SqsWrapperInterface;

class S3Wrapper implements S3WrapperInterface
{
    private $s3Client;
    
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->initS3Client();
    }
    
    public function initS3Client()
    {
        if (null != $this->s3Client) {
            return $this->s3Client;
        }
        $amazonAwsKey = $this->container->getParameter('amazon_aws_key');
        $amazonAwsSecretKey = $this->container->getParameter('amazon_aws_secret_key');
        $s3Client = S3Client::factory(array(
            'region'  => 'us-west-2',
            'version' => 'latest',
            'credentials' => array(
                'key' => $amazonAwsKey,
                'secret'  => $amazonAwsSecretKey,
              )
        ));
        $this->s3Client = $s3Client;
        if(!$this->s3Client instanceof S3Client) {
            throw new \Exception('S3Client not loaded.');
        }
    }
    
    public function getS3Client()
    {
        return $this->s3Client;
    }
    
    public function transfer($source, $dest)
    {
        // Create a transfer object.
        $manager = new \Aws\S3\Transfer($this->s3Client, $source, $dest);
        try {
            // Perform the transfer synchronously.
            $manager->transfer();
        } catch(S3Exception $e) {
            return $e->getMessage();
        }
    }
    
    public function putObject($bucket, $source, $dest, array $meta = array())
    {
        $_agrs = array(
            'Bucket'       => $bucket,
            'Key'          => $dest,
            'SourceFile'   => $source
        );
        if (!empty($meta)) {
            $_agrs['Metadata'] = $meta;
        }
        // Upload a file.
        $result = $this->s3Client->putObject($_agrs);
        
        return $result;
    }
}