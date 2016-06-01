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
        
        // test for loop references
        $sampleChild1 = new File_Therion_Survey("testChild1");
        $sample->addSurvey($sampleChild1);
        $sampleChild2 = new File_Therion_Survey("testChild2");
        $sampleChild1->addSurvey($sampleChild2);
        $exc = null;
        try {
            $sampleChild2->addSurvey($sample);
        } catch (Exception $e) {
            $exc = $e;
        }
        $this->assertInstanceOf('File_Therion_Exception', $exc);
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
    public function testEquatingSimple()
    {        
        // make a survey
        $sample = new File_Therion_Survey("test");
        $cl1    = new File_Therion_Centreline();
        $sample->addCentreline($cl1);
        
        $shot0  = new File_Therion_Shot(); // without station-names in effect!
        $shot0->setFrom(new File_Therion_Station("0"));
        $shot0->setTo(new File_Therion_Station("1"));
        $cl1->addShot($shot0);
        
        $cl1->setStationNames("1.", ""); // now set station names
        // Note this creates a mistake as this yields in two valid stations;
        // one is "1" and the other "1.1". but they are not the same.
        // We will "fix" this using an equate below.
                
        $shot1  = new File_Therion_Shot();
        $shot1->setFrom(new File_Therion_Station("1")); // 1.1, but not linked
        $shot1->setTo(new File_Therion_Station("2"));   // 1.2
        $this->assertEquals("1", $shot1->getFrom()->getName(true));
        $cl1->addShot($shot1); // this updates statin-names for shot stations
        
        $shot2  = new File_Therion_Shot();
        $shot2->setFrom($shot1->getTo());  // linked 1.2
        $shot2->setTo(new File_Therion_Station("3"));  // 1.3
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
        
        // test stations: expected are 8 because of 1<>1.1 mistake
        $this->assertEquals(8, count($sample->getAllStations(-1)));
        
        // set equal (lookup from the survey context):
        // - Fix 1 to 1.1 human error (introduced by station-names above)
        // - second station from first CL == last station from first CL
        $start = $sample->getCentrelines()[0]->getShots()[0]->getTo();   // 1
        $first = $sample->getCentrelines()[0]->getShots()[1]->getFrom(); // 1.1
        $last  = $sample2->getCentrelines()[0]->getShots()[1]->getTo();  // 2.3
        $start->addEquate(array($first, $last));
        $this->assertEquals("1",   $start->getName(true));
        $this->assertEquals("1.1", $first->getName(true));
        $this->assertEquals("2.3", $last->getName(true));

        // expected is two stations in top survey with valid equals.
        // ($first has a valid resolvable backlink)
        // also note, that because me made a mistake above, we have two
        // "1" stations: 1 and 1.1. This can be fixed by equating them.
        $this->assertEquals(array($start), $sample->getEquates());
        $this->assertEquals(
            'equate 1 1.1 2.3@subTest',
            $start->toEquateString()
        );
        $this->assertEquals(
            '', // skipped link, because station 1.1 only links back to 1!
            $first->toEquateString()
        );
        
        
        // test line representation of those equates
        $lines = File_Therion_Line::filterNonEmpty($sample->toLines());
        // FOR DEBUGGING: Print survey lines
        //foreach($lines as $l) {
        //    print "DBG: ".$l->toString();
        //}
        $this->assertEquals( // investigate output slice of lines
            array(
                "\tendcentreline",
                "\tequate 1 1.1 2.3@subTest",
                "\tequate 2.3@subTest 1",  // <- obsolete backref...
                "\tsurvey subTest"
            ),
            array(
                rtrim($lines[7]->toString()),
                rtrim($lines[8]->toString()),
                rtrim($lines[9]->toString()),
                rtrim($lines[10]->toString())
            )
        );
        
    }
    
    public function testEquateParsing()
    {
        // fetch file content
        $equatesFile = $this->testdata_base_own.'/parseEquatetest/equates.th';
        $th = File_Therion::parse($equatesFile);
        $this->assertEquals(1, count($th->getSurveys()));
        $survey = array_shift($th->getSurveys());
        $subsurvey = array_shift($survey->getSurveys());
        $cl1 = $survey->getCentrelines()[0];
        $cl2 = $subsurvey->getCentrelines()[0];
        
        // see what we have
        $cl1_sh1_to   = $cl1->getShots()[0]->getTo();   // 1
        $cl1_sh2_from = $cl1->getShots()[1]->getFrom(); // 1.1
        $cl2_sh2_to   = $cl2->getShots()[1]->getTo();   // 2.3
        
        $this->assertEquals(  // equate 1 1.1 2.3@subTest
            array($cl1_sh2_from, $cl2_sh2_to),
            $cl1_sh1_to->getEquates()
        );
        
        // backlinks established?
        $this->assertEquals(array($cl1_sh1_to), $cl1_sh2_from->getEquates() );
        $this->assertEquals(array($cl1_sh1_to), $cl2_sh2_to->getEquates() );
        
        // TODO negative test cases for equate parsing missing...
    }
    
    /**
     * Test deep equating
     * 
     * That is points that are equal but do not belong to the local survey
     * but must be equated there because the points cannot see each other.
     * 
     * Survey A containts both survey AB and AC.
     * Survey AB1 and AB2 is a child of AC.
     * Points of AB1 and AB2 are equated but cant reach them in local context.
     * The equate command must be given in Survey AB but not in A (but it would
     * be also valid there, given that the referencing is done correctly).
     */
    public function testEquatingOfDeepStationsSameParent()
    {
        // make survey structure
        $srvy_A   = new File_Therion_Survey("A");
        $srvy_AB  = new File_Therion_Survey("AB");
        $srvy_AC  = new File_Therion_Survey("AC");
        $srvy_AB1 = new File_Therion_Survey("AB1");
        $srvy_AB2 = new File_Therion_Survey("AB2");
        $srvy_A->addSurvey($srvy_AB);
        $srvy_A->addSurvey($srvy_AC);
        $srvy_AB->addSurvey($srvy_AB1);
        $srvy_AB->addSurvey($srvy_AB2);
        
        // prepare centreline data
        $stn_1_1 = new File_Therion_Station("1.1");
        $stn_1_2 = new File_Therion_Station("1.2");
        $stn_2_1 = new File_Therion_Station("2.1");
        $stn_2_2 = new File_Therion_Station("2.2");
        $srvy_AB1->addCentreline(new File_Therion_Centreline());
        $srvy_AB2->addCentreline(new File_Therion_Centreline());
        $srvy_AB1->getCentrelines()[0]->addShot(
            new File_Therion_Shot($stn_1_1, $stn_1_2)
        );
        $srvy_AB2->getCentrelines()[0]->addShot(
            new File_Therion_Shot($stn_2_1, $stn_2_2)
        );
        $stn_1_2->addEquate($stn_2_2);
        
        // FOR DEBUGGING: Print survey lines
        //foreach($srvy_A->toLines() as $l) {
        //    print "DBG: ".$l->toString();
        //}
        
        // test it
        $this->assertEquals("", $stn_1_2->toEquateString()); // not referencable
        $this->assertEquals("", $stn_2_2->toEquateString()); // not referencable
        $this->assertEquals(0, count($srvy_A->getEquates(-1))); // can be referenced deeper down (AB)
        $this->assertEquals(0, count($srvy_AC->getEquates(-1))); // not referenceable
        $this->assertEquals(1, count($srvy_AB->getEquates(-1))); // equate in child srvy
        $this->assertEquals(0, count($srvy_AB1->getEquates(-1))); // not referenceable here, only @parent
        $this->assertEquals(0, count($srvy_AB2->getEquates(-1))); // not referenceable here, only @parent


        // test line representation of those equates
        $lines = File_Therion_Line::filterNonEmpty($srvy_A->toLines());
        $this->assertEquals( // investigate output slice of lines
            array(
                "\tsurvey AB",
                "\t\tequate 1.2@AB1 2.2@AB2",
                "\t\tsurvey AB1"
            ),
            array(
                rtrim($lines[1]->toString()),
                rtrim($lines[2]->toString()),
                rtrim($lines[3]->toString())
            )
        );
    }
    
    /**
     * Test deep equating
     * 
     * That is points that are equal but do not belong to the local survey
     * but must be equated there because the points cannot see each other.
     * 
     * Survey A containts both survey AB and AC.
     * Survey AB1 and AB2 is a child of AC.
     * Survey AB2a is a child of AB2 and contains a station.
     * Points of AB1 and AB2a are equated but cant reach them in local context.
     * The equate command must be given in Survey AB but not in A (but it would
     * be also valid there, given that the referencing is done correctly).
     */
    public function testEquatingOfDeepStationsRecursedParent()
    {
        // make survey structure
        $srvy_A   = new File_Therion_Survey("A");
        $srvy_AB  = new File_Therion_Survey("AB");
        $srvy_AC  = new File_Therion_Survey("AC");
        $srvy_AB1 = new File_Therion_Survey("AB1");
        $srvy_AB2 = new File_Therion_Survey("AB2");
        $srvy_AB2a = new File_Therion_Survey("AB2a");
        $srvy_A->addSurvey($srvy_AB);
        $srvy_A->addSurvey($srvy_AC);
        $srvy_AB->addSurvey($srvy_AB1);
        $srvy_AB2->addSurvey($srvy_AB2a);
        $srvy_AB->addSurvey($srvy_AB2);
        
        // prepare centreline data
        $stn_1_1 = new File_Therion_Station("1.1");
        $stn_1_2 = new File_Therion_Station("1.2");
        $stn_2_1 = new File_Therion_Station("2.1");
        $stn_2_2 = new File_Therion_Station("2.2");
        $srvy_AB1->addCentreline(new File_Therion_Centreline());
        $srvy_AB2a->addCentreline(new File_Therion_Centreline());
        $srvy_AB1->getCentrelines()[0]->addShot(
            new File_Therion_Shot($stn_1_1, $stn_1_2)
        );
        $srvy_AB2a->getCentrelines()[0]->addShot(
            new File_Therion_Shot($stn_2_1, $stn_2_2)
        );
        $stn_1_2->addEquate($stn_2_2);
        
        // FOR DEBUGGING: Print survey lines
        //foreach($srvy_A->toLines() as $l) {
        //    print "DBG: ".$l->toString();
        //}
        
        // test it
        $this->assertEquals("", $stn_1_2->toEquateString()); // not referencable
        $this->assertEquals("", $stn_2_2->toEquateString()); // not referencable
        $this->assertEquals(0, count($srvy_A->getEquates(-1))); // can be referenced deeper down (AB)
        $this->assertEquals(0, count($srvy_AC->getEquates(-1))); // not referenceable
        $this->assertEquals(1, count($srvy_AB->getEquates(-1))); // equate in child srvy (recursed from AB2a)
        $this->assertEquals(0, count($srvy_AB1->getEquates(-1))); // not referenceable here, only @parent
        $this->assertEquals(0, count($srvy_AB2->getEquates(-1))); // not referenceable here, only @parent
        $this->assertEquals(0, count($srvy_AB2a->getEquates(-1))); // not referenceable here, only @parent
        
        // test line representation of those equates
        $lines = File_Therion_Line::filterNonEmpty($srvy_A->toLines());
        $this->assertEquals( // investigate output slice of lines
            array(
                "\tsurvey AB",
                "\t\tequate 1.2@AB1 2.2@AB2.AB2a",
                "\t\tsurvey AB1",
            ),
            array(
                rtrim($lines[1]->toString()),
                rtrim($lines[2]->toString()),
                rtrim($lines[3]->toString())
            )
        );
    }
    
    /**
     * Test deep equating
     * 
     * That is points that are equal but do not belong to the local survey
     * but must be equated there because the points cannot see each other.
     * 
     * Survey A containts both survey AB and AC.
     * Survey AB1 and AB2 is a child of AC.
     * Survey AB2a is a child of AB2 and contains a station.
     * Points of AB1 and AB2a are equated but cant reach them in local context.
     * The equate command must be given in Survey AB but not in A (but it would
     * be also valid there, given that the referencing is done correctly).
     * Additionally AB has three local stations where the first and last are eq.
     */
    public function testEquatingOfMixedStations()
    {
        // make survey structure
        $srvy_A   = new File_Therion_Survey("A");
        $srvy_AB  = new File_Therion_Survey("AB");
        $srvy_AC  = new File_Therion_Survey("AC");
        $srvy_AB1 = new File_Therion_Survey("AB1");
        $srvy_AB2 = new File_Therion_Survey("AB2");
        $srvy_AB2a = new File_Therion_Survey("AB2a");
        $srvy_A->addSurvey($srvy_AB);
        $srvy_A->addSurvey($srvy_AC);
        $srvy_AB->addSurvey($srvy_AB1);
        $srvy_AB2->addSurvey($srvy_AB2a);
        $srvy_AB->addSurvey($srvy_AB2);
        
        // prepare centreline data
        $stn_0_1 = new File_Therion_Station("0.1");
        $stn_0_2 = new File_Therion_Station("0.2");
        $stn_0_3 = new File_Therion_Station("0.3");
        $srvy_AB->addCentreline(new File_Therion_Centreline());
        $srvy_AB->getCentrelines()[0]->addShot(
            new File_Therion_Shot($stn_0_1, $stn_0_2)
        );
        $srvy_AB->getCentrelines()[0]->addShot(
            new File_Therion_Shot($stn_0_2, $stn_0_3)
        );
        $stn_0_3->addEquate($stn_0_1);
        
        $stn_1_1 = new File_Therion_Station("1.1");
        $stn_1_2 = new File_Therion_Station("1.2");
        $srvy_AB1->addCentreline(new File_Therion_Centreline());
        $srvy_AB1->getCentrelines()[0]->addShot(
            new File_Therion_Shot($stn_1_1, $stn_1_2)
        );
        
        $stn_2_1 = new File_Therion_Station("2.1");
        $stn_2_2 = new File_Therion_Station("2.2");
        $stn_2_3 = new File_Therion_Station("2.3");
        $stn_2_4 = new File_Therion_Station("2.4");
        $srvy_AB2a->addCentreline(new File_Therion_Centreline());
        $srvy_AB2a->getCentrelines()[0]->addShot(
            new File_Therion_Shot($stn_2_1, $stn_2_2)
        );
        $srvy_AB2a->getCentrelines()[0]->addShot(
            new File_Therion_Shot($stn_2_2, $stn_2_3)
        );
        $srvy_AB2a->getCentrelines()[0]->addShot(
            new File_Therion_Shot($stn_2_3, $stn_2_4)
        );
        $stn_2_3->addEquate($stn_2_4); // local equate
        $stn_1_2->addEquate($stn_2_2);
        $stn_0_2->addEquate($stn_2_2);
        
        // FOR DEBUGGING: Print survey lines
        //foreach($srvy_A->toLines() as $l) {
        //    print "DBG: ".$l->toString();
        //}
        
        // test it
        $this->assertEquals("", $stn_1_2->toEquateString()); // not referencable
        $this->assertEquals("", $stn_2_2->toEquateString()); // not referencable
        $this->assertEquals(0, count($srvy_A->getEquates(-1))); // can be referenced deeper down (AB)
        $this->assertEquals(0, count($srvy_AC->getEquates(-1))); // not referenceable
        $this->assertEquals(4, count($srvy_AB->getEquates(-1))); // equate in child srvy (recursed from AB2a)
        $this->assertEquals(0, count($srvy_AB1->getEquates(-1))); // not referenceable here, only @parent
        $this->assertEquals(0, count($srvy_AB2->getEquates(-1))); // not referenceable here, only @parent
        $this->assertEquals(1, count($srvy_AB2a->getEquates(-1))); // local referenceable, but higher ref not referenceable
        
        // test line representation of those equates
        $lines = File_Therion_Line::filterNonEmpty($srvy_A->toLines());
        $this->assertEquals( // investigate output slice of lines
            array(
                "\t\tendcentreline",
                "\t\tequate 0.2 2.2@AB2.AB2a", // obsoleted by below
                "\t\tequate 0.3 0.1",
                "\t\tequate 1.2@AB1 2.2@AB2.AB2a",
                "\t\tequate 2.2@AB2.AB2a 1.2@AB1 0.2", // contains equate #1
                "\t\tsurvey AB1",
                
                // local equate of AB2a
                "\t\t\t\tendcentreline",
                "\t\t\t\tequate 2.3 2.4",
                "\t\t\tendsurvey AB2a",
            ),
            array(
                rtrim($lines[6]->toString()),
                rtrim($lines[7]->toString()),
                rtrim($lines[8]->toString()),
                rtrim($lines[9]->toString()),
                rtrim($lines[10]->toString()),
                rtrim($lines[11]->toString()),
                
                // local equate of AB2a
                rtrim($lines[24]->toString()),
                rtrim($lines[25]->toString()),
                rtrim($lines[26]->toString()),
            )
        );
    }
    
    
    /**
     * Test join parsing
     */
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