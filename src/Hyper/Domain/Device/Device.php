<?php

namespace Hyper\Domain\Device;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * Device
 *
 * @ORM\Table(name="devices")
 * @ORM\Entity(repositoryClass="Hyper\DomainBundle\Repository\DTDeviceRepository")
 * @ExclusionPolicy("all")
 */
class Device
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="string", options={"unsigned"=true})
     * @ORM\Id
     * @Expose
     */
    private $id;


    /**
     * @var string
     *
     * @ORM\Column(name="udid", type="string", length=100)
     * @Expose
     * @Assert\NotBlank(
     *     message = "entitlement.device.udid.not_blank"
     * )
     */
    private $udid;


    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=100)
     * @Expose
     * @Assert\NotBlank(
     *     message = "entitlement.device.name.not_blank"
     * )
     */
    private $name;


    /**
     * @var integer
     *
     * @ORM\Column(name="os", type="string", length=50)
     * @Assert\NotBlank(
     *     message = "entitlement.device.os.not_blank"
     * )
     * @Expose
     */
    private $os;

    /**
     * @var integer
     *
     * @ORM\Column(name="time_created", type="integer", options={"unsigned"=true})
     * @Assert\NotBlank(
     *     message = "entitlement.device.time_created.not_blank"
     * )
     * @Expose
     */
    private $timeCreated;

    public function __construct()
    {
        $this->id = uniqid('',true);
        $this->timeCreated = time();
    }






    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set udid
     *
     * @param string $udid
     * @return Device
     */
    public function setUDID($udid)
    {
        $this->udid = $udid;

        return $this;
    }

    /**
     * Get udid
     *
     * @return string
     */
    public function getUDID()
    {
        return $this->udid;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Device
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set os
     *
     * @param string $os
     * @return Device
     */
    public function setOS($os)
    {
        $this->os = $os;

        return $this;
    }

    /**
     * Get os
     *
     * @return string
     */
    public function getOS()
    {
        return $this->os;
    }





    /**
     * Set os
     *
     * @param string $timeCreated
     * @return Device
     */
    public function setTimeCreated($timeCreated)
    {
        $this->timeCreated = $timeCreated;

        return $this;
    }

    /**
     * Get os
     *
     * @return string
     */
    public function getTimeCreated()
    {
        return $this->timeCreated;
    }
}