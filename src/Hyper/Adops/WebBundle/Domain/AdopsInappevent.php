<?php

namespace Hyper\Adops\WebBundle\Domain;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Util\Inflector;

use Symfony\Component\Validator\Constraints as Assert;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
* @ORM\Entity(repositoryClass="Hyper\Adops\WebBundle\DomainBundle\Repository\DTInappeventRepository")
* @ORM\Table(name="adops_inappevents")
*
* @author Carl Pham <vanca.vnn@gmail.com>
*/
class AdopsInappevent
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
    
    /**
     * @ORM\ManyToOne(targetEntity="Hyper\Adops\WebBundle\Domain\AdopsApplication", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn()
     * @Expose
     */
    private $application;
    
    public function __construct()
    {
        $this->id = uniqid('',true);
    }

    /**
     * Set id
     *
     * @param string $id
     * @return AdopsInappevent
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
     * @return AdopsInappevent
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
    
    /**
     * Set Application
     * 
     * @return AdopsInappevent
     */
    public function setApplication(\Hyper\Adops\WebBundle\Domain\AdopsApplication $application=null)
    {
        $this->application = $application;
        
        return $this;
    }
    
    /**
     * Get Application
     * 
     * @return AdopsApplication Entity
     */
    public function getApplication()
    {
        return $this->application;
    }

}