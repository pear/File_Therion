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
 * Package includes.
 */
require_once 'PEAR.php';
require_once 'PEAR/Exception.php';
require_once 'File/Therion/Exception.php';
require_once 'File/Therion/Line.php';
//require_once 'File/Therion/Survey.php';
//require_once 'File/Therion/Centreline.php';
//require_once 'File/Therion/Person.php';
//require_once 'File/Therion/Explo.php';
//require_once 'File/Therion/Topo.php';
//require_once 'File/Therion/Station.php';
//require_once 'File/Therion/StationFlag.php';
//require_once 'File/Therion/Shot.php';
//require_once 'File/Therion/ShotFlag.php';

/**
 * Class representing a therion file
 * 
 * Also serves functions to parse and write .th data to/from File_Therion objects.
 *
 * Therion (http://therion.speleo.sk/) is an openSource application for managing
 * cave survey data.
 *
 * The data structure follows mostly the SQL diagram in the therion book (see
 * Chapter 'SQL export'), in short:
 *   - A .th-file contains surveys.
 *   - Surveys can be nested.
 *   - Surveys contain metadata (people etc) and one ore more centreline(s).
 *   - A Centreline contain shots and stations.
 *   - Shots and stations can contain flags.
 * 
 * The Data format is specified in the therion book, but basicly a file contains
 * human readable text lines following a specific syntax and describing the
 * therion objects. A logical line may be "wrapped"; that is, its content can be
 * spread out over several physical lines.
 * 
 * There are two basic workflows:
 * <code>
 * // read and parse to objects:
 * $th = new File_Therion($src); // Instanciate new datasource
 * $th->fetch();                 // Get contents (read)
 * $th->parse();                 // Generate Therion objects to work with
 *
 * // craft therion file out of data model:
 * $survey = new File_Therion_Survey(); // ... craft data model
 * $th = new File_Therion($tgt); // Instanciate new data target
 * $th->addObject($survey);      // associate therion data model objects
 * $th->update();                // update internal line buffer out of objects
 * $th->write();                 // physically write to data target
 * </code>
 *
 * @category   file
 * @package    File_Therion
 * @author     Benedikt Hallinger <beni@php.net>
 * @copyright  2016 Benedikt Hallinger
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @link       http://pear.php.net/package/File_Therion/
 */
class File_Therion implements Countable
{

    /**
     * Datasource/target of this file
     * 
     * This represents the real physical adress of the content
     * 
     * @access protected
     */
     protected $_url = '';

    /**
     * Lines of this file
     * 
     * will be populated by {@link parse()} or {@link update()}
     * 
     * @access protected
     * @var array of data (File_Therion_Line objects)
     */
    protected $_lines = array();
    
    /**
     * objects of this file
     * 
     * will be populated by {@link parse()} or {@link update()}
     * 
     * @access protected
     * @var array of data (File_Therio_* objects)
     */
    protected $_objects = array();
    
    
    /**
     * Create a new therion file object
     * 
     * Use this to create a new interface for parsing existing files
     * or writing new ones.
     * 
     * The $url is a pointer to a datasource (or target).
     * Use {@link parse() if you want to {@link fetch ()} the source contents or
     * {@link write()} to write the current content to the target.
     * 
     * @todo: example documentation
     *
     * @param string $url path or URL of the file
     * @throws PEAR_Exception with wrapped lower level exception
     */
    public function __construct($url)
    {
           $this->setURL($url);
    }


    /**
     * Parses the internal line buffer to associated Therion objects
     * 
     * You may use {@link fetch()} to update the internal line buffer with
     * real physical content from the datasource.
     * Alternatively you can craft the file yourself using {@link addLine()}.
     * 
     * Be aware that this function cleans all references to associated objects.
     *
     * @throws PEAR_Exception         with wrapped lower level exception (InvalidArgumentException, etc)
     * @throws File_Therion_SyntaxException if parse errors occur
     */
    public function parse()
    {
        $this->clearObjects();  // clean references
        
        // TODO implement me: call addLine()

        // OK now we got $data populated with string lines
        // lets iterate over it and try to parse.
        // the ultimate goal is to create an instance of File_Therion_Survey
        $survey = null;
        foreach ($data as $sline) {
            // parse if not already a Therion_Line
            $thline = (is_a($sline, 'File_Therion_Line'))
                ? $sline
                : File_Therion_Line::parse($sline);

            
        }


        return $survey;
    }
    
    
    /**
     * Update the internal line representation of this file from datsource
     * 
     * This will open the connection to the $url and read out its contents;
     * parsing it into File_Therion_Line objects (and thereby validating syntax)
     * 
     * Be aware that this function clears the internal line buffer, so any
     * changes made by {@add addLine()} get discarded.
     * 
     * After fetching physical content, you may call {@link parse()} to generate
     * Therion data model objects out of it.
     * 
     * @todo implement me
     */
     public function fetch()
     {
        $this->clearLines(); // clean existing line buffer as we fetch 'em fresh
        
        // read out datasource denoted by $url and call addLine()
        //   .... tbi ...
        
        $data = array();
        // investigate file parameter and try to get data out of the source.
        $file = $this->_url;
        switch (true) {
            case (is_resource($file) && get_resource_type($file) == 'stream'):
                // fetch data from handle
                while (!feof($handle)) {
                    $line = fgets($file);
                    $data[] = $line; // push to raw dataset
                }
                break;

            case (is_array($file)):
                // just use it: either stringdata or already array of Therion_Line objects
                $data = $file;
                break;

            case (is_readable($file) || is_string($file) && preg_match('^\w+://', $file)):
                // open file/url and fetch data, then repass to factory
                $fh = fopen ($file, 'r');
                $survey = File_Therion::parse($fh);  // TODO
                fclose($fh);
                return $survey;
                break;

            case (is_string($file)):
                // split string data by newlines and use that as result
                $data = explode(PHP_EOL, $file);
                break;

            default:
                // bail out: invalid parameter
                throw new PEAR_Exception('parseSurvey(): Invalid $file argument!', new InvalidArgumentException("passed type='".gettype($file)."'"));
                            
        }
        
     }
    
