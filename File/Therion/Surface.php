<?php
/**
 * Therion cave surface object class.
 *
 * PHP version 5
 *
 * @category   file
 * @package    File_Therion
 * @author     Benedikt Hallinger <beni@php.net>
 * @copyright  2016 Benedikt Hallinger
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @link       http://pear.php.net/package/File_Therion/
 */

/**
 * Class representing a therion surface object.
 * 
 * A surface object holds information of the surface around a cave.
 *
 * @category   file
 * @package    File_Therion
 * @author     Benedikt Hallinger <beni@php.net>
 * @copyright  2016 Benedikt Hallinger
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @link       http://pear.php.net/package/File_Therion/
 */
class File_Therion_Surface
    extends File_Therion_BasicObject
    implements Countable
{
    
    /**
     * Surface options (title, ...).
     * 
     * @var array assoc array
     */
    protected $_options = array(
        // todo unchecked
    );
    
    
    /**
     * Basic data elements.
     * 
     * @var array  
     */
    protected $_data = array(
        'bitmap' => "",
        'grid'   => array(),
        'data'   => array()
    );
    
    
    /**
     * Create a new therion surface object.
     */
    public function __construct()
    {
        // nothing to do here
    }
    
    
    /**
     * Parses given Therion_Line-objects into internal data structures.
     * 
     * @param array $lines File_Therion_Line objects forming a surface
     * @return File_Therion_Surface Surface object
     * @throws InvalidArgumentException
     * @todo implement me
     */
    public static function parse($lines)
    {
        if (!is_array($lines)) {
            throw new InvalidArgumentException(
                'parse(): Invalid $lines argument (expected array, seen:'
                .gettype($lines).')'
            );
        }
        
        $surface = null; // constructed surface
        
        // get first line and construct surface hull object
        $firstLine = array_shift($lines);
        if (is_a($firstLine, 'File_Therion_Line')) {
            $flData = $firstLine->getDatafields();
            if (strtolower($flData[0]) == "surface") {
                $surface = new File_Therion_Surface();
            } else {
                throw new File_Therion_SyntaxException(
                    "First surface line is expected to contain surface definition"
                );
            }
                
        } else {
            throw new InvalidArgumentException(
                "Invalid $line argument @1; passed type='"
                .gettype($firstLine)."'");
        }
        
        // Pop last last line and control that it was the end tag
        $lastLine  = array_pop($lines);
        if (is_a($lastLine, 'File_Therion_Line')) {
            $flData = $firstLine->getDatafields();
            if (!strtolower($flData[0]) == "endmap") {
                throw new File_Therion_SyntaxException(
                    "Last surface line is expected to contain endsurface definition"
                );
            }
            
        } else {
            throw new InvalidArgumentException(
                "Invalid $line argument @last passed type='"
                .gettype($lastLine)."'");
        }
        
        
        /*
         * Parsing contents
         */
        //
        // todo: implement parsing code
        //
        
        return $surface;
        
    }
    
    
    
    /**
     * Count number of data elements (SPL Countable).
     *
     * @return int number of data elements
     */
    public function count()
    {
        return count($this->_data['data']);
    }
    
    
}

?>
