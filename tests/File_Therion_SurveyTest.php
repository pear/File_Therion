<?php
/**
 * Therion cave survey unit test cases
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
 * PHPUnit test class for File_Therion_Survey.
 */
class File_Therion_SurveyTest extends PHPUnit_Framework_TestCase {
    
    
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
     * Test instanciation
     */
    public function testBasicStuff()
    {
        // Instantiation
        $sample = new File_Therion_Survey("test");
        $this->assertInstanceOf('File_Therion_Survey', $sample);
        $this->assertEquals(0, count($sample)); // SPL count subsurveys
        $this->assertEquals(0, $sample->count()); // normal count subsurveys
        $this->assertEquals("test", $sample->getName());
    }
    
    /**
     * Test simple parsing
     * 
     * @todo test wrong invocations
     */
    public function testParsingHull()
    {
        // Basic survey structure
        $sampleLines = array(
            File_Therion_Line::parse('survey test'),
            File_Therion_Line::parse('  # nothing here'),
            File_Therion_Line::parse('endsurvey'),
        );
        $sample = File_Therion_Survey::parse($sampleLines);
        $this->assertInstanceOf('File_Therion_Survey', $sample);
        $this->assertEquals(0, count($sample)); // SPL count subsurveys
        $this->assertEquals(0, $sample->count()); // normal count subsurveys
        $this->assertEquals("test", $sample->getName());
        $this->assertEquals("", $sample->getOption('title'));
        
        // now with options
        $sampleLines = array(
            File_Therion_Line::parse('survey test -title "Foo Bar Survey"'),
            File_Therion_Line::parse('  # nothing here'),
            File_Therion_Line::parse('endsurvey'),
        );
        $sample = File_Therion_Survey::parse($sampleLines);
        $this->assertInstanceOf('File_Therion_Survey', $sample);
        $this->assertEquals("test", $sample->getName());
        $this->assertEquals("Foo Bar Survey", $sample->getOption('title'));
        
        // now with options with some escape sequence
        $sampleLines = array(
            File_Therion_Line::parse('survey test -title "Foo ""Bar"" Survey"'),
            File_Therion_Line::parse('  # nothing here'),
            File_Therion_Line::parse('endsurvey'),
        );
        $sample = File_Therion_Survey::parse($sampleLines);
        $this->assertInstanceOf('File_Therion_Survey', $sample);
        $this->assertEquals("test", $sample->getName());
        $this->assertEquals("Foo \"Bar\" Survey", $sample->getOption('title'));
        
        // now with options with endsurvey named tag
        $sampleLines = array(
            File_Therion_Line::parse('survey test'),
            File_Therion_Line::parse('  # nothing here'),
            File_Therion_Line::parse('endsurvey test'),
        );
        $sample = File_Therion_Survey::parse($sampleLines);
        $this->assertInstanceOf('File_Therion_Survey', $sample);
        $this->assertEquals("test", $sample->getName());
        
        
        // TODO: Test some wrong invocations
        
     }
     
    /**
     * Test joins
     */
    public function testJoins()
    {
        $sample = new File_Therion_Survey("test");
        $sample->addJoin("foo", "bar");
        $sample->addJoin("foo", "bar", "baz");
        $sample->addJoin(array("foo", "bar", "baz"));
        
        $this->assertEquals(
            array(
                array("foo", "bar"),
                array("foo", "bar", "baz"),
                array("foo", "bar", "baz"),
            ),
            $sample->getJoins()
        );
        
        
        // wrong invocations:
        try {
            $sample->addJoin();
        } catch (Exception $e) {
            $this->assertInstanceOf('Exception', $e);
        }
        
        try {
            $sample->addJoin("missingPartner");
        } catch (Exception $e) {
            $this->assertInstanceOf('Exception', $e);
        }
        
        try {
            $sample->addJoin("missingPartner", null);
        } catch (Exception $e) {
            $this->assertInstanceOf('Exception', $e);
        }
        
        try {
            $sample->addJoin("missingPartner", array());
        } catch (Exception $e) {
            $this->assertInstanceOf('Exception', $e);
        }
        
        try {
            $sample->addJoin(array("foo"), "bar", array("baz")); // <-nonsense
        } catch (Exception $e) {
            $this->assertInstanceOf('Exception', $e);
        }
    }
    
