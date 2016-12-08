<?php
/**
 * Therion cave survey formatter interface class.
 *
 * PHP version 5
 *
 * @category   file
 * @package    File_Therion_Formatters
 * @author     Benedikt Hallinger <beni@php.net>
 * @copyright  2016 Benedikt Hallinger
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @link       http://pear.php.net/package/File_Therion/
 */

/**
 * Interface defines formatter plugins used by therion file objects.
 * 
 * Formatters can be used to influence the writing of therion file data.
 * They are attached to a File_Therion instance to carry out formatting stuff.
 * 
 * Formatters are exprectet to be able to work independently, so you also may
 * get lines unformatted as copy (see {@link File_Therion->toLines()}) and
 * feed them in your formatter by hand.
 *
 * @category   file
 * @package    File_Therion_Formatters
 * @author     Benedikt Hallinger <beni@php.net>
 * @copyright  2016 Benedikt Hallinger
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @link       http://pear.php.net/package/File_Therion/
 */
interface File_Therion_Formatter
{
    /**
     * Format lines.
     * 
     * This method is automatically invoked by File_Therion->getLines() to
     * transparently (re-)format the lines returned to the caller.
     * 
     * @param array File_Therion_Lines to format
     * @return array formatted line objects
     */
    public function format($lines);
    
}


/**
 * A simple debug formatter, just for testing purposes and example.
 * 
 * It prepends every line with a line number.
 */
class File_Therion_AddLineNumberFormatter implements File_Therion_Formatter
{
    /**
     * initial offset for line numbers
     * 
     * @var int
     */
    protected $offset = 0;
    
    /**
     * minimum length of line number
     * 
     * @var int
     */
    protected $pad = 0;

    /**
     * Constuct a new AddLineNumberFormatter with given offset.
     * @param int $pad minimal width of line numbers
     * @param int $offset to start with
     */
    public function __construct($pad = 0, $offset = 0)
    {
        $this->offset = $offset;
        $this->pad = $pad;
    }
    
    /**
     * Add a line number.
     * 
     * @param array $lines
     * @return array
     */
    public function format($lines) {
        for ($i=0; $i<count($lines); $i++) {
            $l =& $lines[$i];
            
            $spf = sprintf("%0".$this->pad."d: ", $this->offset + $i);
            $l->setIndent($spf.$l->getIndent());
        }
        return $lines;
    }
    
}

?>