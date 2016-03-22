<?php
/**
 * Therion cave survey reader interface class.
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
 * Reader class that reads disk files (filepaths)
 *
 * @category   file
 * @package    File_Therion
 * @author     Benedikt Hallinger <beni@php.net>
 * @copyright  2016 Benedikt Hallinger
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @link       http://pear.php.net/package/File_Therion/
 */
class File_Therion_FileReader implements File_Therion_Reader
{    

    /**
     * Should the file object be resetted before fetching?
     * 
     * @var boolean
     */
    protected $clear = true;


    /**
     * Read local file and add lines to Therion file object.
     * 
     * This will be called by File_Therion->fetch() to actually perform
     * the read.
     * 
     * @param File_Therion $file  the file object to generate lines into
     * @throws File_Therion_IOException in case of IO error
     * @throws File_Therion_SyntaxException in case of parsing-to-file-obj error
     * @throws File_Therion_Exception for other errors
     */
    public function fetch(File_Therion $file)
    {
        // read filename from therion file object to guess datasource
        $filename = $file->getFilename();
        
        // Perform checks on argument
        if (!file_exists($filename)) {
            throw new File_Therion_IOException("no such file '$filename'!");
        }
        
        if (!is_readable($filename)) {
            throw new File_Therion_IOException(
                "File '".$filename."' is not readable!"
            );
        }
        
        // clean existing line buffer as we fetch 'em fresh
        if ($this->clear) {
            $file->clearLines();
        }
        
        // read out datasource and call addLine() to populate therion file object
            
        // open and read out
        $fh = fopen ($filename, 'r');
        while (!feof($fh)) {
            $data = fgets($fh);
            
            // parse Therion Line object
            $lineObj = File_Therion_Line::parse($data);
            
            // add it to the file
            $file->addLine($lineObj);
            
        }
        fclose($fh);
        
    }
    
    
    
    /**
     * Switches cleaning of file objects lines when fetching.
     * 
     * @param boolean $setting true=clear, false=leave lines alone
     */
    public function switchClearing($setting=false)
    {
        $this->clear = ($setting)? true : false;
    }
    
}

?>
