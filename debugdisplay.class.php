<?php

/******************************************************************************
 *
 *  Name: Debugger Class
 *  Author: Dan Morrow
 *
 *	Date: 11/23/2009
 *
 *	Description: Utility class to augment the built in debugging mechanisms
 *
 *
 *	This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 *  DATE        Developer   Comment
 *  ----------  ---------   --------------------------------------------
 *  11/23/2009  DMM         Initial version
 *  12/22/2009  DMM         Added static functions to detect if object exists.
 *                          Should make it easier to use this in include files.
 *
 ******************************************************************************/

// Assume config.php was included already or nothing will work anyway ...
// that way we don't need to do the directory calculation junk

// 'DEBUG_NONE', 0 | 'DEBUG_NORMAL', 15 '| DEBUG_ALL', 6143 | DEBUG_ALL+ (DEBUG_ALL |�32768 | 'DEBUG_DEVELOPER', 38911);

/// Debug levels ///
/* no warnings at all */
define ('DEBUG_NONE', 0);
/* E_ERROR | E_PARSE */
define ('DEBUG_MINIMAL', 5);
/* E_ERROR | E_PARSE | E_WARNING | E_NOTICE */
define ('DEBUG_NORMAL', 15);
/* E_ALL without E_STRICT for now, do show recoverable fatal errors */
define ('DEBUG_ALL', 6143);
/* DEBUG_ALL with extra Moodle debug messages - (DEBUG_ALL | 32768) */
define ('DEBUG_DEVELOPER', 38911);

// These values are named for setting the working level - they are a little confusing when used for level override
define ('DEBUG_DISPLAY_ALWAYS', DEBUG_DEVELOPER + 1);
define ('DEBUG_DISPLAY_NEVER', -1);
define ('DEBUG_DISPLAY_NONE', 1);
define ('DEBUG_DISPLAY_MSG', 8);
// These are more reader friendly for level overrides
define ('DEBUG_OVER_FORCE', DEBUG_DISPLAY_NEVER);
define ('DEBUG_OVER_SOME', 1);
define ('DEBUG_OVER_DISABLE', DEBUG_DISPLAY_ALWAYS);

class debugdisplay {
    public $prefix=null;
    public $prefixseparator=': ';
    public $separator=' ';
    public $commentmode=false;
    public $linemode=false;
    public $parammode=false;
    public $callermode=true;
    public $timestampmode=false;
    public $divmode=false;
    public $debuglevel;

    private $buffer;

    function __construct($msgprefix=null, $debugoverride = null, $commentmode=false) {
        global $CFG;
        $this->prefix = $msgprefix;
        $this->commentmode = $commentmode;
        $this->buffer = '';

        if ($debugoverride != null) {
            $this->debuglevel = $debugoverride;
        } else {
            $this->debuglevel = $CFG->debug;
        }
    }

    function stacktrace($level=DEBUG_ALL) {
        global $CFG;

        if (empty($this->debuglevel) || ($this->debuglevel < $level)) {
            return false;
        }
        $maxdepth = 15;
        $depth=1;

        $infostr = '';

        $callers = debug_backtrace();
        if (!empty($callers) && is_array($callers) && ($stackdepth=count($callers))) {
            $infostr = "TRACE:<br />\n";
            reset($callers);
            $caller = next($callers);
            $currdepth = 1;
            while ($caller && ($currdepth < $maxdepth)) {
                if (isset($caller['file'])) {
                    $infostr .= str_replace($CFG->dirroot,'',$caller['file']);
                } else {
                    $infostr .= "unknown_file";
                }
                if ($this->linemode) {
                    if (isset($caller['line'])) {
                        $infostr .= "#".$caller['line'];
                    } else {
                        $infostr .= "#unknown_line";
                    }
                }
                $infostr .= $this->prefixseparator;

                if ($this->parammode) {
                    if ($stackdepth > $depth) {
                        $parentcaller = next($callers);
                        if (isset($parentcaller['class'])) {
                            $infostr .= $parentcaller['class'].$parentcaller['type'];
                        }
                        if (isset($parentcaller['function'])) {
                            $infostr .= $parentcaller['function'] . '(';
                            if (isset($parentcaller['args']) && is_array($parentcaller['args'])) {
                                $pprefix = '';
                                foreach($parentcaller['args'] as $pval) {
                                    $infostr .= $pprefix;
                                    if (is_array($pval)) {
                                        $infostr .= 'array';
                                    } elseif (is_object($pval)) {
                                        $infostr .= 'object';
                                    } else {
                                        $infostr .= $pval;
                                    }
                                    $pprefix = ',';
                                }
                            }
                            $infostr .= ') ';
                        }
                        $infostr .= $this->prefixseparator;
                    }
                } else {
                    $parentcaller = next($callers);
                }
                $infostr .= "<br />\n";
                $caller = $parentcaller;
                $currdepth++;
            }
            $infostr .= "END TRACE<br />\n";
        }

        return $infostr;
    }

