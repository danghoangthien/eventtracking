<?php

namespace Hyper\EventProcessingBundle\Service\S3Wrapper;

interface S3WrapperInterface
{
    public function initS3Client();
    public function transfer($source, $dest);
    public function putObject($bucket, $source, $dest, array $meta = array());
}