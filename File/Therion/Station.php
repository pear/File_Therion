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
class File_Therion_Station implements File_Therion_IReferenceable
{
    /**
     * Name/ID of this station.
     * 
     * @var string
     */
    protected $_name = "";
    
    /**
     * Prefix/Postfix of this station
     * 
     * @var array [0]=prefix, [1]=postfix
     */
    protected $_prePostfix = array("", "");
    
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
     * Equated stations.
     * 
     * @var array of File_Therion_Station objects
     */
    protected $_equates = array();
    
    /**
     * Equated stations (backlinks).
     * 
     * @var array of File_Therion_Station objects
     */
    protected $_equatesBL = array();
    
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
     * Station name is a therion keyword and as such may only contain
     * alphanumeric characters and additionally ‘_’ and ‘-’.
     * 
     * @param string
     * @throws InvalidArgumentException
     * @todo The syntax check for station name is probably stronger than necessary; Survex manual is a little unclear here.
     */
    public function setName($name)
    {   
        if (is_string($name) && $name != ""
            && File_Therion_Line::checkSyntax_keyword($name, true)) {
            $this->_name = $name;
        
        } elseif(is_string($name) && ($name == "." || $name == "-")) {
            // support anonymous station names too
            $this->_name = $name;
        
        } else {
            throw new InvalidArgumentException(
                "station name must be nonempty therion keyword string, '$name' given");
        }
        
       
    }
    