    private function callerinfo($depth) {
        global $CFG;

        $infostr = '';

        if ($this->timestampmode) {
            $infostr .= date('m/d/y H:i:s', time());
            $infostr .= $this->prefixseparator;
        }

        $callers = debug_backtrace();
        if (!empty($callers) && $this->callermode && is_array($callers) && (($stackdepth=count($callers)))) {
//echo "Callers:<pre>\n";
//var_dump($callers);
//echo "</pre>\n";
            reset($callers);
            for ($iDepth = 0; $iDepth < $depth; $iDepth++) {
                $caller = next($callers);
            }
            if (isset($caller['file'])) {
                $infostr .= str_replace($CFG->dirroot,'',$caller['file']);
            } else {
                $infostr .= "unknown_file";
            }
            if ($this->linemode) {
                if (isset($caller['line'])) {
                    $infostr .= "#".$caller['line'];
                } else {
                    $infostr .= "#unknown_line";
                }
            }
            $infostr .= $this->prefixseparator;

            if ($this->parammode) {
                if ($stackdepth > $depth) {
                    $parentcaller = next($callers);
                    if (isset($parentcaller['class'])) {
                        $infostr .= $parentcaller['class'].$parentcaller['type'];
                    }
                    if (isset($parentcaller['function'])) {
                        $infostr .= $parentcaller['function'] . '(';
                        if (isset($parentcaller['args']) && is_array($parentcaller['args'])) {
                            $pprefix = '';
                            foreach($parentcaller['args'] as $pval) {
                                $infostr .= $pprefix;
                                if (is_array($pval)) {
                                    $infostr .= 'array';
                                } elseif (is_object($pval)) {
                                    $infostr .= 'object';
                                } else {
                                    $infostr .= $pval;
                                }
                                $pprefix = ',';
                            }
                        }
                        $infostr .= ') ';
                    }
                    $infostr .= $this->prefixseparator;
                }
            }
        }

        return $infostr;
    }

    private function addprefix($depth=2) {
        if (empty($this->buffer)) {
            if ($this->divmode) {
                $this->buffer = "<div style='margin-left: 100px;'>";
            }
            $this->buffer .= $this->prefix.$this->prefixseparator;
            $this->buffer .= $this->callerinfo($depth);
        }
    }

    function append($msg=null, $level=DEBUG_ALL, $depth=2) {

        if (!empty($this->debuglevel) && ($this->debuglevel >= $level)) {
            $this->addprefix($depth);
            if (is_array($msg)) {
                foreach ($msg as $name => $pval ) {
                    $this->buffer .= $name."=";
                    if (is_array($pval)) {
                        $this->buffer .= "array";
                    } elseif (is_object($pval)) {
                        $this->buffer .= "object";
                    } else {
                        $this->buffer .= $pval;
                    }
                    $this->buffer .= $this->separator;
                }
            } else {
                $this->buffer .= $msg.$this->separator;
            }
        }
    }

