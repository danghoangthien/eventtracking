<?php

namespace Hyper\Domain\Application;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * ApplicationTitle
 *
 * @ORM\Table(name="applications_title")
 * @ORM\Entity(repositoryClass="Hyper\DomainBundle\Repository\Application\DTApplicationTitleRepository")
 * @ExclusionPolicy("all")
 */
class ApplicationTitle
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
     * @ORM\Column(name="title", type="string")
     * @Expose
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="s3_folder", type="string")
     * @Expose
     */
    private $s3Folder;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string")
     * @Expose
     */
    private $description;

     /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", nullable=true)
     * @Expose
     */
    private $status;

    public function __construct()
    {
        $this->id = uniqid('',true);
        $this->status = 0;
    }

    /**
     * Set id
     *
     * @param string $id
     * @return ApplicationTitle
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
     * Set title
     *
     * @param string $title
     * @return ApplicationTitle
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
     * Set s3Folder
     *
     * @param string $s3Folder
     * @return ApplicationTitle
     */
    public function setS3Folder($s3Folder)
    {
        $this->s3Folder = $s3Folder;

        return $this;
    }

    /**
     * Get s3Folder
     *
     * @return string
     */
    public function getS3Folder()
    {
        return $this->s3Folder;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return ApplicationTitle
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return ApplicationTitle
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }
}
