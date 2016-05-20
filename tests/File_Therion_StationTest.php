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
    
    
    /**
     * Test equates basic operation
     */
    public function testEquatesBasicOperation()
    {
        $station1 = new File_Therion_Station("1");
        $station2 = new File_Therion_Station("2");
        $station3 = new File_Therion_Station("3");
        
        $this->assertEquals(array(), $station1->getEquates());
        $station1->addEquate($station2);
        $this->assertEquals(array($station2), $station1->getEquates());
        $station1->addEquate($station3);
        $this->assertEquals(array($station2, $station3), $station1->getEquates());
        $station1->clearEquates();
        $this->assertEquals(array(), $station1->getEquates());
        
        // test duplicates
        $station1->addEquate($station2);
        $station1->addEquate($station2);
        $station1->addEquate($station2);
        $this->assertEquals(array($station2), $station1->getEquates());
        
        
        /*
        *  wrong invocations:
        */
        $exc = null;
        try {
            $start->addEquate(null);
        } catch (Exception $e) {
            $exc = $e;
        }
        $this->assertInstanceOf('Exception', $exc);
        
        $exc = null;
        try {
            $start->addEquate(array("foo", "bar"));
        } catch (Exception $e) {
            $exc = $e;
        }
        $this->assertInstanceOf('Exception', $exc);
        
        $exc = null;
        try {
            $start->addEquate(new File_Therion_Centreline());
        } catch (Exception $e) {
            $exc = $e;
        }
        $this->assertInstanceOf('Exception', $exc);
        
    }
    
    /**
     * Test station name aliasing
     */
    public function testStationName()
    {
        $station = new File_Therion_Station("1");
        $this->assertEquals('1', $station->getName(false));
        $this->assertEquals('1', $station->getName(true));
        $this->assertEquals('1', $station->getName());
        
        $station->setStationNames("pre", null);
        $this->assertEquals('1', $station->getName(false));
        $this->assertEquals('pre1', $station->getName(true));
        $this->assertEquals('pre1', $station->getName());
        
        $station->setStationNames(null, "post");
        $this->assertEquals('1', $station->getName(false));
        $this->assertEquals('pre1post', $station->getName(true));
        $this->assertEquals('pre1post', $station->getName());
        
        $station->setStationNames("foo", null);
        $this->assertEquals('1', $station->getName(false));
        $this->assertEquals('foo1post', $station->getName(true));
        $this->assertEquals('foo1post', $station->getName());
        
        $station->setStationNames("bar", "baz");
        $this->assertEquals('1', $station->getName(false));
        $this->assertEquals('bar1baz', $station->getName(true));
        $this->assertEquals('bar1baz', $station->getName());
        
        $station->setStationNames("", "");
        $this->assertEquals('1', $station->getName(false));
        $this->assertEquals('1', $station->getName(true));
        $this->assertEquals('1', $station->getName());
        
        $station->setStationNames("bar", "baz");
        $station->applyStationNames();
        $this->assertEquals('bar1baz', $station->getName(false));
        $this->assertEquals('bar1baz', $station->getName(true));
        $this->assertEquals('bar1baz', $station->getName());
        
        $station->setStationNames("bar", "baz");
        $station->stripStationNames();
        $this->assertEquals('1', $station->getName(false));
        $this->assertEquals('bar1baz', $station->getName(true));
        $this->assertEquals('bar1baz', $station->getName());
        
    }
    
    /**
     * Test equates basic operation (backlinking)
     */
    public function testEquatesBasicOperationBacklink()
    {
        $station1 = new File_Therion_Station("1");
        $station2 = new File_Therion_Station("2");
        $station3 = new File_Therion_Station("3");
        
        // test backlinking
        $station1->addEquate($station2);
        $this->assertEquals(array($station1), $station2->getEquates());
        $station1->addEquate($station3);
        $this->assertEquals(array($station1), $station3->getEquates());
        $station1->clearEquates($station3);
        $this->assertEquals(array($station2), $station1->getEquates());
        $this->assertEquals(array(), $station3->getEquates());
        
        // test with skipping backlink
        $station1->addEquate($station3, true);
        $this->assertEquals(array(), $station3->getEquates());
        $station3->addEquate($station1, true);
        $this->assertEquals(array($station1), $station3->getEquates());
        $station1->clearEquates($station3, true);
        $this->assertEquals(array($station1), $station3->getEquates());
        $station3->clearEquates();
        $this->assertEquals(array(), $station3->getEquates());
        $this->assertEquals(array($station2), $station1->getEquates());
        $station1->clearEquates();
        $this->assertEquals(array(), $station1->getEquates());
        $this->assertEquals(array(), $station2->getEquates());
    }
    
    /**
     * Test equates
     */
    public function testEquatesSimple()
    {
        // craft basic sample objects
        // survey containing other survey.
        $surveyA    = new File_Therion_Survey("surveyA");
        $surveyA->addCentreline(new File_Therion_Centreline());
        
        $surveyBatA = new File_Therion_Survey("surveyBatA");
        $surveyBatA->addCentreline(new File_Therion_Centreline());
        $surveyA->addSurvey($surveyBatA);
        
        $surveyCatB = new File_Therion_Survey("surveyCatB");
        $surveyCatB->addCentreline(new File_Therion_Centreline());
        $surveyBatA->addSurvey($surveyCatB);
        
        // both surveys have associated stations
        $station1 = new File_Therion_Station("1"); // 1@surveyA
        $surveyA->getCentrelines()[0]->addShot(
            new File_Therion_Shot($station1, "-") );
        $station1b = new File_Therion_Station("1b"); // 1b@surveyA
        $surveyA->getCentrelines()[0]->addShot(
            new File_Therion_Shot($station1, $station1b) );
        
        $station2 = new File_Therion_Station("2"); // 2@surveyA.surveyBatA
        $surveyBatA->getCentrelines()[0]->addShot(
            new File_Therion_Shot($station2, "-") );
        $station2b = new File_Therion_Station("2b"); // 2b@surveyA.surveyBatA
        $surveyBatA->getCentrelines()[0]->addShot(
            new File_Therion_Shot($station2, $station2b) );
         
        $station3 = new File_Therion_Station("3"); // 3@A.B.C
        $surveyCatB->getCentrelines()[0]->addShot(
            new File_Therion_Shot($station3, "-") );
        $station3b = new File_Therion_Station("3b"); // 3b@A.B.C
        $surveyCatB->getCentrelines()[0]->addShot(
            new File_Therion_Shot($station3, $station3b) );
            
        
        
        /*
         * Test simple equateing of local stations
         */
        $this->assertEquals("", $station1->toEquateString()); // none so far
        $station1->addEquate($station1b);
        $this->assertEquals(
            "equate 1 1b",
            $station1->toEquateString()
        );
        $station1->addEquate($station2);
        $this->assertEquals(
            "equate 1 1b 2@surveyBatA",
            $station1->toEquateString()
        );
        
        // add in subsurvey; but one of the stations (1) is not reachable
        $station2b->addEquate($station1);
        $station2b->addEquate($station2);
        $station2b->addEquate($station3);
        $this->assertEquals(
            "equate 2b 2 3@surveyCatB", // 1 is silently ommitted
            $station2b->toEquateString()
        );
        
        // test higher view context
        $this->assertEquals(
            "equate 2b@surveyBatA 1 2@surveyBatA 3@surveyBatA.surveyCatB",
            //                    ^--- 1 is visible again!     ^
            //                   deeper context resolved ------|
            $station2b->toEquateString($surveyA)
        );
        
        // test lower view context
        $this->assertEquals(
            "",       // only 3 is local visible, thus no result
            $station2b->toEquateString($surveyCatB)
        );
    }
    
}
?>