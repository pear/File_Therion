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
 * PHPUnit test class for File_Therion.
 */
class File_TherionTest extends PHPUnit_Framework_TestCase {

    /**
     * Base location of test data (therion distribution)
     * 
     * @var string
     */
    protected $testdata_base_therion = __DIR__.'/data/samples_therion/';
    
    /**
     * Base location of test data (own samples)
     * 
     * @var string
     */
    protected $testdata_base_own = __DIR__.'/data/samples_own/';
    
    
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
     * Test addLine variants
     */
    public function testAddLine()
    {
        $sample = new File_Therion("no_file");
        $this->assertEquals(array(), $sample->getLines());
        
        // expect exception in case of wrong parameter
        $exception = null;
        try {
            $sample = new File_Therion("no_file");
            $sample->addLine("string");
        } catch (Exception $e) {
            $exception = $e;
        }
        $this->assertInstanceOf('InvalidArgumentException', $exception);
        
        $exception = null;
        try {
            $sample = new File_Therion("no_file");
            $sample->addLine(new File_Therion_Line("some content"), "notAnInt");
        } catch (Exception $e) {
             $exception = $e;
        }
        $this->assertInstanceOf('InvalidArgumentException', $exception);
                
        
        // adding a line
        $sample = new File_Therion("no_file");
        $sample->addLine(new File_Therion_Line("some content"));
        $this->assertEquals(1, count($sample));
        
        // adding many lines
        $sample = new File_Therion("no_file");
        for ($i=0; $i<500; $i++) {
            $sample->addLine(new File_Therion_Line("some content: $i"));
        }
        $this->assertEquals(500, count($sample));
        
        // check that order stays constant:
        $lines = $sample->getLines();
        for ($i=0; $i<500; $i++) {
            $this->assertEquals(1, preg_match("/^some content: $i$/", $lines[$i]->toString()));
        }
        
        
        // Check line continuation
        $sample = new File_Therion("no_file");
        $sample->addLine(new File_Therion_Line('some content: \\'));
        $this->assertEquals(1, count($sample->getLines()));
        $this->assertNotNull($sample->getLines()[0]);
        $this->assertEquals(
            'some content: '.PHP_EOL,
            $sample->getLines()[0]->toString());
        $this->assertTrue($sample->getLines()[0]->isContinued());
        $sample->addLine(new File_Therion_Line('1\\'));
        $this->assertEquals(1, count($sample->getLines()));
        $this->assertNotNull($sample->getLines()[0]);
        $this->assertEquals(
            'some content: 1'.PHP_EOL,
            $sample->getLines()[0]->toString());
        $this->assertTrue($sample->getLines()[0]->isContinued());
        $sample->addLine(new File_Therion_Line('2'));
        $this->assertEquals(1, count($sample->getLines()));
        $this->assertNotNull($sample->getLines()[0]);
        $this->assertEquals(
            'some content: 12'.PHP_EOL,
            $sample->getLines()[0]->toString());
        $this->assertFalse($sample->getLines()[0]->isContinued());
        
        
        
        // Check insertion mode
        // we expect the inserted line at index 3(=line 4), pushing existing content down.
        // Insert @-1=END, @0=start, @1=after 1, etc
        $sample = new File_Therion("no_file");
        for ($i=0; $i<6; $i++) {
            $sample->addLine(new File_Therion_Line("some content: ".($i+1).""));
        }
        $this->assertEquals(6, count($sample));
        $sample->addLine(new File_Therion_Line("some content: INSERTED"), 3);
        $this->assertEquals(7, count($sample));
        $i = 0;
        foreach ($sample->getLines() as $l) {
            if ($i == 3-1 && !stristr($tp, "INSERTED")) {
                // expect something else at that line!
                $tp = "/^some content: INSERTED$/";
                $i--; // because we inserted; will be adjustet to correct value below
            } else {
                 $tp = "/^some content: ".($i+1)."$/";
            }
            $this->assertEquals(1, preg_match($tp, $l->toString()));
            $i++;
        }
        
        // Check insertion mode WITH REPLACING
        // we expect the replaced line at index 3, pushing existing content down.
        // Insert @-1=END, @0=start, @1=after 1, etc
        $sample = new File_Therion("no_file");
        for ($i=0; $i<6; $i++) {
            $sample->addLine(new File_Therion_Line("some content: ".($i+1).""));
        }
        $this->assertEquals(6, count($sample));
        $sample->addLine(new File_Therion_Line("some content: REPLACED"), 3, true);
        $this->assertEquals(6, count($sample));
        $i = 0;
        foreach ($sample->getLines() as $l) {
            if ($i == 2 && !stristr($tp, "REPLACED")) {
                // expect something else at that line!
                $tp = "/^some content: REPLACED/";
                //$i--; COUNT ONWARDS because we replaced
            } else {
                 $tp = "/^some content: ".($i+1)."$/";
            }
            $this->assertEquals(1, preg_match($tp, $l->toString()));
            $i++;
        }

        
        // Test insertion at START
        // We expect that the inserted line will be the first line, pushing
        // the old-first one down
        $sample = new File_Therion("no_file");
        $this->assertEquals(array(), $sample->getLines());
        $sample->addLine(new File_Therion_Line("some content: 0"));
        $sample->addLine(new File_Therion_Line("some content: 1"));
        $sample->addLine(new File_Therion_Line("some content: INSERTED"), 0);
        $lines = $sample->getLines();
        $this->assertEquals(3, count($lines));
        $this->assertEquals("some content: INSERTED", trim($lines[0]->toString()));
        $this->assertEquals("some content: 0", trim($lines[1]->toString()));
        $this->assertEquals("some content: 1", trim($lines[2]->toString()));
        
        // Test insertion at END (forced so)
        $sample = new File_Therion("no_file");
        $this->assertEquals(array(), $sample->getLines());
        $sample->addLine(new File_Therion_Line("some content: 0"));
        $sample->addLine(new File_Therion_Line("some content: 1"));
        $sample->addLine(new File_Therion_Line("some content: INSERTED"), -1);
        $lines = $sample->getLines();
        $this->assertEquals(3, count($lines));
        $this->assertEquals("some content: 0", trim($lines[0]->toString()));
        $this->assertEquals("some content: 1", trim($lines[1]->toString()));
        $this->assertEquals("some content: INSERTED", trim($lines[2]->toString()));
        
        // Test replace at START
        $sample = new File_Therion("no_file");
        $this->assertEquals(array(), $sample->getLines());
        $sample->addLine(new File_Therion_Line("some content: 0"));
        $sample->addLine(new File_Therion_Line("some content: 1"));
        $sample->addLine(new File_Therion_Line("some content: REPLACED"), 0, true);
        $lines = $sample->getLines();
        $this->assertEquals(2, count($lines));
        $this->assertEquals("some content: REPLACED", trim($lines[0]->toString()));
        $this->assertEquals("some content: 1", trim($lines[1]->toString()));
        
        // Test replace at END
        $sample = new File_Therion("no_file");
        $this->assertEquals(array(), $sample->getLines());
        $sample->addLine(new File_Therion_Line("some content: 0"));
        $sample->addLine(new File_Therion_Line("some content: 1"));
        $sample->addLine(new File_Therion_Line("some content: REPLACED"), -1, true);
        $lines = $sample->getLines();
        $this->assertEquals(2, count($lines));
        $this->assertEquals("some content: 0", trim($lines[0]->toString()));
        $this->assertEquals("some content: REPLACED", trim($lines[1]->toString()));
        
        
        // test support for string shorthands
        $sample = new File_Therion("no_file");
        $sample->addLine(new File_Therion_Line("0"), "start");  // 0
        $sample->addLine(new File_Therion_Line("-1"), "start"); // -1, 0
        $sample->addLine(new File_Therion_Line("1"), "end"); // -1, 0, 1
        $sampleLines = $sample->getLines();
        $this->assertEquals(
            array(
                "-1",
                "0",
                "1"
            ),
            array(
                trim($sampleLines[0]->toString()),
                trim($sampleLines[1]->toString()),
                trim($sampleLines[2]->toString())
            )
        );
        
        
        // test adding with index bigger than valid
        $sample = new File_Therion("no_file");
        $this->assertEquals(array(), $sample->getLines());
        $sample->addLine(new File_Therion_Line("some content: 0"));
        $sample->addLine(new File_Therion_Line("some content: 1"));
        $exception = null;
        try {
            $sample->addLine(new File_Therion_Line("some content: 20"), 20);
        } catch (Exception $e) {
            $exception = $e;
        }
        $this->assertInstanceOf(
            'OutOfBoundsException', $exception,
            "OutOfBoundsException expected"
        );
        
        
        // test replacing with index bigger than valid
        $sample = new File_Therion("no_file");
        $this->assertEquals(array(), $sample->getLines());
        $sample->addLine(new File_Therion_Line("some content: 0"));
        $sample->addLine(new File_Therion_Line("some content: 1"));
        $exception = null;
        try {
            $sample->addLine(new File_Therion_Line("some content: 20"), 20, true);
        } catch (Exception $e) {
            $exception = $e;
        }
        $this->assertInstanceOf(
            'OutOfBoundsException', $exception,
            "OutOfBoundsException expected"
        );
        
    }
    
    
    
