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
 * PHPUnit test class for File_Therion_Line.
 */
class File_Therion_LineTest extends File_TherionTestBase
{

    /**
     * Basic test variables
     */
    protected $basic_test_string = "This is a simple test line";


/* ---------- TESTS ---------- */
/* test functions are public and start with "test*".

    
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
        
        
        $sample = File_Therion_Line::parse("#just a comment");
        $this->assertInstanceOf('File_Therion_Line', $sample);
        
        $sample = File_Therion_Line::parse("    #just a comment with indent");
        $this->assertInstanceOf('File_Therion_Line', $sample);
        
        $sample = File_Therion_Line::parse("     ");   //just a blank line
        $this->assertInstanceOf('File_Therion_Line', $sample);
        
    }
    
    /**
     * test comment and indenting
     */
    public function testCommentingAndIndentation()
    {
        $sample = File_Therion_Line::parse("#just a comment");
        $this->assertEquals("#just a comment".PHP_EOL, $sample->toString());
        
        $sample = File_Therion_Line::parse("    #just a comment with indent");
        $this->assertEquals("    #just a comment with indent".PHP_EOL, $sample->toString());
        
        $sample = File_Therion_Line::parse("     ");   //just a blank line
        $this->assertEquals("     ".PHP_EOL, $sample->toString());
    }
    
