<?php
namespace Hyper\EventBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Hyper\EventBundle\Annotations as HyperEvent;

/**
 * @MongoDB\Document(
 *     db="bklp",
 *     collection="transactions"
 * )
 */
class Transaction
{
    /**
     * @MongoDB\Id
     */
    protected $id;
    /**
     * @MongoDB\Field(name="hytid",type="string")
     */
    protected $hytid;
    /**
     * @MongoDB\Field(name="hypid",type="string")
     */
    protected $hypid;
    /**
     * @MongoDB\Field(name="event_time",type="string")
     * @HyperEvent\CsvMeta(field="Event Time")
     */
    protected $eventTime;
     /**
     * @MongoDB\Field(name="event_name",type="string")
     * @HyperEvent\CsvMeta(field="Event Name")
     */
    protected $eventName;
     /**
     * @MongoDB\Field(name="event_type",type="string")
     * @HyperEvent\CsvMeta(field="Event Type")
     */
    protected $eventType;
    /**
     * @MongoDB\Field(name="event_value",type="string")
     * @HyperEvent\CsvMeta(field="Event Value")
     */
    protected $eventValue;
     /**
     * @MongoDB\Field(name="currency",type="string")
     * @HyperEvent\CsvMeta(field="Currency")
     */
    protected $currency;
     /**
     * @MongoDB\Field(name="product_name",type="string")
     * @HyperEvent\CsvMeta(field="Product Name")
     */
    protected $productName;
     /**
     * @MongoDB\Field(name="product_category",type="string")
     * @HyperEvent\CsvMeta(field="Product Category")
     */
    protected $productCategory;

    /**
     * Get id
     *
     * @return id $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set hytid
     *
     * @param string $hytid
     * @return self
     */
    public function setHytid($hytid)
    {
        $this->hytid = $hytid;
        return $this;
    }

    /**
     * Get hytid
     *
     * @return string $hytid
     */
    public function getHytid()
    {
        return $this->hytid;
    }

    /**
     * Set hypid
     *
     * @param string $hypid
     * @return self
     */
    public function setHypid($hypid)
    {
        $this->hypid = $hypid;
        return $this;
    }

    /**
     * Get hypid
     *
     * @return string $hypid
     */
    public function getHypid()
    {
        return $this->hypid;
    }

    /**
     * Set eventTime
     *
     * @param string $eventTime
     * @return self
     */
    public function setEventTime($eventTime)
    {
        $this->eventTime = $eventTime;
        return $this;
    }

    /**
     * Get eventTime
     *
     * @return string $eventTime
     */
    public function getEventTime()
    {
        return $this->eventTime;
    }

    /**
     * Set eventName
     *
     * @param string $eventName
     * @return self
     */
    public function setEventName($eventName)
    {
        $this->eventName = $eventName;
        return $this;
    }

    /**
     * Get eventName
     *
     * @return string $eventName
     */
    public function getEventName()
    {
        return $this->eventName;
    }
    
    /**
     * Set eventType
     *
     * @param string $eventType
     * @return self
     */
    public function setEventType($eventType)
    {
        $this->eventType = $eventType;
        return $this;
    }

    /**
     * Get eventType
     *
     * @return string $eventType
     */
    public function getEventType()
    {
        return $this->eventType;
    }

    /**
     * Set eventValue
     *
     * @param string $eventValue
     * @return self
     */
    public function setEventValue($eventValue)
    {
        $this->eventValue = $eventValue;
        return $this;
    }

    /**
     * Get eventValue
     *
     * @return string $eventValue
     */
    public function getEventValue()
    {
        return $this->eventValue;
    }

    /**
     * Set currency
     *
     * @param string $currency
     * @return self
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
        return $this;
    }

    /**
     * Get currency
     *
     * @return string $currency
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Set productName
     *
     * @param string $productName
     * @return self
     */
    public function setProductName($productName)
    {
        $this->productName = $productName;
        return $this;
    }

    /**
     * Get productName
     *
     * @return string $productName
     */
    public function getProductName()
    {
        return $this->productName;
    }

    /**
     * Set productCategory
     *
     * @param string $productCategory
     * @return self
     */
    public function setProductCategory($productCategory)
    {
        $this->productCategory = $productCategory;
        return $this;
    }

    /**
     * Get productCategory
     *
     * @return string $productCategory
     */
    public function getProductCategory()
    {
        return $this->productCategory;
    }
}
