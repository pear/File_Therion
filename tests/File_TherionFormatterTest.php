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
class File_Therion_FormatterTest extends File_TherionTestBase {


/* ---------- TESTS ---------- */
/* test functions are public and start with "test*".

    
    /**
     * Test simple formatting interface.
     */
    public function testSimpleFormatting()
    {
        // some basic data
        $sampleLines = array(
            File_Therion_Line::parse('survey test'),
            File_Therion_Line::parse('  centreline'),
            File_Therion_Line::parse('    units compass clino grads'),
            File_Therion_Line::parse('    data normal from to compass clino tape'),
            File_Therion_Line::parse('    0     1   200       -5      6.4 '),
            File_Therion_Line::parse('    1     2    73        8      5.2 '),
            File_Therion_Line::parse('    2     3    42        0      2.09'),
            File_Therion_Line::parse('  endcentreline'),
            File_Therion_Line::parse('endsurvey'), 
        );
        $sample = new File_Therion("in_memory_testfile");
        foreach ($sampleLines as $l) {
            $sample->addLine($l);
        }
        
        $testFormatter = new File_Therion_DummyFormatter();
        $sample->addFormatter($testFormatter);
        
        // First pass should return formatters apllied
        $this->assertEquals(
            array(
                '0: survey test <EOL>',
                '1:   centreline <EOL>',
                '2:     units compass clino grads <EOL>',
                '3:     data normal from to compass clino tape <EOL>',
                '4:     0     1   200       -5      6.4  <EOL>',
                '5:     1     2    73        8      5.2  <EOL>',
                '6:     2     3    42        0      2.09 <EOL>',
                '7:   endcentreline <EOL>',
                '8: endsurvey <EOL>',
                ''  // no clue what this lone newline here is
            ),
            preg_split("/(\\r\\n)|\\r|\\n/", $sample->toString())
        );
        
        // second pass should reapply a second time (as we did not made a copy)
        $this->assertEquals(
            array(
                '0: 0: survey test <EOL> <EOL>',
                '1: 1:   centreline <EOL> <EOL>',
                '2: 2:     units compass clino grads <EOL> <EOL>',
                '3: 3:     data normal from to compass clino tape <EOL> <EOL>',
                '4: 4:     0     1   200       -5      6.4  <EOL> <EOL>',
                '5: 5:     1     2    73        8      5.2  <EOL> <EOL>',
                '6: 6:     2     3    42        0      2.09 <EOL> <EOL>',
                '7: 7:   endcentreline <EOL> <EOL>',
                '8: 8: endsurvey <EOL> <EOL>',
                ''  // no clue what this lone newline here is
            ),
            preg_split("/(\\r\\n)|\\r|\\n/", $sample->toString())
        );
        
        /* NOW CLEAR FORMATTERS */
        $sample->clearFormatters();
        
        // next pass should yoield same result
        $this->assertEquals(
            array(
                '0: 0: survey test <EOL> <EOL>',
                '1: 1:   centreline <EOL> <EOL>',
                '2: 2:     units compass clino grads <EOL> <EOL>',
                '3: 3:     data normal from to compass clino tape <EOL> <EOL>',
                '4: 4:     0     1   200       -5      6.4  <EOL> <EOL>',
                '5: 5:     1     2    73        8      5.2  <EOL> <EOL>',
                '6: 6:     2     3    42        0      2.09 <EOL> <EOL>',
                '7: 7:   endcentreline <EOL> <EOL>',
                '8: 8: endsurvey <EOL> <EOL>',
                ''  // no clue what this lone newline here is
            ),
            preg_split("/(\\r\\n)|\\r|\\n/", $sample->toString())
        );
        
       
    }
    
    /**
     * Test formatting stack usage.
     */
    public function testSimpleStackedFormatting()
    {
        // some basic data
        $sampleLines = array(
            File_Therion_Line::parse('survey test'),
            File_Therion_Line::parse('  centreline'),
            File_Therion_Line::parse('    units compass clino grads'),
            File_Therion_Line::parse('    data normal from to compass clino tape'),
            File_Therion_Line::parse('    0     1   200       -5      6.4 '),
            File_Therion_Line::parse('    1     2    73        8      5.2 '),
            File_Therion_Line::parse('    2     3    42        0      2.09'),
            File_Therion_Line::parse('  endcentreline'),
            File_Therion_Line::parse('endsurvey'), 
        );
        $sample = new File_Therion("in_memory_testfile");
        foreach ($sampleLines as $l) {
            $sample->addLine($l);
        }
        
        $testFormatter = new File_Therion_AnotherDummyFormatter();
        $sample->addFormatter($testFormatter);
        $sample->addFormatter($testFormatter);
        
        // First pass should return two formatters apllied
        $this->assertEquals(
            array(
                'survey test..',
                '  centreline..',
                '    units compass clino grads..',
                '    data normal from to compass clino tape..',
                '    0     1   200       -5      6.4 ..',
                '    1     2    73        8      5.2 ..',
                '    2     3    42        0      2.09..',
                '  endcentreline..',
                'endsurvey..',
                ''  // no clue what this lone newline here is
            ),
            preg_split("/(\\r\\n)|\\r|\\n/", $sample->toString())
        );
        
       
    }
    
