<?php
/**
 * Therion cave scrap point object class.
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
 * Class representing a scrap point definition object.
 *
 * This is a vector graphic element that is used to form a renderable cavemap.
 * 
 * @category   file
 * @package    File_Therion
 * @author     Benedikt Hallinger <beni@php.net>
 * @copyright  2016 Benedikt Hallinger
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @link       http://pear.php.net/package/File_Therion/
 */
class File_Therion_ScrapPoint
    extends File_Therion_BasicObject
{
    
    /**
     * Object options (title, ...).
     * 
     * @var array assoc array
     */
    protected $_options = array(
        'id'          => "",
        'name'        => "", // only certain types!
        'align'       => "", // only certain types!
        'orientation' => "", // only certain types!
        // todo: there are alot more.
    );
    
    
    /**
     * Basic data elements.
     * 
     * @var array  
     */
    protected $_data = array(
        'coords' => array(0.0, 0.0), // x, y
        'type'   => ""
    );
    
    
    /**
     * Create a new therion ScrapPoint object.
     *
     * @param string $id Name/ID of the drawing object
     * @todo Restrict naming convention, not all characters are allowed!
     */
    public function __construct($x, $y, $type, $options = array())
    {
        $this->setX($x);
        $this->setY($y);
        $this->setType($type);
        
        //TODO: parsing options removed for now; optionlist is vastly incomplete
        //$this->setOption($options);  
    }
    
    
    /**
     * Parses given Therion_Line-objects into internal data structures.
     * 
     * @param array $line File_Therion_Line object forming this object
     * @return File_Therion_ScrapPoint ScrapPoint object
     * @throws InvalidArgumentException
     * @todo implement me
     */
    public static function parse($line)
    {
        if (!is_a($line, 'File_Therion_Line')) {
            throw new InvalidArgumentException(
                'Invalid $line argument (expected File_Therion_Line, seen:'
                .gettype($line).')'
            );
        }
        
        // this is a one-line object.
        $flData = $line->extractOptions(true); // get non-options (=data)
        $cmd = array_shift($flData);
        if ($cmd !== "point") {
            throw new File_Therion_SyntaxException(
                "parsing scrap-point expects 'point' command as first data"
                ." element, '".$cmd."' given");
        }
        if (count($flData) != 3) {
            throw new File_Therion_SyntaxException(
                "point command expects exactly three arguments, "
                .count($flData)." given");
        }
        
        $x    = floatval($flData[0]);
        $y    = floatval($flData[1]);
        $type = $flData[2];
        $opts = $line->extractOptions();
        
        return new File_Therion_ScrapPoint($x, $y, $type, $opts);
        
    }
    
    
    /**
     * Set X scrap coordinate value of point.
     * 
     * @param float
     */
    public function setX($x)
    {
        if (!is_float($x)) {
            throw new InvalidArgumentException(
                'Invalid scrapPoint X argument (expected float, seen:'
                .gettype($x).')');
        }
        $this->_data['coords'][0] = $x;
    }
    
    /**
     * Set Y scrap coordinate value of point.
     * 
     * @param float
     */
    public function setY($y)
    {
        if (!is_float($y)) {
            throw new InvalidArgumentException(
                'Invalid scrapPoint Y argument (expected float, seen:'
                .gettype($y).')');
        }
        $this->_data['coords'][1] = $y;
    }
    
    /**
     * Get scrap coordinates of this point.
     * 
     * @return array Index as following: 0=X, 1=Y
     */
    public function getCoordinates()
    {
        return array($this->getX(), $this->getY());
    }
    
    /**
     * Get scrap X coordinate of this point.
     * 
     * @return float
     */
    public function getX()
    {
        return $this->_data['coords'][0];
    }
    
    /**
     * Get scrap Y coordinate of this point.
     * 
     * @return float
     */
    public function getY()
    {
        return $this->_data['coords'][1];
    }
    
    
    /**
     * Set main type of point.
     * 
     * To set the subtype, use the {@link setOption()} method with "subtype" as
     * argument.
     * 
     * @param string
     */
    public function setType($type)
    {
        $this->setData('type', $type);
    }
    
    /**
     * Get point main type.
     * 
     * To get the subtype, use the {@link getOption()} method with "subtype" as
     * argument.
     * 
     * @return string
     */
    public function getType()
    {
        return $this->getData('type');
    }
    
}

?>