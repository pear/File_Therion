<?php
/**
 * Therion datatype class.
 *
 * PHP version 5
 *
 * @category   file
 * @package    File_Therion_DataTypes
 * @author     Benedikt Hallinger <beni@php.net>
 * @copyright  2016 Benedikt Hallinger
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @link       http://pear.php.net/package/File_Therion/
 */

/**
 * Class representing a therion untit object.
 * 
 * From thbook:
 * length units supported: meter[s], centimeter[s], inch[es], feet[s], yard[s]
 * (also m, cm, in, ft, yd).
 * Angle units supported: degree[s], minute[s] (also deg, min), grad[s],
 * mil[s], percent[age] (clino only). A degree value may be entered in decimal
 * notation (x.y) or in a special notation for degrees, minutes and seconds
 * (deg[:min[:sec]]).
 *
 * @category   file
 * @package    File_Therion_DataTypes
 * @author     Benedikt Hallinger <beni@php.net>
 * @copyright  2016 Benedikt Hallinger
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @link       http://pear.php.net/package/File_Therion/
 */
class File_Therion_Unit
    extends File_Therion_DataType
{
    
    /**
     * Units quantity
     * 
     * @var null|float
     */
    protected $quantity = null;
    
    /**
     * Units type
     * 
     * @var string
     */
    protected $type = "";
    
    /**
     * Typemap for internal use.
     * 
     * @var array
     */
    private static $_typemap = array(
        // length units
        'meter'  => array(
            'type'  => 'length',
            'alias' => array('meters', 'm')
        ),
        'centimeter'  => array(
            'type'  => 'length',
            'alias' => array('centimeters', 'cm')
        ),
        'inch'  => array(
            'type'  => 'length',
            'alias' => array('inches', 'in')
        ),
        'feet'  => array(
            'type'  => 'length',
            'alias' => array('feets', 'ft')
        ),
        'yard'  => array(
            'type'  => 'length',
            'alias' => array('yards', 'yd')
        ),
        
        // angle units
        'degree'  => array(
            'type'  => 'angle',
            'alias' => array('degrees', 'deg')
        ),
        'minute'  => array(
            'type'  => 'angle',
            'alias' => array('minutes', 'min')
        ),
        'grad'  => array(
            'type'  => 'angle',
            'alias' => array('grads')
        ),
        'mil'  => array(
            'type'  => 'angle',
            'alias' => array('mils')
        ),
        'percent'  => array(   /* clino only */
            'type'  => 'angle',
            'alias' => array('percentage')
        )
    );
 
    
    /**
     * Create a new therion unit instance.
     * 
     * When $quantity is NULL, it is only initialized to the type specified.
     * Use this in cases where only the unit itself is interesting.
     * {@link toString()} will return only the type in this case.
     * 
     * @param string $quantity Ammount of unit, eg. "123"
     * @param string $type     Type, eg. "meter"
     */
    public function __construct($quantity, $type)
    {
        $this->setQuantity($quantity);
        $this->setType($type);
    }
    
    
    /**
     * Get string representation
     *
     * @param boolean $normalize if TRUE, return unaliased name
     * @return Therion compliant String of this unit instance ("1.5 meter")
     */
    public function toString($normalize = false)
    {
        $r = "";
        if (!is_null($this->getQuantity())) {
            $r .= $this->getQuantity();
            $r .= " ";
        }
        
        $r .= $this->getType($normalize);
        
        return $r;
    }


    /**
     * Parse string content into this datatype.
     * 
     * @param $string data to parse
     * @return File_Therion_Unit crafted object
     */
    public static function parse($string)
    {
        $string   = trim(File_Therion_Line::unescape($string));
        $elements = preg_split("/\s+/", $string, 2);
        
        switch (count($elements)) {
            case 1:
                return new File_Therion_Unit(null, $elements[0]);
                break;
            case 2:
                return new File_Therion_Unit($elements[0], $elements[1]);
                break;
            default:
                throw new File_Therion_SyntaxException(
                    "wrong unit argument count: ".count($elements)
                );
                
        }
        
    }
    
    /**
     * Set quantity of this instance.
     * 
     * When $quantity is NULL, only unit type is considered.
     * 
     * @param null|string $quantity Ammount of unit, eg. "123"
     * @todo check snytax
     * @todo support special notation for deg/min/seconds (deg[:min[:sec]])
     */
    public function setQuantity($quantity)
    {
        
        /* TODO: check syntax
            throw new File_Therion_SyntaxException(
              "Invalid quantity: '$string'"
            );
        */
        
        $this->quantity = $quantity;
    }
    
    /**
     * Get this unit instances quantity.
     * 
     * @return float|null null in case unit was only initialized to type
     */
    public function getQuantity()
    {
        return $this->quantity;
    }
    
    /**
     * Set type of this unit instance.
     * 
     * Note that therion does not allow unit types to be uppercased
     * (e.g. "Meters" is an error).
     * 
     * If you want to unalias an manually given aliased type, use
     * <code>$unit->setType($unit->getType(true));</code>
     * 
     * @param string $type Type of unit, eg. "meters"
     * @throws File_Therion_Exception when type/alias is not known.
     */
    public function setType($type)
    {
        // delegate type checking (throws File_Therion_Exception)
        $normalized = File_Therion_Unit::unalias($type);
        
        $this->type = $type; // store provided type name
    }
    
    /**
     * Get type of this unit instance.
     * 
     * @param boolean $normalize if TRUE, return unaliased name
     * @return string type name
     */
    public function getType($normalize = false)
    {
        if ($normalize) {
            return File_Therion_Unit::unalias($this->type);
        } else {
            return $this->type;
        }
    }
    
    
    /**
     * Convert this unit into another one.
     *
     * Converts the quantity of this unit into the type specified.
     * If you want to preserve this object, make a copy beforehand!
     * 
     * @param string|File_Therion_Unit target type
     * @throws InvalidArgumentException when units are incompatible
     * @throws File_Therion_Exception when type/alias is not known.
     * @todo implement me please (grads/degrees currently raw implemented)
     */
    public function convertTo($typeP)
    {
        if (is_a($typeP, 'File_Therion_Unit')) {
            $type = $typeP->getType(true);
        } elseif(is_string($typeP)) {
            $type = $typeP;
        } else {
            throw InvalidArgumentException(
                'wrong $typeP parameter, expected string or File_Therion_Unit'
            );
        }
        
        /*
         * check class compatibility:
         * only types of the same class may be converted
         */
        // get normalized name of target+source - this also checks type
        $tgt = File_Therion_Unit::unalias($type);
        $src = $this->getType(true);
        
        // get type classes and compare
        $tgt_class  = File_Therion_Unit::getUnitClass($tgt);
        $this_class = File_Therion_Unit::getUnitClass($this->getType(true));
        if ($this_class != $tgt_class) {
            throw new InvalidArgumentException(
                "cannot convert incompatible type classes ("
                .$this->getType()."=$this_class -> "
                .$type."=$tgt_class)"
            );
        }
        
        /*
         * perform conversion and store new type
         */
        
        // factors define possible conversions
        $factors['degree']['grad'] = 10/9;
        $factors['grad']['degree'] = 9/10;
        // @todo: more to come!
        
        
        // check requested conversion against available factors
        if (!array_key_exists($src, $factors)) {
            throw new InvalidArgumentException(
                "unsupported conversion: $src->$tgt ($src unknown)");
        }
        if (!array_key_exists($tgt, $factors[$src])) {
            throw new InvalidArgumentException(
                "unsupported conversion: $src->$tgt ($tgt unknown)");
        }
        
        // perform conversion
        $convResult = $this->getQuantity() * $factors[$src][$tgt];
        
        // store result
        $this->setQuantity($convResult);
        $this->setType($type);
        
    }
    
    
    /**
     * Get unaliased name for aliased type ("m" -> "meter")
     * 
     * @return string
     * @throws File_Therion_Exception when type/alias is not known.
     */
    public static function unalias($type)
    {
        if (array_key_exists($type, File_Therion_Unit::$_typemap)) {
            // $type is already normalized
            return $type;
        }
        
        // not found so far: try to resolve alias
        foreach (File_Therion_Unit::$_typemap as $tn => $td) {
            if (in_array($type, $td['alias'])) return $tn;
        }
        
        // not successful: alias/type is unknown
        throw new File_Therion_Exception("unit name or alias '$type' not known");
    }
    
    
    /**
     * Gets class of unit type ("angle" or "length")
     * 
     * @param string $type type name
     * @return string "angle" or "length"
     */
    public static function getUnitClass($type)
    {
        // normalzie and check name
        $t = File_Therion_Unit::unalias($type);
        
        // return class
        return File_Therion_Unit::$_typemap[$t]['type'];
    }
    
}

?>