<?php

namespace Hyper\Adops\WebBundle\Domain;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Util\Inflector;

use Symfony\Component\Validator\Constraints as Assert;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
* @ORM\Entity(repositoryClass="Hyper\Adops\WebBundle\DomainBundle\Repository\DTPublisherRepository")
* @ORM\Table(name="adops_publishers")
*
* @author Carl Pham <vanca.vnn@gmail.com>
*/
class AdopsPublisher
{
    /**
     * @ORM\Column(type="string", name="id")
     * @ORM\Id
     */
    private $id;

    /**
     * @ORM\Column(name="name", type="string", length=255)
     *
     */
    private $name;
    
    public function __construct()
    {
        $this->id = uniqid('',true);
    }

    /**
     * Set id
     *
     * @param string $id
     * @return AdopsPublisher
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
     * Set Name
     *
     * @param string $name
     * @return AdopsPublisher
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get Name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

}