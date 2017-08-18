<?php

namespace Hyper\Domain\Application;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * ApplicationPlatform
 *
 * @ORM\Table(name="applications_platform")
 * @ORM\Entity(repositoryClass="Hyper\DomainBundle\Repository\Application\DTApplicationPlatformRepository")
 * @ExclusionPolicy("all")
 */
class ApplicationPlatform
{

    /**
     * @var string
     *
     * @ORM\Column(name="app_id", type="string")
     * @ORM\Id
     * @Expose
     */
    private $appId;

    /**
     * @ORM\ManyToOne(targetEntity="ApplicationTitle", inversedBy="ApplicationPlatform")
     * @ORM\JoinColumn(name="app_title_id", referencedColumnName="id")
     * @ORM\Id
     */
    private $appTitle;

    /**
     * @var integer
     *
     * @ORM\Column(name="last_activity", type="integer", nullable=true)
     * @Expose
     */
    private $lastActivity;

    /**
     * Set appId
     *
     * @param string $appId
     * @return ApplicationPlatform
     */
    public function setAppId($appId)
    {
        $this->appId = $appId;

        return $this;
    }

    /**
     * Get appId
     *
     * @return string
     */
    public function getAppId()
    {
        return $this->appId;
    }

    /**
     * Set appTitle
     *
     * @param \Hyper\Domain\Application\ApplicationTitle $appTitle
     * @return ApplicationPlatform
     */
    public function setAppTitle(\Hyper\Domain\Application\ApplicationTitle $appTitle)
    {
        $this->appTitle = $appTitle;

        return $this;
    }

    /**
     * Get appTitle
     *
     * @return \Hyper\Domain\Application\ApplicationTitle
     */
    public function getAppTitle()
    {
        return $this->appTitle;
    }

    /**
     * Set Last Activity
     *
     * @param int $lastActivity
     * @return ApplicationPlatform
     */
    public function setLastActivity($lastActivity)
    {
        $this->lastActivity = $lastActivity;

        return $this;
    }

    /**
     * Get Last Activity
     *
     * @return string
     */
    public function getLastActivity()
    {
        return $this->lastActivity;
    }
}
