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
 * Class representing a therion person object.
 *
 * @category   file
 * @package    File_Therion_DataTypes
 * @author     Benedikt Hallinger <beni@php.net>
 * @copyright  2016 Benedikt Hallinger
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @link       http://pear.php.net/package/File_Therion/
 */
class File_Therion_Person
    implements File_Therion_DataType
{
    
    protected $surname   = "";
    protected $givenName = "";
    
    
    /**
     * Create a new therion person object.
     * 
     * You may supplay givenName and surname; if there are several, just
     * separate them with whitespace.
     *
     * @param $givenName First name of person.
     * @param $surname   Last name of person.
     */
    public function __construct($givenName = "", $surname = "")
    {
        $this->setGivenname($givenName);
        $this->setSurname($surname);
    }
    
    
    /**
     * Get string representation
     *
     * @return Therion compliant String of this type
     */
    public function toString()
    {
        // the thbook says:
        // "person: a person's first name and surname separated by whitespace
        // characters. Use '/' to separate first name and surname if there
        // are more names.
        $gn = $this->givenName;
        $sn = $this->surname;
        if (stristr($gn, ' ') || stristr($sn, ' ')) {
            $gn = implode(' ',
                array_map(
                    'File_Therion_Line::escape',
                    explode(' ', $gn))
                );
            $sn = implode(' ',
                array_map(
                    'File_Therion_Line::escape',
                    explode(' ', $sn))
                );
            
            return '"'.$gn.'/'.$sn.'"';
            
        } else {
            if       ($gn && $sn) {
                return File_Therion_Line::escape("$gn $sn");
                
            } elseif ($gn && !$sn) {
                return File_Therion_Line::escape("$gn/");
                
            } elseif (!$gn && $sn) {
                // unsure if this is correct syntax
                return File_Therion_Line::escape("$sn");
                
            } else {
                return '""';
            }            
        }
    }


    /**
     * Parse string content into this datatype
     * 
     * @param $string data to parse
     * @return mixed crafted object
     * @todo unsure if single name should be interpreted as last-name
     */
    public static function parse($string)
    {
        $string = File_Therion_Line::unescape($string);
        if ($string == "") {
            $gn = "";
            $sn = "";
            
        } else {
            $m = array();
            if (preg_match('!^(.+)/(.+)$!', $string, $m)) {
                // eg. "Benedikt ""Beni"" / Hallinger"
                $gn = File_Therion_Line::unescape($m[1]);
                $sn = File_Therion_Line::unescape($m[2]);
                
            } elseif (preg_match('!^(.+)\s(.+)$!', $string, $m)) {
                // eg. "Benedikt Hallinger"
                $gn = File_Therion_Line::unescape($m[1]);
                $sn = File_Therion_Line::unescape($m[2]);
                
            } elseif (preg_match('!^/(.+)$!', $string, $m)) {
                // eg. "/Hallinger"
                $gn = "";
                $sn = File_Therion_Line::unescape($m[1]);
                
                } elseif (preg_match('!^(.+)/$!', $string, $m)) {
                // eg. "Beni/"
                $gn = File_Therion_Line::unescape($m[1]);
                $sn = "";
                
            } elseif (preg_match('!^(.+)$!', $string, $m)) {
                // eg. "Hallinger"
                // treat this as last name
                // @TODO: unsure if this is correct.
                $gn = "";
                $sn = File_Therion_Line::unescape($m[1]);
                
            } else {
                throw new File_Therion_SyntaxException(
                  "Invalid Person dataType format: '$string'"
                );
            }
        }
        
       return new File_Therion_Person($gn, $sn);
    }
    
    
    /**
     * Sets givenname of person.
     * 
     * @param string $name
     * @todo proper checks for type
     */
    public function setGivenname($name)
    {
        $this->givenName = ltrim($name,'/');
    }
    
    /**
     * Sets surname of person.
     * 
     * @param string $name
     * @todo proper checks for type
     */
    public function setSurname($name)
    {
        $this->surname = ltrim($name, '/');
    }
    

    /**
     * Returns givenName of person.
     * 
     * @return string
     */
    public function getGivenname()
    {
        return $this->givenName;
    }
    
    /**
     * Returns lastname of person.
     * 
     * @return string
     */
    public function getSurname()
    {
        return $this->surname;
    }
        
    
    
}

?>
