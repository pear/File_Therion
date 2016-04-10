<?php
/**
 * Therion cave survey unit test base class
 *
 * PHP version 5
 *
 * @category   file
 * @package    File_Therion_Tests
 * @author     Benedikt Hallinger <beni@php.net>
 * @copyright  2016 Benedikt Hallinger
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @link       http://pear.php.net/package/File_Therion/
 */
 
//includepath is loaded by phpUnit from phpunit.xml
require_once 'File/Therion.php';

/**
 * PHPUnit test base class for File_Therion.
 * 
 * This holds common vars and functions for all other tests.
 */
class File_TherionTestBase extends PHPUnit_Framework_TestCase {

    /**
     * Base location of test data (therion distribution)
     * 
     * @var string
     */
    protected $testdata_base_therion = __DIR__.'/data/samples_therion/';
    
    /**
     * Base location of test data (own samples)
     * 
     * @var string
     */
    protected $testdata_base_own = __DIR__.'/data/samples_own/';
    
    /**
     * Base location of test output
     * 
     * @var string
     */
    protected $testdata_base_out = __DIR__.'/testoutput/';
    
    
    /**
     * setup test case, called before a  test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        // create data output directory if not existing
        if (!file_exists($this->testdata_base_out)) {
            mkdir($this->testdata_base_out);
        }
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
    }


}
?>