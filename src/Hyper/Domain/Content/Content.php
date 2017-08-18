<?php

namespace Hyper\Domain\Content;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * Content
 *
 * @ORM\Table(name="contents")
 * @ORM\Entity(repositoryClass="Hyper\DomainBundle\Repository\Content\DTContentRepository")
 * @ExclusionPolicy("all")
 */
class Content
{
    /**
     * @var string
     * @ORM\Column(name="id", type="string")
     * @ORM\Id
     * @Expose
     */
    private $id;
    
    /**
     * @var string
     *
     * @ORM\Column(name="app_id", type="string",nullable=true)
     * @Expose
     */
    private $appId;

    /**
     * @ORM\ManyToOne(targetEntity="Hyper\Domain\Application\Application", fetch="EXTRA_LAZY", inversedBy="contents")
     * @ORM\JoinColumn()
     * @Expose
     */
    private $application;


    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string")
     * @Expose
     */
    private $title;
    
     /**
     * @var integer
     *
     * @ORM\Column(name="created", type="integer")
     * @Expose
     */
    private $created;  
    
    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="Hyper\Domain\Content\InCategoryContent", mappedBy="content", fetch="EXTRA_LAZY", cascade={"persist"})
     */
    private $inCategoryContents;
    
    public function __construct()
    {
        $this->id = uniqid('',true);
        $this->created = time();
    }
    

    /**
     * Set id
     *
     * @param string $id
     * @return Content
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
     * Set appId
     *
     * @param string $appId
     * @return Content
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
     * Set title
     *
     * @param string $title
     * @return Content
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set created
     *
     * @param integer $created
     * @return Content
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

    /**
     * Set application
     *
     * @param \Hyper\Domain\Application\Application $application
     * @return Content
     */
    public function setApplication(\Hyper\Domain\Application\Application $application = null)
    {
        $this->application = $application;

        return $this;
    }

    /**
     * Get application
     *
     * @return \Hyper\Domain\Application\Application 
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * Add inCategoryContents
     *
     * @param \Hyper\Domain\Content\InCategoryContent $inCategoryContents
     * @return Content
     */
    public function addInCategoryContent(\Hyper\Domain\Content\InCategoryContent $inCategoryContents)
    {
        $this->inCategoryContents[] = $inCategoryContents;

        return $this;
    }

    /**
     * Remove inCategoryContents
     *
     * @param \Hyper\Domain\Content\InCategoryContent $inCategoryContents
     */
    public function removeInCategoryContent(\Hyper\Domain\Content\InCategoryContent $inCategoryContents)
    {
        $this->inCategoryContents->removeElement($inCategoryContents);
    }

    /**
     * Get inCategoryContents
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getInCategoryContents()
    {
        return $this->inCategoryContents;
    }
}
