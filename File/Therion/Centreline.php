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
    extends File_Therion_BasicObject
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
    protected $_data = array(
        'author'      => array(), // 0=year, 1=Person
        'copyright'   => array(), // 0=year, 1=string
        'title'       => "",
        'date'        => array(),
        'explo-date'  => array(),
        'units'       => array(),
        'instrument'  => array(), // assoc: [<quantity>]=<description>
        'infer'       => array(), // assoc: [<what>]=<boolean>
        'declination' => array(), // 0=value, 1=unit; eg. (0.0 grad)
        'grid-angle'  => array(), // (<value> <units>)
        'sd'          => array(), // assoc: [<quantity>]=(<value> <units>)
        'units'       => array(), // assoc: [<quantity>]=(<factor> <units>)
        'station-names' => array("",""), // <prefix> <postfix>
    );
    
    /**
     * Team members (surveying persons).
     * 
     * Each array item is an assoc array containing:
     * - key='persons'  = File_Therion_Person object
     * - key='roles'    = array of strings with roles
     * 
     * @var array
     */
    protected $_team = array();
    
    /**
     * Explo-Team members (exploring persons).
     * 
     * @var array with File_Therion_Person objects
     */
    protected $_exploteam = array();
    
    /**
     * Centreline data definition.
     * 
     * This holds the data definition order of shot elements.
     * (eg "data normal from to length bearing gradient left right up down").
     * 
     * array is associative:
     * - key 'style' is "normal", "diving", etc
     * - key 'order' is "left", "right", "up", etc.
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
     * This gives access to extended data fields like flags.
     *
     * @var array with File_Therion_Shot objects.
     */
    protected $_shots = array();
    
    /**
     * Centreline stations.
     * 
     * Stations may alter some of the shots or introduce supplementary data.
     *
     * @var array with File_Therion_Station objects.
     */
    protected $_stations = array();
    
    /**
     * Create a new therion centreline object.
     *
     * @param array $options Optional associative options array
     * @todo Restrict naming convention, not all characters are allowed!
     */
    public function __construct(array $options = array())
    {
        $this->setOption($options);
    }
    
    
    /**
     * Parses given Therion_Line-objects into internal data structures.
     * 
     * @param array $lines File_Therion_Line objects forming a centreline
     * @return File_Therion_Centreline Centreline object
     * @throws InvalidArgumentException
     * @throws File_Therion_SyntaxException
     * @todo implement me
     */
    public static function parse($lines)
    {
        if (!is_array($lines)) {
            throw new InvalidArgumentException(
                'Invalid $lines argument (expected array, seen:'
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
            throw new InvalidArgumentException(
                "Invalid $line argument @1; passed type='"
                .gettype($firstLine)."'");
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
            throw new InvalidArgumentException(
                "Invalid $line argument @last; passed type='"
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
        $cur_flags = array(
            'splay'       => false,
            'duplicate'   => false,
            'surface'     => false,
            'approximate' => false,
        );
        $lastSeenDatadef = false;
        $lastSeenStyle   = false;
        $lastSeenUnits   = false;
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
                                
                                case 'date':
                                    $pd = File_Therion_Date::parse($lineData[0]);
                                    $centreline->setDate($pd);
                                break;
                                case 'explo-date':
                                    $pd = File_Therion_Date::parse($lineData[0]);
                                    $centreline->setExploDate($pd);
                                break;
                                
                                case 'copyright':
                                case 'declination':
                                    // just add these as arrays
                                    // todo: better handling of type syntax
                                    $centreline->setData($command, $lineData);
                                break;
                                
                                case 'team':
                                    // parse first item as person,
                                    // add remaining stuff as roles (if any)
                                    $p_str = array_shift($lineData);
                                    $p_obj = File_Therion_Person::parse($p_str);
                                    $centreline->addTeam($p_obj, $lineData);                                    
                                break;
                                case 'explo-team':
                                    $p_str = $lineData[0];
                                    // todo: syntax error if more than 1 element
                                    $p_obj = File_Therion_Person::parse($p_str);
                                    $centreline->addExploTeam($p_obj);                                    
                                break;
                                
                                
                                case 'station':
                                   // todo
                                break;
                                
                                case 'station-names':
                                    $centreline->setStationNames(
                                        $lineData[0], $lineData[1]
                                    );
                                break;
                                
                                case 'fix':
                                   // todo
                                break;
                                
                                case 'flags':
                                    // set flags for following shots
                                    // $flag=="not" activates "false"
                                    $state = true;
                                    foreach ($lineData as $flag) {
                                        if ($flag == "approx") { // expand alias
                                            $flag = "approximate";
                                        }
                                        if (array_key_exists($flag, $cur_flags)) {
                                           // change flag state
                                           $cur_flags[$flag] = $state;
                                        }
                                        // toggle for following flag
                                        // (all but "not" reset the state->true)
                                        $state = ($flag==="not")? false: true;
                                    }
                                break;
                                
                                case 'extend':
                                    // todo
                                break;
                                
                                
                                case 'data':
                                    // data format for following shot data
                                    $lastSeenStyle   = array_shift($lineData);
                                    $lastSeenDatadef = $lineData;
                                break;
                                
                                case 'units':
                                    // unit definition for following shot data
                                    // units <quantity list> [<factor>] <units>
                                    
                                    // parse "compass clino grads" into array
                                    // todo: this is rather crude but should
                                    //       work in most basic cases where just
                                    //       one unit and no factor was given
                                    for ($u=0; $u<count($lineData)-1; $u++) {
                                        $type = $lineData[$u];
                                        $unit = $lineData[count($lineData)-1];
                                        $lastSeenUnits[$type] = $unit;
                                    }
                                break;
                                
                                
                                
                                default:
                                    // not a valid command!
                                    
                                    if ($lastSeenStyle) {
                                        // $lastSeenStyle signals that we had
                                        // a data-definition in the centreline:
                                        // see if we can successfully parse
                                        // a shot object. This will raise an
                                        // exception if syntax fails, which
                                        // is desired in this case.
                                        array_unshift($lineData, $command); //readd
                                        $shot = File_Therion_Shot::parse(
                                            $lineData,
                                            $lastSeenDatadef,
                                            $lastSeenUnits
                                        );
                                        
                                        // set reading style of shot
                                        $shot->setStyle($lastSeenStyle);
                                        
                                        // adjust shot flags according to
                                        // current defined flag states
                                        foreach ($cur_flags as $fn=>$fv) {
                                            $shot->setFlag($fn, $fv);
                                        }
                                   
                                        // add the shot to centreline
                                        $centreline->addShot($shot);
                                
                                    } else {
                                        // not in data mode: rise exception
                                        throw new File_Therion_Exception(
                                         "unsupported command '$command'");
                                    }
                            }
                        }
                    }
                break;
                
                default:
                    throw new File_Therion_SyntaxException(
                        "unsupported multiline command '$type'"
                    );
            }
        } 
        
        return $centreline;
        
    }
    
    /**
     * Add a surveying team member.
     * 
     * @param File_Therion_Person $person team member
     * @param string|array string with one role or array of strings with roles
     * @todo add parameter checks, especially for roles param
     */
    public function addTeam(File_Therion_Person $person, $roles = array())
    {
        if (!is_array($roles)) {
            $roles = array($roles);
        }
          
        $this->_team[] = array('person' => $person, 'roles' => $roles);
    }
    
    /**
     * Get all surveying team members.
     * 
     * @return array array of File_Therion_Person objects.
     * @see {@link getTeamRoles()} for querying team roles.
     */
    public function getTeam()
    {
        $rv = array();
        foreach ($this->_team as $tm) {
            $rv[] = $tm['person'];
        }
        return $rv;
    }
    
    /**
     * Remove all associated team members.
     * 
     */
    public function clearTeam()
    {
        $this->_team = array();
    }
    
    /**
     * Get surveying roles of a team member.
     * 
     * Note that exploring team members have no roles.
     * 
     * @param File_Therion_Person $person team member.
     * @return array string array with surveying roles of that person.
     * @throws OutOfBoundsException in case person is no surveying team member.
     */
    public function getTeamRoles(File_Therion_Person $person)
    {
        foreach ($this->_team as $tm) {
            if ($person == $tm['person']) {
                return $tm['roles'];
            }
        }
        
        // in case no such team member:
        throw new OutOfBoundsException(
            "No such team member: ".$person->toString()
        );
    }
    
    /**
     * Add a team member which explored.
     * 
     * @param File_Therion_Person $person team member
     */
    public function addExploTeam(File_Therion_Person $person)
    {
        $this->_exploteam[] = $person;
    }
    
    /**
     * Get all exploring team members.
     * 
     * @return array array of File_Therion_Person objects.
     */
    public function getExploTeam()
    {
        return $this->_exploteam;
    }
    
    /**
     * Remove all associated exploring team members.
     * 
     */
    public function clearExploTeam()
    {
        $this->_exploteam = array();
    }
    
    /**
     * Get survey date.
     * 
     * @return null|File_Therion_Date therion date
     */
    public function getDate()
    {
        $dates = $this->getData('date');
        if (count($dates) == 2) {
            return $dates;
        } elseif (count($dates) == 0) {
            return null;
        } else {
            return $dates[0];
        }
    }
    
    /**
     * Set survey date.
     * 
     * If a an array with exactly two Date objects is given, this will be
     * threaten as time interval.
     * 
     * @param array|File_Therion_Date $date therion date or array for date interval
     * @throws InvalidArgumentException
     */
    public function setDate($date)
    {
        if (is_array($date)) {
            if (count($date) != 2) {
                throw new InvalidArgumentException(
                    "Invalid number of arguments - expected two date objects");
            }
            if (!is_a($date[0], 'File_Therion_Date')) {
                throw new InvalidArgumentException(
                    "Date is not of type File_Therion_Date");
            }
            if (!is_a($date[1], 'File_Therion_Date')) {
                throw new InvalidArgumentException(
                    "Date is not of type File_Therion_Date");
            }
        } else {
            if (!is_a($date, 'File_Therion_Date')) {
                throw new InvalidArgumentException(
                    "Date is not of type File_Therion_Date");
            }
            $date = array($date);
        }
        $this->setData('date', $date);
    }
    
    /**
     * Get exploration date.
     * 
     * @return null|array|File_Therion_Date therion date
     */
    public function getExploDate()
    {
        $dates = $this->getData('explo-date');
        if (count($dates) == 2) {
            return $dates;
        } elseif (count($dates) == 0) {
            return null;
        } else {
            return $dates[0];
        }
    }
    
    /**
     * Set exploration date.
     * 
     * @param File_Therion_Date $date therion date
     */
    public function setExploDate(File_Therion_Date $date)
    {
        if (is_array($date)) {
            if (count($date) != 2) {
                throw new InvalidArgumentException(
                    "Invalid number of arguments - expected two date objects");
            }
            if (!is_a($date[0], 'File_Therion_Date')) {
                throw new InvalidArgumentException(
                    "Date is not of type File_Therion_Date");
            }
            if (!is_a($date[1], 'File_Therion_Date')) {
                throw new InvalidArgumentException(
                    "Date is not of type File_Therion_Date");
            }
        } else {
            if (!is_a($date, 'File_Therion_Date')) {
                throw new InvalidArgumentException(
                    "Date is not of type File_Therion_Date");
            }
            $date = array($date);
        }
        $this->setData('explo-date', $date);
    }
    
    /**
     * Set shot station names pre-/postfix.
     * 
     * @param string $prefix
     * @param string $postfix
     */
    public function setStationNames($prefix, $postfix)
    {
        if (!is_string($prefix)) {
            throw new InvalidArgumentException(
                "Unsupported prefix type '".gettype($prefix)."'" );
        }
        if (!is_string($postfix)) {
            throw new InvalidArgumentException(
                "Unsupported postfix type '".gettype($postfix)."'" );
        }
        
        $this->setData('station-names', array($prefix, $postfix));
    }
    
    /**
     * Get station-names (pre-/postfix).
     * 
     * @return array: [0]=prefix, [1]=postfix
     */
    public function getStationNames()
    {
        return $this->getData('station-names');
    }
    
    
    /**
     * Add a survey shot to this centreline.
     * 
     * @param File_Therion_Shot $shot shot object
     */
    public function addShot(File_Therion_Shot $shot)
    {
        $this->_shots[] = $shot;
    }
    
    /**
     * Get all shots of this centreline.
     * 
     * @return array array of File_Therion_Shot objects.
     */
    public function getShots()
    {
        // update all shots prefix/postfix with station-names param
        // 'station-names' => array("",""), // <prefix> <postfix>
        $s_names = $this->getData('station-names');
        foreach ($this->_shots as $s) {
            $s->setStationNames($s_names[0], $s_names[1]);
        }
        
        return $this->_shots;
    }
    
    /**
     * Remove all associated shots from this centreline.
     * 
     */
    public function clearShots()
    {
        $this->_shots = array();
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
