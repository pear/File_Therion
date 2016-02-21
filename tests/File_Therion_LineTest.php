<?php
/**
 * Therion cave survey unit test cases
 *
 * PHP version 5
 *
 * @category   file
 * @package    File_Therion
 * @author     Benedikt Hallinger <beni@php.net>
 * @copyright  2016 Benedikt Hallinger
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @link       http://pear.php.net/package/File_Therion/
 */
require_once 'File/Therion.php';  //includepath is loaded by phpUnit from phpunit.xml

/**
 * PHPUnit test class for File_Therion.
 */
class File_Therion_LineTest extends PHPUnit_Framework_TestCase
{

    /**
     * Basic test variables
     */
    protected $basic_test_string = "This is a simple test line";
       
    
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
     * Test basic instantiation
     */
    public function testBasicInstantiation()
    {
        $this->assertInstanceOf('File_Therion_Line', new File_Therion_Line(""));
                
        $sample = new File_Therion_Line("testdata");
        $this->assertInstanceOf('File_Therion_Line', $sample);
        
        $sample = new File_Therion_Line("testdata".PHP_EOL);
        $this->assertInstanceOf('File_Therion_Line', $sample);
        
        $sample = new File_Therion_Line("testdata", "with comment");
        $this->assertInstanceOf('File_Therion_Line', $sample);
        
        $sample = new File_Therion_Line("testdata", "with comment", "    "); // with indent
        $this->assertInstanceOf('File_Therion_Line', $sample);
    }
    
