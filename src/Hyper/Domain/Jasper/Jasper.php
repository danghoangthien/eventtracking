<?php

namespace Hyper\Domain\Jasper;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * Jasper
 *
 * @ORM\Table(name="jasper_auth")
 * @ORM\Entity(repositoryClass="Hyper\DomainBundle\Repository\Jasper\DTJasperRepository")
 * @ExclusionPolicy("all")
 */
class Jasper
{
    public function __construct()
    {
        $this->id = uniqid('',true);    
        $this->updated = strtotime(date('Y-m-d h:i:s'));
    }
    
    /**
     * @ORM\Column(name="id", type="string", length=255, nullable=false)")
     * @ORM\Id
     * @Expose
     */
    protected $id;
    
    /**
     * @var string
     *
     * @ORM\Column(name="username", type="string", length=255, nullable=false)
     * @Expose
     */
    private $username;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=255, nullable=false)
     * @Expose
     */
    private $password;
    
    /**
     * @var string
     *
     * @ORM\Column(name="organization", type="string",nullable=false, length=255)
     * @Expose
     */
    private $organization;
    
    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string",nullable=false, length=255)
     * @Expose
     */
    private $email;
    
    /**
     * @var string
     *
     * @ORM\Column(name="extra_fields", type="string", length=13107, nullable=true)
     * @Expose
     */
    private $extraFields;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="created", type="integer", nullable=false,)
     * @Expose
     */
    private $created;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="updated", type="integer", nullable=false,)
     * @Expose
     */
    private $updated;

    /**
     * Set id
     *
     * @param string $id
     * @return Jasper
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
     * Set username
     *
     * @param string $username
     * @return Jasper
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get username
     *
     * @return string 
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set password
     *
     * @param string $password
     * @return Jasper
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string 
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set organization
     *
     * @param string $organization
     * @return Jasper
     */
    public function setOrganization($organization)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * Get organization
     *
     * @return string 
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return Jasper
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string 
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set extraFields
     *
     * @param string $extraFields
     * @return Jasper
     */
    public function setExtraFields($extraFields)
    {
        $this->extraFields = $extraFields;

        return $this;
    }

    /**
     * Get extraFields
     *
     * @return string 
     */
    public function getExtraFields()
    {
        return $this->extraFields;
    }

    /**
     * Set created
     *
     * @param integer $created
     * @return Jasper
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
     * Set updated
     *
     * @param integer $updated
     * @return Jasper
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return integer 
     */
    public function getUpdated()
    {
        return $this->updated;
    }
}
