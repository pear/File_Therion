<?php
/**
 * Therion cave shot data type class.
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
 * Class representing a therion shot object.
 * 
 * The centreline contains the shots of the survey.
 *
 * @category   file
 * @package    File_Therion
 * @author     Benedikt Hallinger <beni@php.net>
 * @copyright  2016 Benedikt Hallinger
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @link       http://pear.php.net/package/File_Therion/
 */
class File_Therion_Shot
{
    
    /**
     * Basic normalized data elements.
     * 
     * @var array  
     */
    protected $_data = array(
        'from'      => "", // Station
        'to'        => "", // Station
        'length'    => "",
        'bearing'   => "",
        'gradient'  => "",
        'left'      => "",
        'right'     => "",
        'up'        => "",
        'down'      => "",
        // more according to thbook
    );
    
    /**
     * Flags of this shot.
     * 
     * @var array  
     */
    protected $_flags = array(
       'surface'     => false,
       'splay'       => false,
       'duplicate'   => false,
       'approximate' => false,
    );
    
    
    /**
     * Create a new therion shot object.
     *
     */
    public function __construct()
    {
    }
    
    /**
     * Parse string content into a shot object using ordering information.
     * 
     * @param array $data  datafields to parse
     * @param array $order therion names of datafields in correct order
     * @return File_Therion_Shot shot object
     * @throws File_Therion_SyntaxException in case $data does not suit $order
     */
    public static function parse(array $data, array $order)
    {
        // inspect $order: count "active" fields
        
        return new File_Therion_Shot();
        
        throw new File_Therion_SyntaxException(
            "parse(): Invalid shot data count ("
            .count($data)." != ".count($order).")"
        );
    }
    
    /**
     * Set shot flag.
     * 
     * @param string  $flag  name of the flag.
     * @param boolean $value true/false
     * @throws PEAR_Exception with nested lower level exception
     */
    public function setFlag($flag, $value=true)
    {
        $value = ($value)? true : false;  // force explicitely bool
        
        if ($flag == "approx") $flag = "approximate"; // expand alias
        
        if (array_key_exists($flag, $this->_flags)) {
            $this->_flags[$flag] = $value;
        } else {
            throw new PEAR_Exception("setFlag(): Invalid flag $flag",
                new InvalidArgumentException("flag not nvalid for shot"));
        }
    }
    
    /**
     * Get shot flag.
     * 
     * @param string  $flag  name of the flag.
     * @throws PEAR_Exception with nested lower level exception
     */
    public function getFlag(string $flag)
    {
        if ($flag == "approx") {
            // expand alias
            $flag = "approximate";
        }
        if (array_key_exists($flag, $this->_flags)) {
            return $this->_flags[$flag];
        } else {
            throw new PEAR_Exception("setFlag(): Invalid flag $flag",
                new InvalidArgumentException("flag not nvalid for shot"));
        }
    }
    
}

?>
