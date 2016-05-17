<?php
/**
 * Therion station unit test cases
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
require_once 'tests/File_TherionTestBase.php';

/**
 * PHPUnit test class for testing various datatypes.
 */
class File_Therion_StationTest extends File_TherionTestBase {


/* ---------- TESTS ---------- */
/* test functions are public and start with "test*".
   
    
    /**
     * Test parsing
     */
    public function testParsing()
    {
        // fixed station
        $line = File_Therion_Line::parse('fix 0 20 40 646.23');
        $sample = File_Therion_Station::parse($line);
        
        $this->assertTrue($sample->isFixed());
        $this->assertEquals("", $sample->getComment());
        $this->assertFalse($sample->getFlag('entrance'));
        
        
        // station comment
        $line = File_Therion_Line::parse('station 1 "some comment"');
        $sample = File_Therion_Station::parse($line);
        $this->assertFalse($sample->isFixed());
        $this->assertEquals("some comment", $sample->getComment());
        $this->assertFalse($sample->getFlag('entrance'));
        $this->assertEquals(array(), $sample->getAllFlags());
        
        
        // station comments and flags
        $line = File_Therion_Line::parse('station 1 "some comment2" entrance');
        $sample = File_Therion_Station::parse($line);
        $this->assertFalse($sample->isFixed());
        $this->assertEquals("some comment2", $sample->getComment());
        $this->assertTrue($sample->getFlag('entrance'));
        $this->assertEquals(
            array(
                'entrance' => true
            ),
            $sample->getAllFlags()
        );
        // TODO: More flags!
        
        
    }
    
}
?>