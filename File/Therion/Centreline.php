<?php
/**
 * Therion cave centreline object class.
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
 * Class representing a therion centreline object.
 * 
 * The centreline contains the shots of the survey.
 *
 * @category   file
 * @package    File_Therion
 * @author     Benedikt Hallinger <beni@php.net>
 * @copyright  2016 Benedikt Hallinger
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @link       http://pear.php.net/package/File_Therion/
 */
class File_Therion_Centreline
    extends File_Therion_AbstractObject
    implements Countable
{
    
    /**
     * Survey options (id, ...).
     * 
     * @var array assoc array
     */
    protected $_options = array(
        'id' => "",
    );
    
    
    /**
     * Basic data elements.
     * 
     * @var array  
     */
    protected $_metadata = array(
        'team'        => array(),
        'explo-team'  => array(),
        'date'        => "",
        'explo-date'  => "",
        'units'       => array(),
        'copyright'   => array(), // 0=year, 1=string
        'declination' => array(), // 0=value, 1=unit: 0.0 grad
    );
    
    /**
     * Centreline data definition.
     * 
     * This holds the data definition order of shot elements.
     * (eg "data normal from to length bearing gradient left right up down").
     * 
     * Index=0 is type, subsequent items define keyword
     *
     * @var array
     */
    protected $_shotDef = array();
    
    /**
     * Centreline shot definition.
     * 
     * This holds a associative array containing the shots.
     * Each shot is represented by an individual File_Therion_Shot object.
     * This gives access to extended data fields.
     *
     * @var array
     */
    protected $_shots = array();
    
    /**
     * Create a new therion centreline object.
     *
     * @param string $id Name of the survey
     * @todo Restrict naming convention, not all characters are allowed!
     */
    public function __construct($options = array())
    {
        $this->setOptions($options);
    }
    
    
    /**
     * Parses given Therion_Line-objects into internal data structures.
     * 
     * @param array $lines File_Therion_Line objects forming a centreline
     * @return File_Therion_Centreline Centreline object
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
        
        $centreline = null; // centreline constructed
        
        /*
         * Preparations
         */
        
        // get first line and construct centreline hull object
        $firstLine = array_shift($lines);
        if (is_a($firstLine, 'File_Therion_Line')) {
            $flData = $firstLine->getDatafields();
            if (preg_match('/^cent(re|er)line/i', $flData[0])) {
                $centreline = new File_Therion_Centreline(
                    $firstLine->extractOptions()
                );
            } else {
                throw new File_Therion_SyntaxException(
                 "First centreline line is expected to contain valid definition"
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
            if (!strtolower($flData[0]) == "endcentreline") {
                throw new File_Therion_SyntaxException(
                  "Last centreline line is expected to contain valid definition"
                );
            }
            
        } else {
            throw new PEAR_Exception("parse(): Invalid $line argument @last",
                new InvalidArgumentException("passed type='".gettype($lastLine)."'"));
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
        $mode = "normal"; // data mode: parse shot data and flags etc
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
                                    $dataMode = false;
                                break;
                                
                                case 'copyright':                           
                                case 'date':
                                case 'explo-date':
                                case 'units':
                                case 'declination':
                                    // just add these as arrays
                                    // todo: better handling of type syntax
                                    $dataMode = false;
                                    $centreline->setMetaData(array(
                                        $command => $lineData
                                        )
                                    );
                                break;
                                
                                case 'team':
                                    $dataMode = false;
                                    $p_str = $lineData[0];
                                    $p_obj = File_Therion_Person::parse($p_str);
                                    $centreline->addTeam($p_obj);                                    
                                break;
                                case 'explo-team':
                                    $dataMode = false;
                                    $p_str = $lineData[0];
                                    $p_obj = File_Therion_Person::parse($p_str);
                                    $centreline->addExploTeam($p_obj);                                    
                                break;
                                
                                
                                case 'station':
                                   // todo
                                   $dataMode = false;
                                break;
                                
                                case 'station-names':
                                   // todo
                                   $dataMode = false;
                                break;
                                
                                
                                case 'data':
                                    //data format for following shot data
                                   // todo
                                   $dataMode = true;
                                break;
                                
                                
                                
                                default:
                                    // not a valid command; see if in data mode.
                                    if ($dataMode) {
                                
                                        if ($command == 'flags') {
                                           // todo
                                           
                                        } elseif ($command == 'fix') {
                                           // todo
                                           
                                        } elseif ($command == 'extend') {
                                           // todo
                                           // ignore for now; will be an issue when
                                           // writing parsed data
                                           
                                        } else {
                                            // line data, as long as the count of fields
                                            // correspond to the definition
                                            
                                            // todo: parse shot
                                            // $centreline->_shots[] =
                                            //  new File_Therion_Shot(...);
                                        }
                                
                                } else {
                                    // not in data mode: rise exception
                                    throw new PEAR_Exception(
                                     "parse(): unsupported command '$command'");
                                 }
                            }
                        }
                    }
                break;
                
                default:
                    throw new PEAR_Exception("parse(): unsupported type '$type'");
            }
        } 
        
        return $centreline;
        
    }
    
    
    /**
     * Add a surveying team member.
     * 
     * @param File_Therion_Person team member
     */
    public function addTeam($person)
    {
        $this->_metadata['team'][] = $person;
    }
    
    /**
     * Add a team member which explored.
     * 
     * @param File_Therion_Person team member
     */
    public function addExploTeam($person)
    {
        $this->_metadata['explo-team'][] = $person;
    }
    
    /**
     * Count number of shots of this centreline (SPL Countable).
     *
     * @return int number of subsurveys
     */
    public function count()
    {
        return count($this->_shots);
    }
    
    
}

?>
