<?php
/**
 * Therion cave survey data file format main class
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
 * Package includes.
 */
require_once 'PEAR.php';
require_once 'PEAR/Exception.php';
require_once 'File/Therion/Exception.php';
require_once 'File/Therion/Line.php';
//require_once 'File/Therion/Survey.php';
//require_once 'File/Therion/Centreline.php';
//require_once 'File/Therion/Person.php';
//require_once 'File/Therion/Explo.php';
//require_once 'File/Therion/Topo.php';
//require_once 'File/Therion/Station.php';
//require_once 'File/Therion/StationFlag.php';
//require_once 'File/Therion/Shot.php';
//require_once 'File/Therion/ShotFlag.php';

/**
 * Wrapper functions to parse and write .th data to/from File_Therion objects
 *
 * Therion (http://therion.speleo.sk/) is an openSource application for managing
 * cave survey data.
 *
 * The data structure follows mostly the SQL diagram in the therion book (see
 * Chapter 'SQL export'). Surveys can be nested.
 * Todo: more long description of purpose and features
 *
 * @category   file
 * @package    File_Therion
 * @author     Benedikt Hallinger <beni@php.net>
 * @copyright  2016 Benedikt Hallinger
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @link       http://pear.php.net/package/File_Therion/
 */
class File_Therion
{

    /**
     * Parses a Therion .th data structure recursively
     *
     * The .th contains a single survey but may contain nested subsurveys.
     *
     * The file parameter may contain an already open filehandle, in this case
     * the parser reads from th current position onwards. the handle will stay
     * open after read.
     * File paths will be opened, read and closed.
     * Arrays will be treated like one file line each key.
     * String data will get parsed directly, splitted by PHP_EOL sequences (\n).
     *
     * @param  string|array|ressource $file string-data, array-of-strings, filename/url or handle
     * @return File_Therion_Survey    Survey object containing the parsed data
     * @throws PEAR_Exception         with wrapped lower level exception (InvalidArgumentException, etc)
     * @throws File_Therion_SyntaxException if parse errors occur
     */
    public static function parseSurvey($file)
    {
        $data = array();
        // investigate file parameter and try to get data out of the source.
        switch (true) {
            case (is_resource($file) && get_resource_type($file) == 'stream'):
                // fetch data from handle
                while (!feof($handle)) {
                    $line = fgets($file);
                    $data[] = $line; // push to raw dataset
                }
                break;

            case (is_array($file)):
                // just use it: either stringdata or already array of Therion_Line objects
                $data = $file;
                break;

            case (is_readable($file) || is_string($file) && preg_match('^\w+://', $file)):
                // open file/url and fetch data, then repass to factory
                $fh = fopen ($file, 'r');
                $survey = File_Therion::parseSurvey($fh);
                fclose($fh);
                return $survey;
                break;

            case (is_string($file)):
                // split string data by newlines and use that as result
                $data = explode(PHP_EOL, $file);
                break;

            default:
                // bail out: invalid parameter
                throw new PEAR_Exception('parseSurvey(): Invalid $file argument!', new InvalidArgumentException("passed type='".gettype($file)."'"));
                            
        }
            

        // OK now we got $data populated with string lines
        // lets iterate over it and try to parse.
        // the ultimate goal is to create an instance of File_Therion_Survey
        $survey = null;
        foreach ($data as $sline) {
            // parse if not already a Therion_Line
            $thline = (is_a($sline, 'File_Therion_Line'))
                ? $sline
                : File_Therion_Line::parse($sline);

            
        }


        return $survey;
    }

    /**
     * Writes a Therion survey data structure recursively
     *
     * Writes a Therion data structure to files. The files will be generated in
     * the following way:
     *   - each survey goes into its own file
     *   - each file is named after its survey name
     *   - if the survey name contains lashes, folders will be created.
     *
     * OPTIONS is an associative array to control output and may contain:
     *   'filter' => regexp   
     *       filter by survey name, only write surveys matching the filter.
     *   'depth'  => number
     *       only export to the nth level (0=all, 1=first level, ...)
     *
     * Will throw an appropriate exception if anything goes wrong.
     *
     * @param  string|ressource    $survey Therion_Survey object to write
     * @param  array               $options Options for the writer
     * @throws Pear_Exception      with wrapped lower level exception (InvalidArgumentException, etc)
     */
    public static function writeSurvey($survey, $options = array())
    {
        throw new PEAR_Exception("NOT IMPLEMENTED YET!");

        // the idea here is to query the objects in correct order and use their
        // toString() method to dump out the contents...
    }


}


?>
