<?php
/**
 * Therion cave scrap line object class.
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
 * Class representing a scrap line definition object.
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
class File_Therion_ScrapLine
    extends File_Therion_BasicObject
    implements Countable
{
    
    /**
     * Object options (id, ...).
     * 
     * @var array assoc array
     */
    protected $_options = array(
        'id'          => "",
        'subtype'     => "", // main subtype of line
        
        'close'       => "",
        'outline'     => "",
        'orientation' => "",
        'reverse'     => "",
        'size'        => 0,
        'r-size'      => 0,
        'l-size'      => 0,
        'place'       => "", // <bottom/default/top>
        'clip'        => "", // <on/off>
        'visibility'  => "",
        'context'     => array(), // <point/line/area> <symbol-type>
        
        'altitude'    => "", // only wall type
        'border'      => "", // only slope type: <on/off>
        'direction'   => "", // only direction type: <begin/end/both/none/point>
        'gradient'    => "", // only contour type: <none/center/point>
        'head'        => "", // only arrow type: <begin/end/both/none>
        'text'        => "", // only label type
        'height'      => "", // only pit or wall:pit types
    );
    
    
    /**
     * Basic data elements.
     * 
     * @var array  
     */
    protected $_data = array(
        'type' => "",  // main type of line
    );
    
    /**
     * Points of this line.
     * 
     * Each array index holds a single point.
     * Point order matters.
     * The array is defined as following:
     * - TODO: specify; most important: order and command like options for this point
     *
     * @var array of {@link File_Therion_ScrapLinePoint} objects (order matters)
     */
    protected $_points = array();
    
    
    /**
     * Create a new therion ScrapLine object.
     *
     * @param string $type type of the line
     * @todo Restrict naming convention, not all characters are allowed!
     */
    public function __construct($type, $options = array())
    {
        $this->setData('type', $type);
        $this->setOption($options);
    }
    
    
    /**
     * Parses given Therion_Line-objects into internal data structures.
     * 
     * @param array $lines File_Therion_Line objects forming this object
     * @return File_Therion_ScrapLine ScrapLine object
     * @throws InvalidArgumentException
     * @todo implement me
     */
    public static function parse($lines)
    {
        if (!is_array($lines)) {
            throw new InvalidArgumentException(
                'parse(): Invalid $lines argument (expected array, seen:'
                .gettype($lines).')'
            );
        }
        
        $scrapline = null; // constructed object
        
        // get first line and construct hull object
        $firstLine = array_shift($lines);
        if (is_a($firstLine, 'File_Therion_Line')) {
            $flData = $firstLine->getDatafields();
            if (strtolower($flData[0]) == "line") {
                $scrapline = new File_Therion_ScrapLine(
                    $flData[1], // type, mandatory
                    $firstLine->extractOptions()
                );
            } else {
                throw new File_Therion_SyntaxException(
                    "First scrap-line line is expected to contain line definition"
                );
            }
                
        } else {
            throw new InvalidArgumentException(
                "Invalid $line argument @1 passed type='"
                .gettype($firstLine)."'");
        }
        
        // Pop last last line and control that it was the end tag
        $lastLine  = array_pop($lines);
        if (is_a($lastLine, 'File_Therion_Line')) {
            $flData = $firstLine->getDatafields();
            if (!strtolower($flData[0]) == "endline") {
                throw new File_Therion_SyntaxException(
                    "Last scrap-line line is expected to contain endline definition"
                );
            }
            
        } else {
            throw new InvalidArgumentException(
                "Invalid $line argument @last passed type='"
                .gettype($lastLine)."'");
        }
        
        
        /*
         * Parsing contents
         */
        //
        // todo: implement parsing code
        //       Add points to this line and apply correct flags/subdata etc
        
        return $scrapline;
        
    }
    
    /**
     * Count points of this line (SPL Countable).
     *
     * @return int number of points
     */
    public function count()
    {
        return count($this->_points);
    }
}




