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
        'elements' => array(),  // string names of references
        'mode'     => "" // map|scrap: for check in addElement()
    );
    
    
    /**
     * Create a new therion Map object.
     *
     * @param string $id Name/ID of the map
     */
    public function __construct($id, $options = array())
    {
        $this->setName($id);
        $this->setOption($options);
    }
    
    
    /**
     * Parses given Therion_Line-objects into internal data structures.
     * 
     * @param array $lines File_Therion_Line objects forming a map
     * @return File_Therion_Map Map object
     * @throws InvalidArgumentException
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
       // split remaining lines into contextual ordering;
        // local lines are those describing this survey
        $orderedData = File_Therion::extractMultilineCMD($lines);
        
        // Walk results and try to parse it in local context.
        // We delegate as much as possible, so we just honor commands
        // the local level knows about.
        // Other lines will be collected and given to a suitable parser.
        foreach ($orderedData as $type => $data) {
            switch ($type) {
                case 'LOCAL':
                    // walk each local line and parse it
                    foreach ($data as $line) {
                        if (!$line->isCommentOnly()) {
                            $lineData = $line->getDatafields();
                            $command  = strtolower(array_shift($lineData));
                            
                            switch ($command) {
                                case 'input':
                                    // ignore silently because this should be 
                                    // handled at the file level
                                break;
                                
                                default:
                                    // there are no commands. Every line
                                    // is threaten as reference name.
                                    if (count($lineData) > 0) {
                                        throw new File_Therion_SyntaxException(
                                            "too many args for map "
                                            .$map->getName()." reference "
                                            .$command
                                        );
                                    }
                                    $map->addElement($command);
 
                            }
                        }
                    }
                break;
                
                default:
                    throw new File_Therion_SyntaxException(
                        "unsupported multiline map command '$type'"
                    );
            }
        }
        
        return $map;
        
    }
    
    
    /**
     * Get name (id) of this map.
     * 
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }
    
    /**
     * Change name (id) of this map.
     * 
     * @return string
     * @todo implement parameter checks
     * @todo Restrict naming convention, not all characters are allowed!
     */
    public function setName($id)
    {
        $this->_name = $id;
    }
    
    /**
     * Add an (scrap or map) element reference to this map.
     * 
     * When adding a File_Therion_Scrap object, the scraps -id option will
     * be used as reference. The same is true for File_Therion_Map.
     * To resolve the line objects using a given scrap object, use
     * {@link dereferenceElements()}.
     * 
     * Note that Maps may contain scrap references of map references,
     * but not both! Therion will complain.
     * 
     * The special "break" string may be used to adjust level rendering
     * in the map output.
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
     * Note that for therion the ordering matters. See the therion
     * documentation for more details.
     * 
     * @param string|File_Therion_Scrap|File_Therion_Map $element reference
     * @param int  $index At which logical position to add (-1=end, 0=first line, ...)
     * @param bool $replace when true, the target line will be overwritten
     * @throws InvalidArgumentException
     * @throws File_Therion_SyntaxException
     * @throws OutOfBoundsException when requested index is not available
     */
    public function addElement($element, $index=-1, $replace=false)
    {
        // DEV Note: this code is copied from File_Therion class
        //           and slightly modified. if bugs occur, they probably
        //           also do in File_Therion!
                
        if (is_a($element, 'File_Therion_Scrap')
            || is_a($element, 'File_Therion_Map')) {
                
            if ($this->getData('mode')
                && get_class($element) != $this->getData('mode')) {
                    $c = get_class($element);
                    $what['File_Therion_Scrap'] = 'File_Therion_Map';
                    $what['File_Therion_Map']   = 'File_Therion_Scrap';
                    throw new File_Therion_SyntaxException(
                        "Map ".$this->getName()." already contains ".$what[$c]
                        ." references, but $c reference should be added!"
                    );
            } else {
                if (is_object($element)) {
                    $this->setData('mode', get_class($element));
                }
            }
            
            $element = $element->getName();
        }
        if (!is_string($element) or strlen($element) == 0) {
            throw new InvalidArgumentException(
                "element argument must be valid string!");
        }
        
        // synonyms+checks for index
        if (is_string($index) && strtolower($index) == "start") {
            $index = 0;
        } elseif (
            (is_string($index) && strtolower($index) == "end")
            || $index === -1) {
            $index = (count($this->_data['elements'])>0)? count($this->_data['elements']) : 0;
        } else {
            if (!is_int($index)) {
                throw new InvalidArgumentException(
                    'addLine(): Invalid $index argument! '
                    ."int expected, or string 'start' or 'end'"
                );
            }
        }
        
        
        // Handle modification
        // Note: $index is the logical element number
        if ($index > count($this->_data['elements'])) {
            // index is bigger than current element-length: error
            throw new OutOfBoundsException(
                "index ".$index." is > ".count($this->_data['elements'])."!");
        }
        
        if ($replace) {
            // REPLACE MODE:
            // $index is the index to replace or END+1
            if ($index == count($this->_data['elements']) && $index > 0) {
                // index is next free index at the end:
                // replace last known index, that is index-1
                $index--;
            }
            
            if (!array_key_exists($index, $this->_data['elements'])) {
                throw new OutOfBoundsException(
                    "replace-index ".$index." is invalid!");
            }
            
            // replace 1 elements at index with $element
            array_splice($this->_data['elements'], $index, 1, array($element));
         
            
        } else {
            // ADD MODE:
            // $index is the index to add or END+1
       
            if ($index == count($this->_data['elements'])) {
                // index is next free index at the end:
                // just push to internal array
                $this->_data['elements'][] = $element;
                
            } else {
                // index is smaller: add to this index, downpushing content
                if (!array_key_exists($index, $this->_data['elements'])) {
                    throw new OutOfBoundsException(
                        "add-index ".$index." is invalid!");
                }
                
                 // replace 0 elements (=splice in) at index with $line
                array_splice($this->_data['elements'], $index, 0, array($element));
               
            }
            
        }
    }
    
    /**
     * Get referenced elements.
     *
     * @return array of string-referenced object id's
     */
    public function getElements()
    {
         return $this->_data['elements'];
    }
     
     
    /**
     * Clear referenced elements from this map.
     */
    public function clearElements()
    {
         $this->_data['elements'] = array();
    }
    
    /**
     * Dereference element references using given survey.
     * 
     * This will try to get the line objects by reference out of the
     * scrap provided.
     * 
     * @param File_Therion_Survey $survey Scrap used for lookup of references
     * @return array of File_Therion_Scrap or File_Therion_Map objects
     * @throw File_Therion_SyntaxException when reference could not be resolved
     * @todo Implement me please
     */
    public function dereferenceElements(File_Therion_Survey $survey)
    {
        // TODO: Implement me
        throw new File_Therion_Exception(
            "dereferenceLines() not implemented yet");
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
