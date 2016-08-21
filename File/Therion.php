<?php
/**
 * Therion cave survey data file format main class
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
 * File_Therion Version
 */
define('FILE_THERION_VERSION', '0.3.0');

/**
 * Package includes.
 */
require_once 'File/Therion/Exception.php';
require_once 'File/Therion/BasicObject.abstract.php';
require_once 'File/Therion/DataTypes/DataTypeInterface.php';
require_once 'File/Therion/DataTypes/Person.php';
require_once 'File/Therion/DataTypes/Date.php';
require_once 'File/Therion/DataTypes/Reference.php';
require_once 'File/Therion/Line.php';
require_once 'File/Therion/Survey.php';
require_once 'File/Therion/Centreline.php';
require_once 'File/Therion/Shot.php';
require_once 'File/Therion/Station.php';
require_once 'File/Therion/Map.php';
require_once 'File/Therion/Surface.php';
require_once 'File/Therion/Scrap.php';
require_once 'File/Therion/ScrapPoint.php';
require_once 'File/Therion/ScrapLine.php';
require_once 'File/Therion/ScrapArea.php';
require_once 'File/Therion/Join.php';
require_once 'File/Therion/Writers/WriterInterface.php';
require_once 'File/Therion/Writers/DirectWriter.php';
require_once 'File/Therion/Writers/ConsoleWriter.php';
require_once 'File/Therion/Writers/StructuredWriter.php';
require_once 'File/Therion/Readers/ReaderInterface.php';
require_once 'File/Therion/Readers/FileReader.php';


/**
 * Class representing a therion file.
 * 
 * Also serves functions to parse and write data to/from File_Therion objects.
 *
 * Therion (http://therion.speleo.sk/) is an openSource application for managing
 * cave survey data.
 *
 * The data structure follows mostly the SQL diagram in the therion book (see
 * Chapter 'SQL export'), in short:
 * - A .th-file contains surveys.
 * - Surveys can be nested.
 * - Surveys contain metadata (people etc) and one ore more centreline(s).
 * - A Centreline contain shots and stations.
 * - Shots and stations can contain flags.
 * 
 * The Data format is specified in the therion book, but basicly a file contains
 * human readable text lines following a specific syntax and describing the
 * therion objects. A logical line may be "wrapped"; that is, its content can be
 * spread out over several physical lines.
 * 
 * There are two basic workflows:
 * <code>
 * // 1. Read datasource and parse to objects:
 * $src = "some/local/file.th";  // physical filename
 * $th = new File_Therion($src); // Instanciate new datasource
 * $th->fetch();                 // Get contents (read)
 * $th->evalInputCMD();          // evaluate 'input' commands recursively
 * $th->updateObjects();         // Generate Therion objects to work with
 * $surveys = $th->getSurveys(); // example: retrieve parsed surveys
 * 
 * // The above can be summed up with:
 * $th = File_Therion::parse($file); // fetch file recursively
 * $surveys = $th->getSurveys();     // get surveys
 *
 * // you may also supply a different Reader plugin to fetch from
 * // other sources like internet URLs or even databases.
 * 
 * 
 * // 2. Generate a .th Therion file out of data model:
 * $survey = new File_Therion_Survey();
 * // $survey->....  // do many things: craft data model
 * $tgt = "some/local/target.th"; // usually local file
 * $th = new File_Therion($tgt);  // Instanciate new data target
 * $th->addObject($survey);       // associate therion data model objects
 * $th->updateLines();            // update internal lines using data objects
 * $th->write();                  // physically write to data target $tgt
 * $th->toString();               // altenatively: fetch  data as string
 * </code>
 *
 * @category   file
 * @package    File_Therion
 * @author     Benedikt Hallinger <beni@php.net>
 * @copyright  2016 Benedikt Hallinger
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @link       http://pear.php.net/package/File_Therion/
 * @todo Therion config files not supported yet - add support
 */
class File_Therion implements Countable
{

    /**
     * Reader used for this file.
     * 
     * This represents the real physical access of the content.
     * 
     * @access protected
     */
    protected $_reader = null;
    
    /**
     * Filename and path of this file.
     * 
     * This represents the real physical adress of the content on the disk.
     * 
     * @access protected
     */
    protected $_filename = '';
    
    /**
     * Encoding of this file.
     * 
     * @var string
     */
    protected $_encoding = 'UTF-8';
    
    /**
     * Currently supported encodings by therion.
     * 
     * You can get an up-to date list with the command
     * 'therion --print-encodings'. If there is a new encoding
     * that is not reflected here, please contact me.
     * 
     * @var array
     */
    protected $_supportedEncodings = array(
        'ASCII',
        'CP1250',
        'CP1251',
        'CP1252',
        'CP1253',
        'ISO8859-1',
        'ISO8859-2',
        'ISO8859-5',
        'ISO8859-7',
        'UTF-8',
    );

    /**
     * Lines of this file.
     * 
     * will be populated by {@link parse()} or {@link updateLines()}.
     * 
     * @access protected
     * @var array of data (File_Therion_Line objects)
     */
    protected $_lines = array();
    
