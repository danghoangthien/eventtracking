<?php

namespace Hyper\Domain\Action;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * Action
 *
 * @ORM\Table(name="actions")
 * @ORM\Entity(repositoryClass="Hyper\DomainBundle\Repository\Action\DTActionRepository")
 * @ExclusionPolicy("all")
 */
class Action
{
    /**
     * @var string
     * @ORM\Column(name="id", type="string")
     * @ORM\Id
     * @Expose
     */
    private $id;


    /**
     * @ORM\ManyToOne(targetEntity="Hyper\Domain\Device\Device", fetch="EXTRA_LAZY", inversedBy="identities")
     * @ORM\JoinColumn()
     * @Expose
     */
    private $device;


    /**
     * @var string
     *
     * @ORM\Column(name="application_id", type="string")
     * @Expose
     */
    private $applicationId;


    /**
     * @var integer
     *
     * @ORM\Column(name="action_type", type="integer")
     * @Expose
     */
    private $actionType;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="behaviour_id", type="integer")
     * @Expose
     */
    private $behaviourId;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="happened_at", type="integer")
     * @Expose
     */
    private $happenedAt;

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
     * @return Action
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
     * Set applicationId
     *
     * @param string $applicationId
     * @return Action
     */
    public function setApplicationId($applicationId)
    {
        $this->applicationId = $applicationId;

        return $this;
    }

    /**
     * Get applicationId
     *
     * @return string 
     */
    public function getApplicationId()
    {
        return $this->applicationId;
    }

    /**
     * Set actionType
     *
     * @param integer $actionType
     * @return Action
     */
    public function setActionType ($actionType)
    {
        $this->actionType = $actionType;

        return $this;
    }

    /**
     * Get actionType
     *
     * @return integer 
     */
    public function getActionType()
    {
        return $this->actionType;
    }

    /**
     * Set behaviourId
     *
     * @param integer $behaviourId
     * @return Action
     */
    public function setBehaviourId ($behaviourId)
    {
        $this->behaviourId = $behaviourId;

        return $this;
    }

    /**
     * Get behaviourId
     *
     * @return integer 
     */
    public function getBehaviourId()
    {
        return $this->behaviourId;
    }

    /**
     * Set happenedAt
     *
     * @param integer $happenedAt
     * @return Action
     */
    public function setHappenedAt ($happenedAt)
    {
        $this->happenedAt = $happenedAt;

        return $this;
    }

    /**
     * Get happenedAt
     *
     * @return integer 
     */
    public function getHappenedAt()
    {
        return $this->happenedAt;
    }

    /**
     * Set created
     *
     * @param integer $created
     * @return Action
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
     * Set device
     *
     * @param \Hyper\Domain\Device\Device $device
     * @return Action
     */
    public function setDevice(\Hyper\Domain\Device\Device $device = null)
    {
        $this->device = $device;

        return $this;
    }

    /**
     * Get device
     *
     * @return \Hyper\Domain\Device\Device 
     */
    public function getDevice()
    {
        return $this->device;
    }
}
