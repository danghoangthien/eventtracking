<?php
namespace Hyper\EventBundle\Service\ElasticSearch;

use Symfony\Component\DependencyInjection\ContainerInterface,
    Symfony\Component\Filesystem\Filesystem,
    Symfony\Component\HttpFoundation\File\File,
    Doctrine\Common\Collections;

use GuzzleHttp\Client;

use Hyper\EventBundle\Service\ElasticSearch\Action\Action;
use Hyper\EventBundle\Service\ElasticSearch\Device\Device;
use Hyper\EventBundle\Service\ElasticSearch\IosDevice\IosDevice;
use Hyper\EventBundle\Service\ElasticSearch\AndroidDevice\AndroidDevice;

/**
 * @author Carl Pham <vanca.vnn@gmail.com>
 */
class ElasticSearch
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function init($objectName)
    {
        switch($objectName) {
            case "actions":
                $this->esInstance = new Action($this->container);
                break;
                
            case "devices":
                $this->esInstance = new Device($this->container);
                break;
                
            case "ios_devices":
                $this->esInstance = new IosDevice($this->container);
                break;
                
            case "android_devices":
                $this->esInstance = new AndroidDevice($this->container);
                break;
                
            default:
                $this->esInstance = new self($this->container);
                break;
        }
        return $this->esInstance;
    }

}