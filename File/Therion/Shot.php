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
    extends File_Therion_BasicObject
{
    
    /**
     * Survey options (none so far).
     * 
     * @var array assoc array
     */
    protected $_options = array();
    
    
    /**
     * Basic data elements.
     * 
     * @var array  
     */
    protected $_metadata = array(
        'from'      => "",
        'to'        => "",
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
       'surface'   => false,
       'splay'     => false,
       'duplicate' => false,
        // more according to thbook
    );
    
    
    /**
     * Create a new therion shot object.
     *
     */
    public function __construct()
    {
    }
    
}

?>