    /**
     * Get name of this station.
     * 
     * If the station has a set prefix/postfix, this will be applied,
     * unless the $applyNames parameter is set to false.
     * 
     * If the station is a anonymous one (original name is dash or dot),
     * the prefix/postfix will always be ommitted.
     * Please bear in mind that if you manually set a prefix/postfix as part
     * of the stations raw name, you need to handle that yourself!
     * 
     * @param boolean $applyNames false: return name without applied pre-/postfix
     * @return string Original or prefixed+postfixed name
     */
    public function getName($applyNames=true)
    {
        if (!$applyNames || $this->_name == "." || $this->_name == "-") {
            return $this->_name;
            
        } else {
            $names = $this->getStationNames();
            return $names[0].$this->_name.$names[1];
        }
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
     * Custom attribute flags (flag 'attr') are returned as associative array
     * holding the attrs name and value.
     * 
     * When the flag is not set explicitely, false is returned;
     * this can be switched to NULL return with $null param set to true.
     * In this case the return is false only if flag is explicitely negated
     * and null if it is not explicitely set; true otherwise.
     * 
     * @param string  $flag name of the flag.
     * @param boolean $null When true, unset flag is returned as null
     * @return bool|string|array
     * @throws InvalidArgumentException
     */
    public function getFlag($flag, $null=false)
    {
        if (array_key_exists($flag, $this->_flags)) {
            if (!$null) {
                return (true == $this->_flags[$flag]);
            } else {
                return $this->_flags[$flag];
            }
        } else {
            throw new InvalidArgumentException(
                "Invalid flag $flag; flag not valid for station");
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
        $rv = array();
        foreach ($this->_flags as $f => $fv) {
            if ($f == 'attr') {
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

        return $rv;
    }
    
    /**
     * Set fixed surface coordinates for this station.
     * 
     * The coordinates are relative to a specified coordinate system that will
     * usually get set at the centreline level.
     * Without centreline context, fixing stations is not meaningful.
     * Please also look at the therion manual.
     * 
     * Latitude and longtitude in format nn°nn'nn.nn" is autoconverted to
     * therions format (nn:nn:nn.nn).
     * {@link getFix()} will return strings instead of floats then!
     * 
     * @param float $x X value of coordinate (Rechtswert in Gauss-Krueger)
     * @param float $y Y value of coordinate (Hochwert in Gauss-Krueger)
     * @param float $z Z (height) value of coordinate (usually Meters above sea level)
     * @param float $stdX standard deviation for X
     * @param float $stdY standard deviation for Y
     * @param float $stdZ standard deviation for Z
     * @todo: parameter checks
     */
    public function setFix($x, $y, $z, $stdX=0, $stdY=0, $stdZ=0)
    {
        // convert lat-lon (only when string was given):
        if (is_string($x)) {
            $x = preg_replace('/°|\'|"/', ':', $x);
            $x = rtrim($x, ':');
        }
        if (is_string($y)) {
            $y = preg_replace('/°|\'|"/', ':', $y);
            $y = rtrim($y, ':');
        }
        
        // apply fix:
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
     * - the value of 'coords' is coordinates array(x,y,z), see {@link setFix()}
     * - the value of 'std' is standard deviation for the coordinate values
     * 
     * The coordinates are relative to a specified coordinate system that will
     * usually get set at the centreline level.
     * Without centreline context linked to a coordinate system,
     * fixing stations is not meaningful.
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
            
            // correct locale influences in coords and std,
            // but only when datatype is applicable
            foreach (array('coords', 'std') as $w) {
                $fixdata[$w] = array_map(
                        function($n) {
                            switch (gettype($n)) {
                                case 'float':
                                case 'double':
                                    return File_Therion_Unit::float2string($n);
                                case 'string':
                                default:
                                    return $n;
                            }
                        },
                        $fixdata[$w]
                );
            }

            // generate return data
            $fixstring = implode(" ", $fixdata['coords']);
            if (count($fixdata['std']) > 0) {
                $fixstring .= " ".implode(" ", $fixdata['std']);
            }
            return "fix ".$this->getName(true)." ".$fixstring;
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
    
    /**
     * Set  prefix/postfix of this station.
     * 
     * This is usually set automatically in centreline context.
     * You can use this to adjust station prefix/postfix after updates from
     * the centreline or to have such data without context.
     * 
     * When null is supplied, the setting will be left untouched.
     * 
     * @param string $prefix
     * @param string $postfix
     * @throws InvalidArgumentException
     */
    public function setStationNames($prefix, $postfix)
    {
        if (!is_string($prefix) && !is_null($prefix)) {
            throw new InvalidArgumentException(
                "Unsupported prefix type '".gettype($prefix)."'" );
        }
        if (!is_string($postfix) && !is_null($postfix)) {
            throw new InvalidArgumentException(
                "Unsupported postfix type '".gettype($postfix)."'" );
        }
        
        // keep values if requested
        if (is_null($prefix))  $prefix  = $this->_prePostfix[0];
        if (is_null($postfix)) $postfix = $this->_prePostfix[1];
        
        $this->_prePostfix = array($prefix, $postfix);
    }
    
    /**
     * Get station-names (pre-/postfix).
     * 
     * @return array: [0]=prefix, [1]=postfix
     */
    public function getStationNames()
    {
        return $this->_prePostfix;
    }
    
    /**
     * Apply prefix/postfix setting permanently.
     * 
     * This will apply the current station-names setting to the name
     * and reset the setting afterwards. The Stations name is then the
     * prefixed/postfixed one with emtpy prefix/postfix setting.
     * 
     * Usually calling {@link getName(true)} is the better alternative because
     * it leaves the prefix/postfix and original name intact.
     */
    public function applyStationNames()
    {
        if ($this->getName() != "-" && $this->getName(false) != ".") {
            $prePost = $this->getStationNames();
            $this->setName($prePost[0].$this->getName(false).$prePost[1]);
        }
        
        $this->setStationNames("", ""); // reset station prefix/postfix setting
    }
    
    /**
     * Strip prefix/postfix setting from name.
     * 
     * This will remove the currently set station-names from the name
     * The Stations name is then the non-prefixed/postfixed one with untouched
     * prefix/postfix setting.
     */
    public function stripStationNames()
    {
        $prePost = $this->getStationNames();
        $name    = $this->getName(false);
        $name    = preg_replace('/^'.$prePost[0].'/', '', $name);
        $name    = preg_replace('/'.$prePost[1].'$/', '', $name);
        $this->setName($name);
    }
    
    /**
     * Add equated station.
     * 
     * This defines that the local station is equal to the passed one.
     * You may also pass an array of Station objects.
     * 
     * A backlink will be established automatically if the equated station
     * already links to this station (the added station will also equate to
     * this station at hand).
     * 
     * @param array|File_Therion_Station $station
     */
    public function addEquate($station)
    {
        if (is_array($station)) {
            // add elements
            foreach ($station as $stn) {
                $this->addEquate($stn);
            }
            
        } else {
            if (!is_a($station, 'File_Therion_Station')) {
                throw new InvalidArgumentException(
                    "wrong argument type '"
                    .gettype($station)."'/'".get_class($station)."'" );
            }
            
            // ignore self-references
            if (in_array($station, $this->getEquates())) {
                return;
            }
        
            // add station link if not already linked as forward or backlink
            $curEquates = $this->getEquates(false);
            if (!in_array($station, $curEquates)) {
                // station is not linked in this station, it must be added.
                // We look if it is to be added as forward link or as backlink.
                if (!in_array($this, $station->getEquates())) {
                    // local station is not linked at target:
                    // this is a normal forward link
                    $this->_equates[] = $station;
                    
                    // establish a backlink in linked station
                    $station->addEquate($this);
                    
                } else {
                    // local station is already linked at target:
                    // this means, a backlink should be established.
                    $this->_equatesBL[] = $station;
                }
                
            } else {
                // station is already linked either as forward or as backlink.
                // do nothing and ignore the add request.
            }
        }
    }
    
    /**
     * Returns equated stations.
     * 
     * @return array of equated File_Therion_Station objects
     */
    public function getEquates()
    {
        return array_merge($this->_equates, $this->_equatesBL);
    }
    
    /**
     * Removes all equated stations.
     * 
     * You may optionally select a station to clear by providing the station
     * object. Other stations will be still equated.
     * 
     * The referenced stations backlink will be cleared automatically.
     * 
     * @param File_Therion_Station $station clear only this link
     */
    public function clearEquates(File_Therion_Station $station = null)
    {
        if (is_null($station)) {
            // clear all stations
            
            // clear backlinks
            foreach ($this->_equates as $eq) {
                $eq->clearEquates($this);
            }
                
            // clear all stations
            $this->_equates   = array();
            $this->_equatesBL = array();
            
        } else {
            // clear selected station
            // (implemented quick and dirty, sorry. Brain empty.)
            
            if (in_array($station, $this->getEquates())) {
                // only do this if the station is still linked
                $neq = array();
                $tgt = null;
                foreach ($this->_equates as $eq) {
                    if ($eq != $station) {
                        $neq[] = $eq;
                    } else {
                        $tgt = $eq; // save for later clearing
                    }
                }
                $this->_equates = $neq;
                if (!is_null($tgt)) {
                    $tgt->clearEquates($this); // clear backlink
                }
                
                // do it again for the backlinks store.
                $neq = array();
                $tgt = null;
                foreach ($this->_equatesBL as $eq) {
                    if ($eq != $station) {
                        $neq[] = $eq;
                    } else {
                        $tgt = $eq; // save for later clearing
                    }
                }
                $this->_equatesBL = $neq;
                if (!is_null($tgt)) {
                    $tgt->clearEquates($this); // clear backlink
                }
            }
        }
    }
    
    /**
     * Return equate command as string.
     * 
     * This creates an "equate"-command string with station references as viewed
     * from the stations local context.
     * 
     * When no stations are equated, an empty string is returned.
     * Otherwise the command "equate" followed by station string references
     * will be returned.
     * 
     * Stations whose reference cannot be resolved will be silently suppressed.
     * 
     * If an alternative survey context is provided, this will be used instead
     * of the stations context. Be aware that this may yield strange results,
     * and be sure to pass parent surveys of the local context.
     *
     * @param File_Therion_Survey $viewCTX alternative context
     * @return string empty string or equate command
     * @throws UnexpectedValueException when view-context is not available
     */
    public function toEquateString(File_Therion_Survey $viewCTX = null)
    {
        if (is_null($viewCTX)) $viewCTX = $this->getSurveyContext();
        
        if (is_null($viewCTX)) {
            throw new UnexpectedValueException(
                "View-Context of station ".$this->getName(true)." is invalid!");
        }
        
        // walk all stations and try to resolve them
        $refStrings = array();
        $equates = $this->getEquates();
        array_unshift($equates, $this); // add local station to equate refs
        foreach ($equates as $es) {
            // Skip referencing in case the link is a backlink AND the
            // referenced station is in the same survey context.
            // This avoids duplicate equate-commands.
            $thisCTX = $this->getSurveyContext();
            if (in_array($es, $this->_equatesBL)
                && $thisCTX === $es->getSurveyContext()) {
                continue; // ignore the station
            }
            
            try {
                // create string reference
                $ref = new File_Therion_Reference($es, $viewCTX);
                $refStrings[] = $ref->toString();
            } catch (File_Therion_InvalidReferenceException $exc) {
                // ignore the station
            } catch (UnexpectedValueException $exc) {
                // ignore the station, when it has no survey context:
                // it is unresolvable, most probably because the stations
                // "home survey" is not in the dataset or dataset inconsistent.
                if (is_a($es->getSurveyContext(), 'File_Therion_Survey')) {
                    // rethrow exception because some other error occured
                    throw $exc;
                }
            }
        }
        
        // return result
        if (count($refStrings) >= 2) {
            return "equate ".implode(" ", $refStrings);
        } else {
            return "";
        }
    }
    
}
?>