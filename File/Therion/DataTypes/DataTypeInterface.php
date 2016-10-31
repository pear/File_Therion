<?php
/**
 * Therion datatype.
 *
 * PHP version 5
 *
 * @category   file
 * @package    File_Therion_DataTypes
 * @author     Benedikt Hallinger <beni@php.net>
 * @copyright  2016 Benedikt Hallinger
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @link       http://pear.php.net/package/File_Therion/
 */

/**
 * Abstract class defining basic dataType expected functions
 *
 * @category   file
 * @package    File_Therion_DataTypes
 * @author     Benedikt Hallinger <beni@php.net>
 * @copyright  2016 Benedikt Hallinger
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @link       http://pear.php.net/package/File_Therion/
 */
abstract class File_Therion_DataType
{
    /**
     * Get string representation
     *
     * @return Therion compliant String of this type
     */
    public abstract function toString();


    /**
     * Parse string content into this datatype
     * 
     * @param $string data to parse
     * @return mixed crafted object
     */
    public static abstract function parse($string);
    
    /**
     * Magic __toString() method calls toString().
     */
    public function __toString()
    {
        return $this->toString();
    }
}

?>