<?php
/**
 * Therion grades object class.
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
 * Class representing a therion grade definition object.
 * 
 * A grade specification is a named standards deviation set defining
 * standard deviations for a grouped set of measurements.
 * A centreline can refer to one of this defined names to set
 * "sd" settings for several instruments at once while retaining
 * a centralized definition for all "sd"-settings.
 * For applying such a grade-definition, see
 * {@link File_Therion_Centreline::setGrade()}.
 *
 * @category   file
 * @package    File_Therion
 * @author     Benedikt Hallinger <beni@php.net>
 * @copyright  2016 Benedikt Hallinger
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @link       http://pear.php.net/package/File_Therion/
 * @todo support alias names for tape=length etc
 */
class File_Therion_Grade
    extends File_Therion_BasicObject
    implements countable
{
    /**
     * ID of this grade
     * 
     * @var string
     */
    protected $_id = "";
    
    /**
     * options (title, ...).
     * 
     * @var array assoc array
     */
    protected $_options = array(
        'title' => ""
    );
    
    
    /**
     * Basic data elements.
     * 
     * Array contains Unit-objects when defined for this grade
     * 
     * @var array
     * @todo support alias names
     */
    protected $_data = array(
        'length'   => null,
        'tape'     => null,
        'bearing'  => null,
        'compass'  => null,
        'gradient' => null,
        'clino'    => null,
        'counter'  => null,
        'depth'    => null,
        'x'        => null,
        'y'        => null,
        'z'        => null,
        'position' => null,
        'easting'  => null,
        'northing' => null,
        'dx'       => null,
        'dy'       => null,
        'dz'       => null,
        'altitude' => null
    );
    
    
    /**
     * Create a new therion Grade object.
     *
     * @param string $id Name/ID of the grade definition
     * @param array $options associative options array to set
     */
    public function __construct($id, $options = array())
    {
        $this->setName($id);
        $this->setOption($options);
    }
    
    /**
     * Get name (id) of this grade definition.
     * 
     * @return string
     */
    public function getName()
    {
        return $this->_id;
    }
    
    /**
     * Change name (id) of this grade definition.
     * 
     * To set a cleartext title, use {@link setOption('title', "...")} instead.
     * 
     * @param string
     * @todo implement parameter checks
     */
    public function setName($id)
    {
        if (!is_string($id)) {
            throw new InvalidArgumentException(
                "grade id/name must be a string "
                ."(".gettype($id)." given)"
            );
        }
        // todo: Check for therion keyword constraints?
        
        $this->_id = $id;
    }
    
    
    /**
     * Parses given Therion_Line-objects into internal data structures.
     * 
     * @param array $lines File_Therion_Line objects forming a grade definition
     * @return File_Therion_Grade Grade object
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
        
        $grade = null; // constructed map
        
        // get first line and construct grade hull object
        $firstLine = array_shift($lines);
        if (is_a($firstLine, 'File_Therion_Line')) {
            $flData = $firstLine->getDatafields();
            if (strtolower($flData[0]) == "grade") {
                $grade = new File_Therion_Grade(
                    $flData[1], // id, mandatory
                    $firstLine->extractOptions()
                );
            } else {
                throw new File_Therion_SyntaxException(
                    "First grade line is expected to contain grade definition"
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
            if (!strtolower($flData[0]) == "endgrade") {
                throw new File_Therion_SyntaxException(
                    "Last grade line is expected to contain endgrade definition"
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
                            
                            if (count($lineData) < 3) {
                                throw new File_Therion_SyntaxException(
                                    "grade definition line expects at least 3 ".
                                    " arguments, ".count($lineData)." given!"
                                );
                            }
                            
                            // generic grade syntax is:
                            //   [<quantity list> <value> <units>]
                           
                            // Pop the last two fields and craft Unit-Object;
                            // (this also checks syntax on units)
                            $sUnit  = array_pop($lineData);
                            $sValue = array_pop($lineData);
                            $unit = new File_Therion_Unit($sValue, $sUnit);
                            
                            // remaining lineData elements must be grade items
                            // (this checks logical validity)
                            $grade->setDefinition($lineData, $unit);
                            
                            // done with this grade definition line.
                            
                        }
                    }
                break;
                
                default:
                    throw new File_Therion_SyntaxException(
                        "unsupported multiline grade command '$type'"
                    );
            }
        }
        
        return $grade;
        
    }
    
    /**
     * Generate line content from this object.
     * 
     * @return array File_Therion_Line objects
     */
    public function toLines()
    {
        $baseIndent = "\t";
        $lines = array();
        
        /*
         * create header
         */
        $hdr = "grade ".$this->getName(); // start
        $hdr .= $this->getOptionsString(); // add options
        $lines[] = new File_Therion_Line($hdr, "", "");
        
        // header type comment
        // (this is a WORKAROUND implementation. Once object ordering is possible,
        //  this must be rewritten and handled well)
        // for now: just push the entire object stack, as it only contain comments.
        foreach ($this->objStack as $osi) {
            if (is_a($osi, 'File_Therion_Line')) {
                // add all contained lines as header comment
                $lines[] = $osi;
                $osi->setIndent($baseIndent);
            }
        }
        
        
        /*
         * Data part
         * todo: We may build up an aggregatet string array, so we can collect
         * repeated definitions for the same units in one line.
         */
        foreach ($this->_data as $quantity => $unit) {
            if (!is_null($unit)) {
                $str_line = $quantity." ".$unit->toString();
                $lines[] = new File_Therion_Line($str_line, "", $baseIndent);
            }
        }
        
        
        
        /*
         *  create footer
         */
        $lines[] = new File_Therion_Line("endgrade ".$this->getName(), "", "");
        
        
        return $lines;
    }
    
    
    /**
     * Set a definition element of this grade definition.
     * 
     * By using NULL as unit-object you can clear an existing definition.
     * The precision of therion grade definition is the same as for the 'sd' command:
     * 95.44% of readings are within the specified tolerance (2 S.D.)
     * <code>
     * // 95.44% of compass readings are within +-1.25 degrees (=2.5 =2 S.D.)
     * $grade->setDefinition("bearing", new File_Therion_Unit(1.25, "degrees"));
     * </code>
     * 
     * @param string|array $quantity What to speicify (string or array of keyword strings)
     * @param null|File_Therion_Unit $unit unit and how much of it (NULL clears existing units)
     * @throws InvalidArgumentException when quantity is not known
     * @throws File_Therion_SyntaxException when units mismatch occurs (e.g. 'tape' with angle-unit)
     * @todo implement logical units checking: not all units can be used with "tape" quantity for example
     */
    public function setDefinition($quantity, File_Therion_Unit $unit)
    {
        // When called for several items, add them one by one
        if (is_array($quantity)) {
            foreach ($quantity as $q) {
                $this->setDefinition($q, $unit);
            }
            return;
        }
        
        // single-item mode: check type existence
        $this->_verify('_data', $quantity, null, 'grade quantity');
        
        // check logical correct units for types
        // (check only if test definition exists as long as not all
        //  tests are specified. once this is done: remove this comment and the todo-tag above)
        $allowedTypes = array(
            'length'   => 'length',
            'tape'     => 'length',
            'bearing'  => 'angle',
            'compass'  => 'angle',
            'gradient' => 'angle',
            'clino'    => 'angle',
            // TODO: MORE checks neede!!!!
        );
        if (array_key_exists($quantity, $allowedTypes)
            && $unit->getClass() != $allowedTypes[$quantity]) {
            throw new File_Therion_SyntaxException(
                    "grade definition for $quantity must have unit type "
                    ."'length', '".$unit->getType()."'="
                    .$unit->getClass()." given!"
                );
        }

        
        // update definition
        $this->setData($quantity, $unit);
        
    }
    
    /**
     * Get the definition of an quantity.
     *
     * @param string $quantity Definition to return
     * @return null|File_Therion_Unit
     */
    public function getDefinition($quantity)
    {
        $this->_verify('_data', $quantity, null, 'grade quantity');
        return $this->getData($quantity);
    }
     
     
    /**
     * Clear all definitions from this grade definition.
     */
    public function clearDefinitions()
    {
        foreach (array_keys($this->_data) as $k) {
            $this->setDefinition($k, null);
        }
    }
    
    /**
     * Count valid definitions (SPL-Interface)
     * 
     * @return int
     */
    public function count()
    {
        $c = 0;
        foreach ($this->_data as $k => $v) {
            if (!is_null($v)) $c++;
        }
        return $c;
    }
    
    
}

?>