    /**
     * Test for extracting multiline commands
     */
    public function testExtractMultilineCMD()
    {
        // empty setup structure
        $sf = File_Therion::extractMultilineCMD(array());
        $this->assertFalse(array_key_exists('LOCAL', $sf));
        $this->assertFalse(array_key_exists('survey', $sf));
        $this->assertFalse(array_key_exists('centreline', $sf));
        $this->assertFalse(array_key_exists('scrap', $sf));
        
        // only LOCAL context
        $sampleLines = array(
            File_Therion_Line::parse('encoding UTF-8'),
            File_Therion_Line::parse('# some comment'),
        );
        $sf = File_Therion::extractMultilineCMD($sampleLines);
        $this->assertTrue(array_key_exists('LOCAL', $sf));
        $this->assertEquals(2, count($sf['LOCAL']));
        $this->assertFalse(array_key_exists('survey', $sf));
        $this->assertFalse(array_key_exists('centreline', $sf));
        $this->assertFalse(array_key_exists('scrap', $sf));
        
 
        // LOCAL+survey context
        $sampleLines = array(
            File_Therion_Line::parse('encoding UTF-8'),
            File_Therion_Line::parse('# some comment'),
            File_Therion_Line::parse('survey test'),
            File_Therion_Line::parse('  join ew1:0 ew2:end'),
            File_Therion_Line::parse('  # some othercomment'),
            File_Therion_Line::parse('endsurvey'),
        );
        $sf = File_Therion::extractMultilineCMD($sampleLines);
        $this->assertTrue(array_key_exists('LOCAL', $sf));
        $this->assertTrue(array_key_exists('survey', $sf));
        $this->assertEquals(2, count($sf['LOCAL']));
        $this->assertEquals(1, count($sf['survey']));     // one survey...
        $this->assertEquals(4, count($sf['survey'][0]));  // ...with 4 lines
        $this->assertFalse(array_key_exists('centreline', $sf));
        $this->assertFalse(array_key_exists('scrap', $sf));
        
        // LOCAL+survey context with trailing local data
        $sampleLines = array(
            File_Therion_Line::parse('encoding UTF-8'),
            File_Therion_Line::parse('# some comment'),
            File_Therion_Line::parse('survey test'),
            File_Therion_Line::parse('  join ew1:0 ew2:end'),
            File_Therion_Line::parse('  # some othercomment'),
            File_Therion_Line::parse('endsurvey'),
            File_Therion_Line::parse('# local again'),
        );
        $sf = File_Therion::extractMultilineCMD($sampleLines);
        $this->assertTrue(array_key_exists('LOCAL', $sf));
        $this->assertTrue(array_key_exists('survey', $sf));
        $this->assertEquals(3, count($sf['LOCAL']));
        $this->assertEquals(1, count($sf['survey']));     // one survey...
        $this->assertEquals(4, count($sf['survey'][0]));  // ...with 4 lines
        $this->assertFalse(array_key_exists('centreline', $sf));
        $this->assertFalse(array_key_exists('scrap', $sf));
        
        // LOCAL+2survey contexts
        $sampleLines = array(
            File_Therion_Line::parse('encoding UTF-8'),
            File_Therion_Line::parse('# some comment'),
            File_Therion_Line::parse('survey test'),
            File_Therion_Line::parse('  join ew1:0 ew2:end'),
            File_Therion_Line::parse('  # some othercomment'),
            File_Therion_Line::parse('endsurvey'),
            File_Therion_Line::parse('survey test-two'),
            File_Therion_Line::parse('  # some nice comment'),
            File_Therion_Line::parse('endsurvey'),
        );
        $sf = File_Therion::extractMultilineCMD($sampleLines);
        $this->assertTrue(array_key_exists('LOCAL', $sf));
        $this->assertTrue(array_key_exists('survey', $sf));
        $this->assertEquals(2, count($sf['LOCAL']));
        $this->assertEquals(2, count($sf['survey']));     // two survey...
        $this->assertEquals(4, count($sf['survey'][0]));  // ...1 with 4 lines
        $this->assertEquals(3, count($sf['survey'][1]));  // ...2 with 3 lines
        $this->assertFalse(array_key_exists('centreline', $sf));
        $this->assertFalse(array_key_exists('scrap', $sf));
 
 
        // Nested structure: Survey with subsurvey
        $sampleLines = array(
            File_Therion_Line::parse('survey test'),
            File_Therion_Line::parse('  join ew1:0 ew2:end'),
            File_Therion_Line::parse('  # some othercomment'),
            File_Therion_Line::parse('  survey subtest'),
            File_Therion_Line::parse('  endsurvey'),
            File_Therion_Line::parse('endsurvey'),
        );
        $sf = File_Therion::extractMultilineCMD($sampleLines);
        $this->assertFalse(array_key_exists('LOCAL', $sf));
        $this->assertTrue(array_key_exists('survey', $sf));
        $this->assertEquals(1, count($sf['survey']));     // one survey...
        $this->assertEquals(6, count($sf['survey'][0]));  // ...with 6 lines
        $this->assertFalse(array_key_exists('centreline', $sf));
        $this->assertFalse(array_key_exists('scrap', $sf));
        
        // Nested structure: Survey with centreline
        $sampleLines = array(
            File_Therion_Line::parse('survey test'),
            File_Therion_Line::parse('  join ew1:0 ew2:end'),
            File_Therion_Line::parse('  # some othercomment'),
            File_Therion_Line::parse('  centreline'),
            File_Therion_Line::parse('    data normal from to tape compass clino'),
            File_Therion_Line::parse('  endcentreline'),
            File_Therion_Line::parse('endsurvey'),
        );
        $sf = File_Therion::extractMultilineCMD($sampleLines);
        $this->assertFalse(array_key_exists('LOCAL', $sf));
        $this->assertTrue(array_key_exists('survey', $sf));
        $this->assertEquals(1, count($sf['survey']));     // one survey...
        $this->assertEquals(7, count($sf['survey'][0]));  // ...with 7 lines
        $this->assertFalse(array_key_exists('centreline', $sf));
        $this->assertFalse(array_key_exists('scrap', $sf));
        
        // Use this data now and investigate centreline.
        // we need to clip the first and last line (outhermost context)
        array_shift($sampleLines);
        array_pop($sampleLines);
        $sf_centreline = File_Therion::extractMultilineCMD($sampleLines);
        $this->assertTrue(array_key_exists('LOCAL', $sf_centreline));
        $this->assertTrue(array_key_exists('centreline', $sf_centreline));
        $this->assertEquals(2, count($sf_centreline['LOCAL']));
        $this->assertEquals(1, count($sf_centreline['centreline']));
        $this->assertEquals(3, count($sf_centreline['centreline'][0]));
    }
    
    
    /**
     * Test simple fetching of a th file
     */
    public function testSimpleFetching()
    {
        $th = new File_Therion($this->testdata_base_therion.'/basics/rabbit.th');
        $th->fetch();
        $this->assertEquals(74, count($th), "parsed line number does not match sample");
    }
    
