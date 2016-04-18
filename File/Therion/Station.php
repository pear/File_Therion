<?php
/**
 * Therion cave station data type class.
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
 * Class representing a therion station object.
 * 
 * The centreline can define stations to fix or alter stations used in shots.
 *
 * @category   file
 * @package    File_Therion
 * @author     Benedikt Hallinger <beni@php.net>
 * @copyright  2016 Benedikt Hallinger
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @link       http://pear.php.net/package/File_Therion/
 */
class File_Therion_Station
{
    /**
     * Name/ID of this station.
     * 
     * @var string
     */
    protected $_name = "";
    
    /**
     * Comment of this station.
     * 
     * @var string
     */
    protected $_comment = "";
    
    /**
     * Fix-data for this station.
     * 
     * This links stations to a fixed coordinate on the surface.
     *
     * Content is an associative array; the keys are 'coords' and 'std':
     * - the value of 'coords' is coordinates array(x,y,z)
     * - the value of 'std' is standard deviation for the coordinate values
     * 
     * @var array
     */
    protected $_fixes = array();
    
    /**
     * Survey context of this station (for name resolution).
     * 
     * @var File_Therion_Survey
     */
    protected $_survey = null;
    
    /**
     * Flags of this station.
     * 
     * NULL values mean that the flag is neither negated nor positively present.
     * 
     * @var array  
     */
    protected $_flags = array(
        'entrance'           => null,
        'continuation'       => null,
        'air-draught'        => null,
        'air-draught:winter' => null,
        'air-draught:summer' => null,
        'spring'             => null,
        'doline'             => null,
        'dig'                => null,
        'arch'               => null,
        'overhang'           => null,
        'explored'           => null,
        'attr'               => array() // key=>values for custom flags
    );
    
    /**
     * Create a new therion station object.
     * 
     * @param string $station Station name (like "1")
     */
    public function __construct($station)
    {
        $this->setName($station);
    }
    
    /**
     * Set Name/ID of this station.
     * 
     * @param string
     * @throws InvalidArgumentException
     */
    public function setName($name)
    {
        if (!is_string($name)) {
            throw new InvalidArgumentException(
                "station name expects string value, ".gettype($name)." given");
        }
        $this->_name = $name;
    }
    
    /**
     * Get name of this station.
     * 
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }
    
    /**
     * Set Comment of this station.
     * 
     * @param string
     * @throws InvalidArgumentException
     */
    public function setComment($comment)
    {
        if (!is_string($comment)) {
            throw new InvalidArgumentException(
                "station comment expects string value, ".gettype($value)." given");
        }
        $this->_comment = $comment;
    }
    
    /**
     * Get comment of this station.
     * 
     * @return string
     */
    public function getComment()
    {
        return $this->_comment;
    }
    
