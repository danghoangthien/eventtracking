<?php
namespace Hyper\EventBundle\Service\FilterService\Condition\DataType;

use Hyper\EventBundle\Service\FilterService\Condition\DataType\DataTypeInterface;
use Hyper\EventBundle\Service\FilterService\Condition\DataType\DataType;
use Hyper\EventBundle\Service\FilterService\Condition\Query\AppTitleQuery;

class AppTitleDataType extends DataType implements DataTypeInterface
{

    private $connection;
    private $appTitleId;
    private $appId;
    private $platform;
    private $countryCode;

    public function __construct(
        $connection
        , $appTitleId
        , $appId
        , $platform
        , $countryCode
    ) {
        $this->connection = $connection;
        $this->appTitleId = $appTitleId;
        $this->appId = $appId;
        $this->platform = $platform;
        $this->countryCode = $countryCode;
        $this->assertData();
        $this->query = new AppTitleQuery(
                $connection
                , $this
        );
    }

    public function appTitleId()
    {
        return $this->appTitleId;
    }

    public function appId()
    {
        return $this->appId;
    }

    public function platform()
    {
        return $this->platform;
    }

    public function countryCode()
    {
        return $this->countryCode;
    }

    public function assertData()
    {
        if (empty($this->appTitleId)) {
            throw new \Exception('The appTitleId must be value.');
        }
        if (empty($this->appId)) {
            throw new \Exception('The appId must be value.');
        }

        if (!empty($this->platform) && !is_array($this->platform)) {
            throw new \Exception('The platform must be array.');
        }
        if (!empty($this->countryCode) && !is_array($this->countryCode)) {
            throw new \Exception('The countryCode must be array.');
        }
    }

    public function serialize()
    {
        return serialize([
            $this->appTitleId
            , $this->appId
            , $this->platform
            , $this->countryCode
        ]);
    }

    public function getQuery()
    {
        return $this->query;
    }

}