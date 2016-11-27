<?php
/**
 * Therion cave scrap area object class.
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
 * Class representing a scrap area definition object.
 *
 * This is a vector graphic element that is used to form a renderable cavemap.
 * Scrap lines may form the borders of an area in the map.
 * 
 * @category   file
 * @package    File_Therion
 * @author     Benedikt Hallinger <beni@php.net>
 * @copyright  2016 Benedikt Hallinger
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @link       http://pear.php.net/package/File_Therion/
 */
class File_Therion_ScrapArea
    extends File_Therion_BasicObject
{
    
    /**
     * Object options (title, ...).
     * 
     * @var array assoc array
     */
    protected $_options = array(
        // NO OPTIONS SUPPORT FOR AREA BY THERION!
    );
    
    /**
     * Basic data elements.
     * 
     * @var array  
     */
    protected $_data = array(
        'type'       => "",
        'place'      => "",
        'clip'       => "",
        'visibility' => ""
    );
    
    /**
     * Line references forming this area borders.
     * 
     * @var array of strings
     */
    protected $_lines = array();
    
    
    /**
     * Create a new therion ScrapPoint object.
     *
     * @param string $type Area type
     * @param array Ordered array of strings|File_Therion_ScrapLine objects
     * @todo Restrict naming convention, not all characters are allowed!
     */
    public function __construct($type, $lines = array())
    {
        $this->setType($type);
        
        // add lines as reference in order of array appearance
        for( $i=0; $i<count($lines); $i++) {
            $this->addLine($lines[$i]);
        }
    }
    
    
    /**
     * Parses given Therion_Line-objects into internal data structures.
     * 
     * Note that this will create new {@link File_Therion_ScrapLine}-objects
     * that are not the same as in the associated scrap.
     * 
     * @param array $lines File_Therion_Line objects forming this object
     * @return File_Therion_ScrapArea ScrapArea object
     * @throws InvalidArgumentException
     * @throws File_Therion_SyntaxException
     */
    public static function parse($lines)
    {
        if (!is_array($lines)) {
            throw new InvalidArgumentException(
                'parse(): Invalid $lines argument (expected array, seen:'
                .gettype($lines).')'
            );
        }
        
        $scraparea = null; // constructed object
        
        // get first line and construct hull object
        $firstLine = array_shift($lines);
        if (is_a($firstLine, 'File_Therion_Line')) {
            $flData = $firstLine->getDatafields();
            if (count($flData) != 2) { // expect exactly "area <type>"
                throw new File_Therion_SyntaxException(
                    "Unexpected arg-count at area definition");
            }
            
            if (strtolower($flData[0]) == "area") {
                $scraparea = new File_Therion_ScrapArea(
                    $flData[1] // type, mandatory
                    // lines will be parsed one by one below
                );
            } else {
                throw new File_Therion_SyntaxException(
                    "First scrap-area line is expected to contain area definition"
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
            if (!strtolower($flData[0]) == "endarea") {
                throw new File_Therion_SyntaxException(
                    "Last scrap-area line is expected to contain endarea definition"
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
        // split remaining lines into contextual ordering;
        // local lines are those describing this survey
        $orderedData = File_Therion::extractMultilineCMD($lines);
        
        // Walk results and try to parse it in local context.
        // we only expect local lines here.
        foreach ($orderedData as $type => $data) {
            switch ($type) {
                case 'LOCAL':
                    // walk each local line and parse it
                    foreach ($data as $line) {
                        if (!$line->isCommentOnly()) {
                            $lineData = $line->getDatafields();
                            if (count($lineData) > 1) {
                                throw new File_Therion_SyntaxException(
                                    "Line reference invalid "
                                    ."(more than one argument supplied)"
                                );
                            }
                            
                            $lineRef  = strtolower(array_shift($lineData));
                            $scraparea->addLine($lineRef);
                            
                        }
                    }
                break;
                
                default:
                    throw new File_Therion_SyntaxException(
                        "unsupported multiline command '$type'"
                    );
            }
        } 
        
        return $scraparea;
        
    }
    
    
    /**
     * Set type of area.
     * 
     * @param string
     */
    public function setType($type)
    {
        $this->setData('type', $type);
    }
    
    /**
     * Get area type.
     * 
     * @return string
     */
    public function getType()
    {
        return $this->getData('type');
    }
    
    
    /**
     * Add a scrap line reference to this area.
     * 
     * The optional index parameter allows to adjust the insertion
     * point; the line will be inserted at the index, pushing already
     * present content one line down (-1=end, 0=start, ...).
     * When replacing, the selected index will be replaced.
     * 
     * Instead of $index 0 and -1 you can use the strings 'start'/'end',
     * this will make your code more readable.
     * Using <code>addLine(..., $lln - 1)</code> will use logical line number
     * instead of the index (logical = index+1).
     * 
     * When adding a File_Therion_ScrapLine object, the scraps -id option will
     * be used as reference.
     * To resolve the line objects using a given scrap object, use
     * {@link dereferenceLines()}.
     * 
     * Note that for therion line ordering matters. See the therion
     * documentation for more details.
     * 
     * @param string|File_Therion_ScrapLine $line ScrapLine to reference
     * @param int  $index At which logical position to add (-1=end, 0=first line, ...)
     * @param bool $replace when true, the target line will be overwritten
     * @throws InvalidArgumentException
     * @throws OutOfBoundsException when requested index is not available
     */
    public function addLine($line, $index=-1, $replace=false)
    {
        // DEV Note: this code is copied from File_Therion class
        //           and slightly modified. if bugs occur, they probably
        //           also do in File_Therion!
        
        if (is_a($line, 'File_Therion_ScrapLine')) {
            $line = $line->getName();
        }
        
        if (!is_string($line) or strlen($line) == 0) {
            throw new InvalidArgumentException(
                "line argument must be valid string!");
        }
        
        // synonyms+checks for index
        if (is_string($index) && strtolower($index) == "start") {
            $index = 0;
        } elseif (
            (is_string($index) && strtolower($index) == "end")
            || $index === -1) {
            $index = (count($this->_lines)>0)? count($this->_lines) : 0;
        } else {
            if (!is_int($index)) {
                throw new InvalidArgumentException(
                    'addLine(): Invalid $index argument! '
                    ."int expected, or string 'start' or 'end'"
                );
            }
        }
        
        
        // Handle modification
        // Note: $index is the logical line number
        if ($index > count($this->_lines)) {
            // index is bigger than current lines-length: error
            throw new OutOfBoundsException(
                "index ".$index." is > ".count($this->_lines)."!");
        }
        
        if ($replace) {
            // REPLACE MODE:
            // $index is the index to replace or END+1
            if ($index == count($this->_lines) && $index > 0) {
                // index is next free index at the end:
                // replace last known index, that is index-1
                $index--;
            }
            
            if (!array_key_exists($index, $this->_lines)) {
                throw new OutOfBoundsException(
                    "replace-index ".$index." is invalid!");
            }
            
            // replace 1 elements at index with $line
            array_splice($this->_lines, $index, 1, array($line));
         
            
        } else {
            // ADD MODE:
            // $index is the index to add or END+1
       
            if ($index == count($this->_lines)) {
                // index is next free index at the end:
                // just push to internal array
                $this->_lines[] = $line;
                
            } else {
                // index is smaller: add to this index, downpushing content
                if (!array_key_exists($index, $this->_lines)) {
                    throw new OutOfBoundsException(
                        "add-index ".$index." is invalid!");
                }
                
                 // replace 0 elements (=splice in) at index with $line
                array_splice($this->_lines, $index, 0, array($line));
               
            }
            
        }
    }
    
    /**
     * Get referenced lines.
     *
     * @return array of string-referenced object id's
     */
    public function getLines()
    {
         return $this->_lines;
    }
     
     
    /**
     * Clear referenced lines from this area.
     */
    public function clearLines()
    {
         $this->_lines = array();
    }
    
    /**
     * Dereference line reference using given scrap.
     * 
     * This will try to get the line objects by reference out of the
     * scrap provided.
     * 
     * @param File_Therion_Scrap $scrap Scrap used for lookup of line references
     * @return array of File_Therion_ScrapLine objects
     * @throw File_Therion_SyntaxException when reference could not be resolved
     * @todo Implement me please
     */
    public function dereferenceLines(File_Therion_Scrap $scrap)
    {
        // TODO: Implement me
        //       - Get scrapLine objects from scrap
        //       - Then walk em and match by ID
        //       - throw SyntaxException on ref-errors
        throw new File_Therion_Exception(
            "dereferenceLines() not implemented yet");
    }
    
    /**
     * Options with area objects are not supported by therion.
     * 
     * @return Always throws File_Therion_SyntaxException when called.
     * @throws File_Therion_SyntaxException because options are not allowed.
     */
    public function setOption($option, $value=null) {
        throw new File_Therion_SyntaxException(
            "Options are not allowed with scrap areas!");
    }
    
    /**
     * Options with area objects are not supported by therion.
     * 
     * @return Always throws File_Therion_SyntaxException when called.
     * @throws File_Therion_SyntaxException because options are not allowed.
     */
    public function getOption($option) {
        throw new File_Therion_SyntaxException(
            "Options are not allowed with scrap areas!");
    }
        
    
}

?>