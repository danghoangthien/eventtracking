<?php

namespace Hyper\Domain\Currency;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * Currency
 *
 * @ORM\Table(name="currency")
 * @ORM\Entity(repositoryClass="Hyper\DomainBundle\Repository\Currency\DTCurrencyRepository")
 * @ExclusionPolicy("all")
 */
class Currency
{
    /**
     * @var string
     * @ORM\Column(name="id", type="string", length=255)
     * @ORM\Id
     * @Expose
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="timestamps", type="integer")
     * @Expose
     */
    private $timestamps;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @Expose
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="rate", type="float")
     * @Expose
     */
    private $rate;

    
    public function __construct()
    {
        $this->id = uniqid('',true);
    }
	
    /**
     * Set id
     *
     * @param string $id
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
     * Set timestamps
     *
     * @param integer $timestamps
     * @return Currency
     */
    public function setTimestamps($timestamps)
    {
        $this->timestamps = $timestamps;

        return $this;
    }

    /**
     * Get timestamps
     *
     * @return integer 
     */
    public function getTimestamps()
    {
        return $this->timestamps;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Currency
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set rate
     *
     * @param string $rate
     * @return Currency
     */
    public function setRate($rate)
    {
        $this->rate = $rate;

        return $this;
    }

    /**
     * Get rate
     *
     * @return string 
     */
    public function getRate()
    {
        return $this->rate;
    }
}