    /**
     * Update the line contents of this file from contained objects
     * 
     * This will generate therion file lines out of the associated objects.
     * 
     * @todo implement me
     */
     public function update()
     {
        $this->_lines = array(); // clean existing line content
        
         // walk trough the associated objects and ask them to generate lines;
         // populate
     }
     
     
     /**
     * Add a line to this file
     * 
     * The optional lineNumber parameter allows to adjust the insertion point;
     * the lie will be inserted at the index, pushing already present content
     * one line down (-1=end, 0=start, ...).
     * 
     * Beware that {@link clearLines()} will discard any manual insertions.
     * Also be aware that {@link fetch()} will clean the line buffer too.
     * 
     * @param $line       File_Therion_Line Line to add
     * @param $lineNumber At which logical position to add (-1=end)
     * @todo implement me
     */
     public function addLine($line, $lineNumber=-1)
     {
     }
     
    /**
     * Get internal line buffer
     *
     * @return array of File_Therion_Line objects
     */
     public function getLines()
     {
         return $this->_lines;
     }
     
     
     /**
     * Clear associated lines
     * 
     * This will wipe out the internal line buffer.
     */
     public function clearLines()
     {
         $this->_lines = array();
     }
     
     /**
     * Clear associated objects
     * 
     * This will unassociate all registered objects.
     * You probably want to call {@link update()} hereafter to also clean the
     * calculated line content.
     */
     public function clearObjects()
     {
         $this->_objects = array();
     }
     
     /**
     * Add an object to this file
     * 
     * Associated objects can be written to a file after {@link update()}
     * has been called to update the internal line representation.
     * 
     * Be aware that {@link clearObjects()} will discard any manual changes made
     * so far, and be warned that {@link parse()} will clean them too.
     * 
     * @param $thObj      File_Therion_* object to add
     * @todo implement me
     */
     public function addObject($thObj)
     {
         $this->_objects[] = $thObj;
     }
     
     /**
     * Get associated objects
     *
     * @return array of File_Therion_* objects
     */
     public function getObjects()
     {
         return $this->_objects;
     }
     
     /**
     * Write this therion file content to the file
     *  
     * This will overwrite the file denoted with $_url.
     * 
     * The files will be generated in the following way:
     *   - each survey goes into its own file
     *   - each file is named after its survey name
     *   - if the survey name contains lashes, folders will be created.
     *
     * OPTIONS is an associative array to control output and may contain:
     *   'filter' => regexp   
     *       filter by survey name, only write surveys matching the filter.
     *   'depth'  => number
     *       only export to the nth level (0=all, 1=first level, ...)
     *
     * Will throw an appropriate exception if anything goes wrong.
     *
     * @param  string|ressource    $survey Therion_Survey object to write
     * @param  array               $options Options for the writer
     * @throws Pear_Exception      with wrapped lower level exception (InvalidArgumentException, etc)
     */
     public function write()
     {
         //@todo implement me...
         
         // go through all $_lines buffer objects and create writable string;
         //$arrayOfLines = $this->getLines();
         
         // then dump that string to the datasource.
     }
     
    /**
    * Update datasource/target path
    * 
    * This will just change the path, no data will be read/written!
    * 
    * @param $url string|ressource  filename/url or handle
    */
    public function setURL($url)
    {
        if (!is_string($url) || !is_resource($url)) {
            throw new PEAR_Exception(
                'Invalid datasource/target type supplied ('.get_type($url).')!',
                new InvalidArgumentException()
            );
        }
        $this->_url = $url;
    }
      
    /**
     * Get currently set datasource/target location
     * 
     * @return string|ressource  filename/url or handle
     */
     public function getURL()
     {
         return $this->_url;
     }
     
     
     
    /**
     * Count (wrapped) lines in this file (SPL Countable)
     * 
     * returns the count of physical (ie. wrapped) lines in this file.
     * To count logical lines (ie. unwrapped, or line objects), use $logical.
     *
     * @param $logical boolean If true, return logical (unwrapped) count
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
    

}


?>
