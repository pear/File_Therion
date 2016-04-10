<?php
/**
 * Therion datatype interface.
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
 * Interface defining basic dataType expected functions
 *
 * @category   file
 * @package    File_Therion_DataTypes
 * @author     Benedikt Hallinger <beni@php.net>
 * @copyright  2016 Benedikt Hallinger
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @link       http://pear.php.net/package/File_Therion/
 */
interface File_Therion_DataType
{
    /**
     * Get string representation
     *
     * @return Therion compliant String of this type
     */
    public function toString();


    /**
     * Parse string content into this datatype
     * 
     * @param $string data to parse
     * @return mixed crafted object
     */
    public static function parse($string);
    
    
}

?>