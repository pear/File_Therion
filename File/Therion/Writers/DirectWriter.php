<?php
/**
 * Therion cave survey basic writer class.
 *
 * PHP version 5
 *
 * @category   file
 * @package    File_Therion_Writers
 * @author     Benedikt Hallinger <beni@php.net>
 * @copyright  2016 Benedikt Hallinger
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @link       http://pear.php.net/package/File_Therion/
 */

/**
 * The writer just dumps the line content into the configured filepath.
 * 
 * This essentially will create one therion file that equals this file:
 * Surveys, Scraps etc will be nested structures in this one big file.
 * 
 * The target file will be overwritten.
 *
 * @category   file
 * @package    File_Therion_Writers
 * @author     Benedikt Hallinger <beni@php.net>
 * @copyright  2016 Benedikt Hallinger
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @link       http://pear.php.net/package/File_Therion/
 */
class File_Therion_DirectWriter implements File_Therion_Writer
{
    /**
     * Overwrite existing files?
     * 
     * @var boolean
     */
    protected $overwrite = true;
    
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
 
        // try to open filehandle
        $filename = $file->getFilename();
        if (file_exists($filename)) {
            if (!$this->overwrite) {
                throw new File_Therion_IOException(
                    "File '$filename' exists but overwriting is prohibited!");
            }
            if (!is_writable($filename)) {
                throw new File_Therion_IOException(
                    "File '$filename' should be overwritten"
                    ." but is not writable!");
            }
            
        } else {
            if (!file_exists(dirname($filename))) {
                throw new File_Therion_IOException(
                    "File '$filename' could not be created: dir '"
                    .dirname($filename)."' does not exist!");
            }
            if (!is_writable(dirname($filename))) {
                throw new File_Therion_IOException(
                    "File '$filename' could not be created: dir '"
                    .dirname($filename)."' not writable!");
            }
        }
        $fh = fopen($filename, 'w');
        if ($fh === false) {
            throw new File_Therion_IOException(
                "Error opening writing filehandle for file '$filename'");
        }
        
         
        // go through all lines and create a writable string
        $outstring = $file->toString();
         
        // then dump that string to the datasource:
        if (!fwrite($fh, $outstring)) {
            throw new File_Therion_IOException(
                "Error writing to filehandle for file '$filename'");
        }
        
    }
    
    
    /**
     * Switch overwriting permission
     * 
     * @param boolean
     */
    public function switchOverwrite($yesno)
    {
        $this->overwrite = ($yesno == true);
    }
}

?>
