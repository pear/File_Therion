<?php
/**
 * Therion cave survey object class.
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
 * Class representing a therion survey object.
 *
 * @category   file
 * @package    File_Therion
 * @author     Benedikt Hallinger <beni@php.net>
 * @copyright  2016 Benedikt Hallinger
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @link       http://pear.php.net/package/File_Therion/
 */
class File_Therion_Survey
    extends File_Therion_BasicObject
    implements Countable
{
    
    
    /**
     * Associated subsurvey structures
     * 
     * @var array
     */
    protected $_surveys = array();
    
    /**
     * Associated parent survey
     * 
     * @var File_Therion_Survey
     */
    protected $_parent = null;
    
    /**
     * Associated centrelines
     * 
     * @var array
     */
    protected $_centrelines = array();
    
    
    /**
     * Associated scraps
     * 
     * @var array
     */
    protected $_scraps = array();
    
    /**
     * Associated scrap joins
     * 
     * @var array
     */
    protected $_joins = array();
    
    /**
     * Associated maps
     * 
     * @var array of map objects
     */
    protected $_maps = array();
    
    /**
     * Associated surface definitions
     * 
     * @var array of surface objects
     */
    protected $_surface = array();
    
    /**
     * survey name (ID)
     * 
     * @var array
     */
    protected $_name = null;
    
    /**
     * Survey options (title, ...)
     * 
     * @var array assoc array
     */
    protected $_options = array(
        'title'       => "",
        'declination' => null, // different string forms possible, including []
        'entrance'    => "",   // name of station: "survey ... -entrance 1.0"
        'namespace'   => ""
    );
    
    
    
    /**
     * Create a new therion survey object.
     *
     * @param string $id Name of the survey
     * @todo Restrict naming convention, not all characters are allowed!
     */
    public function __construct($id, $options = array())
    {
        if (!is_string($id) || $id == "") {
            throw new InvalidArgumentException(
                "survey ID must be nonempty string!");
        }
        
        $this->_name = $id;
        $this->setOption($options);
    }
    
    
    /**
     * Parses given Therion_Line-objects into internal data structures.
     * 
     * @param array $lines array of File_Therion_Line objects containing a survey
     * @return File_Therion_Survey Survey object
     * @throws InvalidArgumentException
     * @throws File_Therion_SyntaxException for therion syntax errors
     * @todo implement me
     */
    public static function parse($lines)
    {        
        if (!is_array($lines)) {
            throw new InvalidArgumentException(
            'parse(): Invalid $lines argument (expected array)');
        }
        
        $survey = null; // survey constructed
        
        /*
         * Preparations
         */
        
        // get first line and construct survey hull object
        $firstLine = array_shift($lines);
        if (is_a($firstLine, 'File_Therion_Line')) {
            $flData = $firstLine->getDatafields();
            if (strtolower($flData[0]) == "survey") {
                $survey = new File_Therion_Survey(
                    $flData[1], // id, mandatory
                    $firstLine->extractOptions()
                );
            } else {
                throw new File_Therion_SyntaxException(
                    "First survey line is expected to contain survey definition"
                );
            }
                
        } else {
            throw new InvalidArgumentException(
                "parse(): Invalid $line argument @1"
                ."passed type='".gettype($firstLine)."'"
            );
        }
        
        // Pop last last line and control that it was the end tag
        $lastLine  = array_pop($lines);
        if (is_a($lastLine, 'File_Therion_Line')) {
            $flData = $firstLine->getDatafields();
            if (!strtolower($flData[0]) == "endsurvey") {
                throw new File_Therion_SyntaxException(
                    "Last survey line is expected to contain endsurvey definition"
                );
            }
            
        } else {
            throw new InvalidArgumentException("Invalid $line argument @last; "
                ."passed type='".gettype($lastLine)."'");
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
        $postponeLineParsing = array();
        foreach ($orderedData as $type => $data) {
            switch ($type) {
                case 'LOCAL':
                    // walk each local line and parse it
                    foreach ($data as $line) {
                        if (!$line->isCommentOnly()) {
                            $lineData = $line->getDatafields();
                            $command  = array_shift($lineData);
                            
                            switch (strtolower($command)) {
                                case 'input':
                                    // ignore silently because this should be 
                                    // handled at the file level
                                break;
                                
                                case 'join':
                                    // Scrapjoins: add join object
                                    // todo: it may be better to postpone joins and try to add defined subobjects as joins.
                                    $survey->addJoin(
                                        File_Therion_Join::parse($line));
                                break;
                                
                                case 'equate':
                                    // Postpone parsing after surveys are rdy
                                    $postponeLineParsing[] = $line;
                                break;
                                
                                default:
                                    throw new File_Therion_SyntaxException(
                                     "parse(): unsupported command '$command'");
                            }
                        }
                    }
                break;
                
                case 'survey':
                    // Parse line collection using subparser
                    foreach ($data as $ctxLines) {
                        $ctxObj = File_Therion_Survey::parse($ctxLines);
                        $survey->addSurvey($ctxObj);
                    }
                break;
                
                case 'centreline':
                    // Parse line collection using subparser
                    foreach ($data as $ctxLines) {
                        $ctxObj = File_Therion_Centreline::parse($ctxLines);
                        $survey->addCentreline($ctxObj);
                    }
                break;
                
                case 'map':
                    // Parse line collection using subparser
                    foreach ($data as $ctxLines) {
                        $ctxObj = File_Therion_Map::parse($ctxLines);
                        $survey->addMap($ctxObj);
                    }
                break;
                
                case 'surface':
                    // Parse line collection using subparser
                    foreach ($data as $ctxLines) {
                        $ctxObj = File_Therion_Surface::parse($ctxLines);
                        $survey->addSurface($ctxObj);
                    }
                break;
                
                case 'scrap':
                    // Parse line collection using subparser
                    foreach ($data as $ctxLines) {
                        $ctxObj = File_Therion_Scrap::parse($ctxLines);
                        $survey->addScrap($ctxObj);
                    }
                break;
                
                default:
                    throw new File_Therion_SyntaxException(
                        "unsupported multiline command '$type'"
                    );
            }
        }


        // Parse postponed local lines
        // this is neccessary because some commands reference stations, however
        // therion is not dependent on ordering of lines.
        foreach ($postponeLineParsing as $line) {
            $lineData = $line->getDatafields();
            $command  = strtolower(array_shift($lineData));
            
            switch ($command) {
                case 'equate':
                    // resolve stations and establish equate
                    if (count($lineData) < 2) {
                        throw new File_Therion_SyntaxException(
                            "equate command needs at least two arguments, "
                            .count($lineData)." given "
                            ."('".trim($line->toString())."')"
                        );
                    }
                    
                    // resolve first station
                    try {
                        $strRef  = array_shift($lineData);
                        $ref     = new File_Therion_Reference($strRef, $survey);
                        $station = $ref->getObject();
                        
                        try {
                            // resolve remaining stations and equate them
                            while ($sref = array_shift($lineData)) {
                                $refo    = new File_Therion_Reference(
                                                $sref, $survey);
                                $stn = $refo->getObject();
                                $station->addEquate($stn);
                            }
                        } catch (Exception $exc) {
                            // not possible to dereference station:
                            // ignore silently for now (->partial data?)
                        }
                    } catch (Exception $exc) {
                        // not possible to dereference first station:
                        // ignore silently for now (->partial data?)
                    }
                    
                break;

            }
        }
        
        
        return $survey;
        
    }
    
    
    /**
     * Generate line content from this object.
     * 
     * @return array File_Therion_Line objects
     * @todo finish implementation, implement proper escaping
     * @todo skip deep equates that are already contained in normal equates
     */
    public function toLines()
    {
        $lines = array();
        
        /*
         * create header
         */
        $hdr = "survey ".$this->getName(); // start
        $hdr .= $this->getOptionsString(); // add options
        $lines[] = new File_Therion_Line($hdr, "", "");
        
        /*
         * create subobjects lines
         */
        $baseIndent = "\t";

        // centrelines
        foreach ($this->getCentrelines() as $sobj) {
            foreach ($sobj->toLines() as $l) {
                $l->setIndent($baseIndent.$l->getIndent());
                $lines[] = $l;
            }
            unset($l);
        }
        unset($sobj);
        
        // equates
        foreach ($this->getEquates() as $stn) {
                //print "DBG: seen equated station: '".."' -> ''\n";
                $lines[] = new File_Therion_Line(
                    $stn->toEquateString($this), "", $baseIndent);
            unset($stn);
        }
        unset($eqs);
        // TODO: we can skip deep equates in case the equated stationRefs are
        //       already contained in normal equate strings
        foreach ($this->getDeepEquates() as $stn) {
            //print "DBG: seen deepequated station: '".."' -> ''\n";
            $lines[] = new File_Therion_Line(
                $stn->toEquateString($this), "", $baseIndent);
            unset($stn);
        }
        unset($eqs);
        
        // scraps
        foreach ($this->getScraps() as $sobj) {
            foreach ($sobj->toLines() as $l) {
                $l->setIndent($baseIndent.$l->getIndent());
                $lines[] = $l;
            }
            unset($l);
        }
        unset($sobj);
        
        // joins
        foreach ($this->getJoins() as $sobj) {
            foreach ($sobj as $j) {
                $l = $j->toLines();
                $l->setIndent($baseIndent.$l->getIndent());
                $lines[] = $l;
            }
            unset($j);
        }
        unset($sobj);
        
        // maps
        foreach ($this->getMaps() as $sobj) {
            foreach ($sobj->toLines() as $l) {
                $l->setIndent($baseIndent.$l->getIndent());
                $lines[] = $l;
            }
            unset($l);
        }
        unset($sobj);
        

        // surfaces
        foreach ($this->getSurfaces() as $sobj) {
            foreach ($sobj->toLines() as $l) {
                $l->setIndent($baseIndent.$l->getIndent());
                $lines[] = $l;
            }
            unset($l);
        }
        unset($sobj);
        
        // subsurveys
        foreach ($this->getSurveys() as $sobj) {
            foreach ($sobj->toLines() as $l) {
                $l->setIndent($baseIndent.$l->getIndent());
                $lines[] = $l;
            }
            unset($l);
        }
        unset($sobj);
        
        
        /*
         *  create footer
         */
        $footr = "endsurvey ".$this->getName();
        $lines[] = new File_Therion_Line($footr, "", "");
        
        // done, go home
        return $lines;
    }
    
    
    /**
     * Get name (id) of this survey.
     * 
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }
    
    /**
     * Change name (id) of this survey.
     * 
     * @return string
     * @todo implement parameter checks
     */
    public function setName($id)
    {
        $this->_name = $id;
    }
    
    
    /**
     * Get equated stations of this survey.
     * 
     * This evaluates all stations of this survey and returns those, wo have
     * forward equates that are valid seen from the local survey context.
     * 
     * Backlinked equates will be skipped if the survey context of the forward-
     * linked station equals the equated stations context (i.e. skip the
     * backlink when forward-link was already considered).
     * 
     * @return array of {@link File_Therion_Station} objects
     */
    public function getEquates()
    {
        // inspect all local stations from all local centrelines
        $equated_stations = array();
        foreach ($this->getAllStations(0) as $stn) {
            if ($stn->toEquateString($this) != "") {
                // viewed from this survey, the station has
                // some resolvable equates.
                // Backlinks in same context are skipped automatically from
                // the Station class toEquateString() method.
                $equated_stations[] = $stn;
            }
        }
        
        return $equated_stations;
    }
    
    /**
     * Get equated stations of lower level surveys.
     * 
     * This evaluates all stations of lower level surveys and returns those,
     * wo have equates that are valid seen from the local survey context but
     * cannot be fully referenced in the next deeper survey context of
     * the survey structure path they belong to.
     * 
     * Viewed from the stations local survey perspective:
     * This happens when stations of a local survey are equated with stations
     * from another survey which is not part of this surveys tree (station is
     * not local and not in a child survey) but share a common parent survey.
     * 
     * Essentially, this reports all station eqautes of all subsurveys that are
     * only fully referenceable from the local perspective of this survey.
     * 
     * @return array of {@link File_Therion_Station} objects
     */
    public function getDeepEquates()
    {        
        // get all stations with equates from all subsurveys
        $allStns = $this->getAllStations(-1);
        
        // now see, if the equating string result differs dependig on context:
        // - when they are equal, this means, that the station equates could be
        //   resolved fully in the child survey, which means we can skip here.
        // - when not, this means that the equate must be placed at the local
        //   survey context because there are stations that are only visible
        //   from here.
        $equated_stations = array();
        $processedEquates = array();
        foreach ($allStns as $stn) {
            // skip station if it has no equates defined
            if (count($stn->getEquates()) == 0 ) continue;
            
            $localEqres  = $stn->toEquateString($this);
            $localEqresC = count(explode(" ", $localEqres));
            
            foreach($this->getSurveys() as $srvy) {
                // check if the stations parent survey structure belongs
                // to this subsurvey. It makes only sense to compare if
                // we can expect valid results.
                try {
                    $ref = new File_Therion_Reference($stn, $srvy);
                    $parentPath = $ref->getSurveyPath();
                     
                } catch (File_Therion_InvalidReferenceException $exc) {
                    // station is not referenceable from child survey,
                    // that is: it does not belong to this branch of the tree
                    continue; //skip this child survey
                }
                
                $childEqres  = $stn->toEquateString($srvy);
                $childEqresC = count(explode(" ", $childEqres));
                if ($childEqresC < $localEqresC) {
                    // filter duplicates (backlinks!) without altering order
                    $localEqresSRT = explode(" ", $localEqres);
                    sort($localEqresSRT);
                    if (!in_array($localEqresSRT, $processedEquates)) {
                        // stations equates could not be referenced to the
                        // extend as we have seen it in local context!
                        $equated_stations[] = $stn;
                        $processedEquates[] = $localEqresSRT; // cache result
                        
                    } else {
                        // just ignore the equate: we already processed those
                        // exact equate command (but maybe in different order)
                    }
                    
                } else {
                    // ignore this station because child surveys equate is the
                    // same as the local equate result string
                }
            }
        }
        
        return $equated_stations;
    }
    
    /**
     * Add a subsurvey.
     * 
     * Adds a survey as subsurvey to this survey and updates its
     * parent reference.
     * 
     * A {@link File_Therion_Exception} is thrown, when the child survey is
     * already present as one of the parents.
     * 
     * Example:
     * <code>
     * $subsurvey = new File_Therion_Survey("fooSurvey");
     * // $subsurvey->....
     * $survey->addSurvey($subsurvey);
     * </code>
     * 
     * @param {@link File_Therion_Survey} $subsurvey Survey object to add
     * @throws File_Therion_Exception at loop reference
     * @todo unreference children from old parent when reassigning children
     */
    public function addSurvey(File_Therion_Survey $subsurvey)
    {
        // walk parents upwards and search for subsurvey
        $next = $this;
        while (!is_null($next)) {
            $next = $next->getParent();
            if ($next == $subsurvey) {
                throw new File_Therion_Exception(
                    "survey '".$subsurvey->getName()
                    ."' is a parent of '".$next->getName()."'!"
                );
            }
        }
        
        $subsurvey->setParent($this); // update parent
        $this->_surveys[] = $subsurvey;
    }
    
    /**
     * Remove associated subsurveys.
     */
    public function clearSurveys()
    {
        $this->_surveys = array();
    }
    
    /**
     * Get existing subsurveys.
     * 
     * You may query for a survey in which case either the survey is
     * returned or throws an OutOfBoundsException when not found.
     *
     * @param string|File_Therion_Survey $survey Query for named survey
     * @return array of File_Therion_Survey objects
     * @throws OutOfBoundsException if no named survey is found.
     */
    public function getSurveys($survey = null)
    {
        if (is_null($survey)) {
            // return all surveys
            return $this->_surveys;
            
        } else {
            // search for named survey
            if (is_a($survey, 'File_Therion_Survey')) {
                $survey = $survey->getName();
            }
            
            foreach ($this->getSurveys() as $s) {
                if ($s->getName() === $survey) return $s;
            }
        
            // in case no such station defined:
            throw new OutOfBoundsException(
                "No such survey '".$survey."' in survey '".
                $this->getName()."'!");
        }
    }
    
    
    /**
     * Sets the parent survey of this survey.
     * 
     * Note that in contrast to {@link addSurvey()}, no references in the parent
     * will be updated: The parent does not know of its child.
     * It's mainly called by {@link addSurvey()} but may be used to create fake
     * survey structures manually (may come in handy for equate and friends
     * together with only partial survey data available as PHP objects).
     * 
     * @param File_Therion_Survey|null $parent Use null to reset parent
     */
    public function setParent(File_Therion_Survey $parent=null)
    {
        $this->_parent = $parent;
    }
    
    /**
     * Returns the parent survey of this survey.
     * 
     * Returns null when this survey has no parent set.
     * 
     * @return null|File_Therion_Survey
     */
    public function getParent()
    {
        return $this->_parent;
    }
    
    /**
     * Add a centreline definition.
     * 
     * This will implicitely update the centrelines survey context.
     * 
     * Example:
     * <code>
     * $centreline = new File_Therion_Centreline();
     * // $centreline->....
     * $survey->addCentreline($centreline);
     * </code>
     * 
     * @param File_Therion_Centreline $centreline Centreline object to add
     */
    public function addCentreline(File_Therion_Centreline $centreline)
    {
        $centreline->setSurveyContext($this);
        $this->_centrelines[] = $centreline;
    }
    
    /**
     * Remove associated centrelines.
     */
    public function clearCentrelines()
    {
        $this->_centrelines = array();
    }
    
    /**
     * Get existing Centrelines objects.
     * 
     * @return array of File_Therion_Centreline objects
     */
    public function getCentrelines()
    {
        return $this->_centrelines;
    }
    
    /**
     * Get all stations defined in all centrelines.
     * 
     * By default, only returns stations local to this survey.
     * With $maxDepth you can swith on recursion:
     * - -1 = recurse endlessly
     * - 0  = only local stations (default)
     * - >1 = recurse down to this level (1=first child level, etc)
     * 
     * @param boolean $maxDepth
     * @return array containing {@link File_Therion_Station} objects
     */
    public function getAllStations($maxDepth = 0)
    {
        $stns = array();
        
        // return local stations
        foreach ($this->getCentrelines() as $cl) {
            $stns = array_merge($stns, $cl->getStations());
            // todo we probably need to filter out duplicate stations
        }
        
        // recurse if requested
        if ($maxDepth <= -1 || $maxDepth > 1) {
            $maxDepth--;
            foreach ($this->getSurveys() as $srvy) {
                $stns = array_merge($stns, $srvy->getAllStations($maxDepth));
            }
        }
        
        return $stns;
    }
    
    /**
     * Add a scrap object.
     * 
     * Example:
     * <code>
     * $scrap = new File_Therion_Scrap("fooScrap");
     * // $scrap->....
     * $survey->addScrap($scrap);
     * </code>
     * 
     * @param File_Therion_Scrap $scrap Map object to add
     */
    public function addScrap(File_Therion_Scrap $scrap)
    {
        $this->_scraps[] = $scrap;
    }
    
    /**
     * Remove associated scraps.
     */
    public function clearScraps()
    {
        $this->_scraps = array();
    }
    
    /**
     * Get existing Scrap objects.
     * 
     * @return array of File_Therion_Scrap objects
     */
    public function getScraps()
    {
        return $this->_scraps;
    }
    
    /**
     * Add a scrap join.
     * 
     * Example:
     * <code>
     * $join = new File_Therion_Join($someScrap, $otherScrap);
     * $survey->addJoin($join);
     * </code>
     * 
     * @param File_Therion_Join|array $join Single or multiple joins.
     */
    public function addJoin(File_Therion_Join $join)
    {
        $this->_joins[] = $join;
    }
    
    /**
     * Clean existing scrap joins.
     */
    public function clearJoins()
    {
        $this->_joins = array();
    }
    
    /**
     * Get existing scrap joins.
     * 
     * @return array {@link File_Therion_Join} objects describing the joins
     */
    public function getJoins()
    {
        return $this->_joins;
    }
    
    /**
     * Add a map definition.
     * 
     * Example:
     * <code>
     * $map = new File_Therion_Map("fooMap");
     * // $map->....
     * $survey->addMap($map);
     * </code>
     * 
     * @param File_Therion_Map $map Map object to add
     */
    public function addMap(File_Therion_Map $map)
    {
        $this->_maps[] = $map;
    }
    
    /**
     * Remove associated maps.
     */
    public function clearMaps()
    {
        $this->_maps = array();
    }
    
    /**
     * Get existing Map objects.
     * 
     * @return array of File_Therion_Map objects
     */
    public function getMaps()
    {
        return $this->_maps;
    }
    
    /**
     * Add a surface definition.
     * 
     * Example:
     * <code>
     * $surface = new File_Therion_Surface();
     * // $surface->....
     * $survey->addMap($surface);
     * </code>
     * 
     * @param File_Therion_Surface $surface Surface object to add
     */
    public function addSurface(File_Therion_Surface $surface)
    {
        $this->_surface[] = $surface;
    }
    
    /**
     * Remove associated surface definitions.
     */
    public function clearSurface()
    {
        $this->_surface = array();
    }
    
    /**
     * Get existing surface objects.
     * 
     * @return array of File_Therion_Surface objects
     */
    public function getSurfaces()
    {
        return $this->_surface;
    }
    
    
    /**
     * Count subsurveys of this survey (SPL Countable).
     *
     * @return int number of subsurveys
     */
    public function count()
    {
        return count($this->_surveys);
    }
    
    
}

?>