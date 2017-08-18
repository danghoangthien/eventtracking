<?php

namespace Hyper\Domain\Device;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * DevicePresetFilter
 *
 * @ORM\Table(name="device_preset_filter")
 * @ORM\Entity(repositoryClass="Hyper\DomainBundle\Repository\Device\DTDevicePresetFilterRepository")
 * @ExclusionPolicy("all")
 */
class DevicePresetFilter
{
    const DERIVED_BY_QUERY = 0;
    const ADDED_MANUALLY = 1;
    const REMOVED_MANUALLY = -1;

    /**
     * @var string
     * @ORM\Column(name="device_id", type="string")
     * @ORM\Id
     * @Expose
     */
    private $deviceId;

    /**
     * @var string
     * @ORM\Column(name="preset_filter_id", type="string")
     * @ORM\Id
     * @Expose
     */
    private $presetFilterId;

    /**
     * @var integer
     *
     * status of the identity,determine if device added or removed to card manually or derived by audience card query
     * 0 : derived by audience card query
     * 1 : added manually by user, not derived by audience card query
     * -1: removed manually by user,might or might not derived by audience card query
     *
     * @ORM\Column(name="status", type="integer", options={"default" = 0})
     * @Expose
     */
    private $status;

    public function setDeviceId($deviceId)
    {
        $this->deviceId = $deviceId;
    }

    public function getDeviceId()
    {
        return $this->deviceId;
    }

    public function setPresetFilterId($presetFilterId)
    {
        $this->presetFilterId = $presetFilterId;
    }

    public function getPresetFilterId()
    {
        return $this->presetFilterId;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function getStatus($status)
    {
        return $this->status;
    }

}