    /**
     * Test simple recursively fetching of a th file
     */
    public function testSimpleFetching_recurse()
    {
        $th = new File_Therion($this->testdata_base_therion.'/basics/rabbit.th');
        $th->fetch();
        $th->evalInputCMD(); // interpret input commands (rabbit.th2)
        // expect those lines minus one nested occurence of "encoding"
        $this->assertEquals(74+936-1, count($th), "parsed line number does not match sample");
        
        // test recursing limit
        // @todo: this should be better tested with custom created nested data
        $th = new File_Therion($this->testdata_base_therion.'/basics/rabbit.th');
        $th->fetch();
        $th->evalInputCMD(1);
        // expect those lines minus one nested occurence of "encoding"
        $this->assertEquals(74+936-1, count($th), "parsed line number does not match sample");
        
        // test recursing limit
        // @todo: this should be better tested with custom created nested data
        $th = new File_Therion($this->testdata_base_therion.'/basics/rabbit.th');
        $th->fetch();
        $th->evalInputCMD(0);
        $this->assertEquals(74, count($th), "parsed line number does not match sample");
    }


    /**
     * Test simple parsing of a th file
     */
    public function testSimpleObjectUpdate()
    {
        // Fetch rabbit example
        $th = new File_Therion($this->testdata_base_therion.'/basics/rabbit.th');
        $th->fetch();
        $this->assertEquals(0, count($th->getSurveys()));
        $this->assertEquals(74, count($th), "parsed line number does not match sample");
        $this->assertEquals('ISO8859-2', $th->getEncoding());
        
        // parse file contents into php therion objects
        $th->updateObjects();
        $this->assertEquals(1, count($th->getSurveys()));
        
        // get survey and inspect it
        $survey = array_shift($th->getSurveys());
        $this->assertInstanceOf('File_Therion_Survey', $survey);
        $this->assertEquals("Rabbit Cave", $survey->getOption('title'));
        $this->assertEquals(0, count($survey->getSurveys()));
        $this->assertEquals(1, count($survey->getCentrelines()));
        $this->assertEquals(3, count($survey->getJoins()));
        $this->assertEquals(0, count($survey->getEquates()));
        $this->assertEquals(2, count($survey->getMaps()));
        $this->assertEquals(1, count($survey->getSurface()));

        // inspect centreline
        $centreline = array_shift($survey->getCentrelines());
        $this->assertInstanceOf('File_Therion_Centreline', $centreline);
        $this->assertEquals("1997.08.10", $centreline->getDate()->toString());
        $this->assertEquals(null, $centreline->getExploDate());
        $this->assertEquals(3, count($centreline->getTeam()));
        $this->assertEquals('"Martin Budaj"',
            $centreline->getTeam()[0]->toString());
            $this->assertEquals('"Miroslav Hofer"',
            $centreline->getTeam()[2]->toString());
        $this->assertEquals(0, count($centreline->getExploTeam()));
        $this->assertEquals(array(), $centreline->getExploTeam());
        
        
        // TODO: More to test for!
    }
    
