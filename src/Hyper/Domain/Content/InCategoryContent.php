<?php

namespace Hyper\Domain\Content;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * InCategorycontent
 *
 * @ORM\Table(name="in_category_contents")
 * @ORM\Entity(repositoryClass="Hyper\DomainBundle\Repository\Content\DTInCategoryContentRepository")
 * @ExclusionPolicy("all")
 */
class InCategoryContent
{
    /**
     * @var string
     * @ORM\Column(name="id", type="string")
     * @ORM\Id
     * @Expose
     */
    private $id;
    
    /**
     * @ORM\ManyToOne(targetEntity="Hyper\Domain\Category\Category", fetch="EXTRA_LAZY", inversedBy="inCategoryContents")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id")
     * @Expose
     */
    private $category;
    
    /**
     * @ORM\ManyToOne(targetEntity="Hyper\Domain\Content\Content", fetch="EXTRA_LAZY", inversedBy="inCategoryContents")
     * @ORM\JoinColumn(name="content_id", referencedColumnName="id")
     * @Expose
     */
    private $content;
    
    public function __construct()
    {
        $this->id = uniqid('',true);
    }
     

    /**
     * Set id
     *
     * @param string $id
     * @return InCategoryContent
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
     * Set category
     *
     * @param \Hyper\Domain\Category\Category $category
     * @return InCategoryContent
     */
    public function setCategory(\Hyper\Domain\Category\Category $category = null)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category
     *
     * @return \Hyper\Domain\Category\Category 
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set content
     *
     * @param \Hyper\Domain\Content\Content $content
     * @return InCategoryContent
     */
    public function setContent(\Hyper\Domain\Content\Content $content = null)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return \Hyper\Domain\Content\Content 
     */
    public function getContent()
    {
        return $this->content;
    }
}
