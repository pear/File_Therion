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
 * // craft a .th Therion file out of data model:
 * <code>
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
     * Datasource/target of this file.
     * 
     * This represents the real physical adress of the content
     * 
     * @access protected
     */
    protected $_url = '';
    
    /**
     * Encoding of this file.
     * 
     * @var string
     */
    protected $_encoding = 'UTF-8';

    /**
     * Lines of this file.
         * 
     * will be populated by {@link parse()} or {@link update()}
     * 
     * @access protected
     * @var array of data (File_Therion_Line objects)
     */
    protected $_lines = array();
    
    /**
     * objects of this file.
     * 
     * will be populated by {@link parse()} or {@link update()}
     * 
     * @access protected
     * @var array of data (File_Therio_* objects)
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
     * Allows/Disables parsing of 'input' command.
     *
     * @see {@link parse()}
     * @see {@link enableInputCommand()}
     * @var boolean
     */
    protected $_allowImport = true;
    
    
    
    /**
     * Create a new therion file object.
     *
     * Use this to create a new interface for parsing existing files
     * or writing new ones.
     * 
     * The $url is a pointer to a datasource (or target).
     * Use {@link parse()} if you want to {@link fetch()} the source contents or
     * {@link write()} to write the current content to the target.
     * 
     * Example:
     * <code>
     * $thFile = new File_Therion('foobar.th'); // local file (r/w access)
     * $thFile = new File_Therion('http://example.com/foo.th'); // web (r/o)
     * $thFile->fetch();  // get contents
     * $thFile->parse();  // parse fetched contents
     * $surveys = $thFile->getObjects('File_Therion_Survey'); // get surveys
     * $all = $thFile->getObjects(); // get all parsed objects
     * </code>
     *
     * @param string $url path or URL of the file
     * @throws PEAR_Exception with wrapped lower level exception
     */
    public function __construct($url)
    {
           $this->setURL($url);
    }


    /**
     * Parses the internal line buffer to associated Therion objects.
     * 
     * You may use {@link fetch()} to update the internal line buffer with
     * real physical content from the datasource.
     * Alternatively you can craft the file yourself using {@link addLine()}.
     * 
     * Therions 'import' statement will include the remote file content
     * at the place of the import command. With remote files the function tries
     * to guess the remote place. (see {@link enableInputCommand()}). This may
     * fail with an exception (esp. if $url was initially a filehandle).
     * 
     * Be aware that this function cleans all references to associated objects.
     *
     * @todo implement "import" command of therion to include other files
     * @throws PEAR_Exception with wrapped lower level exception (InvalidArgumentException, etc)
     * @throws File_Therion_SyntaxException if parse errors occur
     * @throws TODO_FILE_NOT_THERE_Exception in case 'input' failed.
     * @see {@link enableInputCommand()}
     */
    public function parse()
    {
        $this->checkSyntax();
        
        $this->clearObjects();  // clean references
        
        // walk all lines and try to parse them in this context.
        // we delegate as much as possible






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

        // syntax check; if there is still 
        // TODO
    }
    
    
    /**
     * Update the internal line representation of this file from datsource.
     * 
     * This will open the connection to the $url and read out its contents;
     * parsing it to File_Therion_Line objects (and thereby validating syntax).
     * 
     * Be aware that this function clears the internal line buffer, so any
     * changes made by {@link addLine()} get discarded.
     * 
     * After fetching physical content, you may call {@link parse()} to generate
     * Therion data model objects out of it.
     * 
     */
    public function fetch()
    {
        $this->clearLines(); // clean existing line buffer as we fetch 'em fresh
        
        // read out datasource denoted by $url and call addLine()  
        $data = array(); // raw file data      
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
                // open file/url and fetch data
                $fh = fopen ($file, 'r');
                while (!feof($handle)) {
                    $line = fgets($file);
                    $data[] = $line; // push to raw dataset
                }
                fclose($fh);
                return;
                break;

            case (is_string($file)):
                // split string data by newlines and use that as result
                $data = explode(PHP_EOL, $file);
                break;

            default:
                // bail out: invalid parameter
                throw new PEAR_Exception('parseSurvey(): Invalid $file argument!',
                  new InvalidArgumentException("passed type='".gettype($file)."'"));

        }
        
        
        
        // raw $data is now populated, lets parse it into proper line therion 
        // objects, thereby set encoding if such a command arises.
        foreach ($data as $dl) {
            // parse raw line
            $line = (!is_a('File_Therion_Line', $dl))
                ? File_Therion_Line::parse($dl)  // parse raw data string
                : $dl;                           // use Line object as-is
            
            // handle continuations:
            // if this is the first line, pack it on the stack, otherwise see
            // if the most current line expects additional content; append to it
            // if that's the case, otherwise add as fresh line to the stack.
            if (count($this) == 0) {
                $this->addLine($line);
            } else {
                $priorLine =& $this->_lines[count($this->_lines-1)];
                if ($line->isContinuation($priorLine)) {
                    $priorLine->addPhysicalLine($line);
                } else {
                    $this->addLine($line);
                }
            }
            
            
            // If the last line on the stack is complete now, we can
            // investigate the line a little further
            $mostCurrentLine =& $this->_lines[count($this->_lines-1)];
            $mostCurrentLineData = $mostCurrentLine->getDatafields();
            
            // set encoding if specified
            if ($mostCurrentLineData[0] == 'encoding') {
                $this->setEncodign($mostCurrentLineData[1]);
            }
            
            
        }
        
    }
    
    /**
     * Update the line contents of this file from contained objects.
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
     * Add a line to this file.
     * 
     * The optional lineNumber parameter allows to adjust the insertion point;
     * the lie will be inserted at the index, pushing already present content
     * one line down (-1=end, 0=start, ...).
     * 
     * Beware that {@link clearLines()} will discard any manual insertions.
     * Also be aware that {@link fetch()} will clean the line buffer too.
     * 
     * Note that addLine() will not take care of wrapping; make sure
     * that the line content remains consistent.
     * 
     * @param File_Therion_Line $line Line to add
     * @param int $lineNumber At which logical position to add (-1=end)
     * @todo implement me
     */
    public function addLine($line, $lineNumber=-1)
    {
        if (!is_a($line, 'File_Therion_Line')) {
            throw new PEAR_Exception('addLine(): Invalid $line argument!',
                  new InvalidArgumentException("passed type='".gettype($line)."'"));
        }
        
        if ($lineNumber != -1) throw new PEAR_Exception('INSERTION FEATURE NOT IMPLEMENTED');
        
        $this->_lines[] = $line; // add line to internal buffer
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
     * You probably want to call {@link update()} hereafter to also clean the
     * calculated line content.
     */
    public function clearObjects()
    {
         $this->_objects = array();
    }
     
     /**
     * Add an File_Therion data model object to this file
     * 
     * Associated objects can be written to a file after {@link update()}
     * has been called to update the internal line representation.
     * 
     * Be aware that {@link clearObjects()} will discard any manual changes made
     * so far, and be warned that {@link parse()} will clean them too.
     * 
     * @param object $thObj File_Therion_* object to add
     * @todo implement me better: checks etc
     */
    public function addObject($thObj)
    {
         $this->_objects[] = $thObj;
    }
     
     /**
     * Get all associated objects.
     * 
     * You can optionaly query for specific types using $filter.
     * 
     * Example:
     * <code>
     * $allObjects = $thFile->getObjects(); // get all
     * $surveys    = $thFile->getObjects('File_Therion_Survey'); // get surveys
     * </code>
     *
     * @param string $filter File_Therion_* class name, retrieve only objects of that kind
     * @return array of File_Therion_* objects (empty array if no such objects)
     */
    public function getObjects($filter = null)
    {
         if (is_null($filter)) {
            return $this->_objects;
        } else {
            $supported = "Survey"; // todo: support more types
            if (!preg_match('^File_Therion_(?:'.$supported.')$', $filter)) {
                throw new PEAR_Exception('getObjects(): Invalid $filter argument!',
                 new InvalidArgumentException("unsupported filter='".$filter."'"));
            }
            
            $rv = array();
            foreach ($this->_objects as $o) {
                if (get_class($o) == $filter) {
                    $ret[] = $o;
                }
            }
            return $ret;
        }
    }
     
     /**
     * Write this therion file content to the file.
     *  
     * This will overwrite the file denoted with {@link $_url}.
     * Wrapping will be applied according the setting of {@link setWrapping()}.
     *
     * Will throw an appropriate exception if anything goes wrong.
     *
     * @param  string|ressource $survey Therion_Survey object to write
     * @param  array            $options Options for the writer
     * @throws Pear_Exception   with wrapped lower level exception (InvalidArgumentException, etc)
     */
    public function write()
    {
        // go through all $_lines buffer objects and create writable string;
        $stringContent = $this->toString();
         
        // open filehandle in case its not already open
        if (!is_resource($this->_url)) {
            $fh = fopen ($this->_url, 'w');
        } else {
            $fh = $this->_url;
        }
        if (!is_writable($fh)) {
           throw new Pear_Exception("'".$this->_url."' is not writable!");
        }
         
        // then dump that string to the datasource:
        if (!fwrite($fh, $stringContent)) {
            throw new Pear_Exception("error writing to '".$this->_url."' !");
        }
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
     * @return string The file contents as string
     * @todo Line endings should not depend on Line class implementation
     */
    public function toString()
    {
        // Iterate over file objects composing a string
        $ret = "";
        foreach ($this->_lines as $line) {
            if ($this->_wrapAt > 0) {
                // todo: honor wrapping request by user
                throw new PEAR_Exception('WRAPPING FEATURE NOT IMPLEMENTED');
            }
            $ret .= $line->toString();
        }
        return $ret;
    }
     
    /**
     * Update datasource/target path.
     * 
     * This will just change the path, no data will be read/written!
     * 
     * @param string|ressource $url filename/url or handle
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
     * Get currently set datasource/target location.
     * 
     * @return string|ressource  filename/url or handle
     */
    public function getURL()
    {
         return $this->_url;
    }
     
     
    /**
     * Set wrapping column when writing.
     * 
     * The wrapping will be carried out using therions data format
     * (eg. ending the line with backslash and continuing it on the next one).
     * 
     * @param int $wrapAt wrap at this column, 0=disable
     */
    public function setWrapping($wrapAt)
    {
        if (!is_int($wrapAt)) {
            throw new PEAR_Exception('setWrapping(): Invalid $wrapAt argument!',
                  new InvalidArgumentException("passed type='".gettype($wrapAt)."'"));
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
     * Allow/Disable parsing of 'input' command.
     * 
     * Therion files may contain an 'input <filepath>' command.
     * This tells therion to include the referenced file at the place of the
     * command.
     * {@link parse()} by default performs this lookup, but you may turn this
     * behavior off.
     * 
     * @param boolean $allow
     */
    public function enableInputCommand($allow)
    {
        $this->_allowImport = $allow;
    }
    
    /**
     * Set encoding of input/output files.
     * 
     * This will tell what encoding to use.
     * The default assumed encoding is utf8.
     * 
     * When {@link fetch()}ing a file, there is usually a 'encoding' therion
     * command telling the encoding of the following code, so when reading in
     * file data there is usually no need to call this explicitely.
     * 
     * @param string $codeset
     * @todo currently not supported - does nothing
     */
    public function setEncodign($codeset)
    {
        $this->_encoding = $codeset;
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
        
        if ($lines[count($lines-1)]->isContinued()) {
            throw new File_Therion_SyntaxException(
                "Data incomplete: last line still expects another physical line!"
                );
        }
        
        // TODO search matching multiline for survey / scrap / centreline
    }
}


?>
