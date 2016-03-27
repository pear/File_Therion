<?php
/**
 * Therion cave scrap object class.
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
 * Class representing a therion scrap object.
 * 
 * A scrap is a digital vectorized sketch with enriched cave data.
 *
 * @category   file
 * @package    File_Therion
 * @author     Benedikt Hallinger <beni@php.net>
 * @copyright  2016 Benedikt Hallinger
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @link       http://pear.php.net/package/File_Therion/
 */
class File_Therion_Scrap
    extends File_Therion_BasicObject
    implements Countable
{
    
    /**
     * Scrap name (ID)
     * 
     * @var array
     */
    protected $_name = null;
    
    /**
     * Scrap options (title, ...).
     * 
     * @var array assoc array
     */
    protected $_options = array(
        'title'         => "",
        'scale'         => "", // 4 forms possible
        'projection'    => "",
        'author'        => array(), // array of arrays(<date>,<persons>)
        'flip'          => "",
        'cs'            => "", // coord system
        'stations'      => array(), // list of station names (to be plotted)
        'scetch'        => array(), // <filename> <x> <y>
        'walls'         => "",
        'station-names' => array(), // <prefix> <suffix> (like in centreline)
        'copyright'     => array()  // <date> <string>
    );
    
    
    /**
     * Basic data elements.
     * 
     * @var array
     */
    protected $_data = array(
        'join' => array(),
    );
    
    /**
     * Scrap joins.
     * 
     * @var array
     */
    protected $_joins = array();
    
    
    /**
     * Objects of this scrap.
     * 
     * will be populated by {@link parse()} or {@link addObject()}.
     * Order of objects matters for later rendering.
     * 
     * @access protected
     * @var array of data (File_Therion_Scrap* objects)
     */
    protected $_objects = array();
    
    
    /**
     * Create a new therion Scrap object.
     *
     * @param string $id Name/ID of the scrap
     * @todo Restrict naming convention, not all characters are allowed!
     */
    public function __construct($id, $options = array())
    {
        $this->_name = $id;
        $this->setOption($options);
    }
    
    
    /**
     * Parses given Therion_Line-objects into internal data structures.
     * 
     * @param array $lines File_Therion_Line objects forming a scrap
     * @return File_Therion_Scrap Scrap object
     * @throws InvalidArgumentException
     * @throws File_Therion_SyntaxException
     * @todo implement me better/more complete
     */
    public static function parse($lines)
    {
        if (!is_array($lines)) {
            throw new InvalidArgumentException(
                'parse(): Invalid $lines argument (expected array, seen:'
                .gettype($lines).')'
            );
        }
        
        $scrap = null; // constructed scrap
        
        // get first line and construct scrap hull object
        $firstLine = array_shift($lines);
        if (is_a($firstLine, 'File_Therion_Line')) {
            $flData = $firstLine->getDatafields();
            if (strtolower($flData[0]) == "scrap") {
                $scrap = new File_Therion_Scrap(
                    $flData[1], // id, mandatory
                    $firstLine->extractOptions()
                );
            } else {
                throw new File_Therion_SyntaxException(
                    "First scrap line is expected to contain scrap definition"
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
            if (!strtolower($flData[0]) == "endscrap") {
                throw new File_Therion_SyntaxException(
                    "Last scrap line is expected to contain endscrap definition"
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
                            $command  = array_shift($lineData);
                            
                            switch (strtolower($command)) {
                                case 'input':
                                    // ignore silently because this should be 
                                    // handled at the file level
                                break;
                                
                                case 'join':
                                    // Scrapjoins: add the remaining data fields
                                    $scrap->addJoin($lineData);
                                break;
                                
                                case 'point':
                                    // points
                                    // TODO
                                break;
                                
                                default:
                                    throw new File_Therion_SyntaxException(
                                     "unsupported scrap command '$command'");
                            }
                        }
                    }
                break;
                
                case 'area':
                    // Parse line collection using subparser
                    foreach ($data as $ctxLines) {
                        $ctxObj = File_Therion_ScrapArea::parse($ctxLines);
                        $scrap->addObject($ctxObj);
                    }
                break;
                
                case 'line':
                    // Parse line collection using subparser
                    foreach ($data as $ctxLines) {
                        $ctxObj = File_Therion_ScrapLine::parse($ctxLines);
                        $scrap->addObject($ctxObj);
                    }
                break;
                
                default:
                    throw new File_Therion_SyntaxException(
                        "unsupported multiline scrap command '$type'"
                    );
            }
        } 
        
        return $scrap;
        
    }
    
    
    /**
     * Count number of elements of this scrap (SPL Countable).
     *
     * @return int number of elements
     */
    public function count()
    {
        return count($this->_data);
    }
    
    
    /**
     * Add an area definition to this scrap.
     * 
     */
    //public function addArea()
    //{
    //}


    /**
     * Clear associated objects.
     * 
     * This will unassociate all registered objects.
     * You probably want to call {@link clearLines()} hereafter to also clean the
     * calculated line content.
     */
    public function clearObjects()
    {
         $this->_objects = array();
    }
     
    /**
     * Add an File_Therion_Scrap* data model object to this scrap.
     * 
     * Accepted types are:
     * - File_Therion_ScrapPoint
     * - File_Therion_ScrapLine
     * - File_Therion_ScrapScrapArea
     * 
     * Note that for later rendering, object ordering matters.
     * 
     * @param object $scrapObj File_Therion_Scrap* object to add
     */
    public function addObject($scrapObj)
    {
        if (!is_object($scrapObj)
        || !preg_match('/^File_Therion_Scrap(Point|Line|Area)$/',
                get_class($scrapObj))) {
            throw new InvalidArgumentException(
                "addObject() expects Scrap object, "
                .getType($scrapObj)." given!");
        }
        
        $this->_objects[] = $scrapObj;
    }
     
    /**
     * Get all associated objects of this file.
     * 
     * You can optionaly query for specific types using $filter.
     * You may ommit the prefix 'File_Therion_' from the filter.
     * 
     * Example:
     * <code>
     * $allObjects = $scrap->getObjects(); // get all
     * $surveys    = $scrap->getObjects('File_Therion_ScrapLine'); // get lines
     * $surveys    = $scrap->getObjects('Line'); // get lines
     * </code>
     *
     * @param string $filter File_Therion_Scrap* class name, retrieve only objects of that kind
     * @return array of File_Therion_Scrap* objects (empty array if no such objects)
     * @throws InvalidArgumentException
     */
    protected function getObjects($filter = null)
    {
         if (is_null($filter)) {
            return $this->_objects;
            
        } else {
            // allow shorthands (ommitting class prefix)
            if (!preg_match('/^File_Therion_Scrap/', $filter)) {
                $filter = 'File_Therion_Scrap'.$filter;
            }
            
            $supported = array(
                "File_Therion_ScrapPoint",
                "File_Therion_ScrapLine",
                "File_Therion_ScrapArea"
            );
            if (!in_array($filter, $supported)) {
                throw new InvalidArgumentException(
                    'getObjects(): Invalid $filter argument ('.$filter.')!'
                );
            }
            
            $rv = array();
            foreach ($this->_objects as $o) {
                if (get_class($o) == $filter) {
                    $rv[] = $o;
                }
            }
            return $rv;
        }
    }
    
    
    /**
     * Get all associated objects of this scrap.
     * 
     * Example:
     * <code>
     * $allObjects = $scrap->getObjects(); // get all
     * </code>
     *
     * @return array of File_Therion_Scrap* objects (empty array if no such objects)
     */
    public function getAllObjects()
    {
        return $this->getObjects();
    }
    
    /**
     * Get all Point objects of this scrap.
     * 
     * @return array File_Therion_ScrapPoint objects
     */
    public function getPoints()
    {
        return $this->getObjects('File_Therion_ScrapPoint');
    }
    
    /**
     * Get all Line objects of this scrap.
     * 
     * @return array File_Therion_ScrapLine objects
     */
    public function getLines()
    {
        return $this->getObjects('File_Therion_ScrapLine');
    }
    
    /**
     * Get all Area objects of this scrap.
     * 
     * @return array File_Therion_ScrapArea objects
     */
    public function getAreas()
    {
        return $this->getObjects('File_Therion_ScrapArea');
    }
    
    
    /**
     * Add a scrap join.
     * 
     * Example:
     * <code>
     * $survey->addJoin("ew1:0", "ew2:end"); // normal join
     * $survey->addJoin("ew1:0", "ew2:end", "ew3:2"); // threesome
     * </code>
     * 
     * @param string|array $join Single or multiple scrap joins.
     * @throws File_Therion_SyntaxException
     * @todo maybe invent join datatype and consider this too...
     * @todo add syntax checks
     */
    public function addJoin($src=null, ...$tgts)
    {
        if (!is_array($src)) {
            $src = array($src);
        }
        
        
        $merged = array_merge($src, $tgts);
        
        // check parameters
        if (count($merged) < 2) {
            throw new File_Therion_SyntaxException(
                "Missing argument: expected >=2 elements");
        }
        foreach ($merged as $j) {
            if (!is_string($j)) {
                throw new File_Therion_SyntaxException(
                    "Invalid argument expected string");
            }
        }
        
        // homogenize and add
        $this->_joins[] = $merged;
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
     * Each unique definition forms one array element.
     * Each level has one array containing all join arguments.
     * 
     * @return array nested array
     */
    public function getJoins()
    {
        return $this->_joins;
    }

}
?>
