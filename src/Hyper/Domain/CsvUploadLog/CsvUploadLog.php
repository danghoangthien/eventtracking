<?php

namespace Hyper\Domain\CsvUploadLog;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Util\Inflector;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;


/**
* @ORM\Entity(repositoryClass="Hyper\Domain\CsvUploadLog\DTCsvUploadLogRepository")
* @ORM\Table(name="event_csvupload_logs")
*
* @author Carl Pham <vanca.vnn@gmail.com>
*/
class CsvUploadLog
{
    /**
     * @ORM\Column(type="string", name="id")
     * @ORM\Id
     */
    private $id;

    /**
     * @ORM\Column(name="total_csv_row", type="integer", nullable=true)
     *
     */
    private $totalCsvRow;

    /**
     * @ORM\Column(name="total_row_uploaded", type="integer", nullable=true)
     *
     */
    private $totalRowUploaded;
    
    /**
     * @ORM\Column(name="start_time", type="integer")
     *
     */
    private $startTime;
    
    /**
     * @ORM\Column(name="end_time", type="integer", nullable=true)
     *
     */
    private $endTime;
    
    /**
     * @ORM\Column(name="file_size", type="integer", nullable=true)
     * Unit is byte
     */
    private $fileSize;
    
    /**
     * @ORM\Column(name="detail", type="string", length=255)
     *
     */
    private $detail;

    public function __construct()
    {
        $this->id = uniqid('',true);
    }

    /**
     * Set id
     *
     * @param string $id
     * @return CsvUpload
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
     * Set Total CSV Row
     *
     * @param integer $totalCsvRow
     * @return CsvUpload
     */
    public function setTotalCsvRow($totalCsvRow)
    {
        $this->totalCsvRow = $totalCsvRow;
        return $this;
    }

    /**
     * Get Total CSV Row
     *
     * @return integer
     */
    public function getTotalCsvRow()
    {
        return $this->totalCsvRow;
    }

    /**
     * Set Total Row Uploaded
     *
     * @param integer $totalRowUploaded
     * @return CsvUpload
     */
    public function setTotalRowUploaded($totalRowUploaded)
    {
        $this->totalRowUploaded = $totalRowUploaded;
        return $this;
    }

    /**
     * Get Total Row Uploaded
     *
     * @return integer
     */
    public function getTotalRowUploaded()
    {
        return $this->totalRowUploaded;
    }

    /**
     * Set Start Time
     *
     * @param integer $startTime
     * @return CsvUpload
     */
    public function setStartTime($startTime)
    {
        $this->startTime = $startTime;
        return $this;
    }

    /**
     * Get Start Time
     *
     * @return integer
     */
    public function getStartTime()
    {
        return $this->startTime;
    }
    
    /**
     * Set End Time
     *
     * @param integer $endTime
     * @return CsvUpload
     */
    public function setEndTime($endTime)
    {
        $this->endTime = $endTime;
        return $this;
    }

    /**
     * Get End Time
     *
     * @return integer
     */
    public function getEndTime()
    {
        return $this->endTime;
    }
    
    /**
     * Set File Size
     *
     * @param integer $fileSize
     * @return CsvUpload
     */
    public function setFileSize($fileSize)
    {
        $this->fileSize = $fileSize;
        return $this;
    }

    /**
     * Get File Size
     *
     * @return integer
     */
    public function getFileSize()
    {
        return $this->fileSize;
    }
    
    /**
     * Set Detail
     *
     * @param string $detail
     * @return CsvUpload
     */
    public function setDetail($detail)
    {
        $this->detail = $detail;
        return $this;
    }

    /**
     * Get Detail
     *
     * @return string
     */
    public function getDetail()
    {
        return $this->detail;
    }

}