    /**
     * Test equates
     */
    public function testEquates()
    {
        $sample = new File_Therion_Survey("test");
        $sample->addEquate("1.1", "1.25@subsurvey");
        $sample->addEquate("1.1", "2.1", "1.25@subsurvey");
        $sample->addEquate(array("foo", "bar", "baz"));
        
        $this->assertEquals(
            array(
                array("1.1", "1.25@subsurvey"),
                array("1.1", "2.1", "1.25@subsurvey"),
                array("foo", "bar", "baz"),
            ),
            $sample->getEquates()
        );
        
        
        // wrong invocations:
        try {
            $sample->addEquate();
        } catch (Exception $e) {
            $this->assertInstanceOf('Exception', $e);
        }
        
        try {
            $sample->addEquate("missingPartner");
        } catch (Exception $e) {
            $this->assertInstanceOf('Exception', $e);
        }
        
        try {
            $sample->addEquate("missingPartner", null);
        } catch (Exception $e) {
            $this->assertInstanceOf('Exception', $e);
        }
        
        try {
            $sample->addEquate("missingPartner", array());
        } catch (Exception $e) {
            $this->assertInstanceOf('Exception', $e);
        }
        
        try {
            $sample->addEquate(array("foo"), "bar", array("baz")); // <-nonsense
        } catch (Exception $e) {
            $this->assertInstanceOf('Exception', $e);
        }
    }
    
    
    public function testParsingJoins()
    {   
        // Basic survey structure with simple data
        $sampleLines = array(
            File_Therion_Line::parse('survey test'),
            File_Therion_Line::parse('  join ew1:0 ew2:end'),
            File_Therion_Line::parse('  join ps1 ps2'),
            File_Therion_Line::parse('endsurvey'),
        );
        $sample = File_Therion_Survey::parse($sampleLines);
        $this->assertInstanceOf('File_Therion_Survey', $sample);
        $this->assertEquals(
            array(
                array("ew1:0", "ew2:end"),
                array("ps1", "ps2")
            ),
            $sample->getJoins()
        );
        
        $sample->clearJoins();
        $this->assertEquals(array(), $sample->getJoins());
        
        
    }
    
    public function testParsingMaps()
    {   
        // Basic survey structure with simple data
        // Maps will be tested extensively in the map test class
        $sampleLines = array(
            File_Therion_Line::parse('survey test'),
            File_Therion_Line::parse('  map foo'),
            File_Therion_Line::parse('    # map content'),
            File_Therion_Line::parse('  endmap'),
            File_Therion_Line::parse('  map bar'),
            File_Therion_Line::parse('    # map content'),
            File_Therion_Line::parse('  endmap'),
            File_Therion_Line::parse('endsurvey'),
        );
        $sample = File_Therion_Survey::parse($sampleLines);
        $this->assertInstanceOf('File_Therion_Survey', $sample);
        $this->assertEquals(2, count($sample->getMaps()));
        
        $sample->clearMaps();
        $this->assertEquals(array(), $sample->getMaps());
        
    }
    
    public function testParsingSurface()
    {   
        // Basic survey structure with simple data
        // Surfaces will be tested extensively in the surfacetest class
        $sampleLines = array(
            File_Therion_Line::parse('survey test'),
            File_Therion_Line::parse('  surface'),
            File_Therion_Line::parse('    # content'),
            File_Therion_Line::parse('  endsurface'),
            File_Therion_Line::parse('endsurvey'),
        );
        $sample = File_Therion_Survey::parse($sampleLines);
        $this->assertInstanceOf('File_Therion_Survey', $sample);
        $this->assertEquals(1, count($sample->getSurface()));
        
        $sample->clearMaps();
        $this->assertEquals(array(), $sample->getMaps());
        
    }
    
