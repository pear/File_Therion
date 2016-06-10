<?php
/**
 * Therion cave writer unit test cases
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
 * PHPUnit test class for File_Therion writers.
 */
class File_TherionWriterTest extends File_TherionTestBase {


/* ---------- TESTS ---------- */
/* test functions are public and start with "test*". */

    public function testDefaultWriter()
    {
        $srcFile = $this->testdata_base_therion.'/basics/rabbit.th';
        $th = File_Therion::parse($srcFile, 0);
        
        // test console writer (dumps content to terminal)
        // (this could be handy if i want to inspect generated content of file)
        //$th->write(new File_Therion_ConsoleWriter());
        
        // setup clean outfile
        $tgtFile = $this->testdata_base_out.'/directWriter.rabbit.th';
        if (file_exists($tgtFile)) unlink($tgtFile); // clean outfile
        
        // write!
        $th->setFilename($tgtFile);
        $th->write(); // implicit default writer
        
        
        // check if input file equals output file
        // TODO: There is currently one newline too much produced.
        //       This should be investigated... but is not critical.
        $srcData = file($srcFile);
        $tgtData = file($tgtFile);
        // Adjust for the TODO bug
        $this->assertEquals(count($srcData)+1, count($tgtData), // bug: one too
            "Newline-too-much-bug probably fixed?");       // much newline...
        $lastTGTLine = array_pop($tgtData);        // ...(verify that it really
        $this->assertEquals("\n", $lastTGTLine);   //     was a newline there!)
        
        $this->assertEquals($srcData, $tgtData); // check that content is same

    }
    
    public function testStructuredWriter()
    {
        $srcFile = $this->testdata_base_therion.'/basics/rabbit.th';
        $th = new File_Therion($srcFile);
        $th->fetch();
        $th->evalInputCMD();
        $th->addLine(new File_Therion_Line("# Custom test file header"), 'start');
        
        // add another test survey as raw lines
        $testlines = array(
            "survey foobar",
            "  # just an empty test survey...",
            "  ",
            "  # here comes a scrap:",
            "  scrap fooScrap1 -scale [295.0 203.0 995.0 207.5 0.0 0.0 0 -36 m]",
            "    point 629.0 269.5 debris",
            "  endscrap",
            "  # end of scrap data",
            "endsurvey"
        );
        foreach (array_reverse($testlines) as $l) {
            $th->addLine(new File_Therion_Line($l), 4);
        }
        
        // test console writer (dumps content to terminal)
        // (this could be handy if i want to inspect generated content of file)
        //$th->write(new File_Therion_ConsoleWriter());
        
        // setup clean outfile
        $tgtFile = $this->testdata_base_out.'/structuredWriter/index.th';
        if (file_exists($tgtFile)) unlink($tgtFile); // clean outfile
        
        $writer = new File_Therion_StructuredWriter();
        
        // write!
        $th->setFilename($tgtFile);
        $th->write($writer);
        
        // other future test ideas:
        //$writer->changeTemplate('File_Therion_Survey', '$(base)/therion/$(name).th');
        //$writer->changeTemplate('File_Therion_Scrap', '$(root)/scraps/$(parent)/$(name).th2');
        
        /*
         * Test results
         */
        $expectedFiles = array(
            '/structuredWriter/index.th',
            '/structuredWriter/rabbit/',
            '/structuredWriter/rabbit/rabbit.th',
            '/structuredWriter/rabbit/rabbit.th2',
            '/structuredWriter/rabbit/foobar',
            '/structuredWriter/rabbit/foobar/foobar.th',
            '/structuredWriter/rabbit/foobar/foobar.th2',
        );
        foreach ($expectedFiles as $fl) {
            $this->assertTrue(
                file_exists($this->testdata_base_out.$fl),
                "file exists: ".$fl);
        }
        
        $this->markTestIncomplete("TODO: implement content checking");

    }

}
?>