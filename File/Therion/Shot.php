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
     * Units definition for normalized fields.
     * 
     * @var array  
     */
    protected $_units = array(
        'length'    => 'meters',
        'bearing'   => 'degrees',
        'gradient'  => 'degrees',
        'left'      => 'meters',
        'right'     => 'meters',
        'up'        => 'meters',
        'down'      => 'meters'
    );
    
    /**
     * Basic normalized data elements.
     * 
     * @var array  
     */
    protected $_data = array(
        'from'      => null, // Station
        'to'        => null, // Station
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
     * @param array $data  datafields to parse
     * @param array $order therion names of datafields in correct order
     * @param array $units associative unit settings (type=>unit)
     * @return File_Therion_Shot shot object
     * @throws File_Therion_SyntaxException
     * @throws InvalidArgumentException
     * @todo implement more fields (currently just basic normal data fields)
     */
    public static function parse($data, $order, $units=array())
    {
        if (!$units) $units = array();
        
        // craft basic shot
        $shot = new File_Therion_Shot();
        $shot->setOrder($order);  // will throw exception
        foreach ($units as $t => $u) {
            $shot->setUnit($t, $u);  // will throw exception
        }
        
        // use order (with normalized names) to parse value into correct field
        $lastParsedOrder = null;
        foreach ($shot->getOrder(true) as $o) {
            $lastParsedOrder = $o; // just for the record
            
            $value = array_shift($data); // get next corresponding value
            
            if (is_null($value)) {
                // no such data left:
                // The use case coming to mind is missing LRUD data.
                $value = "-"; // survex manual says so!
            }

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
                
                // TODO: Also know that survex supports explicitely NEWLINE...
                
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
     * Get a shot flag.
     * 
     * Note that the shot is flagged implicitely as splay when one of from- 
     * or to-stations name is a dot or dash ('.', '-').
     * 
     * @param string  $flag  name of the flag.
     * @return boolean
     * @throws InvalidArgumentException
     */
    public function getFlag($flag)
    {
        if ($flag == "approx") {
            // expand alias
            $flag = "approximate";
        }
        if (array_key_exists($flag, $this->_flags)) {
            
            // return splay flag true, when station name indicates splay
            if ($flag == "splay" && $this->hasSplayStation()) {
                return true;
                
            } else {
                // other cases: other flag or no splay station
                // return flag value
                return $this->_flags[$flag];
            }
           
        } else {
            throw new InvalidArgumentException(
                "Invalid flag $flag; flag not valid for shot");
        }
    }
    
    /**
     * Get all active shot flags.
     * 
     * This returns an associative array of all flags.
     * 
     * @return array
     */
    public function getAllFlags()
    {
        // build return array containing result of individual getFlag() result.
        // (proper handling of splay flag due to name)
        $r = array();
        foreach (array_keys($this->_flags) as $f) {
            $r[$f] = $this->getFlag($f);
        }
        return $r;
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
     * Set unit for measurements of this shot.
     * 
     * 
     * @param string $type Measurement type ('clino', 'bearing', ...)
     * @param string $unit Unit: deg, degree[s]; grad[s], ...
     * @throws InvalidArgumentException
     * @todo support other units than degree
     * @todo unit checking
     */
    public function setUnit($type, $unit)
    {
        $ntype = File_Therion_Shot::unaliasField($type);
        if (!array_key_exists($ntype, $this->_units)) {
            throw new InvalidArgumentException(
                "Unsupported unit type '$type'" );
        }
        
        // normalize unit names
        $unit = preg_replace('/deg|degrees?/', 'degrees', $unit);
        $unit = preg_replace('/grads?/', 'grads', $unit);
        // todo: more units!
        // length units supported: meter[s], centimeter[s], inch[es], feet[s], yard[s] (also m,
        // cm, in, ft, yd). Angle units supported: degree[s], minute[s] (also deg, min), grad[s],
        // mil[s], percent[age] (clino only). A degree value may be entered in decimal notation
        // (x.y) or in a special notation for degrees, minutes and seconds (deg[:min[:sec]]).
        
        
        $this->_units[$ntype] = $unit;
    }
    
    /**
     * Get current unit for measurement.
     * 
     * @param string $type Measurement type ('clino', 'bearing', ...) or 'all'
     * @return string|array Unit: degrees; grads, ...; array when $type='all'
     */
    public function getUnit($type)
    {
        if ($type == 'all') {
            return $this->_units;
        } else {
            $ntype = File_Therion_Shot::unaliasField($type);
            if (!array_key_exists($ntype, $this->_units)) {
                throw new InvalidArgumentException(
                    "Unsupported unit type '$type'" );
            }
            return $this->_units[$ntype];
        }
    }
      
    
    
    
    /**
     * GET DATA
     */
     
     
    /**
     * Get from (source) station name.
     * 
     * @return File_Therion_Station station object
     */
    public function getFrom()
    {
        return $this->_data['from'];
    }
    
    /**
     * Get to (targeted) station name.
     * 
     * @return File_Therion_Station station object
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
     * @return int between 0 and 360 (when unit is degrees)
     */
    public function getBearing()
    {
        return $this->_data['bearing'];
    }
    
    /**
     * Get shot gradient.
     * 
     * @return float between -90 to 90 (when unit is degrees)
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
     * If the passed argument is string, a new station object will be created.
     * 
     * @param string|File_Therion_Station $station
     */
    public function setFrom($station)
    {
        if (is_string($station)) {
            $station = new File_Therion_Station($station);
        }

        if (!is_a($station, 'File_Therion_Station')) {
            throw new InvalidArgumentException("Invalid station argument type");
        }
        $this->_data['from'] = $station;
    }
    
    /**
     * Set to (targeted) station.
     * 
     * When station name is "-" or ".", then the splay flag is set implicitely.
     * If the passed argument is string, a new station object will be created.
     * 
     * @param string|File_Therion_Station $station
     */
    public function setTo($station)
    {
        if (is_string($station)) {
            $station = new File_Therion_Station($station);
        }

        if (!is_a($station, 'File_Therion_Station')) {
            throw new InvalidArgumentException("Invalid station argument type");
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
        if ($length !== "-") {
            $length = (is_string($length)||is_int($length))? floatval($length) : $length;
            
            if (!is_float($length)) {
                throw new InvalidArgumentException("Invalid argument type");
            }
        }
        $this->_data['length'] = $length;
    }
    
    /**
     * Set shot compass bearing.
     * 
     * @param float $bearing between 0 and 360 (when unit was grad)
     * @throws InvalidArgumentException
     * @todo support other units than degrees and grad
     */
    public function setBearing($bearing)
    {
        if ($bearing !== "-") {
            // convert to explicit type
            $bearing = (is_string($bearing)||is_int($bearing))? floatval($bearing) : $bearing;
            
            if (!is_float($bearing)) {
                throw new InvalidArgumentException("Invalid argument type");
            }
            
            // basic sanity checks and adjustments
            if ($this->getUnit('bearing') == 'degrees'
                && ($bearing <0 || $bearing > 360)) {
                throw new InvalidArgumentException(
                    "bearing out of range: $bearing (0->360 degrees)"
                );
                $bearing = ($bearing==360)? 0.0 : $bearing; // treat 360 as float(0)
            }
            if ($this->getUnit('bearing') == 'grads'
                && ($bearing <0 || $bearing > 400)) {
                throw new InvalidArgumentException(
                    "bearing out of range: $bearing (0->400 grads)"
                );
                
                $bearing = ($bearing==400)? 0.0 : $bearing; // treat 400 as float(0)
            }            
            // TODO: support other units too
        }
        
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
        if ($gradient !== "-") {
            // convert to explicit type
            $gradient = (is_string($gradient)||is_int($gradient))? floatval($gradient) : $gradient;
            
            if (!is_float($gradient)) {
                throw new InvalidArgumentException(
                    "Invalid argument type (".gettype($gradient).") gradient=$gradient");
            }
            if ($this->getUnit('gradient') == 'degrees'
                && ($gradient <-90 || $gradient > 90)) {
                
                throw new InvalidArgumentException(
                    "gradient out of range: $gradient (-90->+90 degrees)"
                );
            }
            if ($this->getUnit('gradient') == 'grad'
                && ($gradient <-100 || $gradient > 100)) {
                
                throw new InvalidArgumentException(
                    "gradient out of range: $gradient (-90->+90 grads)"
                );
            }
            // TODO: support other units too
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
        if ($left !== "-") {
            // convert to explicit type
            $left = (is_string($left)||is_int($left))? floatval($left) : $left;
            
            if (!is_float($left)) {
                throw new InvalidArgumentException("Invalid argument type");
            }
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
        if ($right !== "-") {
            // convert to explicit type
            $right = (is_string($right)||is_int($right))? floatval($right) : $right;
            
            if (!is_float($right)) {
                throw new InvalidArgumentException("Invalid argument type");
            }
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
        if ($up !== "-") {
            // convert to explicit type
            $up = (is_string($up)||is_int($up))? floatval($up) : $up;
            
            if (!is_float($up)) {
                throw new InvalidArgumentException("Invalid argument type");
            }
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
        if ($down !== "-") {
            // convert to explicit type
            $down = (is_string($down)||is_int($down))? floatval($down) : $down;
            
            if (!is_float($down)) {
                throw new InvalidArgumentException("Invalid argument type");
            }
        }
        
        $this->_data['down'] = $down;
    }
    
    
    /**
     * Return formatted datafields as Therion Line in current order.
     * 
     * @return File_Therion_Line containing data items
     */
    public function toLines()
    {
        $strItems = array();
        foreach ($this->getOrderedData() as $od) {
            if (is_a($od, 'File_Therion_Station')) {
                // resolve station objects to string names
                $strItems[] = File_Therion_Line::escape($od->getName());
            } else {
                $strItems[] = File_Therion_Line::escape($od);
            }
        }
        
        // todo: maybe use better formatting for nicer table output
        $data_str = implode("\t", $strItems);
        return new File_Therion_Line($data_str, "", "\t\t");
    }

    
    /**
     * Return formatted datadefinition as Therion Line.
     * 
     * @return File_Therion_Line containing "data normal x y z ..."
     */
    public function toLinesDataDef()
    {
        // todo: maybe use better formatting for nicer table output
        $order_str = implode("\t", $this->getOrder());
        return new File_Therion_Line(
            "data\t".$this->getStyle()."\t".$order_str
        );
    }
    
    /**
     * Return formatted units definitions for data defintion as Therion Line(s).
     * 
     * In default mode, only units deviating from the therion default are
     * reported. Currently that is meters for lengths and degrees for angles.
     * If no default unit was changed, an empty array is returned.
     * Otherwise (or if $all was selected) Lines will be generated.
     * 
     * @param boolean $all If true, return also default units (that is: all)
     * @return array with File_Therion_Line objects (or empty)
     * @todo support unit factor: string like [factor]
     */
    public function toLinesUnitsDef($all=false)
    {
        $unitsStrings = array(); // unit to instruments
        // walk each unit setting and add in case of deviation
        foreach ($this->getOrder() as $inst) {
            // skip if station: from, to
            if (in_array($inst, array('from', 'to'))) continue;
            
            $unit = $this->getUnit($inst);
            if ($all || ($unit != 'meters' && $unit != 'degrees')) {
                if (!array_key_exists($unit, $unitsStrings)) {
                    $unitsStrings[$unit] = array($inst);
                } else {
                    $unitsStrings[$unit][] = $inst;
                }
            }
        }
        
        // build distinct units lines as neccessary
        $retLines = array();
        foreach ($unitsStrings as $u => $i) {
            $retLines[] =
                new File_Therion_Line("units ".implode(" ", $i)." ".$u);
        }
        return $retLines;
    }
    
    
    /**
     * UTILITY FUNCTIONS
     */
     
    /**
     * Calculate backwards compass reading.
     * 
     * Result=360 will be reported as 0 (with degrees).
     * 
     * @return float between 0 and 360 (when unit is degrees)
     * @throws InvalidArgumentException
     * @todo implement other units than grad and degree
     */
    public function getBackBearing()
    {
        $b = $this->getBearing();
        if (!is_float($b)) {
            throw new InvalidArgumentException("bearing '$b' not type float!");
        }
        
        $unit = $this->getUnit('bearing');
        if ($unit == 'degrees' || $unit == 'grads') {
            $max = ($unit=='degrees')? 360.0 : 400.0;
            if ($b >= $max/2) {
                $r = $b-$max/2;
            } else {
                $r = $b+$max/2;
            }
            if ($r >= $max) $r = $max; // cap limit, eg. 360 becomes 0
            
        } else {
            // todo: implement more units
            throw new File_Therion_Exception("Unit '$unit' not implmented yet!");
        }

        return $r;
    }
    
    /**
     * Calculate backwards clino reading.
     * 
     * @return float between -90 and 90 (when unit is degrees)
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
    
    /**
     * Returns array with data fields ordered by current order.
     * 
     * @return array with data elements
     */
    public function getOrderedData()
    {
        $r = array();
        foreach ($this->getOrder(true) as $o) {
            $rv  = $this->_data[$o]; // resolve value
            $r[] = $rv; // append
        }
        return $r;
    }
    
    /**
     * Convert value between units.
     * 
     * @param float  $value
     * @param string $from Unit to convert from
     * @param string $to   Unit to convert to
     * @return float converted value
     * @see {@link setUnit()} and {@link $_units} for possible units
     * @throws InvalidArgumentException
     * @todo implement me please (grads/degrees currently raw implemented)
     */
    public static function convertValue($value, $from, $to)
    {
        // todo: support aliases
        
        // factors define possible conversions
        $factors['degrees']['grads'] = 10/9;
        $factors['grads']['degrees'] = 9/10;
        
        if (!array_key_exists($from, $factors)) {
            throw new InvalidArgumentException(
                "unsupported conversion: $from->$to ($from unknown)");
        }
        if (!array_key_exists($to, $factors[$from])) {
            throw new InvalidArgumentException(
                "unsupported conversion: $from->$to ($to unknown)");
        }
        
        return $value * $factors[$from][$to];
    }
    
    /**
     * Tell if this shot is a splay shot due to naming conventions.
     * 
     * If the from-station or to-station are named with a dot or dash,
     * then splay flag is assumed.
     * 
     * @return boolean
     */
    public function hasSplayStation()
    {
        foreach (array($this->getFrom(), $this->getTo()) as $s) {
            if (!is_null($s)
                && ($s->getName() == '.' || $s->getName() == '-')) {
                return true;
            }
        }
        
        return false;
    }

}

?>