    /**
     * Test basic parsing
     */
    public function testBasicParsing()
    {
        $sample = File_Therion_Line::parse($this->basic_test_string);
        $this->assertInstanceOf('File_Therion_Line', $sample);
        
        $sample = File_Therion_Line::parse("This is a simpe test line   # with comment");
        $this->assertInstanceOf('File_Therion_Line', $sample);
        
        $sample = File_Therion_Line::parse("This is a simpe test line   # with comment".PHP_EOL);
        $this->assertInstanceOf('File_Therion_Line', $sample);
        
        $sample = File_Therion_Line::parse("    This is a simpe test line   # with comment and indent");
        $this->assertInstanceOf('File_Therion_Line', $sample);
        
    }
    
    
    /**
     * test instantiation/parsing and output
     */
    public function testParsingOutput_BasicInstantiation()
    {
        //
        // Basic instantiation
        //
        $sample = new File_Therion_Line("");
        $this->assertEquals("".PHP_EOL, $sample->toString());
        
        $sample = new File_Therion_Line("".PHP_EOL); // EOL in data
        $this->assertEquals("".PHP_EOL, $sample->toString());
        
        $sample = new File_Therion_Line("", "".PHP_EOL);  // EOL in comment
        $this->assertEquals("".PHP_EOL, $sample->toString());
        
        $sample = new File_Therion_Line("".PHP_EOL.PHP_EOL);  // too many EOL in data
        $this->assertEquals("".PHP_EOL, $sample->toString());
        
        $sample = new File_Therion_Line("", "".PHP_EOL.PHP_EOL);  // too many EOL in comment
        $this->assertEquals("".PHP_EOL, $sample->toString());
        
        $sample = new File_Therion_Line("".PHP_EOL.PHP_EOL, "".PHP_EOL.PHP_EOL);  // too many EOL in both
        $this->assertEquals("".PHP_EOL, $sample->toString());
        
        
        $sample = new File_Therion_Line($this->basic_test_string);
        $this->assertEquals($this->basic_test_string.PHP_EOL, $sample->toString());
        
        $sample = new File_Therion_Line($this->basic_test_string.PHP_EOL); // data was passed with line ending
        $this->assertEquals($this->basic_test_string.PHP_EOL, $sample->toString());
        
        
        // testing with comments;
        // with basic invocation, we want comments to be appended with a single tab.
        $sample = new File_Therion_Line($this->basic_test_string, "some comment");
        $this->assertEquals($this->basic_test_string."\t#some comment".PHP_EOL, $sample->toString());
        
        $sample = new File_Therion_Line($this->basic_test_string, "some comment", "    "); // with indent
        $this->assertEquals("    ".$this->basic_test_string."\t#some comment".PHP_EOL, $sample->toString());
        
        $sample = new File_Therion_Line($this->basic_test_string.PHP_EOL, "some comment", "    "); // with indent and EOL in data
        $this->assertEquals("    ".$this->basic_test_string."\t#some comment".PHP_EOL, $sample->toString());
        
        // testing the alternative comment separator
        $sample = new File_Therion_Line($this->basic_test_string.PHP_EOL, "some comment", "    "); // with indent
        $sample->setCommentSeparator("[---commentsep---]");
        $this->assertEquals("    ".$this->basic_test_string."[---commentsep---]#some comment".PHP_EOL, $sample->toString());
        
        
    }
    
    
    /**
     * test instantiation/parsing and output
     */
    public function testParsingOutput_usingParser()
    {
        //
        // using parser
        //
        $sample = File_Therion_Line::parse("");
        $this->assertEquals("".PHP_EOL, $sample->toString());
        
        $sample = File_Therion_Line::parse($this->basic_test_string);
        $this->assertEquals($this->basic_test_string.PHP_EOL, $sample->toString());
        
        $sample = File_Therion_Line::parse($this->basic_test_string.PHP_EOL); // with line ending
        $this->assertEquals($this->basic_test_string.PHP_EOL, $sample->toString());
        
        $sample = File_Therion_Line::parse("This is a simple test line   # with comment");
        $this->assertEquals("This is a simple test line   # with comment".PHP_EOL, $sample->toString());
        
        $sample = File_Therion_Line::parse("This is a simple test line   # with comment".PHP_EOL); // with line ending in input
        $this->assertEquals("This is a simple test line   # with comment".PHP_EOL, $sample->toString());
        
        $sample = File_Therion_Line::parse("This is a simple test line   # with comment".PHP_EOL.PHP_EOL); // with too much line ending in input
        $this->assertEquals("This is a simple test line   # with comment".PHP_EOL, $sample->toString());
        
        $sample = File_Therion_Line::parse("    This is a simple test line   # with comment and indent");
        $this->assertEquals("    This is a simple test line   # with comment and indent".PHP_EOL, $sample->toString());
        
        $sample = File_Therion_Line::parse("    This is a simple test line   # with comment and indent".PHP_EOL.PHP_EOL); // with too much line ending in input
        $this->assertEquals("    This is a simple test line   # with comment and indent".PHP_EOL, $sample->toString());
        
        // testing reset of comment sep
        $sample = File_Therion_Line::parse("    This is a simple test line   # with comment and indent");
        $sample->setCommentSeparator("[---commentsep---]");
        $this->assertEquals("    This is a simple test line[---commentsep---]# with comment and indent".PHP_EOL, $sample->toString());
        
        
        // testing of reset of indent
        $sample = File_Therion_Line::parse($this->basic_test_string);
        $sample->setIndent("....");
        $this->assertEquals("....".$this->basic_test_string.PHP_EOL, $sample->toString());
        $sample->setIndent("....".PHP_EOL);  // user made a mistake
        $this->assertEquals("....".$this->basic_test_string.PHP_EOL, $sample->toString());
    }
    
    
    /**
     * test multiline handling
     */
    public function testMultilineHandling()
    {
        // Simple test: does this line expect more lines?
        $sample = File_Therion_Line::parse("foo\\".PHP_EOL);
        $this->assertEquals("foo".PHP_EOL, $sample->toString());
        $this->assertTrue($sample->isContinued());
        $this->assertFalse($sample->isWrapped()); // does not yet have more physical lines
        
        // construct continued line and see if this gets recognized
        $secondLine = File_Therion_Line::parse("bar".PHP_EOL);
        $this->assertEquals("bar".PHP_EOL, $secondLine->toString());
        $this->assertFalse($secondLine->isContinued());
        $this->assertFalse($secondLine->isWrapped());
               
        $this->assertTrue($secondLine->isContinuation($sample));
        
              
        
        
        // Manully crafted: basic instantiation with parsing trick
        $sample = new File_Therion_Line("foo\\");
        $this->assertEquals("foo".PHP_EOL, $sample->toString());
        $this->assertTrue($sample->isContinued());
        $this->assertFalse($sample->isWrapped());
        
        // Manully crafted: basic instantiation with manual setting of state
        $sample = new File_Therion_Line("foo");
        $this->assertEquals("foo".PHP_EOL, $sample->toString());
        $this->assertFalse($sample->isContinued());
        $sample->expectMoreLines();
        $this->assertFalse($sample->isWrapped());
        $this->assertTrue($sample->isContinued());
        
        // ... now adding a second line
        $sample2 = new File_Therion_Line("bar");
        $this->assertEquals("bar".PHP_EOL, $sample2->toString());
        $sample->addPhysicalLine($sample2);
        $this->assertTrue($sample->isWrapped());     // true: now contains two physical lines
        $this->assertFalse($sample->isContinued());  // false, because the second line is no cont. itself
        $this->assertEquals("foobar".PHP_EOL, $sample->toString()); // just concatenated
        
        // The same, but whe forgetting to set state
        $sample = new File_Therion_Line("foo");
        $sample2 = new File_Therion_Line("bar");
        $sample->addPhysicalLine($sample2);
        $this->assertTrue($sample->isWrapped());
        $this->assertFalse($sample->isContinued());
        $this->assertEquals("foobar".PHP_EOL, $sample->toString());  // just concatenated
        
        // Threesome
        $sample  = new File_Therion_Line("foo".PHP_EOL); // one has an EOL!
        $sample2 = new File_Therion_Line("bar\\");  // one has an continuation sign already!
        $sample3 = new File_Therion_Line("baz");
        $sample->addPhysicalLine($sample2);
        $this->assertTrue($sample->isWrapped());
        $sample->addPhysicalLine($sample3);
        $this->assertFalse($sample->isContinued());
        $this->assertTrue($sample->isWrapped());
        $this->assertEquals("foobarbaz".PHP_EOL, $sample->toString());  // just concatenated
        
        
        // Test proper intendation
        // (we expect the first line intendation to apply to the generated one)
        $sample  = File_Therion_Line::parse("    FOO1".PHP_EOL);
        $this->assertFalse($sample->isWrapped());
        $sample2 = File_Therion_Line::parse("        FOO2".PHP_EOL);
        $sample3 = File_Therion_Line::parse("FOO3".PHP_EOL);
        $sample->addPhysicalLine($sample2);
        $this->assertTrue($sample->isWrapped());
        $sample->addPhysicalLine($sample3);
        $this->assertTrue($sample->isWrapped());
        $this->assertEquals("    FOO1FOO2FOO3".PHP_EOL, $sample->toString());  // just concatenated
        
        
        // Test with comments; we expect the comments joined
        $sample  = File_Therion_Line::parse("foo".PHP_EOL);
        $this->assertFalse($sample->isWrapped());
        $sample2 = File_Therion_Line::parse("bar          #c2".PHP_EOL);
        $sample3 = File_Therion_Line::parse("baz   #c3".PHP_EOL);
        $sample->addPhysicalLine($sample2);
        $sample->addPhysicalLine($sample3);
        $this->assertTrue($sample->isWrapped());
        $this->assertEquals("foobarbaz\t#c2; c3".PHP_EOL, $sample->toString());  // just concatenated
        
        
        // Test with comments and intendation; we expect the comments joined and intendation of first line
        $sample  = File_Therion_Line::parse("    foo".PHP_EOL);
        $this->assertFalse($sample->isWrapped());
        $sample2 = File_Therion_Line::parse("        bar #c2".PHP_EOL);
        $sample3 = File_Therion_Line::parse("baz    #c3".PHP_EOL);
        $sample->addPhysicalLine($sample2);
        $sample->addPhysicalLine($sample3);
        $this->assertTrue($sample->isWrapped());
        $this->assertEquals("    foobarbaz\t#c2; c3".PHP_EOL, $sample->toString());  // just concatenated
        
        // now try to readjust commentsep
        $sample->setCommentSeparator("          ");
        $this->assertEquals("    foobarbaz          #c2; c3".PHP_EOL, $sample->toString());  // just concatenated
        
        // testing of reset of indent
        $sample->setIndent("....");
        $this->assertEquals("....foobarbaz          #c2; c3".PHP_EOL, $sample->toString());  // just concatenated
        
    }
    
    
    
    
    /**
     * test counting
     */
    public function testCounting()
    {
        $sample = new File_Therion_Line("foo");
        $this->assertEquals(1, $sample->count()); // counting
        $this->assertEquals(1, count($sample));   // SPL interface
        
        $sample2 = new File_Therion_Line("bar");
        $sample->addPhysicalLine($sample2);
        $this->assertEquals(2, $sample->count()); // counting
        $this->assertEquals(2, count($sample));   // SPL interface
        
        $sample3 = new File_Therion_Line("baz");
        $sample->addPhysicalLine($sample3);
        $this->assertEquals(3, $sample->count()); // counting
        $this->assertEquals(3, count($sample));   // SPL interface
        
    }


}
?>
