<?php
/**
 * Therion cave survey base encoding object class.
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
 * Base class representing basic therion encoding support.
 *
 * @category   file
 * @package    File_Therion
 * @author     Benedikt Hallinger <beni@php.net>
 * @copyright  2016 Benedikt Hallinger
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @link       http://pear.php.net/package/File_Therion/
 */
class File_Therion_EncodedData
{
    /**
     * Currently supported encodings by therion.
     * 
     * You can get an up-to date list with the command
     * 'therion --print-encodings'. If there is a new encoding
     * that is not reflected here, please contact me.
     * 
     * @var array
     */
    protected $_supportedEncodings = array(
        // therion name => PHP name
        'ASCII'     => '',
        'CP1250'    => '',
        'CP1251'    => '',
        'CP1252'    => '',
        'CP1253'    => '',
        'ISO8859-1' => '',
        'ISO8859-2' => '',
        'ISO8859-5' => '',
        'ISO8859-7' => '',
        'UTF-8'     => '',
    );
    
    /**
     * Encoding of data file (case insensitive).
     * 
     * @var string
     */
    protected $_encoding = 'UTF-8';
    
    /**
     * Set encoding of input/output files.
     * 
     * This will tell what encoding to use.
     * The default assumed encoding is UTF-8.
     * 
     * Only a small subset of encoding names are supported by therion,
     * see {@link $_supportedEncodings} for a list.
     * 
     * @param string $codeset
     * @throws InvalidArgumentException when unsupported encoding is used
     */
    public function setEncoding($codeset)
    {
        if (!array_key_exists($this->_getName($codeset), $this->_supportedEncodings)) {
            throw new InvalidArgumentException(
                "Encoding '$codeset' not supported");
        }
        $this->_encoding = $codeset;
    }
    
    /**
     * Get encoding of input/output files currently active.
     * 
     * @return string encoding
     */
    public function getEncoding()
    {
        return $this->_encoding;
    }
    
    /**
     * Encode data.
     * 
     * @param string $data
     * @param string $fromEncoding
     * @param string $toEncoding
     * @return $data in toEncoding.
     * @todo currently not supported - does nothing
     */
    public function encode($data, $fromEncoding, $toEncoding)
    {
        if (!array_key_exists($this->_getName($fromEncoding), $this->_supportedEncodings)) {
            throw new InvalidArgumentException(
                "From-Encoding '$fromEncoding' not supported");
        }
        if (!array_key_exists($this->_getName($toEncoding), $this->_supportedEncodings)) {
            throw new InvalidArgumentException(
                "To-Encoding '$toEncoding' not supported");
        }
        
         throw new File_Therion_Exception(
            __METHOD__.': FEATURE NOT IMPLEMENTED');
    }
    
    
    /**
     * Get internal name of encoding name.
     * 
     * @param string $name
     * @return string
     */
    protected function _getName($name)
    {
        return strtoupper($name);
    }
    
}

?>
