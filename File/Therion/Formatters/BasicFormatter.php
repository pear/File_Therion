<?php
/**
 * Therion cave survey basic formatter class.
 *
 * PHP version 5
 *
 * @category   file
 * @package    File_Therion_Formatters
 * @author     Benedikt Hallinger <beni@php.net>
 * @copyright  2016 Benedikt Hallinger
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @link       http://pear.php.net/package/File_Therion/
 */

/**
 * This formatter serves some basic formatting capabilitys.
 *
 * @category   file
 * @package    File_Therion_Formatters
 * @author     Benedikt Hallinger <beni@php.net>
 * @copyright  2016 Benedikt Hallinger
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @link       http://pear.php.net/package/File_Therion/
 * @todo Implement wrapping feature
 */
class File_Therion_BasicFormatter implements File_Therion_Formatter
{
    /**
     * Wrapping of the file.
     * 
     * This controls the wrapping column when writing
     * 
     * @access protected
     * @var int column to wrap at (0=no wrapping)
     */
    protected $wrapAt = 0;
    
    /**
     * Indent character(s)
     * @var string
     */
    protected $baseindent = "  ";
    
    /**
     * Field definition for sprintf() at centerline data
     */
    
    /**
     * commands to honor as separate context
     */
    protected $contextMark = array(
        'survey', 'centerline', 'centreline', 'scrap', 'surface',
        'grade', 'map'
    );
    
    /**
     * Template for centerline data item elements.
     * 
     * This an sprintf() compatible string.
     * 
     * @var string
     */
    protected $clDataItemTemplate = "%6s";
    
    /**
     * Template for centerline data item separator.
     * 
     * This an native string.
     * 
     * @var string
     */
    protected $clDataSepTemplate = "  ";
    
    
    /**
     * Format lines.
     * 
     * This method is automatically invoked by File_Therion->getLines() to
     * transparently (re-)format the lines returned to the caller.
     * 
     * @param array File_Therion_Lines to format
     */
    public function format($lines) {
        $ctx = array();
        $old_ctx = "";
        
        foreach ($lines as $l) {
            // retrieve therion command (if any)
            $datafields = $l->getDataFields();
            $command    = count($datafields)>0? $datafields[0] : "";
            
            /* 
             * setup context chain
             */
            $cmdOpensCTX  = $this->isOpenedCTX($command, $ctx);
            $cmdClosesCTX = $this->isClosedCTX($command, $ctx);
            if($cmdOpensCTX) {
                $ctx[] = $command;
            }
            if($cmdClosesCTX) {
                $old_ctx = array_pop($ctx);
            }
            
            
            /*
             * readjust indenting
             */
            $indentStr = "";
            $i = $cmdOpensCTX ? 1 : 0;
            for (null; $i <= count($ctx)-1; $i++) {
                $indentStr .= $this->getIndent();
            }
            
            $l->setIndent($indentStr);
            
            
            /**
             * Reformat centerline data items
             */
            if (preg_match("/cent(re|er)line/", end($ctx))) {
                // see if we have an ordinary command or a data line.
                // detection is based on known cl commands - this is bruteforce
                // and probably not very elegant: more thinking needed!
                $clCommands = array(
                    "centerline", "endcenterline","centreline", "endcentreline",
                    "date", "explo-date", "team", "explo-team", "instrument",
                    "calibrate", "units", "sd", "grade", "declination",
                    "grid-angle", "infer", "mark", "flags", "station", "cs",
                    "fix", "equate", "data", "break", "group", "endgroup",
                    "walls", "vthreshold", "extend", "station-names",
                    "copyright"
                );

                if (   count($datafields) > 0
                    && !in_array($datafields[0], $clCommands)) {
                    // first datafield is not known amongst commands,
                    // this must be a data line!
                    $formatStr = "";
                    $fieldTPL = $this->getCenterlineDataTemplate();
                    $sepTPL   = $this->getCenterlineSeparatorTemplate();
                    foreach ($datafields as $f) {
                        $formatStr .= sprintf($fieldTPL, $f).$sepTPL;
                    }
                    $formatStr= rtrim($formatStr, $sepTPL); // strip last sepTPL

                    $l->setContent($formatStr); // adjust line
                }
            }
            

        }
        
        return $lines;
    }
    
    
    /**
     * Set wrapping of lines.
     * 
     * Lines will be wrapped after this length.
     * 
     * @param int $lentgh column to wrap at (0=no wrapping)
     */
    public function setWrapping($length)
    {
        if (!is_int($length)) {
            throw new InvalidArgumentException(
                    "int expected as wrapping length");
        }
        $this->wrapAt = $length;
    }
    
    /**
     * Get wrapping length.
     * 
     * @return int
     */
    public function getWrapping()
    {
        return $this->wrapAt;
    }
    
    /**
     * Set base inention of lines.
     * 
     * Lines will be indented using this string. Each context level will
     * prepend this string one more time.
     * 
     * @param string $indent
     * @see {@link $contextMark} for known contexts
     */
    public function setIndent($indent)
    {
        if (!is_string($indent)) {
            throw new InvalidArgumentException(
                    "string expected as indent parameter");
        }
        $this->baseindent = $indent;
    }
    
    /**
     * Get indent string.
     * 
     * @return string
     */
    public function getIndent()
    {
        return $this->baseindent;
    }
    
    /**
     * Set Centerline data fields template.
     * 
     * This is the sprintf()-compatible template that is applied to
     * centerline data items.
     * Note that here the data is already interpreted as sring, so you should
     * only use sprintf() string formatters.
     * 
     * Example:
     * <code>
     * // make data item fields 8 characters wide:
     * $formatter->setCenterlineDataTemplate("%8s");
     * </code>
     * 
     * @var string
     */
    public function setCenterlineDataTemplate($sprintf_string)
    {
        $this->clDataItemTemplate = $sprintf_string;
    }
    
    /**
     * Get current Centerline data fields template.
     * 
     * @return string
     */
    public function getCenterlineDataTemplate()
    {
        return $this->clDataItemTemplate;
    }
    
    /**
     * Set Centerline data separator template.
     * 
     * This is the string template that is used to separate
     * centerline data items.
     * 
     * @var string
     */
    public function setCenterlineSeparatorTemplate($string)
    {
        $this->clDataSepTemplate = $string;
    }
    
    /**
     * Get current Centerline data separator template.
     * 
     * @return string
     */
    public function getCenterlineSeparatorTemplate()
    {
        return $this->clDataSepTemplate;
    }
    
    
    /**
     * See if current line command opens a new context.
     */
    protected function isOpenedCTX($cmd, $ctx)
    {
        if (preg_match("/cent(re|er)line/", end($ctx))) {
            // we are in centerline context: no further subcontext allowed!
            return false;
        } else {
            return in_array(strtolower($cmd), $this->contextMark);
        }
    }
    
    /**
     * See if current line command opens a new context.
     */
    protected function isClosedCTX($cmd, $ctx)
    {
        return in_array(
            strtolower($cmd),
            array_map(
                function($n) { return 'end'.$n; }, 
                $this->contextMark
            )
        );
    }
    
    
}

?>