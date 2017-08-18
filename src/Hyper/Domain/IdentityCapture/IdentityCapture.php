<?php

namespace Hyper\Domain\IdentityCapture;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * IdentityCapture
 *
 * @ORM\Table(name="identity_capture")
 * @ORM\Entity(repositoryClass="Hyper\DomainBundle\Repository\IdentityCapture\DTIdentityCaptureRepository")
 */
class IdentityCapture
{
    /**
     * @var string
     *
     * @ORM\Column(name="device_id", type="string", nullable=false)
     * @ORM\Id
     */
    private $deviceId;

    /**
     * @var integer
     *
     * @ORM\Column(name="email", type="string", nullable=false)
     * @ORM\Id
     */
    private $email;

    public function setDeviceId($deviceId)
    {
        $this->deviceId = $deviceId;
        return $this;
    }

    public function getDeviceId()
    {
        return $this->deviceId;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function getEmail()
    {
        return $this->email;
    }
}