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
     * Associated equate definitions
     * 
     * @var array of equal-station-arrays ($x[n]=array(1, 2, 3, ...))
     */
    protected $_equates = array();
    
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
                                    // Equates: add the remaining data fields
                                    $survey->addEquate($lineData);
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
        
        return $survey;
        
    }
    
    
    /**
     * Generate line content from this object.
     * 
     * @return array File_Therion_Line objects
     * @todo finish implementation, implement proper escaping
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
        foreach ($this->getEquates() as $sobj) {
            foreach ($sobj as $e) {
                $l = $e->toLines();
                $l->setIndent($baseIndent.$l->getIndent());
                $lines[] = $l;
            }
        }
        unset($sobj);
        
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
     * Add a station equate.
     * 
     * The stations to equate must have a valid survey context. This is usually
     * the case automatically when the stations are part of a centreline that
     * was added to a survey.
     * 
     * The survey context of the equate command will be updatet to the
     * local survey.
     * 
     * Example:
     * <code>
     * // get from-station of first shot of first centreline
     * // get to-station of third shot of second centreline
     * $station1 = $survey->getCentrelines()[0]->getShots()[0]->getFrom();
     * $station2 = $survey->getCentrelines()[1]->getShots()[2]->getTo();
     * 
     * // make them equal
     * $equate = new File_Therion_Equate($station1, $station2);
     * 
     * // add that equality definition to survey
     * $survey->addEquate($equate);
     * </code>
     * 
     * @param string|array station equates.
     * @throws File_Therion_SyntaxException when equate < 2 stations
     */
    public function addEquate(File_Therion_Equate $equate)
    {
        // check parameters
        if (count($equate) < 2) {
            throw new File_Therion_SyntaxException(
                "Missing argument: equate command expects >=2 stations");
        }
        
        $equate->setSurveyContext($this); // update context
        
        $this->_equates[] = $equate;
    }
    
    /**
     * Clean existing equates.
     */
    public function clearEquates()
    {
        $this->_equates = array();
    }
    
    /**
     * Get existing equates.
     * 
     * Each unique definition forms one array element.
     * 
     * @return array of {@link File_Therion_Equate} objects
     */
    public function getEquates()
    {
        return $this->_equates;
    }
    
    /**
     * Add a subsurvey.
     * 
     * Adds a survey as subsurvey to this survey and updates its
     * parent reference.
     * 
     * Example:
     * <code>
     * $subsurvey = new File_Therion_Survey("fooSurvey");
     * // $subsurvey->....
     * $survey->addSurvey($subsurvey);
     * </code>
     * 
     * @param File_Therion_Survey $subsurvey Survey object to add
     */
    public function addSurvey(File_Therion_Survey $subsurvey)
    {
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
     * @return array of File_Therion_Survey objects
     */
    public function getSurveys()
    {
        return $this->_surveys;
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