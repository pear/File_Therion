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
     * @see _getSet_dataOrOption()
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
     * @param string     $type    name of local object variable
     * @param string     $key     key to check (if != null)
     * @param string     $value   value to check against (if != null)
     * @param string     $altname alternative type-name for error output
     * @return true in case everything was ok
     * @throws InvalidArgumentException in case of verification failure
     */
    protected function _verify($type, $key=null, $value=null, $altname="")
    {
        if (!$altname) $altname = ltrim($type, '_');
        
        // check basic existence of type
        if (!isset($this->{"$type"})) {
            throw new InvalidArgumentException("Invalid type name '$type'");
        }
        
        // check basic existence of key
        if ($key!==null) {
            if (!array_key_exists($key, $this->{"$type"})) {
                throw new InvalidArgumentException(
                    "Invalid $altname name '$key'");
            }
        }
        
        // check that passed value is of correct type
        if (!is_null($value)
            && !is_null($this->{"$type"}[$key])
            && gettype($this->{"$type"}[$key])
            ) {
            if (gettype($this->{"$type"}[$key]) !== gettype($value)) {
                throw new InvalidArgumentException(
                    "$altname [$key]: Invalid value type '".gettype($value)."'! "
                    ."passed option='$key'; type='".gettype($value)
                    ."'; expected='".gettype($this->{"$type"}[$key])."'"
                    ."; value='".$value."'"
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
     * $obj->setOption($key, $value);
     * 
     * // set several options at once using assoc array:
     * $opts = array('key1' => 'value', 'key2' => 'value', ...);
     * $obj->setOptions($opts);
     *
     * @param array $option option (or associative array of options) to set
     * @param array $value when $options is no array: value to set
     * @see $_options
     * @throws InvalidArgumentException when option is not defined
     */
    public function setOption($option, $value=null)
    {
        if (!is_array($option)) {
            // single mode
            
            // When the option accepts non-array and we got an array with just
            // a single value, we just fetch it. Most probably this comes from
            // Line->extractOptions() and this will always return an array.
            if (is_array($value) && count($value) == 1
                && array_key_exists($option, $this->_options)
                && !is_array($this->_options[$option])) {
                $value = array_shift($value);
            }
            
            $this->_verify('_options', $option, $value);
            if (!$this->handleCommonOption($option, $value)) {
                // option was not handled specially
                $this->_options[$option] = $value;
            }
            
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
     * @see $_options
     * @throws InvalidArgumentException when option is not defined
     */
     public function getOption($option)
     {
         $this->_verify('_options', $option, null);
         return $this->_options[$option];
     }
     
     
     
    /**
     * Handle known options supported by several objects.
     * 
     * Some options are common to several objects and need enhanced parsing.
     * This is centralized here.
     * Note that this funcion should only be used after a call to
     * {@link _verify()} so it is assured the key exists in the specific class.
     * 
     * @param string $option to try to handle
     * @param mixed $value value to handle
     * @return boolean true = handling performed, no further action needed.
     * @throws File_Therion_SyntaxException on syntax errors (eg missing values)
     */
    protected function handleCommonOption($option, $value=null)
    {
        // Handle options;
        // each case branch must return TRUE on success
        $option = strtolower($option);
        switch ($option) {
            case 'author':
                if (is_array($value)) {
                    // author with year: parse into person object
                    $value[1] = File_Therion_Person::parse($value[1]);
                } else {
                    $value = array("", File_Therion_Person::parse($value));
                }
                $this->_options[$option] = $value;
                return true;
            break;
            
            // TODO: There are more common options!
            
            
            default:
                return false; // signal: "not handled"
                
        }
            
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
     * @see $_data
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
     * @see $_data
     * @throws InvalidArgumentException when option is not defined
     */
     protected function getData($key)
     {
         $this->_verify('_data', $key, null);
         return $this->_data[$key];
     }
    
    
    /**
     * Generate basic options string.
     * 
     * For options that are not null a string will be added with the value:
     * "-<option> <value>"
     * - string value: will be escaped and added unless empty
     * - array value: will be joined with space and then escaped as single value unless empty
     * 
     * @return string
     */
    public function getOptionsString()
    {
        // DEV note:
        // it may be a good idea to let outside code supply callbacks
        // (array('optionname' => CALLBACK)) that generate values, so
        // special option formats can be handled nicely from the calling class
        
        $options = ""; // the finalized options string
        
        // walk each option and try to parse it to string
        foreach (array_keys($this->_options) as $opt) {
            $optval = $this->getOption($opt);
            $o = "";
            switch (gettype($optval)) {
                case 'NULL':
                    // ignore silently - default value!
                    continue;
                break;
                
                case 'string':
                    if ($optval != "") {
                        $o = $optval;
                    }
                break;
                
                case 'array':
                    if (count($optval > 0)) {
                        $o = implode(" ", $optval);
                    }
                break;
                
                
                default:
                    throw new File_Therion_Exception(
                        "option type ".getType($optval)." could not be"
                        ."converted to optionString: unsupported!");
            }
            
            if ($o != "") {
                // generate option string out of value if value was parsed
                $options .= " -".$opt." ".File_Therion_Line::escape($o);
            }
            
        }
        
        return $options;
    }
}

?>