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
require_once 'File/Therion/BasicObject.abstract.php';
require_once 'File/Therion/Line.php';
require_once 'File/Therion/Survey.php';
require_once 'File/Therion/Centreline.php';
//require_once 'File/Therion/Station.php';
require_once 'File/Therion/Shot.php';


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
 * $th->evalInputCMD();          // evaluate 'input' commands recursively
 * $th->parse();                 // Generate Therion objects to work with
 *
 * // craft a .th Therion file out of data model:
 * <code>
 * $survey = new File_Therion_Survey(); // ... craft data model
 * $th = new File_Therion($tgt); // Instanciate new data target
 * $th->addObject($survey);      // associate therion data model objects
 * $th->update();                // update internal line buffer out of objects
 * $th->write();                 // physically write to data target
 * $th->toString();              // altenatively: fetch  data as string
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
     * Be aware that this function cleans all references to associated objects.
     *
     * @throws PEAR_Exception with wrapped lower level exception (InvalidArgumentException, etc)
     * @throws File_Therion_SyntaxException if parse errors occur
     */
    public function parse()
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
                            switch (strtolower($lineData[0])) {
                                case 'encoding':
                                    $this->setEncoding($lineData[1]);
                                break;
                            }
                        }
                    }
                break;
                
                case 'survey':
                    // walk each line collection and parse it using subparser
                    foreach ($data as $ctxLines) {
                        $ctxObj = File_Therion_Survey::parse($ctxLines);
                        $this->addObject($ctxObj);
                    }
                break;
                
                default:
                    throw new PEAR_Exception("parse(): unsupported type '$type'");
            }
        } 
        
        
        // TODO: Parsing done! Investigate: can we check some errors now?
        
        return; 
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
     * @throws PEAR_Exception with wrapped lower level exception
     * @todo Honor input encoding
     */
    public function fetch()
    {
        $this->clearLines(); // clean existing line buffer as we fetch 'em fresh
        
        // read out datasource denoted by $url and call addLine()  
        $data = array(); // raw file data      
        switch (true) {
            case (is_resource($this->_url) && get_resource_type($this->_url) == 'stream'):
                // fetch data from handle
                while (!feof($handle)) {
                    $line = fgets($this->_url);
                    $data[] = $line; // push to raw dataset
                }
                break;

            case (is_array($this->_url)):
                // just use it: either stringdata or already array of Therion_Line objects
                // TODO better checks needed!
                $data = $this->_url;
                break;

            case (is_string($this->_url)):
                // check if the string is a filepath or URL
                if (preg_match('?^\w+://?', $this->_url)) {
                    // its a real URL ('http://...' or 'file://...')
                    $fh = fopen ($this->_url, 'r');
                    while (!feof($fh)) {
                        $data[] = fgets($fh); // push to raw dataset
                    }
                    fclose($fh);
                    
                } else {
                    // its not a real URL - see if there is such a file
                    if (file_exists($this->_url)) {
                        if (!is_readable($this->_url)) {
                            throw new PEAR_Exception("File '".$this->_url."' is not readable!");
                        }
                        
                        // open and read out
                        $fh = fopen ($this->_url, 'r');
                        while (!feof($fh)) {
                            $data[] = fgets($fh); // push to raw dataset
                        }
                        fclose($fh);
                        
                    } else {
                        // bail out: invalid parameter
                        throw new PEAR_Exception(
                          'fetch(): $url \''.$this->_url.'\' not readable nor a valid URL!',
                          new InvalidArgumentException("passed type='".gettype($this->_url)."'"));
                    }
                }
                break;
            

            default:
                // bail out: invalid parameter
                throw new PEAR_Exception('fetch(): unsupported $url type!',
                  new InvalidArgumentException("passed type='".gettype($this->_url)."'"));

        }
        
        
        // raw $data is now populated, lets parse it into proper line therion 
        // objects, thereby set encoding if such a command arises.
        foreach ($data as $dl) {
            // Encoding: transfer raw dataline to internal utf8 representation
            // todo implement me
            
            // parse raw line
            $line = (!is_a($dl, 'File_Therion_Line'))
                ? File_Therion_Line::parse($dl)  // parse raw data string
                : $dl;                           // use Line object as-is
            
            // handle continuations:
            // if this is the first line, pack it on the stack, otherwise see
            // if the most current line expects additional content; append to it
            // if that's the case, otherwise add as fresh line to the stack.
            if (count($this) == 0) {
                $this->addLine($line);
            } else {
                $priorLine =& $this->_lines[count($this->_lines)-1];
                if ($line->isContinuation($priorLine)) {
                    $priorLine->addPhysicalLine($line);
                } else {
                    $this->addLine($line);
                }
            }
            
            
            // If the last line on the stack is complete now, we can
            // investigate the line a little further
            $mostCurrentLine     =& $this->_lines[count($this->_lines)-1];
            $mostCurrentLineData =  $mostCurrentLine->getDatafields();
            
            
            // set encoding if specified
            if (isset($mostCurrentLineData[0])
                && strtolower($mostCurrentLineData[0]) == 'encoding') {
                $this->setEncoding($mostCurrentLineData[1]);
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
     * The optional lineNumber parameter allows to adjust the insertion
     * point; the line will be inserted at the index, pushing already
     * present content one line down (-1=end, 0=start, ...).
     * When replacing, the selected index will be replaced; here 0 will
     * be treated as 1 (replacing the first line). When there is no such
     * line, it will be added instead.
     * 
     * Instead of $lineNumber 0 and -1 you can use the strings 'start'/'end',
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
     * $th->addLine(new File_Therion_Line("final"));
     * // result: line-0="startline", line-1="somecontent", line-3="final"
     * 
     * // replace line-1:
     * $th->addLine(new File_Therion_Line("othercontent"), 1, true);
     * // result: line-0="startline", line-1="othercontent", line-3="final"
     * 
     * </code>
     * 
     * @param File_Therion_Line $line Line to add
     * @param int  $lineNumber At which logical position to add (-1=end, 0=first line, ...)
     * @param bool $replace when true, the target line will be overwritten
     * @throws PEAR_Exception with wrapped lower level exception
     */
    public function addLine($line, $lineNumber=-1, $replace=false)
    {
        if (!is_a($line, 'File_Therion_Line')) {
            throw new PEAR_Exception('addLine(): Invalid $line argument!',
                  new InvalidArgumentException("passed type='".gettype($line)."'"));
        }
        
        // synonyms+checks for lineNumber
        if (is_string($lineNumber) && strtolower($lineNumber) == "start") {
            $lineNumber = 0;
        } elseif (is_string($lineNumber) && strtolower($lineNumber) == "end") {
            $lineNumber = -1;
        } else {
            if (!is_int($lineNumber)) {
                throw new PEAR_Exception('addLine(): Invalid $lineNumber argument!',
                 new InvalidArgumentException("int expected, or string 'start' or 'end'"));
            }
        }
        
        if ($lineNumber != -1 && count($this->_lines) > 0) {
            // append/replace somewhere in the middle
            if ($lineNumber == 0) $lineNumber++; // correct index
            $insertion = ($replace)? array($line) : array($line, $this->_lines[$lineNumber-1]);
            array_splice($this->_lines, $lineNumber-1, 1, $insertion);
                    
        } else {
            // append/replace at end
            if ($replace && count($this->_lines) > 0) {
                $this->_lines[count($this->_lines)-1] = $line; // replace last entry
            } else {
                $this->_lines[] = $line; // add line to internal buffer
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
            
            $supported = "Survey"; // todo: support more types
            if (!preg_match('/^File_Therion_(?:'.$supported.')$/', $filter)) {
                throw new PEAR_Exception(
                    'getObjects(): Invalid $filter argument ('.$filter.')!',
                    new InvalidArgumentException(
                        "unsupported filter='".$filter."'"
                    )
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
     * Write this therion file content to the file.
     *  
     * This will overwrite the file denoted with {@link $_url}.
     * Wrapping will be applied according the setting of {@link setWrapping()}.
     *
     * Will throw an appropriate exception if anything goes wrong.
     * 
     * The content will be converted to the current active encoding which
     * corresponds to the encoding command of the file.
     *
     * @param  string|ressource $survey Therion_Survey object to write
     * @param  array            $options Options for the writer
     * @throws Pear_Exception   with wrapped lower level exception (InvalidArgumentException, etc)
     * @todo write to target encoding
     */
    public function write()
    {
        // go through all $_lines buffer objects and create writable string;
        $stringContent = $this->toString();
        
        // convert stringContent from internal utf8 data to tgt encoding
        // todo implement me
         
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
     * If no encoding is contained in internal line data, a suitable
     * encoding line will be added with the current active encoding setting.
     * The string however will be returned in UTF-8 regardless of any encoding
     * commands.
     * 
     * @return string The file contents as string
     * @see {@link setEncoding()}
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
        if (!is_string($url) && !is_resource($url)) {
            throw new PEAR_Exception(
                'Invalid datasource/target type supplied ('.gettype($url).')!',
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
    public function setEncoding($codeset)
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
     * at the place of the input command. With remote files the function tries
     * to guess the remote place. (see {@link enableInputCommand()}). This may
     * fail with an exception (esp. if $url was initially a filehandle).
     * 
     * This will try to interpret the given filepath in local context;
     * the input-parameter is always a filepath in a filesystem.
     * When the filename has no extension, ".th" is assumed.
     * 
     * The argument to the input command will be treaten differently depending
     * on the type of the File_Therions $url:
     *  FILE: We can treat the url as file path relative to the current path.
     *   URL: When we have fetched the current file from a web-url,
     *        then most probably the denoted filepath is also a web url.
     * OTHER: Filehandles, string/array data or handcrafted objects cannot
     *        be automatically resolved.
     * 
     * To limit the number of possible nested levels you may specify the
     * $lvls parameter (null/default: endless, 0: no input, >0: nested levels).
     * 
     * @param  int $lvls remaining levels to input
     * @throws PEAR_Exception with wrapped subexception in case of resolution error
     */
    public function evalInputCMD($lvls = null) {
        // check nesting limit
        if (!is_null($lvls)) {
            if ($lvls <= 0) {
                return;  // if nesting reached limit, we dont need to go further
            } else {
                $lvls--; // otherwise reduce remaining levels by one
            }
        }
        
        // scan all local files and search for 'input' commands
        for ($i=0; $i<count($this->_lines); $i++) {
            $curline  =& $this->_lines[$i];
            $lineData = $curline->getDatafields();
            if (isset($lineData[0]) && $lineData[0] == 'input') {
                // TODO: try to guess datasource relative to
                //   - url:    path is relative to local url
                //   - string: path is either absolute or relative to current file
                //   - other:  unable to handle -> exception
                $remotePath = $lineData[1];
                $localURL = $this->_url;
                if (is_string($localURL) && preg_match('?^\w+://?', $localURL)) {
                    // real URL: TODO
                    throw new PEAR_Exception("evalInputCMD(): unsupported feature: input type URL");
                } elseif (is_string($localURL)) {
                    // its a plain string (file path)
                    $remotePath = dirname($localURL).'/'.$remotePath;
                    
                } else {
                    // other: we cant guess it
                    throw new PEAR_Exception(
                        'evalInputCMD() Invalid $url type!',
                        new InvalidArgumentException());
                }
                
                // when $url basename has no filename extension, append ".th".
                if (!preg_match('/\.\w+$/', $remotePath)) {
                    $remotePath .= '.th';
                }
                
                
                // setup new File-object with same options
                $tmpFile = new File_Therion($remotePath);
                $tmpFile->setEncoding($this->_encoding);
                
                // fetch datasource and eval input commands there
                $tmpFile->fetch();
                
                // eval nested input commands in those lines;
                // will do nothing when the nesting reached its limit
                $tmpFile->evalInputCMD($lvls);
                
                // add retrieved file lines to local buffer in place of $i
                // (this has to replace the orginating input command,
                //  which gets replaced with a commented out original)
                $commtdOri = new File_Therion_Line("", // empty content
                    $curline->getContent(), // old content as comment
                    $curline->getIndent()); // preserve indenting
                $this->_lines[$i] = $commtdOri;
                $subLines = array_reverse($tmpFile->getLines());
                foreach ($subLines as $subLine) {
                    $this->addLine($subLine, $i+1); // pushing content down
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
                 throw new PEAR_Exception(
                        'extractMultilineCMD() Invalid $line type!',
                        new InvalidArgumentException());
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