    /**
     * Test basic parse recursion
     */
    public function testBasicParseRecursion()
    {
        // expect exception in case of wrong parameter
        $exception = null;
        try {
            $th = File_Therion::parse(
                $this->testdata_base_own.'/recursetest/cave.th', -1);
        } catch (Exception $e) {
            $exception = $e;
        }
        $this->assertInstanceOf(
            'InvalidArgumentException', $exception,
            "InvalidArgumentException expected"
        );
        $exception = null;
        try {
            $th = File_Therion::parse(
                $this->testdata_base_own.'/recursetest/cave.th', 'wrongParam');
        } catch (Exception $e) {
            $exception = $e;
        }
        $this->assertInstanceOf(
            'InvalidArgumentException', $exception,
            "InvalidArgumentException expected"
        );
        
        
        // Recurse 0-6 level
        for ($i=0; $i<=6; $i++) {
            $expectedCentrelines = ($i<=3)? $i+1 : 4; // expect max 4 CLs
            $recursor = $i;
            $th = File_Therion::parse(
                $this->testdata_base_own.'/recursetest/cave.th', $recursor);
    
            $this->assertEquals(1, count($th->getSurveys()));
            $this->assertEquals($i, $recursor, "Recursing variable touched!");
            $survey = array_shift($th->getSurveys());
            $this->assertEquals(
                $expectedCentrelines, count($survey->getCentrelines())
            );
        }
        
        // Recurse endlessly
        $th = File_Therion::parse($this->testdata_base_own.'/recursetest/cave.th');
        $this->assertEquals(1, count($th->getSurveys()));
        $survey = array_shift($th->getSurveys());
        $this->assertEquals(4, count($survey->getCentrelines()));
        
        // Recurse endlessly (explicitely)
        $th = File_Therion::parse($this->testdata_base_own.'/recursetest/cave.th', null);
        $this->assertEquals(1, count($th->getSurveys()));
        $survey = array_shift($th->getSurveys());
        $this->assertEquals(4, count($survey->getCentrelines()));
        
    }
    
