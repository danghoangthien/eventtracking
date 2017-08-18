<?php

namespace Hyper\Domain\Item;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * InCategoryItem
 *
 * @ORM\Table(name="in_category_items")
 * @ORM\Entity(repositoryClass="Hyper\DomainBundle\Repository\Item\DTInCategoryItemRepository")
 * @ExclusionPolicy("all")
 */
class InCategoryItem
{
    /**
     * @var string
     * @ORM\Column(name="id", type="string")
     * @ORM\Id
     * @Expose
     */
    private $id;
    
    /**
     * @ORM\ManyToOne(targetEntity="Hyper\Domain\Category\Category", fetch="EXTRA_LAZY", inversedBy="inCategoryItems")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id")
     * @Expose
     */
    private $category;
    
    /**
     * @var string
     *
     * @ORM\Column(name="item_code", type="string")
     * @Expose
     */
    private $itemCode;
    
    public function __construct()
    {
        $this->id = uniqid('',true);
    }
    

    /**
     * Set id
     *
     * @param string $id
     * @return InCategoryItem
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
     * Set itemCode
     *
     * @param string $itemCode
     * @return InCategoryItem
     */
    public function setItemCode($itemCode)
    {
        $this->itemCode = $itemCode;

        return $this;
    }

    /**
     * Get itemCode
     *
     * @return string 
     */
    public function getItemCode()
    {
        return $this->itemCode;
    }

    /**
     * Set category
     *
     * @param \Hyper\Domain\Category\Category $category
     * @return InCategoryItem
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
}
