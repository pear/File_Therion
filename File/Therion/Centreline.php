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
     * Survey context of this centreline.
     * 
     * @var File_Therion_Survey
     */
    protected $_survey = null;
    
    /**
     * Centreline options (id, ...).
     * 
     * @var array assoc array
     */
    protected $_options = array(
        'id' => "",
    );
    
    /**
     * Shot template for initializing units etc
     * 
     * Will be initialized in construtor and updated from {@link setUnit()}
     * 
     * @var File_Therion_Shot
     */
    protected $_shotTPL = null;
    
    
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
        'instrument'  => array(), // assoc: [<quantity>]=<description>
        'infer'       => array(), // assoc: [<what>]=<boolean>
        'declination' => array(), // 0=value, 1=unit; eg. (0.0 grad)
        'grid-angle'  => array(), // (<value> <units>)
        'sd'          => array(), // assoc: [<quantity>]=(<value> <units>)
        'cs'          => "",      // <coordinate system>
        'station-names' => null, // <prefix> <postfix>; null=leave alone
        'grade'       => array(), // grade <grade list>
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
     * Fixed stations outside of shot data
     * 
     * @var array
     */
    protected $_fixedStations = array();
    
    
    /**
     * Extend definitions.
     * 
     * Controls how extended elevation is rendered.
     *
     * Contains array with associative array:
     * - key 'obj':  value is relevant object (Shot or Station)
     * - key 'spec': value is specification 
     * 
     * @var array
     */
    protected $_extend = array();
    
    
    
    /**
     * Create a new therion centreline object.
     *
     * @param array $options Optional associative options array
     * @todo Restrict naming convention, not all characters are allowed!
     */
    public function __construct(array $options = array())
    {
        $this->setOption($options);
        $this->_shotTPL = new File_Therion_Shot(); // init shot template
    }
    
    
    /**
     * Parses given Therion_Line-objects into internal data structures.
     * 
     * @param array $lines File_Therion_Line objects forming a centreline
     * @return File_Therion_Centreline Centreline object
     * @throws InvalidArgumentException
     * @throws File_Therion_SyntaxException
     * @throws OutOfBoundsException when unknown station is referenced
     * @todo Implement parsing of centreline extending
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
        $lastSeenDatadef     = false;
        $lastSeenStyle       = false;
        $lastSeenUnits       = false;
        $postponeLineParsing = array();
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
                                    // just add these as arrays
                                    // todo: better handling of type syntax
                                    $centreline->setData($command, $lineData);
                                break;

                                case 'declination':
                                    if (count($lineData) != 2) {
                                        throw new File_Therion_SyntaxException(
                                                "Wrong declination arg count: "
                                                .count($lineData));
                                    }
                                    $this->setDeclination($lineData[0], $lineData[1]);
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
                                case 'fix':
                                case 'extend':
                                    // Postpone parsing after centreline is rdy
                                    $postponeLineParsing[] = $line;
                                break;
                                
                                case 'station-names':
                                    $centreline->setStationNames(
                                        $lineData[0], $lineData[1]
                                    );
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
                                
                                case 'cs':
                                    // coordinate system specification
                                    if (count($lineData) != 1) {
                                        throw new File_Therion_SyntaxException(
                                                "Wrong cs arg count "
                                                .count($lineData));
                                    }
                                    $centreline->setCoordinateSystem(
                                        $lineData[0]);
                                break;
                                
                                case 'sd':
                                    // TODO: implement me please; for now its just ignored
                                break;
                                
                                case 'grade':
                                    // grade definition(s)
                                    // they will be evaluated as a whole at the end, becaus there may
                                    // be several grade definitions in the data.
                                    // As per Therion definition, only the last grade command is evaluated.
                                    if (count($lineData) < 1) {
                                        throw new File_Therion_SyntaxException(
                                                "Wrong grade arg count "
                                                .count($lineData));
                                    }
                                    $centreline->setGrade($lineData);
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
                                        
                                        // add the shot to centreline
                                        // (init context etc)
                                        $centreline->addShot($shot);
                                        
                                        // set reading style of shot
                                        $shot->setStyle($lastSeenStyle);
                                        
                                        // swap parsed shot stations with
                                        // existing centreline ones, if possible
                                        try {
                                            $rsf = $centreline->getStations(
                                                $shot->getFrom()->getName(true));
                                            $shot->setFrom($rsf);
                                        } catch (OutOfBoundsException $e) {
                                            // just ignore.
                                        }
                                        try {
                                            $rst = $centreline->getStations(
                                                $shot->getTo()->getName(true));
                                            $shot->setTo($rst);
                                        } catch (OutOfBoundsException $e) {
                                            // just ignore.
                                        }
                                        
                                        // adjust shot flags according to
                                        // current defined flag states
                                        foreach ($cur_flags as $fn=>$fv) {
                                            $shot->setFlag($fn, $fv);
                                        }
                                   
                                
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
                        "unsupported multiline centreline command '$type'"
                    );
            }
        }
        
        
        // Parse postponed lines
        // this is neccessary because some commands reference stations, however
        // therion is not dependent on ordering of lines.
        foreach ($postponeLineParsing as $line) {
            $lineData = $line->getDatafields();
            $command  = strtolower(array_shift($lineData));
            
            switch ($command) {
                case 'station':
                    // change comment and flags of existing station
                    // OutOfBoundsException (no such station) will bubble up!
                    $tmpStn = File_Therion_Station::parse($line);
                    $refStn = $centreline->getStations($tmpStn->getName(true));
                    $refStn->setComment($tmpStn->getComment());
                    foreach($tmpStn->getAllFlags() as $f => $v) {
                        $refStn->setFlag($f, $v);
                    }
                break;
                
                case 'fix':
                    // fixate existing station (or add outside-shot-one)
                    $tmpStn = File_Therion_Station::parse($line);
                    $centreline->addFixedStation($tmpStn);
                break;
                    
                case 'extend':
                    // @TODO: IMPLEMENT ME - probably its a good idea to hold the specification at the shot and stastion object and not in the centreline. This is maybe more OO like and easier to understand.
                    // set centreline extending
                    // OutOfBoundsException (no such station) will bubble up!
                    $spec = array_shift($lineData);
                    switch (count($lineData)) {
                        case 1:
                            // station spec: get station
                            $obj = $centreline->getStations($lineData[0]);
                            //$centreline->setExtend($spec, $obj);
                        break;
                        case 2:
                            // shot spec: get shot for referenced stations
                            // (note that we cannot create a fresh shot here!)
                            $obj = $centreline->getShots(
                                $lineData[0], $lineData[1]);
                            //$centreline->setExtend($spec, $obj);
                        break;
                        default:
                            throw new File_Therion_SyntaxException(
                                "Wrong extend arg count "
                                .count($lineData));
                    }
                    
                    $centreline->setExtend($spec, $obj);
                    
                break;
            }
        }
        
        
        
        return $centreline;
        
    }

    /**
     * Set declination of this centreline.
     *
     * @param float  $decl Declination angle
     * @param string $unit Unit ("degrees" ...)
     * @todo add parameter checks
     */
    public function setDeclination($decl, $unit="degrees")
    {
        $this->setData('declination', array($decl, $unit));
    }

    /**
     * Get declination of this centreline.
     *
     * returns null in case declination was not set.
     *
     * @return null|array [0]=Declination angle; [1]=unit
     */
    public function getDeclination()
    {
        if (count($this->getData('declination')) >0 ) {
            return $this->getData('declination');
        } else {
            return null;
        }
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
     * @see getTeamRoles() for querying team roles.
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
     * When no date is set, NULL will be returned.
     * Otherwise a date object is returned OR if there is a date interval,
     * an array containing two date objects is returned.
     * 
     * @return null|array|File_Therion_Date therion date
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
     * @todo: unsure if proper syntax is invocating "date <singledate>" several times instead!
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
     * When no date is set, NULL will be returned.
     * Otherwise a date object is returned OR if there is a date interval,
     * an array containing two date objects returned.
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
     * Set station names pre-/postfix for newly added shots/stations.
     * 
     * Newly added shots stations will receive the new setting.
     * Already present shots will NOT be modified (use 
     * {@link updateShotStationNames() to update all shots in this centreline).
     * 
     * Use null as lone parameter to disable prefix/postfix.
     * Without active setting, newly added stations will be left alone
     * (set prefix/postfix manually on each station then).
     * 
     * Note that this setting has no direct influence on the line representation
     * of station-names commands: they are derived from the individual station
     * prefix/postfix settings.
     * 
     * When switching prefix/postfix in the middle of the centreline, be aware
     * that the stations links may break at this position. This is because
     * the to-station at the previous shot has no prefix/postfix applied,
     * rather they are viewed as separate distinct stations by therion.
     * You should handle this situation manually to produce correct data (for
     * example applying the new setting to the to-station of the previous shot).
     * 
     * @param string $prefix
     * @param string $postfix
     */
    public function setStationNames($prefix, $postfix = null)
    {
        if (is_null($prefix)) {
            $this->setData('station-names', null);
            
        } else {
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
    }
    
    /**
     * Get station-names (pre-/postfix).
     * 
     * When no station-names setting is in effect, null is returned.
     * 
     * @return null|array: [0]=prefix, [1]=postfix
     */
    public function getStationNames()
    {
        return $this->getData('station-names');
    }
    
    /**
     * Get all station objects.
     * 
     * You may query for a station name in which case either the station is
     * returned or throws an OutOfBoundsException when not found.
     * Use the full stations name when station-names is in effect.
     *
     * @param string|File_Therion_Station $station Query for named station (use full name)
     * @return array of File_Therion_Station objects
     * @throws OutOfBoundsException if no named station is found.
     */
    public function getStations($station = null)
    {        
        if (is_null($station)) {
            // return all stations from all shots
            $allSt = array();
            foreach ($this->getShots() as $sht) {
                foreach (array($sht->getFrom(), $sht->getTo()) as $stn) {
                    if (!is_null($stn) && !in_array($stn, $allSt)) {
                        array_push($allSt, $stn);
                    }
                }
            }
            
            // also fixed stations outside of shot data
            foreach ($this->getFixedStations() as $stn) {
                if (!is_null($stn) && !in_array($stn, $allSt)) {
                    array_push($allSt, $stn);
                }
            }

            return $allSt;
            
        } else {
            // search for station
            if (is_a($station, 'File_Therion_Station')) {
                $station = $station->getName(true);
            }
            
            foreach ($this->getStations() as $s) {
                if ($s->getName(true) === $station) return $s;
            }
        
            // in case no such station defined:
            throw new OutOfBoundsException("No such station: ".$station);
        }
    }
    
    /**
     * Add a fixed station definition.
     * 
     * Note that the usual way to go is, to call
     * {@link File_Therion_Station::setFix()} on a existing station from one of
     * the associated centreline shots.
     * 
     * If a station object with the name exists, that station will be fixed.
     * Otherwise the station will be added as uncorrelated to shot data.
     * 
     * Example:
     * <code>
     * $station = new File_Therion_Station("1");
     * $station->setComment("Small rabbit hole left of tree");
     * $station->setFix(1, 2, 3);
     * $centreline->addFixedStation($station);
     * </code>
     * 
     * @param File_Therion_Station $station Station object to add
     * @throws InvalidArgumentException if station is not fixed
     */
    public function addFixedStation(File_Therion_Station $station)
    {
        if (!$station->isFixed()) {
            throw new InvalidArgumentException(
                "station '".$station->getName()."' must be fixed!");
        }
        
        // see if there is such a station... if so, use it;
        // otherwise add as uncorrelated to existing shot data.
        try {
            $refStn = $this->getStations($station->getName(true));
            $fix    = $station->getFix();
            $refStn->setFix(
                $fix['coords'][0],
                $fix['coords'][1],
                $fix['coords'][2],
                $fix['std'][0],
                $fix['std'][1],
                $fix['std'][2] );
                
        } catch (OutOfBoundsException $e) {
            // no such station found: add as uncorrelated to shots
            $this->_fixedStations[] = $station;
        }
        
    }
    
    /**
     * Remove associated fixed stations.
     */
    public function clearFixedStations()
    {
        $this->_fixedStations = array();
    }
    
    /**
     * Get existing fixed station objects associated outside of shot data.
     * 
     * Associated stations (see {@link addFixedStation()}) will be tested so
     * only currently fixed stations will be returned.
     * 
     * @return array of fixed File_Therion_Station objects
     */
    public function getFixedStations()
    {
        $r = array();
        foreach ($this->_fixedStations as $fs) {
            if ($fs->isFixed()) {
                $r[] = $fs;
            }
        }
        $this->_fixedStations = $r; // update contents with filtered data
        
        return $r;
    }
    
    
    /**
     * Modify extend definition of centreline.
     * 
     * This controls how the centreline extended elevation will be rendered.
     * 
     * $spec may be one of the following:
     * - "normal"/"reverse"
     * - "left"/"right"
     * - "vertical"
     * - "start"
     * - "ignore"
     * - "hide"
     * 
     * $stationOrShot is a Station or Shot object. If an extend command for a
     * given object already exists, its $spec will be updated, otherwise added.
     * 
     * @param null|string $spec 
     * @param File_Therion_Station|File_Therion_Shot $stationOrShot
     * @TODO 'extends': probably its a good idea to hold the specification at the shot and stastion object and not in the centreline. This is maybe more OO like and easier to understand.
     */
    public function setExtend($spec, $stationOrShot)
    {
        $supported = array(
            'normal','reverse','left','right','vertical',
            'start','ignore','hide');
            
        if (!in_array($spec, $supported)) {
            throw new InvalidArgumentException(
                "Unsupported extend specification '".$spec."'" );
        }
        if (!is_a($stationOrShot, 'File_Therion_Station')
            && !is_a($stationOrShot, 'File_Therion_Shot')) {
            throw new InvalidArgumentException(
                "Unsupported extend type '".gettype($stationOrShot)."'" );
        }
        
        
        // if spec exists: update; otherwise add
        $found = false;
        foreach ($this->_extend as $ei => $e) {
            if ($e['obj'] == $stationOrShot) {
                $found = true;
                break;
            }
        }
        if ($found) {
            $this->_extend[$ei]['spec'] = $spec;
        } else {
            $this->_extend[] = array(
                'obj'  => $stationOrShot,
                'spec' => $spec
            );
        }
    }
    
    /**
     * Get extend definitions of centreline.
     * 
     * Returns array with associative array:
     * - key 'obj':  value is relevant object (Shot or Station)
     * - key 'spec': value is specification
     * 
     * @return array
     * @see setExtend()
     */
    public function getExtends()
    {
        return $this->_extend;
    }
    
    /**
     * Clear all extend definitions of centreline.
     */
    public function clearExtends()
    {
        $this->_extend = array();
    }
    
    
    /**
     * Add a survey shot to this centreline.
     * 
     * If the centreline has an active station-names setting, 
     * the stations pre- and postfix (see {@link setStationNames()}) will be
     * updated at the added shot stations with the current centreline setting.
     * 
     * This will also update the survey context of the shots from- and
     * to-station with the survey context of the centreline; as a given station
     * could only be part of one centreline.
     * 
     * The shots units declarations will be updated using the current default
     * units settings of the centreline (see {@link setUnit()}), but only if
     * the shot does not already have a more specific units setting.
     * 
     * You need to use {@link File_Therion_Equate}s when you need to set
     * stations equal.
     * 
     * @param File_Therion_Shot $shot shot object
     * @todo test for null pointer exception if a shot has no stations
     */
    public function addShot(File_Therion_Shot $shot)
    {
        // update station survey context
        $shot->getFrom()->setSurveyContext($this->getSurveyContext());
        $shot->getTo()->setSurveyContext($this->getSurveyContext());
        
        // update station names
        $names = $this->getStationNames();
        if (is_array($names)) {
            $shot->getFrom()->setStationNames($names[0], $names[1]);
            $shot->getTo()->setStationNames($names[0], $names[1]);
        }
        
        // update shot units in case they are not yet initialized
        foreach ($shot->getUnit('all') as $uname => $uobj) {
            if (is_null($uobj)) {
                $shot->setUnit($uname, $this->_shotTPL->getUnit($uname));
            }
        }
        
        // add shot to centreline
        $this->_shots[] = $shot;
    }
    
    /**
     * Get all shots of this centreline.
     * 
     * @param string $fromStation Optionally filter by from-station
     * @param string $fromStation Optionally filter by to-station
     * @return array array of File_Therion_Shot objects.
     * @throws OutOfBoundsException in case filtering of unset shot requested
     */
    public function getShots($fromStation=null, $toStation=null)
    {
        if (!$fromStation && !$toStation) {
            // no filter requested: return all
            return $this->_shots;
             
        } else {
            // filter by from and to
            $r = array();
            if ($fromStation) {
                $fromFound = false;
                foreach ($this->getShots() as $s) {
                    if ($s->getFrom()->getName() == $fromStation) {
                        $r[] = $s;
                        $fromFound = true;
                    }
                }
                if (!$fromFound) {
                    // in case no such shot found:
                    throw new OutOfBoundsException(
                        "No such shot with from-station: ".$fromStation);
                }
            }
            if ($toStation) {
                $toFound = false;
                foreach ($this->getShots() as $s) {
                    if ($s->getTo()->getName() == $toStation) {
                        $r[] = $s;
                        $toFound = true;
                    }
                }
                if (!$toFound) {
                    // in case no such shot found:
                    throw new OutOfBoundsException(
                        "No such shot with to-station: ".$toStation);
                }
            }
        }
        return $r;
       
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
     * Apply station-names to all stations of shots from this centreline.
     * 
     * This applies the individual station prefix/postfix setting of all
     * stations from all shots of this centreline.
     * This may be handy when you want to avoid station-name commands in
     * generated output or want fully qualified station names everywhere.
     * 
     * Be aware that this will overwrite the station names and resets the
     * prefix/postfix setting.
     * If you just want to enforce a given prefix/postfix softly, use
     * {@link updateShotStationNames()} instead.
     * 
     * You also could call {@link updateShotStationNames()} prior calling
     * {@link applyStationNames()} to enforce homogenous fully qualified
     * station names.
     * 
     * The centrelines prefix/postfix setting will be untouched after this
     * operation. Reset it manually if you don't want that further added
     * stations receive the current prefix/postfix setting
     * (see {@link setStationNames()}).
     * 
     * @todo test for nullPointerException in case shot station is invalid
     */
    public function applyStationNames()
    {
        foreach ($this->_shots as $s) {
            foreach (array($s->getFrom(), $s->getTo()) as $st) {
                $st->applyStationNames();
            }
        }
    }
    
    /**
     * Strip station-names from all stations of shots from this centreline.
     * 
     * This strips the individual station prefix/postfix setting of all
     * stations from all shots of this centreline.
     * 
     * You also could call {@link updateShotStationNames()} prior calling
     * {@link applyStationNames()} to enforce homogenous station-names
     * settings throughout all shots.
     * 
     * @todo test for nullPointerException in case shot station is invalid
     */
    public function stripStationNames()
    {
        foreach ($this->_shots as $s) {
            foreach (array($s->getFrom(), $s->getTo()) as $st) {
                $st->stripStationNames();
            }
        }
    }
    
    /**
     * Update station-names setting in all shots of this centreline.
     * 
     * This will overwrite the current prefix/postfix setting of all shot
     * stations in this centreline using
     * {@link File_Therion_Station::setStationNames()} on each station.
     * The original name of the stations remain intact.
     * 
     * This can be useful to correct the station prefix/postfix to a common
     * centreline wide setting.
     * Use {@link applyStationNames()} to permanently apply the setting. 
     * 
     * @todo test for nullPointerException in case shot station is invalid
     */
    public function updateShotStationNames()
    {
        $names = $this->getStationNames();
        foreach ($this->getShots() as $shot) {
            $shot->getFrom()->setStationNames($names[0], $names[1]);
            $shot->getTo()->setStationNames($names[0], $names[1]);
        }
    }
    
    /**
     * Sets the coordinate system that is used for fixing stations.
     * 
     * Fixing stations coordinates is only meaningful defining a coordinate
     * system for the coordinates given.
     * 
     * @param string
     * @todo: check on possible values accoring to thbook p.14
     */
    public function setCoordinateSystem($cs)
    {
        $this->setData('cs', $cs);
    }
    
    /**
     * Returns the coordinate system used for fixing stations.
     * 
     * @return string (empty string if not set so far)
     */
    public function getCoordinateSystem()
    {
        return $this->getData('cs');
    }
    
    /**
     * Sets copyright remark.
     * 
     * @param int    $year
     * @param string $text
     */
    public function setCopyright($year, $text)
    {
        $this->setData('copyright', array($year, $text));
    }
    
    /**
     * Return copyright.
     * 
     * @return array, 0=year, 1=text
     */
    public function getCopyright()
    {
        return $this->getData('copyright');
    }
    
    /**
     * Sets author remark.
     * 
     * @param int    $year
     * @param string $text
     */
    public function setAuthor($year, $text)
    {
        $this->setData('author', array($year, $text));
    }
    
    /**
     * Return author.
     * 
     * @return array, 0=year, 1=Person object
     */
    public function getAuthor()
    {
        return $this->getData('author');
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
    
    
    /**
     * Generate line content from this object.
     * 
     * @return array File_Therion_Line objects
     * @todo finish implementation, implement proper escaping, implement proper declination handling etc
     */
    public function toLines()
    {
        $lines = array();
        
        /*
         * create header
         */
        $hdr = "centreline"; // start
        $hdr .= $this->getOptionsString(); // add options
        $lines[] = new File_Therion_Line($hdr, "", "");
        
        
        /*
         * Basic data
         */
        $baseIndent = "\t";
        
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
        
        // copyright
        $copyright = $this->getData('copyright');
        if (count($copyright) > 0) {
            $lines[] = new File_Therion_Line(
                "copyright "
                .File_Therion_Line::escape($copyright[0])
                ." ".File_Therion_Line::escape($copyright[1]),
                "", $baseIndent);
        }
        
        // author
        $author = $this->getData('author');
        if (count($author) > 0) {
            $lines[] = new File_Therion_Line(
                "author "
                .File_Therion_Line::escape($author[0])
                ." ".$author[1]->toString(),
                "", $baseIndent);
        }
        
        $lines[] = new File_Therion_Line(""); // add line spacer
        
        // explo-date
        // TODO: im not sure if thbook means it like this, or if there are several
        // invocations of date commands with single date objects are expected.
        if (!is_null($this->getExploDate())) {
            $ds = "";
            if (is_array($this->getExploDate())) {
                // date interval: "date <date1> <date2>"
                $ds .= $this->getExploDate()[0]->toString();
                $ds .= " ".$this->getExploDate()[1]->toString();
            } elseif (is_object($this->getExploDate())) {
                $ds .= $this->getExploDate()->toString();
            }
            
            if ($ds) {
                $lines[] = new File_Therion_Line("explo-date ".$ds, "", $baseIndent);
            }
        }
        
        // explo-team
        foreach ($this->getExploTeam() as $tm) {
            $lines[] = new File_Therion_Line("explo-team ".$tm->toString(), "", $baseIndent);
        }
        
        // date
        // TODO: im not sure if thbook means it like this, or if there are several
        // invocations of date commands with single date objects are expected.
        if (!is_null($this->getDate())) {
            $ds = "";
            if (is_array($this->getDate())) {
                // date interval: "date <date1> <date2>"
                $ds .= $this->getDate()[0]->toString();
                $ds .= " ".$this->getDate()[1]->toString();
            } elseif (is_object($this->getDate())) {
                $ds .= $this->getDate()->toString();
            }
            
            if ($ds) {
                $lines[] = new File_Therion_Line("date ".$ds, "", $baseIndent);
            }
        }
        
        // team
        foreach ($this->getTeam() as $tm) {
            $lines[] = new File_Therion_Line(
                "team ".trim($tm->toString()." ".implode(" ", $this->getTeamRoles($tm))),
                "", $baseIndent);
        }
        
        $lines[] = new File_Therion_Line(""); // add line spacer

        // declination
        $decl = $this->getDeclination();
        if (!is_null($decl)) {
            $lines[] = new File_Therion_Line(
                "declination ".implode(" ", $decl),
                "", $baseIndent);
        }

        // Coordinate System
        $cs = $this->getCoordinateSystem();
        if ($cs != "") {
            $lines[] = new File_Therion_Line(
                "cs $cs",
                "", $baseIndent);
        }        
        
        // Grade(s)
        foreach ($this->getGrade() as $grade) {
            $lines[] = new File_Therion_Line(
                "grade ".$grade->getName(),
                "", $baseIndent);
        }

        // shots, units and data definitions.
        // this comes from the shot objects.
        if (count($this->getShots()) > 0) {
            $unitsdef = array(); // array of Line objects with units
            $datadef  = null;    // line object with last seen order def
            $st_names = array("", ""); // assume empty start
            $flags    = array(
               'surface'     => false,
               'splay'       => false,
               'duplicate'   => false,
               'approximate' => false,
            );
            

            // Generate Lines for all shots:
            foreach ($this->getShots() as $sobj) {
                // see if station-names changed;
                // currently we determine this from the from station.
                // this may yield weird results if one of the two stations
                // has a diverging prefix or postfix.
                // This however may be forced behavior: therion will complain,
                // so we wont handle this error for now.
                $st_namesNew = $sobj->getFrom()->getStationNames();
                if ($st_names != $st_namesNew) {
                    // craft new station-names command
                    $lines[] = new File_Therion_Line(
                        "station-names "
                        .File_Therion_Line::escape($st_namesNew[0])
                        ." "
                        .File_Therion_Line::escape($st_namesNew[1]), 
                        "", $baseIndent);
                    $st_names = $st_namesNew;
                }
                
                // see if units changed (the case at least at start of loop!)
                $unitsdefNew = $sobj->toLinesUnitsDef();
                if ($unitsdef != $unitsdefNew) {
                    foreach ($unitsdefNew as $nu) {
                        $nlu = clone $nu; // clone for indenting
                        $nlu->setIndent($baseIndent.$nlu->getIndent());
                        $lines[] = $nlu;
                    }
                    $unitsdef = $unitsdefNew;
                }
                
                // see if data definition changed (again at least at loop start)
                $datadefNew = $sobj->toLinesDataDef();
                if ($datadef != $datadefNew) {
                    $ndd = clone $datadefNew;
                    $ndd->setIndent($baseIndent.$ndd->getIndent());
                    $lines[] = $ndd;
                    $datadef = $datadefNew;
                }
                
                // see if flags changed
                $objFlags = $sobj->getAllFlags();
                $flagStr = "";
                foreach ($flags as $f => $fv) {
                    if ($f == 'splay' && $sobj->hasSplayStation()) {
                        // skip the flag printing and adjusting of seen value in
                        // case splay flag is examined and shot has splaystation
                        continue;
                    }
                    
                    $nfv = $objFlags[$f]; // new flag value
                    if ($nfv !== $fv) {
                        // flag differs: print new state and adjust seen one
                        $not = ($nfv)? "": "not "; // new state == negated
                        $flagStr .= " ".$not.$f; // add this flag as string
                        $flags[$f] = $nfv; // store new state
                    }
                }
                if ($flagStr) {
                    // print flag adjusting line with all flags
                    // TODO: suppress splay-flags if to-/from-name is '.' or '-'
                    $lines[] = new File_Therion_Line(
                        "flags ".trim($flagStr), "", $baseIndent);
                }
                
                // finally add ordered shot data
                $shotLine = $sobj->toLines();
                $shotLine->setIndent($baseIndent.$shotLine->getIndent());
                $lines[] = $shotLine;
                
            }
            unset($sobj);
        }
        
        
        // stations (fixed, normal)
        foreach ($this->getStations() as $s) {
                
                // comment and/or flags
                if ($s->getComment() != "" || count($s->getAllFlags()) > 0) {
                    $lines[] = new File_Therion_Line(
                        $s->toStationString(),"", $baseIndent);
                }
                
                // fixes
                if ($s->isFixed()) {
                    $lines[] = new File_Therion_Line(
                        $s->toFixString(),"", $baseIndent);
                }

        }
        unset($s);
        
        
        // Extends
        // TODO
        
        /*
         *  create footer
         */
        $lines[] = new File_Therion_Line("endcentreline", "", "");
        
        // done, go home
        return $lines;
    }
    
    
    /**
     * Set local survey context of this centreline.
     * 
     * The survey context will be passed to newly added stations and shots.
     * All already present shot stations context will be updated.
     * 
     * @param File_Therion_Survey
     * @throws InvalidArgumentException
     */
    public function setSurveyContext(File_Therion_Survey $survey)
    {
        $this->_survey = $survey;
        
        // update all shots
        foreach ($this->getShots() as $shot) {
            $shot->getFrom()->setSurveyContext($this->getSurveyContext());
            $shot->getTo()->setSurveyContext($this->getSurveyContext());
        }
    }
    
    /**
     * Get survey context of this equate.
     * 
     * This returns the survey context of this equate for name resolution.
     * 
     * @return null|File_Therion_Survey
     */
    public function getSurveyContext()
    {
        return $this->_survey;
    }
    
    /**
     * Set default unit for shots.
     * 
     * The unit will be used to initialize all newly added shots, if the
     * corresponding unit of that shot is not set so far. If the new shot to
     * be added already has an associated unit, that will take precedence.
     * If you want to change that shot, you need to convert the shot
     * ({@see File_Therion_Shot} for details).
     * 
     * @param string $type Measurement type ('clino', 'bearing', ...)
     * @param null|string|File_Therion_Unit $unit Unit instance
     * @throws InvalidArgumentException
     */
    public function setUnit($type, $unit)
    {
        // adjust shot template. This will take care of all the checks etc
        $this->_shotTPL->setUnit($type, $unit);
    }
    
    /**
     * Get current default unit for shots.
     * 
     * See {@link setUnit()} for details.
     * 
     * @see setUnit()
     * @param string $type Measurement type ('clino', 'bearing', ...) or 'all'
     * @return File_Therion_Unit|array Unit object or associative array of unit objects
     */
    public function getUnit($type)
    {
        // just return the shot templates data
        return $this->_shotTPL->getUnit($type);
    }
    
    /**
     * Set grade(s) applying to this centreline.
     * 
     * When $grade is a string, a new empty internal Grade object will be
     * created implicitely. use this to reference grade names already
     * built into therion.
     * When passing an array, you can either pass Grade objects or
     * strings to reference the grade definition names.
     * To delete all grade definitions from the centreline,
     * use NULL as $grade (the empty string works too).
     * 
     * @param File_Therion_Grade|array|string|null $grade
     * @throws InvalidArgumentException
     */
    public function setGrade($grade)
    {
        if (is_null($grade) || $grade === "") {
            // clear grade settings
            $this->setData('grade', array());
            
        } elseif (is_string($grade)) {
            // only string should be referenced, so create
            // a new internal object for user convinience.
            $this->setGrade(new File_Therion_Grade($grade));
            
        } elseif (is_a($grade, 'File_Therion_Grade')) {
            // actual single grade object used.
            $this->setData('grade', array($grade));
            
        } elseif (is_array($grade)) {
            // several grades referenced
            // todo: check types (better checks needed!)
            $ng = array();
            foreach ($grade as $g) {
                if (is_string($g)) {
                    // again, convert string into object for
                    // user convinience.
                    $g = new File_Therion_Grade($g);
                }
                if (!is_a($g, 'File_Therion_Grade')) {
                    throw new InvalidArgumentException('invalid $grade parameter in array');
                }
                array_push($ng, $g);
            }
            // apply checked array
            $this->setData('grade', $ng);
            
        } else {
            throw new InvalidArgumentException('invalid $grade parameter');
        }
    }
    
    /**
     * Get grade definition(s) of this centreline.
     * 
     * @return array
     */
    public function getGrade()
    {
        return $this->getData('grade');
    }
}

?>