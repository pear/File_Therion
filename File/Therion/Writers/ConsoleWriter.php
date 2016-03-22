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
 * The writer just prints the content to the terminal.
 *
 * @category   file
 * @package    File_Therion
 * @author     Benedikt Hallinger <beni@php.net>
 * @copyright  2016 Benedikt Hallinger
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @link       http://pear.php.net/package/File_Therion/
 */
class File_Therion_ConsoleWriter implements File_Therion_Writer
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

        // go through all $_lines buffer objects and create writable string;
        $stringContent = $file->toString();
        print __CLASS__." writing:\n";
        print $stringContent;
        print __CLASS__." done!\n";
        
    }
    
}

?>
