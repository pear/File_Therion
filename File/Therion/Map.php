<?php
/**
 * Therion cave map object class.
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
 * Class representing a therion map definition object.
 * 
 * A map is a collection of scraps or other maps to render togehter.
 *
 * @category   file
 * @package    File_Therion
 * @author     Benedikt Hallinger <beni@php.net>
 * @copyright  2016 Benedikt Hallinger
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @link       http://pear.php.net/package/File_Therion/
 */
class File_Therion_Map
    extends File_Therion_BasicObject
    implements Countable
{
    
    /**
     * Map name (ID)
     * 
     * @var array
     */
    protected $_name = null;
    
    /**
     * Map options (title, ...).
     * 
     * @var array assoc array
     */
    protected $_options = array(
        'title' => "",
    );
    
    
    /**
     * Basic data elements.
     * 
     * @var array  
     */
    protected $_data = array(
        // todo
    );
    
    
    /**
     * Create a new therion Map object.
     *
     * @param string $id Name/ID of the map
     * @todo Restrict naming convention, not all characters are allowed!
     */
    public function __construct($id, $options = array())
    {
        $this->_name = $id;
        $this->setOption($options);
    }
    
    
    /**
     * Parses given Therion_Line-objects into internal data structures.
     * 
     * @param array $lines File_Therion_Line objects forming a map
     * @return File_Therion_Map Map object
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
        
        $map = null; // constructed map
        
        // get first line and construct map hull object
        $firstLine = array_shift($lines);
        if (is_a($firstLine, 'File_Therion_Line')) {
            $flData = $firstLine->getDatafields();
            if (strtolower($flData[0]) == "map") {
                $map = new File_Therion_Map(
                    $flData[1], // id, mandatory
                    $firstLine->extractOptions()
                );
            } else {
                throw new File_Therion_SyntaxException(
                    "First map line is expected to contain map definition"
                );
            }
                
        } else {
            throw new InvalidArgumentException(
                "Invalid $line argument @1 passed type='"
                .gettype($firstLine)."'");
        }
        
        // Pop last last line and control that it was the end tag
        $lastLine  = array_pop($lines);
        if (is_a($lastLine, 'File_Therion_Line')) {
            $flData = $firstLine->getDatafields();
            if (!strtolower($flData[0]) == "endmap") {
                throw new File_Therion_SyntaxException(
                    "Last map line is expected to contain endmap definition"
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
        
        return $map;
        
    }
    
    
    
    /**
     * Count number of elements of this map (SPL Countable).
     *
     * @return int number of subsurveys
     */
    public function count()
    {
        return count($this->_data);
    }
    
    
}

?>
