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
 * A single logical line of a Therion file
 *
 * This class implements the basic file syntax and represent a single
 * logocal line of such a file.
 * Therion files may be wrapped (a logically signle line can be wrapped into
 * several physical lines); this class helps to deal with that.
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
    * Line content
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
    * Create a new therion line object
    *
    * @param string $data    data of the line
    * @param string $comment comment of the line
    * @param string $indent  intent characters
    * @throws PEAR_Exception with wrapped lower level exception
    */
    public function __construct($data, $comment = '', $indent = '')
    {
           if (is_string($data) && is_string($comment) && is_string($indent)) {
                // add internal data format        
                $this->addData($indent, $data, $comment);
              
           } else {               
                throw new PEAR_Exception('parseSurvey(): Invalid $line type!', new InvalidArgumentException());
           }    
    }
    
    /**
     * Parse a physical Therion line into its contents
     * 
     * @param string  $line physical line
     * @return File_Therion_Line object
     * @throws File_Therion_SyntaxException in case of syntax error
     */
    protected static function parse($line)
    {
        $matches = array();
        if (! preg_match("/^(\s*)(.+?)((?:#.*))$/", $line, $matches) ) {
            throw new File_Therion_SyntaxException("line syntax error: '$line'");
        } else {
            // strip comment sign from comment data:
            $matches[2] = preg_replace("^#", $matches[2], '');
            
            return new File_Therion_Line($matches[1], $matches[2], $matches[0]);
        }
    }
    
    /**
     * Add a physical Therion line as wrapped into its contents
     * 
     * @param string|File_Therion_Line  $line physical line or Line object
     * @throws File_Therion_SyntaxException in case the Line did not expect additional wrapped content
     */
    public function addPhysicalLine($line)
    {
        $this->expectMoreLines();
        if (is_string($line)) {
            $this->addPhysicalLine(File_Therion_Line::parse($line));
            
        } else {
            foreach ($line->_content as $cl) {
                $this->addData($cl['indent'], $cl['data'], $cl['comment']);
            }
        }
    }
    
    /**
     * Add internal data to stack
     * 
     * @param string  $data    data of the line
     * @param string  $comment comment of the line
     * @param string  $indent  intent characters
     * @param boolean $indent  in case the line expects further data
     * @throws File_Therion_SyntaxException in case the Line did not expect additional wrapped content
     * @todo syntax checks on parameters?
     */
    protected function addData($indent, $data, $comment)
    {
        // Detect if this line expects further lines...
        /* thbook says on p.12: "each line ending with a backslash (\) is
        *  considered to continue on the next line, as if there was neither
        *  line-break nor backlash."      
        */
        $dataContinued    = preg_match('/\\$/', $data;
        $commentContinued = preg_match('/\\$/', $comment;
        $wrapDetected = ($dataContinued || $commentContinued)
        
        // strip continuation character from data blocks:
        preg_replace('\\$', $data, '');
        preg_replace('\\$', $comment, '');
        
        if (count($this->_content) == 0 || $this->isContinued()) {
        
            // push the line on internal stack
            $this->_content[] = array(
                'indent'  =>  $indent,
                'data'    =>  $data,
                'comment' =>  $comment,
            );
            if ($wrapDetected) {
                $this->expectMoreLines();
            }
            
        } else {
            throw new File_Therion_SyntaxException('Unexpected continuation added to unwrapped file')
        }
    }
    
    /**
     * Let the Line know that it should expect an additional wrapped line.
     * 
     */
    protected function expectMoreLines()
    {
        for ($i=0; $i< count($this->_content); $i++) {
            $this->_content[$i]['wrapped'] = true;
        }
    }
    
    
    /**
     * Returns the unwrapped line content as string
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
     * Returns the unwrapped comment as string
     * 
     * Comments of wrapped lines will be joined.
     * 
     * @param string $ml_sep Separator used for joining multiline
     * @return string data part of the unwrapped line
     */
    public function getComment($ml_sep = '; ')
    {
        $ar = array();
        foreach ($this->_content as $cl) {
            $ar[] = $cl['comment'];
        }
        return implode($ml_sep, $ar);
    }
    
    /**
     * Returns the indenting characters
     * 
     * In case this line contains wrapped content, the indenting of
     * the first physical line will be used.
     * 
     * @return string
     */
    public function getIndent()
    {
        return $this->_content[0]['intend'];
    }
 
    /**
     * Say if this is just a comment or empty line
     * 
     * @return boolean
     */
    public function isCommentOnly()
    {
        // its the case if the datapart is empty
        return preg_match('/^\s*$/', $this->getContent);
    }
    
    /**
     * Detect if this Line expects wrapped content in the following physical line
     * 
     * @return boolean
     */
    public function isContinued()
    {
        // this will be told by the last stored physical line
        return ($this->_content[count($this->_content)-1]['wrapped']);
    }
    
    /**
     * Detect if this logocal Line contains wrapped physical data
     * 
     * @return boolean
     */
    public function isWrapped()
    {
        return (count($this->_content) > 1);
    }
       
    /**
     * Detect if the line is a continuation of a wrapped one
     * 
     * @param array    $priorLines array of prior lines
     * @param string   $line       current line to test
     * @return boolean true in case of continuation
     */
    protected static function detectWrapContinuation($priorLines, $line)
    {
        TODO
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
     * Strip comments from single line
     * 
     * @param string  $line current line to test
     * @return string the comment
     */
    protected static function extractData($line)
    {
        $matches = array();
        preg_match("/^.+(#.*)$/", $line, $matches);
    }
    
    /**
     * Count (wrapped) lines in this line (SPL Countable)
     * 
     * returns the raw value of files. Ususally 1, however if the line contains
     * wrapped data, it will return the 'true' count of those lines as they
     * would appear in a textual representation.
     * 
     * @return int number of raw lines
     */
    public function count()
    {
        return count($this->_content);
    } 
    
}
    
?>