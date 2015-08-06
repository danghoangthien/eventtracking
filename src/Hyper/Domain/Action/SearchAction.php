<?php

namespace Hyper\Domain\Action;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * SearchAction
 *
 * @ORM\Table(name="search_actions")
 * @ORM\Entity(repositoryClass="Hyper\DomainBundle\Repository\Action\DTSearchActionRepository")
 * @ExclusionPolicy("all")
 */
class SearchAction
{
    /**
     * @ORM\OneToOne(targetEntity="Action")
     * @ORM\JoinColumn(name="id", referencedColumnName="id")
     * @ORM\Id
     */
    private $action;


    /**
     * @var string
     *
     * @ORM\Column(name="device_id", type="string")
     * @Expose
     */
    private $deviceId;


    /**
     * @var string
     *
     * @ORM\Column(name="application_id", type="string")
     * @Expose
     */
    private $applicationId;


    /**
     * @var string
     *
     * @ORM\Column(name="search_string", type="string")
     * @Expose
     */
    private $searchString;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="searched_time", type="integer")
     * @Expose
     */
    private $searchedTime;
    

    /**
     * @var integer
     *
     * @ORM\Column(name="created", type="integer")
     * @Expose
     */
    private $created;    
    
    
    
    
    public function __construct()
    {
        $this->created = time();
    }
    

    /**
     * Set deviceId
     *
     * @param string $deviceId
     * @return SearchAction
     */
    public function setDeviceId($deviceId)
    {
        $this->deviceId = $deviceId;

        return $this;
    }

    /**
     * Get deviceId
     *
     * @return string 
     */
    public function getDeviceId()
    {
        return $this->deviceId;
    }

    /**
     * Set applicationId
     *
     * @param string $applicationId
     * @return SearchAction
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
     * Set searchString
     *
     * @param string $searchString
     * @return SearchAction
     */
    public function setSearchString($searchString)
    {
        $this->searchString = $searchString;

        return $this;
    }

    /**
     * Get searchString
     *
     * @return string 
     */
    public function getSearchString()
    {
        return $this->searchString;
    }

    /**
     * Set searchedTime
     *
     * @param integer $searchedTime
     * @return SearchAction
     */
    public function setSearchedTime ($searchedTime)
    {
        $this->searchedTime = $searchedTime;

        return $this;
    }

    /**
     * Get searchedTime
     *
     * @return integer 
     */
    public function getSearchedTime()
    {
        return $this->searchedTime;
    }

    /**
     * Set created
     *
     * @param integer $created
     * @return SearchAction
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
     * Set action
     *
     * @param \Hyper\Domain\Action\Action $action
     * @return SearchAction
     */
    public function setAction(\Hyper\Domain\Action\Action $action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Get action
     *
     * @return \Hyper\Domain\Action\Action 
     */
    public function getAction()
    {
        return $this->action;
    }
}
