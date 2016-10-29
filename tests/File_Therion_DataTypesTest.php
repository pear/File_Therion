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
 
//includepath is loaded by phpUnit from phpunit.xml
require_once 'tests/File_TherionTestBase.php';

/**
 * PHPUnit test class for testing various datatypes.
 */
class File_Therion_DataTypesTest extends File_TherionTestBase {


/* ---------- TESTS ---------- */
/* test functions are public and start with "test*".

    
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
    
    /**
     * Test Datatype Unit.
     */
    public function testUnit()
    {
        // test basic instantiation
        $sample = new File_Therion_Unit(5.3, 'meter');
        $sample = new File_Therion_Unit(5.3, 'meters'); // aliased
        $sample = new File_Therion_Unit(5.3, 'm');      // aliased
        
        // test wrong instantiation
        $exc = null;
        try {
             $sample = new File_Therion_Unit(5.3, 'Meters');
        } catch (Exception $e) {
            $exc = $e;
        }
        $this->assertInstanceOf('File_Therion_Exception', $exc);
       
        
        // testing of parsing direct string values
        $sample = File_Therion_Unit::parse("meters");
        $this->assertInstanceOf('File_Therion_Unit', $sample);
        $sample = File_Therion_Unit::parse("5.3 meters");
        $this->assertInstanceOf('File_Therion_Unit', $sample);
        
        // testing of type mapping
        $sample = File_Therion_Unit::parse("meters");
        $this->assertInstanceOf('File_Therion_Unit', $sample);
        $this->assertEquals("meters", $sample->getType());      // default
        $this->assertEquals("meters", $sample->getType(false)); // explicit original
        $this->assertEquals("meter", $sample->getType(true));   // normalized
        $this->assertEquals(null, $sample->getQuantity());
        
        $sample = new File_Therion_Unit(5.3, 'deg'); // aliased
        $this->assertEquals("deg", $sample->getType());      // default
        $this->assertEquals("deg", $sample->getType(false)); // explicit original
        $this->assertEquals("degree", $sample->getType(true));   // normalized
        $this->assertEquals(5.3, $sample->getQuantity());
        
        // toString() testing
        $this->assertEquals("5.3 deg", $sample->toString());        // default behavior
        $this->assertEquals("5.3 deg", $sample->toString(false));   // explicit original
        $this->assertEquals("5.3 degree", $sample->toString(true)); // normalized
    }
        
        
    /**
     * Test units calculations
     * 
     * @todo just raw tests implemented, more needed
     */
    public function testUnitConversions()
    {
        $sample = new File_Therion_Unit(0, 'degrees');
        $sample->convertTo("grads");
        $this->assertEquals("grads", $sample->getType());
        $this->assertEquals(0, $sample->getQuantity());
 
        $sample = new File_Therion_Unit(360, 'degrees');
        $sample->convertTo("grads");
        $this->assertEquals("grads", $sample->getType());
        $this->assertEquals(400, $sample->getQuantity());
        
        $sample = new File_Therion_Unit(0, 'grads');
        $sample->convertTo("degree");
        $this->assertEquals("degree", $sample->getType());
        $this->assertEquals(0, $sample->getQuantity());
        
        $sample = new File_Therion_Unit(400, 'grads');
        $sample->convertTo("degree");
        $this->assertEquals("degree", $sample->getType());
        $this->assertEquals(360, $sample->getQuantity());
        

        // todo: more tests!
       
    }

}
?>