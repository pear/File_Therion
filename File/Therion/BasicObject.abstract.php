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
     * Object data (simple ones).
     * 
     * When inheriting from this class, redeclare the array and remember to
     * define explicit setters/getters to access those elements.
     * 
     * @var array assoc array
     */
    protected $_simpledata = array(
        // In subclasses: add here the valid options and datatypes
        // 'team'        => array(),
        // 'explo-team'  => array(),
        // 'declination' => 0.0,
    );
    
    /**
     * Verify basic compliance of item.
     * 
     * This will test existence and type of the passed key and value and
     * throw an appropriate exception in case of problems.
     * 
     * @param string     $type  name of local object variable
     * @param string     $key   key to check (if != null)
     * @param string     $value value to check against (if != null)
     * @return true in case everything was ok
     * @throws InvalidArgumentException in case of verification failure
     */
    protected function _verify($type, $key=null, $value=null)
    {
        // check basic existence of type
        if (!isset($this->{"$type"})) {
            throw new InvalidArgumentException("Invalid type name '$type'");
        }
        
        // check basic existence of key
        if ($key!==null) {
            if (!isset($this->{"$type"}[$key])) {
                throw new InvalidArgumentException(
                    "$type: Invalid key name '$key'");
            }
        }
        
        // check that passed value is of correct type
        if ($value!==null) {
            if (gettype($this->{"$type"}[$key]) !== gettype($value)) {
                throw new InvalidArgumentException(
                    "$type [$key]: Invalid value type '".gettype($value)."'! "
                    ."passed option='$key'; type='".gettype($value)
                    ."'; expected='".gettype($this->{"$type"}[$key])."'"
                );
            }
        }
        
        
        // all checks passed:
        return true;
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
     * @param array $option option (or associative array of options) to set
     * @param array $value when $options is no array: value to set
     * @see {@link $_options}
     * @throws InvalidArgumentException when option is not defined
     */
    public function setOption($option, $value=null)
    {
        if (!is_array($option)) {
            // single mode
            $this->_verify('_options', $option, $value);
            $this->_options[$option] = $value;
            
        } else {
            // multi mode
            foreach ($option as $k => $v) {
                $this->setOption($k, $v);
            }
        }
    }
     
    /**
     * Get option of this object.
     *
     * @param string $option option key to get
     * @return mixed depending on option
     * @see {@link $_options}
     * @throws InvalidArgumentException when option is not defined
     */
     public function getOption($option)
     {
          return $this->_options[$option];
     }
     
     
    /**
     * Set some simple data of this object.
     * 
     * Dev-Note: real object data should be accessible to the end user
     * only through explicitely named functions.
     * 
     * The key and datatype will be checked against the
     * {@link $_data} array.
     *
     * @param array $key name (or associative array of data) to set
     * @param array $value when $key is no array: value to set
     * @see {@link $_data}
     * @throws InvalidArgumentException when option is not defined
     */
     protected function setData($key, $value=null)
     {
         if (!is_array($key)) {
            // single mode
            $this->_verify('_data', $key, $value);
            $this->_data[$key] = $value;
            
        } else {
            // multi mode
            foreach ($key as $k => $v) {
                $this->setData($k, $v);
            }
        }
         
     }
     
    /**
     * Get some simple data of this object.
     * 
     * Dev-Note: real object data should be accessbile to the end user
     * only through explicitely named functions.
     *
     * @param string $key data key to get
     * @return mixed depending on option
     * @see {@link $_data}
     * @throws InvalidArgumentException when option is not defined
     */
     protected function getData($key)
     {
          return $this->_data[$key];
     }
    
}

?>
