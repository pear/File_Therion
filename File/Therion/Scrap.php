<?php
/**
 * Therion cave scrap object class.
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
 * Class representing a therion scrap object.
 * 
 * A scrap is a digital vectorized sketch with enriched cave data.
 *
 * @category   file
 * @package    File_Therion
 * @author     Benedikt Hallinger <beni@php.net>
 * @copyright  2016 Benedikt Hallinger
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @link       http://pear.php.net/package/File_Therion/
 */
class File_Therion_Scrap
    extends File_Therion_BasicObject
    implements Countable
{
    
    /**
     * Scrap name (ID)
     * 
     * @var array
     */
    protected $_name = null;
    
    /**
     * Scrap options (title, ...).
     * 
     * @var array assoc array
     */
    protected $_options = array(
        //todo: 'title' => "",
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
     * @param array $lines File_Therion_Line objects forming a scrap
     * @return File_Therion_Scrap Scrap object
     * @throws PEAR_Exception with wrapped lower level exception
     * @todo implement me
     */
    public static function parse($lines)
    {
        if (!is_array($lines)) {
            throw new PEAR_Exception(
                'parse(): Invalid $lines argument (expected array, seen:'
                .gettype($lines).')'
            );
        }
        
        $scrap = null; // constructed scrap
        
        // get first line and construct scrap hull object
        $firstLine = array_shift($lines);
        if (is_a($firstLine, 'File_Therion_Line')) {
            $flData = $firstLine->getDatafields();
            if (strtolower($flData[0]) == "scrap") {
                $scrap = new File_Therion_Scrap(
                    $flData[1], // id, mandatory
                    $firstLine->extractOptions()
                );
            } else {
                throw new File_Therion_SyntaxException(
                    "First scrap line is expected to contain scrap definition"
                );
            }
                
        } else {
            throw new PEAR_Exception("parse(): Invalid $line argument @1",
                new InvalidArgumentException("passed type='".gettype($firstLine)."'"));
        }
        
        // Pop last last line and control that it was the end tag
        $lastLine  = array_pop($lines);
        if (is_a($lastLine, 'File_Therion_Line')) {
            $flData = $firstLine->getDatafields();
            if (!strtolower($flData[0]) == "endscrap") {
                throw new File_Therion_SyntaxException(
                    "Last scrap line is expected to contain endscrap definition"
                );
            }
            
        } else {
            throw new PEAR_Exception("parse(): Invalid $line argument @last",
                new InvalidArgumentException("passed type='".gettype($lastLine)."'"));
        }
        
        
        /*
         * Parsing contents
         */
        //
        // todo: implement parsing code
        //
        
        return $scrap;
        
    }
    
    
    
    /**
     * Count number of elements of this scrap (SPL Countable).
     *
     * @return int number of elements
     */
    public function count()
    {
        return count($this->_data);
    }
    
    
}

?>
