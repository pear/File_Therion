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