<?php

namespace Hyper\Domain\Identity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * Identity
 *
 * @ORM\Table(name="identities")
 * @ORM\Entity(repositoryClass="Hyper\DomainBundle\Repository\Identity\DTIdentityRepository")
 * @ExclusionPolicy("all")
 */
class Identity
{
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
     * @ORM\Column(name="full_name", type="string")
     * @Expose
     */
    private $fullName;


    /**
     * @var integer
     *
     * @ORM\Column(name="sex", type="integer",options={"unsigned"=true})
     * @Expose
     */
    private $sex;


    /**
     * @var string
     *
     * @ORM\Column(name="birthday", type="string")
     * @Expose
     */
    private $birthDay;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string")
     * @Expose
     */
    private $email;
    
    /**
     * @var string
     *
     * @ORM\Column(name="email_2", type="string")
     * @Expose
     */
    private $email2;

    /**
     * @var string
     *
     * @ORM\Column(name="email_3", type="string")
     * @Expose
     */
    private $email3;

    /**
     * @var string
     *
     * @ORM\Column(name="facebook_id", type="string")
     * @Expose
     */
    private $facebookId;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="created", type="integer")
     * @Expose
     */
    private $created;
    
    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="Hyper\Domain\Device\Device", mappedBy="identities", fetch="EXTRA_LAZY", cascade={"persist"})
     */
     private $devices;

    
    public function __construct()
    {
        $this->id = uniqid('',true);
        $this->devices = new \Doctrine\Common\Collections\ArrayCollection();
        $this->created = time();
    }
    

    /**
     * Set id
     *
     * @param string $id
     * @return Identity
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
     * Set fullName
     *
     * @param string $fullName
     * @return Identity
     */
    public function setFullName($fullName)
    {
        $this->fullName = $fullName;

        return $this;
    }

    /**
     * Get fullName
     *
     * @return string 
     */
    public function getFullName()
    {
        return $this->fullName;
    }

    /**
     * Set sex
     *
     * @param integer $sex
     * @return Identity
     */
    public function setSex ($sex)
    {
        $this->sex = $sex;

        return $this;
    }

    /**
     * Get sex
     *
     * @return integer 
     */
    public function getSex()
    {
        return $this->sex;
    }

    /**
     * Set birthDay
     *
     * @param string $birthDay
     * @return Identity
     */
    public function setBirthDay($birthDay)
    {
        $this->birthDay = $birthDay;

        return $this;
    }

    /**
     * Get birthDay
     *
     * @return string 
     */
    public function getBirthDay()
    {
        return $this->birthDay;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return Identity
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
     * Set email2
     *
     * @param string $email2
     * @return Identity
     */
    public function setEmail2($email2)
    {
        $this->email2 = $email2;

        return $this;
    }

    /**
     * Get email2
     *
     * @return string 
     */
    public function getEmail2()
    {
        return $this->email2;
    }

    /**
     * Set email3
     *
     * @param string $email3
     * @return Identity
     */
    public function setEmail3($email3)
    {
        $this->email3 = $email3;

        return $this;
    }

    /**
     * Get email3
     *
     * @return string 
     */
    public function getEmail3()
    {
        return $this->email3;
    }

    /**
     * Set facebookId
     *
     * @param string $facebookId
     * @return Identity
     */
    public function setFacebookId($facebookId)
    {
        $this->facebookId = $facebookId;

        return $this;
    }

    /**
     * Get facebookId
     *
     * @return string 
     */
    public function getFacebookId()
    {
        return $this->facebookId;
    }

    /**
     * Set created
     *
     * @param integer $created
     * @return Identity
     */
    public function setCreated ($created)
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
     * Add devices
     *
     * @param \Hyper\Domain\Device\Device $devices
     * @return Identity
     */
    public function addDevice(\Hyper\Domain\Device\Device $devices)
    {
        $this->devices[] = $devices;

        return $this;
    }

    /**
     * Remove devices
     *
     * @param \Hyper\Domain\Device\Device $devices
     */
    public function removeDevice(\Hyper\Domain\Device\Device $devices)
    {
        $this->devices->removeElement($devices);
    }

    /**
     * Get devices
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getDevices()
    {
        return $this->devices;
    }
}
