<?php
/**
 * Therion data types unit test cases
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
require_once 'File/Therion.php';  //includepath is loaded by phpUnit from phpunit.xml

/**
 * PHPUnit test class for testing various datatypes.
 */
class File_Therion_DataTypesTest extends PHPUnit_Framework_TestCase {
    
    
    /**
     * setup test case, called before a  test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
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




/* ---------- TESTS ---------- */
/* test functions are public and start with "test*".

    /**
     * dummy test
     */
    public function testDummy()
    {
        //$this->markTestSkipped('Skipped Test.');
        //$this->markTestIncomplete("This test has not been implemented yet.");
    
        //$this->assertInstanceOf('File_Therion', $testSubject);
        //$this->assertTrue($false);
        //$this->assertEquals($expected, $actual, 'Failed!');
        //$this->assertNotEquals($expected, $actual, 'Failed!');
        //$this->assertThat(1, $this->greaterThanOrEqual(2));

    }
    
    /**
     * Test Datatype Person.
     */
    public function testPerson()
    {
        // testing of parsing direct string values
        $sample = File_Therion_Person::parse("");
        $this->assertInstanceOf('File_Therion_Person', $sample);
        $this->assertEquals("", $sample->getGivenname());
        $this->assertEquals("", $sample->getSurname());
        $this->assertEquals('""', $sample->toString());
        
        $sample = File_Therion_Person::parse('""');
        $this->assertInstanceOf('File_Therion_Person', $sample);
        $this->assertEquals("", $sample->getGivenname());
        $this->assertEquals("", $sample->getSurname());
        $this->assertEquals('""', $sample->toString());
        
        $sample = File_Therion_Person::parse('"Foo"');
        $this->assertInstanceOf('File_Therion_Person', $sample);
        $this->assertEquals("", $sample->getGivenname());
        $this->assertEquals("Foo", $sample->getSurname());
        $this->assertEquals('Foo', $sample->toString());  // unsure if correct
        
        $sample = File_Therion_Person::parse('"Foo Bar"');
        $this->assertInstanceOf('File_Therion_Person', $sample);
        $this->assertEquals("Foo", $sample->getGivenname());
        $this->assertEquals("Bar", $sample->getSurname());
        $this->assertEquals('"Foo Bar"', $sample->toString());
        
        $sample = File_Therion_Person::parse('"Foo Bar/Baz"');
        $this->assertInstanceOf('File_Therion_Person', $sample);
        $this->assertEquals("Foo Bar", $sample->getGivenname());
        $this->assertEquals("Baz", $sample->getSurname());
        $this->assertEquals('"Foo Bar/Baz"', $sample->toString());
        
        $sample = File_Therion_Person::parse('"Foo/Bar Baz"');
        $this->assertInstanceOf('File_Therion_Person', $sample);
        $this->assertEquals("Foo", $sample->getGivenname());
        $this->assertEquals("Bar Baz", $sample->getSurname());
        $this->assertEquals('"Foo/Bar Baz"', $sample->toString());
        
        $sample = File_Therion_Person::parse('"/Bar"');
        $this->assertInstanceOf('File_Therion_Person', $sample);
        $this->assertEquals("", $sample->getGivenname());
        $this->assertEquals("Bar", $sample->getSurname());
        $this->assertEquals('Bar', $sample->toString());  // unsure if correct
        
        $sample = File_Therion_Person::parse('"Foo/"');
        $this->assertInstanceOf('File_Therion_Person', $sample);
        $this->assertEquals("Foo", $sample->getGivenname());
        $this->assertEquals("", $sample->getSurname());
        $this->assertEquals('Foo/', $sample->toString());

        // testing of normal php string values (without quotes)
        $sample = File_Therion_Person::parse('');
        $this->assertInstanceOf('File_Therion_Person', $sample);
        $this->assertEquals("", $sample->getGivenname());
        $this->assertEquals("", $sample->getSurname());
        $this->assertEquals('""', $sample->toString());
        
        $sample = File_Therion_Person::parse('Foo');
        $this->assertInstanceOf('File_Therion_Person', $sample);
        $this->assertEquals("", $sample->getGivenname());
        $this->assertEquals("Foo", $sample->getSurname());
        $this->assertEquals('Foo', $sample->toString());  // unsure if correct
        
        $sample = File_Therion_Person::parse('Foo Bar');
        $this->assertInstanceOf('File_Therion_Person', $sample);
        $this->assertEquals("Foo", $sample->getGivenname());
        $this->assertEquals("Bar", $sample->getSurname());
        $this->assertEquals('"Foo Bar"', $sample->toString());
        
        $sample = File_Therion_Person::parse('Foo/');
        $this->assertInstanceOf('File_Therion_Person', $sample);
        $this->assertEquals("Foo", $sample->getGivenname());
        $this->assertEquals("", $sample->getSurname());
        $this->assertEquals('Foo/', $sample->toString());
        
        $sample = File_Therion_Person::parse('Foo Bar/Baz');
        $this->assertInstanceOf('File_Therion_Person', $sample);
        $this->assertEquals("Foo Bar", $sample->getGivenname());
        $this->assertEquals("Baz", $sample->getSurname());
        $this->assertEquals('"Foo Bar/Baz"', $sample->toString());
        
        
        // testing of basic construction, internal state and toString
        $sample = new File_Therion_Person();
        $this->assertEquals("", $sample->getGivenname());
        $this->assertEquals("", $sample->getSurname());
        $this->assertEquals('""', $sample->toString());
        
        $sample = new File_Therion_Person('Foo');
        $this->assertEquals("Foo", $sample->getGivenname());
        $this->assertEquals("", $sample->getSurname());
        $this->assertEquals('Foo/', $sample->toString());
        
        $sample = new File_Therion_Person('', 'Bar');
        $this->assertEquals("", $sample->getGivenname());
        $this->assertEquals("Bar", $sample->getSurname());
        $this->assertEquals('Bar', $sample->toString());  // unsure if correct
        
        $sample = new File_Therion_Person('Foo', '');
        $this->assertEquals("Foo", $sample->getGivenname());
        $this->assertEquals("", $sample->getSurname());
        $this->assertEquals('Foo/', $sample->toString());
        
        $sample = new File_Therion_Person('Foo', 'Bar');
        $this->assertEquals("Foo", $sample->getGivenname());
        $this->assertEquals("Bar", $sample->getSurname());
        $this->assertEquals('"Foo Bar"', $sample->toString());
        
        $sample = new File_Therion_Person('Foo', 'Bar Baz');
        $this->assertEquals("Foo", $sample->getGivenname());
        $this->assertEquals("Bar Baz", $sample->getSurname());
        $this->assertEquals('"Foo/Bar Baz"', $sample->toString());
        
        $sample = new File_Therion_Person('Benedikt "Beni"', 'Hallinger');
        $this->assertEquals('Benedikt "Beni"', $sample->getGivenname());
        $this->assertEquals('Hallinger', $sample->getSurname());
        $this->assertEquals('"Benedikt ""Beni""/Hallinger"', $sample->toString());
    }
    
    

}
?>