    /**
     * objects of this file.
     * 
     * will be populated by {@link parse()} or {@link updateObjects()}
     * 
     * @access protected
     * @var array of data (File_Therion_* objects)
     */
    protected $_objects = array();
    
    /**
     * Wrapping of the file.
     * 
     * This controls the wrapping column when writing
     * 
     * @access protected
     * @var int column to wrap at (0=no wrapping)
     */
    protected $_wrapAt = 0;
    
    /**
    * Returns the current release version, may be called statically
    *
    * @static
    * @return string version
    */
    public static function getVersion()
    {
        return FILE_THERION_VERSION;
    }

    
    /**
     * Create a new therion file object representing content at $filename.
     *
     * Use this to create a new interface for parsing existing files
     * or writing new ones.
     * 
     * The $filename tells the physical file name of the file.
     * Use {@link fetch()} if you want to read the source contents or
     * {@link write()} to write the current content to the target.
     * 
     * Example:
     * <code>
     * $thFile = new File_Therion('foobar.th'); // local file (r/w access)
     * $thFile->fetch();  // get contents
     * $thFile->updateObjects();  // parse fetched contents
     * $surveys = $thFile->getObjects('File_Therion_Survey'); // get surveys
     * $all = $thFile->getObjects(); // get all parsed objects
     * </code>
     *
     * @param string $filename Name of the file represented by this object
     * @throws File_Therion_Exception with wrapped lower level exception
     */
    public function __construct($filename)
    {
           $this->setFilename($filename);
           
           // initalize default reader
           $this->setReader(new File_Therion_FileReader());
    }
    
    /**
     * Parse datasource into objects.
     * 
     * This will perform the following operations:
     * - create a new File_Therion object pointing to the datasource given
     * - {@link fetch()} the datasource
     * - {@link evalInputCMD()} optionally import referenced content ($lvls > 0)
     * - {@link updateObjects()} represented by the lines
     * 
     * After this procedure the File_Therion object returned is in a consistent
     * state (internal line buffer equals parsed content).
     * Exceptions will be bubbled up.
     * 
     * To limit the number of possible nested levels you may specify the
     * $recurse parameter (null=default: endless, 0: none, >0: nested levels).
     * 
     * Please read also the documentation of {@link fetch()},
     * {@link evalInputCMD()} and {@link updateObjects()} for informations on
     * their respective capabilitys and pitfalls.
     * 
     * @param string $url     path or URL of the file
     * @param int    $recurse restrict recursion
     * @param File_Therion_Reader $reader optionally alternative reader
     * @return File_Therion object
     * @throws File_Therion_IOException in case of reading problems
     * @throws File_Therion_SyntaxException case of parsing/syntax errors
     * @throws File_Therion_Exception for generic errors
     */
    public static function parse(
        $url,
        $recurse=null,
        File_Therion_Reader $reader=null)
    {
        $th = new File_Therion($url);
        $th->fetch($reader);
        $th->evalInputCMD($recurse);
        $th->updateObjects();
        return $th;
    }


    /**
     * Parses the internal line buffer to associated Therion objects.
     * 
     * You may use {@link fetch()} to update the internal line buffer with
     * real physical content from the datasource.
     * Alternatively you can craft the file yourself using {@link addLine()}.
     * 
     * Be aware that this function cleans all references to associated objects.
     *
     * @throws InvalidArgumentException
     * @throws File_Therion_SyntaxException if parse errors occur
     */
    public function updateObjects()
    {
        $this->checkSyntax();
        
        $this->clearObjects();  // clean references
        
        // split file into contextual ordering
        $orderedData = File_Therion::extractMultilineCMD($this->getLines());
        
        // Walk results and try to parse it in file context.
        // We delegate as much as possible, so we just honor commands
        // the file level knows about.
        // Other lines will be collected and given to a suitable parser.
        foreach ($orderedData as $type => $data) {
            switch ($type) {
                case 'LOCAL':
                    // walk each local line and parse it
                    foreach ($data as $line) {
                        if (!$line->isCommentOnly()) {
                            $lineData = $line->getDatafields();
                            $command  = array_shift($lineData);
                            switch ($command) {
                                case 'encoding':
                                    $this->setEncoding($lineData[0]);
                                break;
                                
                                //case 'join':
                                //  TODO: Support lone join commands in file
                                //break;
                                
                                //case 'equate':
                                //  TODO: Support lone join commands in file
                                //break;
                                
                                /* TODO: switch on syntax check
                                 *       Once we have identified and coded all
                                 *       possible commands at this level
                                default:
                                    throw new File_Therion_SyntaxException(
                                       "unsupported multiline command '$type'");
                                */
                            }
                        }
                    }
                break;
                
                // Multiline commands:
                // walk each line collection and parse it using subparser
                case 'survey':
                    foreach ($data as $ctxLines) {
                        $ctxObj = File_Therion_Survey::parse($ctxLines);
                        $this->addObject($ctxObj);
                    }
                break;
                case 'centreline':
                    foreach ($data as $ctxLines) {
                        $ctxObj = File_Therion_Centreline::parse($ctxLines);
                        $this->addObject($ctxObj);
                    }
                break;
                case 'scrap':
                    foreach ($data as $ctxLines) {
                        $ctxObj = File_Therion_Scrap::parse($ctxLines);
                        $this->addObject($ctxObj);
                    }
                break;
                case 'map':
                    foreach ($data as $ctxLines) {
                        $ctxObj = File_Therion_Map::parse($ctxLines);
                        $this->addObject($ctxObj);
                    }
                break;
                case 'surface':
                    foreach ($data as $ctxLines) {
                        $ctxObj = File_Therion_Surface::parse($ctxLines);
                        $this->addObject($ctxObj);
                    }
                break;
                
                default:
                    throw new File_Therion_SyntaxException(
                        "unsupported multiline command '$type'");
            }
        } 
        
        
        // TODO: Parsing done! Investigate: can we check some errors now?
        
        return; 
    }
    
    
    /**
     * Set default reader that is used to fetch file content.
     * 
     * @param File_Therion_Reader $reader
     */
    public function setReader(File_Therion_Reader $reader)
    {
        $this->_reader = $reader;
    }
    
