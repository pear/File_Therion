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
 * The interface is restricted because other than options should be
 * explicitely defined with getters/setters.
 *
 * @category   file
 * @package    File_Therion
 * @author     Benedikt Hallinger <beni@php.net>
 * @copyright  2016 Benedikt Hallinger
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @link       http://pear.php.net/package/File_Therion/
 */
abstract class File_Therion_BasicObject
{
    
    /**
     * Object options (title, ...).
     * 
     * This defines the options available in the concrete object.
     * When inheriting from this class, redeclare the array.
     * 
     * @see {@link _getSet_dataOrOption()}
     * @var array assoc array
     */
    protected $_options = array(
        // In subclasses: add here the valid options and datatypes
        // 'title' => "",
        // 'id'    => 0,
        // 'array' => array(),
    );
    
    /**
     * Object metadata (simple ones).
     * 
     * When inheriting from this class, redeclare the array and remember to
     * define explicit setters/getters to access those elements.
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
     * This will test existence and type of the passed key and value and
     * throw an appropriate exception in case of problems.
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
                    throw new PEAR_Exception("set_$type '$key': Invalid option type!",
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
     * There are two call modes:
     * <code>
     * //set single option:
     * $obj->setOptions($key, $value);
     * 
     * // set several options at once using assoc array:
     * $opts = array('key1' => 'value', 'key2' => 'value', ...);
     * $obj->setOptions($opts);
     *
     * @param array $options associative array of options to set
     * @see {@link $_options}
     * @throws PEAR_Exception with InvalidArgumentException
     */
    public function setOptions($options=array(), $value=null)
    {
        if (!is_array($options)) {
            // single mode
            $this->_getSet_dataOrOption('options', $options, $value);
            
        } else {
            // multi mode
            foreach ($options as $k => $v) {
            $this->_getSet_dataOrOption('options', $k, $v);
            }
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
     * Set some Metadata of this object.
     * 
     * Dev-Note: real object data should be accessbile to the end user
     * only through explicitely named functions.
     * 
     * The key and datatype will be checked against the
     * {@link $_options} array.
     *
     * @param array $options associative array of options to set
     * @see {@link $_metadata}
     * @throws PEAR_Exception with InvalidArgumentException
     */
     protected function setMetaData($options=array())
     {
         foreach ($options as $k => $v) {
             $this->_getSet_dataOrOption('metadata', $k, $v);
         }
         
     }
     
    /**
     * Get some Metadata of this object.
     * 
     * Dev-Note: real object data should be accessbile to the end user
     * only through explicitely named functions.
     *
     * @param string $option option key to get
     * @return mixed depending on option
     * @see {@link $_metadata}
     * @throws PEAR_Exception with InvalidArgumentException
     */
     protected function getMetaData($option)
     {
          return $this->_getSet_dataOrOption('metadata', $option, null);
         
     }
    
}

?>