    /**
     * test recognition of "only comment lines"
     */
    public function testDetectCommentRecognition()
    {
        $sample = new File_Therion_Line("");
        $this->assertTrue($sample->isCommentOnly());
        $sample = new File_Therion_Line("", "", "   ");
        $this->assertTrue($sample->isCommentOnly());
        $sample = new File_Therion_Line("", "    ", "   ");
        $this->assertTrue($sample->isCommentOnly());
        $sample = new File_Therion_Line("    ", "    ", "   ");
        $this->assertTrue($sample->isCommentOnly());
        $sample = new File_Therion_Line("    ", "   ", "");
        $this->assertTrue($sample->isCommentOnly());
        $sample = new File_Therion_Line("    ", "", "");
        $this->assertTrue($sample->isCommentOnly());
        
        $sample = File_Therion_Line::parse("");
        $this->assertTrue($sample->isCommentOnly());
        $sample = File_Therion_Line::parse("    ");
        $this->assertTrue($sample->isCommentOnly());
        $sample = File_Therion_Line::parse("#");
        $this->assertTrue($sample->isCommentOnly());
        $sample = File_Therion_Line::parse("# foo");
        $this->assertTrue($sample->isCommentOnly());
        $sample = File_Therion_Line::parse("    #");
        $this->assertTrue($sample->isCommentOnly());
        $sample = File_Therion_Line::parse("    # foo   ");
        $this->assertTrue($sample->isCommentOnly());
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
    
    
    /**
     * Test escaping and unescaping
     * 
     * @todo Complete escaping of lonly " characters
     */
    public function testEscapingUnescaping()
    {
        // test normal strings without special chars
        $this->assertEquals("foo",  File_Therion_Line::escape("foo"));
        $this->assertEquals("foo",  File_Therion_Line::unescape("foo"));
        $this->assertEquals("1.23", File_Therion_Line::escape("1.23"));
        $this->assertEquals("1.23", File_Therion_Line::unescape("1.23"));
        $this->assertEquals(1.23,   File_Therion_Line::escape(1.23));   // int
        $this->assertEquals(1.23,   File_Therion_Line::unescape(1.23)); // int
        $this->assertEquals('""',   File_Therion_Line::escape("")); // empty
        $this->assertEquals('',     File_Therion_Line::unescape('""')); // empty
        $this->assertEquals('" "',  File_Therion_Line::escape(' ')); // blank
        $this->assertEquals(' ',    File_Therion_Line::unescape('" "')); // blank
        
        // test array interface
        $this->assertEquals(array("foo", "bar", "1.23"),
            File_Therion_Line::escape(array("foo", "bar", "1.23")));
        $this->assertEquals(array("foo", "bar", "1.23"),
            File_Therion_Line::unescape(array("foo", "bar", "1.23")));
            
        // test white space
        $this->assertEquals('"foo bar"', File_Therion_Line::escape("foo bar"));
        $this->assertEquals("[1. 23]", File_Therion_Line::escape("1. 23"));
        $this->assertEquals("[1.23 5.43]", File_Therion_Line::escape("1.23 5.43"));
        $this->assertEquals("[1.23 degrees]", File_Therion_Line::escape("1.23 degrees"));
        $this->assertEquals("foo bar", File_Therion_Line::unescape('"foo bar"'));
        $this->assertEquals("1. 23", File_Therion_Line::unescape("[1. 23]"));
        $this->assertEquals("1.23 5.43", File_Therion_Line::unescape("[1.23 5.43]"));
        $this->assertEquals("1.23 degrees", File_Therion_Line::unescape("[1.23 degrees]"));
        
        // test nested escaping
        $this->assertEquals('"""foo bar"""', File_Therion_Line::escape('"foo bar"'));
        $this->assertEquals('"foo bar"', File_Therion_Line::unescape('"""foo bar"""'));
        $this->assertEquals('"foo ""baz"" bar"', File_Therion_Line::escape('foo "baz" bar'));
        $this->assertEquals('"foo bar"', File_Therion_Line::unescape('""foo bar""'));
        
        // test encoding data
        $this->assertEquals('iso8859-2', File_Therion_Line::escape('iso8859-2'));
        $this->assertEquals('iso8859-2', File_Therion_Line::unescape('iso8859-2'));
        
        // @todo test uncommon cases
        // (i'm not entirely sure this is the correct syntax!
        //  it could probably also be '""' =esc> '""""""';
        //  which means the quotes must live inside single quotes themselves.
        //  currently i think """" is enough to denote two quotes)
        //$this->assertEquals('""""', File_Therion_Line::escape('""'));
        //$this->assertEquals('""', File_Therion_Line::unescape('""""'));
        $this->markTestIncomplete("some corner cases not tested yet!");

       
        // keywords currently not supported
        // $this->assertEquals("[key word]", File_Therion_Line::escape("key word"));
        // $this->assertEquals("key word", File_Therion_Line::escape("[key word]"));
        $this->markTestIncomplete("keyword escaping not tested yet!");
    }


    /**
     * Test data fields functionality
     */
    public function testDataFields()
    {
        // Basic testing of datafields
        $sample = new File_Therion_Line("");
        $this->assertInstanceOf('File_Therion_Line', $sample);
        $this->assertEquals(array(), $sample->getDatafields());
        
        $sample = new File_Therion_Line("one two three");
        $this->assertEquals(array("one", "two", "three"),
            $sample->getDatafields());
            
        $sample = new File_Therion_Line("one two three\\"); // \\-> is not data!
        $this->assertEquals(array("one", "two", "three"),
            $sample->getDatafields());
        
        $sample = File_Therion_Line::parse("one two three");
        $this->assertEquals(array("one", "two", "three"),
            $sample->getDatafields());
            
        // testing with simple escaped/quoted sequences
        $sample = File_Therion_Line::parse('one "foo bar"');
        $this->assertEquals(array("one", "foo bar"), $sample->getDatafields());
        
        $sample = File_Therion_Line::parse(
            'one "foo bar" emptyString "" emptyData []');
        $this->assertEquals(
            array("one", "foo bar", "emptyString", "", "emptyData", ""),
            $sample->getDatafields());
        
        $sample = File_Therion_Line::parse('one "foo ""bar baz"" bar" end');
        $this->assertEquals(
            array("one", 'foo "bar baz" bar', "end"),
            $sample->getDatafields());
            
        $sample = new File_Therion_Line('one "foo bar"');
        $this->assertEquals(array("one", "foo bar"), $sample->getDatafields());
        
        $sample = new File_Therion_Line('one "foo ""bar baz"" bar" end');
        $this->assertEquals(
            array("one", 'foo "bar baz" bar', "end"),
            $sample->getDatafields());
            
        $sample = new File_Therion_Line('one [1. 23] baz');
        $this->assertEquals(array("one", "1. 23", "baz"), $sample->getDatafields());
        
        $sample = new File_Therion_Line('one "foo says: 1.23" baz');
        $this->assertEquals(array("one", "foo says: 1.23", "baz"), $sample->getDatafields());
        
        $sample = new File_Therion_Line('one "foo says: ""1.23""" baz');
        $this->assertEquals(array("one", 'foo says: "1.23"', "baz"), $sample->getDatafields());
        
        $sample = new File_Therion_Line('one "foo says: [1. 23]" baz');
        $this->assertEquals(array("one", "foo says: [1. 23]", "baz"), $sample->getDatafields());
        
        $sample = new File_Therion_Line('one "foo says: ""[1. 23]""" baz');
        $this->assertEquals(array("one", 'foo says: "[1. 23]"', "baz"), $sample->getDatafields());
        
        $sample = new File_Therion_Line('"foo bar"');
        $this->assertEquals(array('foo bar'), $sample->getDatafields());
        
        $sample = new File_Therion_Line('"""foo bar"""');
        $this->assertEquals(array('"foo bar"'), $sample->getDatafields());
        
        $sample = new File_Therion_Line('"""foobar"""');
        $this->assertEquals(array('"foobar"'), $sample->getDatafields());
        
        $sample = new File_Therion_Line('point 773.5 517.0 station -name 1');
        $this->assertEquals(
            array('point', '773.5', '517.0', 'station', '-name', '1'),
            $sample->getDatafields()
        );
        
        $sample = new File_Therion_Line('point 536.5 563.5 station-name -align br');
        $this->assertEquals(
            array('point', '536.5', '563.5', 'station-name', '-align', 'br'),
            $sample->getDatafields()
        );
        
        $sample = new File_Therion_Line('map pdx -title "Rabbit Cave -- extended elevation"');
        $this->assertEquals(
            array('map', 'pdx', '-title', 'Rabbit Cave -- extended elevation'),
            $sample->getDatafields()
        );
        
        $sample = new File_Therion_Line('equate 1 2 3@A 4@B.A 5@A.B.C');
        $this->assertEquals(
            array("equate", "1", "2", "3@A", "4@B.A", "5@A.B.C"),
            $sample->getDatafields());
        
        $sample = new File_Therion_Line('input foo/bar/baz.th');
        $this->assertEquals(
            array("input", "foo/bar/baz.th"),
            $sample->getDatafields());
            
        $sample = new File_Therion_Line('input /foo/bar/baz.th');
        $this->assertEquals(
            array("input", "/foo/bar/baz.th"),
            $sample->getDatafields());
        
        
        // test encoding data
        $sample = new File_Therion_Line("encoding  iso8859-2".PHP_EOL);
        $this->assertEquals(array('encoding', 'iso8859-2'), $sample->getDatafields());
        $sample = File_Therion_Line::parse("encoding  iso8859-2".PHP_EOL);
        $this->assertEquals(array('encoding', 'iso8859-2'), $sample->getDatafields());
    }
    
    /**
     * Test basic instantiation using datafields
     */
    public function testBasicInstantiationWithDatafields()
    {
        $sample = new File_Therion_Line(array());
        $this->assertInstanceOf('File_Therion_Line', $sample);
        $this->assertEquals(array(), $sample->getDatafields());
                
        $sample = new File_Therion_Line(array("testdata"));
        $this->assertInstanceOf('File_Therion_Line', $sample);
        $this->assertEquals(array("testdata"), $sample->getDatafields());
        
        $sample = new File_Therion_Line(array("foo", "bar", "baz"));
        $this->assertInstanceOf('File_Therion_Line', $sample);
        $this->assertEquals(array("foo", "bar", "baz"), $sample->getDatafields());
        
        $sample = new File_Therion_Line(array("foo", 'bar test', "baz"));
        $this->assertInstanceOf('File_Therion_Line', $sample);
        $this->assertEquals(array("foo", 'bar test', "baz"), $sample->getDatafields());
       
    }
    
    
    /**
     * Test options parsing
     */
    public function testOptionsParsing()
    {
        // no options
        $sample = $sample = new File_Therion_Line('');
        $this->assertEquals(array(), $sample->extractOptions());
        
        $sample = $sample = new File_Therion_Line('survey foo');
        $this->assertEquals(array(), $sample->extractOptions());
        
        $sample = $sample = new File_Therion_Line('blafaz foo bar baz');
        $this->assertEquals(array(), $sample->extractOptions());
        
        // one option
        $sample = $sample = new File_Therion_Line(
            'survey foo -title "bar foo passage"');
        $this->assertEquals(array('title'=> array('bar foo passage')),
            $sample->extractOptions());
            
        // one option without arg
        // (this is illegal according to thbook)
        $sample = new File_Therion_Line('survey foo -someSwitch');
        $exception = null;
        try {
            $sample->extractOptions();
        } catch (Exception $e) {
            $exception = $e;
        }
        $this->assertInstanceOf('File_Therion_SyntaxException', $exception);
        
        // multiple options
        $sample = $sample = new File_Therion_Line(
            'survey foo -title "bar foo passage" -animal cats -catch rats');
        $this->assertEquals(array(
                'title'  => array('bar foo passage'),
                'animal' => array('cats'),
                'catch'  => array('rats')),
            $sample->extractOptions());
        
        // multiple options with multiple args
        $sample = $sample = new File_Therion_Line(
            'survey foo -title "bar passage" -animal cat "brown fox" -catch rats');
        $this->assertEquals(array(
                'title'  => array('bar passage'),
                'animal' => array(array('cat', 'brown fox')),
                'catch'  => array('rats')),
            $sample->extractOptions());
            
        // multiple options with multiple args several times
        $sample = $sample = new File_Therion_Line(
            'survey foo -title "bar passage" -animal cat "brown fox" '
                .'-catch rats -animal dog -catch mice trap');
        $this->assertEquals(
                array(
                    'title'  => array('bar passage'),
                    'animal' => array(
                        array('cat', 'brown fox'),
                        'dog'
                    ),
                    'catch'  => array(
                        'rats',
                        array('mice', 'trap')
                    )
                ),
                $sample->extractOptions() );
            
        // multiple options with multiple args but one without arg
        // (this is illegal according to thbook)
        $sample = $sample = new File_Therion_Line(
            'survey foo -title "bar passage" -animal -catch rats');
        $exception = null;
        try {
            $sample->extractOptions();
        } catch (Exception $e) {
            $exception = $e;
        }
        $this->assertInstanceOf('File_Therion_SyntaxException', $exception);
            
        // test parsing of scrap line; that is:
        // test proper handling of escaped negative numeric arguments
        $sample = $sample = new File_Therion_Line(
            'scrap ps2 -scale [295.0 203.0 995.0 207.5 0.0 0.0 0 -36 m]');
        $this->assertEquals(
            array('scale'  => array('295.0 203.0 995.0 207.5 0.0 0.0 0 -36 m')),
            $sample->extractOptions());
    }
    
    /**
     * Test filtering options out of args
     */
    public function testOptionsInverting()
    {
        // no options
        $sample = $sample = new File_Therion_Line('');
        $this->assertEquals(array(), $sample->extractOptions(true));
        
        $sample = $sample = new File_Therion_Line('survey foo');
        $this->assertEquals(array('survey', 'foo'), $sample->extractOptions(true));
        
        $sample = $sample = new File_Therion_Line('blafaz foo bar baz');
        $this->assertEquals(
            array('blafaz', 'foo', 'bar', 'baz'),
            $sample->extractOptions(true)
        );
        
        // option at end
        $sample = $sample = new File_Therion_Line(
            'join foo bar -smooth on -count 2');
        $this->assertEquals(
            array('join', 'foo', 'bar'),
            $sample->extractOptions(true)
        );
        
        // option at start after cmd
        $sample = $sample = new File_Therion_Line('foo -animal fox bar');
        $this->assertEquals(
            array('foo'),
            $sample->extractOptions(true)
        );
        
        
        // real world examples
        $sample = new File_Therion_Line('point 773.5 517.0 station -name 1');
        $this->assertEquals(
            array('name' => array('1')),
            $sample->extractOptions()
        );
        $this->assertEquals(
            array('point', '773.5', '517.0', 'station'),
            $sample->extractOptions(true)
        );
        
        $sample = new File_Therion_Line('point 536.5 563.5 station-name -align br');
        $this->assertEquals(
            array('align' => array('br')),
            $sample->extractOptions()
        );
        $this->assertEquals(
            array('point', '536.5', '563.5', 'station-name'),
            $sample->extractOptions(true)
        );
    }
}
?>