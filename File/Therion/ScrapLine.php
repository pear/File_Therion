<?php
/**
 * Therion cave scrap line object class.
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
 * Class representing a scrap line definition object.
 *
 * This is a vector graphic element that is used to form a renderable cavemap.
 * 
 * @category   file
 * @package    File_Therion
 * @author     Benedikt Hallinger <beni@php.net>
 * @copyright  2016 Benedikt Hallinger
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @link       http://pear.php.net/package/File_Therion/
 */
class File_Therion_ScrapLine
    extends File_Therion_BasicObject
{
    
    /**
     * Object options (id, ...).
     * 
     * @var array assoc array
     */
    protected $_options = array(
        'id' => "",
    );
    
    
    /**
     * Basic data elements.
     * 
     * @var array  
     */
    protected $_data = array(
        'type' => "",
    );
    
    
    /**
     * Create a new therion ScrapLine object.
     *
     * @param string $type type of the line
     * @todo Restrict naming convention, not all characters are allowed!
     */
    public function __construct($type, $options = array())
    {
        $this->setData('type', $type);
        $this->setOption($options);
    }
    
    
    /**
     * Parses given Therion_Line-objects into internal data structures.
     * 
     * @param array $lines File_Therion_Line objects forming this object
     * @return File_Therion_ScrapLine ScrapLine object
     * @throws InvalidArgumentException
     * @todo implement me
     */
    public static function parse($lines)
    {
        if (!is_array($lines)) {
            throw new InvalidArgumentException(
                'parse(): Invalid $lines argument (expected array, seen:'
                .gettype($lines).')'
            );
        }
        
        $scrapline = null; // constructed object
        
        // get first line and construct hull object
        $firstLine = array_shift($lines);
        if (is_a($firstLine, 'File_Therion_Line')) {
            $flData = $firstLine->getDatafields();
            if (strtolower($flData[0]) == "line") {
                $scrapline = new File_Therion_ScrapLine(
                    $flData[1], // type, mandatory
                    $firstLine->extractOptions()
                );
            } else {
                throw new File_Therion_SyntaxException(
                    "First scrap-line line is expected to contain line definition"
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
            if (!strtolower($flData[0]) == "endline") {
                throw new File_Therion_SyntaxException(
                    "Last scrap-line line is expected to contain endline definition"
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
        //
        // todo: implement parsing code
        //
        
        return $scrapline;
        
    }
    
}

?>