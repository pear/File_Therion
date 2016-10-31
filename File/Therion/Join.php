<?php
/**
 * Therion cave join object class.
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
 * Class representing a join definition object.
 *
 * Joins are used to connect scraps or point/lines in scraps.
 * 
 * @category   file
 * @package    File_Therion
 * @author     Benedikt Hallinger <beni@php.net>
 * @copyright  2016 Benedikt Hallinger
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @link       http://pear.php.net/package/File_Therion/
 */
class File_Therion_Join
    extends File_Therion_BasicObject
    implements Countable
{
    
    /**
     * Object options (title, ...).
     * 
     * @var array assoc array
     */
    protected $_options = array(
        'smooth' => "",
        'count'  => 0
    );
    
    
    /**
     * Join arguments.
     * 
     * Array elements may be the following:
     * - array(Scrap):          Join whole scraps
     * - array(ScrapPoint):     scrap point object
     * - array(ScrapLine):      scrap line object
     * - array(ScrapLinePoint): line with marked point (eg 'end')
     * 
     * @var array  
     */
    protected $_joins = array();
    
    
    /**
     * Create a new therion Join object.
     * 
     * After creation of the join command you can call {@link addArgument()}
     * to add join arguments.
     *
     * @param array $args Objects to join
     * @param array $options key=>value pairs of options to set
     */
    public function __construct($args=array(), $options = array())
    {
        $this->setOption($options);
        $this->addArgument($args);
    }
    
    
    /**
     * Parses given Therion_Line-objects into internal data structures.
     * 
     * Note that this will generate fresh subobjects to reflect the named IDs.
     * 
     * @param File_Therion_Line $line line forming this object
     * @return File_Therion_Join Join object
     * @throws InvalidArgumentException
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
        $opts   = $line->extractOptions();
        if (array_key_exists('count', $opts)) {
            // explicit type conversion from parsed string
            $opts['count'][0] = intval($opts['count'][0]);
        }
        
        $cmd = array_shift($flData);
        if ($cmd !== "join") {
            throw new File_Therion_SyntaxException(
                "parsing join expects 'join' command as first data element, '"
                .$cmd."' given");
        }
        if (count($flData) < 1) {
            throw new File_Therion_SyntaxException(
                "join command expects at least two arguments, "
                .count($flData)." given");
        }
        
        // craft new Join object
        $joinObj = new File_Therion_Join(array(), $opts);
        
        // retrieve non-option lines and add them as join arguments
        foreach ($flData as $ja) {
            $m = array();
            if (preg_match('/(.+?):(.+)/', $ja, $m)) {
                // <line>:<mark> syntax
                $line = new File_Therion_ScrapLine('wall');
                $line->setOption('id', $m[1]);
                $point = $line->addPoint();
                $point->setMark($m[2]);
                $joinObj->addArgument($point);
                
            } else {
                // we cannot distinguish scrap-ids from line-ids;
                // so we need to guess the type...
                // 
                // this is obviously wrong in some cases, however should
                // only be a problem when dealing with parsed data AND when
                // objects of the join are retrieved. Those objects should also
                // be accessible from outside user code already.
                // Outside parsing code may circumvent this (eg that needs to be
                // addressed in parse() of Survey/Scrap class)
                //
                // We simply guess Line here because of wider compatibility.
                // (when writing join args later this is not a problem)
                $line = new File_Therion_ScrapLine('wall');
                $line->setOption('id', $ja);
                $joinObj->addArgument($line);
            }
        }
        
        return $joinObj;
        
    }
    
    /**
     * Add a join argument.
     * 
     * Join arguments may be one of the following object instances:
     * - File_Therion_Scrap:             Join whole scraps
     * - File_Therion_ScrapPoint object: Scrap point object
     * - File_Therion_ScrapLine:         scrap line object
     * - File_Therion_ScrapLinePoint:    Line with marked point (eg 'end')
     * 
     * @param array|object Join argument (scrap, Line, etc) or array of objects
     * @throws InvalidArgumentException when incompatible object is added.
     */
    public function addArgument($arg)
    {
        // array mode: recall on each element
        if (is_array($arg)) {
            foreach ($arg as $a) {
                $this->addArgument($a);
            }
            return;
        }
        
        $supported = array('File_Therion_Scrap', 'File_Therion_ScrapPoint',
            'File_Therion_ScrapLine', 'File_Therion_ScrapLinePoint');
        if (!in_array(get_class($arg), $supported)) {
            throw new InvalidArgumentException(
                "Invalid join argument type: '".get_class($arg)."'");
        }
        
        if (count($this->_joins) > 0
            && gettype($this->_joins[0]) == 'File_Therion_Scrap'
            && gettype($arg) != 'File_Therion_Scrap') {
                throw new InvalidArgumentException(
                    'Invalid join argument type: already in scrap join mode, '
                    .'expecting more scraps as join arg but '
                    .gettype($arg).' given');
        }
        
        if (count($this->_joins) == 2
            && gettype($this->_joins[0]) == 'File_Therion_Scrap') {
                throw new InvalidArgumentException(
                'Join already has two scrap object arguments, max 2 allowed!');
        }
        
        $this->_joins[] = $arg;
    }
    
    /**
     * Return therion compatible string of this join definition.
     * 
     * @return string eg "join lineA lineB -smooth on"
     * @throws File_Therion_SyntaxException in case join syntax is invalid
     */
    public function toString()
    {
        if (count($this) < 2) {
            throw new File_Therion_SyntaxException(
                "join command expects at least two arguments, "
                .count($flData)." given");
        }
        
        $rv = "join";
        // add join args
        foreach ($this->_joins as $j) {
            switch (get_class($j)) {
                case 'File_Therion_Scrap':
                    $rv .= " ".$j->getName();
                break;
                
                case 'File_Therion_ScrapPoint':
                case 'File_Therion_ScrapLine':
                    $rv .= " ".$j->getOption('id');
                break;

                case 'File_Therion_ScrapLinePoint':
                    $sl    = $j->getLine();
                    $sl_id = $sl->getOption('id');
                    $lp_id = $j->getMark();
                    $rv .= " ".$sl_id.":".$lp_id;
                break;
            }
        }
        
        // add options if given
        if ($this->_options['smooth'] && $this->_options['smooth'] != "") {
            $rv .= " -smooth ".$this->_options['smooth'];
        }
        if ($this->_options['count'] > 0) {
            $rv .= " -count ".$this->_options['count'];
        }
        
        return $rv;
    }
    
    /**
     * Magic __toString() method calls toString().
     */
    public function __toString()
    {
        return $this->toString();
    }
    
    /**
     * Return Line representation of this command.
     * 
     * @return {@link File_Therion_Line} object
     */
    public function toLines()
    {
        return new File_Therion_Line($this->toString());
    }
    
    /**
     * Return joined objects.
     * 
     * @return array
     */
    public function getArguments()
    {
        return $this->_joins;
    }
    
    /**
     * Count arguments of this join (SPL Countable).
     *
     * @return int number of argument objects
     */
    public function count()
    {
        return count($this->_joins);
    }
    
}

?>