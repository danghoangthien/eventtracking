<?php

namespace Hyper\Domain\Setting;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * Setting
 *
 * @ORM\Table(name="settings")
 * @ORM\Entity(repositoryClass="Hyper\DomainBundle\Repository\Setting\DTSettingRepository")
 * @ExclusionPolicy("all")
 */
class Setting
{
    const PRE_EVENT_HANDLING_TYPE_KEY = 'pre_event_handling';
    const EVENT_HANDLING_TYPE_KEY = 'event_handling';
    const POST_EVENT_HANDLING_TYPE_KEY = 'post_event_handling';
    const IDENTITY_CAPTURE_HANDLING_TYPE_KEY = 'identity_capture_handling';
    const STATUS_START_VALUE = 'start';
    const STATUS_STOP_VALUE = 'stop';
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
     * @ORM\Column(name="value", type="string", length=65535)
     * @Expose
     */
    private $value;

    /**
     * @var integer
     *
     * @ORM\Column(name="created", type="integer")
     * @Expose
     */
    private $created;

    public function __construct()
    {
        $this->id = uniqid('',true);
        $this->created = time();
    }


    /**
     * Set id
     *
     * @param string $id
     * @return Setting
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
     * Set key
     *
     * @param string $key
     * @return Setting
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
     * Set value
     *
     * @param string $value
     * @return Setting
     */
    public function setValue($value)
    {
        $this->value = serialize($value);

        return $this;
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getValue()
    {
        return unserialize($this->value);
    }

    /**
     * Set created
     *
     * @param integer $created
     * @return Setting
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

}
