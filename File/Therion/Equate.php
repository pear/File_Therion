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
 * to form an extended centreline and loop closures.
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
     * 
     * @param File_Therion_Line $line line forming this object
     * @return File_Therion_Equate Equate object
     * @throws InvalidArgumentException
     * @throws File_Therion_SyntaxException
     * @todo try to locate station object and survey references from outside
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
        
        // craft new Equate object
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
                    $equateObj->addArgument($sobj);
                break;
                
                case 2:
                    // at least one survey reference found:
                    $sobj = new File_Therion_Station($sdata[0]);
                    
                    // TODO: try to locate the survey object from outer ctx
                    // Build fake survey structure representing the survey chain
                    $subSsurveysString = explode(".", $sdata[1]);
                    $subsObjs = array(); // path of survey objects
                    foreach ($subSsurveysString as $ssid) {
                        $subSurvey = new File_Therion_Survey($ssid);
                        if (count($subsO) > 0) {
                            // subordinate to current deepest subsurvey
                            $subsO[count($subsO)-1]->addSurvey($subSurvey);
                        }
                        $subsO[] = $subSurvey; // append as next deeper item
                    }
                    
                    // associate the station with deepest survey
                    $sobj->setSurveyContext($subsO[count($subsO)-1]);
                    
                    
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
     * The station references will be {@link resolve()}d using the survey 
     * structure given. If null is supplied as survey context, all stations are
     * assumed to be local.
     * 
     * @return string like "equate 1 2 3@survey 4@survey.subsurvey"
     * @throws File_Therion_SyntaxException in case equate syntax is invalid
     * @throws File_Therion_Exception when resolving was requested but failed
     */
    public function toString(File_Therion_Survey $survey)
    {
        if (count($this) < 2) {
            throw new File_Therion_SyntaxException(
                "equate command expects at least two arguments, "
                .count($this)." given");
        }
        
        $rv = "equate";
        // add args
        foreach ($this->_equates as $station) {
            if (!is_null($survey)) {
                // get resolved name
                $fqsn = $survey->locate($station, true);
                if ($fqsn) {
                    // resolving succeeded
                    $rv .= " ".$fqsn;
                } else {
                    // resolving error!
                    throw new File_Therion_Exception(
                    "Station name ".$this->getName()." could not be resolved!");
                }
                
            } else {
                // no resolving possible: treat as local
                $rv .= " ".$station->getName();
            }
        }
        
        return $rv;
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
    
}

?>