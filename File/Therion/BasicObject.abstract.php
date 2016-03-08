<?php
/**
 * Therion cave survey abstract object class.
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
 * Abstract Class representing a basic therion object.
 * 
 * It features some common functions to set/get basic data elements.
 *
 * @category   file
 * @package    File_Therion
 * @author     Benedikt Hallinger <beni@php.net>
 * @copyright  2016 Benedikt Hallinger
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @link       http://pear.php.net/package/File_Therion/
 */
abstract class File_Therion_AbstractObject
{
    
    /**
     * Object options (title, ...)
     * 
     * @var array assoc array
     */
    protected $_options = array(
        // In subclasses: add here the valid options and datatypes
        // 'title' => "",
        // 'id'    => 0,
        // 'array' => array(),
    );
    
    /**
     * Object metadata (simple ones)
     * 
     * @var array assoc array
     */
    protected $_metadata = array(
        // In subclasses: add here the valid options and datatypes
        // 'team'        => array(),
        // 'explo-team'  => array(),
        // 'declination' => 0.0,
    );
    
    /**
     * Central shorthand function to get/set data items in options/metadata.
     * 
     * @param string     $type "options" or "metadata"
     * @param string     $key  key to get/set
     * @param mixed|null value to set or NULL to retrieve value
     * @returns nothing or stored value
     * @throws PEAR_Exception with InvalidArgumentException
     */
    protected function _getSet_dataOrOption($type, $key, $value=null)
    {
        if ($value === null) {
            // GET VALUE
            if (array_key_exists($key, $this->{"_$type"})) {
             return $this->{"_$type"}[$key];
             } else {
                throw new PEAR_Exception("get_$type: Invalid option name '$k'",
                    new InvalidArgumentException("passed option='$k'"));
             }
             
         } else {
             // SET VALUE
             if (array_key_exists($key, $this->{"_$type"})) {
                 if (gettype($this->{"_$type"}[$key]) == gettype($value)) {
                     $this->{"_$type"}[$key] = $value;
                 } else {
                    throw new PEAR_Exception("set_$type '$k': Invalid option type!",
                        new InvalidArgumentException(
                        "passed option='$key'; type='".gettype($value)
                        ."'; expected='".gettype($this->{"_$type"}[$key])."'")
                    );
                 }
             } else {
                throw new PEAR_Exception("set_$type: Invalid option name '$k'",
                    new InvalidArgumentException("passed option='$key'"));
             }
         }
    }
    
    /**
     * Set options of this object.
     * 
     * The key and datatype will be checked against the
     * {@link $_options} array.
     *
     * @param array $options associative array of options to set
     * @see {@link $_options}
     * @throws PEAR_Exception with InvalidArgumentException
     */
     public function setOptions($options=array())
     {
         foreach ($options as $k => $v) {
             $this->_getSet_dataOrOption('options', $k, $v);
         }
         
     }
     
    /**
     * Get option of this object.
     *
     * @param string $option option key to get
     * @return mixed depending on option
     * @see {@link $_options}
     * @throws PEAR_Exception with InvalidArgumentException
     */
     public function getOption($option)
     {
          return $this->_getSet_dataOrOption('options', $option, null);
         
     }
     
    /**
     * Set Metadata of this object.
     * 
     * The key and datatype will be checked against the
     * {@link $_options} array.
     *
     * @param array $options associative array of options to set
     * @see {@link $_metadata}
     * @throws PEAR_Exception with InvalidArgumentException
     */
     public function setMetaData($options=array())
     {
         foreach ($options as $k => $v) {
             $this->_getSet_dataOrOption('metadata', $k, $v);
         }
         
     }
     
    /**
     * Get Metadata of this object.
     *
     * @param string $option option key to get
     * @return mixed depending on option
     * @see {@link $_metadata}
     * @throws PEAR_Exception with InvalidArgumentException
     */
     public function getMetaData($option)
     {
          return $this->_getSet_dataOrOption('metadata', $option, null);
         
     }
    
    
    
    
    
}

?>
