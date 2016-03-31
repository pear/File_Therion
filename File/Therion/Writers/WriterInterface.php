<?php
/**
 * Therion cave survey writer interface class.
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
 * Interface defines writer plugins.
 * 
 * Writers can be used to influence the writing of therion file data.
 * They are usually expected to write the line contents of the th file.
 * However there may be special writers that do magical things :).
 *
 * @category   file
 * @package    File_Therion_Writers
 * @author     Benedikt Hallinger <beni@php.net>
 * @copyright  2016 Benedikt Hallinger
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @link       http://pear.php.net/package/File_Therion/
 */
interface File_Therion_Writer
{
    /**
     * Write Therion data out of the line objects contained in $file.
     * 
     * This will be called by File_Therion->write() to actually perform
     * the write.
     * 
     * @param File_Therion $file  the file object to write
     * @throws File_Therion_IOException in case of IO error
     * @throws File_Therion_Exception for other errors
     */
    public function write(File_Therion $file);
    
}

?>
