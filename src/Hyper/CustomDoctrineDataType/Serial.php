<?php
namespace Hyper\CustomDoctrineDataType;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * My custom datatype.
 */
class Serial extends Type
{
    const TYPE = 'serial'; // modify to match your type name

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        // return the SQL used to create your column type. To create a portable column type, use the $platform.
        return self::TYPE;
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        // This is executed when the value is read from the database. Make your conversions here, optionally using the $platform.
        return self::TYPE;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        // This is executed when the value is written to the database. Make your conversions here, optionally using the $platform.
        return self::TYPE;
    }

    public function getName()
    {
        return self::TYPE; // modify to match your constant name
    }
}