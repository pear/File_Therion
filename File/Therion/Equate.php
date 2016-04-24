<?php
/**
 * Therion cave equate object class.
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
 * Class representing an equate definition object.
 *
 * Equates are used to equate stations inside the survey or in subsurveys
 * to form an extended centreline network with loop closures.
 *
 * @obsolete
 * THIS CLASS MAY BE OBSOLETE!
 * As a specific station could only b part of a specific centreline and a
 * centreline only part of a specific survey, the stations context in surveys
 * is already known. Thus, this could be handled at the station-class level,
 * possibly much more convinient for the end user.
 * The only problem could be lone equate commands in included files and also
 * referenced stations that are not known because of only partial data.
 * However, when parsing, this could be circumvented with faked data strutures
 * as it is donw currently.
 * some more thinking should go into this (also tests with therion itself),
 * however i currently think that the current impementation is not elegant.
 * On the other hand: equate is valid in centreline, survey AND none context,
 * that means a separate entity is needed to resolve this. The dedicated
 * Equate-class solves this as it is able to form an own "context" to hold such
 * definitions. It is also in conformance with the rest of the API (scrap-objs,
 * lines etc). We will keep this class and the current implementation for a
 * a while until it really proves wrong. Probably for more convinience,
 * a equates() method at the station class could be introduced, returning
 * a proper initialized Equate-object with stations given.
 * 
 * @category   file
 * @package    File_Therion
 * @author     Benedikt Hallinger <beni@php.net>
 * @copyright  2016 Benedikt Hallinger
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @link       http://pear.php.net/package/File_Therion/
 */