    /**
     * Update the internal line representation of this file from datasource.
     * 
     * This will use the supplied Reader to fetch therion file content.
     * If no Reader was supplied (NULL/missing value), the internal
     * default reader will be used ({@link File_Therion_FileReader}).
     * The supplied reader will be also used for subsequent reads,
     * like when resolving input commands.
     * 
     * Be aware that the Reader usually clears the internal line buffer, so any
     * changes made by {@link addLine()} get discarded. Please refer to
     * the readers documentation if you want to avoid this.
     * 
     * After fetching physical content, you may call {@link updateObjects()
     * to generate Therion data model objects out of it.
     * (Some Reader implementeations may already do this for you!)
     *
     * @param File_Therion_Reader $reader Reader to use (NULL for default)
     * @throws File_Therion_IOException
     * @throws InvalidArgumentException
     */
    public function fetch(File_Therion_Reader $reader = null)
    {
        // use default reader in case null parameter
        if (is_null($reader)) {
            $reader =& $this->_reader;
        } else {
            // store reader for later use (eg. evalInputCMD())
            $this->setReader($reader);
        }
        
        // fetch the file!
        $reader->fetch($this);
        
    }
    
    /**
     * Update the internal line representation of this file from contained objects.
     * 
     * This will generate therion file lines out of the associated objects.
     * 
     * @todo implement me
     */
    public function updateLines()
    {
        $this->_lines = array(); // clean existing line content
        
         // walk trough the associated objects and ask them to generate lines;
         // populate
    }
     
     
    /**
     * Add a line to this file.
     * 
     * The optional index parameter allows to adjust the insertion
     * point; the line will be inserted at the index, pushing already
     * present content one line down (-1=end, 0=start, ...).
     * When replacing, the selected index will be replaced.
     * 
     * Instead of $index 0 and -1 you can use the strings 'start'/'end',
     * this will make your code more readable.
     * Using <code>addLine(..., $lln - 1)</code> will use logical line number
     * instead of the index (logical = index+1).
     * 
     * Beware that {@link clearLines()} will discard any manual insertions.
     * Also be aware that {@link fetch()} will clean the line buffer too.
     * 
     * Note that addLine() will not take care of wrapping; make sure
     * that the line content remains consistent.
     * 
     * Be sure to use the right encoding for your data (-> {@link setEncoding()}
     * and {@link encode()} for more details).
     * 
     * Example:
     * <code>
     * // add a simple line (implicitely to the end):
     * $th->addLine(new File_Therion_Line("somecontent"));
     * 
     * // add a line to the start, pushing previous line one down:
     * $th->addLine(new File_Therion_Line("startline"), 0);
     * // result: line-0="startline", line-1="somecontent"
     * 
     * // add another simple line (explicitely to the end):
     * $th->addLine(new File_Therion_Line("final"), 'end');
     * // result: line-0="startline", line-1="somecontent", line-3="final"
     * 
     * // replace line-1:
     * $th->addLine(new File_Therion_Line("othercontent"), 1, true);
     * // result: line-0="startline", line-1="othercontent", line-3="final"
     * </code>
     * 
     * Instead of Line objects you may also add a string as $line argument. In
     * this case a new Line object will be parsed for you. Right spaces and
     * newline will be trimmed.
     * 
     * @param File_Therion_Line|string $line Line to add
     * @param int  $index At which logical position to add (-1=end, 0=first line, ...)
     * @param bool $replace when true, the target line will be overwritten
     * @throws InvalidArgumentException
     * @throws OutOfBoundsException when requested index is not available
     */
    public function addLine($line, $index=-1, $replace=false)
    {
        // enable short string variant
        if (is_string($line)) {
            $line = File_Therion_Line::parse(rtrim($line));
        }
        
        if (!is_a($line, 'File_Therion_Line')) {
            throw new InvalidArgumentException(
                'addLine(): Invalid $line argument! '.
                "passed type='".gettype($line)."'"
            );
        }
        
        // synonyms+checks for index
        if (is_string($index) && strtolower($index) == "start") {
            $index = 0;
        } elseif (
            (is_string($index) && strtolower($index) == "end")
            || $index === -1) {
            $index = (count($this->_lines)>0)? count($this->_lines) : 0;
        } else {
            if (!is_int($index)) {
                throw new InvalidArgumentException(
                    'addLine(): Invalid $index argument! '
                    ."int expected, or string 'start' or 'end'"
                );
            }
        }
        
        
        // Handle modification
        // Note: $index is the logical line number
        if ($index > count($this->_lines)) {
            // index is bigger than current lines-length: error
            throw new OutOfBoundsException(
                "index ".$index." is > ".count($this->_lines)."!");
        }
        
        if ($replace) {
            // REPLACE MODE:
            // $index is the index to replace or END+1
            if ($index == count($this->_lines) && $index > 0) {
                // index is next free index at the end:
                // replace last known index, that is index-1
                $index--;
            }
            
            if (!array_key_exists($index, $this->_lines)) {
                throw new OutOfBoundsException(
                    "replace-index ".$index." is invalid!");
            }
            
            // replace 1 elements at index with $line
            array_splice($this->_lines, $index, 1, array($line));
         
            
        } else {
            // ADD MODE:
            // $index is the index to add or END+1
       
            if ($index == count($this->_lines)) {
                // index is next free index at the end:
                // just push to internal array
                
                // in case the prior line expects more content, $line is assumed
                // to be its continuation.
                if (count($this->_lines) > 0) {
                    $priorLine = &$this->_lines[count($this->_lines)-1];
                    if ($priorLine->isContinued()) {
                        // add to the unfinished line
                        $priorLine->addPhysicalLine($line);
                    } else {
                        // just normal add at end
                        $this->_lines[] = $line;
                    }
                } else {
                    // first line to add in this file: normal add
                    $this->_lines[] = $line;
                }
                
            } else {
                // index is smaller: add to this index, downpushing content
                if (!array_key_exists($index, $this->_lines)) {
                    throw new OutOfBoundsException(
                        "add-index ".$index." is invalid!");
                }
                
                if ($index == 0) {
                    // the add occurs on line=0, we just add there as there is
                    // never a prior line by definition
                    array_splice($this->_lines, $index, 0, array($line));
                    
                } else {
                    // in case the prior line to the existing line is continued,
                    // this line represents a continuation of that line.
                    $priorLine = &$this->_lines[$index-1];
                    if ($priorLine->isContinued()) {
                        // continuation: add the line to the existing one
                        $priorLine->addPhysicalLine($line);
                    } else {
                        // replace 0 elements (=splice in) at index with $line
                        array_splice($this->_lines, $index, 0, array($line));
                    }
                    
                }
               
            }
            
        }
        
        // See if we had an finished encoding line
        $this->handleEncodingLine();
        
    }
    
    
    /**
     * Handle 'encoding' command in internal line buffer.
     */
    protected function handleEncodingLine()
    {
        // If the last line on the stack is complete now, we can
        // investigate the line a little further...
        $mostCurrentLine =& $this->_lines[count($this->_lines)-1];
        $priorLine =& $this->_lines[count($this->_lines)-1];
        if (!$mostCurrentLine->isContinued()) {
            $mostCurrentLineData = $mostCurrentLine->getDatafields();
            
            // set file encoding if specified
            if (isset($mostCurrentLineData[0])
                && strtolower($mostCurrentLineData[0]) == 'encoding') {
                $this->setEncoding($mostCurrentLineData[1]);
            }
        }
    }
     
     
    /**
     * Get internal line buffer.
     *
     * @return array of File_Therion_Line objects
     */
    public function getLines()
    {
         return $this->_lines;
    }
     
     
    /**
     * Clear associated lines.
     * 
     * This will wipe out the internal line buffer.
     */
     public function clearLines()
     {
         $this->_lines = array();
     }
     
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
     * Add an File_Therion data model object to this file
     * 
     * Associated objects can be written to a file after {@link updateLines()}
     * has been called to update the internal line representation.
     * 
     * Be aware that {@link clearObjects()} will discard any manual changes made
     * so far, and be warned that {@link updateObjects()} will clean them too.
     * 
     * @param object $thObj File_Therion_* object to add
     * @todo implement me better: checks etc
     */
    public function addObject($thObj)
    {
         $this->_objects[] = $thObj;
    }
     
