<?php

namespace Hyper\EventProcessingBundle\Service\RedshiftWrapper;

use Symfony\Component\DependencyInjection\ContainerInterface;

class RedshiftWrapper
{
    protected $em;
    protected $connection;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->em = $this->container->get('doctrine')->getManager('pgsql');
        $this->connection = $this->em->getConnection();
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function copyDataSource($listTableName, $credentials = '', $format = '')
    {
        if (empty($listTableName)) {
            return;
        }
        if (empty($credentials)) {
            $credentials = $this->getCredentials();
        }
        if (empty($format)) {
            $format = $this->getFormat();
        }
        $this->connection->beginTransaction();
        $query = '';
        foreach ($listTableName as $tableName => $data) {
            $query = "
                COPY {$tableName}
                FROM '{$data['source']}'
                CREDENTIALS {$credentials}
                JSON '{$data['jsonpath']}' {$format}
                ACCEPTINVCHARS
                TRUNCATECOLUMNS;
            ";
            $stmt = $this->connection->prepare($query);
            $result = $stmt->execute();
        }
        $this->connection->commit();

        return $result;
    }

    public function getCredentials()
    {
        $amazonAwsKey = $this->container->getParameter('amazon_aws_key');
        $amazonAwsSecretKey = $this->container->getParameter('amazon_aws_secret_key');

        return "'aws_access_key_id={$amazonAwsKey};aws_secret_access_key={$amazonAwsSecretKey}'";
    }

    public function getFormat()
    {
        return "gzip";
    }
}