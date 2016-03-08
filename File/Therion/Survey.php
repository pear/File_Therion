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
    extends File_Therion_AbstractObject
    implements Countable
{
    
    
    /**
     * Associated subsurvey structures
     * 
     * @var array
     */
    protected $_surveys = array();
    
    /**
     * Associated centrelines
     * 
     * @var array
     */
    protected $_centrelines = array();
    
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
     * Associated stations
     * 
     * @var array of station objects
     */
    protected $_stations = array();
    
    /**
     * Associated maps
     * 
     * @var array of map objects
     */
    protected $_maps = array();
    
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
        'title' => "",
    );
    
    
    
    /**
     * Create a new therion survey object.
     *
     * @param string $id Name of the survey
     * @todo Restrict naming convention, not all characters are allowed!
     */
    public function __construct($id, $options = array())
    {
        $this->_name = $id;
        $this->setOptions($options);
    }
    
    
    /**
     * Parses given Therion_Line-objects into internal data structures.
     * 
     * @param array $lines array of File_Therion_Line objects containing a survey
     * @return File_Therion_Survey Survey object
     * @throws PEAR_Exception with wrapped lower level exception
     * @todo implement me
     */
    public static function parse($lines)
    {        
        if (!is_array($lines)) {
            throw new PEAR_Exception(
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
                    $flData[1],
                    $firstLine->extractOptions()
                );
            } else {
                throw new File_Therion_SyntaxException(
                    "First survey line is expected to contain survey definition"
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
            if (!strtolower($flData[0]) == "endsurvey") {
                throw new File_Therion_SyntaxException(
                    "Last survey line is expected to contain endsurvey definition"
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
                                    $survey->_joins[] = $lineData;
                                break;
                                
                                case 'equate':
                                    // Equates: add the remaining data fields
                                    $survey->_equates[] = $lineData;
                                break;
                                
                                default:
                                    throw new PEAR_Exception(
                                     "parse(): unsupported command '$command'");
                            }
                        }
                    }
                break;
                
                case 'survey':
                    // Parse line collection using subparser
                    foreach ($data as $ctxLines) {
                        $ctxObj = File_Therion_Survey::parse($ctxLines);
                        $survey->_surveys[] = $ctxObj;
                    }
                break;
                
                case 'centreline':
                    // Parse line collection using subparser
                    foreach ($data as $ctxLines) {
                        $ctxObj = File_Therion_Centreline::parse($ctxLines);
                        $centrelines->_surveys[] = $ctxObj;
                    }
                break;
                
                case 'map':
                    // Parse line collection using subparser
                    // TODO
                    //foreach ($data as $ctxLines) {
                    //    $ctxObj = File_Therion_Map::parse($ctxLines);
                    //    $survey->_maps[] = $ctxObj;
                    //}
                break;
                
                case 'surface':
                    // Parse line collection using subparser
                    foreach ($data as $ctxLines) {
                        // TODO
                        //$ctxObj = File_Therion_Surface::parse($ctxLines);
                        //$survey->_surface[] = $ctxObj;
                    }
                break;
                
                default:
                    throw new PEAR_Exception("parse(): unsupported type '$type'");
            }
        } 
        
        return $survey;
        
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