    /**
     * Get all associated objects of this file.
     * 
     * You can optionaly query for specific types using $filter.
     * You may ommit the prefix 'File_Therion_' from the filter.
     * 
     * Example:
     * <code>
     * $allObjects = $thFile->getObjects(); // get all
     * $surveys    = $thFile->getObjects('File_Therion_Survey'); // get surveys
     * $surveys    = $thFile->getObjects('Survey'); // get surveys
     * </code>
     *
     * @param string $filter File_Therion_* class name, retrieve only objects of that kind
     * @return array of File_Therion_* objects (empty array if no such objects)
     * @throws InvalidArgumentException
     */
    public function getObjects($filter = null)
    {
         if (is_null($filter)) {
            return $this->_objects;
        } else {
            // allow shorthands (ommitting class prefix)
            if (!preg_match('/^File_Therion_/', $filter)) {
                $filter = 'File_Therion_'.$filter;
            }
            $filter = ($filter=='File_Therion_Centerline')?   // allow alias
                'File_Therion_Centreline' : $filter;
            
            $supported = array(
                "File_Therion_Survey",
                "File_Therion_Centreline",
                "File_Therion_Scrap",
                "File_Therion_Map",
                "File_Therion_Surface",
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
     * Get all associated objects of this file.
     * 
     * Example:
     * <code>
     * $allObjects = $thFile->getObjects(); // get all
     * </code>
     *
     * @return array of File_Therion_* objects (empty array if no such objects)
     */
    public function getAllObjects()
    {
        return $this->getObjects();
    }
    
    /**
     * Get all directly associated surveys of this file.
     * 
     * Example:
     * <code>
     * $allSurveys = $thFile->getSurveys();
     * </code>
     *
     * @return array of File_Therion_Survey objects (or empty array)
     */
    public function getSurveys()
    {
        return $this->getObjects('File_Therion_Survey');
    }
    
    /**
     * Get all directly associated centrelines of this file.
     * 
     * Note that this kind of object is usually not "lone" in a file but 
     * commonly part of a survey. This function returns only "lone" objects
     * without parent structure.
     * 
     * Example:
     * <code>
     * $allCentrelines = $thFile->getCentrelines();
     * </code>
     *
     * @return array of File_Therion_Centreline objects (or empty array)
     */
    public function getCentrelines()
    {
        return $this->getObjects('File_Therion_Centreline');
    }
    
    /**
     * Get all directly associated Scraps of this file.
     * 
     * Note that this kind of object is usually not "lone" in a file but 
     * commonly part of a survey. This function returns only "lone" objects
     * without parent structure.
     * 
     * Example:
     * <code>
     * $allScraps = $thFile->getScraps();
     * </code>
     *
     * @return array of File_Therion_Scrap objects (or empty array)
     */
    public function getScraps()
    {
        return $this->getObjects('File_Therion_Scrap');
    }
    
    /**
     * Get all directly associated Maps of this file.
     * 
     * Note that this kind of object is usually not "lone" in a file but 
     * commonly part of a survey. This function returns only "lone" objects
     * without parent structure.
     * 
     * Example:
     * <code>
     * $allMaps = $thFile->getMaps();
     * </code>
     *
     * @return array of File_Therion_Map objects (or empty array)
     */
    public function getMaps()
    {
        return $this->getObjects('File_Therion_Map');
    }
    
    /**
     * Get all directly associated Surface definitions of this file.
     * 
     * Note that this kind of object is usually not "lone" in a file but 
     * commonly part of a survey. This function returns only "lone" objects
     * without parent structure.
     * 
     * Example:
     * <code>
     * $allSurfaces = $thFile->getSurfaces();
     * </code>
     *
     * @return array of File_Therion_Surface objects (or empty array)
     */
    public function getSurfaces()
    {
        return $this->getObjects('File_Therion_Surface');
    }
    
     
    /**
     * Write this therion file content to the file.
     *  
     * The writing will be carried out by the supplied writer object.
     * If no writer was supplied, a new {@link DirectWriter} will be
     * instantiated as default.
     *
     * @param  File_Therion_Writer $writer Writer to use.
     * @throws File_Therion_IOException in case of IO error
     * @throws File_Therion_Exception for other errors
     */
    public function write(File_Therion_Writer $writer = null)
    {
        if (is_null($writer)) {
            $writer = new File_Therion_DirectWriter();
        }
        
        // write the file!
        $writer->write($this);
    }
    
    /**
     * Get file lines as string.
     * 
     * Returns the file line content as string, suitable for writing using
     * PHPs fwrite().
     * The line ending used is depending on the current PHP_EOL constant.
     * 
     * If wrapping was requested, the file content will be wrapped at the
     * given column (see {@link setWrapping()}.
     * 
     * If no encoding is contained in internal line data, a suitable
     * encoding line will be added with the current active encoding setting.
     * The string however will be returned in UTF-8 regardless of any encoding
     * commands.
     * 
     * @return string The file contents as string
     * @see setEncoding()
     * @todo Line endings should not depend on Line class implementation
     */
    public function toString()
    {
        // Iterate over line objects composing a string
        $ret = "";
        foreach ($this->_lines as $line) {
            if ($this->_wrapAt > 0) {
                // todo: honor wrapping request by user
                throw new File_Therion_Exception('WRAPPING FEATURE NOT IMPLEMENTED');
            }
            $ret .= $line->toString();
        }
        return $ret;
    }
     
    /**
     * Set filename of this file object.
     * 
     * This will just change the path, no data will be read/written!
     * 
     * @param string $filename filename
     * @throws InvalidArgumentException in case $filename is no string
     */
    public function setFilename($filename)
    {
        if (!is_string($filename)) {
            throw new InvalidArgumentException(
                'Invalid filename type supplied ('.gettype($filename).')!'
            );
        }
        $this->_filename = $filename;
    }
      
    /**
     * Get physical file name of this file object.
     * 
     * @param string $relPath return only relative path compared to argument
     * @return string  filename
     * @throws InvalidArgumentException in case $relPath is no string
     */
    public function getFilename($relPath = "")
    {
        if (!is_string($relPath)) {
            throw new InvalidArgumentException(
                'Invalid relPath type supplied ('.gettype($relPath).')!'
            );
        }
        
        $path = $this->_filename;
        
        if ($relPath) {
            $path = str_replace(dirname($relPath), '', $path);
            $path = preg_replace('/^\/+/', '', $path); // strip leading slashes
        }
        
        return $path;
    }
     
     
    /**
     * Set wrapping column when writing.
     * 
     * The wrapping will be carried out using therions data format
     * (eg. ending the line with backslash and continuing it on the next one).
     * 
     * @param int $wrapAt wrap at this column, 0=disable
     * @throws InvalidArgumentException
     */
    public function setWrapping($wrapAt)
    {
        if (!is_int($wrapAt)) {
            throw new InvalidArgumentException('Invalid $wrapAt argument!');
        }
        $this->_wrapAt = $wrapAt;
    }
     
     
    /**
     * Count (wrapped) lines in this file (SPL Countable).
     * 
     * returns the count of physical (ie. wrapped) lines in this file.
     * To count logical lines (ie. unwrapped, or line objects), use $logical.
     *
     * @param boolean $logical If true, return logical (unwrapped) count
     * @return int number of raw lines
     */
    public function count($logical=false)
    {
        if ($logical) {
            return count($this->_lines);  // count line objects
        } else {
            $r = 0;
            foreach ($this->_lines as $l) {
                $r += count($l); // count wrapped lines
            }
            return $r;
        }
    }
    
    
    /**
     * Set encoding of input/output files.
     * 
     * This will tell what encoding to use.
     * The default assumed encoding is UTF-8.
     * 
     * This method only signals which encoding the data should be in and will
     * not actively change encoding. Make sure your data matches the encoding
     * given!
     * 
     * Only a small subset of PHP encoding names are supported by therion,
     * see {@link $_supportedEncodings} for a list.
     * 
     * @param string $codeset
     * @throws InvalidArgumentException when unsupported encoding is used
     */
    public function setEncoding($codeset)
    {
        $this->_encoding = $this->_getEncodingName($codeset);
    }
    
    /**
     * Get encoding of input/output files currently active.
     * 
     * @return string encoding
     */
    public function getEncoding()
    {
        return $this->_encoding;
    }
    
    /**
     * Encode data.
     * 
     * Will check encoding supported by therion.
     * 
     * @param string $data
     * @param string $toEncoding
     * @param string $fromEncoding
     * @return $data in toEncoding.
     * @throws InvalidArgumentException in case encoding is not supported.
     * @throws File_Therion_Exception when encoding fails.
     */
    public function encode($data, $toEncoding, $fromEncoding)
    {
        // get normalized names (throws InvalidArgumentException when unknown)
        $from = $this->_getEncodingName($fromEncoding);
        $to   = $this->_getEncodingName($toEncoding);
        
        // perform encoding and check for errors
        $r = iconv($from, $to.'//TRANSLIT', $data);
        if ($r === false) {
            throw new File_Therion_Exception(
                "Encoding failure: '$data' ($fromEncoding -> $toEncoding)");
        }
        return $r;
            
    }
    
    
    /**
     * Get internal name of encoding name.
     * 
     * @param string $name
     * @return string
     * @throws InvalidArgumentException in case encoding is not supported.
     */
    protected function _getEncodingName($name)
    {
        $normalizedName = strtoupper($name);
        if (!in_array($normalizedName, $this->_supportedEncodings)) {
            throw new InvalidArgumentException(
                "Encoding '$normalizedName' not supported");
        }
        return $normalizedName;
    }
    
    
    /**
     * Check basic syntax of internal line buffer
     * 
     * This validates basic syntax of internal file buffer.
     * The following checks will be performed:
     *   - last line should not expect additional data
     *   - matching multiline commands
     * 
     * @throws File_Therion_SyntaxException if syntax errors occur
     * @todo implement me
     */
    public function checkSyntax()
    {
        $lines =& $this->_lines;
        
        if ($lines[count($lines)-1]->isContinued()) {
            throw new File_Therion_SyntaxException(
                "Data incomplete: last line still expects another physical line!"
                );
        }
        
        // TODO search matching multiline for survey / scrap / centreline
    }
    
    
    /**
     * Scan local filebuffer for 'input' commands and execute them.
     * 
     * Therions 'input' statement will include the remote file content
     * at the place of the 'input <filename>' therion command.
     * 
     * This will try to interpret the given filepath in local context;
     * the input-parameter is always a filepath in a filesystem.
     * When the filename has no extension, ".th" is assumed.
     * 
     * For actually fetching the content, the currently associated
     * {@link File_Therion_Reader} implementation will be used
     * (see {@link setReader()}).
     * With the default FileReader this means that we will assume the filename
     * to be relative to the local file on a local mounted disk.
     * 
     * To limit the number of possible nested levels you may specify the
     * $lvls parameter:
     * - null: endless recursion (default)
     * -    0: no input
     * -    1: only local inputs
     * -   >1: nested levels
     * 
     * @param  int $lvls remaining levels to input
     * @throws InvalidArgumentException when input command is invalid pointer
     * @throws File_Therion_IOException in case of reading problems
     * @throws File_Therion_SyntaxException case of parsing/syntax errors
     */
    public function evalInputCMD($lvls = null) {
        // check params
        if (!is_null($lvls) && !is_int($lvls)) {
            throw new InvalidArgumentException(
                'Invalid $lvls argument type ('
                .gettype($lvls).', expected NULL or integer)');
        }
        if ($lvls < 0) {
            throw new InvalidArgumentException(
                'Invalid $lvls argument ('.$lvls.', expected >0)');
        }
        
        // check nesting limit
        if (!is_null($lvls)) {
            if ($lvls <= 0) {
                return;  // if nesting reached limit, we dont need to go further
            } else {
                $lvls--; // otherwise reduce remaining levels by one
            }
        }
        
        // scan all local lines and search for 'input' commands.
        // (the scan is performed from end-to-start to make expanding the
        //  the fetched lines convinient)
        for ($i=count($this->_lines)-1; $i>=0; $i--) {
            $curline  =& $this->_lines[$i];
            $lineData = $curline->getDatafields();
            if (isset($lineData[0]) && $lineData[0] == 'input') {
                $remotePath = $lineData[1]; // get path argument from command
                
                // Get path reference:
                // - absolute paths are used as is,
                // - relative ones are expanded from the local file directory               
                if (preg_match('%^/|^[a-z]:%i', $remotePath)) {
                    // path is absolute: use as is
                    
                } else {
                    // path is relative: expand the remote file argument 
                    // as viewed from the local filename
                    $remotePath = dirname($this->_filename).'/'.$remotePath;
                }
                
                // when file-basename has no filename extension, append ".th".
                if (!preg_match('/\.\w+$/', $remotePath)) {
                    $remotePath .= '.th';
                }
                
                
                // setup new File-object with same options that we
                // will use to conviniently fetch and collect the content.
                // (we assume the same encoding, but this may be overridden
                //  with an 'encoding' command in the loaded file)
                $tmpFile = new File_Therion($remotePath);
                $tmpFile->setEncoding($this->_encoding);
    
                // fetch datasource and eval input commands there
                // may throw File_Therion_IOException, which bubbles up.
                // we use the currently available Reader implementation for this
                if (!is_null($this->_reader)) {
                    $tmpFile->fetch($this->_reader);
                }
                
                // eval nested input commands in those lines;
                // will do nothing when the nesting reached its limit.
                // after this call, $tmpFile contains all nested lines of
                // to-be-input files, as deep as nesting was allowed by $lvls.
                $tmpFile->evalInputCMD($lvls);
                
                // Replace current input command with a commented one
                $cp = " # (resolved below)"; // some comment postfix
                $commtdOri = new File_Therion_Line(
                    "",                         // empty content (comment only)
                    $curline->getContent().$cp  // old content as comment...
                    .$curline->getComment(),    //   ... and with old comment
                    $curline->getIndent()       // preserve indenting
                );
                $this->addLine($commtdOri, $i, true);  // replace that line
                
                // add retrieved file lines to local buffer in place of $i;
                // do not add "encoding" command in subfile but reencode the
                // values there to that of the parent file.
                $subLines = array_reverse($tmpFile->getLines());
                foreach ($subLines as $subLine) {
                    if (!preg_match('/encoding/', $subLine->getContent())) {
                        // adjust indenting to that of the sourcing file
                        $newIndent = $curline->getIndent().$subLine->getIndent();
                        
                        // get data and translate to proper encoding
                        $newData = $this->encode(
                                $subLine->getContent(),
                                $this->getEncoding(),
                                $tmpFile->getEncoding()
                            );
                        
                        // get comment and translate to proper encoding
                        $newComment = $this->encode(
                                $subLine->getComment(),
                                $this->getEncoding(),
                                $tmpFile->getEncoding()
                            );
                        
                        // create a new line object with new encoded content
                        $subline = new File_Therion_Line(
                            $newData, $newComment, $newIndent);
                            
                        // add the line to the sourcing file
                        if ($i+1 <= count($this->getLines())) {
                            // add somewhere in the middle
                            $this->addLine($subLine, $i+1); // pushing content down
                        } else {
                            // add to end: this happens, when the input command
                            // was on the very last line.
                             $this->addLine($subLine, 'end');
                        }
                    }
                }

            }
        }
    }
    
    
    /**
     * Build structure of local-scope multiline commands with associated lines.
     * 
     * This will order the lines passed into a nested array sorting the
     * lines belonging together into subarrays collected under a common tag.
     * 
     * The first level contains all "seen" tags like surveys.
     * There all survey lines will be collected and ordered into their own array.
     * The special 'LOCAL' array kay holds all lines that where not enclosed
     * into their own contect and are therefore considered "local".
     * 
     * Nested structures (like they are ancountered with surveys) need to be
     * resolved with consecutive calls to this method. The structure holds all
     * the lines of the first levels, so eg. in case of nested structures there
     * will be all lines of the outermost survey command
     * 
     * The structure will look like this:
     * <code>
     * array(
     *      'LOCAL'  => array(local lines without own context)
     *      'survey' => array(
     *                      array(lines-of-survey-1),
     *                      array(lines-of-survey-2),
     *      'centreline' => array(
     *                          array(lines-of-centreline-1),
     *                          array(lines-of-centreline-2),
     *      ...
     * </code>
     * 
     * @todo: deal with nested structures: each call to extract... should deal with just one level
     * @param array $lines array of File_Therion_Line objects
     * @return array with ordered lines
     * @throws InvalidArgumentException when $lines is not strictly Line-objects
     */
    public static function extractMultilineCMD($lines)
    {
        // setup known multiline commands with start- and endtags
        $knownCTX = array(
            //ctx-name => array(starttag-regexp, endtag-regexp, curLVL),
            
            // therion data format
            'survey'     => array('/^survey/', '/^endsurvey/', 0),
            'centreline' => array('/^cent(re|er)line/', '/^endcent(re|er)line/', 0),
            'map'        => array('/^map/', '/^endmap/', 0),
            'surface'    => array('/^surface/', '/^endsurface/', 0),
            
            // therion scrap format
            'scrap'      => array('/^scrap/', '/^endscrap/', 0),
            'line'       => array('/^line/', '/^endline/', 0),
            'area'       => array('/^area/', '/^endarea/', 0),
            
            // @todo: more? (see thbook)
        );
        
        // setup return structure
        $orderedLines = array('LOCAL' => array());
        foreach ($knownCTX as $ctx_name => $ctx_cfg) {
            $orderedLines[$ctx_name] = array();
        }
        
        
        // Order lines:
        // iterate over the lines and try to sort them in
        $baseCTX = 'LOCAL';
        $curCTX  = $baseCTX;
        foreach ($lines as $line) {
            if (!is_a($line, 'File_Therion_Line')) {
                 throw new InvalidArgumentException(
                        'extractMultilineCMD() Invalid $line object type!');
            }
                 
            // investigate command type of line and determine context
            $lineData = $line->getDatafields(); //i=0 holds command type
            if ($curCTX == $baseCTX) {
                // LOCAL MODE:
                // see if new context opens, or if line belongs to LOCAL.
                
                // test command against all known context start tags
                foreach ($knownCTX as $ctx_name => $ctx_cfg) {
                    if (!$line->isCommentOnly()
                        && preg_match($ctx_cfg[0], $lineData[0])) {
                            

                        // start a new context and add line to it
                        $curCTX = $ctx_name;
                        $knownCTX[$ctx_name][2]++;  // increase lvl counter
                        $next_ctxIdx = count($orderedLines[$curCTX]);
                        $orderedLines[$curCTX][$next_ctxIdx] = array();
                        $orderedLines[$curCTX][$next_ctxIdx][] = $line;
                        continue 2; // on to the next line
                    }
                }
                
                // still no starting context found: add line to local
                $orderedLines[$baseCTX][] = $line;
                
                
            } else {
                // CONTEXT-MODE:
                // see if active context is closed, otherwise add
                // line to most recent line array of the active type
                
                // in any case, we need to add the line to cur_ctx
                $cur_ctxIdx = count($orderedLines[$curCTX]) -1;
                $orderedLines[$curCTX][$cur_ctxIdx][] = $line;
                
                // handle nesting of structures:
                // test command against this context start tag
                if (!$line->isCommentOnly()
                    && preg_match($knownCTX[$curCTX][0], $lineData[0])) {
                    $knownCTX[$curCTX][2]++;  // increase lvl counter
                    continue; // on to the next line
                }
                
                // test command against current active context end tag
                if (!$line->isCommentOnly()
                    && preg_match($knownCTX[$curCTX][1], $lineData[0])) {
                    // end current context; but only if we are at the base
                    // level (support nested structures)
                    $knownCTX[$curCTX][2]--;  // decrease lvl counter
                    
                    if ($knownCTX[$ctx_name][2] == 0) {
                        // we reached the base level: this was the last
                        // nested context level, so we are back to LOCAL
                        $curCTX = $baseCTX;
                    }
                    continue; // on to the next line
                }

            }
                
        }
        
        
        // clean up structure: remove types not seen so far from result
        foreach ($orderedLines as $type=>$lns) {
            if (count($lns) == 0) {
                unset($orderedLines[$type]);
            }
        }
        
        
        return $orderedLines;

    }
}


?>