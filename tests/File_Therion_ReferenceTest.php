<?php
/**
 * Therion reference unit test cases
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
 * PHPUnit test class for testing Reference class.
 */
class File_Therion_ReferenceTest extends File_TherionTestBase {


/* ---------- TESTS ---------- */
/* test functions are public and start with "test*".
   
    
    /**
     * Test parsing
     */
    public function testBasicInstantiation()
    {
        // W/o Context in string mode
        $sample = new File_Therion_Reference("1@A", null);
        $this->assertEquals("1@A", $sample->toString());
        
        $sample = File_Therion_Reference::parse("1@A");
        $this->assertEquals("1@A", $sample->toString());
        
        
        //
        // wrong instantiation
        //
        $exception = null;
        try {
            $sample = new File_Therion_Reference("1@B", new Exception());
        } catch (Exception $e) {
            $exception = $e;
        }
        
        $this->assertInstanceOf('InvalidArgumentException', $exception);
        $exception = null;
        try {
            $sample = new File_Therion_Reference(new Exception(), null);
        } catch (Exception $e) {
            $exception = $e;
        }
        $this->assertInstanceOf('InvalidArgumentException', $exception);

    }
    
    /**
     * Test invalid resolving to string
     */
    public function testInvalidStringGeneration()
    {
        $surveyA  = new File_Therion_Survey("surveyA");
        $station1 = new File_Therion_Station("1");
        
        
        // without proper object ctx
        $ref       = new File_Therion_Reference($station1, $surveyA);
        $exception = null;
        try {
            $path = $ref->getSurveyPath();
        } catch (Exception $e) {
            $exception = $e;
        }
        $this->assertInstanceOf('UnexpectedValueException', $exception);
         
    }
    
    
    /**
     * Test fake structure resolving to string
     */
    public function testFakeStringGeneration()
    {
        // craft basic sample objects
        // survey containing other survey.
        $surveyA    = new File_Therion_Survey("surveyA");
        $surveyBatA = new File_Therion_Survey("surveyBatA");
        $surveyBatA->setParent($surveyA);
        
        // both surveys have associated stations
        $station = new File_Therion_Station("1");
        $station->setSurveyContext($surveyBatA);  // 1@surveyA.surveyBatA
        
        // fake resolving
        $ref = new File_Therion_Reference($station, null);
        $this->assertEquals("1@surveyA.surveyBatA", $ref->toString());
    }
    
    
    /**
     * Test generating of path
     */
    public function testPathRetrival()
    {
        // craft basic sample objects
        // survey containing other survey.
        $surveyA    = new File_Therion_Survey("surveyA");
        $surveyBatA = new File_Therion_Survey("surveyBatA");
        $surveyBatA->setParent($surveyA);
        $surveyCatB = new File_Therion_Survey("surveyCatB");
        $surveyCatB->setParent($surveyBatA);
        
        // both surveys have associated stations
        $station1 = new File_Therion_Station("1"); // 1@surveyA
        $station1->setSurveyContext($surveyA);
        $station2 = new File_Therion_Station("2");
        $station2->setSurveyContext($surveyBatA);  // 2@surveyA.surveyBatA
        $station3 = new File_Therion_Station("3");
        $station3->setSurveyContext($surveyCatB);  // 3@A.B.C
    
        
        // Reference: Station 1 viewed from surveyA
        //   -> expect local result
        $ref = new File_Therion_Reference($station1, $surveyA);
        $this->assertEquals(
            array(),       
            $ref->getSurveyPath()
        );
        $this->assertEquals("1", $ref->toString());
        
        // Reference: Station 2 viewed from surveyA
        //   -> expect nested result
        $ref = new File_Therion_Reference($station2, $surveyA);
        $this->assertEquals(
            array($surveyBatA),        
            $ref->getSurveyPath()
        );
        $this->assertEquals("2@surveyBatA", $ref->toString());
        
        // Reference: Station 1 viewed from surveyBatA
        //   -> expect resolving exception as its not reachable
        $ref       = new File_Therion_Reference($station1, $surveyBatA);
        $exception = null;
        try {
            $path = $ref->getSurveyPath();
        } catch (Exception $e) {
            $exception = $e;
        }
        $this->assertInstanceOf(
            'File_Therion_InvalidReferenceException',
            $exception);
        
        // Reference: Station 2 viewed from surveyBatA
        //   -> expect local result
        $ref = new File_Therion_Reference($station2, $surveyBatA);
        $this->assertEquals(
            array(),       // expect local result
            $ref->getSurveyPath()
        );
        $this->assertEquals("2", $ref->toString());
        
        // Reference: Station 3 viewed from surveyA
        //   -> expect nested result
        $ref = new File_Therion_Reference($station3, $surveyA);
        $this->assertEquals(
            array($surveyBatA, $surveyCatB),        
            $ref->getSurveyPath()
        );
        $this->assertEquals("3@surveyBatA.surveyCatB", $ref->toString());
        
    }
    
    
    /**
     * Test resolving of station objects from string reference
     */
    public function testStationResolving()
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
        
        $station2 = new File_Therion_Station("2"); // 2@surveyA.surveyBatA
        $surveyBatA->getCentrelines()[0]->addShot(
            new File_Therion_Shot($station2, "-") );
         
        $station3 = new File_Therion_Station("3"); // 3@A.B.C
        $surveyCatB->getCentrelines()[0]->addShot(
            new File_Therion_Shot($station3, "-") );
        
        
        //
        // craft string reference and try to dereference stations with it.
        //
        
        // local station 1 viewed from top survey
        $ref = new File_Therion_Reference("1", $surveyA);
        $obj = $ref->getObject();
        $this->assertEquals($station1, $obj);
        
        // nested station 2 viewed from top survey
        $ref = new File_Therion_Reference("2@surveyBatA", $surveyA);
        $obj = $ref->getObject();
        $this->assertEquals($station2, $obj);
        
        // nested station 3 viewed from top survey
        $ref = new File_Therion_Reference("3@surveyBatA.surveyCatB", $surveyA);
        $obj = $ref->getObject();
        $this->assertEquals($station3, $obj);
        
        // local station 2 viewed from surveyBatA
        $ref = new File_Therion_Reference("2", $surveyBatA);
        $obj = $ref->getObject();
        $this->assertEquals($station2, $obj);
        
        // nested station 3 viewed from surveyBatA
        $ref = new File_Therion_Reference("3@surveyCatB", $surveyBatA);
        $obj = $ref->getObject();
        $this->assertEquals($station3, $obj);
        
        // local station 3 viewed from surveyCatB
        $ref = new File_Therion_Reference("3", $surveyCatB);
        $obj = $ref->getObject();
        $this->assertEquals($station3, $obj);
        
        // undefined station: expect esception
        $ref = new File_Therion_Reference("undefined", $surveyA);
        $exception = null;
        try {
            $obj = $ref->getObject();
        } catch (Exception $e) {
            $exception = $e;
        }
        $this->assertInstanceOf(
            'File_Therion_InvalidReferenceException',
            $exception);
            
        // unreachable station: expect esception
        $ref = new File_Therion_Reference("1", $surveyBatA);
        $exception = null;
        try {
            $obj = $ref->getObject();
        } catch (Exception $e) {
            $exception = $e;
        }
        $this->assertInstanceOf(
            'File_Therion_InvalidReferenceException',
            $exception);
        
    }
    
}
?>