    /**
     * Test parsing static factory
     */
    public function testParse()
    {
        // Basic parse: no recursion
        $th = File_Therion::parse($this->testdata_base_therion.'/basics/rabbit.th', 0);
        $this->assertEquals(1, count($th->getSurveys()));
        $survey = array_shift($th->getSurveys());
        $this->assertEquals(1, count($survey->getCentrelines()));
        $this->assertEquals(3, count($survey->getJoins()));
        $this->assertEquals(0, count($survey->getEquates()));
        $this->assertEquals(2, count($survey->getMaps()));
        $this->assertEquals(1, count($survey->getSurface()));
        $this->assertEquals(0, count($th->getScraps()));
        
        // Recursive parse: recurse endlessly
        $th = File_Therion::parse($this->testdata_base_therion.'/basics/rabbit.th');
        $this->assertEquals(1, count($th->getSurveys()));
        $survey = array_shift($th->getSurveys());
        $this->assertEquals(1, count($survey->getCentrelines()));
        $this->assertEquals(3, count($survey->getJoins()));
        $this->assertEquals(0, count($survey->getEquates()));
        $this->assertEquals(2, count($survey->getMaps()));
        $this->assertEquals(1, count($survey->getSurface()));
        $this->assertEquals(4, count($survey->getScraps()));
        
    }
    
    public function testEncodings()
    {
        // TODO Implement me
        $this->markTestIncomplete("This test has not been implemented yet.");
    }
    
    public function testWriter()
    {
        $th = File_Therion::parse($this->testdata_base_therion.'/basics/rabbit.th', 0);
        $th->setURL(__DIR__.'data/output/directWriter.rabbit.th');
        
        // test debug writer (dumps content to terminal)
        // (this could be handy if i want to inspect generated content of file)
        //$th->write(new File_Therion_DebugWriter());
        
        
        // TODO test direct writer
        //$th->write(); // implicit default writer
    }

}
?>