    /**
     * Parse Therion_Line into station object.
     * 
     * @param string|File_Therion_Line $line  line to parse
     * @return File_Therion_Station object
     * @throws File_Therion_SyntaxException
     * @throws InvalidArgumentException
     * @todo: parameter checks
     */
    public static function parse($line)
    {
        if (is_string($line)) {
            $line = File_Therion_Line::parse($line);
        }
        
        $lineData = $line->getDatafields();
        $command  = strtolower(array_shift($lineData));
        $s_name   = strtolower(array_shift($lineData));
        
        $station = new File_Therion_Station($s_name);
        
        if ($command == "fix") {
            // parse a fix-station command into station object
            // fix <station> [<x> <y> <z> [<std x> <std y> <std z>]]
            switch (count($lineData)) {
                case 6:
                    // stddev was given
                    $station->setFix(
                        $lineData[0],
                        $lineData[1],
                        $lineData[2],
                        $lineData[3],
                        $lineData[4],
                        $lineData[5]
                    );
                break;
                case 3:
                    // stddev was NOT given
                    $station->setFix(
                        $lineData[0],
                        $lineData[1],
                        $lineData[2]
                    );
                break;
                default:
                  throw new File_Therion_SyntaxException(
                    "wrong arg count (".count($linedata)
                    .") for fix command"
                  );
            }
            
            
        } elseif ($command == "station") {
            // parse a station comment+flags command into station object
            // station <station> <comment> [<flags>]
            
            $station->setComment(array_shift($lineData));
            
            $truefalse = true;
            while ($flag = array_shift($lineData)) {
                switch ($flag) {
                    case "attr":
                        // user defined flag: <flag> <value>
                        if (count($linedata) < 2) {
                            throw new File_Therion_SyntaxException(
                                "wrong arg count (".count($linedata)
                                .") for station flag $flag"
                              );
                        }
                        $flagVal = array(
                            array_shift($lineData),
                            array_shift($lineData)
                        );
                    break;
                    
                    case 'explored':
                        // explored flag expects string argument
                        $flagVal = array_shift($lineData);
                    break;
                    
                    case 'not':
                        // negate the next flag
                        $truefalse = false;
                        continue 2; // skip adding this flag (which is no flag)
                    break;
                    
                    default:
                        // simple boolean flag
                        $flagVal = $truefalse;
                        $truefalse = true; // reset
                        
                }

                // set the flag
                $station->setFlag($flag, $flagVal);

            }
            
            
        } else {
            throw new File_Therion_SyntaxException(
                "station parsing is only possible with 'fix' and "
                ."'station' commands" );
        }
        
        return $station;
    }
    
    /**
     * Set station flag.
     * 
     * The flag value is expected as following:
     * - normal flags: TRUE or FALSE
     * - flag 'explored': the value is expected to be string (eg "100m" or so)
     * - flag 'attr': cutsom flag; value is array(customflag => value)
     * 
     * @param string  $flag  name of the flag.
     * @param boolean|string|array $value Flag value to set
     * @throws InvalidArgumentException
     */
    public function setFlag($flag, $value=true)
    {
        $flag = strtolower($flag);
        if ($flag == 'explored' && !is_string($value)) {
            throw new InvalidArgumentException(
                "station-flag $flag expects string value, "
                .gettype($value)." given");
                
        } elseif ($flag == 'attr' && !is_array($value)) {
            throw new InvalidArgumentException(
                "station-flag $flag expects array value, "
                .gettype($value)." given");
                
        } else {
            $value = ($value)? true : false;  // force explicitely bool
        }
        
        // set the flag
        if ($flag != 'attr') {
            // normal flag:    set value   
            if (array_key_exists($flag, $this->_flags)) {
                $this->_flags[$flag] = $value;
            } else {
                throw new InvalidArgumentException(
                    "Invalid flag $flag; flag not nvalid for shot");
            }
        } else {
            // custom user flag: set to attr array
            $k = array_shift(array_keys($value));
            $v = array_shift($value);
            $this->_flags['attr'][$k] = $v;
        }
    }
    
    /**
     * Get a station flag.
     * 
     * When the flag is not set explicitely, NULL is returned.
     * Custom attribute flags (flag 'attr') are returned as associative array
     * holding the attrs name and value.
     * 
     * @param string  $flag name of the flag.
     * @return bool|string|array
     * @throws InvalidArgumentException
     */
    public function getFlag($flag)
    {
        if (array_key_exists($flag, $this->_flags)) {
            return $this->_flags[$flag];
        } else {
            throw new InvalidArgumentException(
                "Invalid flag $flag; flag not valid for shot");
        }
    }
    
    /**
     * Get all active station flags.
     * 
     * This returns an associative array of all flags that have set values.
     * 
     * @return array
     */
    public function getAllFlags()
    {
        $r = array();
        foreach ($this->_flags as $f => $fv) {
            if ($flag == 'attr') {
                if (count($fv) > 0) {
                    $rv[$f] = $fv;
                }
                
            } else {
                // normal flag: add if not-null
                if (!is_null($fv)) {
                    $rv[$f] = $fv;
                }
            }
        }
        return $r;
    }
    
