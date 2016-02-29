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
class File_Therion_Survey implements Countable
{
    
    
    /**
     * Associated subsurvey structures
     * 
     * @var array
     */
    protected $_surveys = array();
    
    /**
     * Create a new therion survey object.
     *
     * @todo implement me please
     */
    public function __construct()
    {
        
    }
    
    
    /**
     * Parses given Therion_Line-objects into internal data structures.
     * 
     * @param array $lines array of File_Therion_Line objects containing a survey
     * @todo implement me
     */
    public function parse(&$lines)
    {
        // walk lines and parse
        foreach ($lines as $line) {
            print("DBG: SURVEY CONTEXT PARSED: ".$line->toString());
        }
    }
    
    /**
     * Count subsurveys of this survey (SPL Countable).
     *
     * @return int number of subsurveys
     */
    public function count()
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