    function display($msg=null, $level=DEBUG_ALL, $returnoutput=false, $depth=2) {
        $returnstr = null;

        if (empty($this->debuglevel) || ($this->debuglevel < $level)) {
            return false;
        }

        if (!empty($msg)) {
            $this->addprefix($depth);
            $this->append($msg, $level);
        }

        if (!empty($this->buffer)) {
            $returnstr = "";
            if ($this->commentmode) {
                $returnstr .= "<!-- \n";
                $returnstr .= $this->buffer;
                if ($this->divmode) {
                    $returnstr .= "</div>";
                }
                $returnstr .= "\n -->\n";
            } else {
                $returnstr .= $this->buffer;
                if ($this->divmode) {
                    $returnstr .= "</div>";
                }
                $returnstr .= "<br />\n";
            }
        }
        $this->buffer = '';

        if ($returnoutput) {
            return $returnstr;
        } else {
            echo $returnstr;
        }
    }

    function isdebug($level=DEBUG_ALL) {
        if (empty($this->debuglevel) || ($this->debuglevel < $level)) {
            return false;
        } else {
            return true;
        }
    }


    function dump($var, $level=DEBUG_ALL, $depth=2) {
        if (empty($this->debuglevel) || ($this->debuglevel < $level)) {
            return false;
        }
        $this->addprefix($depth);
        if ($this->commentmode) {
            echo "<!-- ".$this->buffer."\n";
            var_dump($var);
            echo "\n -->\n";
        } else {
            echo "<br />\n".$this->buffer."\n<pre>\n";
            var_dump($var);
            echo "\n</pre><br />\n";
        }
        $this->buffer = '';
    }
}

function sdbgdsp_append($debuggername, $msg=null, $level=DEBUG_ALL, $depth=3) {
    if (array_key_exists($debuggername,$GLOBALS)) {
        $debugdisplay = $GLOBALS[$debuggername];
        if (is_object($debugdisplay)) {
            $debugdisplay->append($msg, $level, $depth);
        }
    }
}
function sdbgdsp_display($debuggername, $msg=null, $level=DEBUG_ALL, $returnoutput=false, $depth=3) {
    if (array_key_exists($debuggername,$GLOBALS)) {
        $debugdisplay = $GLOBALS[$debuggername];
        if (is_object($debugdisplay)) {
            $debugdisplay->display($msg, $level, $returnoutput, $depth);
        }
    }
}
function sdbgdsp_dump($debuggername, $var, $level=DEBUG_ALL, $depth=3) {
    if (array_key_exists($debuggername,$GLOBALS)) {
        $debugdisplay = $GLOBALS[$debuggername];
        if (is_object($debugdisplay)) {
            $debugdisplay->dump($var, $level, $depth);
        }
    }
}
function sdbgdsp_stacktrace($debuggername, $level=DEBUG_ALL) {
    if (array_key_exists($debuggername,$GLOBALS)) {
        $debugdisplay = $GLOBALS[$debuggername];
        if (is_object($debugdisplay)) {
            if ($returnstr = $debugdisplay->stacktrace($level)) {
                echo $returnstr;
            }
        }
    }
}
function dbgdsp_append($debugdisplay=null, $msg=null, $level=DEBUG_ALL) {
    if (isobject($debugdisplay)) {
        $debugdisplay->append($msg, $level);
    }
}
function dbgdsp_display($debugdisplay=null, $msg=null, $level=DEBUG_ALL, $returnoutput=false) {
    if (is_object($debugdisplay)) {
        $debugdisplay->display($msg, $level, $returnoutput);
    }
}
function dbgdsp_dump($debugdisplay=null, $var=null, $level=DEBUG_ALL) {
    if (is_object($debugdisplay)) {
        $debugdisplay->dump($var, $level);
    }
}
function dbgdsp_isdebug($debugdisplay=null, $level=DEBUG_ALL) {
    if (is_object($debugdisplay)) {
        $debugdisplay->isdebug($level);
    }
}

?>