class File_Therion_Equate
    implements Countable
{
    
    /**
     * Survey context of this equate (for name resolution).
     * 
     * @var File_Therion_Survey
     */
    protected $_survey = null;
    
    /**
     * Equate stations.
     * 
     * @var array with File_Therion_Station objects
     */
    protected $_equates = array();
    
    
    /**
     * Create a new therion Equate object.
     * 
     * After creation of the equate command you must call {@link addArgument()}
     * to add equate arguments.
     *
     * @param array $args Stations to equate
     * @param array $options key=>value pairs of options to set
     */
    public function __construct($args=array())
    {
        $this->addArgument($args);
    }
    
    
    /**
     * Parses given Therion_Line-objects into internal data structures.
     * 
     * Note that this will generate fresh subobjects to reflect the named IDs.
     * Most notably the stations will be bare objects with an fake survey
     * context with only names set.
     * In many cases this will be enough but keep in mind that you may not be
     * able to get references to other real therion objects when parsing.
     * In such cases you should check context equivalence.
     * 
     * @param File_Therion_Line $line line forming this object
     * @return File_Therion_Equate Equate object
     * @throws InvalidArgumentException
     * @throws File_Therion_SyntaxException
     */
    public static function parse($line)
    {
        if (!is_a($line, 'File_Therion_Line')) {
            throw new InvalidArgumentException(
                'Invalid $line argument (expected File_Therion_Line, seen:'
                .gettype($line).')'
            );
        }
        
        // this is a one-line object.
        $flData = $line->getDatafields();
        $cmd = array_shift($flData);
        if ($cmd !== "equate") {
            throw new File_Therion_SyntaxException(
                "parsing equate expects 'equate' command as first data"
                ." element, '".$cmd."' given");
        }
        if (count($flData) < 2) {
            throw new File_Therion_SyntaxException(
                "join command expects at least two arguments, "
                .count($flData)." given");
        }
        
        // craft new Equate object without valid context
        // (without context, all stations paths will be resolved to their tops)
        $equateObj = new File_Therion_Equate(array());
            
        // retrieve lines and add them as arguments
        foreach ($flData as $earg) {
            $sobj = null; // to-be-crafted station object
            
            // investigate argument: either local station or reference
            $sdata = explode("@", $earg, 2);
            switch (count($sdata)) {
                case 1:
                    // local reference only:
                    $sobj = new File_Therion_Station($sdata[0]);
                break;
                
                case 2:
                    // at least one survey reference found:
                    $sobj = new File_Therion_Station($sdata[0]);
                    
                    // Build fake survey structure representing the survey chain
                    $subSsurveysString = explode(".", $sdata[1]);
                    $subsObjs = array(); // path of survey objects
                    foreach (array_reverse($subSsurveysString) as $ssid) {
                        // build fake hierarchy from end: set parents
                        $topSurvey = new File_Therion_Survey($ssid);
                        if (count($subsObjs) > 0) {
                            // subordinate current top survey to this new one
                            $subsObjs[0]->setParent($topSurvey);
                        }
                        array_unshift($subsObjs, $topSurvey); // prepend new top
                    }
                    
                    // associate the station with deepest survey:
                    // stations now has valid fake survey context with
                    // faked parent surveys.
                    $sobj->setSurveyContext($subsObjs[count($subsObjs)-1]);
                    
                break;
                
                default:
                    throw new File_Therion_SyntaxException(
                        "invalid equals station reference: '$earg'");
            }
            
            $equateObj->addArgument($sobj);
        }
        
        return $equateObj;
        
    }
    
    /**
     * Add an equate argument.
     * 
     * This adds a station as equal to the already given stations.
     * 
     * @param array|File_Therion_Station Station or array of stations
     * @throws InvalidArgumentException when incompatible object is added.
     */
    public function addArgument($arg)
    {
        // array mode: recall on each element
        if (is_array($arg)) {
            foreach ($arg as $a) {
                $this->addArgument($a);
            }
            return;
        }
        
        if (get_class($arg) != 'File_Therion_Station') {
            throw new InvalidArgumentException(
                "Invalid join argument type: '".get_class($arg)."'");
        }
        
        $this->_equates[] = $arg;
    }
    
    /**
     * Return therion compatible string of this equate definition.
     * 
     * The station references will be {@link resolve()}d using the current
     * equate commands survey context (see {@link setSurveyContext()}).
     * 
     * @return string like "equate 1 2 3@survey 4@survey.subsurvey"
     * @throws File_Therion_SyntaxException in case equate syntax is invalid
     * @throws File_Therion_Exception when resolving was requested but failed
     */
    public function toString()
    {
        if (count($this) < 2) {
            throw new File_Therion_SyntaxException(
                "equate command expects at least two arguments, "
                .count($this)." given");
        }
        
        $rv = "equate";
        // add args to command string:
        foreach ($this->_equates as $station) {
            $path = $this->resolveStationPath($station); // bubble up exceptions
            if (count($path) > 0) {
                // resolved in subsurvey
                
                // resolve survey chain names:
                $srvyNames = array();
                foreach ($path as $srvy) {
                    $srvyNames[] = $srvy->getName();
                }

                // build path:
                $rv .= " ".$station->getName(true)."@".implode(".", $srvyNames);
                
            } else {
                // local resolving result: return plain name of station
                $rv .= " ".$station->getName(true);
            }
            
        }
        
        return $rv;
    }
    
    /**
     * Return Line representation of this command.
     * 
     * @return {@link File_Therion_Line} object
     */
    public function toLines()
    {
        return new File_Therion_Line($this->toString());
    }
    
    /**
     * Return equating station objects.
     * 
     * @return array
     */
    public function getArguments()
    {
        return $this->_equates;
    }
    
    /**
     * Count arguments of this equate (SPL Countable).
     *
     * @return int number of argument objects
     */
    public function count()
    {
        return count($this->_equates);
    }
    
    /**
     * Set local survey context of this equate.
     * 
     * This is the survey context that is used to resolve station names.
     * In case you have incomplete data at the survey structure but want to
     * be able to resolve deeper names, you need to
     * - fake a survey structure context
     * - set the stations survey context accordingly for it to match properly
     * see {@link resolvePath()} for more info.
     * 
     * @param File_Therion_Survey|null Pass null to reset prior given context
     * @throws InvalidArgumentException
     */
    public function setSurveyContext(File_Therion_Survey $survey=null)
    {
        $this->_survey = $survey;
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
     * Resolve path of station from subsurveys.
     * 
     * Stations must be associated to a survey. The surveys of the equaled
     * stations must be reachable from the survey context of the equate, which
     * is usually the parent survey referencing station of subsurveys.
     * 
     * When the equates context is not set (null), the topmost station survey
     * reference will be used as top parent.
     * When the stations survey is not set, it will be threaten as local to
     * the equate survey context.
     * When the stations survey context is not reachable from the equate survey
     * context, a File_Therion_Exception will be thrown.
     * 
     * The resolution is performed by walking up the survey parent structure
     * until the equate objects survey context matches one of the parents of the
     * investigated station.
     * 
     * The array returned is one of the following:
     * - array containing no elements: station is local to equate context
     * - array containing n elements: top-down path of surveys
     * 
     * @param File_Therion_Station $station Station to locate
     * @return array Path sequence
     */
    public function resolveStationPath(File_Therion_Station $station)
    {
        $resPath = array();
        
        $equateCTX  = $this->getSurveyContext();
        $stationCTX = $station->getSurveyContext();
      
        // no station context available: return station as local 
        if (is_null($stationCTX)) {
            return $resPath;
        }
        
        // walk parents upwards until survey contexts match by-name
        $parent = $stationCTX; // compare local contexts first
        while (!is_null($parent)) {
            if (!is_null($equateCTX)
                && $parent->getName() == $equateCTX->getName()) {
                // station ctx == equals ctx
                //   stop searching, reached target context: go home
                return $resPath;
                
            } else {
                // ctx does not match OR parent ctx is valid but equate ctx=null
                //   add the station as parent to the resolved path
                array_unshift($resPath, $parent);
            }
          
            // get parents parent for next loop run
            $parent = $parent->getParent(); 
        }
        
        // We made it past the resolving loop, eg we reached the TOP of the
        // parent structure of the station.
        // This is only a valid result if there was no valid equate context,
        // otherwise this means that the stations context structure is not
        // reachable from the equates survey context.
        if (is_null($equateCTX)) {
            // return valid result (=all survey parents of station)
            return $resPath;
            
        } else {
            // no context match but valid equateCTX: resolving failed
            throw new File_Therion_Exception(
                "could not resolve station ".$station->getName()
                ." from survey ctx ".$equateCTX->getName());
        }
        
    }
}

?>
