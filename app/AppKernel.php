<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Exception\ParseException;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Symfony\Bundle\AsseticBundle\AsseticBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new FOS\RestBundle\FOSRestBundle(),
            new JMS\SerializerBundle\JMSSerializerBundle(),
            new Nelmio\ApiDocBundle\NelmioApiDocBundle(),
            new AppBundle\AppBundle(),
            //new Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle(),
            new Hyper\EventBundle\HyperEventBundle(),
            new Knp\Bundle\GaufretteBundle\KnpGaufretteBundle(),
            //new Lsw\MemcacheBundle\LswMemcacheBundle(),
            new Hyper\DomainBundle\HyperDomainBundle(),
            //new Hyper\Adops\WebBundle\HyperAdopsWebBundle(),
            //new Hyper\Adops\APIBundle\HyperAdopsAPIBundle(),
            new Maxmind\Bundle\GeoipBundle\MaxmindGeoipBundle(),
            new Knp\Bundle\PaginatorBundle\KnpPaginatorBundle(),
            new Hyper\EventProcessingBundle\HyperEventProcessingBundle(),
            new Snc\RedisBundle\SncRedisBundle(),
            new Hyper\EventAPIBundle\HyperEventAPIBundle()
        );

        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
            $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
        }

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load($this->getRootDir().'/config/config_'.$this->getEnvironment().'.yml');
        // load elasticache config
        $yaml = new Parser();
        try {
            $content = $yaml->parse(file_get_contents($this->getRootDir().'/config/parameters.yml'));
            $loader->load($this->getRootDir().'/config/elasticache_'.$content['parameters']['elasticache_env'].'.yml');
        } catch (ParseException $e) {
            echo "Unable to parse the YAML string: " . $e->getMessage();
        }
    }
}
