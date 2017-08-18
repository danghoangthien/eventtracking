<?php

namespace Hyper\Domain\InappeventConfig;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * InappeventConfig
 *
 * @ORM\Table(name="inappevent_configs")
 * @ORM\Entity(repositoryClass="Hyper\DomainBundle\Repository\InappeventConfig\DTInappeventConfigRepository")
 */
class InappeventConfig
{
    const TAG_AS_EMAIL_VALUE = 1;
    const TAG_AS_IAP_VALUE = 1;
    /**
     * @var string
     * @ORM\Column(name="id", type="string", options={"unsigned"=true})
     * @ORM\Id
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="app_id", type="string", length=255)
     */
    private $appId;

    /**
     * @var string
     * @ORM\Column(name="event_name", type="string", length=255)
     */
    private $eventName;

    /**
     * @var string
     * @ORM\Column(name="event_friendly_name", type="string", length=255, nullable=true)
     */
    private $eventFriendlyName;

    /**
     * @var string
     * @ORM\Column(name="tag_as_iap", type="string", length=255, nullable=true)
     */
    private $tagAsIap;

    /**
     * @var string
     * @ORM\Column(name="tag_as_email", type="string", length=255, nullable=true)
     */
    private $tagAsEmail;

    /**
     * @var string
     * @ORM\Column(name="color", type="string", length=255, nullable=true)
     */
    private $color;

    /**
     * @var string
     * @ORM\Column(name="icon", type="string", length=255, nullable=true)
     */
    private $icon;

    public function __construct()
    {
        $this->id = uniqid('',true);
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setAppId($appId)
    {
        $this->appId = $appId;
        return $this;
    }

    public function getAppId()
    {
        return $this->appId;
    }

    public function setEventName($eventName)
    {
        $this->eventName = $eventName;
        return $this;
    }

    public function getEventName()
    {
        return $this->eventName;
    }

    public function setEventFriendlyName($eventFriendlyName)
    {
        $this->eventFriendlyName = $eventFriendlyName;
        return $this;
    }

    public function getEventFriendlyName()
    {
        return $this->eventFriendlyName;
    }

    public function setTagAsIap($tagAsIap)
    {
        $this->tagAsIap = $tagAsIap;
        return $this;
    }

    public function getTagAsIap()
    {
        return $this->tagAsIap;
    }

    public function setTagAsEmail($tagAsEmail)
    {
        $this->tagAsEmail = $tagAsEmail;
        return $this;
    }

    public function getTagAsEmail()
    {
        return $this->tagAsEmail;
    }

    public function setColor($color)
    {
        $this->color = $color;
        return $this;
    }

    public function getColor()
    {
        return $this->color;
    }

    public function setIcon($icon)
    {
        $this->icon = $icon;
        return $this;
    }

    public function getIcon()
    {
        return $this->icon;
    }
}