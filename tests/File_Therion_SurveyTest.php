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
 
//includepath is loaded by phpUnit from phpunit.xml 
require_once 'tests/File_TherionTestBase.php';

/**
 * PHPUnit test class for File_Therion_Survey.
 */
class File_Therion_SurveyTest extends File_TherionTestBase {
    

/* ---------- TESTS ---------- */
/* test functions are public and start with "test*".
    
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
        
        // in-deep testing will be done in dedicated test class
        $j1 = File_Therion_Join::parse(
            File_Therion_Line::parse("join foo bar")
        );
        $j2 = File_Therion_Join::parse(
            File_Therion_Line::parse("join l1 l2:0 l3:end")
        );
        
        $sample->addJoin($j1);
        $sample->addJoin($j2);
        
        $this->assertEquals(array($j1, $j2), $sample->getJoins());
    }
    
    /**
     * Test equates
     */
    public function testEquates()
    {        
        // make a survey
        $sample = new File_Therion_Survey("test");
        $cl1    = new File_Therion_Centreline();
        $sample->addCentreline($cl1);
        $cl1->setStationNames("1.", "");
                
        $shot1  = new File_Therion_Shot();
        $shot1->setFrom(new File_Therion_Station("1"));
        $shot1->setTo(new File_Therion_Station("2"));
        $cl1->addShot($shot1);
        
        $shot2  = new File_Therion_Shot();
        $shot2->setFrom($shot1->getTo());
        $shot2->setTo(new File_Therion_Station("3"));
        $cl1->addShot($shot2);
        
        // make another survey as subsurvey to sample
        $sample2 = new File_Therion_Survey("subTest");
        $sample->addSurvey($sample2);
        $cl2     = new File_Therion_Centreline();
        $sample2->addCentreline($cl2);
                
        $shot11  = new File_Therion_Shot();
        $shot11->setFrom(new File_Therion_Station("2.1"));
        $shot11->setTo(new File_Therion_Station("2.2"));
        $cl2->addShot($shot11);
        
        $shot21  = new File_Therion_Shot();
        $shot21->setFrom($shot11->getTo());
        $shot21->setTo(new File_Therion_Station("2.3"));
        $cl2->addShot($shot21);
        
        // set equal: first station from first CL == last station from last CL
        // lookup from the survey context
        $first = $sample->getCentrelines()[0]->getShots()[0]->getFrom();
        $last  = $sample2->getCentrelines()[0]->getShots()[1]->getTo();
        $sample->addEquate(new File_Therion_Equate(array($first, $last)));
        
        // expected is clean referenced equate command
        // TODO: Im not sure how the station prefix is correctly dealth with.
        //       Maybe with present station-names the name must be fully given
        $this->assertEquals(
            'equate 1 2.3@subTest',
            $sample->getEquates()[0]->toString()
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
                "join ew1:0 ew2:end",
                "join ps1 ps2"
            ),
            array(
                $sample->getJoins()[0]->toString(),
                $sample->getJoins()[1]->toString()
            )
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
        $this->assertEquals(1, count($sample->getSurfaces()));
        
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
            File_Therion_Line::parse('      endsurvey lvl_2_1'),
            File_Therion_Line::parse('    endsurvey'),
            File_Therion_Line::parse('  endsurvey'),
            File_Therion_Line::parse('endsurvey lvl_1'),
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
    
    /**
     * test Line generation
     */
    public function testToLinesSimple()
    {
        // simple example: hull without anything
        $sample = new File_Therion_Survey("testSurvey");
        $sampleLines = $sample->toLines();
        $this->assertEquals(2, count($sampleLines));
        $this->assertEquals(
            array(
                File_Therion_Line::parse('survey testSurvey'),
                File_Therion_Line::parse('endsurvey testSurvey'),
            ),
            $sampleLines
        );
        
        // simple example: hull with options
        $sample = new File_Therion_Survey("testSurvey", array(
                'title'       => "Foo bar",
                'declination' => "3.0 grads",
                'namespace'   => "on"
            ));
        $sampleLines = $sample->toLines();
        $this->assertEquals(2, count($sampleLines));
        $this->assertEquals(
            array(
                File_Therion_Line::parse(
                    'survey testSurvey -title "Foo bar"'
                    .' -declination [3.0 grads]'
                    .' -namespace on'),
                File_Therion_Line::parse('endsurvey testSurvey'),
            ),
            $sampleLines
        );
    }
    
    /**
     * test Line generation with subsurvey
     */
    public function testToLinesWithSubsurvey()
    {
        // outer hull without anything
        $sample = new File_Therion_Survey("outherSurvey");
        
        // inner example: hull with options
        $sample2 = new File_Therion_Survey("innerSurvey", array(
                'title'       => "Foo bar",
                'declination' => "3.0 grads",
                'namespace'   => "on"
            ));
            
        $sample->addSurvey($sample2);
        
        $sampleLines = $sample->toLines();
        $this->assertEquals(4, count($sampleLines));
        $this->assertEquals(
            array(
                File_Therion_Line::parse('survey outherSurvey'),
                File_Therion_Line::parse(
                    "\tsurvey innerSurvey -title \"Foo bar\""
                    .' -declination [3.0 grads]'
                    .' -namespace on'),
                File_Therion_Line::parse("\tendsurvey innerSurvey"),
                File_Therion_Line::parse('endsurvey outherSurvey'),
            ),
            $sampleLines
        );
    }

}
?>