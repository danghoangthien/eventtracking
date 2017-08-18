<?php

namespace Hyper\Adops\WebBundle\Domain;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Util\Inflector;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
* @ORM\Entity(repositoryClass="Hyper\Adops\WebBundle\DomainBundle\Repository\DTApplicationRepository")
* @ORM\Table(name="adops_applications")
*
* @author Carl Pham <vanca.vnn@gmail.com>
*/
class AdopsApplication
{
    /**
     * @ORM\Column(type="string", name="id")
     * @ORM\Id
     */
    private $id;

    /**
     * @ORM\Column(name="app_name", type="string", length=255)
     *
     */
    private $appName;

    /**
     * @ORM\Column(name="app_id", type="string", length=255)
     *
     */
    private $appId;

    /**
     * @ORM\Column(name="platform", type="string", length=20)
     *
     */
    private $platform;

    public function __construct()
    {
        $this->id = uniqid('',true);
    }

    /**
     * Set id
     *
     * @param string $id
     * @return AdopsApplication
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
     * Set App Name
     *
     * @param string $appName
     * @return AdopsApplication
     */
    public function setAppName($appName)
    {
        $this->appName = $appName;
        return $this;
    }

    /**
     * Get App Name
     *
     * @return string
     */
    public function getAppName()
    {
        return $this->appName;
    }

    /**
     * Set App Id
     *
     * @param string $appId
     * @return AdopsApplication
     */
    public function setAppId($appId)
    {
        $this->appId = $appId;
        return $this;
    }

    /**
     * Get App Id
     *
     * @return string
     */
    public function getAppId()
    {
        return $this->appId;
    }

    /**
     * Set platform
     *
     * @param string $platform
     * @return AdopsApplication
     */
    public function setPlatform($platform)
    {
        $this->platform = $platform;
        return $this;
    }

    /**
     * Get Platform
     *
     * @return string
     */
    public function getPlatform()
    {
        return $this->platform;
    }
}