<?php
/**
 * Therion cave grade unit test cases
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
 * PHPUnit test class for File_Therion_Grade.
 */
class File_Therion_GradeTest extends File_TherionTestBase {
    

/* ---------- TESTS ---------- */
/* test functions are public and start with "test*".
    
    /**
     * Test instanciation
     */
    public function testBasicStuff()
    {
        // Instantiation
        $sample = new File_Therion_Grade("test");
        $this->assertInstanceOf('File_Therion_Grade', $sample);
        $this->assertEquals("test", $sample->getName());
        
    }
    
    /**
     * Test simple parsing
     */
    public function testParsingHull()
    {
        // Basic survey structure
        $sampleLines = array(
            File_Therion_Line::parse('grade test'),
            File_Therion_Line::parse('  # nothing here'),
            File_Therion_Line::parse('endgrade'),
        );
        $sample = File_Therion_Grade::parse($sampleLines);
        $this->assertInstanceOf('File_Therion_Grade', $sample);
        $this->assertEquals(0, count($sample)); // SPL count definitions
        $this->assertEquals(0, $sample->count()); // normal count definitions
        $this->assertEquals("test", $sample->getName());
        $this->assertEquals("", $sample->getOption('title'));
        
        // now with options
        $sampleLines = array(
            File_Therion_Line::parse('grade test -title "Foo Bar Grade"'),
            File_Therion_Line::parse('  # nothing here'),
            File_Therion_Line::parse('endgrade'),
        );
        $sample = File_Therion_Grade::parse($sampleLines);
        $this->assertInstanceOf('File_Therion_Grade', $sample);
        $this->assertEquals("test", $sample->getName());
        $this->assertEquals("Foo Bar Grade", $sample->getOption('title'));
        
        // now with options with some escape sequence
        $sampleLines = array(
            File_Therion_Line::parse('grade test -title "Foo ""Bar"" Grade"'),
            File_Therion_Line::parse('  # nothing here'),
            File_Therion_Line::parse('endgrade'),
        );
        $sample = File_Therion_Grade::parse($sampleLines);
        $this->assertInstanceOf('File_Therion_Grade', $sample);
        $this->assertEquals("test", $sample->getName());
        $this->assertEquals("Foo \"Bar\" Grade", $sample->getOption('title'));
        
        // now with options with endgrade named tag
        $sampleLines = array(
            File_Therion_Line::parse('grade test'),
            File_Therion_Line::parse('  # nothing here'),
            File_Therion_Line::parse('endgrade test'),
        );
        $sample = File_Therion_Grade::parse($sampleLines);
        $this->assertInstanceOf('File_Therion_Grade', $sample);
        $this->assertEquals("test", $sample->getName());
        
        
        // TODO: Test some wrong invocations
        
     }
     
    /**
     * Test simple parsing
     */
    public function testParsing()
    {
        // Basic survey structure
        $sampleLines = array(
            File_Therion_Line::parse('grade test'),
            File_Therion_Line::parse('  # 95.44% of tape readings are within 0.5m (2 S.D.)'),
            File_Therion_Line::parse('  length 0.25 metres'),
            File_Therion_Line::parse(''),
            File_Therion_Line::parse('  # 95.44% of compass+clino readings are within 2.5 degrees (2 S.D.)'),
            File_Therion_Line::parse('  bearing clino 1.25 degrees'),
            File_Therion_Line::parse('endgrade'),
        );
        $sample = File_Therion_Grade::parse($sampleLines);
        $this->assertInstanceOf('File_Therion_Grade', $sample);
        $this->assertEquals(3, count($sample)); // SPL count definitions
        $this->assertEquals("test", $sample->getName());
        $this->assertEquals("", $sample->getOption('title'));
        $this->assertEquals(new File_Therion_Unit(0.25, 'metres'), $sample->getDefinition("length"));
        $this->assertEquals(new File_Therion_Unit(1.25, 'degrees'), $sample->getDefinition("bearing"));
        $this->assertEquals(new File_Therion_Unit(1.25, 'degrees'), $sample->getDefinition("clino"));
    }
     
    
    
    /**
     * test Line generation
     */
    public function testToLinesSimpleHull()
    {
        // simple example: hull without anything
        $sample = new File_Therion_Grade("test");
        $sampleLines = $sample->toLines();
        $this->assertEquals(2, count($sampleLines));
        $this->assertEquals(
            array(
                File_Therion_Line::parse('grade test'),
                File_Therion_Line::parse('endgrade test'),
            ),
            $sampleLines
        );
        
        // simple example: hull with options
        $sample = new File_Therion_Grade("test", array(
                'title'       => "Foo bar"
            ));
        $sampleLines = $sample->toLines();
        $this->assertEquals(2, count($sampleLines));
        $this->assertEquals(
            array(
                File_Therion_Line::parse(
                    'grade test -title "Foo bar"'),
                File_Therion_Line::parse('endgrade test'),
            ),
            $sampleLines
        );
    }
        
    /**
     * test Line generation
     */
    public function testToLinesWithContent()
    {
        $sample = new File_Therion_Grade('test');
        $sample->setDefinition('length',  new File_Therion_Unit(0.25, 'metres'));
        $sample->setDefinition('bearing', new File_Therion_Unit(1.25, 'degrees'));
        $sample->setDefinition('clino',   new File_Therion_Unit(1.25, 'degrees'));
        
        $this->assertEquals(array(
                File_Therion_Line::parse('grade test'),
                File_Therion_Line::parse("\tlength 0.25 metres"),
                File_Therion_Line::parse("\tbearing 1.25 degrees"),
                File_Therion_Line::parse("\tclino 1.25 degrees"),
                File_Therion_Line::parse('endgrade test')
            ),
            $sample->toLines()
        );
        
        
    }
    
    /**
     * Test adding grades as array
     */
    public function testAddAsArray()
    {
        $grade = new File_Therion_Grade('UISv1_3', array('title' => 'Rough magnetic/analogue survey'));
        $grade->setDefinition(
                array(
                    'length'   => new File_Therion_Unit(0.25, 'metres'),
                    'bearing'  => new File_Therion_Unit(2.50, 'degrees'),
                    'gradient' => new File_Therion_Unit(15.0, 'degrees')
                )
            );
   
    }
    

}
?>