    public function testParsingScraps()
    {   
        // Basic survey structure with simple data
        // Scraps will be tested extensively in the scrap test class
        $sampleLines = array(
            File_Therion_Line::parse('survey test'),
            File_Therion_Line::parse('  scrap fooScrap'),
            File_Therion_Line::parse('    # content'),
            File_Therion_Line::parse('  endscrap'),
            File_Therion_Line::parse('  scrap barScrap'),
            File_Therion_Line::parse('    # content'),
            File_Therion_Line::parse('  endscrap'),
            File_Therion_Line::parse('endsurvey'),
        );
        $sample = File_Therion_Survey::parse($sampleLines);
        $this->assertInstanceOf('File_Therion_Survey', $sample);
        $this->assertEquals(2, count($sample->getScraps()));
        
        $sample->clearScraps();
        $this->assertEquals(array(), $sample->getScraps());
        
    }
      
    public function testParsingCentreline()
    {   
        // Basic survey structure with simple data
        // Centreline will be tested extensively in the scrap test class
        $sampleLines = array(
            File_Therion_Line::parse('survey test'),
            File_Therion_Line::parse('  centreline'),
            File_Therion_Line::parse('    # content'),
            File_Therion_Line::parse('  endcentreline'),
            File_Therion_Line::parse('  centreline'),
            File_Therion_Line::parse('    # content'),
            File_Therion_Line::parse('  endcentreline'),
            File_Therion_Line::parse('endsurvey'),
        );
        $sample = File_Therion_Survey::parse($sampleLines);
        $this->assertInstanceOf('File_Therion_Survey', $sample);
        $this->assertEquals(2, count($sample->getCentrelines()));
        
        $sample->clearCentrelines();
        $this->assertEquals(array(), $sample->getCentrelines());
        
    }
    
    public function testParsingSubsurvey()
    {   
        // Basic nested survey structure
        $sampleLines = array(
            File_Therion_Line::parse('survey lvl_1'),
            File_Therion_Line::parse('  survey lvl_1_1'),
            File_Therion_Line::parse('    # content'),
            File_Therion_Line::parse('  endsurvey'),
            File_Therion_Line::parse('  survey lvl_1_2'),
            File_Therion_Line::parse('    # content'),
            File_Therion_Line::parse('    survey lvl_2_1'),
            File_Therion_Line::parse('      # content'),
            File_Therion_Line::parse('    endsurvey'),
            File_Therion_Line::parse('  endsurvey'),
            File_Therion_Line::parse('  survey lvl_1_3'),
            File_Therion_Line::parse('    survey lvl_2_2'),
            File_Therion_Line::parse('      # content'),
            File_Therion_Line::parse('    endsurvey'),
            File_Therion_Line::parse('    survey lvl_2_3'),
            File_Therion_Line::parse('      survey lvl_2_1'),
            File_Therion_Line::parse('        # content'),
            File_Therion_Line::parse('      endsurvey'),
            File_Therion_Line::parse('    endsurvey'),
            File_Therion_Line::parse('  endsurvey'),
            File_Therion_Line::parse('endsurvey'),
        );
        $sample = File_Therion_Survey::parse($sampleLines);
        $this->assertInstanceOf('File_Therion_Survey', $sample);
        $this->assertEquals(3, count($sample->getSurveys()));
        $subsurveys = $sample->getSurveys();
        $this->assertEquals(0, count($subsurveys[0]));
        $this->assertEquals(1, count($subsurveys[1]));
        $this->assertEquals(2, count($subsurveys[2]));
        
        $sample->clearSurveys();
        $this->assertEquals(array(), $sample->getSurveys());
        $this->assertEquals(0, count($sample->getSurveys()));
        
    }
    
    

}
?>
