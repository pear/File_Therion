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
 * A single logical line of a Therion file.
 *
 * This class implements the basic file syntax and represent a single
 * logical line of such a file.
 * Therion files may be wrapped (a logically signle line can be wrapped into
 * several physical lines); this class helps to deal with that.
 * 
 * The exact format is specified in the therion documentation,
 * please refer to {@link http://therion.speleo.sk/downloads/thbook.pdf}, page 12).
 *
 * @category   file
 * @package    File_Therion
 * @author     Benedikt Hallinger <beni@php.net>
 * @copyright  2016 Benedikt Hallinger
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @link       http://pear.php.net/package/File_Therion/
 */
class File_Therion_Line implements Countable
{

    /**
     * Line content.
     * 
     * The internal data structure of the line is an array containing
     * at least one associative array:
     *   'intend'  =>  detected intend characters
     *   'data'    =>  payload of the line
     *   'comment' =>  comment of the line
     *   'wrapped' =>  true, if the line expects additional content in the next physical line
     *
     * @access protected
     * @var array of data
     */
    protected $_content = array();
    
    /**
     * Standard output separator for comment.
     * 
     * @var string
     */
    protected $_out_std_commentSep = "\t";
    
    /**
     * Default line ending character
     */
     protected $_eol = PHP_EOL;
    
    
    /**
     * Create a new therion line object.
     * 
     * Data may be given in two forms:
     *   - string: line data will be used as given (like from file)
     *   - array:  interpreted as array returned by {@link getDatafields()}
     *
     * Be sure to escape values in string mode properly.
     * 
     * @param string|array $data    data of the line
     * @param string $comment comment of the line
     * @param string $indent  intent characters
     * @throws InvalidArgumentException
     */
    public function __construct($data, $comment = '', $indent = '')
    {
        if (is_string($data) && is_string($comment) && is_string($indent)) {      
            $this->addData($indent, $data, $comment);
            
        } elseif (is_array($data) && is_string($comment) && is_string($indent)) { 
            // escape values and concatenate them
            $dataEscaped = File_Therion_Line::escape($data);
            $stringData = implode(' ', $dataEscaped);
           // var_dump(array('DBG:escp'=>$dataEscaped,'DBG:strd'=>$stringData));
            
            $this->addData($indent, $stringData, $comment);
          
        } else {               
            throw new InvalidArgumentException(
                'cannot construct new Line: Invalid $data type!');
        }    
    }
    
    /**
     * Parse a physical Therion line into its contents.
     * 
     * @param string  $line physical line
     * @return File_Therion_Line object
     * @throws File_Therion_SyntaxException in case of syntax error
     */
    public static function parse($line)
    {
        $line = trim($line, "\r\n");  // strip cross-platform newline symbols
        
        $matches = array();
        if (! preg_match("/^(\s*)(.*?)((?:#.*)?)$/", $line, $matches) ) {
            throw new File_Therion_SyntaxException("line syntax error: '$line'");
        } else {
            // strip comment sign from comment data:
            $matches[3] = preg_replace("/^#/", "", $matches[3], 1);
            //print "DBG: IN='$line' => File_Therion_Line('$matches[2]', '$matches[3]', '$matches[1]');\n";
            return new File_Therion_Line($matches[2], $matches[3], $matches[1]);
        }
    }
    
    /**
     * Add a physical Therion line as wrapped into its contents.
     * 
     * @param string|File_Therion_Line  $line physical line or Line object
     * @throws File_Therion_SyntaxException in case the Line did not expect additional wrapped content
     * @throws InvalidArgumentException
     */
    public function addPhysicalLine($line)
    {
        $this->expectMoreLines();
        if (is_string($line)) {
            $this->addPhysicalLine(File_Therion_Line::parse($line));
            
        } else {
            if (!is_a($line, 'File_Therion_Line')) {
                throw new InvalidArgumentException(
                    'addPhysicalLine(): Invalid $line type!');
            }
            $this->addData($line->getIndent(), $line->getContent(), $line->getComment());
            if ($line->isContinued()) $this->expectMoreLines();
        }
    }
    
    /**
     * Add internal data to stack.
     * 
     * @param string  $data    data string of the line
     * @param string  $comment comment of the line
     * @param string  $indent  intent characters
     * @throws File_Therion_SyntaxException in case the Line did not expect additional wrapped content
     * @throws InvalidArgumentException
     */
    protected function addData($indent, $data, $comment)
    {
        if (!is_string($indent)) {
            throw new InvalidArgumentException(
                "Invalid $indent argument! passed type='".gettype($file)."'");
        }
        if (!is_string($data)) {
            throw new InvalidArgumentException(
                "Invalid $data argument! passed type='".gettype($file)."'");
        }
        if (!is_string($comment)) {
            throw new InvalidArgumentException(
                "Invalid $comment argument! passed type='".gettype($file)."'");
        }
                
        // Detect if this line expects further lines...
        /* thbook says on p.12: "each line ending with a backslash (\) is
        *  considered to continue on the next line, as if there was neither
        *  line-break nor backlash."      
        */
        $wrapDetected = false;
        $dataContinued    = preg_match('/\\\$/', $data);
        $commentContinued = preg_match('/\\\$/', $comment);
        if ($dataContinued || $commentContinued) {
            // strip continuation character from data blocks:
            $data    = preg_replace('/\\\$/', '', $data);
            $comment = preg_replace('/\\\$/', '', $comment);
            
            $wrapDetected = true;
        }

        // see, if $data has trailing whitespace, followed by comment;
        // if so, set the comment separator to that characters:
        $matches = array();
        if ($comment != "" && preg_match('/^(.*?)( +)$/', $data, $matches)) {
            $data   = $matches[1];
            $newSep = $matches[2];
            $this->setCommentSeparator($newSep);
        }
        
        if (count($this->_content) == 0 || $this->isContinued()) {
        
            // push the line on internal stack
            $this->_content[] = array(
                'indent'  =>  $indent,
                'data'    =>  trim($data,    "\r\n"),
                'comment' =>  trim($comment, "\r\n"),
            );
            
            $this->getDatafields(); // syntax check of lines, will throw exception
            
            ($wrapDetected)? $this->expectMoreLines() : $this->expectMoreLines(false);
            
        } else {
            throw new File_Therion_SyntaxException('Unexpected continuation added to unwrapped file');
        }
    }
    
    /**
     * Let the Line know that it should expect an additional wrapped line.
     * 
     * Do not confuse this with {@link isContinued()}!
     * 
     * @param boolean $yesno set to false to explicitely revert
     */
    public function expectMoreLines($yesno = true)
    {
        // reset all fields:
        // only update the last field
        $c = count($this->_content);
        for ($i=0; $i<$c; $i++) {
            $y = ($c > 1)? true : false;    // all elements...
            $y = ($i+1 == $c)? $yesno : $y; // ... but the last one
            
            $this->_content[$i]['wrapped'] = $y;
        }
    }
    
    
    /**
     * Returns the unwrapped data-content as string.
     * 
     * Comments and indenting will be stripped
     * 
     * @return string data part of the unwrapped line
     */
    public function getContent()
    {
        $unwrappedLine = "";
        foreach ($this->_content as $cl) {
            $unwrappedLine .= $cl['data'];
        }
        return $unwrappedLine;
    }
    
    /**
     * Returns the data part of the line as array.
     * 
     * Each distinct value will form one array index.
     * With single line therion commands the command will be naturally at i=0,
     * whereas with multiline commands there will usually just be data.
     * 
     * Unescaping will be performed, so each array entry has only valid data
     * suitable for proper processing.
     * 
     * @see {@link escape()} for escaping rules as per therion book
     * @return array
     * @throws File_Therion_SyntaxException in case of quoting errors of this Line
     * @todo support corner cases such as ""foo"" (which is valid='"foo"')
     */
    public function getDatafields()
    {
        $c = $this->getContent();
        
        // this pattern tries to grep all possible non-quoted and quoted
        // string tokens.
        // this pattern is still insufficient as it will not get ""foo"" etc,
        // however it should already grep most of the possible combinations.
        $p ='((?:\[[\s\w\d.\-]+\])|(?:"(?:""|[\s\w.:-_\-])+")|(?:[\d\w.:\-_]+)|(?:"")|(?:\[\]))';
        $r = array();
        $pr = preg_match_all($p, $c, $r);
        if ($pr === false) throw new File_Therion_SyntaxException(
            "error parsing datafields (could not grep tokens)");
 
        // apply unescaping to all tokens
        if (isset($r[0]) && is_array($r[0])) {
            $rv = array_map('File_Therion_Line::unescape', $r[0]);
            
        } else {
            // empty result at getting tokens or something like that
            $rv = array();
        }
    
        return $rv;
    }
    
    /**
     * Extract options given to a command in this line.
     * 
     * Returns associative array of set options with key=option.
     * The value is an array containing the values whose items can have the
     * following forms:
     * - empty string with no arguments
     * - string when one argument was given
     * - array with strings with multiple arguments
     * 
     * When an option is given more than once, value(s) will be appended to
     * the already existing key like described above.
     * 
     * Example:
     * <code>
     * //Line = 'survey foo -title "bar foo passage"'
     * $options = $line->extractOptions();
     * print $options['title']; // -> array("bar foo passage")
     * 
     * //Line = 'scrap bar -author 2016 "Some One"'
     * $options = $line->extractOptions();
     * print $options['author']; // -> array(array("2016", "Some One"))
     * 
     * //Line = 'scrap bar -author 2016 "Some One" -author "Someone Else"'
     * $options = $line->extractOptions();
     * print $options['author']; // -> array(array("2016", "Some One"), "Someone Else")
     * </code>
     * 
     * @return array
     * @throws File_Therion_SyntaxException in case of missing option arguments
     */
    public function extractOptions()
    {
        $r = array();
        $data = $this->getDatafields();
        for ($i=0; $i<count($data); $i++) {
            $m = array();
            if (preg_match('/^-(.+)/', $data[$i], $m)) {
                // this data field is an option:
                // see if there are more arguments following
                $opt  = $m[1]; // option name
                $args = array();
                for ($y=$i+1; $y<count($data); $y++) {
                    // add following fields as argument until
                    // end or new option starts
                    if (!preg_match('/^-(.+)/', $data[$y])) {
                        $args[] = $data[$y]; // add as arg
                    } else {
                        $i = $y-1; // adjust $i to reevaluate next option
                        break; // end investigation/adding
                    }
                }
                
                if (count($args) == 0) {
                    // according to thbook, any option needs at least
                    // one argument ("-<option> <arg1>")!
                    throw new File_Therion_SyntaxException(
                        "Missing argument for option '$opt'!"
                    );
                }
                
                // format $args:
                // - one item:   fetch content
                // - more items: leave in array, so they remain linked
                if (count($args) == 1) {
                    $args = array_shift($args);
                }
                
                // add $args to option key
                if (array_key_exists($opt, $r)) {
                    // some other value is already present: add this args
                    $r[$opt][] = $args;
                } else {
                    // create fresh key
                    $r[$opt] = array($args);
                }

            }
        }
        
        return $r;
    }
    
    
    /**
     * Escapes one or more datafields for proper therion syntax.
     * 
     * This will escape and quote datafields properly, so the can be put into
     * a file for writing.
     * 
     * Escaping and quoting rules are described in the therion book at p. 12.
     * 
     * When passed an array (like from {@link getDatafields()}, an array
     * will be returned with each value escaped separately.
     * 
     * @param string|array string or array to escape
     * @return string|array with escaped/quoted values
     * @todo []-escaping is also used for keywords with spaces; the are not yet supported
     */
    public static function escape($value)
    {
        if (is_array($value)) {
            $r = array();
            foreach ($value as $v) {
                $r[] = File_Therion_Line::escape($v);
            }
            return $r;
        } else {
            // Escape double-quotes: duplicate them
            $value = preg_replace('/"/', '""', $value);
            
            if (stristr($value, ' ') || $value == "") {
                // if space is contained, quote entire field
                // TODO: support keywords as well
                if (preg_match('/^\d.+\s+/', $value)) {
                    $value = '['.$value.']'; // put numeric value or keyword in []
                } else {
                    $value = '"'.$value.'"'; // put string value in ""
                }
            }
            
            return $value;
        }
    }
    
    /**
     * Unescapes one or more datafields for proper processing in PHP.
     * 
     * This will undo the escaping and quoting performed by {@link escape()}.
     * 
     * @param string|array string or array to unescape
     * @return string|array with unescaped/unquoted values
     */
    public static function unescape($value)
    {
        if (is_array($value)) {
            $r = array();
            foreach ($value as $v) {
                $r[] = File_Therion_Line::unescape($v);
            }
            return $r;
        } else {
            
            // remove quote sign from start and end of string
            $value = preg_replace('/^["\[]|["\]]$/', '', $value);
            
            // Escaped double-quotes: strip one of them
            $value = preg_replace('/""/', '"', $value);
            
            return $value;
        }
    }
    
    /**
     * Returns the unwrapped comment as string.
     * 
     * Comments of wrapped lines will be joined.
     * 
     * @param string $ml_sep Separator used for joining multiline
     * @return string comment part of the unwrapped line
     */
    public function getComment($ml_sep = '; ')
    {
        $ar = array();
        foreach ($this->_content as $cl) {
            if (strlen($cl['comment']) > 0) {
                // ignore empty comments
                $ar[] = $cl['comment'];
            }
        }
        return implode($ml_sep, $ar);
    }
    
    /**
     * Returns the indenting characters.
     * 
     * In case this line contains wrapped content, the indenting of
     * the first physical line will be used.
     * 
     * @return string
     */
    public function getIndent()
    {
        return $this->_content[0]['indent'];
    }
    
    /**
     * Returns the whole line as String.
     * 
     * Comments will be added using a single tabulator by default.
     * Line ending is the current value of PHP_EOL (NEWLINE on *NIX).
     * 
     * @return string
     */
    public function toString()
    {
        // adjust comment separator "#<comment>" when comment is present.
        $commentSep = "";
        if (strlen($this->getComment()) > 0) {
            $commentSep = (strlen($this->getContent()) > 0)? $this->_out_std_commentSep.'#' : '#';
        }
        //print("DBG: getIndent='".$this->getIndent()."'; getContent='".$this->getContent()."'; SEP='".$commentSep."'; getComment='".$this->getComment()."'\n");
        return $this->getIndent().$this->getContent().$commentSep.$this->getComment().$this->_eol;
    }
 
    /**
     * Say if this is just a comment or empty line.
     * 
     * @return boolean
     */
    public function isCommentOnly()
    {
        // Its the case if the datapart is empty
        // (it does not matter if there is indenting or an additional comment)
        return (preg_match('/^\s*$/', $this->getContent()))
            ? true : false; // preg match returns int, not bool
    }
    
    /**
     * Detect if this Line expects wrapped content in the following physical line.
     * 
     * @return boolean
     */
    public function isContinued()
    {
        // this will be told by the last stored physical line
        return ($this->_content[count($this->_content)-1]['wrapped']);
    }
    
    /**
     * Detect if this logical Line contains wrapped physical data.
     * 
     * @return boolean
     */
    public function isWrapped()
    {
        return (count($this->_content) > 1);
    }
       
    /**
     * Detect if the line is a continuation of a wrapped one.
     * 
     * This cannot be derived from the current line. The current line can
     * be expected to be a continuation in case the preceeding line
     * expects more content (that it does not contian itself), eg it was
     * endet with an backslash.
     * Such lines may be consolidatet into one single Therion_Line object
     * using {@link addPhysicalLine()} on the prior line with the inspected one
     * as parameter: '<code>$priorLine->addPhysicalLine($thisOne)</code>'.
     * 
     * @param File_Therion_Line|array    $priorLines array of prior lines
     * @param File_Therion_Line   $line       current line to test
     * @return boolean true in case of continuation
     */
    public function isContinuation($priorLine)
    { 
        if (is_array($priorLine) && count($priorLine) > 0) {
            $preceedingLineObj = $priorLine[count($priorLine)-1];
        } else {
            $preceedingLineObj = $priorLine;
        }
        
        if (!is_a($preceedingLineObj, 'File_Therion_Line')) {
            throw new InvalidArgumentException(
                'detectWrapContinuation(): Invalid $priorLine argument! '
                ."passed type='".gettype($priorLine)
                ."', expected File_Therion_Line");
        } else {
            return $preceedingLineObj->isContinued();
        }
        
        if (count($priorLines) == 0) {
            // first line cannot be a continuation
            return false;
        } else {
            // Get previous line and test for ending backslash
            $lastOldLine = $priorLines[count($priorLines)-1];
            return (preg_match('/\\$/', $lastOldLine))? true : false;
        }
    }
    
    
    /**
     * Set separator for appending comments to datalines in output ({@link toString()}.
     * 
     * @param string $separator
     */
    public function setCommentSeparator($separator)
    {
        $separator = trim($separator, "\r\n");  // strip newlines
        $this->_out_std_commentSep = $separator;
    }
    
    
    /**
     * Set indenting for output ({@link toString()}.
     * 
     * @param string $indent
     */
    public function setIndent($indent)
    {
        $indent = trim($indent, "\r\n");  // strip newlines
        $this->_content[0]['indent'] = $indent;  // indenting is stored at first line
    }
    
    
    /**
     * Count (wrapped) lines in this line (SPL Countable).
     * 
     * returns the raw value of lines. Ususally 1, however if the line contains
     * wrapped data, it will return the 'true' count of those lines as they
     * would appear in a textual representation.
     * 
     * @return int number of raw lines
     */
    public function count()
    {
        return count($this->_content);
    }
    
    /**
     * Change default End-of-Line characters of string representation.
     * 
     * The default EOL is PHP_EOL, eg. the local platform EOL.
     * 
     * Use this to change the EOL character to an platform dependant
     * char sequence; for example to generate windows files from linux.
     * 
     * @param string EOL sequence (be sure to get the escaping right)
     */
    public function changeEOL($eol) {
        $this->_eol = $eol;
    }
    
    
    
    /*
     * Utility functions
     */
     
    /**
    * Filter empty Therion data lines out of array.
    * 
    * Returns the lines that are not only commentary or empty;
    * that is: lines with real content.
    * 
    * @param array
    * @return int
    * @throws InvalidArgumentException in case element is not a line object
    */
    public static function filterNonEmpty(array $arrayToCount)
    {
        $filtered = array();
        for ($i=0; $i < count($arrayToCount); $i++) {
            $a = $arrayToCount[$i];
            if (!is_a($a, 'File_Therion_Line')) {
                throw new InvalidArgumentException(
                    "countNonEmpty() @$i: invalid object type ".gettype($a));
            }
            
            if (! $a->isCommentOnly()) {
                $filtered[] = $a;
            }
        }
        
        return $filtered;
    }
}
    
?>
