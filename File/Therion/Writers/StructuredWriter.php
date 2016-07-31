<?php
/**
 * Therion cave survey structured writer class.
 *
 * PHP version 5
 *
 * @category   file
 * @package    File_Therion_Writers
 * @author     Benedikt Hallinger <beni@php.net>
 * @copyright  2016 Benedikt Hallinger
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @link       http://pear.php.net/package/File_Therion/
 */

/**
 * The writer creates nested file structures linked with 'input' commands.
 * 
 * The exact file generation is controlled with file path templates.
 * Everytime a template generates a new path at the current context,
 * an input command is created in the parent file and a new file is
 * created instead.
 * The actual write is performed using the basic DirectWriter (options from
 * there apply in this writer too); Existing target files are overwritten.
 * 
 * The default survey structure is written in a nested fashion where:
 * - each survey forms a new directory in the parent folder
 * - each surveys content go into a separate .th file
 * - each surveys scrap data go into a separate .th2 file per survey
 * - subsurveys are handled like that too, but the files are
 *   linked together with input-commands.
 * 
 * An alternative structure could be one, where you want to introduce
 * another subfolder for therions data, so the therion files do not interfer
 * with other files (eg. photos) stored in the default folder structure:
 * <code>
 * $writer->changeTemplate('File_Therion_Survey', '$(base)/../$(name)/therion/$(name).th');
 * $writer->changeTemplate('File_Therion_Scrap', '$(base)/$(parent).th2');
 * </code>
 * With this, each survey still forms a subfolder containing folders for the
 * subsurveys. Therion data however will go into a subfolder "therion"
 * (example assumes a survey structure like "main/sub1/sub2"):
 * - .../main/therion/main.th            -> first survey level
 * - .../main/sub1/therion/sub1.th       -> second survey level data
 * - .../main/sub1/therion/sub1.th2      -> second survey level scraps
 * - .../main/sub1/sub2/therion/sub2.th  -> third survey level data
 * - .../main/sub1/sub2/therion/sub2.th2 -> third survey level scraps
 *
 * @category   file
 * @package    File_Therion_Writers
 * @author     Benedikt Hallinger <beni@php.net>
 * @copyright  2016 Benedikt Hallinger
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @link       http://pear.php.net/package/File_Therion/
 */