    /**
     * Set fixed surface coordinates for this station.
     * 
     * The coordinates are relative to a specified coordinate system that will
     * usually get set at the centreline level.
     * Without centreline context, fixing stations is not meaningful.
     * Please also look at the therion manual.
     * 
     * @param float $x X value of coordinate
     * @param float $y Y value of coordinate
     * @param float $z Z (height) value of coordinate
     * @param float $stdX standard deviation for X
     * @param float $stdY standard deviation for Y
     * @param float $stdZ standard deviation for Z
     * @todo: parameter checks
     */
    public function setFix($x, $y, $z, $stdX=0, $stdY=0, $stdZ=0)
    {
        $this->_fixes = array(
            'coords' => array($x, $y, $z),
            'std'    => array($stdX, $stdY, $stdZ)
        );
    }
    
    /**
     * Gets the fix-data for this station.
     * 
     * If no such data is associated for this station, an empty array will be
     * returned.
     * 
     * Content is an associative array; the keys are 'coords', 'std' and 'cs':
     * - the value of 'coords' is coordinates array(x,y,z)
     * - the value of 'std' is standard deviation for the coordinate values
     * 
     * The coordinates are relative to a specified coordinate system that will
     * usually get set at the centreline level.
     * Without centreline context, fixing stations is not meaningful.
     * Please also look at the therion manual.
     * 
     * @return array like described at {@link $_fixes}
     */
    public function getFix()
    {
        if ($this->isFixed()) {
            return array(
                'coords' => $this->_fixes['coords'],
                'std'    => $this->_fixes['std'],
            );
        } else {
            return array();
        }
    }
    
    /**
     * Tell if this station has defined fixes.
     * 
     * @return boolean
     */
    public function isFixed()
    {
        return (count($this->_fixes) > 0);
    }
    
    /**
     * Removes the fix-data for this station.
     * 
     * The method will silently do nothing if there was no fix so far.
     */
    public function clearFix()
    {
        $this->_fixes = array();
    }
    
    
    /**
     * Generate "fix ..." string for this station.
     * 
     * @return string Empty, if no fix is set
     */
    public function toFixString()
    {
        if (!$this->isFixed()) {
            return "";
        } else {
            $fixdata = $this->getFix();
            $fixstring = implode(" ", $fixdata['coords']);
            if (count($fixdata['std']) > 0) {
                $fixstring .= " ".implode(" ", $fixdata['std']);
            }
            return "fix ".$fixstring;
        }
    }
    
    /**
     * Generate "station ..." string for this station.
     * 
     * @return string "station <station> <comment> <flags>"
     */
    public function toStationString()
    {
        $stationStr = "station";
        $stationStr .= " ".File_Therion_Line::escape($this->getName());
        $stationStr .= " ".File_Therion_Line::escape($this->getComment());
        
        foreach ($this->getAllFlags() as $flag => $value) {
            if ($flag == 'attr') {
                // add each cutsom flag
                foreach ($value as $ck => $cv) {
                    $stationStr .= " ".$flag
                        ." ".File_Therion_Line::escape($ck)
                        ." ".File_Therion_Line::escape($cv);
                }
            } else {
                // add normal flag
                $not = ($value)? "": "not ";
                $stationStr .= " ".$not.$flag;
            }
        }
        
        return $stationStr;
    }
    
    /**
     * Set local survey context of this station.
     * 
     * This is important for name resolution because there may be more stations
     * named the same in several subsurveys.
     * 
     * The context of the station will be automatically set when the station is
     * added to a shot which is part of a centreline.
     * 
     * @param File_Therion_Survey|null Use null to reset context
     * @throws InvalidArgumentException
     */
    public function setSurveyContext(File_Therion_Survey $survey=null)
    {
        $this->_survey = $survey;
    }
    
    /**
     * Get survey context of this station.
     * 
     * This returns the survey this station is assumed to be a local part of.
     * 
     * @return null|File_Therion_Survey
     */
    public function getSurveyContext()
    {
        return $this->_survey;
    }
    
}
?>