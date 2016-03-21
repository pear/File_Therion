<?php
/**
 * Therion cave survey basic writer class.
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
 * The writer just dumps the line content into the configured URL.
 *
 * @category   file
 * @package    File_Therion
 * @author     Benedikt Hallinger <beni@php.net>
 * @copyright  2016 Benedikt Hallinger
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @link       http://pear.php.net/package/File_Therion/
 */
class File_Therion_DirectWriter implements File_Therion_Writer
{
    /**
     * Write a Therion file structure.
     * 
     * This will be called by File_Therion->write() to actually perform
     * the write.
     * 
     * @param File_Therion $file  the file object to write
     * @throws File_Therion_IOException in case of IO error
     * @throws File_Therion_Exception for other errors
     */
    public function write(File_Therion $file) {
        // TODO: IMPLEMENT ME
        throw new File_Therion_Exception(__CLASS__." not implemented yet");
        
        
        // go through all $_lines buffer objects and create writable string;
        $stringContent = $this->toString();
        
        // convert stringContent from internal utf8 data to tgt encoding
        // todo implement me
         
        // open filehandle in case its not already open
        if (!is_resource($this->_url)) {
            $fh = fopen ($this->_url, 'w');
        } else {
            $fh = $this->_url;
        }
        if (!is_writable($fh)) {
           throw new File_Therion_IOException("'".$this->_url."' is not writable!");
        }
         
        // then dump that string to the datasource:
        if (!fwrite($fh, $stringContent)) {
            throw new File_Therion_IOException("error writing to '".$this->_url."' !");
        }
        
    }
    
}

?>
