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
        'title'         => "",
        'scale'         => "", // 4 forms possible
        'projection'    => "",
        'author'        => array(), // array of arrays(<date>,<persons>)
        'flip'          => "",
        'cs'            => "", // coord system
        'stations'      => array(), // list of station names (to be plotted)
        'scetch'        => array(), // <filename> <x> <y>
        'walls'         => "",
        'station-names' => array(), // <prefix> <suffix> (like in centreline)
        'copyright'     => array()  // <date> <string>
    );
    
    
    /**
     * Basic data elements.
     * 
     * @var array
     */
    protected $_data = array(
        'join' => array(),
    );
    
    /**
     * Line objects of this scrap.
     * 
     * @var array of File_Therion_ScrapLine objects
     */
    protected $_lines = array();
    
    /**
     * Point objects of this scrap.
     * 
     * @var array of File_Therion_ScrapPoint objects
     */
    protected $_points = array();
    
    /**
     * Area objects of this scrap.
     * 
     * @var array of File_Therion_ScrapArea objects
     */
    protected $_areas = array();
    
    
    /**
     * Create a new therion Scrap object.
     *
     * @param string $id Name/ID of the scrap
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
            throw new InvalidArgumentException(
                "Invalid $line argument @1 passed type='"
                .gettype($firstLine)."'");
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
            throw new InvalidArgumentException(
                "Invalid $line argument @last passed type='"
                .gettype($lastLine)."'");
        }
        
        
        /*
         * Parsing contents
         */
        //
        // todo: implement parsing code
        //       with areas we should collect raw line references and add create
        //       the area once parsing is complete, this way we can a) check
        //       existence of references and b) add object-references to the area
    
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
    
    
    /**
     * Add an area definition to this scrap.
     * 
     */
    //public function addArea()
    //{
    //}
}

?>
