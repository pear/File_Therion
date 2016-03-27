<?php
/**
 * Therion cave surface object class.
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
 * Class representing a therion surface object.
 * 
 * A surface object holds information of the surface around a cave.
 *
 * @category   file
 * @package    File_Therion
 * @author     Benedikt Hallinger <beni@php.net>
 * @copyright  2016 Benedikt Hallinger
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @link       http://pear.php.net/package/File_Therion/
 */
class File_Therion_Surface
    extends File_Therion_BasicObject
    implements Countable
{
    
    /**
     * Surface options (title, ...).
     * 
     * @var array assoc array
     */
    protected $_options = array(
        // todo unchecked
    );
    
    
    /**
     * Basic data elements.
     * 
     * @var array  
     */
    protected $_data = array(
        'cs'         => "", // <coordinate system>
        'bitmap'     => "", // <filename> <calibration>
        'grid-units' => "", // <units>, meter by default if not specified
        'grid'       => array(), // <origin x> <origin y> <x spacing> <y spacing> <x count> <y count>
        'grid-flip'  => "", // (none)/vertical/horizontal
        'data'       => array()
    );
    
    
    /**
     * Create a new therion surface object.
     * 
     * @param string $id optional name of the surface object.
     */
    public function __construct($id="")
    {
        $this->setName($id);
    }
    
    
    /**
     * Parses given Therion_Line-objects into internal data structures.
     * 
     * @param array $lines File_Therion_Line objects forming a surface
     * @return File_Therion_Surface Surface object
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
        
        $surface = null; // constructed surface
        
        // get first line and construct surface hull object
        $firstLine = array_shift($lines);
        if (is_a($firstLine, 'File_Therion_Line')) {
            $flData = $firstLine->getDatafields();
            if (strtolower($flData[0]) == "surface") {
                switch (count($flData)) {
                    case 1:
                        $surface = new File_Therion_Surface();
                        break;
                    case 2:
                        $surface = new File_Therion_Surface($flData[1]);
                        break;
                    default:
                        throw new File_Therion_SyntaxException(
                            "invalid surface command arg count ("
                            .count($flData)-1 .")"
                        );
                }
            } else {
                throw new File_Therion_SyntaxException(
                    "First surface line is expected to contain surface definition"
                );
            }
                
        } else {
            throw new InvalidArgumentException(
                "Invalid $line argument @1; passed type='"
                .gettype($firstLine)."'");
        }
        
        // Pop last last line and control that it was the end tag
        $lastLine  = array_pop($lines);
        if (is_a($lastLine, 'File_Therion_Line')) {
            $flData = $firstLine->getDatafields();
            if (!strtolower($flData[0]) == "endsurface") {
                throw new File_Therion_SyntaxException(
                    "Last surface line is expected to contain endsurface definition"
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
                            $command  = strtolower(array_shift($lineData));
                            
                            switch ($command) {
                                case 'input':
                                    // ignore silently because this should be 
                                    // handled at the file level
                                break;
                                
                                case 'cs':
                                    // todo
                                break;
                                
                                case 'bitmap':
                                    // todo
                                break;
                                
                                case 'grid-units':
                                    // todo
                                break;
                                
                                case 'grid':
                                    // todo
                                break;
                                
                                case 'grid-flip':
                                    // todo
                                break;
                                
                                
                                default:
                                    // not a valid command!
                                    // TODO: see if this is a data specification
                                    //       (eg numbers) and add data if so
                                    
                                    if (is_numeric($command)) {
                                        // numeric dats signals DATA block
                                        array_unshift($lineData, $command); //readd
                                        
                                        
                                        // TODO: Add to object
                                        // $surface->add...($lineData[0], ...);
                                
                                    } else {
                                        // not in data mode: rise exception
                                        throw new File_Therion_Exception(
                                         "unsupported surface command '$command'");
                                    }
                            }
                        }
                    }
                break;
                
                default:
                    throw new File_Therion_SyntaxException(
                        "unsupported multiline surface command '$type'"
                    );
            }
        } 
        
        return $surface;
        
    }
    
    /**
     * Get name (id) of this surface object.
     * 
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }
    
    /**
     * Change name (id) of this surface object.
     * 
     * @return string
     * @todo implement parameter checks
     */
    public function setName($id)
    {
        $this->_name = $id;
    }
    
    /**
     * Count number of data elements (SPL Countable).
     *
     * @return int number of data elements
     */
    public function count()
    {
        return count($this->_data['data']);
    }
    
    
}

?>