class File_Therion_StructuredWriter
    extends File_Therion_DirectWriter
    implements File_Therion_Writer
{
    
    /**
     * Configured filepath templates.
     * 
     * @var array
     */
    protected $_fp_tpl = array(
        'File_Therion_Survey'  =>  '$(base)/$(name)/$(name).th',
        'File_Therion_Scrap'   =>  '$(base)/$(parent).th2'
    );
    
    /**
     * Registered files.
     * 
     * This gets filled by {@link handleLines()}.
     * 
     * @var array of File_Therion objects
     */
    protected $_files = array();
    
    /**
     * Registered input commands.
     * 
     * This gets filled by {@link handleLines()}.
     * It is used to suppress multiple occurences of the same input-command
     * at the same file.
     * 
     * @var array index=filename, value=array of input cmds
     */
    protected $_inputCMDs = array();
    
    
    /**
     * Write a Therion file structure.
     * 
     * The lines of the {@link File_Therion} instance will be split different
     * context collections.
     * Local lines are written to the filepath of the given {@link File_Therion}
     * instance. Multiline commands (e.g. 'survey', etc) are first evaulated
     * against the matching file template. The lines are then written to the
     * template file.
     * 
     * @param File_Therion $file  the file object to write
     * @throws File_Therion_IOException in case of IO error
     * @throws File_Therion_Exception for other errors
     */
    public function write(File_Therion $file) {
        // retrieve lines of original file.
        // (this creates our global file buffer of lines-to-be-processed.)
        $lineBuffer = $file->getLines();
        
        // initialize root file
        $rootFile = new File_Therion($file->getFilename());
        $this->_files[] = $rootFile;
        
        // now feed this inital buffer to the handleLines() routine.
        // it will sort the lines to the appropriate file items.
        $this->handleLines($lineBuffer, $rootFile);
        
        // finally write the contents to disk
        foreach ($this->_files as $fo) {
            parent::write($fo);  // use directWriter parent for this
            // Debug purpose: $fo->write(new File_Therion_ConsoleWriter());
        }
        
    }
    
    
    /**
     * Handle a given set of therion lines.
     * 
     * Sort the lines into appropriate file items depending on template
     * evaluation results.
     * 
     * @param array array with File_Therion_Line objects
     * @param File_Therion $file   the current file context
     * @param array $ctx current object context (path of names)
     */
    public function handleLines(&$lineBuffer, File_Therion $file, $ctx = array()) {
        //print "DBG: handleLines() called ('".basename($file->getFilename())."'; buffer length ".count($lineBuffer).")\n";
        
        // process remaining lines in file buffer
        $previousLine = null;
        while ($line = array_shift($lineBuffer)) {
            // fetch line data to ease investigation
            $lineData = $line->getDatafields();
            $lineCMD  = array_shift($lineData);
            $lineArg  = array_shift($lineData);
            
            
            /* see if we reached a new context; if yes:
             * - evaluate template to generate filename
             * - get matching file item
             * - add the line to the item
             * - delegate further lines of line buffer to subhandler;
             *   he will return to our level once the context was closed
             */
            $fp = $this->resolveTemplate($line, $file, $ctx);
            $fh = $this->getFileInstance($fp);
            if (!is_null($fh) && $fh !== $file) {
                // print "DBG: ---CTX SWITCH---".$line->toString();
                // add the current line to resolved file
                $fh->addLine($line);
                
                // delegate remaining lines
                $ctx_cp = $ctx;
                $ctx_cp[] = $lineArg; // extend contextcopy (eg add survey name)
                $this->handleLines($lineBuffer, $fh, $ctx_cp);
                
                // add 'input' command for file inclusion to local file
                $previousLineIndent = is_null($previousLine)? '' : $previousLine->getIndent();
                $file_fn = $file->getFilename();
                $ifile   = $fh->getFilename($file_fn);
                $inputcmd = new File_Therion_Line(
                    'input '.$ifile, '', $previousLineIndent);
                if (!array_key_exists($file_fn, $this->_inputCMDs)
                    || !in_array($inputcmd, $this->_inputCMDs[$file_fn])) {
                    
                    $file->addLine($inputcmd);
                    
                    // store for further comparison used for suppressing dupes
                    $this->_inputCMDs[$file_fn][] = $inputcmd;
                }
                
                // continue with the following local line
                // as the current line was already processed
                continue;
            }
            
            
            /*
             *  Other lines: add the local line to the local file
             */
           // print "DBG: handleLines() handles: '".trim($line->toString())."' @".basename($file->getFilename())."\n";
            $file->addLine($line);
            
            
            /* Conext-close detected
             * - return to uplevel as the closing line was already processed
             */
            if (preg_match('/^end(survey|scrap)/i', $lineCMD)) {
              //  print "DBG: ---CTX END---".$line->toString();
                return;
            }
            
            
            // store current line for further reference
            // (only real lines, ignore comments only)
            if (!$line->isCommentOnly()) $previousLine = $line;
            
        }
        
    }
    
    
    
    /**
     * Change filepath template.
     * 
     * The template defines where the content of an object is written to.
     * A template may contain variables (see below).
     * 
     * Recognized $items are class names of Therion objects.
     * 
     * Valid template variables are:
     * - root:      The full directory path of initial file
     * - base:      The full directory path of the parent survey
     * - name:      Name of the current object
     * - parent:    Name of the current survey context (parent survey)
     * 
     * @param string $item Class name to configure
     * @param string $template
     * @throws File_Therion_Exception if template item is not supported
     * @throws File_Therion_SyntaxException in case of invalid template
     * @todo check on syntax of template
     */
    public function changeTemplate($item, $template)
    {
        if (!array_key_exists($item, $this->_fp_tpl)) {
            throw new File_Therion_Exception(
                "template for ".$item." is not supported!"
            );
        }
        $this->_fp_tpl[$item] = $template;
    }
    
    /**
     * Return template for therion command or class name.
     * 
     * @param string $name Therion command or class name
     * @return null|string NULL when no such template is defined
     */
    public function getTemplate($name)
    {
        if (preg_match('/^File_Therion/', $name)) {
            return array_key_exists($name, $this->_fp_tpl)?
                $this->_fp_tpl[$name] : null;
                
        } else {
            // resolve command to class name
            $cmd2type = array(
                'survey' => 'File_Therion_Survey',
                'scrap'  => 'File_Therion_Scrap'
            );
            
            if (array_key_exists($name, $cmd2type)) {
                return $this->getTemplate($cmd2type[$name]);
            } else {
                return null;
            }
        }
        
    }
    
    /**
     * Resolve a template to an actual file path.
     * 
     * Returns either the resolved template as viewed from the given file as
     * base or NULL in case resolution was not possible.
     *
     * @param File_Therion_Line $line Definition of object
     * @param File_Therion $file the file object to resolve from
     * @param array $ctx Context array (array of names leading to the line obj)
     * @return null|string the resolved path
     */
    protected function resolveTemplate($line, File_Therion $file, $ctx)
    {
        $fp = null; // parsed template file path (return var)
        
        // get line data
        $lineData = $line->getDatafields();
        $lineCMD  = array_shift($lineData);
        $lineArg  = array_shift($lineData);
        
        // get base context in filesystem (path)
        $ctx_path = dirname($file->getFilename());
        
        // get template for line
        $tpl = $this->getTemplate($lineCMD);
        if (!is_null($tpl)) {
            /* resolved a template!
             *   -> parse it and return result
             */
        
            /*
             *  populate template vars
             */
            $obj_name   = $lineArg;
            $obj_parent = array_pop($ctx);
            $root_path  = dirname($this->_files[0]->getFilename());
            
            /*
             * Parse template into real filepath
             */
            $fp = $tpl; // load template
            $fp = preg_replace('/\$\(root\)/',   $root_path, $fp);   // $(root)
            $fp = preg_replace('/\$\(base\)/',   $ctx_path, $fp);    // $(base)
            $fp = preg_replace('/\$\(name\)/',   $obj_name, $fp);    // $(name)
            $fp = preg_replace('/\$\(parent\)/', $obj_parent, $fp);  // $(survey)
        }
        
        // return final result
        return $fp;
    }
    
    /**
     * Return file instance for template.
     * 
     * Instantiates a new file object for the given file path or returns
     * the already present one.
     * 
     * NULL is returned, if the passed string is also NULL.
     * 
     * @param string $filename Path to file resolved from template
     * @return null|File_Therion object for the target file
     */
    protected function getFileInstance($filename)
    {
        if (is_null($filename)) {
            return null;
        }
        
        // search existing files
        foreach ($this->_files as $file) {
            if ($file->getFileName() == $filename) {
                return $file;
            }
        }
        
        // no matching file: create new
        $fnew = new File_Therion($filename);
        $this->_files[] = $fnew;
        return $fnew;
    }
    
}

?>