/**
 * Class describing a ScrapLine Point (a bezier courve point).
 * 
 * Scrap line objects consist of at least one point objects.
 * These point objects can have various further information attached.
 * They form a point of a bezier-courve and have handles.
 * 
 * @category   file
 * @package    File_Therion
 * @author     Benedikt Hallinger <beni@php.net>
 * @copyright  2016 Benedikt Hallinger
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @link       http://pear.php.net/package/File_Therion/
 * @todo implement me, especially getters to get data out
 */
class File_Therion_ScrapLinePoint
{
    
    /**
     * Data of this point.
     */
    protected $_data = array(
        'coords'   => array(0, 0),
        'bezierLC' => null,  // left bezier control point (array(x,y) when set)
        'bezierRC' => null,  // right bezier control point (array(x,y) when set)
        'mark'     => "",
        'smooth'   => "auto",  // on/off/auto
    );
    
    /**
     * Create a new therion ScrapLinePoint object.
     *
     * @param float  $x X-position in scrap pane
     * @param float  $y Y-position in scrap pane
     * @param string $mark alternative id of this point
     * @todo implement me
     */
    public function __construct(float $x, float $y, $mark = "")
    {
        $this->setX($x);
        $this->setY($y);
        $this->setMark($mark);
    }
    
    /**
     * Set Points X coordinate on the scrap.
     * 
     * @param float
     */
    public function setX(float $x)
    {
        $this->_data['coords'][0] = $x;
    }
    
    /**
     * Set Points Y coordinate on the scrap.
     * 
     * @param float
     */
    public function setY(float $y)
    {
        $this->_data['coords'][1] = $y;
    }
    
    /**
     * Set mark (alternative ID) of this point
     * 
     * @param string
     */
    public function setMark(string $arg)
    {
        $this->_data['mark'] = $arg;
    }
    
    /**
     * Remove mark (alternative ID) of this point
     */
    public function clearMark()
    {
        $this->_data['mark'] = $arg;
    }
    
    /**
     * Set smoothness of this point
     * 
     * @param string "on", "off" or "auto" (default)
     */
    public function setSmoothness(string $arg)
    {
        $this->_data['smooth'] = $arg;
    }
    
    /**
     * Set left bezier-courve handle position.
     *
     * @param float $x X-position in scrap pane
     * @param float $y Y-position in scrap pane
     */
    public function setLeftBezierHandle(float $x, float $y)
    {
        $this->_data['bezierLC'] = array($x, $y);
    }
    
    /**
     * Set right bezier-courve handle position.
     *
     * @param float $x X-position in scrap pane
     * @param float $y Y-position in scrap pane
     */
    public function setRightBezierHandle(float $x, float $y)
    {
        $this->_data['bezierRC'] = array($x, $y);
    }
    
    /**
     * Remove left bezier-courve handle.
     */
    public function clearLeftBezierHandle()
    {
        $this->_data['bezierLC'] = null;
    }
    
    /**
     * Remove left bezier-courve handle.
     */
    public function clearRightBezierHandle()
    {
        $this->_data['bezierRC'] = null;
    }
    
    /**
     * Generate therion compatible string representation of this point.
     * 
     * @return string
     */
    public function toString()
    {
        // get bezier handles; when not set, they correspond to the point coords
        // If both points are unset, we only need to report the point coords.
        $pcoords = $this->_data['coords'];
        $bezL = $this->_data['bezierLC'];
        $bezR = $this->_data['bezierLC'];
        if (!is_null($bezL) || !is_null($bezR)) {
            // fill in point coords if neccessary (bezier handle is null)
            $bezL = (is_null($bezL))? $pcoords : $bezL;
            $bezR = (is_null($bezR))? $pcoords : $bezR;
            
            return implode(" ", array_merge($bezL, $bezR, $pcoords));
            
        } else {
            // just return point coords as there are no bezier handles
            return implode(" ", $pcoords);
        }
        
    }
}
?>
