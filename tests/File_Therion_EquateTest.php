<?php
/**
 * Therion use cases test.
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
 * PHPUnit test class for File_Therion use cases.
 * 
 * This will test some realistic use cases.
 */
class File_Therion_EquateTest extends File_TherionTestBase {

  
/* ---------- TESTS ---------- */
/* test functions are public and start with "test*".
 
    
    /**
     * Parsing test
     */
    public function testParsing()
    {
        $testLines = array(
            'equate 1 2 3',
            'equate 1 2 3@A',
            'equate 1 2@A 3@B.A',
            'equate 1@A 2@B.A 3@C.B.A',
        );
        foreach ($testLines as $sL) {
            $sampleLine = File_Therion_Line::parse($sL);
            $sample = File_Therion_Equate::parse($sampleLine);
            $this->assertInstanceOf(
                'File_Therion_Equate', $sample, "testLine='".$sL."'");
            $this->assertEquals(3, count($sample),
                "testLine='".$sL."'"); // SPL: count equal stations
            $this->assertEquals($sL, $sample->toString(),
                "testLine='".$sL."'");
        }
        
    }
    
    
    /**
     * Test equating in object mode
     */
    public function testBasicUsage()
    {
        // basic instantiation
        $this->assertEquals(0, count(new File_Therion_Equate()));
        $this->assertEquals(0, count(new File_Therion_Equate(array())));
        
        // TODO
        $this->markTestIncomplete("This test has not been implemented yet.");
    }
    
    
    /**
     * Test OO functionality
     */
    public function testOOCapabilitysBasic()
    {
        $this->markTestIncomplete("This test has not been implemented yet.");
        
    }
    
    /**
     * Test OO functionality
     */
    public function testOOCapabilitysRenaming()
    {
        $this->markTestIncomplete("This test has not been implemented yet.");
    }
    
    /**
     * Test resolving of path
     */
    public function testContextResolving_NullCTX()
    {
        // craft basic samples
        $station1 = new File_Therion_Station("1");
        $station2 = new File_Therion_Station("2");
        $sample   = new File_Therion_Equate(array($station1, $station2));
        
        // no contexts should yield local result
        $this->assertEquals(
            array(),
            $sample->resolveStationPath($station1)
        );
        
        // also adding ctx to the equate should make no difference
        $sample->setSurveyContext(new File_Therion_Survey("surveyA"));
        $this->assertEquals(
            array(),
            $sample->resolveStationPath($station1)
        );
        
    }
    /**
     * Test resolving of path
     */
    public function testContextResolving_ValidCTX()
    {
        // craft basic samples
        $surveyA    = new File_Therion_Survey("surveyA");
        $surveyBatA = new File_Therion_Survey("surveyBatA");
        $surveyBatA->setParent($surveyA);
        
        $station1 = new File_Therion_Station("1");
        $station1->setSurveyContext($surveyBatA);
        $station2 = new File_Therion_Station("2");
        $station2->setSurveyContext($surveyA);
        
        $sample     = new File_Therion_Equate(array($station1, $station2));
        $sample->setSurveyContext($surveyA);
        
        // Station-1 is in subsurvey "surveyBatA"
        $this->assertEquals(
            array($surveyBatA),
            $sample->resolveStationPath($station1)
        );
        
        // Station-2 is local to equate context
        $this->assertEquals(
            array(),
            $sample->resolveStationPath($station2)
        );
        
        // when revoking the equate context, both should return the full path
        $sample->setSurveyContext(null);
        $this->assertEquals(
            array($surveyA, $surveyBatA),
            $sample->resolveStationPath($station1)
        );
        $this->assertEquals(
            array($surveyA),
            $sample->resolveStationPath($station2)
        );
        
    }
    /**
     * Test resolving of path
     */
    public function testContextResolving_invalid()
    {
        $surveyA     = new File_Therion_Survey("surveyA");
        $surveyBatA  = new File_Therion_Survey("surveyBatA");
        $surveyBatA->setParent($surveyA);
        
        $surveyOtherP = new File_Therion_Survey("surveyOtherParent");
        $surveyOtherC = new File_Therion_Survey("surveyOtherChild");
        
        $station1 = new File_Therion_Station("1");
        $station1->setSurveyContext($surveyBatA);
        $station2 = new File_Therion_Station("2");
        $station2->setSurveyContext($surveyOtherC);
        
        $sample     = new File_Therion_Equate(array($station1, $station2));
        $sample->setSurveyContext($surveyA);
        
        // station 2 should not be resolvable because its parent structure
        // is nowhere to be found in equate context
        $exception = null;
        try {
            $sample->resolveStationPath($station2);
        } catch (Exception $e) {
            $exception = $e;
        }
        $this->assertInstanceOf(
            'File_Therion_Exception', $exception,
            "File_Therion_Exception expected"
        );
    }
    
    /**
     * Test toString()
     */
    public function testToString_LocalOnly()
    {
        // Basic example with only local stations
        $station1 = new File_Therion_Station("1");
        $station2 = new File_Therion_Station("2");
        $sample   = new File_Therion_Equate(array($station1, $station2));
        
        $this->assertEquals("equate 1 2", $sample->toString());
    }
    /**
     * Test toString()
     */
    public function testToString_Substations()
    {
         // craft basic samples
        $surveyA    = new File_Therion_Survey("surveyA");
        $surveyBatA = new File_Therion_Survey("surveyBatA");
        $surveyBatA->setParent($surveyA);
        $surveyCatB = new File_Therion_Survey("surveyCatB");
        $surveyCatB->setParent($surveyBatA);
        
        $stationLocal0a = new File_Therion_Station("0a"); // null-ctx
        $stationLocal0b = new File_Therion_Station("0b");
        $stationLocal0b->setSurveyContext($surveyA);
        $station1 = new File_Therion_Station("1");
        $station1->setSurveyContext($surveyBatA);
        $station2 = new File_Therion_Station("2");
        $station2->setSurveyContext($surveyCatB);
        
        $sample = new File_Therion_Equate(
            array($stationLocal0a, $stationLocal0b, $station1, $station2));
        $sample->setSurveyContext($surveyA);
        
        // test with root context surveyA
        $this->assertEquals(
            "equate 0a 0b 1@surveyBatA 2@surveyBatA.surveyCatB",
            $sample->toString()
        );
        
        // reset equate CTX and test again, this should return FQSN to the top
        //   the only difference is the handling of station 0a which still has
        //   no context to walk upwards and is thus still local.
        $sample->setSurveyContext(null);
        $this->assertEquals(
            "equate 0a 0b@surveyA"
                ." 1@surveyA.surveyBatA 2@surveyA.surveyBatA.surveyCatB",
            $sample->toString()
        );
    }
}
?>