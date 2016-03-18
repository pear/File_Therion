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
     * Known field name aliases
     * 
     * @var array
     */
    protected static $_aliases = array(
        'tape'     => 'length',
        'compass'  => 'bearing',
        'clino'    => 'gradient',
        'ceiling'  => 'up',
        'floor'    => 'down'
    );
    
    /**
     * data definition order for this shot.
     * 
     * This may contain alias names.
     * 
     * @var array
     */
    protected $_order = array(
        'from', 'to', 'length', 'bearing', 'gradient',
        'left', 'right', 'up', 'down'
    );
    
    /**
     * data reading style
     * 
     * @var string
     */
    protected $_style = "normal";
    
    /**
     * Basic normalized data elements.
     * 
     * @var array  
     */
    protected $_data = array(
        'from'      => "", // Station
        'to'        => "", // Station
        'length'    => 0.0,
        'bearing'   => 0.0,
        'gradient'  => 0.0,
        'left'      => 0.0,
        'right'     => 0.0,
        'up'        => 0.0,
        'down'      => 0.0,
        // more according to thbook
        // Therion book says: station, from, to, tape/length, 
        // [back]compass/[back]bearing, [back]clino/[back]gradient, depth,
        // fromdepth, todepth, depthchange, counter, fromcount, tocount,
        // northing, easting, altitude, up/ceiling, down/floor, left,
        // right, ignore, ignoreall.
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
     * After creating, set style and order.
     */
    public function __construct()
    {
    }
    
    /**
     * Parse string content into a shot object using ordering information.
     * 
     * @param array  $data  datafields to parse
     * @param array  $order therion names of datafields in correct order
     * @return File_Therion_Shot shot object
     * @throws File_Therion_SyntaxException
     * @throws InvalidArgumentException
     * @todo implement more fields (currently just basic normal data fields)
     */
    public static function parse($data, $order)
    {
        
        // craft basic shot
        $shot = new File_Therion_Shot();
        $shot->setOrder($order);  // will throw exception
        
        // use order (with normalized names) to parse value into correct field
        $lastParsedOrder = null;
        foreach ($shot->getOrder(true) as $o) {
            $lastParsedOrder = $o; // just for the record
            
            $value = array_shift($data); // get next corresponding value
            
            // Determine parsing action and carry it out
            switch ($o) {
                // Normal fields: they have corresponding method names
                case 'from':
                    $shot->setFrom($value);
                    break;
                case 'to':
                    $shot->setTo($value);
                    break;
                case 'length':
                    $shot->setLength($value);
                    break;
                case 'bearing':
                    $shot->setBearing($value);
                    break;
                case 'gradient':
                    $shot->setGradient($value);
                    break;
                    
                // Dimensions need separate method names
                case 'up':
                    $shot->setUpDimension($value);
                    break;
                case 'down':
                    $shot->setDownDimension($value);
                    break;
                case 'left':
                    $shot->setLeftDimension($value);
                    break;
                case 'right':
                    $shot->setRightDimension($value);
                    break;
                    
                // TODO: support backwards readings; this should stay untouched
                //       to enable proper export of original data.
                //       However we need extra functions to let the user adjust.
                
                
                // TODO: Support more fields from the book
                
                // ignored field: ignore :)
                // ignoreall field: ignore and stop parsing
                case 'ignore':
                    break;
                case 'ignoreall':
                    break 2;  // done with parsing
                    
                
                default:
                    throw new File_Therion_SyntaxException(
                        "unknown/unsupported data readings order type '$o'"
                    );
            }
        }
        
        // Done with parsing.
        // In case we have still valid data left and the last field was not
        // an 'ignoreall' one, we have a syntax error.
        if ($lastParsedOrder != 'ignoreall' && count($data) > 0) {
            throw new File_Therion_SyntaxException(
                count($data)." data elements left after parsing "
                .count($order)." fields"
            );
        }
        
        return $shot;
    }
    
    /**
     * Set shot flag.
     * 
     * @param string  $flag  name of the flag.
     * @param boolean $value true/false
     * @throws InvalidArgumentException
     */
    public function setFlag($flag, $value=true)
    {
        $value = ($value)? true : false;  // force explicitely bool
        
        if ($flag == "approx") $flag = "approximate"; // expand alias
        
        if (array_key_exists($flag, $this->_flags)) {
            $this->_flags[$flag] = $value;
        } else {
            throw new InvalidArgumentException(
                "Invalid flag $flag; flag not nvalid for shot");
        }
    }
    
    /**
     * Get shot flag.
     * 
     * @param string  $flag  name of the flag.
     * @throws InvalidArgumentException
     */
    public function getFlag($flag)
    {
        if ($flag == "approx") {
            // expand alias
            $flag = "approximate";
        }
        if (array_key_exists($flag, $this->_flags)) {
            return $this->_flags[$flag];
        } else {
            throw new InvalidArgumentException(
                "Invalid flag $flag; flag not valid for shot");
        }
    }
    
    
    /**
     * Get data definition style of this shot
     * 
     * @return string "normal", "diving", etc
     */
    public function getStyle()
    {
        return $this->_style;
    }
    
    
    /**
     * Set data definition style of this shot
     * 
     * @param string $style "normal", "diving", etc
     * @throws InvalidArgumentException when style is unknown
     * @todo support other styles besides "normal"
     */
    public function setStyle($style)
    {
        if (preg_match('/^normal|diving$/', $style)) {
            $this->_style = $style;
        } else {
            throw new InvalidArgumentException(
                "data readings style unsupported: '$style'"
            );
        }
    }
    
    /**
     * Get data definition order of this shot.
     * 
     * @param boolean $normalize return unaliased field names
     * @return array ordered array with keywords
     */
    public function getOrder($normalize = false)
    {
        $fields = $this->_order;
        if ($normalize) {
            $fields = array_map(
                array('File_Therion_Shot', 'unaliasField'),
                $fields
            );
        }
        return $fields;
    }
    
    /**
     * Set data definition order of this shot.
     * 
     * @param array $order therion names of datafields in correct order
     * @throws InvalidArgumentException
     */
    public function setOrder($order)
    {
        if (count($order) < 1) {
            throw new InvalidArgumentException(
                "data readings order must at least have one value"
            );
        }
        
        // check order fields against known fields.
        // while doing this, also resolve aliases.
        $newOrder = array();
        foreach ($order as $o) {
            $o_normalized = File_Therion_Shot::unaliasField($o);
            
            if (!in_array($o_normalized, array_keys($this->_data))) {
                throw new File_Therion_SyntaxException(
                    "unknown/unsupported data readings order type '$o'"
                );
            }
            
            $newOrder[] = $o; // add original field name
        }
        
        
        $this->_order = $newOrder;
    }
    
    
    
    
    
    
    
    /**
     * GET DATA
     */
     
     
    /**
     * Get from (source) station.
     * 
     * @return string
     */
    public function getFrom()
    {
        return $this->_data['from'];
    }
    
    /**
     * Get to (targeted) station.
     * 
     * @return string
     */
    public function getTo()
    {
        return $this->_data['to'];
    }
    
    /**
     * Get shot length.
     * 
     * @return float
     */
    public function getLength()
    {
        return $this->_data['length'];
    }
    
    /**
     * Get shot compass bearing.
     * 
     * @return int between 0 and 360 (when unit was grad)
     */
    public function getBearing()
    {
        return $this->_data['bearing'];
    }
    
    /**
     * Get shot gradient.
     * 
     * @return float between -90 to 90 (when unit was grad)
     */
    public function getGradient()
    {
        return $this->_data['gradient'];
    }
    
    /**
     * Get shot left dimensions.
     * 
     * @return float
     */
    public function getLeft()
    {
        return $this->_data['left'];
    }
    
    /**
     * Get shot right dimensions.
     * 
     * @return float
     */
    public function getRight()
    {
        return $this->_data['right'];
    }
    
    /**
     * Get shot up (height to ceiling) dimensions.
     * 
     * @return float
     */
    public function getUp()
    {
        return $this->_data['up'];
    }
    
    /**
     * Get shot down (hieght to ground) dimensions.
     * 
     * @return float
     */
    public function getDown()
    {
        return $this->_data['down'];
    }

    
    
    
    /**
     * SET DATA
     */
     
     /**
     * Set from (source) station.
     * 
     * When station name is "-" or ".", then the splay flag is set implicitely.
     * 
     * @param string $station
     */
    public function setFrom($station)
    {
        if (!is_string($station)) {
            throw new InvalidArgumentException("Invalid argument type");
        }
        $this->_data['from'] = $station;
    }
    
    /**
     * Set to (targeted) station.
     * 
     * @param string $station
     */
    public function setTo($station)
    {
        if (!is_string($station)) {
            throw new InvalidArgumentException("Invalid argument type");
        }
        $this->_data['to'] = $station;
    }
    
    /**
     * Set shot length.
     * 
     * @param float $length
     */
    public function setLength($length)
    {
        // convert to explicit type
        $length = (is_string($length)||is_int($length))? floatval($length) : $length;
        
        if (!is_float($length)) {
            throw new InvalidArgumentException("Invalid argument type");
        }
        $this->_data['length'] = $length;
    }
    
    /**
     * Set shot compass bearing.
     * 
     * @param float $bearing between 0 and 360 (when unit was grad)
     * @throws InvalidArgumentException
     * @todo support other units than grad
     */
    public function setBearing($bearing)
    {
        // convert to explicit type
        $bearing = (is_string($bearing)||is_int($bearing))? floatval($bearing) : $bearing;
        
        if (!is_float($bearing)) {
            throw new InvalidArgumentException("Invalid argument type");
        }
        if ($bearing <0 || $bearing > 360) {
            // TODO: support other units too
            throw new InvalidArgumentException(
                "bearing out of range: $bearing (0->360 grad)"
            );
        }
        
        $bearing = ($bearing==360)? 0.0 : $bearing; // treat 360 as float(0)
        $this->_data['bearing'] = $bearing;
    }
    
    /**
     * Set shot gradient.
     * 
     * @param float $gradient between -90 to 90 (when unit was grad)
     * @throws InvalidArgumentException
     * @todo support other units than grad
     */
    public function setGradient($gradient)
    {
        // convert to explicit type
        $gradient = (is_string($gradient)||is_int($gradient))? floatval($gradient) : $gradient;
        
        if (!is_float($gradient)) {
            throw new InvalidArgumentException("Invalid argument type");
        }
        if ($gradient <-90 || $gradient > 90) {
            // TODO: support other units too
            throw new InvalidArgumentException(
                "gradient out of range: $gradient (-90->+90 grad)"
            );
        }
        
        
        $this->_data['gradient'] = $gradient;
    }
    
    /**
     * Set shot left dimensions.
     * 
     * @param float $left Distance to left wall
     */
    public function setLeftDimension($left)
    {
        // convert to explicit type
        $left = (is_string($left)||is_int($left))? floatval($left) : $left;
        
        if (!is_float($left)) {
            throw new InvalidArgumentException("Invalid argument type");
        }
        $this->_data['left'] = $left;
    }
    
    /**
     * Set shot right dimensions.
     * 
     * @param float $right distance to right wall
     */
    public function setRightDimension($right)
    {
        // convert to explicit type
        $right = (is_string($right)||is_int($right))? floatval($right) : $right;
        
        if (!is_float($right)) {
            throw new InvalidArgumentException("Invalid argument type");
        }
        $this->_data['right'] = $right;
    }
    
    /**
     * Set shot up (height to ceiling) dimensions.
     * 
     * @param float $up distance to ceiling.
     */
    public function setUpDimension($up)
    {
        // convert to explicit type
        $up = (is_string($up)||is_int($up))? floatval($up) : $up;
        
        if (!is_float($up)) {
            throw new InvalidArgumentException("Invalid argument type");
        }
        $this->_data['up'] = $up;
    }
    
    /**
     * Set shot down (height to ground) dimensions.
     * 
     * @param float $down distance to passage ground
     */
    public function setDownDimension($down)
    {
        // convert to explicit type
        $down = (is_string($down)||is_int($down))? floatval($down) : $down;
        
        if (!is_float($down)) {
            throw new InvalidArgumentException("Invalid argument type");
        }
        $this->_data['down'] = $down;
    }
    
    
    
    /**
     * UTILITY FUNCTIONS
     */
     
    /**
     * Calculate backwards compass reading.
     * 
     * Result=360 will be reported as 0.
     * 
     * @return float between 0 and 360 (when unit was grad)
     * @todo implement other units than grad
     */
    public function getBackBearing()
    {
        $b = $this->getBearing();
        if (!is_float($b)) {
            throw new InvalidArgumentException("bearing '$b' not type float!");
        }
        
        if ($b >= 180.0) {
            $r = $b-180.0;
        } else {
            $r = $b+180.0;
        }

        return $r;
    }
    
    /**
     * Calculate backwards clino reading.
     * 
     * @return float between -90 and 90 (when unit was grad)
     */
    public function getBackGradient()
    {
        $b = $this->getGradient();
        if (!is_float($b)) {
            throw new InvalidArgumentException("bearing '$b' not type float!");
        }
        
        $r = $b*-1;
        
        return $r;
    }
    
    /**
     * Swap direction of measurement of this shot.
     * 
     * This will swap the from and to stations and adjust bearing and gradient.
     */
    public function reverse()
    {
        // swap stations
        $f = $this->getFrom();
        $this->setFrom($this->getTo());
        $this->setTo($f);
        
        // swap bearing
        $this->setBearing($this->getBackBearing());
        
        // swap gradient
        $this->setGradient($this->getBackGradient());
    }
    
    
    
    /**
     * Resolve field name alias to normalized name.
     * 
     * @param string $alias alias name
     * @return string normalized name
     */
    public static function unaliasField($alias)
    {
        $aliases = File_Therion_Shot::$_aliases;
        return (array_key_exists($alias, $aliases))
            ? $aliases[$alias]
            : $alias;
    }
    
    /**
     * Get alias for normalized field name.
     * 
     * @param string $name alias name
     * @return string alias name
     */
    public static function aliasField($name)
    {
        $reversedAliases = array_flip(File_Therion_Shot::$_aliases);
        return (array_key_exists($name, $reversedAliases))
            ? $reversedAliases[$name]
            : $name;
    }
}

?>