    /**
     * Test BasicFormatter
     */
    public function testBasicFormatter()
    {
        // some basic data
        $sampleLines = array(
            File_Therion_Line::parse('survey test'),
            File_Therion_Line::parse('  survey foo'),
            File_Therion_Line::parse('    centreline'),
            File_Therion_Line::parse('        0 1 200 -5 6.4'),
            File_Therion_Line::parse('    endcentreline'),
            File_Therion_Line::parse('  endsurvey foo'),
            File_Therion_Line::parse('  centreline'),
            File_Therion_Line::parse('    units compass clino grads'),
            File_Therion_Line::parse('    data normal from to compass clino tape'),
            File_Therion_Line::parse('    1.0     1.1   200.32       -50.43      126.4 '),
            File_Therion_Line::parse('    1.1  1.212 73.1  8.2    5.2 '),
            File_Therion_Line::parse('    1.212     3    42        0      2.09'),
            File_Therion_Line::parse('    flags surface'),
            File_Therion_Line::parse('  endcentreline'),
            File_Therion_Line::parse('endsurvey'), 
        );
        $sample = new File_Therion("in_memory_testfile");
        foreach ($sampleLines as $l) {
            $sample->addLine($l);
        }
        
        $testFormatter = new File_Therion_BasicFormatter();
        $testFormatter->setIndent(".");
        //$testFormatter->setCenterlineDataTemplate("%1s");
        $testFormatter->setCenterlineSeparatorTemplate("-");
        $sample->addFormatter($testFormatter);
        
        
        $this->assertEquals(
            array(
                'survey test',
                '.survey foo',
                '..centreline',
                '...     0-     1-   200-    -5-   6.4',
                '..endcentreline',
                '.endsurvey foo',
                '.centreline',
                '..units compass clino grads',
                '..data normal from to compass clino tape',
                '..   1.0-   1.1-200.32--50.43- 126.4',
                '..   1.1- 1.212-  73.1-   8.2-   5.2',
                '.. 1.212-     3-    42-     0-  2.09',
                '..flags surface',
                '.endcentreline',
                'endsurvey',
                ''
            ),
            preg_split("/(\\r\\n)|\\r|\\n/", $sample->toString())
        );
    }
    
    /**
     * Test writing with formatter
     */
    public function testFormattedWriting()
    {
        // read in rabbit cave example
        $srcFile = $this->testdata_base_therion.'/basics/rabbit.th';
        $th = File_Therion::parse($srcFile, 0);
        
        // prepare out file
        $tgtFile = $this->testdata_base_out.'/directWriter.basicFormatter.rabbit.th';
        $th->setFilename($tgtFile);
        if (file_exists($tgtFile)) unlink($tgtFile); // clean outfile
        
        // attach formatter and write to target
        $formatter=new File_Therion_BasicFormatter();
        $th->addFormatter($formatter);
        $th->write();
        
        $srcData = file($srcFile);
        $tgtData = file($tgtFile);
        
        // We expect the content to not be the same.
        // TODO: Way better testing needed than this!
        $this->assertNotEquals($srcData, $tgtData); // check that content is same
        
    }

}


/**
 * A simple dummy formatter, just for testing purposes.
 * 
 * It appends "EOL" to every line and prepends it by line number.
 */
class File_Therion_DummyFormatter extends File_Therion_AddLineNumberFormatter
{
    public function format($lines) {
        
        // format using parent formatter.
        // (note this is adifferent apporach than using a formatter collection)
        $lines = parent::format($lines); 
        
        for ($i=0; $i<count($lines); $i++) {
            $l =& $lines[$i];
            $l->setContent($l->getContent()." <EOL>");
        }
        return $lines;
    }
    
}

/**
 * A nother simple dummy formatter, just for testing purposes again.
 * 
 * It appends "." to every line.
 */
class File_Therion_AnotherDummyFormatter implements File_Therion_Formatter
{
    public function format($lines) {
        for ($i=0; $i<count($lines); $i++) {
            $l =& $lines[$i];
            $l->setContent($l->getContent().".");
        }
        return $lines;
    }
    
}
?>