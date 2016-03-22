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
 * Interface defines reader plugins for fetching data.
 * 
 * Readers can be used to influence the reading of therion file data.
 * They are usually expected to {@link addLine()} the line representation of
 * Therion file data content.
 * 
 * The reader must be capable of being invoked several times with different
 * target arguments as it will also be used when evaluating 'input' commands.
 * This means that eg. for a database reader it must be able to detect the
 * neccessary datasets from the supplied filename (via passed therion file
 * object) alone.
 *
 * It is also possible to implement readers that directly create therion data
 * model objects and generate the line data afterwards.
 *
 * @category   file
 * @package    File_Therion
 * @author     Benedikt Hallinger <beni@php.net>
 * @copyright  2016 Benedikt Hallinger
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @link       http://pear.php.net/package/File_Therion/
 */
interface File_Therion_Reader
{
    /**
     * Read datasource and add lines to Therion file object.
     * 
     * This will be called by File_Therion->fetch() to actually perform
     * the read.
     * The filename can be determined from $file->{@link getFilename()} if
     * needed.
     * 
     * The reader is expected to reset the File_Therion using clearLines().
     * Only in special circumstances should this be passed, for example to
     * federate line content from several datasources.
     * 
     * @param File_Therion $file  the file object to generate lines into
     * @throws File_Therion_IOException in case of IO error
     * @throws File_Therion_SyntaxException in case of parsing-to-file-obj error
     * @throws File_Therion_Exception for other errors
     */
    public function fetch(File_Therion $file);
    
}

?>
