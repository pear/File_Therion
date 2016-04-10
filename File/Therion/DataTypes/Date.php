<?php
/**
 * Therion datatype class.
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
 * Class representing a therion date object.
 *
 * @category   file
 * @package    File_Therion_DataTypes
 * @author     Benedikt Hallinger <beni@php.net>
 * @copyright  2016 Benedikt Hallinger
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @link       http://pear.php.net/package/File_Therion/
 */
class File_Therion_Date
    implements File_Therion_DataType
{
    
    /**
     * Main date.
     * 
     * @var array string
     */
    protected $date = "";
    
    
    /**
     * Create a new therion date object.
     *
     * Basic format is: 'YYYY.MM.DD@HH:MM:SS.SS'
     * 
     * @param $dateString
     */
    public function __construct($dateString)
    {
        $this->setDate($dateString);
    }
    
    
    /**
     * Get string representation
     *
     * @return Therion compliant String of this date
     */
    public function toString()
    {
        return $this->date;
    }


    /**
     * Parse string content into this datatype.
     * 
     * When an interval was supplied, the parser returns an array
     * containing two date objects; the first one is the near end and the
     * second is the far point in time of the interval.
     * 
     * @param $string data to parse
     * @return array|File_Therion_Date crafted object or array with two objects.
     * @todo check syntax when in date interval mode
     */
    public static function parse($string)
    {
        $string = File_Therion_Line::unescape($string);
        $data = explode(' ', $string, 3);
        
        if (count($data) > 1) {
            return array(
                new File_Therion_Date($data[0]),
                // data[1] must contain a '-'
                new File_Therion_Date($data[2])
            );
        } else {
            return new File_Therion_Date($data[0]);
        }
    }
    
    /**
     * Set therion date.
     * 
     * Specification in a format:
     * - 'YYYY[.MM[.DD[@HH[:MM[:SS[.SS]]]]'
     * - or ‘-’ to leave a date unspecified.
     * 
     * @param string $date valid date string
     * @todo check snytax
     */
    public function setDate($date)
    {
        
        /* TODO: check syntax
            throw new File_Therion_SyntaxException(
              "Invalid Person dataType format: '$string'"
            );
        */
        
        $this->date = $date;
    }
    
}

?>