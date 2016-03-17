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
        'length'    => 0.0,
        'bearing'   => 0.0,
        'gradient'  => 0.0,
        'left'      => 0.0,
        'right'     => 0.0,
        'up'        => 0.0,
        'down'      => 0.0,
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
     * @todo implement me
     */
    public static function parse(array $data, array $order)
    {
        // inspect $order: count "active" fields
        
        // TODO: Implement me please
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
}

?>
