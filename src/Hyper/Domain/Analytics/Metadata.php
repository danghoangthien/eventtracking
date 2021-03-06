<?php

namespace Hyper\Domain\Analytics;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * Metadata
 *
 * @ORM\Table(name="analytics_metadata")
 * @ORM\Entity(repositoryClass="Hyper\DomainBundle\Repository\Analytics\DTMetadataRepository")
 * @ExclusionPolicy("all")
 */
class Metadata
{
    const DATA_SOURCE_JSON = 1;
    const DATA_SOURCE_MEM = 2;
    const DATA_SOURCE_REF_TABLE = 3;
    
    /**
     * @var string
     * @ORM\Column(name="id", type="string", options={"unsigned"=true})
     * @ORM\Id
     * @Expose
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="key", type="string")
     * @Expose
     */
    private $key;
    
    /**
     * @var string
     *
     * @ORM\Column(name="query", type="string", length=65535)
     * @Expose
     */
    private $query;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="data_source", type="integer",options={"default"=1})
     * @Expose
     */
    private $dataSource;

    /**
     * @var string
     *
     * @ORM\Column(name="metadata", type="string", length=65535)
     * @Expose
     */
    private $metadata;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="created", type="integer")
     * @Expose
     */
    private $created;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="is_processing", type="integer", nullable=true)
     * @Expose
     */
    private $isProcessing;
    
    public function __construct()
    {
        $this->id = uniqid('',true);
        $this->created = time();
    }

    /**
     * Set id
     *
     * @param string $id
     * @return Metadata
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id
     *
     * @return string 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set dataSource
     *
     * @param integer $dataSource
     * @return Metadata
     */
    public function setDataSource($dataSource)
    {
        $this->dataSource = $dataSource;

        return $this;
    }

    /**
     * Get dataSource
     *
     * @return integer 
     */
    public function getDataSource()
    {
        return $this->dataSource;
    }

    /**
     * Set metadata
     *
     * @param string $metadata
     * @return Metadata
     */
    public function setMetadata($metadata)
    {
        $this->metadata = $metadata;

        return $this;
    }

    /**
     * Get metadata
     *
     * @return string 
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * Set key
     *
     * @param string $key
     * @return Metadata
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * Get key
     *
     * @return string 
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Set query
     *
     * @param string $query
     * @return Metadata
     */
    public function setQuery($query)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Get query
     *
     * @return string 
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Set created
     *
     * @param integer $created
     * @return Metadata
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return integer 
     */
    public function getCreated()
    {
        return $this->created;
    }
    
    /**
     * Set isProcessing
     *
     * @param integer $isProcessing
     * @return Metadata
     */
    public function setIsProcessing($isProcessing)
    {
        $this->isProcessing = $isProcessing;

        return $this;
    }

    /**
     * Get isProcessing
     *
     * @return integer 
     */
    public function getIsProcessing()
    {
        return $this->isProcessing;
    }
}
