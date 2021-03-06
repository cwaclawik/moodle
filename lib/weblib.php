<?php // $Id$

///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.com                                            //
//                                                                       //
// Copyright (C) 1999 onwards Martin Dougiamas  http://dougiamas.com     //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 2 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

/**
 * Library of functions for web output
 *
 * Library of all general-purpose Moodle PHP functions and constants
 * that produce HTML output
 *
 * Other main libraries:
 * - datalib.php - functions that access the database.
 * - moodlelib.php - general-purpose Moodle functions.
 * @author Martin Dougiamas
 * @version  $Id$
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package moodlecore
 */

/// Constants

/// Define text formatting types ... eventually we can add Wiki, BBcode etc

/**
 * Does all sorts of transformations and filtering
 */
define('FORMAT_MOODLE',   '0');   // Does all sorts of transformations and filtering

/**
 * Plain HTML (with some tags stripped)
 */
define('FORMAT_HTML',     '1');   // Plain HTML (with some tags stripped)

/**
 * Plain text (even tags are printed in full)
 */
define('FORMAT_PLAIN',    '2');   // Plain text (even tags are printed in full)

/**
 * Wiki-formatted text
 * Deprecated: left here just to note that '3' is not used (at the moment)
 * and to catch any latent wiki-like text (which generates an error)
 */
define('FORMAT_WIKI',     '3');   // Wiki-formatted text

/**
 * Markdown-formatted text http://daringfireball.net/projects/markdown/
 */
define('FORMAT_MARKDOWN', '4');   // Markdown-formatted text http://daringfireball.net/projects/markdown/

/**
 * TRUSTTEXT marker - if present in text, text cleaning should be bypassed
 */
define('TRUSTTEXT', '#####TRUSTTEXT#####');


/**
 * Javascript related defines
 */
define('REQUIREJS_BEFOREHEADER', 0);
define('REQUIREJS_INHEADER',     1);
define('REQUIREJS_AFTERHEADER',  2);

/**
 * Allowed tags - string of html tags that can be tested against for safe html tags
 * @global string $ALLOWED_TAGS
 */
global $ALLOWED_TAGS;
$ALLOWED_TAGS =
'<p><br><b><i><u><font><table><tbody><thead><tfoot><span><div><tr><td><th><ol><ul><dl><li><dt><dd><h1><h2><h3><h4><h5><h6><hr><img><a><strong><emphasis><em><sup><sub><address><cite><blockquote><pre><strike><param><acronym><nolink><lang><tex><algebra><math><mi><mn><mo><mtext><mspace><ms><mrow><mfrac><msqrt><mroot><mstyle><merror><mpadded><mphantom><mfenced><msub><msup><msubsup><munder><mover><munderover><mmultiscripts><mtable><mtr><mtd><maligngroup><malignmark><maction><cn><ci><apply><reln><fn><interval><inverse><sep><condition><declare><lambda><compose><ident><quotient><exp><factorial><divide><max><min><minus><plus><power><rem><times><root><gcd><and><or><xor><not><implies><forall><exists><abs><conjugate><eq><neq><gt><lt><geq><leq><ln><log><int><diff><partialdiff><lowlimit><uplimit><bvar><degree><set><list><union><intersect><in><notin><subset><prsubset><notsubset><notprsubset><setdiff><sum><product><limit><tendsto><mean><sdev><variance><median><mode><moment><vector><matrix><matrixrow><determinant><transpose><selector><annotation><semantics><annotation-xml><tt><code>';

/**
 * Allowed protocols - array of protocols that are safe to use in links and so on
 * @global string $ALLOWED_PROTOCOLS
 */
$ALLOWED_PROTOCOLS = array('http', 'https', 'ftp', 'news', 'mailto', 'rtsp', 'teamspeak', 'gopher', 'mms',
                           'color', 'callto', 'cursor', 'text-align', 'font-size', 'font-weight', 'font-style', 'font-family',
                           'border', 'margin', 'padding', 'background', 'background-color', 'text-decoration');   // CSS as well to get through kses


/// Functions

/**
 * Add quotes to HTML characters
 *
 * Returns $var with HTML characters (like "<", ">", etc.) properly quoted.
 * This function is very similar to {@link p()}
 *
 * @param string $var the string potentially containing HTML characters
 * @param boolean $obsolete no longer used.
 * @return string
 */
function s($var, $obsolete = false) {

    if ($var == '0') {  // for integer 0, boolean false, string '0'
        return '0';
    }

    return preg_replace("/&amp;(#\d+);/i", "&$1;", htmlspecialchars($var));
}

/**
 * Add quotes to HTML characters
 *
 * Prints $var with HTML characters (like "<", ">", etc.) properly quoted.
 * This function is very similar to {@link s()}
 *
 * @param string $var the string potentially containing HTML characters
 * @param boolean $obsolete no longer used.
 * @return string
 */
function p($var, $obsolete = false) {
    echo s($var, $obsolete);
}

/**
 * Does proper javascript quoting.
 * Do not use addslashes anymore, because it does not work when magic_quotes_sybase is enabled.
 *
 * @since 1.8 - 22/02/2007
 * @param mixed value
 * @return mixed quoted result
 */
function addslashes_js($var) {
    if (is_string($var)) {
        $var = str_replace('\\', '\\\\', $var);
        $var = str_replace(array('\'', '"', "\n", "\r", "\0"), array('\\\'', '\\"', '\\n', '\\r', '\\0'), $var);
        $var = str_replace('</', '<\/', $var);   // XHTML compliance
    } else if (is_array($var)) {
        $var = array_map('addslashes_js', $var);
    } else if (is_object($var)) {
        $a = get_object_vars($var);
        foreach ($a as $key=>$value) {
          $a[$key] = addslashes_js($value);
        }
        $var = (object)$a;
    }
    return $var;
}

/**
 * Remove query string from url
 *
 * Takes in a URL and returns it without the querystring portion
 *
 * @param string $url the url which may have a query string attached
 * @return string
 */
 function strip_querystring($url) {

    if ($commapos = strpos($url, '?')) {
        return substr($url, 0, $commapos);
    } else {
        return $url;
    }
}

/**
 * Returns the URL of the HTTP_REFERER, less the querystring portion if required
 * @param boolean $stripquery if true, also removes the query part of the url.
 * @return string
 */
function get_referer($stripquery=true) {
    if (isset($_SERVER['HTTP_REFERER'])) {
        if ($stripquery) {
            return strip_querystring($_SERVER['HTTP_REFERER']);
        } else {
            return $_SERVER['HTTP_REFERER'];
        }
    } else {
        return '';
    }
}


/**
 * Returns the name of the current script, WITH the querystring portion.
 * this function is necessary because PHP_SELF and REQUEST_URI and SCRIPT_NAME
 * return different things depending on a lot of things like your OS, Web
 * server, and the way PHP is compiled (ie. as a CGI, module, ISAPI, etc.)
 * <b>NOTE:</b> This function returns false if the global variables needed are not set.
 *
 * @return string
 */
 function me() {
     global $ME;
     return $ME;
}

/**
 * Like {@link me()} but returns a full URL
 * @see me()
 * @return string
 */
function qualified_me() {
    global $FULLME;
    return $FULLME;
}

/**
 * Class for creating and manipulating urls.
 *
 * See short write up here http://docs.moodle.org/en/Development:lib/weblib.php_moodle_url
 */
class moodle_url {
    protected $scheme = ''; // e.g. http
    protected $host = '';
    protected $port = '';
    protected $user = '';
    protected $pass = '';
    protected $path = '';
    protected $fragment = '';
    protected $params = array(); // Associative array of query string params

    /**
     * Pass no arguments to create a url that refers to this page. Use empty string to create empty url.
     *
     * @param mixed $url a number of different forms are accespted:
     *      null - create a URL that is the same as the URL used to load this page, but with no query string
     *      '' - and empty URL
     *      string - a URL, will be parsed into it's bits, including query string
     *      array - as returned from the PHP function parse_url
     *      moodle_url - make a copy of another moodle_url
     * @param array $params these params override anything in the query string
     *      where params have the same name.
     */
    public function __construct($url = null, $params = array()) {
        if ($url === '') {
            // Leave URL blank.
        } else if (is_a($url, 'moodle_url')) {
            $this->scheme = $url->scheme;
            $this->host = $url->host;
            $this->port = $url->port;
            $this->user = $url->user;
            $this->pass = $url->pass;
            $this->path = $url->path;
            $this->fragment = $url->fragment;
            $this->params = $url->params;
        } else {
            if ($url === null) {
                global $ME;
                $url = $ME;
            }
            if (is_string($url)) {
                $url = parse_url($url);
            }
            $parts = $url;
            if ($parts === FALSE) {
                throw new moodle_exception('invalidurl');
            }
            if (isset($parts['query'])) {
                parse_str(str_replace('&amp;', '&', $parts['query']), $this->params);
            }
            unset($parts['query']);
            foreach ($parts as $key => $value) {
                $this->$key = $value;
            }
        }
        $this->params($params);
    }

    /**
     * Add an array of params to the params for this page. The added params override existing ones if they
     * have the same name.
     *
     * @param array $params Defaults to null. If null then return value of param 'name'.
     * @return array params for url.
     */
    public function params($params = null) {
        if (!is_null($params)) {
            return $this->params = $params + $this->params;
        } else {
            return $this->params;
        }
    }

    /**
     * Remove all params if no arguments passed. Remove selected params if
     * arguments are passed. Can be called as either remove_params('param1', 'param2')
     * or remove_params(array('param1', 'param2')).
     *
     * @param mixed $params either an array of param names, or a string param name,
     * @param string $params,... any number of additional param names.
     */
    public function remove_params($params = NULL) {
        if (empty($params)) {
            $this->params = array();
            return;
        }
        if (!is_array($params)) {
            $params = func_get_args();
        }
        foreach ($params as $param) {
            if (isset($this->params[$param])) {
                unset($this->params[$param]);
            }
        }
    }

    /**
     * Add a param to the params for this page. The added param overrides existing one if they
     * have the same name.
     *
     * @param string $paramname name
     * @param string $param value. Defaults to null. If null then return value of param 'name'
     */
    public function param($paramname, $param = null) {
        if (!is_null($param)) {
            $this->params = array($paramname => $param) + $this->params;
        } else {
            return $this->params[$paramname];
        }
    }

    /**
     * Get the params as as a query string.
     * @param array $overrideparams params to add to the output params, these
     *      override existing ones with the same name.
     * @return string query string that can be added to a url.
     */
    public function get_query_string($overrideparams = array()) {
        $arr = array();
        $params = $overrideparams + $this->params;
        foreach ($params as $key => $val) {
           $arr[] = urlencode($key)."=".urlencode($val);
        }
        return implode($arr, "&amp;");
    }

    /**
     * Outputs params as hidden form elements.
     *
     * @param  array $exclude params to ignore
     * @param integer $indent indentation
     * @param array $overrideparams params to add to the output params, these
     *      override existing ones with the same name.
     * @return string html for form elements.
     */
    public function hidden_params_out($exclude = array(), $indent = 0, $overrideparams=array()) {
        $tabindent = str_repeat("\t", $indent);
        $str = '';
        $params = $overrideparams + $this->params;
        foreach ($params as $key => $val) {
            if (FALSE === array_search($key, $exclude)) {
                $val = s($val);
                $str.= "$tabindent<input type=\"hidden\" name=\"$key\" value=\"$val\" />\n";
            }
        }
        return $str;
    }

    /**
     * Output url
     *
     * @param boolean $omitquerystring whether to output page params as a query string in the url.
     * @param array $overrideparams params to add to the output url, these override existing ones with the same name.
     * @return string url
     */
    public function out($omitquerystring = false, $overrideparams = array()) {
        $uri = $this->scheme ? $this->scheme.':'.((strtolower($this->scheme) == 'mailto') ? '':'//'): '';
        $uri .= $this->user ? $this->user.($this->pass? ':'.$this->pass:'').'@':'';
        $uri .= $this->host ? $this->host : '';
        $uri .= $this->port ? ':'.$this->port : '';
        $uri .= $this->path ? $this->path : '';
        if (!$omitquerystring) {
            $querystring = $this->get_query_string($overrideparams);
            if ($querystring) {
                $uri .= '?' . $querystring;
            }
        }
        $uri .= $this->fragment ? '#'.$this->fragment : '';
        return $uri;
    }

    /**
     * Output action url with sesskey
     *
     * @param boolean $noquerystring whether to output page params as a query string in the url.
     * @return string url
     */
    public function out_action($overrideparams = array()) {
        $overrideparams = array('sesskey'=> sesskey()) + $overrideparams;
        return $this->out(false, $overrideparams);
    }
}

/**
 * Determine if there is data waiting to be processed from a form
 *
 * Used on most forms in Moodle to check for data
 * Returns the data as an object, if it's found.
 * This object can be used in foreach loops without
 * casting because it's cast to (array) automatically
 *
 * Checks that submitted POST data exists and returns it as object.
 *
 * @return mixed false or object
 */
function data_submitted() {

    if (empty($_POST)) {
        return false;
    } else {
        return (object)$_POST;
    }
}

/**
 * Given some normal text this function will break up any
 * long words to a given size by inserting the given character
 *
 * It's multibyte savvy and doesn't change anything inside html tags.
 *
 * @param string $string the string to be modified
 * @param int $maxsize maximum length of the string to be returned
 * @param string $cutchar the string used to represent word breaks
 * @return string
 */
function break_up_long_words($string, $maxsize=20, $cutchar=' ') {

/// Loading the textlib singleton instance. We are going to need it.
    $textlib = textlib_get_instance();

/// First of all, save all the tags inside the text to skip them
    $tags = array();
    filter_save_tags($string,$tags);

/// Process the string adding the cut when necessary
    $output = '';
    $length = $textlib->strlen($string);
    $wordlength = 0;

    for ($i=0; $i<$length; $i++) {
        $char = $textlib->substr($string, $i, 1);
        if ($char == ' ' or $char == "\t" or $char == "\n" or $char == "\r" or $char == "<" or $char == ">") {
            $wordlength = 0;
        } else {
            $wordlength++;
            if ($wordlength > $maxsize) {
                $output .= $cutchar;
                $wordlength = 0;
            }
        }
        $output .= $char;
    }

/// Finally load the tags back again
    if (!empty($tags)) {
        $output = str_replace(array_keys($tags), $tags, $output);
    }

    return $output;
}

/**
 * This function will print a button/link/etc. form element
 * that will work on both Javascript and non-javascript browsers.
 * Relies on the Javascript function openpopup in javascript.php
 *
 * All parameters default to null, only $type and $url are mandatory.
 *
 * $url must be relative to home page  eg /mod/survey/stuff.php
 * @param string $url Web link. Either relative to $CFG->wwwroot, or a full URL.
 * @param string $name Name to be assigned to the popup window (this is used by
 *   client-side scripts to "talk" to the popup window)
 * @param string $linkname Text to be displayed as web link
 * @param int $height Height to assign to popup window
 * @param int $width Height to assign to popup window
 * @param string $title Text to be displayed as popup page title
 * @param string $options List of additional options for popup window
 * @param string $return If true, return as a string, otherwise print
 * @param string $id id added to the element
 * @param string $class class added to the element
 * @return string
 * @uses $CFG
 */
function element_to_popup_window ($type=null, $url=null, $name=null, $linkname=null,
                                  $height=400, $width=500, $title=null,
                                  $options=null, $return=false, $id=null, $class=null) {

    if (is_null($url)) {
        debugging('You must give the url to display in the popup. URL is missing - can\'t create popup window.', DEBUG_DEVELOPER);
    }

    global $CFG;

    if ($options == 'none') { // 'none' is legacy, should be removed in v2.0
        $options = null;
    }

    // add some sane default options for popup windows
    if (!$options) {
        $options = 'menubar=0,location=0,scrollbars,resizable';
    }
    if ($width) {
        $options .= ',width='. $width;
    }
    if ($height) {
        $options .= ',height='. $height;
    }
    if ($id) {
        $id = ' id="'.$id.'" ';
    }
    if ($class) {
        $class = ' class="'.$class.'" ';
    }
    if ($name) {
        $_name = $name;
        if (($name = preg_replace("/\s/", '_', $name)) != $_name) {
            debugging('The $name of a popup window shouldn\'t contain spaces - string modified. '. $_name .' changed to '. $name, DEBUG_DEVELOPER);
        }
    } else {
        $name = 'popup';
    }

    // get some default string, using the localized version of legacy defaults
    if (is_null($linkname) || $linkname === '') {
        $linkname = get_string('clickhere');
    }
    if (!$title) {
        $title = get_string('popupwindowname');
    }

    $fullscreen = 0; // must be passed to openpopup
    $element = '';

    switch ($type) {
        case 'button':
            $element = '<input type="button" name="'. $name .'" title="'. $title .'" value="'. $linkname .'" '. $id . $class .
                       "onclick=\"return openpopup('$url', '$name', '$options', $fullscreen);\" />\n";
            break;
        case 'link':
            // Add wwwroot only if the URL does not already start with http:// or https://
            if (!preg_match('|https?://|', $url)) {
                $url = $CFG->wwwroot . $url;
            }
            $element = '<a title="'. s(strip_tags($title)) .'" href="'. $url .'" '.
                       "onclick=\"this.target='$name'; return openpopup('$url', '$name', '$options', $fullscreen);\">$linkname</a>";
            break;
        default :
            print_error('cannotcreatepopupwin');
            break;
    }

    if ($return) {
        return $element;
    } else {
        echo $element;
    }
}

/**
 * Creates and displays (or returns) a link to a popup window, using element_to_popup_window function.
 *
 * @return string html code to display a link to a popup window.
 * @see element_to_popup_window()
 */
function link_to_popup_window ($url, $name=null, $linkname=null,
                               $height=400, $width=500, $title=null,
                               $options=null, $return=false) {

    return element_to_popup_window('link', $url, $name, $linkname, $height, $width, $title, $options, $return, null, null);
}

/**
 * Creates and displays (or returns) a buttons to a popup window, using element_to_popup_window function.
 *
 * @return string html code to display a button to a popup window.
 * @see element_to_popup_window()
 */
function button_to_popup_window ($url, $name=null, $linkname=null,
                                 $height=400, $width=500, $title=null, $options=null, $return=false,
                                 $id=null, $class=null) {

    return element_to_popup_window('button', $url, $name, $linkname, $height, $width, $title, $options, $return, $id, $class);
}


/**
 * Prints a simple button to close a window
 * @param string $name name of the window to close
 * @param boolean $return whether this function should return a string or output it.
 * @param boolean $reloadopener if true, clicking the button will also reload
 *      the page that opend this popup window.
 * @return string if $return is true, nothing otherwise
 */
function close_window_button($name='closewindow', $return=false, $reloadopener = false) {
    global $CFG;

    $js = 'self.close();';
    if ($reloadopener) {
        $js = 'window.opener.location.reload(1);' . $js;
    }

    $output = '';

    $output .= '<div class="closewindow">' . "\n";
    $output .= '<form action="#"><div>';
    $output .= '<input type="button" onclick="' . $js . '" value="'.get_string($name).'" />';
    $output .= '</div></form>';
    $output .= '</div>' . "\n";

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/*
 * Try and close the current window using JavaScript, either immediately, or after a delay.
 * @param integer $delay a delay in seconds before closing the window. Default 0.
 * @param boolean $reloadopener if true, we will see if this window was a pop-up, and try
 *      to reload the parent window before this one closes.
 */
function close_window($delay = 0, $reloadopener = false) {
    global $THEME, $PAGE;

    if (!$PAGE->headerprinted) {
        print_header(get_string('closewindow'));
    } else {
        print_container_end_all(false, $THEME->open_header_containers);
    }

    if ($reloadopener) {
        $function = 'close_window_reloading_opener';
    } else {
        $function = 'close_window';
    }
    echo '<p class="centerpara">' . get_string('windowclosing') . '</p>';
    print_delayed_js_call($delay, $function);

    print_footer('empty');
    exit;
}

/**
 * Given an array of values, output the HTML for a select element with those options.
 * Normally, you only need to use the first few parameters.
 *
 * @param array $options The options to offer. An array of the form
 *      $options[{value}] = {text displayed for that option};
 * @param string $name the name of this form control, as in &lt;select name="..." ...
 * @param string $selected the option to select initially, default none.
 * @param string $nothing The label for the 'nothing is selected' option. Defaults to get_string('choose').
 *      Set this to '' if you don't want a 'nothing is selected' option.
 * @param string $script in not '', then this is added to the &lt;select> element as an onchange handler.
 * @param string $nothingvalue The value corresponding to the $nothing option. Defaults to 0.
 * @param boolean $return if false (the default) the the output is printed directly, If true, the
 *      generated HTML is returned as a string.
 * @param boolean $disabled if true, the select is generated in a disabled state. Default, false.
 * @param int $tabindex if give, sets the tabindex attribute on the &lt;select> element. Default none.
 * @param string $id value to use for the id attribute of the &lt;select> element. If none is given,
 *      then a suitable one is constructed.
 * @param mixed $listbox if false, display as a dropdown menu. If true, display as a list box.
 *      By default, the list box will have a number of rows equal to min(10, count($options)), but if
 *      $listbox is an integer, that number is used for size instead.
 * @param boolean $multiple if true, enable multiple selections, else only 1 item can be selected. Used
 *      when $listbox display is enabled
 * @param string $class value to use for the class attribute of the &lt;select> element. If none is given,
 *      then a suitable one is constructed.
 */
function choose_from_menu ($options, $name, $selected='', $nothing='choose', $script='',
                           $nothingvalue='0', $return=false, $disabled=false, $tabindex=0,
                           $id='', $listbox=false, $multiple=false, $class='') {

    if ($nothing == 'choose') {
        $nothing = get_string('choose') .'...';
    }

    $attributes = ($script) ? 'onchange="'. $script .'"' : '';
    if ($disabled) {
        $attributes .= ' disabled="disabled"';
    }

    if ($tabindex) {
        $attributes .= ' tabindex="'.$tabindex.'"';
    }

    if ($id ==='') {
        $id = 'menu'.$name;
         // name may contaion [], which would make an invalid id. e.g. numeric question type editing form, assignment quickgrading
        $id = str_replace('[', '', $id);
        $id = str_replace(']', '', $id);
    }

    if ($class ==='') {
        $class = 'menu'.$name;
         // name may contaion [], which would make an invalid class. e.g. numeric question type editing form, assignment quickgrading
        $class = str_replace('[', '', $class);
        $class = str_replace(']', '', $class);
    }
    $class = 'select ' . $class; /// Add 'select' selector always

    if ($listbox) {
        if (is_integer($listbox)) {
            $size = $listbox;
        } else {
            $numchoices = count($options);
            if ($nothing) {
                $numchoices += 1;
            }
            $size = min(10, $numchoices);
        }
        $attributes .= ' size="' . $size . '"';
        if ($multiple) {
            $attributes .= ' multiple="multiple"';
        }
    }

    $output = '<select id="'. $id .'" class="'. $class .'" name="'. $name .'" '. $attributes .'>' . "\n";
    if ($nothing) {
        $output .= '   <option value="'. s($nothingvalue) .'"'. "\n";
        if ($nothingvalue === $selected) {
            $output .= ' selected="selected"';
        }
        $output .= '>'. $nothing .'</option>' . "\n";
    }

    if (!empty($options)) {
        foreach ($options as $value => $label) {
            $output .= '   <option value="'. s($value) .'"';
            if ((string)$value == (string)$selected ||
                    (is_array($selected) && in_array($value, $selected))) {
                $output .= ' selected="selected"';
            }
            if ($label === '') {
                $output .= '>'. $value .'</option>' . "\n";
            } else {
                $output .= '>'. $label .'</option>' . "\n";
            }
        }
    }
    $output .= '</select>' . "\n";

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * Choose value 0 or 1 from a menu with options 'No' and 'Yes'.
 * Other options like choose_from_menu.
 * @param string $name
 * @param string $selected
 * @param string $string (defaults to '')
 * @param boolean $return whether this function should return a string or output it (defaults to false)
 * @param boolean $disabled (defaults to false)
 * @param int $tabindex
 */
function choose_from_menu_yesno($name, $selected, $script = '',
        $return = false, $disabled = false, $tabindex = 0) {
    return choose_from_menu(array(get_string('no'), get_string('yes')), $name,
            $selected, '', $script, '0', $return, $disabled, $tabindex);
}

/**
 * Just like choose_from_menu, but takes a nested array (2 levels) and makes a dropdown menu
 * including option headings with the first level.
 */
function choose_from_menu_nested($options,$name,$selected='',$nothing='choose',$script = '',
                                 $nothingvalue=0,$return=false,$disabled=false,$tabindex=0) {

   if ($nothing == 'choose') {
        $nothing = get_string('choose') .'...';
    }

    $attributes = ($script) ? 'onchange="'. $script .'"' : '';
    if ($disabled) {
        $attributes .= ' disabled="disabled"';
    }

    if ($tabindex) {
        $attributes .= ' tabindex="'.$tabindex.'"';
    }

    $output = '<select id="menu'.$name.'" name="'. $name .'" '. $attributes .'>' . "\n";
    if ($nothing) {
        $output .= '   <option value="'. $nothingvalue .'"'. "\n";
        if ($nothingvalue === $selected) {
            $output .= ' selected="selected"';
        }
        $output .= '>'. $nothing .'</option>' . "\n";
    }
    if (!empty($options)) {
        foreach ($options as $section => $values) {

            $output .= '   <optgroup label="'. s(format_string($section)) .'">'."\n";
            foreach ($values as $value => $label) {
                $output .= '   <option value="'. format_string($value) .'"';
                if ((string)$value == (string)$selected) {
                    $output .= ' selected="selected"';
                }
                if ($label === '') {
                    $output .= '>'. $value .'</option>' . "\n";
                } else {
                    $output .= '>'. $label .'</option>' . "\n";
                }
            }
            $output .= '   </optgroup>'."\n";
        }
    }
    $output .= '</select>' . "\n";

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}


/**
 * Given an array of values, creates a group of radio buttons to be part of a form
 *
 * @param array  $options  An array of value-label pairs for the radio group (values as keys)
 * @param string $name     Name of the radiogroup (unique in the form)
 * @param string $checked  The value that is already checked
 */
function choose_from_radio ($options, $name, $checked='', $return=false) {

    static $idcounter = 0;

    if (!$name) {
        $name = 'unnamed';
    }

    $output = '<span class="radiogroup '.$name."\">\n";

    if (!empty($options)) {
        $currentradio = 0;
        foreach ($options as $value => $label) {
            $htmlid = 'auto-rb'.sprintf('%04d', ++$idcounter);
            $output .= ' <span class="radioelement '.$name.' rb'.$currentradio."\">";
            $output .= '<input name="'.$name.'" id="'.$htmlid.'" type="radio" value="'.$value.'"';
            if ($value == $checked) {
                $output .= ' checked="checked"';
            }
            if ($label === '') {
                $output .= ' /> <label for="'.$htmlid.'">'.  $value .'</label></span>' .  "\n";
            } else {
                $output .= ' /> <label for="'.$htmlid.'">'.  $label .'</label></span>' .  "\n";
            }
            $currentradio = ($currentradio + 1) % 2;
        }
    }

    $output .= '</span>' .  "\n";

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/** Display an standard html checkbox with an optional label
 *
 * @param string  $name    The name of the checkbox
 * @param string  $value   The valus that the checkbox will pass when checked
 * @param boolean $checked The flag to tell the checkbox initial state
 * @param string  $label   The label to be showed near the checkbox
 * @param string  $alt     The info to be inserted in the alt tag
 */
function print_checkbox ($name, $value, $checked = true, $label = '', $alt = '', $script='',$return=false) {

    static $idcounter = 0;

    if (!$name) {
        $name = 'unnamed';
    }

    if ($alt) {
        $alt = strip_tags($alt);
    } else {
        $alt = 'checkbox';
    }

    if ($checked) {
        $strchecked = ' checked="checked"';
    } else {
        $strchecked = '';
    }

    $htmlid = 'auto-cb'.sprintf('%04d', ++$idcounter);
    $output  = '<span class="checkbox '.$name."\">";
    $output .= '<input name="'.$name.'" id="'.$htmlid.'" type="checkbox" value="'.$value.'" alt="'.$alt.'"'.$strchecked.' '.((!empty($script)) ? ' onclick="'.$script.'" ' : '').' />';
    if(!empty($label)) {
        $output .= ' <label for="'.$htmlid.'">'.$label.'</label>';
    }
    $output .= '</span>'."\n";

    if (empty($return)) {
        echo $output;
    } else {
        return $output;
    }

}

/** Display an standard html text field with an optional label
 *
 * @param string  $name    The name of the text field
 * @param string  $value   The value of the text field
 * @param string  $label   The label to be showed near the text field
 * @param string  $alt     The info to be inserted in the alt tag
 */
function print_textfield ($name, $value, $alt = '',$size=50,$maxlength=0, $return=false) {

    static $idcounter = 0;

    if (empty($name)) {
        $name = 'unnamed';
    }

    if (empty($alt)) {
        $alt = 'textfield';
    }

    if (!empty($maxlength)) {
        $maxlength = ' maxlength="'.$maxlength.'" ';
    }

    $htmlid = 'auto-tf'.sprintf('%04d', ++$idcounter);
    $output  = '<span class="textfield '.$name."\">";
    $output .= '<input name="'.$name.'" id="'.$htmlid.'" type="text" value="'.$value.'" size="'.$size.'" '.$maxlength.' alt="'.$alt.'" />';

    $output .= '</span>'."\n";

    if (empty($return)) {
        echo $output;
    } else {
        return $output;
    }

}


/**
 * Implements a complete little form with a dropdown menu. When JavaScript is on
 * selecting an option from the dropdown automatically submits the form (while
 * avoiding the usual acessibility problems with this appoach). With JavaScript
 * off, a 'Go' button is printed.
 *
 * @param string $baseurl The target URL up to the point of the variable that changes
 * @param array $options A list of value-label pairs for the popup list
 * @param string $formid id for the control. Must be unique on the page. Used in the HTML.
 * @param string $selected The option that is initially selected
 * @param string $nothing The label for the "no choice" option
 * @param string $help The name of a help page if help is required
 * @param string $helptext The name of the label for the help button
 * @param boolean $return Indicates whether the function should return the HTML
 *         as a string or echo it directly to the page being rendered
 * @param string $targetwindow The name of the target page to open the linked page in.
 * @param string $selectlabel Text to place in a [label] element - preferred for accessibility.
 * @param array $optionsextra an array with the same keys as $options. The values are added within the corresponding <option ...> tag.
 * @param string $submitvalue Optional label for the 'Go' button. Defaults to get_string('go').
 * @param boolean $disabled If true, the menu will be displayed disabled.
 * @param boolean $showbutton If true, the button will always be shown even if JavaScript is available
 * @return string If $return is true then the entire form is returned as a string.
 * @todo Finish documenting this function<br>
 */
function popup_form($baseurl, $options, $formid, $selected='', $nothing='choose', $help='', $helptext='', $return=false,
    $targetwindow='self', $selectlabel='', $optionsextra=NULL, $submitvalue='', $disabled=false, $showbutton=false) {
    global $CFG, $SESSION;
    static $go, $choose;   /// Locally cached, in case there's lots on a page

    if (empty($options)) {
        return '';
    }

    if (empty($submitvalue)){
        if (!isset($go)) {
            $go = get_string('go');
            $submitvalue=$go;
        }
    }
    if ($nothing == 'choose') {
        if (!isset($choose)) {
            $choose = get_string('choose');
        }
        $nothing = $choose.'...';
    }
    if ($disabled) {
        $disabled = ' disabled="disabled"';
    } else {
        $disabled = '';
    }

    // changed reference to document.getElementById('id_abc') instead of document.abc
    // MDL-7861
    $output = '<form action="'.$CFG->wwwroot.'/course/jumpto.php"'.
                        ' method="get" '.
                         $CFG->frametarget.
                        ' id="'.$formid.'"'.
                        ' class="popupform">';
    if ($help) {
        $button = helpbutton($help, $helptext, 'moodle', true, false, '', true);
    } else {
        $button = '';
    }

    if ($selectlabel) {
        $selectlabel = '<label for="'.$formid.'_jump">'.$selectlabel.'</label>';
    }

    if ($showbutton) {
        // Using the no-JavaScript version
        $javascript = '';
    } else if (check_browser_version('MSIE') || (check_browser_version('Opera') && !check_browser_operating_system("Linux"))) {
        //IE and Opera fire the onchange when ever you move into a dropdown list with the keyboard.
        //onfocus will call a function inside dropdown.js. It fixes this IE/Opera behavior.
        //Note: There is a bug on Opera+Linux with the javascript code (first mouse selection is inactive),
        //so we do not fix the Opera behavior on Linux
        $javascript = ' onfocus="initSelect(\''.$formid.'\','.$targetwindow.')"';
    } else {
        //Other browser
        $javascript = ' onchange="'.$targetwindow.
          '.location=document.getElementById(\''.$formid.
          '\').jump.options[document.getElementById(\''.
          $formid.'\').jump.selectedIndex].value;"';
    }

    $output .= '<div style="white-space:nowrap">'.$selectlabel.$button.'<select id="'.$formid.'_jump" name="jump"'.$javascript.$disabled.'>'."\n";

    if ($nothing != '') {
        $selectlabeloption = '';
        if ($selected=='') {
            $selectlabeloption = ' selected="selected"';
        }
        foreach ($options as $value => $label) {  //if one of the options is the empty value, don't make this the default
            if ($value == '') {
                $selected = '';
            }
        }
        $output .= "   <option value=\"javascript:void(0)\"$selectlabeloption>$nothing</option>\n";
    }

    $inoptgroup = false;

    foreach ($options as $value => $label) {

        if ($label == '--') { /// we are ending previous optgroup
            /// Check to see if we already have a valid open optgroup
            /// XHTML demands that there be at least 1 option within an optgroup
            if ($inoptgroup and (count($optgr) > 1) ) {
                $output .= implode('', $optgr);
                $output .= '   </optgroup>';
            }
            $optgr = array();
            $inoptgroup = false;
            continue;
        } else if (substr($label,0,2) == '--') { /// we are starting a new optgroup

            /// Check to see if we already have a valid open optgroup
            /// XHTML demands that there be at least 1 option within an optgroup
            if ($inoptgroup and (count($optgr) > 1) ) {
                $output .= implode('', $optgr);
                $output .= '   </optgroup>';
            }

            unset($optgr);
            $optgr = array();

            $optgr[]  = '   <optgroup label="'. s(format_string(substr($label,2))) .'">';   // Plain labels

            $inoptgroup = true; /// everything following will be in an optgroup
            continue;

        } else {
           if (!empty($CFG->usesid) && !isset($_COOKIE[session_name()]))
            {
                $url = $SESSION->sid_process_url( $baseurl . $value );
            } else
            {
                $url=$baseurl . $value;
            }
            $optstr = '   <option value="' . $url . '"';

            if ($value == $selected) {
                $optstr .= ' selected="selected"';
            }

            if (!empty($optionsextra[$value])) {
                $optstr .= ' '.$optionsextra[$value];
            }

            if ($label) {
                $optstr .= '>'. $label .'</option>' . "\n";
            } else {
                $optstr .= '>'. $value .'</option>' . "\n";
            }

            if ($inoptgroup) {
                $optgr[] = $optstr;
            } else {
                $output .= $optstr;
            }
        }

    }

    /// catch the final group if not closed
    if ($inoptgroup and count($optgr) > 1) {
        $output .= implode('', $optgr);
        $output .= '    </optgroup>';
    }

    $output .= '</select>';
    $output .= '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
    if (!$showbutton) {
        $output .= '<div id="noscript'.$formid.'" style="display: inline;">';
    }
    $output .= '<input type="submit" value="'.$submitvalue.'" '.$disabled.' /></div>';
    if (!$showbutton) {
        $output .= '<script type="text/javascript">'.
                   "\n//<![CDATA[\n".
                   'document.getElementById("noscript'.$formid.'").style.display = "none";'.
                   "\n//]]>\n".'</script>';
    }
    $output .= '</div></form>';

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}


/**
 * Prints some red text
 *
 * @param string $error The text to be displayed in red
 */
function formerr($error) {

    if (!empty($error)) {
        echo '<span class="error">'. $error .'</span>';
    }
}

/**
 * Validates an email to make sure it makes sense.
 *
 * @param string $address The email address to validate.
 * @return boolean
 */
function validate_email($address) {

    return (ereg('^[-!#$%&\'*+\\/0-9=?A-Z^_`a-z{|}~]+'.
                 '(\.[-!#$%&\'*+\\/0-9=?A-Z^_`a-z{|}~]+)*'.
                  '@'.
                  '[-!#$%&\'*+\\/0-9=?A-Z^_`a-z{|}~]+\.'.
                  '[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+$',
                  $address));
}

/**
 * Extracts file argument either from file parameter or PATH_INFO
 * Note: $scriptname parameter is not needed anymore
 *
 * @return string file path (only safe characters)
 */
function get_file_argument() {
    global $SCRIPT;

    $relativepath = optional_param('file', FALSE, PARAM_PATH);

    // then try extract file from PATH_INFO (slasharguments method)
    if ($relativepath === false and isset($_SERVER['PATH_INFO']) and $_SERVER['PATH_INFO'] !== '') {
        // check that PATH_INFO works == must not contain the script name
        if (strpos($_SERVER['PATH_INFO'], $SCRIPT) === false) {
            $relativepath = clean_param(urldecode($_SERVER['PATH_INFO']), PARAM_PATH);
        }
    }

    // note: we are not using any other way because they are not compatible with unicode file names ;-)

    return $relativepath;
}

/**
 * Just returns an array of text formats suitable for a popup menu
 *
 * @uses FORMAT_MOODLE
 * @uses FORMAT_HTML
 * @uses FORMAT_PLAIN
 * @uses FORMAT_MARKDOWN
 * @return array
 */
function format_text_menu() {

    return array (FORMAT_MOODLE => get_string('formattext'),
                  FORMAT_HTML   => get_string('formathtml'),
                  FORMAT_PLAIN  => get_string('formatplain'),
                  FORMAT_MARKDOWN  => get_string('formatmarkdown'));
}

/**
 * Given text in a variety of format codings, this function returns
 * the text as safe HTML.
 *
 * This function should mainly be used for long strings like posts,
 * answers, glossary items etc. For short strings @see format_string().
 *
 * @uses $CFG
 * @uses FORMAT_MOODLE
 * @uses FORMAT_HTML
 * @uses FORMAT_PLAIN
 * @uses FORMAT_WIKI
 * @uses FORMAT_MARKDOWN
 * @param string $text The text to be formatted. This is raw text originally from user input.
 * @param int $format Identifier of the text format to be used
 *            (FORMAT_MOODLE, FORMAT_HTML, FORMAT_PLAIN, FORMAT_WIKI, FORMAT_MARKDOWN)
 * @param  array $options ?
 * @param int $courseid ?
 * @return string
 * @todo Finish documenting this function
 */
function format_text($text, $format=FORMAT_MOODLE, $options=NULL, $courseid=NULL) {
    global $CFG, $COURSE, $DB, $PAGE;

    static $croncache = array();

    $hashstr = '';

    if ($text === '') {
        return ''; // no need to do any filters and cleaning
    }

    if (!isset($options->trusted)) {
        $options->trusted = false;
    }
    if (!isset($options->noclean)) {
        if ($options->trusted and trusttext_active()) {
            // no cleaning if text trusted and noclean not specified
            $options->noclean=true;
        } else {
            $options->noclean=false;
        }
    }
    if (!isset($options->nocache)) {
        $options->nocache=false;
    }
    if (!isset($options->smiley)) {
        $options->smiley=true;
    }
    if (!isset($options->filter)) {
        $options->filter=true;
    }
    if (!isset($options->para)) {
        $options->para=true;
    }
    if (!isset($options->newlines)) {
        $options->newlines=true;
    }
    if (empty($courseid)) {
        $courseid = $COURSE->id;
    }

    if ($options->filter) {
        $filtermanager = filter_manager::instance();
    } else {
        $filtermanager = new null_filter_manager();
    }
    $context = $PAGE->context;

    if (!empty($CFG->cachetext) and empty($options->nocache)) {
        $hashstr .= $text.'-'.$filtermanager->text_filtering_hash($context, $courseid).'-'.(int)$courseid.'-'.current_language().'-'.
                (int)$format.(int)$options->trusted.(int)$options->noclean.(int)$options->smiley.
                (int)$options->filter.(int)$options->para.(int)$options->newlines;

        $time = time() - $CFG->cachetext;
        $md5key = md5($hashstr);
        if (CLI_SCRIPT) {
            if (isset($croncache[$md5key])) {
                return $croncache[$md5key];
            }
        }

        if ($oldcacheitem = $DB->get_record('cache_text', array('md5key'=>$md5key), '*', true)) {
            if ($oldcacheitem->timemodified >= $time) {
                if (CLI_SCRIPT) {
                    if (count($croncache) > 150) {
                        reset($croncache);
                        $key = key($croncache);
                        unset($croncache[$key]);
                    }
                    $croncache[$md5key] = $oldcacheitem->formattedtext;
                }
                return $oldcacheitem->formattedtext;
            }
        }
    }

    switch ($format) {
        case FORMAT_HTML:
            if ($options->smiley) {
                replace_smilies($text);
            }
            if (!$options->noclean) {
                $text = clean_text($text, FORMAT_HTML);
            }
            $text = $filtermanager->filter_text($text, $context, $courseid);
            break;

        case FORMAT_PLAIN:
            $text = s($text); // cleans dangerous JS
            $text = rebuildnolinktag($text);
            $text = str_replace('  ', '&nbsp; ', $text);
            $text = nl2br($text);
            break;

        case FORMAT_WIKI:
            // this format is deprecated
            $text = '<p>NOTICE: Wiki-like formatting has been removed from Moodle.  You should not be seeing
                     this message as all texts should have been converted to Markdown format instead.
                     Please post a bug report to http://moodle.org/bugs with information about where you
                     saw this message.</p>'.s($text);
            break;

        case FORMAT_MARKDOWN:
            $text = markdown_to_html($text);
            if ($options->smiley) {
                replace_smilies($text);
            }
            if (!$options->noclean) {
                $text = clean_text($text, FORMAT_HTML);
            }
            $text = $filtermanager->filter_text($text, $context, $courseid);
            break;

        default:  // FORMAT_MOODLE or anything else
            $text = text_to_html($text, $options->smiley, $options->para, $options->newlines);
            if (!$options->noclean) {
                $text = clean_text($text, FORMAT_HTML);
            }
            $text = $filtermanager->filter_text($text, $context, $courseid);
            break;
    }

    // Warn people that we have removed this old mechanism, just in case they
    // were stupid enough to rely on it.
    if (isset($CFG->currenttextiscacheable)) {
        debugging('Once upon a time, Moodle had a truly evil use of global variables ' .
                'called $CFG->currenttextiscacheable. The good news is that this no ' .
                'longer exists. The bad news is that you seem to be using a filter that '.
                'relies on it. Please seek out and destroy that filter code.', DEBUG_DEVELOPER);
    }

    if (empty($options->nocache) and !empty($CFG->cachetext)) {
        if (CLI_SCRIPT) {
            // special static cron cache - no need to store it in db if its not already there
            if (count($croncache) > 150) {
                reset($croncache);
                $key = key($croncache);
                unset($croncache[$key]);
            }
            $croncache[$md5key] = $text;
            return $text;
        }

        $newcacheitem = new object();
        $newcacheitem->md5key = $md5key;
        $newcacheitem->formattedtext = $text;
        $newcacheitem->timemodified = time();
        if ($oldcacheitem) {                               // See bug 4677 for discussion
            $newcacheitem->id = $oldcacheitem->id;
            try {
                $DB->update_record('cache_text', $newcacheitem);   // Update existing record in the cache table
            } catch (dml_exception $e) {
               // It's unlikely that the cron cache cleaner could have
               // deleted this entry in the meantime, as it allows
               // some extra time to cover these cases.
            }
        } else {
            try {
                $DB->insert_record('cache_text', $newcacheitem);   // Insert a new record in the cache table
            } catch (dml_exception $e) {
               // Again, it's possible that another user has caused this
               // record to be created already in the time that it took
               // to traverse this function.  That's OK too, as the
               // call above handles duplicate entries, and eventually
               // the cron cleaner will delete them.
            }
        }
    }

    return $text;
}

/** Converts the text format from the value to the 'internal'
 *  name or vice versa. $key can either be the value or the name
 *  and you get the other back.
 *
 *  @param mixed int 0-4 or string one of 'moodle','html','plain','markdown'
 *  @return mixed as above but the other way around!
 */
function text_format_name( $key ) {
  $lookup = array();
  $lookup[FORMAT_MOODLE] = 'moodle';
  $lookup[FORMAT_HTML] = 'html';
  $lookup[FORMAT_PLAIN] = 'plain';
  $lookup[FORMAT_MARKDOWN] = 'markdown';
  $value = "error";
  if (!is_numeric($key)) {
    $key = strtolower( $key );
    $value = array_search( $key, $lookup );
  }
  else {
    if (isset( $lookup[$key] )) {
      $value =  $lookup[ $key ];
    }
  }
  return $value;
}

/**
 * Resets all data related to filters, called during upgrade or when filter settings change.
 * @return void
 */
function reset_text_filters_cache() {
    global $CFG, $DB;

    $DB->delete_records('cache_text');
    $purifdir = $CFG->dataroot.'/cache/htmlpurifier';
    remove_dir($purifdir, true);
}

/** Given a simple string, this function returns the string
 *  processed by enabled string filters if $CFG->filterall is enabled
 *
 *  This function should be used to print short strings (non html) that
 *  need filter processing e.g. activity titles, post subjects,
 *  glossary concepts.
 *
 *  @param string  $string     The string to be filtered.
 *  @param boolean $striplinks To strip any link in the result text (Moodle 1.8 default changed from false to true! MDL-8713)
 *  @param int     $courseid   Current course as filters can, potentially, use it
 *  @return string
 */
function format_string($string, $striplinks=true, $courseid=NULL ) {
    global $CFG, $COURSE, $PAGE;

    //We'll use a in-memory cache here to speed up repeated strings
    static $strcache = false;

    if ($strcache === false or count($strcache) > 2000 ) { // this number might need some tuning to limit memory usage in cron
        $strcache = array();
    }

    //init course id
    if (empty($courseid)) {
        $courseid = $COURSE->id;
    }

    //Calculate md5
    $md5 = md5($string.'<+>'.$striplinks.'<+>'.$courseid.'<+>'.current_language());

    //Fetch from cache if possible
    if (isset($strcache[$md5])) {
        return $strcache[$md5];
    }

    // First replace all ampersands not followed by html entity code
    $string = preg_replace("/\&(?![a-zA-Z0-9#]{1,8};)/", "&amp;", $string);

    if (!empty($CFG->filterall) && $CFG->version >= 2009040600) { // Avoid errors during the upgrade to the new system.
        $context = $PAGE->context;
        $string = filter_manager::instance()->filter_string($string, $context, $courseid);
    }

    // If the site requires it, strip ALL tags from this string
    if (!empty($CFG->formatstringstriptags)) {
        $string = strip_tags($string);

    } else {
        // Otherwise strip just links if that is required (default)
        if ($striplinks) {  //strip links in string
            $string = preg_replace('/(<a\s[^>]+?>)(.+?)(<\/a>)/is','$2',$string);
        }
        $string = clean_text($string);
    }

    //Store to cache
    $strcache[$md5] = $string;

    return $string;
}

/**
 * Given text in a variety of format codings, this function returns
 * the text as plain text suitable for plain email.
 *
 * @uses FORMAT_MOODLE
 * @uses FORMAT_HTML
 * @uses FORMAT_PLAIN
 * @uses FORMAT_WIKI
 * @uses FORMAT_MARKDOWN
 * @param string $text The text to be formatted. This is raw text originally from user input.
 * @param int $format Identifier of the text format to be used
 *            (FORMAT_MOODLE, FORMAT_HTML, FORMAT_PLAIN, FORMAT_WIKI, FORMAT_MARKDOWN)
 * @return string
 */
function format_text_email($text, $format) {

    switch ($format) {

        case FORMAT_PLAIN:
            return $text;
            break;

        case FORMAT_WIKI:
            $text = wiki_to_html($text);
        /// This expression turns links into something nice in a text format. (Russell Jungwirth)
        /// From: http://php.net/manual/en/function.eregi-replace.php and simplified
            $text = eregi_replace('(<a [^<]*href=["|\']?([^ "\']*)["|\']?[^>]*>([^<]*)</a>)','\\3 [ \\2 ]', $text);
            return strtr(strip_tags($text), array_flip(get_html_translation_table(HTML_ENTITIES)));
            break;

        case FORMAT_HTML:
            return html_to_text($text);
            break;

        case FORMAT_MOODLE:
        case FORMAT_MARKDOWN:
        default:
            $text = eregi_replace('(<a [^<]*href=["|\']?([^ "\']*)["|\']?[^>]*>([^<]*)</a>)','\\3 [ \\2 ]', $text);
            return strtr(strip_tags($text), array_flip(get_html_translation_table(HTML_ENTITIES)));
            break;
    }
}

/**
 * Given some text in HTML format, this function will pass it
 * through any filters that have been configured for this context.
 *
 * @param string $text The text to be passed through format filters
 * @param int $courseid The current course.
 * @return string the filtered string.
 */
function filter_text($text, $courseid=NULL) {
    global $CFG, $COURSE, $PAGE;

    if (empty($courseid)) {
        $courseid = $COURSE->id;       // (copied from format_text)
    }

    $context = $PAGE->context;

    return filter_manager::instance()->filter_text($text, $context, $courseid);
}
/**
 * Formats activity intro text
 * @param string $module name of module
 * @param object $activity instance of activity
 * @param int $cmid course module id
 * @param bool $filter filter resulting html text
 * @return text
 */
function format_module_intro($module, $activity, $cmid, $filter=true) {
    global $CFG;
    require_once("$CFG->libdir/filelib.php");
    $options = (object)array('noclean'=>true, 'para'=>false, 'filter'=>false);
    $context = get_context_instance(CONTEXT_MODULE, $cmid);
    $intro = file_rewrite_pluginfile_urls($activity->intro, 'pluginfile.php', $context->id, $module.'_intro', null);
    return trim(format_text($intro, $activity->introformat, $options));
}

/**
 * Legacy function, used for cleaning of old forum and glossary text only.
 * @param string $text text that may contain TRUSTTEXT marker
 * @return text without any TRUSTTEXT marker
 */
function trusttext_strip($text) {
    global $CFG;

    while (true) { //removing nested TRUSTTEXT
        $orig = $text;
        $text = str_replace('#####TRUSTTEXT#####', '', $text);
        if (strcmp($orig, $text) === 0) {
            return $text;
        }
    }
}

/**
 * Must be called before editing of all texts
 * with trust flag. Removes all XSS nasties
 * from texts stored in database if needed.
 * @param object $object data object with xxx, xxxformat and xxxtrust fields
 * @param string $field name of text field
 * @param object $context active context
 * @return object updated $object
 */
function trusttext_pre_edit($object, $field, $context) {
    $trustfield  = $field.'trust';
    $formatfield = $field.'format'; 
    
    if (!$object->$trustfield or !trusttext_trusted($context)) {
        $object->$field = clean_text($object->$field, $object->$formatfield);
    }

    return $object;
}

/**
 * Is urrent user trusted to enter no dangerous XSS in this context?
 * Please note the user must be in fact trusted everywhere on this server!!
 * @param $context
 * @return bool true if user trusted
 */
function trusttext_trusted($context) {
    return (trusttext_active() and has_capability('moodle/site:trustcontent', $context)); 
}

/**
 * Is trusttext feature active?
 * @param $context
 * @return bool
 */
function trusttext_active() {
    global $CFG;

    return !empty($CFG->enabletrusttext); 
}

/**
 * Given raw text (eg typed in by a user), this function cleans it up
 * and removes any nasty tags that could mess up Moodle pages.
 *
 * @uses FORMAT_MOODLE
 * @uses FORMAT_PLAIN
 * @uses ALLOWED_TAGS
 * @param string $text The text to be cleaned
 * @param int $format Identifier of the text format to be used
 *            (FORMAT_MOODLE, FORMAT_HTML, FORMAT_PLAIN, FORMAT_WIKI, FORMAT_MARKDOWN)
 * @return string The cleaned up text
 */
function clean_text($text, $format=FORMAT_MOODLE) {

    global $ALLOWED_TAGS, $CFG;

    if (empty($text) or is_numeric($text)) {
       return (string)$text;
    }

    switch ($format) {
        case FORMAT_PLAIN:
        case FORMAT_MARKDOWN:
            return $text;

        default:

            if (!empty($CFG->enablehtmlpurifier)) {
                $text = purify_html($text);
            } else {
            /// Fix non standard entity notations
                $text = preg_replace('/(&#[0-9]+)(;?)/', "\\1;", $text);
                $text = preg_replace('/(&#x[0-9a-fA-F]+)(;?)/', "\\1;", $text);

            /// Remove tags that are not allowed
                $text = strip_tags($text, $ALLOWED_TAGS);

            /// Clean up embedded scripts and , using kses
                $text = cleanAttributes($text);

            /// Again remove tags that are not allowed
                $text = strip_tags($text, $ALLOWED_TAGS);

            }

        /// Remove potential script events - some extra protection for undiscovered bugs in our code
            $text = eregi_replace("([^a-z])language([[:space:]]*)=", "\\1Xlanguage=", $text);
            $text = eregi_replace("([^a-z])on([a-z]+)([[:space:]]*)=", "\\1Xon\\2=", $text);

            return $text;
    }
}

/**
 * KSES replacement cleaning function - uses HTML Purifier.
 */
function purify_html($text) {
    global $CFG;

    // this can not be done only once because we sometimes need to reset the cache
    $cachedir = $CFG->dataroot.'/cache/htmlpurifier';
    $status = check_dir_exists($cachedir, true, true);

    static $purifier = false;
    static $config;
    if ($purifier === false) {
        require_once $CFG->libdir.'/htmlpurifier/HTMLPurifier.safe-includes.php';
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Core', 'ConvertDocumentToFragment', true);
        $config->set('Core', 'Encoding', 'UTF-8');
        $config->set('HTML', 'Doctype', 'XHTML 1.0 Transitional');
        $config->set('Cache', 'SerializerPath', $cachedir);
        $config->set('URI', 'AllowedSchemes', array('http'=>1, 'https'=>1, 'ftp'=>1, 'irc'=>1, 'nntp'=>1, 'news'=>1, 'rtsp'=>1, 'teamspeak'=>1, 'gopher'=>1, 'mms'=>1));
        $config->set('Attr', 'AllowedFrameTargets', array('_blank'));
        $purifier = new HTMLPurifier($config);
    }
    return $purifier->purify($text);
}

/**
 * This function takes a string and examines it for HTML tags.
 * If tags are detected it passes the string to a helper function {@link cleanAttributes2()}
 *  which checks for attributes and filters them for malicious content
 *         17/08/2004              ::          Eamon DOT Costello AT dcu DOT ie
 *
 * @param string $str The string to be examined for html tags
 * @return string
 */
function cleanAttributes($str){
    $result = preg_replace_callback(
            '%(<[^>]*(>|$)|>)%m', #search for html tags
            "cleanAttributes2",
            $str
            );
    return  $result;
}

/**
 * This function takes a string with an html tag and strips out any unallowed
 * protocols e.g. javascript:
 * It calls ancillary functions in kses which are prefixed by kses
*        17/08/2004              ::          Eamon DOT Costello AT dcu DOT ie
 *
 * @param array $htmlArray An array from {@link cleanAttributes()}, containing in its 1st
 *              element the html to be cleared
 * @return string
 */
function cleanAttributes2($htmlArray){

    global $CFG, $ALLOWED_PROTOCOLS;
    require_once($CFG->libdir .'/kses.php');

    $htmlTag = $htmlArray[1];
    if (substr($htmlTag, 0, 1) != '<') {
        return '&gt;';  //a single character ">" detected
    }
    if (!preg_match('%^<\s*(/\s*)?([a-zA-Z0-9]+)([^>]*)>?$%', $htmlTag, $matches)) {
        return ''; // It's seriously malformed
    }
    $slash = trim($matches[1]); //trailing xhtml slash
    $elem = $matches[2];    //the element name
    $attrlist = $matches[3]; // the list of attributes as a string

    $attrArray = kses_hair($attrlist, $ALLOWED_PROTOCOLS);

    $attStr = '';
    foreach ($attrArray as $arreach) {
        $arreach['name'] = strtolower($arreach['name']);
        if ($arreach['name'] == 'style') {
            $value = $arreach['value'];
            while (true) {
                $prevvalue = $value;
                $value = kses_no_null($value);
                $value = preg_replace("/\/\*.*\*\//Us", '', $value);
                $value = kses_decode_entities($value);
                $value = preg_replace('/(&#[0-9]+)(;?)/', "\\1;", $value);
                $value = preg_replace('/(&#x[0-9a-fA-F]+)(;?)/', "\\1;", $value);
                if ($value === $prevvalue) {
                    $arreach['value'] = $value;
                    break;
                }
            }
            $arreach['value'] = preg_replace("/j\s*a\s*v\s*a\s*s\s*c\s*r\s*i\s*p\s*t/i", "Xjavascript", $arreach['value']);
            $arreach['value'] = preg_replace("/e\s*x\s*p\s*r\s*e\s*s\s*s\s*i\s*o\s*n/i", "Xexpression", $arreach['value']);
            $arreach['value'] = preg_replace("/b\s*i\s*n\s*d\s*i\s*n\s*g/i", "Xbinding", $arreach['value']);
        } else if ($arreach['name'] == 'href') {
            //Adobe Acrobat Reader XSS protection
            $arreach['value'] = preg_replace('/(\.(pdf|fdf|xfdf|xdp|xfd)[^#]*)#.*$/i', '$1', $arreach['value']);
        }
        $attStr .=  ' '.$arreach['name'].'="'.$arreach['value'].'"';
    }

    $xhtml_slash = '';
    if (preg_match('%/\s*$%', $attrlist)) {
        $xhtml_slash = ' /';
    }
    return '<'. $slash . $elem . $attStr . $xhtml_slash .'>';
}

/**
 * Replaces all known smileys in the text with image equivalents
 *
 * @uses $CFG
 * @param string $text Passed by reference. The string to search for smily strings.
 * @return string
 */
function replace_smilies(&$text) {

    global $CFG;

    if (empty($CFG->emoticons)) { /// No emoticons defined, nothing to process here
        return;
    }

    $lang = current_language();
    $emoticonstring = $CFG->emoticons;
    static $e = array();
    static $img = array();
    static $emoticons = null;

    if (is_null($emoticons)) {
        $emoticons = array();
        if ($emoticonstring) {
            $items = explode('{;}', $CFG->emoticons);
            foreach ($items as $item) {
               $item = explode('{:}', $item);
              $emoticons[$item[0]] = $item[1];
            }
        }
    }

    if (empty($img[$lang])) {  /// After the first time this is not run again
        $e[$lang] = array();
        $img[$lang] = array();
        foreach ($emoticons as $emoticon => $image){
            $alttext = get_string($image, 'pix');
            $alttext = preg_replace('/^\[\[(.*)\]\]$/', '$1', $alttext); /// Clean alttext in case there isn't lang string for it.
            $e[$lang][] = $emoticon;
            $img[$lang][] = '<img alt="'. $alttext .'" width="15" height="15" src="'. $CFG->pixpath .'/s/'. $image .'.gif" />';
        }
    }

    // Exclude from transformations all the code inside <script> tags
    // Needed to solve Bug 1185. Thanks to jouse 2001 detecting it. :-)
    // Based on code from glossary fiter by Williams Castillo.
    //       - Eloy

    // Detect all the <script> zones to take out
    $excludes = array();
    preg_match_all('/<script language(.+?)<\/script>/is',$text,$list_of_excludes);

    // Take out all the <script> zones from text
    foreach (array_unique($list_of_excludes[0]) as $key=>$value) {
        $excludes['<+'.$key.'+>'] = $value;
    }
    if ($excludes) {
        $text = str_replace($excludes,array_keys($excludes),$text);
    }

/// this is the meat of the code - this is run every time
    $text = str_replace($e[$lang], $img[$lang], $text);

    // Recover all the <script> zones to text
    if ($excludes) {
        $text = str_replace(array_keys($excludes),$excludes,$text);
    }
}

/**
 * This code is called from help.php to inject a list of smilies into the
 * emoticons help file.
 *
 * @return string HTML for a list of smilies.
 */
function get_emoticons_list_for_help_file(){
    global $CFG, $SESSION;
    if (empty($CFG->emoticons)) {
        return '';
    }

    require_js(array('yui_yahoo', 'yui_event'));
    $items = explode('{;}', $CFG->emoticons);
    $output = '<ul id="emoticonlist">';
    foreach ($items as $item) {
        $item = explode('{:}', $item);
        $output .= '<li><img src="' . $CFG->pixpath.'/s/' . $item[1] . '.gif" alt="' .
                $item[0] . '" /><code>' . $item[0] . '</code></li>';
    }
    $output .= '</ul>';
    if (!empty($SESSION->inserttextform)) {
        $formname = $SESSION->inserttextform;
        $fieldname = $SESSION->inserttextfield;
    } else {
        $formname = 'theform';
        $fieldname = 'message';
    }

    $output .= print_js_call('emoticons_help.init', array($formname, $fieldname, 'emoticonlist'), true);
    return $output;

}

/**
 * Given plain text, makes it into HTML as nicely as possible.
 * May contain HTML tags already
 *
 * @uses $CFG
 * @param string $text The string to convert.
 * @param boolean $smiley Convert any smiley characters to smiley images?
 * @param boolean $para If true then the returned string will be wrapped in paragraph tags
 * @param boolean $newlines If true then lines newline breaks will be converted to HTML newline breaks.
 * @return string
 */

function text_to_html($text, $smiley=true, $para=true, $newlines=true) {
///

    global $CFG;

/// Remove any whitespace that may be between HTML tags
    $text = eregi_replace(">([[:space:]]+)<", "><", $text);

/// Remove any returns that precede or follow HTML tags
    $text = eregi_replace("([\n\r])<", " <", $text);
    $text = eregi_replace(">([\n\r])", "> ", $text);

    convert_urls_into_links($text);

/// Make returns into HTML newlines.
    if ($newlines) {
        $text = nl2br($text);
    }

/// Turn smileys into images.
    if ($smiley) {
        replace_smilies($text);
    }

/// Wrap the whole thing in a paragraph tag if required
    if ($para) {
        return '<p>'.$text.'</p>';
    } else {
        return $text;
    }
}

/**
 * Given Markdown formatted text, make it into XHTML using external function
 *
 * @uses $CFG
 * @param string $text The markdown formatted text to be converted.
 * @return string Converted text
 */
function markdown_to_html($text) {
    global $CFG;

    require_once($CFG->libdir .'/markdown.php');

    return Markdown($text);
}

/**
 * Given HTML text, make it into plain text using external function
 *
 * @uses $CFG
 * @param string $html The text to be converted.
 * @return string
 */
function html_to_text($html) {

    global $CFG;

    require_once($CFG->libdir .'/html2text.php');

    $h2t = new html2text($html);
    $result = $h2t->get_text();

    // html2text does not fix HTML entities so handle those here.
    $result = trim(html_entity_decode($result, ENT_NOQUOTES, 'UTF-8'));

    return $result;
}

/**
 * Given some text this function converts any URLs it finds into HTML links
 *
 * @param string $text Passed in by reference. The string to be searched for urls.
 */
function convert_urls_into_links(&$text) {
/// Make lone URLs into links.   eg http://moodle.com/
    $text = eregi_replace("([[:space:]]|^|\(|\[)([[:alnum:]]+)://([^[:space:]]*)([[:alnum:]#?/&=])",
                          "\\1<a href=\"\\2://\\3\\4\" target=\"_blank\">\\2://\\3\\4</a>", $text);

/// eg www.moodle.com
    $text = eregi_replace("([[:space:]]|^|\(|\[)www\.([^[:space:]]*)([[:alnum:]#?/&=])",
                          "\\1<a href=\"http://www.\\2\\3\" target=\"_blank\">www.\\2\\3</a>", $text);
}

/**
 * This function will highlight search words in a given string
 * It cares about HTML and will not ruin links.  It's best to use
 * this function after performing any conversions to HTML.
 *
 * @param string $needle The search string. Syntax like "word1 +word2 -word3" is dealt with correctly.
 * @param string $haystack The string (HTML) within which to highlight the search terms.
 * @param boolean $matchcase whether to do case-sensitive. Default case-insensitive.
 * @param string $prefix the string to put before each search term found.
 * @param string $suffix the string to put after each search term found.
 * @return string The highlighted HTML.
 */
function highlight($needle, $haystack, $matchcase = false,
        $prefix = '<span class="highlight">', $suffix = '</span>') {

/// Quick bail-out in trivial cases.
    if (empty($needle) or empty($haystack)) {
        return $haystack;
    }

/// Break up the search term into words, discard any -words and build a regexp.
    $words = preg_split('/ +/', trim($needle));
    foreach ($words as $index => $word) {
        if (strpos($word, '-') === 0) {
            unset($words[$index]);
        } else if (strpos($word, '+') === 0) {
            $words[$index] = '\b' . preg_quote(ltrim($word, '+'), '/') . '\b'; // Match only as a complete word.
        } else {
            $words[$index] = preg_quote($word, '/');
        }
    }
    $regexp = '/(' . implode('|', $words) . ')/u'; // u is do UTF-8 matching.
    if (!$matchcase) {
        $regexp .= 'i';
    }

/// Another chance to bail-out if $search was only -words
    if (empty($words)) {
        return $haystack;
    }

/// Find all the HTML tags in the input, and store them in a placeholders array.
    $placeholders = array();
    $matches = array();
    preg_match_all('/<[^>]*>/', $haystack, $matches);
    foreach (array_unique($matches[0]) as $key => $htmltag) {
        $placeholders['<|' . $key . '|>'] = $htmltag;
    }

/// In $hastack, replace each HTML tag with the corresponding placeholder.
    $haystack = str_replace($placeholders, array_keys($placeholders), $haystack);

/// In the resulting string, Do the highlighting.
    $haystack = preg_replace($regexp, $prefix . '$1' . $suffix, $haystack);

/// Turn the placeholders back into HTML tags.
    $haystack = str_replace(array_keys($placeholders), $placeholders, $haystack);

    return $haystack;
}

/**
 * This function will highlight instances of $needle in $haystack
 * It's faster that the above function and doesn't care about
 * HTML or anything.
 *
 * @param string $needle The string to search for
 * @param string $haystack The string to search for $needle in
 * @return string
 */
function highlightfast($needle, $haystack) {

    if (empty($needle) or empty($haystack)) {
        return $haystack;
    }

    $parts = explode(moodle_strtolower($needle), moodle_strtolower($haystack));

    if (count($parts) === 1) {
        return $haystack;
    }

    $pos = 0;

    foreach ($parts as $key => $part) {
        $parts[$key] = substr($haystack, $pos, strlen($part));
        $pos += strlen($part);

        $parts[$key] .= '<span class="highlight">'.substr($haystack, $pos, strlen($needle)).'</span>';
        $pos += strlen($needle);
    }

    return str_replace('<span class="highlight"></span>', '', join('', $parts));
}

/**
 * Return a string containing 'lang', xml:lang and optionally 'dir' HTML attributes.
 * Internationalisation, for print_header and backup/restorelib.
 * @param $dir Default false.
 * @return string Attributes.
 */
function get_html_lang($dir = false) {
    $direction = '';
    if ($dir) {
        if (get_string('thisdirection') == 'rtl') {
            $direction = ' dir="rtl"';
        } else {
            $direction = ' dir="ltr"';
        }
    }
    //Accessibility: added the 'lang' attribute to $direction, used in theme <html> tag.
    $language = str_replace('_', '-', str_replace('_utf8', '', current_language()));
    @header('Content-Language: '.$language);
    return ($direction.' lang="'.$language.'" xml:lang="'.$language.'"');
}

/**
 * Return the markup for the destination of the 'Skip to main content' links.
 *   Accessibility improvement for keyboard-only users.
 *   Used in course formats, /index.php and /course/index.php
 * @return string HTML element.
 */
function skip_main_destination() {
    return '<span id="maincontent"></span>';
}


/// STANDARD WEB PAGE PARTS ///////////////////////////////////////////////////

/**
 * Print a standard header
 *
 * @uses $USER
 * @uses $CFG
 * @uses $SESSION
 * @param string  $title Appears at the top of the window
 * @param string  $heading Appears at the top of the page
 * @param array   $navigation Array of $navlinks arrays (keys: name, link, type) for use as breadcrumbs links
 * @param string  $focus Indicates form element to get cursor focus on load eg  inputform.password
 * @param string  $meta Meta tags to be added to the header
 * @param boolean $cache Should this page be cacheable?
 * @param string  $button HTML code for a button (usually for module editing)
 * @param string  $menu HTML code for a popup menu
 * @param boolean $usexml use XML for this page
 * @param string  $bodytags This text will be included verbatim in the <body> tag (useful for onload() etc)
 * @param bool    $return If true, return the visible elements of the header instead of echoing them.
 */
function print_header ($title='', $heading='', $navigation='', $focus='',
                       $meta='', $cache=true, $button='&nbsp;', $menu='',
                       $usexml=false, $bodytags='', $return=false) {

    global $USER, $CFG, $THEME, $SESSION, $ME, $SITE, $COURSE, $PAGE;

    if (gettype($navigation) == 'string' && strlen($navigation) != 0 && $navigation != 'home') {
        debugging("print_header() was sent a string as 3rd ($navigation) parameter. "
                . "This is deprecated in favour of an array built by build_navigation(). Please upgrade your code.", DEBUG_DEVELOPER);
    }

    $PAGE->set_state(moodle_page::STATE_PRINTING_HEADER);

    $heading = format_string($heading); // Fix for MDL-8582

    if (CLI_SCRIPT) {
        $output = $heading;
        if ($return) {
            return $output;
        } else {
            console_write($output . "\n",'',false);
            return;
        }
    }

/// Add the required stylesheets
    $stylesheetshtml = '';
    foreach ($CFG->stylesheets as $stylesheet) {
        $stylesheetshtml .= '<link rel="stylesheet" type="text/css" href="'.$stylesheet.'" />'."\n";
    }
    $meta = $stylesheetshtml.$meta;


/// Add the meta page from the themes if any were requested

    $metapage = '';

    if (!isset($THEME->standardmetainclude) || $THEME->standardmetainclude) {
        ob_start();
        include_once($CFG->dirroot.'/theme/standard/meta.php');
        $metapage .= ob_get_contents();
        ob_end_clean();
    }

    if ($THEME->parent && (!isset($THEME->parentmetainclude) || $THEME->parentmetainclude)) {
        if (file_exists($CFG->dirroot.'/theme/'.$THEME->parent.'/meta.php')) {
            ob_start();
            include_once($CFG->dirroot.'/theme/'.$THEME->parent.'/meta.php');
            $metapage .= ob_get_contents();
            ob_end_clean();
        }
    }

    if (!isset($THEME->metainclude) || $THEME->metainclude) {
        if (file_exists($CFG->dirroot.'/theme/'.current_theme().'/meta.php')) {
            ob_start();
            include_once($CFG->dirroot.'/theme/'.current_theme().'/meta.php');
            $metapage .= ob_get_contents();
            ob_end_clean();
        }
    }

    $meta = $meta."\n".$metapage;

    $meta .= "\n".require_js('',1);

    $meta .= standard_js_config();

/// Set up some navigation variables

    if (is_newnav($navigation)){
        $home = false;
    } else {
        if ($navigation == 'home') {
            $home = true;
            $navigation = '';
        } else {
            $home = false;
        }
    }

/// This is another ugly hack to make navigation elements available to print_footer later
    $THEME->title      = $title;
    $THEME->heading    = $heading;
    $THEME->navigation = $navigation;
    $THEME->button     = $button;
    $THEME->menu       = $menu;
    $navmenulist = isset($THEME->navmenulist) ? $THEME->navmenulist : '';

    if ($button == '') {
        $button = '&nbsp;';
    }

    if (file_exists($CFG->dataroot.'/'.SITEID.'/maintenance.html')) {
        $button = '<a href="'.$CFG->wwwroot.'/'.$CFG->admin.'/maintenance.php">'.get_string('maintenancemode', 'admin').'</a> '.$button;
        if(!empty($title)) {
            $title .= ' - ';
        }
        $title .= get_string('maintenancemode', 'admin');
    }

    if (!$menu and $navigation) {
        if (empty($CFG->loginhttps)) {
            $wwwroot = $CFG->wwwroot;
        } else {
            $wwwroot = str_replace('http:','https:',$CFG->wwwroot);
        }
        $menu = user_login_string($COURSE);
    }

    if (isset($SESSION->justloggedin)) {
        unset($SESSION->justloggedin);
        if (!empty($CFG->displayloginfailures)) {
            if (!empty($USER->username) and $USER->username != 'guest') {
                if ($count = count_login_failures($CFG->displayloginfailures, $USER->username, $USER->lastlogin)) {
                    $menu .= '&nbsp;<font size="1">';
                    if (empty($count->accounts)) {
                        $menu .= get_string('failedloginattempts', '', $count);
                    } else {
                        $menu .= get_string('failedloginattemptsall', '', $count);
                    }
                    if (has_capability('coursereport/log:view', get_context_instance(CONTEXT_SYSTEM))) {
                        $menu .= ' (<a href="'.$CFG->wwwroot.'/course/report/log/index.php'.
                                             '?chooselog=1&amp;id=1&amp;modid=site_errors">'.get_string('logs').'</a>)';
                    }
                    $menu .= '</font>';
                }
            }
        }
    }


    $meta = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />' .
            "\n" . $meta . "\n";
    if (!$usexml) {
        @header('Content-Type: text/html; charset=utf-8');
    }
    @header('Content-Script-Type: text/javascript');
    @header('Content-Style-Type: text/css');

    //Accessibility: added the 'lang' attribute to $direction, used in theme <html> tag.
    $direction = get_html_lang($dir=true);

    if ($cache) {  // Allow caching on "back" (but not on normal clicks)
        @header('Cache-Control: private, pre-check=0, post-check=0, max-age=0');
        @header('Pragma: no-cache');
        @header('Expires: ');
    } else {       // Do everything we can to always prevent clients and proxies caching
        @header('Cache-Control: no-store, no-cache, must-revalidate');
        @header('Cache-Control: post-check=0, pre-check=0', false);
        @header('Pragma: no-cache');
        @header('Expires: Mon, 20 Aug 1969 09:23:00 GMT');
        @header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');

        $meta .= "\n<meta http-equiv=\"pragma\" content=\"no-cache\" />";
        $meta .= "\n<meta http-equiv=\"expires\" content=\"0\" />";
    }
    @header('Accept-Ranges: none');

    $currentlanguage = current_language();

    if (empty($usexml)) {
        $direction =  ' xmlns="http://www.w3.org/1999/xhtml"'. $direction;  // See debug_header
    } else {
        $mathplayer = preg_match("/MathPlayer/i", $_SERVER['HTTP_USER_AGENT']);
        if(!$mathplayer) {
            header('Content-Type: application/xhtml+xml');
        }
        echo '<?xml version="1.0" ?>'."\n";
        if (!empty($CFG->xml_stylesheets)) {
            $stylesheets = explode(';', $CFG->xml_stylesheets);
            foreach ($stylesheets as $stylesheet) {
                echo '<?xml-stylesheet type="text/xsl" href="'. $CFG->wwwroot .'/'. $stylesheet .'" ?>' . "\n";
            }
        }
        echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1';
        if (!empty($CFG->xml_doctype_extra)) {
            echo ' plus '. $CFG->xml_doctype_extra;
        }
        echo '//' . strtoupper($currentlanguage) . '" "'. $CFG->xml_dtd .'">'."\n";
        $direction = " xmlns=\"http://www.w3.org/1999/xhtml\"
                       xmlns:math=\"http://www.w3.org/1998/Math/MathML\"
                       xmlns:xlink=\"http://www.w3.org/1999/xlink\"
                       $direction";
        if($mathplayer) {
            $meta .= '<object id="mathplayer" classid="clsid:32F66A20-7614-11D4-BD11-00104BD3F987">' . "\n";
            $meta .= '<!--comment required to prevent this becoming an empty tag-->'."\n";
            $meta .= '</object>'."\n";
            $meta .= '<?import namespace="math" implementation="#mathplayer" ?>' . "\n";
        }
    }

    // Clean up the title

    $title = format_string($title);    // fix for MDL-8582
    $title = str_replace('"', '&quot;', $title);

    // Create class and id for this page
    $pageid = $PAGE->pagetype;
    $pageclass = $PAGE->bodyclasses;
    $bodytags .= ' class="'.$pageclass.'" id="'.$pageid.'"';

    ob_start();
    include($CFG->header);
    $output = ob_get_contents();
    ob_end_clean();

    // container debugging info
    $THEME->open_header_containers = open_containers();

    // Skip to main content, see skip_main_destination().
    if ($pageid=='course-view' or $pageid=='site-index' or $pageid=='course-index') {
        $skiplink = '<a class="skip" href="#maincontent">'.get_string('tocontent', 'access').'</a>';
        if (! preg_match('/(.*<div[^>]+id="page"[^>]*>)(.*)/s', $output, $matches)) {
            preg_match('/(.*<body.*?>)(.*)/s', $output, $matches);
        }
        $output = $matches[1]."\n". $skiplink .$matches[2];
    }

    $output = force_strict_header($output);

    if (!empty($CFG->messaging)) {
        $output .= message_popup_window();
    }

    // Add in any extra JavaScript libraries that occurred during the header
    $output .= require_js('', 2);
    $output .= print_js_call('moodle_initialise_body', array(), true);

    $PAGE->set_state(moodle_page::STATE_IN_BODY);

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * Used to include JavaScript libraries.
 *
 * When the $lib parameter is given, the function will ensure that the
 * named library or libraries is loaded onto the page - either in the
 * HTML <head>, just after the header, or at an arbitrary later point in
 * the page, depending on where this function is called.
 *
 * Libraries will not be included more than once, so this works like
 * require_once in PHP.
 *
 * There are two special-case calls to this function from print_header which are
 * internal to weblib and use the second $extracthtml parameter:
 * $extracthtml = 1: this is used before printing the header.
 *      It returns the script tag code that should go inside the <head>.
 * $extracthtml = 2: this is used after printing the header and handles any
 *      require_js calls that occurred within the header itself.
 *
 * @param mixed $lib The library or libraries to load (a string or array of strings)
 *      There are three way to specify the library:
 *      1. a shorname like 'yui_yahoo'. The list of recognised values is in lib/ajax/ajaxlib.php
 *      2. the path to the library relative to wwwroot, for example 'lib/javascript-static.js'
 *      3. (legacy) a full URL like $CFG->wwwroot . '/lib/javascript-static.js'.
 * @param int $extracthtml Private. For internal weblib use only.
 * @return mixed No return value (except when doing the internal $extracthtml
 *      calls, when it returns html code).
 */
function require_js($lib, $extracthtml = 0) {
    global $CFG;
    static $loadlibs = array();

    static $state = REQUIREJS_BEFOREHEADER;
    static $latecode = '';

    if (!empty($lib)) {
        // Add the lib to the list of libs to be loaded, if it isn't already
        // in the list.
        if (is_array($lib)) {
            foreach($lib as $singlelib) {
                require_js($singlelib);
            }
        } else {
            $libpath = ajax_get_lib($lib);
            if (array_search($libpath, $loadlibs) === false) {
                $loadlibs[] = $libpath;

                // For state other than 0 we need to take action as well as just
                // adding it to loadlibs
                if($state != REQUIREJS_BEFOREHEADER) {
                    // Get the script statement for this library
                    $scriptstatement=get_require_js_code(array($libpath));

                    if($state == REQUIREJS_AFTERHEADER) {
                        // After the header, print it immediately
                        print $scriptstatement;
                    } else {
                        // Haven't finished the header yet. Add it after the
                        // header
                        $latecode .= $scriptstatement;
                    }
                }
            }
        }
    } else if($extracthtml==1) {
        if($state !== REQUIREJS_BEFOREHEADER) {
            debugging('Incorrect state in require_js (expected BEFOREHEADER): be careful not to call with empty $lib (except in print_header)');
        } else {
            $state = REQUIREJS_INHEADER;
        }

        return get_require_js_code($loadlibs);
    } else if($extracthtml==2) {
        if($state !== REQUIREJS_INHEADER) {
            debugging('Incorrect state in require_js (expected INHEADER): be careful not to call with empty $lib (except in print_header)');
            return '';
        } else {
            $state = REQUIREJS_AFTERHEADER;
            return $latecode;
        }
    } else {
        debugging('Unexpected value for $extracthtml');
    }
}

/**
 * Should not be called directly - use require_js. This function obtains the code
 * (script tags) needed to include JavaScript libraries.
 * @param array $loadlibs Array of library files to include
 * @return string HTML code to include them
 */
function get_require_js_code($loadlibs) {
    global $CFG;
    // Return the html needed to load the JavaScript files defined in
    // our list of libs to be loaded.
    $output = '';
    foreach ($loadlibs as $loadlib) {
        $output .= '<script type="text/javascript" ';
        $output .= " src=\"$loadlib\"></script>\n";
        if ($loadlib == $CFG->wwwroot.'/lib/yui/logger/logger-min.js') {
            // Special case, we need the CSS too.
            $output .= '<link type="text/css" rel="stylesheet" ';
            $output .= " href=\"{$CFG->wwwroot}/lib/yui/logger/assets/logger.css\" />\n";
        }
    }
    return $output;
}

/**
 * Generate the HTML for calling a javascript funtion. You often need to do this
 * if you have your javascript in an external file, and need to call one function
 * to initialise it.
 *
 * You can pass in an optional list of arguments, which are properly escaped for
 * you using the json_encode function.
 *
 * @param string $function the name of the JavaScript function to call.
 * @param array $args an optional list of arguments to the function call.
 * @param boolean $return if true, return the HTML code, otherwise output it.
 * @return mixed string if $return is true, otherwise nothing.
 */
function print_js_call($function, $args = array(), $return = false) {
    $quotedargs = array();
    foreach ($args as $arg) {
        $quotedargs[] = json_encode($arg);
    }
    $html = '';
    $html .= '<script type="text/javascript">//<![CDATA[' . "\n";
    $html .= $function . '(' . implode(', ', $quotedargs) . ");\n";
    $html .= "//]]></script>\n";
    if ($return) {
        return $html;
    } else {
        echo $html;
    }
}

/**
 * Generate the HTML for calling a javascript funtion after a time delay.
 * In other respects, this function is the same as print_js_call.
 *
 * @param integer $delay the desired delay in seconds.
 * @param string $function the name of the JavaScript function to call.
 * @param array $args an optional list of arguments to the function call.
 * @param boolean $return if true, return the HTML code, otherwise output it.
 * @return mixed string if $return is true, otherwise nothing.
 */
function print_delayed_js_call($delay, $function, $args = array(), $return = false) {
    $quotedargs = array();
    foreach ($args as $arg) {
        $quotedargs[] = json_encode($arg);
    }
    $html = '';
    $html .= '<script type="text/javascript">//<![CDATA[' . "\n";
    $html .= 'setTimeout(function() {' . $function . '(' .
            implode(', ', $quotedargs) . ');}, ' . ($delay * 1000) . ");\n";
    $html .= "//]]></script>\n";
    if ($return) {
        return $html;
    } else {
        echo $html;
    }
}

/**
 * Sometimes you need access to some values in your JavaScript that you can only
 * get from PHP code. You can handle this by generating your JS in PHP, but a
 * better idea is to write static javascrip code that reads some configuration
 * variable, and then just output the configuration variables from PHP using
 * this function.
 *
 * For example, look at the code in question_init_qenginejs_script() in
 * lib/questionlib.php. It writes out a bunch of $settings like
 * 'pixpath' => $CFG->pixpath, with $prefix = 'qengine_config'. This gets output
 * in print_header, then the code in question/qengine.js can access these variables
 * as qengine_config.pixpath, and so on.
 *
 * This method will also work without a prefix, but it is better to avoid that
 * we don't want to add more things than necessary to the global JavaScript scope.
 *
 * This method automatically wrapps the values in quotes, and addslashes_js them.
 *
 * @param array $settings the values you want to write out, as variablename => value.
 * @param string $prefix a namespace prefix to use in the JavaScript.
 * @param boolean $return if true, return the HTML code, otherwise output it.
 * @return mixed string if $return is true, otherwise nothing.
 */
function print_js_config($settings = array(), $prefix='', $return = false) {
    $html = '';
    $html .= '<script type="text/javascript">//<![CDATA[' . "\n";

    // Have to treat the prefix and no prefix cases separately.
    if ($prefix) {
        // Recommended way, only one thing in global scope.
        $html .= "var $prefix = " . json_encode($settings) . "\n";

    } else {
        // Old fashioned way.
        foreach ($settings as $name => $value) {
            $html .= "var $name = '" . addslashes_js($value) . "'\n";
        }
    }

    // Finish off and return/output.
    $html .= "//]]></script>\n";
    if ($return) {
        return $html;
    } else {
        echo $html;
    }
}

/**
 * This function generates the code that defines the standard moodle_cfg object.
 * This object has a number of fields that are values that various pieces of
 * JavaScript code need access too. For example $CFG->wwwroot and $CFG->pixpath.
 *
 * @return string a <script> tag that defines the moodle_cfg object.
 */
function standard_js_config() {
    global $CFG;
    $config = array(
        'wwwroot' => $CFG->httpswwwroot, // Yes, really.
        'pixpath' => $CFG->pixpath,
        'modpixpath' => $CFG->modpixpath,
        'sesskey' => sesskey(),
    );
    if (debugging('', DEBUG_DEVELOPER)) {
        $config['developerdebug'] = true;
    }
    return print_js_config($config, 'moodle_cfg', true);
}

/**
 * Debugging aid: serve page as 'application/xhtml+xml' where possible,
 *     and substitute the XHTML strict document type.
 *     Note, requires the 'xmlns' fix in function print_header above.
 *     See:  http://tracker.moodle.org/browse/MDL-7883
 * TODO:
 */
function force_strict_header($output) {
    global $CFG;
    $strict = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
    $xsl = '/lib/xhtml.xsl';

    if (!headers_sent() && !empty($CFG->xmlstrictheaders)) {   // With xml strict headers, the browser will barf
        $ctype = 'Content-Type: ';
        $prolog= "<?xml version='1.0' encoding='utf-8'?>\n";

        if (isset($_SERVER['HTTP_ACCEPT'])
            && false !== strpos($_SERVER['HTTP_ACCEPT'], 'application/xhtml+xml')) {
            //|| false !== strpos($_SERVER['HTTP_USER_AGENT'], 'Safari') //Safari "Entity 'copy' not defined".
            // Firefox et al.
            $ctype .= 'application/xhtml+xml';
            $prolog .= "<!--\n  DEBUG: $ctype \n-->\n";

        } else if (file_exists($CFG->dirroot.$xsl)
            && preg_match('/MSIE.*Windows NT/', $_SERVER['HTTP_USER_AGENT'])) {
            // XSL hack for IE 5+ on Windows.
            //$www_xsl = preg_replace('/(http:\/\/.+?\/).*/', '', $CFG->wwwroot) .$xsl;
            $www_xsl = $CFG->wwwroot .$xsl;
            $ctype .= 'application/xml';
            $prolog .= "<?xml-stylesheet type='text/xsl' href='$www_xsl'?>\n";
            $prolog .= "<!--\n  DEBUG: $ctype \n-->\n";

        } else {
            //ELSE: Mac/IE, old/non-XML browsers.
            $ctype .= 'text/html';
            $prolog = '';
        }
        @header($ctype.'; charset=utf-8');
        $output = $prolog . $output;

        // Test parser error-handling.
        if (isset($_GET['error'])) {
            $output .= "__ TEST: XML well-formed error < __\n";
        }
    }

    $output = preg_replace('/(<!DOCTYPE.+?>)/s', $strict, $output);   // Always change the DOCTYPE to Strict 1.0

    return $output;
}



/**
 * This version of print_header is simpler because the course name does not have to be
 * provided explicitly in the strings. It can be used on the site page as in courses
 * Eventually all print_header could be replaced by print_header_simple
 *
 * @param string $title Appears at the top of the window
 * @param string $heading Appears at the top of the page
 * @param string $navigation Premade navigation string (for use as breadcrumbs links)
 * @param string $focus Indicates form element to get cursor focus on load eg  inputform.password
 * @param string $meta Meta tags to be added to the header
 * @param boolean $cache Should this page be cacheable?
 * @param string $button HTML code for a button (usually for module editing)
 * @param string $menu HTML code for a popup menu
 * @param boolean $usexml use XML for this page
 * @param string $bodytags This text will be included verbatim in the <body> tag (useful for onload() etc)
 * @param bool   $return If true, return the visible elements of the header instead of echoing them.
 */
function print_header_simple($title='', $heading='', $navigation='', $focus='', $meta='',
                       $cache=true, $button='&nbsp;', $menu='', $usexml=false, $bodytags='', $return=false) {

    global $COURSE, $CFG;

    // if we have no navigation specified, build it
    if( empty($navigation) ){
       $navigation = build_navigation('');
    }

    // If old style nav prepend course short name otherwise leave $navigation object alone
    if (!is_newnav($navigation)) {
        if ($COURSE->id != SITEID) {
            $shortname = '<a href="'.$CFG->wwwroot.'/course/view.php?id='. $COURSE->id .'">'. $COURSE->shortname .'</a> ->';
            $navigation = $shortname.' '.$navigation;
        }
    }

    $output = print_header($COURSE->shortname .': '. $title, $COURSE->fullname .' '. $heading, $navigation, $focus, $meta,
                           $cache, $button, $menu, $usexml, $bodytags, true);

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}


/**
 * Can provide a course object to make the footer contain a link to
 * to the course home page, otherwise the link will go to the site home
 * @uses $USER
 * @param mixed $course course object, used for course link button or
 *                      'none' means no user link, only docs link
 *                      'empty' means nothing printed in footer
 *                      'home' special frontpage footer
 * @param object $usercourse course used in user link
 * @param boolean $return output as string
 * @return mixed string or void
 */
function print_footer($course=NULL, $usercourse=NULL, $return=false) {
    global $USER, $CFG, $THEME, $COURSE, $SITE, $PAGE;

    if (defined('ADMIN_EXT_HEADER_PRINTED') and !defined('ADMIN_EXT_FOOTER_PRINTED')) {
        admin_externalpage_print_footer();
        return;
    }

    $PAGE->set_state(moodle_page::STATE_PRINTING_FOOTER);

/// Course links or special footer
    if ($course) {
        if ($course === 'empty') {
            // special hack - sometimes we do not want even the docs link in footer
            $output = '';
            if (!empty($THEME->open_header_containers)) {
                for ($i=0; $i<$THEME->open_header_containers; $i++) {
                    $output .= print_container_end_all(); // containers opened from header
                }
            } else {
                //1.8 theme compatibility
                $output .= "\n</div>"; // content div
            }
            $output .= "\n</div>\n</body>\n</html>"; // close page div started in header
            if ($return) {
                return $output;
            } else {
                echo $output;
                return;
            }

        } else if ($course === 'none') {          // Don't print any links etc
            $homelink = '';
            $loggedinas = '';
            $home  = false;

        } else if ($course === 'home') {   // special case for site home page - please do not remove
            $course = $SITE;
            $homelink  = '<div class="sitelink">'.
               '<a title="Moodle '. $CFG->release .'" href="http://moodle.org/">'.
               '<img style="width:100px;height:30px" src="'.$CFG->wwwroot.'/pix/moodlelogo.gif" alt="moodlelogo" /></a></div>';
            $home  = true;

        } else if ($course === 'upgrade') {
            $home = false;
            $loggedinas = '';
            $homelink  = '<div class="sitelink">'.
               '<a title="Moodle '. $CFG->target_release .'" href="http://docs.moodle.org/en/Administrator_documentation" onclick="this.target=\'_blank\'">'.
               '<img style="width:100px;height:30px" src="'.$CFG->wwwroot.'/pix/moodlelogo.gif" alt="moodlelogo" /></a></div>';

        } else {
            $homelink = '<div class="homelink"><a '.$CFG->frametarget.' href="'.$CFG->wwwroot.
                        '/course/view.php?id='.$course->id.'">'.format_string($course->shortname).'</a></div>';
            $home  = false;
        }

    } else {
        $course = $SITE;  // Set course as site course by default
        $homelink = '<div class="homelink"><a '.$CFG->frametarget.' href="'.$CFG->wwwroot.'/">'.get_string('home').'</a></div>';
        $home  = false;
    }

/// Set up some other navigation links (passed from print_header by ugly hack)
    $menu        = isset($THEME->menu) ? str_replace('navmenu', 'navmenufooter', $THEME->menu) : '';
    $title       = isset($THEME->title) ? $THEME->title : '';
    $button      = isset($THEME->button) ? $THEME->button : '';
    $heading     = isset($THEME->heading) ? $THEME->heading : '';
    $navigation  = isset($THEME->navigation) ? $THEME->navigation : '';
    $navmenulist = isset($THEME->navmenulist) ? $THEME->navmenulist : '';


/// Set the user link if necessary
    if (!$usercourse and is_object($course)) {
        $usercourse = $course;
    }

    if (!isset($loggedinas)) {
        $loggedinas = user_login_string($usercourse, $USER);
    }

    if ($loggedinas == $menu) {
        $menu = '';
    }

/// there should be exactly the same number of open containers as after the header
    if ($THEME->open_header_containers != open_containers()) {
        debugging('Unexpected number of open containers: '.open_containers().', expecting '.$THEME->open_header_containers, DEBUG_DEVELOPER);
    }

/// Provide some performance info if required
    $performanceinfo = '';
    if (defined('MDL_PERF') || (!empty($CFG->perfdebug) and $CFG->perfdebug > 7)) {
        $perf = get_performance_info();
        if (defined('MDL_PERFTOLOG') && !function_exists('register_shutdown_function')) {
            error_log("PERF: " . $perf['txt']);
        }
        if (defined('MDL_PERFTOFOOT') || debugging() || $CFG->perfdebug > 7) {
            $performanceinfo = $perf['html'];
        }
    }

/// Include the actual footer file

    ob_start();
    include($CFG->footer);
    $output = ob_get_contents();
    ob_end_clean();

    $PAGE->set_state(moodle_page::STATE_DONE);

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * Returns the name of the current theme
 *
 * @uses $CFG
 * @uses $USER
 * @uses $SESSION
 * @uses $COURSE
 * @uses $SCRIPT
 * @return string
 */
function current_theme() {
    global $CFG, $USER, $SESSION, $COURSE, $SCRIPT;

    if (empty($CFG->themeorder)) {
        $themeorder = array('page', 'course', 'category', 'session', 'user', 'site');
    } else {
        $themeorder = $CFG->themeorder;
    }

    if (isloggedin() and isset($CFG->mnet_localhost_id) and $USER->mnethostid != $CFG->mnet_localhost_id) {
        require_once($CFG->dirroot.'/mnet/peer.php');
        $mnet_peer = new mnet_peer();
        $mnet_peer->set_id($USER->mnethostid);
    }

    $theme = '';
    foreach ($themeorder as $themetype) {

        if (!empty($theme)) continue;

        switch ($themetype) {
            case 'page': // Page theme is for special page-only themes set by code
                if (!empty($CFG->pagetheme)) {
                    $theme = $CFG->pagetheme;
                }
                break;
            case 'course':
                if (!empty($CFG->allowcoursethemes) and !empty($COURSE->theme)) {
                    $theme = $COURSE->theme;
                }
                break;
            case 'category':
                if (!empty($CFG->allowcategorythemes)) {
                /// Nasty hack to check if we're in a category page
                    if ($SCRIPT == '/course/category.php') {
                        global $id;
                        if (!empty($id)) {
                            $theme = current_category_theme($id);
                        }
                /// Otherwise check if we're in a course that has a category theme set
                    } else if (!empty($COURSE->category)) {
                        $theme = current_category_theme($COURSE->category);
                    }
                }
                break;
            case 'session':
                if (!empty($SESSION->theme)) {
                    $theme = $SESSION->theme;
                }
                break;
            case 'user':
                if (!empty($CFG->allowuserthemes) and !empty($USER->theme)) {
                    if (isloggedin() and $USER->mnethostid != $CFG->mnet_localhost_id && $mnet_peer->force_theme == 1 && $mnet_peer->theme != '') {
                        $theme = $mnet_peer->theme;
                    } else {
                        $theme = $USER->theme;
                    }
                }
                break;
            case 'site':
                if (isloggedin() and isset($CFG->mnet_localhost_id) and $USER->mnethostid != $CFG->mnet_localhost_id && $mnet_peer->force_theme == 1 && $mnet_peer->theme != '') {
                    $theme = $mnet_peer->theme;
                } else {
                    $theme = $CFG->theme;
                }
                break;
            default:
                /// do nothing
        }
    }

/// A final check in case 'site' was not included in $CFG->themeorder
    if (empty($theme)) {
        $theme = $CFG->theme;
    }

    return $theme;
}

/**
 * Retrieves the category theme if one exists, otherwise checks the parent categories.
 * Recursive function.
 *
 * @uses $COURSE
 * @param   integer   $categoryid   id of the category to check
 * @return  string    theme name
 */
function current_category_theme($categoryid=0) {
    global $COURSE, $DB;

/// Use the COURSE global if the categoryid not set
    if (empty($categoryid)) {
        if (!empty($COURSE->category)) {
            $categoryid = $COURSE->category;
        } else {
            return false;
        }
    }

/// Retrieve the current category
    if ($category = $DB->get_record('course_categories', array('id'=>$categoryid))) {

    /// Return the category theme if it exists
        if (!empty($category->theme)) {
            return $category->theme;

    /// Otherwise try the parent category if one exists
        } else if (!empty($category->parent)) {
            return current_category_theme($category->parent);
        }

/// Return false if we can't find the category record
    } else {
        return false;
    }
}

/**
 * This function is called by stylesheets to set up the header
 * approriately as well as the current path
 *
 * @uses $CFG
 * @param int $lastmodified ?
 * @param int $lifetime ?
 * @param string $thename ?
 */
function style_sheet_setup($lastmodified=0, $lifetime=300, $themename='', $forceconfig='', $lang='') {

    global $CFG, $THEME;

    // Fix for IE6 caching - we don't want the filemtime('styles.php'), instead use now.
    $lastmodified = time();

    header('Last-Modified: ' . gmdate("D, d M Y H:i:s", $lastmodified) . ' GMT');
    header('Expires: ' . gmdate("D, d M Y H:i:s", time() + $lifetime) . ' GMT');
    header('Cache-Control: max-age='. $lifetime);
    header('Pragma: ');
    header('Content-type: text/css'); // Correct MIME type

    $DEFAULT_SHEET_LIST = array('styles_layout', 'styles_fonts', 'styles_color');

    if (empty($themename)) {
        $themename = current_theme(); // So we have something.  Normally not needed.
    } else {
        $themename = clean_param($themename, PARAM_SAFEDIR);
    }

    theme_setup($themename);

    if (!empty($forceconfig)) { // Page wants to use the config from this theme instead
        unset($THEME);
        include($CFG->themedir.'/'.$forceconfig.'/'.'config.php');
    }

/// If this is the standard theme calling us, then find out what sheets we need
    if ($themename == 'standard') {
        if (!isset($THEME->standardsheets) or $THEME->standardsheets === true) { // Use all the sheets we have
            $THEME->sheets = $DEFAULT_SHEET_LIST;
        } else if (empty($THEME->standardsheets)) { // We can stop right now!
            echo "/***** Nothing required from this stylesheet by main theme *****/\n\n";
            exit;
        } else { // Use the provided subset only
            $THEME->sheets = $THEME->standardsheets;
        }

/// If we are a parent theme, then check for parent definitions
    } else if (!empty($THEME->parent) && $themename == $THEME->parent) {
        if (!isset($THEME->parentsheets) or $THEME->parentsheets === true) {     // Use all the sheets we have
            $THEME->sheets = $DEFAULT_SHEET_LIST;
        } else if (empty($THEME->parentsheets)) {                                // We can stop right now!
            echo "/***** Nothing required from this stylesheet by main theme *****/\n\n";
            exit;
        } else {                                                                 // Use the provided subset only
            $THEME->sheets = $THEME->parentsheets;
        }
    }

/// Work out the last modified date for this theme
    foreach ($THEME->sheets as $sheet) {
        if (file_exists($CFG->themedir.'/'.$themename.'/'.$sheet.'.css')) {
            $sheetmodified = filemtime($CFG->themedir.'/'.$themename.'/'.$sheet.'.css');
            if ($sheetmodified > $lastmodified) {
                $lastmodified = $sheetmodified;
            }
        }
    }

/// Get a list of all the files we want to include
    $files = array();

    foreach ($THEME->sheets as $sheet) {
        $files[] = array($CFG->themedir, $themename.'/'.$sheet.'.css');
    }

    if ($themename == 'standard') {          // Add any standard styles included in any modules
        if (!empty($THEME->modsheets)) {     // Search for styles.php within activity modules
            if ($mods = get_list_of_plugins('mod')) {
                foreach ($mods as $mod) {
                    if (file_exists($CFG->dirroot.'/mod/'.$mod.'/styles.php')) {
                        $files[] = array($CFG->dirroot, '/mod/'.$mod.'/styles.php');
                    }
                }
            }
        }

        if (!empty($THEME->blocksheets)) {     // Search for styles.php within block modules
            if ($mods = get_list_of_plugins('blocks')) {
                foreach ($mods as $mod) {
                    if (file_exists($CFG->dirroot.'/blocks/'.$mod.'/styles.php')) {
                        $files[] = array($CFG->dirroot, '/blocks/'.$mod.'/styles.php');
                    }
                }
            }
        }

        if (!isset($THEME->courseformatsheets) || $THEME->courseformatsheets) { // Search for styles.php in course formats
            if ($mods = get_list_of_plugins('format','',$CFG->dirroot.'/course')) {
                foreach ($mods as $mod) {
                    if (file_exists($CFG->dirroot.'/course/format/'.$mod.'/styles.php')) {
                        $files[] = array($CFG->dirroot, '/course/format/'.$mod.'/styles.php');
                    }
                }
            }
        }

        if (!isset($THEME->gradereportsheets) || $THEME->gradereportsheets) { // Search for styles.php in grade reports
            if ($reports = get_list_of_plugins('grade/report')) {
                foreach ($reports as $report) {
                    if (file_exists($CFG->dirroot.'/grade/report/'.$report.'/styles.php')) {
                        $files[] = array($CFG->dirroot, '/grade/report/'.$report.'/styles.php');
                    }
                }
            }
        }

        if (!empty($THEME->langsheets)) {     // Search for styles.php within the current language
            if (file_exists($CFG->dirroot.'/lang/'.$lang.'/styles.php')) {
                $files[] = array($CFG->dirroot, '/lang/'.$lang.'/styles.php');
            }
        }
    }

    if ($files) {
    /// Produce a list of all the files first
        echo '/**************************************'."\n";
        echo ' * THEME NAME: '.$themename."\n *\n";
        echo ' * Files included in this sheet:'."\n *\n";
        foreach ($files as $file) {
            echo ' *   '.$file[1]."\n";
        }
        echo ' **************************************/'."\n\n";


        /// check if csscobstants is set
        if (!empty($THEME->cssconstants)) {
            require_once("$CFG->libdir/cssconstants.php");
            /// Actually collect all the files in order.
            $css = '';
            foreach ($files as $file) {
                $css .= '/***** '.$file[1].' start *****/'."\n\n";
                $css .= file_get_contents($file[0].'/'.$file[1]);
                $ccs .= '/***** '.$file[1].' end *****/'."\n\n";
            }
            /// replace css_constants with their values
            echo replace_cssconstants($css);
        } else {
        /// Actually output all the files in order.
            if (empty($CFG->CSSEdit) && empty($THEME->CSSEdit)) {
                foreach ($files as $file) {
                    echo '/***** '.$file[1].' start *****/'."\n\n";
                    @include_once($file[0].'/'.$file[1]);
                    echo '/***** '.$file[1].' end *****/'."\n\n";
                }
            } else {
                foreach ($files as $file) {
                    echo '/* @group '.$file[1].' */'."\n\n";
                    if (strstr($file[1], '.css') !== FALSE) {
                        echo '@import url("'.$CFG->themewww.'/'.$file[1].'");'."\n\n";
                    } else {
                        @include_once($file[0].'/'.$file[1]);
                    }
                    echo '/* @end */'."\n\n";
                }
            }
        }
    }

    return $CFG->themewww.'/'.$themename;   // Only to help old themes (1.4 and earlier)
}


function theme_setup($theme = '', $params=NULL) {
/// Sets up global variables related to themes

    global $CFG, $THEME, $SESSION, $USER, $HTTPSPAGEREQUIRED, $PAGE;

/// Do not mess with THEME if header already printed - this would break all the extra stuff in global $THEME from print_header()!!
    if ($PAGE->headerprinted) {
        return;
    }

    if (empty($theme)) {
        $theme = current_theme();
    }

/// If the theme doesn't exist for some reason then revert to standardwhite
    if (!file_exists($CFG->themedir .'/'. $theme .'/config.php')) {
        $CFG->theme = $theme = 'standardwhite';
    }

/// Load up the theme config
    $THEME = NULL;   // Just to be sure
    include($CFG->themedir .'/'. $theme .'/config.php');  // Main config for current theme

/// Put together the parameters
    if (!$params) {
        $params = array();
    }

    if ($theme != $CFG->theme) {
        $params[] = 'forceconfig='.$theme;
    }

/// Force language too if required
    if (!empty($THEME->langsheets)) {
        $params[] = 'lang='.current_language();
    }

/// Convert params to string
    if ($params) {
        $paramstring = '?'.implode('&', $params);
    } else {
        $paramstring = '';
    }

/// Set up image paths
    if(isset($CFG->smartpix) && $CFG->smartpix==1) {
        if($CFG->slasharguments) {        // Use this method if possible for better caching
            $extra='';
        } else {
            $extra='?file=';
        }

        $CFG->pixpath = $CFG->wwwroot. '/pix/smartpix.php'.$extra.'/'.$theme;
        $CFG->modpixpath = $CFG->wwwroot .'/pix/smartpix.php'.$extra.'/'.$theme.'/mod';
    } else if (empty($THEME->custompix)) {    // Could be set in the above file
        $CFG->pixpath = $CFG->wwwroot .'/pix';
        $CFG->modpixpath = $CFG->wwwroot .'/mod';
    } else {
        $CFG->pixpath = $CFG->themewww .'/'. $theme .'/pix';
        $CFG->modpixpath = $CFG->themewww .'/'. $theme .'/pix/mod';
    }

/// Header and footer paths
    $CFG->header = $CFG->themedir .'/'. $theme .'/header.html';
    $CFG->footer = $CFG->themedir .'/'. $theme .'/footer.html';

/// Define stylesheet loading order
    $CFG->stylesheets = array();
    if ($theme != 'standard') {    /// The standard sheet is always loaded first
        $CFG->stylesheets[] = $CFG->themewww.'/standard/styles.php'.$paramstring;
    }
    if (!empty($THEME->parent)) {  /// Parent stylesheets are loaded next
        $CFG->stylesheets[] = $CFG->themewww.'/'.$THEME->parent.'/styles.php'.$paramstring;
    }
    $CFG->stylesheets[] = $CFG->themewww.'/'.$theme.'/styles.php'.$paramstring;

/// We have to change some URLs in styles if we are in a $HTTPSPAGEREQUIRED page
    if (!empty($HTTPSPAGEREQUIRED)) {
        $CFG->themewww = str_replace('http:', 'https:', $CFG->themewww);
        $CFG->pixpath = str_replace('http:', 'https:', $CFG->pixpath);
        $CFG->modpixpath = str_replace('http:', 'https:', $CFG->modpixpath);
        foreach ($CFG->stylesheets as $key => $stylesheet) {
            $CFG->stylesheets[$key] = str_replace('http:', 'https:', $stylesheet);
        }
    }

// RTL support - only for RTL languages, add RTL CSS
    if (get_string('thisdirection') == 'rtl') {
        $CFG->stylesheets[] = $CFG->themewww.'/standard/rtl.css'.$paramstring;
        $CFG->stylesheets[] = $CFG->themewww.'/'.$theme.'/rtl.css'.$paramstring;
    }

    /// Set up the block regions.
    if (!empty($THEME->blockregions)) {
        $PAGE->blocks->add_regions($THEME->blockregions);
    } else {
        // Support legacy themes by supplying a sensible default.
        $PAGE->blocks->add_regions(array('side-pre', 'side-post'));
    }
    if (!empty($THEME->defaultblockregion)) {
        $PAGE->blocks->set_default_region($THEME->defaultblockregion);
    } else {
        // Support legacy themes by supplying a sensible default.
        $PAGE->blocks->set_default_region('side-post');
    }
}


/**
 * Returns text to be displayed to the user which reflects their login status
 *
 * @uses $CFG
 * @uses $USER
 * @param course $course {@link $COURSE} object containing course information
 * @param user $user {@link $USER} object containing user information
 * @return string
 */
function user_login_string($course=NULL, $user=NULL) {
    global $USER, $CFG, $SITE, $DB;

    if (empty($user) and !empty($USER->id)) {
        $user = $USER;
    }

    if (empty($course)) {
        $course = $SITE;
    }

    if (session_is_loggedinas()) {
        $realuser = session_get_realuser();
        $fullname = fullname($realuser, true);
        $realuserinfo = " [<a $CFG->frametarget
        href=\"$CFG->wwwroot/course/loginas.php?id=$course->id&amp;return=1&amp;sesskey=".sesskey()."\">$fullname</a>] ";
    } else {
        $realuserinfo = '';
    }

    $loginurl = get_login_url();

    if (empty($course->id)) {
        // $course->id is not defined during installation
        return '';
    } else if (!empty($user->id)) {
        $context = get_context_instance(CONTEXT_COURSE, $course->id);

        $fullname = fullname($user, true);
        $username = "<a $CFG->frametarget href=\"$CFG->wwwroot/user/view.php?id=$user->id&amp;course=$course->id\">$fullname</a>";
        if (is_mnet_remote_user($user) and $idprovider = $DB->get_record('mnet_host', array('id'=>$user->mnethostid))) {
            $username .= " from <a $CFG->frametarget href=\"{$idprovider->wwwroot}\">{$idprovider->name}</a>";
        }
        if (isset($user->username) && $user->username == 'guest') {
            $loggedinas = $realuserinfo.get_string('loggedinasguest').
                      " (<a $CFG->frametarget href=\"$loginurl\">".get_string('login').'</a>)';
        } else if (!empty($user->access['rsw'][$context->path])) {
            $rolename = '';
            if ($role = $DB->get_record('role', array('id'=>$user->access['rsw'][$context->path]))) {
                $rolename = ': '.format_string($role->name);
            }
            $loggedinas = get_string('loggedinas', 'moodle', $username).$rolename.
                      " (<a $CFG->frametarget
                      href=\"$CFG->wwwroot/course/view.php?id=$course->id&amp;switchrole=0&amp;sesskey=".sesskey()."\">".get_string('switchrolereturn').'</a>)';
        } else {
            $loggedinas = $realuserinfo.get_string('loggedinas', 'moodle', $username).' '.
                      " (<a $CFG->frametarget href=\"$CFG->wwwroot/login/logout.php?sesskey=".sesskey()."\">".get_string('logout').'</a>)';
        }
    } else {
        $loggedinas = get_string('loggedinnot', 'moodle').
                      " (<a $CFG->frametarget href=\"$loginurl\">".get_string('login').'</a>)';
    }
    return '<div class="logininfo">'.$loggedinas.'</div>';
}

/**
 * Tests whether $THEME->rarrow, $THEME->larrow have been set (theme/-/config.php).
 * If not it applies sensible defaults.
 *
 * Accessibility: right and left arrow Unicode characters for breadcrumb, calendar,
 * search forum block, etc. Important: these are 'silent' in a screen-reader
 * (unlike &gt; &raquo;), and must be accompanied by text.
 * @uses $THEME
 */
function check_theme_arrows() {
    global $THEME;

    if (!isset($THEME->rarrow) and !isset($THEME->larrow)) {
        // Default, looks good in Win XP/IE 6, Win/Firefox 1.5, Win/Netscape 8...
        // Also OK in Win 9x/2K/IE 5.x
        $THEME->rarrow = '&#x25BA;';
        $THEME->larrow = '&#x25C4;';
        if (empty($_SERVER['HTTP_USER_AGENT'])) {
            $uagent = '';
        } else {
            $uagent = $_SERVER['HTTP_USER_AGENT'];
        }
        if (false !== strpos($uagent, 'Opera')
            || false !== strpos($uagent, 'Mac')) {
            // Looks good in Win XP/Mac/Opera 8/9, Mac/Firefox 2, Camino, Safari.
            // Not broken in Mac/IE 5, Mac/Netscape 7 (?).
            $THEME->rarrow = '&#x25B6;';
            $THEME->larrow = '&#x25C0;';
        }
        elseif (false !== strpos($uagent, 'Konqueror')) {
            $THEME->rarrow = '&rarr;';
            $THEME->larrow = '&larr;';
        }
        elseif (isset($_SERVER['HTTP_ACCEPT_CHARSET'])
            && false === stripos($_SERVER['HTTP_ACCEPT_CHARSET'], 'utf-8')) {
            // (Win/IE 5 doesn't set ACCEPT_CHARSET, but handles Unicode.)
            // To be safe, non-Unicode browsers!
            $THEME->rarrow = '&gt;';
            $THEME->larrow = '&lt;';
        }

    /// RTL support - in RTL languages, swap r and l arrows
        if (right_to_left()) {
            $t = $THEME->rarrow;
            $THEME->rarrow = $THEME->larrow;
            $THEME->larrow = $t;
        }
    }
}


/**
 * Return the right arrow with text ('next'), and optionally embedded in a link.
 * See function above, check_theme_arrows.
 * @param string $text HTML/plain text label (set to blank only for breadcrumb separator cases).
 * @param string $url An optional link to use in a surrounding HTML anchor.
 * @param bool $accesshide True if text should be hidden (for screen readers only).
 * @param string $addclass Additional class names for the link, or the arrow character.
 * @return string HTML string.
 */
function link_arrow_right($text, $url='', $accesshide=false, $addclass='') {
    global $THEME;
    check_theme_arrows();
    $arrowclass = 'arrow ';
    if (! $url) {
        $arrowclass .= $addclass;
    }
    $arrow = '<span class="'.$arrowclass.'">'.$THEME->rarrow.'</span>';
    $htmltext = '';
    if ($text) {
        $htmltext = '<span class="arrow_text">'.$text.'</span>&nbsp;';
        if ($accesshide) {
            $htmltext = get_accesshide($htmltext);
        }
    }
    if ($url) {
        $class = 'arrow_link';
        if ($addclass) {
            $class .= ' '.$addclass;
        }
        return '<a class="'.$class.'" href="'.$url.'" title="'.preg_replace('/<.*?>/','',$text).'">'.$htmltext.$arrow.'</a>';
    }
    return $htmltext.$arrow;
}

/**
 * Return the left arrow with text ('previous'), and optionally embedded in a link.
 * See function above, check_theme_arrows.
 * @param string $text HTML/plain text label (set to blank only for breadcrumb separator cases).
 * @param string $url An optional link to use in a surrounding HTML anchor.
 * @param bool $accesshide True if text should be hidden (for screen readers only).
 * @param string $addclass Additional class names for the link, or the arrow character.
 * @return string HTML string.
 */
function link_arrow_left($text, $url='', $accesshide=false, $addclass='') {
    global $THEME;
    check_theme_arrows();
    $arrowclass = 'arrow ';
    if (! $url) {
        $arrowclass .= $addclass;
    }
    $arrow = '<span class="'.$arrowclass.'">'.$THEME->larrow.'</span>';
    $htmltext = '';
    if ($text) {
        $htmltext = '&nbsp;<span class="arrow_text">'.$text.'</span>';
        if ($accesshide) {
            $htmltext = get_accesshide($htmltext);
        }
    }
    if ($url) {
        $class = 'arrow_link';
        if ($addclass) {
            $class .= ' '.$addclass;
        }
        return '<a class="'.$class.'" href="'.$url.'" title="'.preg_replace('/<.*?>/','',$text).'">'.$arrow.$htmltext.'</a>';
    }
    return $arrow.$htmltext;
}

/**
 * Return a HTML element with the class "accesshide", for accessibility.
 *   Please use cautiously - where possible, text should be visible!
 * @param string $text Plain text.
 * @param string $elem Lowercase element name, default "span".
 * @param string $class Additional classes for the element.
 * @param string $attrs Additional attributes string in the form, "name='value' name2='value2'"
 * @return string HTML string.
 */
function get_accesshide($text, $elem='span', $class='', $attrs='') {
    return "<$elem class=\"accesshide $class\" $attrs>$text</$elem>";
}

/**
 * Return the breadcrumb trail navigation separator.
 * @return string HTML string.
 */
function get_separator() {
    //Accessibility: the 'hidden' slash is preferred for screen readers.
    return ' '.link_arrow_right($text='/', $url='', $accesshide=true, 'sep').' ';
}

/**
 * Prints breadcrumb trail of links, called in theme/-/header.html
 *
 * @uses $CFG
 * @param mixed $navigation The breadcrumb navigation string to be printed
 * @param string $separator OBSOLETE, mostly not used any more. See build_navigation instead.
 * @param boolean $return False to echo the breadcrumb string (default), true to return it.
 * @return string or null, depending on $return.
 */
function print_navigation ($navigation, $separator=0, $return=false) {
    global $CFG, $THEME, $SITE;
    $output = '';

    if (0 === $separator) {
        $separator = get_separator();
    }
    else {
        $separator = '<span class="sep">'. $separator .'</span>';
    }

    if ($navigation) {

        if (is_newnav($navigation)) {
            if ($return) {
                return($navigation['navlinks']);
            } else {
                echo $navigation['navlinks'];
                return;
            }
        } else {
            debugging('Navigation needs to be updated to use build_navigation()', DEBUG_DEVELOPER);
        }

        if (!is_array($navigation)) {
            $ar = explode('->', $navigation);
            $navigation = array();

            foreach ($ar as $a) {
                if (strpos($a, '</a>') === false) {
                    $navigation[] = array('title' => $a, 'url' => '');
                } else {
                    if (preg_match('/<a.*href="([^"]*)">(.*)<\/a>/', $a, $matches)) {
                        $navigation[] = array('title' => $matches[2], 'url' => $matches[1]);
                    }
                }
            }
        }

        if (!$SITE) {
            $site = new object();
            $site->shortname = get_string('home');
        } else {
            $site = $SITE;
        }

        //Accessibility: breadcrumb links now in a list, &raquo; replaced with a 'silent' character.
        $output .= get_accesshide(get_string('youarehere','access'), 'h2')."<ul>\n";

        $output .= '<li class="first">'."\n".'<a '.$CFG->frametarget.' onclick="this.target=\''.$CFG->framename.'\'" href="'
               .$CFG->wwwroot.((!has_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM))
                                 && !empty($USER->id) && !empty($CFG->mymoodleredirect) && !isguest())
                                 ? '/my' : '') .'/">'. format_string($site->shortname) ."</a>\n</li>\n";


        foreach ($navigation as $navitem) {
            $title = trim(strip_tags(format_string($navitem['title'], false)));
            $url   = $navitem['url'];

            if (empty($url)) {
                $output .= '<li>'."$separator $title</li>\n";
            } else {
                $output .= '<li>'."$separator\n<a ".$CFG->frametarget.' onclick="this.target=\''.$CFG->framename.'\'" href="'
                           .$url.'">'."$title</a>\n</li>\n";
            }
        }

        $output .= "</ul>\n";
    }

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * This function will build the navigation string to be used by print_header
 * and others.
 *
 * It automatically generates the site and course level (if appropriate) links.
 *
 * If you pass in a $cm object, the method will also generate the activity (e.g. 'Forums')
 * and activityinstances (e.g. 'General Developer Forum') navigation levels.
 *
 * If you want to add any further navigation links after the ones this function generates,
 * the pass an array of extra link arrays like this:
 * array(
 *     array('name' => $linktext1, 'link' => $url1, 'type' => $linktype1),
 *     array('name' => $linktext2, 'link' => $url2, 'type' => $linktype2)
 * )
 * The normal case is to just add one further link, for example 'Editing forum' after
 * 'General Developer Forum', with no link.
 * To do that, you need to pass
 * array(array('name' => $linktext, 'link' => '', 'type' => 'title'))
 * However, becuase this is a very common case, you can use a shortcut syntax, and just
 * pass the string 'Editing forum', instead of an array as $extranavlinks.
 *
 * At the moment, the link types only have limited significance. Type 'activity' is
 * recognised in order to implement the $CFG->hideactivitytypenavlink feature. Types
 * that are known to appear are 'home', 'course', 'activity', 'activityinstance' and 'title'.
 * This really needs to be documented better. In the mean time, try to be consistent, it will
 * enable people to customise the navigation more in future.
 *
 * When passing a $cm object, the fields used are $cm->modname, $cm->name and $cm->course.
 * If you get the $cm object using the function get_coursemodule_from_instance or
 * get_coursemodule_from_id (as recommended) then this will be done for you automatically.
 * If you don't have $cm->modname or $cm->name, this fuction will attempt to find them using
 * the $cm->module and $cm->instance fields, but this takes extra database queries, so a
 * warning is printed in developer debug mode.
 *
 * @uses $CFG
 * @uses $THEME
 *
 * @param mixed $extranavlinks - Normally an array of arrays, keys: name, link, type. If you
 *      only want one extra item with no link, you can pass a string instead. If you don't want
 *      any extra links, pass an empty string.
 * @param mixed $cm - optionally the $cm object, if you want this function to generate the
 *      activity and activityinstance levels of navigation too.
 *
 * @return $navigation as an object so it can be differentiated from old style
 *      navigation strings.
 */
function build_navigation($extranavlinks, $cm = null) {
    global $CFG, $COURSE, $DB, $SITE;

    if (is_string($extranavlinks)) {
        if ($extranavlinks == '') {
            $extranavlinks = array();
        } else {
            $extranavlinks = array(array('name' => $extranavlinks, 'link' => '', 'type' => 'title'));
        }
    }

    $navlinks = array();

    //Site name
    if (!empty($SITE->shortname)) {
        $navlinks[] = array(
                'name' => format_string($SITE->shortname),
                'link' => "$CFG->wwwroot/",
                'type' => 'home');
    }

    // Course name, if appropriate.
    if (isset($COURSE) && $COURSE->id != SITEID) {
        $navlinks[] = array(
                'name' => format_string($COURSE->shortname),
                'link' => "$CFG->wwwroot/course/view.php?id=$COURSE->id",
                'type' => 'course');
    }

    // Activity type and instance, if appropriate.
    if (is_object($cm)) {
        if (!isset($cm->modname)) {
            debugging('The field $cm->modname should be set if you call build_navigation with '.
                    'a $cm parameter. If you get $cm using get_coursemodule_from_instance or '.
                    'get_coursemodule_from_id, this will be done automatically.', DEBUG_DEVELOPER);
            if (!$cm->modname = $DB->get_field('modules', 'name', array('id'=>$cm->module))) {
                print_error('cannotmoduletype');
            }
        }
        if (!isset($cm->name)) {
            debugging('The field $cm->name should be set if you call build_navigation with '.
                    'a $cm parameter. If you get $cm using get_coursemodule_from_instance or '.
                    'get_coursemodule_from_id, this will be done automatically.', DEBUG_DEVELOPER);
            if (!$cm->name = $DB->get_field($cm->modname, 'name', array('id'=>$cm->instance))) {
                print_error('cannotmodulename');
            }
        }
        $navlinks[] = array(
                'name' => get_string('modulenameplural', $cm->modname),
                'link' => $CFG->wwwroot . '/mod/' . $cm->modname . '/index.php?id=' . $cm->course,
                'type' => 'activity');
        $navlinks[] = array(
                'name' => format_string($cm->name),
                'link' => $CFG->wwwroot . '/mod/' . $cm->modname . '/view.php?id=' . $cm->id,
                'type' => 'activityinstance');
    }

    //Merge in extra navigation links
    $navlinks = array_merge($navlinks, $extranavlinks);

    // Work out whether we should be showing the activity (e.g. Forums) link.
    // Note: build_navigation() is called from many places --
    // install & upgrade for example -- where we cannot count on the
    // roles infrastructure to be defined. Hence the $CFG->rolesactive check.
    if (!isset($CFG->hideactivitytypenavlink)) {
        $CFG->hideactivitytypenavlink = 0;
    }
    if ($CFG->hideactivitytypenavlink == 2) {
        $hideactivitylink = true;
    } else if ($CFG->hideactivitytypenavlink == 1 && $CFG->rolesactive &&
            !empty($COURSE->id) && $COURSE->id != SITEID) {
        if (!isset($COURSE->context)) {
            $COURSE->context = get_context_instance(CONTEXT_COURSE, $COURSE->id);
        }
        $hideactivitylink = !has_capability('moodle/course:manageactivities', $COURSE->context);
    } else {
        $hideactivitylink = false;
    }

    //Construct an unordered list from $navlinks
    //Accessibility: heading hidden from visual browsers by default.
    $navigation = get_accesshide(get_string('youarehere','access'), 'h2')." <ul>\n";
    $lastindex = count($navlinks) - 1;
    $i = -1; // Used to count the times, so we know when we get to the last item.
    $first = true;
    foreach ($navlinks as $navlink) {
        $i++;
        $last = ($i == $lastindex);
        if (!is_array($navlink)) {
            continue;
        }
        if (!empty($navlink['type']) && $navlink['type'] == 'activity' && !$last && $hideactivitylink) {
            continue;
        }
        $navigation .= '<li class="first">';
        if (!$first) {
            $navigation .= get_separator();
        }
        if ((!empty($navlink['link'])) && !$last) {
            $navigation .= "<a onclick=\"this.target='$CFG->framename'\" href=\"{$navlink['link']}\">";
        }
        $navigation .= "{$navlink['name']}";
        if ((!empty($navlink['link'])) && !$last) {
            $navigation .= "</a>";
        }

        $navigation .= "</li>";
        $first = false;
    }
    $navigation .= "</ul>";

    return(array('newnav' => true, 'navlinks' => $navigation));
}


/**
 * Prints a string in a specified size  (retained for backward compatibility)
 *
 * @param string $text The text to be displayed
 * @param int $size The size to set the font for text display.
 */
function print_headline($text, $size=2, $return=false) {
    $output = print_heading($text, '', $size, true);
    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * Prints text in a format for use in headings.
 *
 * @param string $text The text to be displayed
 * @param string $align The alignment of the printed paragraph of text
 * @param int $size The size to set the font for text display.
 */
function print_heading($text, $align='', $size=2, $class='main', $return=false, $id='') {
    global $verbose;
    if ($align) {
        $align = ' style="text-align:'.$align.';"';
    }
    if ($class) {
        $class = ' class="'.$class.'"';
    }
    if ($id) {
        $id = ' id="'.$id.'"';
    }
    if (!CLI_SCRIPT) {
        $output = "<h$size $align $class $id>".$text."</h$size>";
    } else {
        $output = $text;
        if ($size == 1) {
            $output = '=>'.$output;
        } else if ($size == 2) {
            $output = '-->'.$output;
        }
    }

    if ($return) {
        return $output;
    } else {
        if (!CLI_SCRIPT) {
            echo $output;
        } else {
            console_write($output."\n", '', false);
        }
    }
}

/**
 * Centered heading with attached help button (same title text)
 * and optional icon attached
 *
 * @param string $text The text to be displayed
 * @param string $helppage The help page to link to
 * @param string $module The module whose help should be linked to
 * @param string $icon Image to display if needed
 */
function print_heading_with_help($text, $helppage, $module='moodle', $icon='', $return=false) {
    $output = '<div class="heading-with-help">';
    $output .= '<h2 class="main help">'.$icon.$text.'</h2>';
    $output .= helpbutton($helppage, $text, $module, true, false, '', true);
    $output .= '</div>';

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}


function print_heading_block($heading, $class='', $return=false) {
    //Accessibility: 'headingblock' is now H1, see theme/standard/styles_*.css: ??
    $output = '<h2 class="headingblock header '.$class.'">'.$heading.'</h2>';

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}


/**
 * Print a link to continue on to another page.
 *
 * @uses $CFG
 * @param string $link The url to create a link to.
 */
function print_continue($link, $return=false) {

    global $CFG;

    $output = '';

    if ($link == '') {
        if (!empty($_SERVER['HTTP_REFERER'])) {
            $link = $_SERVER['HTTP_REFERER'];
            $link = str_replace('&', '&amp;', $link); // make it valid XHTML
        } else {
            $link = $CFG->wwwroot .'/';
        }
    }

    $options = array();
    $linkparts = parse_url(str_replace('&amp;', '&', $link));
    if (isset($linkparts['query'])) {
        parse_str($linkparts['query'], $options);
    }

    $output .= '<div class="continuebutton">';

    $output .= print_single_button($link, $options, get_string('continue'), 'get', $CFG->framename, true);
    $output .= '</div>'."\n";

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}


/**
 * Print a message in a standard themed box.
 * Replaces print_simple_box (see deprecatedlib.php)
 *
 * @param string $message, the content of the box
 * @param string $classes, space-separated class names.
 * @param string $idbase
 * @param boolean $return, return as string or just print it
 * @return mixed string or void
 */
function print_box($message, $classes='generalbox', $ids='', $return=false) {

    $output  = print_box_start($classes, $ids, true);
    $output .= $message;
    $output .= print_box_end(true);

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * Starts a box using divs
 * Replaces print_simple_box_start (see deprecatedlib.php)
 *
 * @param string $classes, space-separated class names.
 * @param string $idbase
 * @param boolean $return, return as string or just print it
 * @return mixed string or void
 */
function print_box_start($classes='generalbox', $ids='', $return=false) {
    global $THEME;

    if (strpos($classes, 'clearfix') !== false) {
        $clearfix = true;
        $classes = trim(str_replace('clearfix', '', $classes));
    } else {
        $clearfix = false;
    }

    if (!empty($THEME->customcorners)) {
        $classes .= ' ccbox box';
    } else {
        $classes .= ' box';
    }

    return print_container_start($clearfix, $classes, $ids, $return);
}

/**
 * Simple function to end a box (see above)
 * Replaces print_simple_box_end (see deprecatedlib.php)
 *
 * @param boolean $return, return as string or just print it
 */
function print_box_end($return=false) {
    return print_container_end($return);
}

/**
 * Print (or return) a collapisble region, that has a caption that can
 * be clicked to expand or collapse the region. If JavaScript is off, then the region
 * will always be exanded.
 *
 * @param string $contents the contents of the box.
 * @param string $classes class names added to the div that is output.
 * @param string $id id added to the div that is output. Must not be blank.
 * @param string $caption text displayed at the top. Clicking on this will cause the region to expand or contract.
 * @param string $userpref the name of the user preference that stores the user's preferred deafault state.
 *      (May be blank if you do not wish the state to be persisted.
 * @param boolean $default Inital collapsed state to use if the user_preference it not set.
 * @param boolean $return if true, return the HTML as a string, rather than printing it.
 * @return mixed if $return is false, returns nothing, otherwise returns a string of HTML.
 */
function print_collapsible_region($contents, $classes, $id, $caption, $userpref = '', $default = false, $return = false) {
    $output  = print_collapsible_region_start($classes, $id, $caption, $userpref, true);
    $output .= $contents;
    $output .= print_collapsible_region_end(true);

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * Print (or return) the start of a collapisble region, that has a caption that can
 * be clicked to expand or collapse the region. If JavaScript is off, then the region
 * will always be exanded.
 *
 * @param string $classes class names added to the div that is output.
 * @param string $id id added to the div that is output. Must not be blank.
 * @param string $caption text displayed at the top. Clicking on this will cause the region to expand or contract.
 * @param string $userpref the name of the user preference that stores the user's preferred deafault state.
 *      (May be blank if you do not wish the state to be persisted.
 * @param boolean $default Inital collapsed state to use if the user_preference it not set.
 * @param boolean $return if true, return the HTML as a string, rather than printing it.
 * @return mixed if $return is false, returns nothing, otherwise returns a string of HTML.
 */
function print_collapsible_region_start($classes, $id, $caption, $userpref = false, $default = false, $return = false) {
    global $CFG;

    // Include required JavaScript libraries.
    require_js(array('yui_yahoo', 'yui_dom-event', 'yui_event', 'yui_animation'));

    // Work out the initial state.
    if (is_string($userpref)) {
        user_preference_allow_ajax_update($userpref, PARAM_BOOL);
        $collapsed = get_user_preferences($userpref, $default);
    } else {
        $collapsed = $default;
        $userpref = false;
    }

    if ($collapsed) {
        $classes .= ' collapsed';
    }

    $output = '';
    $output .= '<div id="' . $id . '" class="collapsibleregion ' . $classes . '">';
    $output .= '<div id="' . $id . '_sizer">';
    $output .= '<div id="' . $id . '_caption" class="collapsibleregioncaption">';
    $output .= $caption . ' ';
    $output .= '</div><div id="' . $id . '_inner" class="collapsibleregioninner">';
    $output .= print_js_call('new collapsible_region', array($id, $userpref, get_string('clicktohideshow')), true);

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * Close a region started with print_collapsible_region_start.
 *
 * @param boolean $return if true, return the HTML as a string, rather than printing it.
 * @return mixed if $return is false, returns nothing, otherwise returns a string of HTML.
 */
function print_collapsible_region_end($return = false) {
    $output = '</div></div></div>';

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * Print a message in a standard themed container.
 *
 * @param string $message, the content of the container
 * @param boolean $clearfix clear both sides
 * @param string $classes, space-separated class names.
 * @param string $idbase
 * @param boolean $return, return as string or just print it
 * @return string or void
 */
function print_container($message, $clearfix=false, $classes='', $idbase='', $return=false) {

    $output  = print_container_start($clearfix, $classes, $idbase, true);
    $output .= $message;
    $output .= print_container_end(true);

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * Starts a container using divs
 *
 * @param boolean $clearfix clear both sides
 * @param string $classes, space-separated class names.
 * @param string $idbase
 * @param boolean $return, return as string or just print it
 * @return mixed string or void
 */
function print_container_start($clearfix=false, $classes='', $idbase='', $return=false) {
    global $THEME;

    if (!isset($THEME->open_containers)) {
        $THEME->open_containers = array();
    }
    $THEME->open_containers[] = $idbase;


    if (!empty($THEME->customcorners)) {
        $output = _print_custom_corners_start($clearfix, $classes, $idbase);
    } else {
        if ($idbase) {
            $id = ' id="'.$idbase.'"';
        } else {
            $id = '';
        }
        if ($clearfix) {
            $clearfix = ' clearfix';
        } else {
            $clearfix = '';
        }
        if ($classes or $clearfix) {
            $class = ' class="'.$classes.$clearfix.'"';
        } else {
            $class = '';
        }
        $output = '<div'.$id.$class.'>';
    }

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * Simple function to end a container (see above)
 * @param boolean $return, return as string or just print it
 * @return mixed string or void
 */
function print_container_end($return=false) {
    global $THEME;

    if (empty($THEME->open_containers)) {
        debugging('Incorrect request to end container - no more open containers.', DEBUG_DEVELOPER);
        $idbase = '';
    } else {
        $idbase = array_pop($THEME->open_containers);
    }

    if (!empty($THEME->customcorners)) {
        $output = _print_custom_corners_end($idbase);
    } else {
        $output = '</div>';
    }

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * Returns number of currently open containers
 * @return int number of open containers
 */
function open_containers() {
    global $THEME;

    if (!isset($THEME->open_containers)) {
        $THEME->open_containers = array();
    }

    return count($THEME->open_containers);
}

/**
 * Force closing of open containers
 * @param boolean $return, return as string or just print it
 * @param int $keep number of containers to be kept open - usually theme or page containers
 * @return mixed string or void
 */
function print_container_end_all($return=false, $keep=0) {
    $output = '';
    while (open_containers() > $keep) {
        $output .= print_container_end($return);
    }

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * Internal function - do not use directly!
 * Starting part of the surrounding divs for custom corners
 *
 * @param boolean $clearfix, add CLASS "clearfix" to the inner div against collapsing
 * @param string $classes
 * @param mixed $idbase, optionally, define one idbase to be added to all the elements in the corners
 * @return string
 */
function _print_custom_corners_start($clearfix=false, $classes='', $idbase='') {
/// Analise if we want ids for the custom corner elements
    $id = '';
    $idbt = '';
    $idi1 = '';
    $idi2 = '';
    $idi3 = '';

    if ($idbase) {
        $id   = 'id="'.$idbase.'" ';
        $idbt = 'id="'.$idbase.'-bt" ';
        $idi1 = 'id="'.$idbase.'-i1" ';
        $idi2 = 'id="'.$idbase.'-i2" ';
        $idi3 = 'id="'.$idbase.'-i3" ';
    }

/// Calculate current level
    $level = open_containers();

/// Output begins
    $output = '<div '.$id.'class="wrap wraplevel'.$level.' '.$classes.'">'."\n";
    $output .= '<div '.$idbt.'class="bt"><div>&nbsp;</div></div>';
    $output .= "\n";
    $output .= '<div '.$idi1.'class="i1"><div '.$idi2.'class="i2">';
    $output .= (!empty($clearfix)) ? '<div '.$idi3.'class="i3 clearfix">' : '<div '.$idi3.'class="i3">';

    return $output;
}


/**
 * Internal function - do not use directly!
 * Ending part of the surrounding divs for custom corners
 * @param string $idbase
 * @return string
 */
function _print_custom_corners_end($idbase) {
/// Analise if we want ids for the custom corner elements
    $idbb = '';

    if ($idbase) {
        $idbb = 'id="' . $idbase . '-bb" ';
    }

/// Output begins
    $output = '</div></div></div>';
    $output .= "\n";
    $output .= '<div '.$idbb.'class="bb"><div>&nbsp;</div></div>'."\n";
    $output .= '</div>';

    return $output;
}


/**
 * Print a self contained form with a single submit button.
 *
 * @param string $link used as the action attribute on the form, so the URL that will be hit if the button is clicked.
 * @param array $options these become hidden form fields, so these options get passed to the script at $link.
 * @param string $label the caption that appears on the button.
 * @param string $method HTTP method used on the request of the button is clicked. 'get' or 'post'.
 * @param string $notusedanymore no longer used.
 * @param boolean $return if false, output the form directly, otherwise return the HTML as a string.
 * @param string $tooltip a tooltip to add to the button as a title attribute.
 * @param boolean $disabled if true, the button will be disabled.
 * @param string $jsconfirmmessage if not empty then display a confirm dialogue with this string as the question.
 * @return string / nothing depending on the $return paramter.
 */
function print_single_button($link, $options, $label='OK', $method='get', $notusedanymore='',
        $return=false, $tooltip='', $disabled = false, $jsconfirmmessage='', $formid = '') {
    $output = '';
    if ($formid) {
        $formid = ' id="' . s($formid) . '"';
    }
    $link = str_replace('"', '&quot;', $link); //basic XSS protection
    $output .= '<div class="singlebutton">';
    // taking target out, will need to add later target="'.$target.'"
    $output .= '<form action="'. $link .'" method="'. $method .'"' . $formid . '>';
    $output .= '<div>';
    if ($options) {
        foreach ($options as $name => $value) {
            $output .= '<input type="hidden" name="'. $name .'" value="'. s($value) .'" />';
        }
    }
    if ($tooltip) {
        $tooltip = 'title="' . s($tooltip) . '"';
    } else {
        $tooltip = '';
    }
    if ($disabled) {
        $disabled = 'disabled="disabled"';
    } else {
        $disabled = '';
    }
    if ($jsconfirmmessage){
        $jsconfirmmessage = addslashes_js($jsconfirmmessage);
        $jsconfirmmessage = 'onclick="return confirm(\''. $jsconfirmmessage .'\');" ';
    }
    $output .= '<input type="submit" value="'. s($label) ."\" $tooltip $disabled $jsconfirmmessage/></div></form></div>";

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}


/**
 * Print a spacer image with the option of including a line break.
 *
 * @param int $height ?
 * @param int $width ?
 * @param boolean $br ?
 * @todo Finish documenting this function
 */
function print_spacer($height=1, $width=1, $br=true, $return=false) {
    global $CFG;
    $output = '';

    $output .= '<img class="spacer" height="'. $height .'" width="'. $width .'" src="'. $CFG->wwwroot .'/pix/spacer.gif" alt="" />';
    if ($br) {
        $output .= '<br />'."\n";
    }

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * Given the path to a picture file in a course, or a URL,
 * this function includes the picture in the page.
 *
 * @param string $path ?
 * @param int $courseid ?
 * @param int $height ?
 * @param int $width ?
 * @param string $link ?
 * @todo Finish documenting this function
 */
function print_file_picture($path, $courseid=0, $height='', $width='', $link='', $return=false) {
    global $CFG;
    $output = '';

    if ($height) {
        $height = 'height="'. $height .'"';
    }
    if ($width) {
        $width = 'width="'. $width .'"';
    }
    if ($link) {
        $output .= '<a href="'. $link .'">';
    }
    if (substr(strtolower($path), 0, 7) == 'http://') {
        $output .= '<img style="height:'.$height.'px;width:'.$width.'px;" src="'. $path .'" />';

    } else if ($courseid) {
        $output .= '<img style="height:'.$height.'px;width:'.$width.'px;" src="';
        require_once($CFG->libdir.'/filelib.php');
        $output .= get_file_url("$courseid/$path");
        $output .= '" />';
    } else {
        $output .= 'Error: must pass URL or course';
    }
    if ($link) {
        $output .= '</a>';
    }

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * Print the specified user's avatar.
 *
 * @param mixed $user Should be a $user object with at least fields id, picture, imagealt, firstname, lastname
 *      If any of these are missing, or if a userid is passed, the the database is queried. Avoid this
 *      if at all possible, particularly for reports. It is very bad for performance.
 * @param int $courseid The course id. Used when constructing the link to the user's profile.
 * @param boolean $picture The picture to print. By default (or if NULL is passed) $user->picture is used.
 * @param int $size Size in pixels. Special values are (true/1 = 100px) and (false/0 = 35px) for backward compatability
 * @param boolean $return If false print picture to current page, otherwise return the output as string
 * @param boolean $link enclose printed image in a link the user's profile (default true).
 * @param string $target link target attribute. Makes the profile open in a popup window.
 * @param boolean $alttext add non-blank alt-text to the image. (Default true, set to false for purely
 *      decorative images, or where the username will be printed anyway.)
 * @return string or nothing, depending on $return.
 */
function print_user_picture($user, $courseid, $picture=NULL, $size=0, $return=false, $link=true, $target='', $alttext=true) {
    global $CFG, $DB;

    $needrec = false;
    // only touch the DB if we are missing data...
    if (is_object($user)) {
        // Note - both picture and imagealt _can_ be empty
        // what we are trying to see here is if they have been fetched
        // from the DB. We should use isset() _except_ that some installs
        // have those fields as nullable, and isset() will return false
        // on null. The only safe thing is to ask array_key_exists()
        // which works on objects. property_exists() isn't quite
        // what we want here...
        if (! (array_key_exists('picture', $user)
               && ($alttext && array_key_exists('imagealt', $user)
                   || (isset($user->firstname) && isset($user->lastname)))) ) {
            $needrec = true;
            $user = $user->id;
        }
    } else {
        if ($alttext) {
            // we need firstname, lastname, imagealt, can't escape...
            $needrec = true;
        } else {
            $userobj = new StdClass; // fake it to save DB traffic
            $userobj->id = $user;
            $userobj->picture = $picture;
            $user = clone($userobj);
            unset($userobj);
        }
    }
    if ($needrec) {
        $user = $DB->get_record('user', array('id'=>$user), 'id,firstname,lastname,imagealt');
    }

    if ($link) {
        $url = '/user/view.php?id='. $user->id .'&amp;course='. $courseid ;
        if ($target) {
            $target='onclick="return openpopup(\''.$url.'\');"';
        }
        $output = '<a '.$target.' href="'. $CFG->wwwroot . $url .'">';
    } else {
        $output = '';
    }
    if (empty($size)) {
        $file = 'f2';
        $size = 35;
    } else if ($size === true or $size == 1) {
        $file = 'f1';
        $size = 100;
    } else if ($size >= 50) {
        $file = 'f1';
    } else {
        $file = 'f2';
    }
    $class = "userpicture";

    if (is_null($picture)) {
        $picture = $user->picture;
    }

    if ($picture) {  // Print custom user picture
        require_once($CFG->libdir.'/filelib.php');
        $src = get_file_url($user->id.'/'.$file.'.jpg', null, 'user');
    } else {         // Print default user pictures (use theme version if available)
        $class .= " defaultuserpic";
        $src =  "$CFG->pixpath/u/$file.png";
    }
    $imagealt = '';
    if ($alttext) {
        if (!empty($user->imagealt)) {
            $imagealt = $user->imagealt;
        } else {
            $imagealt = get_string('pictureof','',fullname($user));
        }
    }

    $output .= "<img class=\"$class\" src=\"$src\" height=\"$size\" width=\"$size\" alt=\"".s($imagealt).'"  />';
    if ($link) {
        $output .= '</a>';
    }

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * Prints a summary of a user in a nice little box.
 *
 * @uses $CFG
 * @uses $USER
 * @param user $user A {@link $USER} object representing a user
 * @param course $course A {@link $COURSE} object representing a course
 */
function print_user($user, $course, $messageselect=false, $return=false) {

    global $CFG, $USER;

    $output = '';

    static $string;
    static $datestring;
    static $countries;

    $context = get_context_instance(CONTEXT_COURSE, $course->id);
    if (isset($user->context->id)) {
        $usercontext = $user->context;
    } else {
        $usercontext = get_context_instance(CONTEXT_USER, $user->id);
    }

    if (empty($string)) {     // Cache all the strings for the rest of the page

        $string->email       = get_string('email');
        $string->city = get_string('city');
        $string->lastaccess  = get_string('lastaccess');
        $string->activity    = get_string('activity');
        $string->unenrol     = get_string('unenrol');
        $string->loginas     = get_string('loginas');
        $string->fullprofile = get_string('fullprofile');
        $string->role        = get_string('role');
        $string->name        = get_string('name');
        $string->never       = get_string('never');

        $datestring->day     = get_string('day');
        $datestring->days    = get_string('days');
        $datestring->hour    = get_string('hour');
        $datestring->hours   = get_string('hours');
        $datestring->min     = get_string('min');
        $datestring->mins    = get_string('mins');
        $datestring->sec     = get_string('sec');
        $datestring->secs    = get_string('secs');
        $datestring->year    = get_string('year');
        $datestring->years   = get_string('years');

        $countries = get_list_of_countries();
    }

/// Get the hidden field list
    if (has_capability('moodle/course:viewhiddenuserfields', $context)) {
        $hiddenfields = array();
    } else {
        $hiddenfields = array_flip(explode(',', $CFG->hiddenuserfields));
    }

    $output .= '<table class="userinfobox">';
    $output .= '<tr>';
    $output .= '<td class="left side">';
    $output .= print_user_picture($user, $course->id, $user->picture, true, true);
    $output .= '</td>';
    $output .= '<td class="content">';
    $output .= '<div class="username">'.fullname($user, has_capability('moodle/site:viewfullnames', $context)).'</div>';
    $output .= '<div class="info">';
    if (!empty($user->role)) {
        $output .= $string->role .': '. $user->role .'<br />';
    }
    if ($user->maildisplay == 1 or ($user->maildisplay == 2 and ($course->id != SITEID) and !isguest()) or
has_capability('moodle/course:viewhiddenuserfields', $context)) {
        $output .= $string->email .': <a href="mailto:'. $user->email .'">'. $user->email .'</a><br />';
    }
    if (($user->city or $user->country) and (!isset($hiddenfields['city']) or !isset($hiddenfields['country']))) {
        $output .= $string->city .': ';
        if ($user->city && !isset($hiddenfields['city'])) {
            $output .= $user->city;
        }
        if (!empty($countries[$user->country]) && !isset($hiddenfields['country'])) {
            if ($user->city && !isset($hiddenfields['city'])) {
                $output .= ', ';
            }
            $output .= $countries[$user->country];
        }
        $output .= '<br />';
    }

    if (!isset($hiddenfields['lastaccess'])) {
        if ($user->lastaccess) {
            $output .= $string->lastaccess .': '. userdate($user->lastaccess);
            $output .= '&nbsp; ('. format_time(time() - $user->lastaccess, $datestring) .')';
        } else {
            $output .= $string->lastaccess .': '. $string->never;
        }
    }
    $output .= '</div></td><td class="links">';
    //link to blogs
    if ($CFG->bloglevel > 0) {
        $output .= '<a href="'.$CFG->wwwroot.'/blog/index.php?userid='.$user->id.'">'.get_string('blogs','blog').'</a><br />';
    }
    //link to notes
    if (!empty($CFG->enablenotes) and (has_capability('moodle/notes:manage', $context) || has_capability('moodle/notes:view', $context))) {
        $output .= '<a href="'.$CFG->wwwroot.'/notes/index.php?course=' . $course->id. '&amp;user='.$user->id.'">'.get_string('notes','notes').'</a><br />';
    }

    if (has_capability('moodle/site:viewreports', $context) or has_capability('moodle/user:viewuseractivitiesreport', $usercontext)) {
        $output .= '<a href="'. $CFG->wwwroot .'/course/user.php?id='. $course->id .'&amp;user='. $user->id .'">'. $string->activity .'</a><br />';
    }
    if (has_capability('moodle/role:assign', $context) and get_user_roles($context, $user->id, false)) {  // I can unassing and user has some role
        $output .= '<a href="'. $CFG->wwwroot .'/course/unenrol.php?id='. $course->id .'&amp;user='. $user->id .'">'. $string->unenrol .'</a><br />';
    }
    if ($USER->id != $user->id && !session_is_loggedinas() && has_capability('moodle/user:loginas', $context) &&
                                 ! has_capability('moodle/site:doanything', $context, $user->id, false)) {
        $output .= '<a href="'. $CFG->wwwroot .'/course/loginas.php?id='. $course->id .'&amp;user='. $user->id .'&amp;sesskey='. sesskey() .'">'. $string->loginas .'</a><br />';
    }
    $output .= '<a href="'. $CFG->wwwroot .'/user/view.php?id='. $user->id .'&amp;course='. $course->id .'">'. $string->fullprofile .'...</a>';

    if (!empty($messageselect)) {
        $output .= '<br /><input type="checkbox" name="user'.$user->id.'" /> ';
    }

    $output .= '</td></tr></table>';

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * Print a specified group's avatar.
 *
 * @param group $group A single {@link group} object OR array of groups.
 * @param int $courseid The course ID.
 * @param boolean $large Default small picture, or large.
 * @param boolean $return If false print picture, otherwise return the output as string
 * @param boolean $link Enclose image in a link to view specified course?
 * @return string
 * @todo Finish documenting this function
 */
function print_group_picture($group, $courseid, $large=false, $return=false, $link=true) {
    global $CFG;

    if (is_array($group)) {
        $output = '';
        foreach($group as $g) {
            $output .= print_group_picture($g, $courseid, $large, true, $link);
        }
        if ($return) {
            return $output;
        } else {
            echo $output;
            return;
        }
    }

    $context = get_context_instance(CONTEXT_COURSE, $courseid);

    if ($group->hidepicture and !has_capability('moodle/course:managegroups', $context)) {
        return '';
    }

    if ($link or has_capability('moodle/site:accessallgroups', $context)) {
        $output = '<a href="'. $CFG->wwwroot .'/user/index.php?id='. $courseid .'&amp;group='. $group->id .'">';
    } else {
        $output = '';
    }
    if ($large) {
        $file = 'f1';
    } else {
        $file = 'f2';
    }
    if ($group->picture) {  // Print custom group picture
        require_once($CFG->libdir.'/filelib.php');
        $grouppictureurl = get_file_url($group->id.'/'.$file.'.jpg', null, 'usergroup');
        $output .= '<img class="grouppicture" src="'.$grouppictureurl.'"'.
            ' alt="'.s(get_string('group').' '.$group->name).'" title="'.s($group->name).'"/>';
    }
    if ($link or has_capability('moodle/site:accessallgroups', $context)) {
        $output .= '</a>';
    }

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * Print a png image.
 *
 * @param string $url ?
 * @param int $sizex ?
 * @param int $sizey ?
 * @param boolean $return ?
 * @param string $parameters ?
 * @todo Finish documenting this function
 */
function print_png($url, $sizex, $sizey, $return, $parameters='alt=""') {
    global $CFG;
    static $recentIE;

    if (!isset($recentIE)) {
        $recentIE = check_browser_version('MSIE', '5.0');
    }

    if ($recentIE) {  // work around the HORRIBLE bug IE has with alpha transparencies
        $output .= '<img src="'. $CFG->pixpath .'/spacer.gif" width="'. $sizex .'" height="'. $sizey .'"'.
                   ' class="png" style="width: '. $sizex .'px; height: '. $sizey .'px; '.
                   ' filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src='.
                   "'$url', sizingMethod='scale') ".
                   ' '. $parameters .' />';
    } else {
        $output .= '<img src="'. $url .'" style="width: '. $sizex .'px; height: '. $sizey .'px; '. $parameters .' />';
    }

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * Print a nicely formatted table.
 *
 * @param array $table is an object with several properties.
 * <ul>
 *     <li>$table->head - An array of heading names.
 *     <li>$table->align - An array of column alignments
 *     <li>$table->size  - An array of column sizes
 *     <li>$table->wrap - An array of "nowrap"s or nothing
 *     <li>$table->data[] - An array of arrays containing the data.
 *     <li>$table->width  - A percentage of the page
 *     <li>$table->tablealign  - Align the whole table
 *     <li>$table->cellpadding  - Padding on each cell
 *     <li>$table->cellspacing  - Spacing between cells
 *     <li>$table->class - class attribute to put on the table
 *     <li>$table->id - id attribute to put on the table.
 *     <li>$table->rowclass[] - classes to add to particular rows. (space-separated string)
 *     <li>$table->colclass[] - classes to add to every cell in a particular colummn. (space-separated string)
 *     <li>$table->summary - Description of the contents for screen readers.
 *     <li>$table->headspan can be used to make a heading span multiple columns.
 *     <li>$table->rotateheaders - Causes the contents of the heading cells to be rotated 90 degrees.
 * </ul>
 * @param bool $return whether to return an output string or echo now
 * @return boolean or $string
 * @todo Finish documenting this function
 */
function print_table($table, $return=false) {
    $output = '';

    if (isset($table->align)) {
        foreach ($table->align as $key => $aa) {
            if ($aa) {
                $align[$key] = ' text-align:'. fix_align_rtl($aa) .';';  // Fix for RTL languages
            } else {
                $align[$key] = '';
            }
        }
    }
    if (isset($table->size)) {
        foreach ($table->size as $key => $ss) {
            if ($ss) {
                $size[$key] = ' width:'. $ss .';';
            } else {
                $size[$key] = '';
            }
        }
    }
    if (isset($table->wrap)) {
        foreach ($table->wrap as $key => $ww) {
            if ($ww) {
                $wrap[$key] = ' white-space:nowrap;';
            } else {
                $wrap[$key] = '';
            }
        }
    }

    if (empty($table->width)) {
        $table->width = '80%';
    }

    if (empty($table->tablealign)) {
        $table->tablealign = 'center';
    }

    if (!isset($table->cellpadding)) {
        $table->cellpadding = '5';
    }

    if (!isset($table->cellspacing)) {
        $table->cellspacing = '1';
    }

    if (empty($table->class)) {
        $table->class = 'generaltable';
    }
    if (!empty($table->rotateheaders)) {
        $table->class .= ' rotateheaders';
    } else {
        $table->rotateheaders = false; // Makes life easier later.
    }

    $tableid = empty($table->id) ? '' : 'id="'.$table->id.'"';

    $output .= '<table width="'.$table->width.'" ';
    if (!empty($table->summary)) {
        $output .= " summary=\"$table->summary\"";
    }
    $output .= " cellpadding=\"$table->cellpadding\" cellspacing=\"$table->cellspacing\" class=\"$table->class boxalign$table->tablealign\" $tableid>\n";

    $countcols = 0;

    if (!empty($table->head)) {
        $countcols = count($table->head);
        $output .= '<tr>';
        $keys = array_keys($table->head);
        $lastkey = end($keys);
        foreach ($table->head as $key => $heading) {
            $classes = array('header', 'c' . $key);
            if (!isset($size[$key])) {
                $size[$key] = '';
            }
            if (!isset($align[$key])) {
                $align[$key] = '';
            }
            if (isset($table->headspan[$key]) && $table->headspan[$key] > 1) {
                $colspan = ' colspan="' . $table->headspan[$key] . '"';
            } else {
                $colspan = '';
            }
            if ($key == $lastkey) {
                $classes[] = 'lastcol';
            }
            if (isset($table->colclasses[$key])) {
                $classes[] = $table->colclasses[$key];
            }
            if ($table->rotateheaders) {
                $wrapperstart = '<span>';
                $wrapperend = '</span>';
            } else {
                $wrapperstart = '';
                $wrapperend = '';
            }

            $output .= '<th style="'. $align[$key].$size[$key] .
                    ';white-space:nowrap;" class="'.implode(' ', $classes).'" scope="col"' . $colspan . '>'.
                    $wrapperstart . $heading . $wrapperend . '</th>';
        }
        $output .= '</tr>'."\n";
    }

    if (!empty($table->data)) {
        $oddeven = 1;
        $keys=array_keys($table->data);
        $lastrowkey = end($keys);
        foreach ($table->data as $key => $row) {
            $oddeven = $oddeven ? 0 : 1;
            if (!isset($table->rowclass[$key])) {
                $table->rowclass[$key] = '';
            }
            if ($key == $lastrowkey) {
                $table->rowclass[$key] .= ' lastrow';
            }
            $output .= '<tr class="r'.$oddeven.' '.$table->rowclass[$key].'">'."\n";
            if ($row == 'hr' and $countcols) {
                $output .= '<td colspan="'. $countcols .'"><div class="tabledivider"></div></td>';
            } else {  /// it's a normal row of data
                $keys2 = array_keys($row);
                $lastkey = end($keys2);
                foreach ($row as $key => $item) {
                    $classes = array('cell', 'c' . $key);
                    if (!isset($size[$key])) {
                        $size[$key] = '';
                    }
                    if (!isset($align[$key])) {
                        $align[$key] = '';
                    }
                    if (!isset($wrap[$key])) {
                        $wrap[$key] = '';
                    }
                    if ($key == $lastkey) {
                        $classes[] = 'lastcol';
                    }
                    if (isset($table->colclasses[$key])) {
                        $classes[] = $table->colclasses[$key];
                    }
                    $output .= '<td style="'. $align[$key].$size[$key].$wrap[$key] .'" class="'.implode(' ', $classes).'">'. $item .'</td>';
                }
            }
            $output .= '</tr>'."\n";
        }
    }
    $output .= '</table>'."\n";

    if ($table->rotateheaders && can_use_rotated_text()) {
        require_js(array('yui_yahoo','yui_event','yui_dom'));
        require_js('course/report/progress/textrotate.js');
    }

    if ($return) {
        return $output;
    }

    echo $output;
    return true;
}

function print_recent_activity_note($time, $user, $text, $link, $return=false, $viewfullnames=null) {
    static $strftimerecent = null;
    $output = '';

    if (is_null($viewfullnames)) {
        $context = get_context_instance(CONTEXT_SYSTEM);
        $viewfullnames = has_capability('moodle/site:viewfullnames', $context);
    }

    if (is_null($strftimerecent)) {
        $strftimerecent = get_string('strftimerecent');
    }

    $output .= '<div class="head">';
    $output .= '<div class="date">'.userdate($time, $strftimerecent).'</div>';
    $output .= '<div class="name">'.fullname($user, $viewfullnames).'</div>';
    $output .= '</div>';
    $output .= '<div class="info"><a href="'.$link.'">'.format_string($text,true).'</a></div>';

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}


/**
 * Prints a basic textarea field.
 *
 * When using this function, you should
 *
 * @uses $CFG
 * @param bool $usehtmleditor Enables the use of the htmleditor for this field.
 * @param int $rows Number of rows to display  (minimum of 10 when $height is non-null)
 * @param int $cols Number of columns to display (minimum of 65 when $width is non-null)
 * @param null $width (Deprecated) Width of the element; if a value is passed, the minimum value for $cols will be 65. Value is otherwise ignored.
 * @param null $height (Deprecated) Height of the element; if a value is passe, the minimum value for $rows will be 10. Value is otherwise ignored.
 * @param string $name Name to use for the textarea element.
 * @param string $value Initial content to display in the textarea.
 * @param int $obsolete deprecated
 * @param bool $return If false, will output string. If true, will return string value.
 * @param string $id CSS ID to add to the textarea element.
 * @param string $editorclass CSS classes to add to the textarea element when using the htmleditor. Use 'form-textarea-simple' to get a basic editor. Defaults to 'form-textarea-advanced' (complete editor). If this is null or invalid, the htmleditor will not show for this field.
 */
function print_textarea($usehtmleditor, $rows, $cols, $width, $height, $name, $value='', $obsolete=0, $return=false, $id='') {
    /// $width and height are legacy fields and no longer used as pixels like they used to be.
    /// However, you can set them to zero to override the mincols and minrows values below.

    global $CFG;

    $mincols = 65;
    $minrows = 10;
    $str = '';

    if ($id === '') {
        $id = 'edit-'.$name;
    }

    if ($usehtmleditor) {
        if ($height && ($rows < $minrows)) {
            $rows = $minrows;
        }
        if ($width && ($cols < $mincols)) {
            $cols = $mincols;
        }
    }

    if ($usehtmleditor) {
        $editor = get_preferred_texteditor(FORMAT_HTML);
        $editorclass = $editor->get_legacy_textarea_class();
    } else {
        $editorclass = '';
    }

    $str .= "\n".'<textarea class="form-textarea '.$editorclass.'" id="'. $id .'" name="'. $name .'" rows="'. $rows .'" cols="'. $cols .'">'."\n";
    if ($usehtmleditor) {
        $str .= htmlspecialchars($value); // needed for editing of cleaned text!
    } else {
        $str .= s($value);
    }
    $str .= '</textarea>'."\n";

    if ($return) {
        return $str;
    }
    echo $str;
}

/**
 * Returns a turn edit on/off button for course in a self contained form.
 * Used to be an icon, but it's now a simple form button
 *
 * Note that the caller is responsible for capchecks.
 *
 * @uses $CFG
 * @uses $USER
 * @param int $courseid The course  to update by id as found in 'course' table
 * @return string
 */
function update_course_icon($courseid) {
    global $CFG, $USER;

    if (!empty($USER->editing)) {
        $string = get_string('turneditingoff');
        $edit = '0';
    } else {
        $string = get_string('turneditingon');
        $edit = '1';
    }

    return '<form '.$CFG->frametarget.' method="get" action="'.$CFG->wwwroot.'/course/view.php">'.
           '<div>'.
           '<input type="hidden" name="id" value="'.$courseid.'" />'.
           '<input type="hidden" name="edit" value="'.$edit.'" />'.
           '<input type="hidden" name="sesskey" value="'.sesskey().'" />'.
           '<input type="submit" value="'.$string.'" />'.
           '</div></form>';
}

/**
 * Returns a little popup menu for switching roles
 *
 * @uses $CFG
 * @uses $USER
 * @param int $courseid The course  to update by id as found in 'course' table
 * @return string
 */
function switchroles_form($courseid) {

    global $CFG, $USER;


    if (!$context = get_context_instance(CONTEXT_COURSE, $courseid)) {
        return '';
    }

    if (!empty($USER->access['rsw'][$context->path])){  // Just a button to return to normal
        $options = array();
        $options['id'] = $courseid;
        $options['sesskey'] = sesskey();
        $options['switchrole'] = 0;

        return print_single_button($CFG->wwwroot.'/course/view.php', $options,
                                   get_string('switchrolereturn'), 'post', '_self', true);
    }

    if (has_capability('moodle/role:switchroles', $context)) {
        if (!$roles = get_switchable_roles($context)) {
            return '';   // Nothing to show!
        }
        // unset default user role - it would not work
        unset($roles[$CFG->guestroleid]);
        return popup_form($CFG->wwwroot.'/course/view.php?id='.$courseid.'&amp;sesskey='.sesskey().'&amp;switchrole=',
                          $roles, 'switchrole', '', get_string('switchroleto'), 'switchrole', get_string('switchroleto'), true);
    }

    return '';
}


/**
 * Returns a turn edit on/off button for course in a self contained form.
 * Used to be an icon, but it's now a simple form button
 *
 * @uses $CFG
 * @uses $USER
 * @param int $courseid The course  to update by id as found in 'course' table
 * @return string
 */
function update_mymoodle_icon() {

    global $CFG, $USER;

    if (!empty($USER->editing)) {
        $string = get_string('updatemymoodleoff');
        $edit = '0';
    } else {
        $string = get_string('updatemymoodleon');
        $edit = '1';
    }

    return "<form $CFG->frametarget method=\"get\" action=\"$CFG->wwwroot/my/index.php\">".
           "<div>".
           "<input type=\"hidden\" name=\"edit\" value=\"$edit\" />".
           "<input type=\"submit\" value=\"$string\" /></div></form>";
}

/**
 * Returns a turn edit on/off button for tag in a self contained form.
 *
 * @uses $CFG
 * @uses $USER
 * @return string
 */
function update_tag_button($tagid) {

    global $CFG, $USER;

    if (!empty($USER->editing)) {
        $string = get_string('turneditingoff');
        $edit = '0';
    } else {
        $string = get_string('turneditingon');
        $edit = '1';
    }

    return "<form $CFG->frametarget method=\"get\" action=\"$CFG->wwwroot/tag/index.php\">".
           "<div>".
           "<input type=\"hidden\" name=\"edit\" value=\"$edit\" />".
           "<input type=\"hidden\" name=\"id\" value=\"$tagid\" />".
           "<input type=\"submit\" value=\"$string\" /></div></form>";
}

/**
 * Prints the 'update this xxx' button that appears on module pages.
 * @param $cmid the course_module id.
 * @param $ignored not used any more. (Used to be courseid.)
 * @param $string the module name - get_string('modulename', 'xxx')
 * @return string the HTML for the button, if this user has permission to edit it, else an empty string.
 */
function update_module_button($cmid, $ignored, $string) {
    global $CFG, $USER;

    if (has_capability('moodle/course:manageactivities', get_context_instance(CONTEXT_MODULE, $cmid))) {
        $string = get_string('updatethis', '', $string);

        return "<form $CFG->frametarget method=\"get\" action=\"$CFG->wwwroot/course/mod.php\" onsubmit=\"this.target='{$CFG->framename}'; return true\">".//hack to allow edit on framed resources
               "<div>".
               "<input type=\"hidden\" name=\"update\" value=\"$cmid\" />".
               "<input type=\"hidden\" name=\"return\" value=\"true\" />".
               "<input type=\"hidden\" name=\"sesskey\" value=\"".sesskey()."\" />".
               "<input type=\"submit\" value=\"$string\" /></div></form>";
    } else {
        return '';
    }
}

/**
 * Prints the editing button on search results listing
 * For bulk move courses to another category
 */

function update_categories_search_button($search,$page,$perpage) {
    global $CFG, $PAGE;

    // not sure if this capability is the best  here
    if (has_capability('moodle/category:manage', get_context_instance(CONTEXT_SYSTEM))) {
        if ($PAGE->user_is_editing()) {
            $string = get_string("turneditingoff");
            $edit = "off";
            $perpage = 30;
        } else {
            $string = get_string("turneditingon");
            $edit = "on";
        }

        return "<form $CFG->frametarget method=\"get\" action=\"$CFG->wwwroot/course/search.php\">".
               '<div>'.
               "<input type=\"hidden\" name=\"edit\" value=\"$edit\" />".
               "<input type=\"hidden\" name=\"sesskey\" value=\"".sesskey()."\" />".
               "<input type=\"hidden\" name=\"search\" value=\"".s($search, true)."\" />".
               "<input type=\"hidden\" name=\"page\" value=\"$page\" />".
               "<input type=\"hidden\" name=\"perpage\" value=\"$perpage\" />".
               "<input type=\"submit\" value=\"".s($string)."\" /></div></form>";
    }
}

/**
 * Given a course and a (current) coursemodule
 * This function returns a small popup menu with all the
 * course activity modules in it, as a navigation menu
 * The data is taken from the serialised array stored in
 * the course record
 *
 * @param course $course A {@link $COURSE} object.
 * @param course $cm A {@link $COURSE} object.
 * @param string $targetwindow ?
 * @return string
 * @todo Finish documenting this function
 */
function navmenu($course, $cm=NULL, $targetwindow='self') {
    global $CFG, $THEME, $USER, $DB;
    require_once($CFG->dirroot . '/course/lib.php'); // Required for get_fast_modinfo

    if (empty($THEME->navmenuwidth)) {
        $width = 50;
    } else {
        $width = $THEME->navmenuwidth;
    }

    if ($cm) {
        $cm = $cm->id;
    }

    if ($course->format == 'weeks') {
        $strsection = get_string('week');
    } else {
        $strsection = get_string('topic');
    }
    $strjumpto = get_string('jumpto');

    $modinfo = get_fast_modinfo($course);
    $context = get_context_instance(CONTEXT_COURSE, $course->id);

    $section = -1;
    $selected = '';
    $url = '';
    $previousmod = NULL;
    $backmod = NULL;
    $nextmod = NULL;
    $selectmod = NULL;
    $logslink = NULL;
    $flag = false;
    $menu = array();
    $menustyle = array();

    $sections = $DB->get_records('course_sections', array('course'=>$course->id), 'section', 'section,visible,summary');

    if (!empty($THEME->makenavmenulist)) {   /// A hack to produce an XHTML navmenu list for use in themes
        $THEME->navmenulist = navmenulist($course, $sections, $modinfo, $strsection, $strjumpto, $width, $cm);
    }

    foreach ($modinfo->cms as $mod) {
        if ($mod->modname == 'label') {
            continue;
        }

        if ($mod->sectionnum > $course->numsections) {   /// Don't show excess hidden sections
            break;
        }

        if (!$mod->uservisible) { // do not icnlude empty sections at all
            continue;
        }

        if ($mod->sectionnum > 0 and $section != $mod->sectionnum) {
            $thissection = $sections[$mod->sectionnum];

            if ($thissection->visible or !$course->hiddensections or
                has_capability('moodle/course:viewhiddensections', $context)) {
                $thissection->summary = strip_tags(format_string($thissection->summary,true));
                if ($course->format == 'weeks' or empty($thissection->summary)) {
                    $menu[] = '--'.$strsection ." ". $mod->sectionnum;
                } else {
                    if (strlen($thissection->summary) < ($width-3)) {
                        $menu[] = '--'.$thissection->summary;
                    } else {
                        $menu[] = '--'.substr($thissection->summary, 0, $width).'...';
                    }
                }
                $section = $mod->sectionnum;
            } else {
                // no activities from this hidden section shown
                continue;
            }
        }

        $url = $mod->modname.'/view.php?id='. $mod->id;
        if ($flag) { // the current mod is the "next" mod
            $nextmod = $mod;
            $flag = false;
        }
        $localname = $mod->name;
        if ($cm == $mod->id) {
            $selected = $url;
            $selectmod = $mod;
            $backmod = $previousmod;
            $flag = true; // set flag so we know to use next mod for "next"
            $localname = $strjumpto;
            $strjumpto = '';
        } else {
            $localname = strip_tags(format_string($localname,true));
            $tl=textlib_get_instance();
            if ($tl->strlen($localname) > ($width+5)) {
                $localname = $tl->substr($localname, 0, $width).'...';
            }
            if (!$mod->visible) {
                $localname = '('.$localname.')';
            }
        }
        $menu[$url] = $localname;
        if (empty($THEME->navmenuiconshide)) {
            $menustyle[$url] = 'style="background-image: url('.$CFG->modpixpath.'/'.$mod->modname.'/icon.gif);"';  // Unfortunately necessary to do this here
        }
        $previousmod = $mod;
    }
    //Accessibility: added Alt text, replaced &gt; &lt; with 'silent' character and 'accesshide' text.

    if ($selectmod and has_capability('coursereport/log:view', $context)) {
        $logstext = get_string('alllogs');
        $logslink = '<li>'."\n".'<a title="'.$logstext.'" '.
                    $CFG->frametarget.'onclick="this.target=\''.$CFG->framename.'\';"'.' href="'.
                    $CFG->wwwroot.'/course/report/log/index.php?chooselog=1&amp;user=0&amp;date=0&amp;id='.
                       $course->id.'&amp;modid='.$selectmod->id.'">'.
                    '<img class="icon log" src="'.$CFG->pixpath.'/i/log.gif" alt="'.$logstext.'" /></a>'."\n".'</li>';

    }
    if ($backmod) {
        $backtext= get_string('activityprev', 'access');
        $backmod = '<li><form action="'.$CFG->wwwroot.'/mod/'.$backmod->modname.'/view.php" '.
                   'onclick="this.target=\''.$CFG->framename.'\';"'.'><fieldset class="invisiblefieldset">'.
                   '<input type="hidden" name="id" value="'.$backmod->id.'" />'.
                   '<button type="submit" title="'.$backtext.'">'.link_arrow_left($backtext, $url='', $accesshide=true).
                   '</button></fieldset></form></li>';
    }
    if ($nextmod) {
        $nexttext= get_string('activitynext', 'access');
        $nextmod = '<li><form action="'.$CFG->wwwroot.'/mod/'.$nextmod->modname.'/view.php"  '.
                   'onclick="this.target=\''.$CFG->framename.'\';"'.'><fieldset class="invisiblefieldset">'.
                   '<input type="hidden" name="id" value="'.$nextmod->id.'" />'.
                   '<button type="submit" title="'.$nexttext.'">'.link_arrow_right($nexttext, $url='', $accesshide=true).
                   '</button></fieldset></form></li>';
    }

    return '<div class="navigation">'."\n".'<ul>'.$logslink . $backmod .
            '<li>'.popup_form($CFG->wwwroot .'/mod/', $menu, 'navmenupopup', $selected, $strjumpto,
                       '', '', true, $targetwindow, '', $menustyle).'</li>'.
            $nextmod . '</ul>'."\n".'</div>';
}

/**
 * Given a course
 * This function returns a small popup menu with all the
 * course activity modules in it, as a navigation menu
 * outputs a simple list structure in XHTML
 * The data is taken from the serialised array stored in
 * the course record
 *
 * @param course $course A {@link $COURSE} object.
 * @return string
 * @todo Finish documenting this function
 */
function navmenulist($course, $sections, $modinfo, $strsection, $strjumpto, $width=50, $cmid=0) {

    global $CFG;

    $section = -1;
    $url = '';
    $menu = array();
    $doneheading = false;

    $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);

    $menu[] = '<ul class="navmenulist"><li class="jumpto section"><span>'.$strjumpto.'</span><ul>';
    foreach ($modinfo->cms as $mod) {
        if ($mod->modname == 'label') {
            continue;
        }

        if ($mod->sectionnum > $course->numsections) {   /// Don't show excess hidden sections
            break;
        }

        if (!$mod->uservisible) { // do not icnlude empty sections at all
            continue;
        }

        if ($mod->sectionnum >= 0 and $section != $mod->sectionnum) {
            $thissection = $sections[$mod->sectionnum];

            if ($thissection->visible or !$course->hiddensections or
                      has_capability('moodle/course:viewhiddensections', $coursecontext)) {
                $thissection->summary = strip_tags(format_string($thissection->summary,true));
                if (!$doneheading) {
                    $menu[] = '</ul></li>';
                }
                if ($course->format == 'weeks' or empty($thissection->summary)) {
                    $item = $strsection ." ". $mod->sectionnum;
                } else {
                    if (strlen($thissection->summary) < ($width-3)) {
                        $item = $thissection->summary;
                    } else {
                        $item = substr($thissection->summary, 0, $width).'...';
                    }
                }
                $menu[] = '<li class="section"><span>'.$item.'</span>';
                $menu[] = '<ul>';
                $doneheading = true;

                $section = $mod->sectionnum;
            } else {
                // no activities from this hidden section shown
                continue;
            }
        }

        $url = $mod->modname .'/view.php?id='. $mod->id;
        $mod->name = strip_tags(format_string(urldecode($mod->name),true));
        if (strlen($mod->name) > ($width+5)) {
            $mod->name = substr($mod->name, 0, $width).'...';
        }
        if (!$mod->visible) {
            $mod->name = '('.$mod->name.')';
        }
        $class = 'activity '.$mod->modname;
        $class .= ($cmid == $mod->id) ? ' selected' : '';
        $menu[] = '<li class="'.$class.'">'.
                  '<img src="'.$CFG->modpixpath.'/'.$mod->modname.'/icon.gif" alt="" />'.
                  '<a href="'.$CFG->wwwroot.'/mod/'.$url.'">'.$mod->name.'</a></li>';
    }

    if ($doneheading) {
        $menu[] = '</ul></li>';
    }
    $menu[] = '</ul></li></ul>';

    return implode("\n", $menu);
}

/**
 * Prints form items with the names $day, $month and $year
 *
 * @param string $day   fieldname
 * @param string $month  fieldname
 * @param string $year  fieldname
 * @param int $currenttime A default timestamp in GMT
 * @param boolean $return
 */
function print_date_selector($day, $month, $year, $currenttime=0, $return=false) {

    if (!$currenttime) {
        $currenttime = time();
    }
    $currentdate = usergetdate($currenttime);

    for ($i=1; $i<=31; $i++) {
        $days[$i] = $i;
    }
    for ($i=1; $i<=12; $i++) {
        $months[$i] = userdate(gmmktime(12,0,0,$i,15,2000), "%B");
    }
    for ($i=1970; $i<=2020; $i++) {
        $years[$i] = $i;
    }

    // Build or print result
    $result='';
    // Note: There should probably be a fieldset around these fields as they are
    // clearly grouped. However this causes problems with display. See Mozilla
    // bug 474415
    $result.='<label class="accesshide" for="menu'.$day.'">'.get_string('day','form').'</label>';
    $result.=choose_from_menu($days,   $day,   $currentdate['mday'], '', '', '0', true);
    $result.='<label class="accesshide" for="menu'.$month.'">'.get_string('month','form').'</label>';
    $result.=choose_from_menu($months, $month, $currentdate['mon'],  '', '', '0', true);
    $result.='<label class="accesshide" for="menu'.$year.'">'.get_string('year','form').'</label>';
    $result.=choose_from_menu($years,  $year,  $currentdate['year'], '', '', '0', true);

    if ($return) {
        return $result;
    } else {
        echo $result;
    }
}

/**
 *Prints form items with the names $hour and $minute
 *
 * @param string $hour  fieldname
 * @param string ? $minute  fieldname
 * @param $currenttime A default timestamp in GMT
 * @param int $step minute spacing
 * @param boolean $return
 */
function print_time_selector($hour, $minute, $currenttime=0, $step=5, $return=false) {

    if (!$currenttime) {
        $currenttime = time();
    }
    $currentdate = usergetdate($currenttime);
    if ($step != 1) {
        $currentdate['minutes'] = ceil($currentdate['minutes']/$step)*$step;
    }
    for ($i=0; $i<=23; $i++) {
        $hours[$i] = sprintf("%02d",$i);
    }
    for ($i=0; $i<=59; $i+=$step) {
        $minutes[$i] = sprintf("%02d",$i);
    }

    // Build or print result
    $result='';
    // Note: There should probably be a fieldset around these fields as they are
    // clearly grouped. However this causes problems with display. See Mozilla
    // bug 474415
    $result.='<label class="accesshide" for="menu'.$hour.'">'.get_string('hour','form').'</label>';
    $result.=choose_from_menu($hours,   $hour,   $currentdate['hours'],   '','','0',true);
    $result.='<label class="accesshide" for="menu'.$minute.'">'.get_string('minute','form').'</label>';
    $result.=choose_from_menu($minutes, $minute, $currentdate['minutes'], '','','0',true);

    if ($return) {
        return $result;
    } else {
        echo $result;
    }
}

/**
 * Prints time limit value selector
 *
 * @uses $CFG
 * @param int $timelimit default
 * @param string $unit
 * @param string $name
 * @param boolean $return
 */
function print_timer_selector($timelimit = 0, $unit = '', $name = 'timelimit', $return=false) {

    global $CFG;

    if ($unit) {
        $unit = ' '.$unit;
    }

    // Max timelimit is sessiontimeout - 10 minutes.
    $maxvalue = ($CFG->sessiontimeout / 60) - 10;

    for ($i=1; $i<=$maxvalue; $i++) {
        $minutes[$i] = $i.$unit;
    }
    return choose_from_menu($minutes, $name, $timelimit, get_string('none'), '','','0',$return);
}

/**
 * Prints a grade menu (as part of an existing form) with help
 * Showing all possible numerical grades and scales
 *
 * @uses $CFG
 * @param int $courseid ?
 * @param string $name ?
 * @param string $current ?
 * @param boolean $includenograde ?
 * @todo Finish documenting this function
 */
function print_grade_menu($courseid, $name, $current, $includenograde=true, $return=false) {

    global $CFG;

    $output = '';
    $strscale = get_string('scale');
    $strscales = get_string('scales');

    $scales = get_scales_menu($courseid);
    foreach ($scales as $i => $scalename) {
        $grades[-$i] = $strscale .': '. $scalename;
    }
    if ($includenograde) {
        $grades[0] = get_string('nograde');
    }
    for ($i=100; $i>=1; $i--) {
        $grades[$i] = $i;
    }
    $output .= choose_from_menu($grades, $name, $current, '', '', 0, true);

    $linkobject = '<span class="helplink"><img class="iconhelp" alt="'.$strscales.'" src="'.$CFG->pixpath .'/help.gif" /></span>';
    $output .= link_to_popup_window ('/course/scales.php?id='. $courseid .'&amp;list=true', 'ratingscales',
                                     $linkobject, 400, 500, $strscales, 'none', true);

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * Prints a scale menu (as part of an existing form) including help button
 * Just like {@link print_grade_menu()} but without the numeric grades
 *
 * @param int $courseid ?
 * @param string $name ?
 * @param string $current ?
 * @todo Finish documenting this function
 */
function print_scale_menu($courseid, $name, $current, $return=false) {

    global $CFG;

    $output = '';
    $strscales = get_string('scales');
    $output .= choose_from_menu(get_scales_menu($courseid), $name, $current, '', '', 0, true);

    $linkobject = '<span class="helplink"><img class="iconhelp" alt="'.$strscales.'" src="'.$CFG->pixpath .'/help.gif" /></span>';
    $output .= link_to_popup_window ('/course/scales.php?id='. $courseid .'&amp;list=true', 'ratingscales',
                                     $linkobject, 400, 500, $strscales, 'none', true);
    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * Prints a help button about a scale
 *
 * @uses $CFG
 * @param id $courseid ?
 * @param object $scale ?
 * @todo Finish documenting this function
 */
function print_scale_menu_helpbutton($courseid, $scale, $return=false) {

    global $CFG;

    $output = '';
    $strscales = get_string('scales');

    $linkobject = '<span class="helplink"><img class="iconhelp" alt="'.$scale->name.'" src="'.$CFG->pixpath .'/help.gif" /></span>';
    $output .= link_to_popup_window ('/course/scales.php?id='. $courseid .'&amp;list=true&amp;scaleid='. $scale->id, 'ratingscale',
                                     $linkobject, 400, 500, $scale->name, 'none', true);
    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

/**
 * Print an error page displaying an error message.  New method - use this for new code.
 *
 * @param string $errorcode The name of the string from error.php to print
 * @param string $module name of module
 * @param string $link The url where the user will be prompted to continue. If no url is provided the user will be directed to the site index page.
 * @param object $a Extra words and phrases that might be required in the error string
 * @return terminates script, does not return!
 */
function print_error($errorcode, $module='error', $link='', $a=NULL) {
    global $CFG, $UNITTEST;

    // If unittest running, throw exception instead
    if (!empty($UNITTEST->running)) {
        // Errors in unit test become exceptions, so you can unit test
        // code that might call error().
        throw new moodle_exception($errorcode, $module, $link, $a);
    }

    if (empty($module) || $module == 'moodle' || $module == 'core') {
        $module = 'error';
    }

    if (!isset($CFG->theme) or !isset($CFG->stylesheets)) {
        // error found before setup.php finished
        _print_early_error($errorcode, $module, $a);
    } else {
        _print_normal_error($errorcode, $module, $a, $link, debug_backtrace());
    }
}

/**
 * Internal function - do not use directly!!
 */
function _print_normal_error($errorcode, $module, $a, $link, $backtrace, $debuginfo=null, $showerrordebugwarning=false) {
    global $CFG, $SESSION, $THEME, $DB, $PAGE;

    if ($DB) {
        //if you enable db debugging and exception is thrown, the print footer prints a lot of rubbish
        $DB->set_debug(0);
    }

    if ($module === 'error') {
        $modulelink = 'moodle';
    } else {
        $modulelink = $module;
    }

    $message = get_string($errorcode, $module, $a);
    if ($module === 'error' and strpos($message, '[[') === 0) {
        //search in moodle file if error specified - needed for backwards compatibility
        $message = get_string($errorcode, 'moodle', $a);
    }

    if (CLI_SCRIPT) {
        // Errors in cron should be mtrace'd.
        mtrace($message);
        die;
    }

    if (empty($link) and !defined('ADMIN_EXT_HEADER_PRINTED')) {
        if ( !empty($SESSION->fromurl) ) {
            $link = $SESSION->fromurl;
            unset($SESSION->fromurl);
        } else {
            $link = $CFG->wwwroot .'/';
        }
    }

    if (!empty($CFG->errordocroot)) {
        $errordocroot = $CFG->errordocroot;
    } else if (!empty($CFG->docroot)) {
        $errordocroot = $CFG->docroot;
    } else {
        $errordocroot = 'http://docs.moodle.org';
    }

    if (!$PAGE->headerprinted) {
        //header not yet printed
        @header('HTTP/1.0 404 Not Found');
        print_header(get_string('error'));
    } else {
        print_container_end_all(false, $THEME->open_header_containers);
    }

    echo '<br />';

    $message = clean_text('<p class="errormessage">'.$message.'</p>'.
               '<p class="errorcode">'.
               '<a href="'.$errordocroot.'/en/error/'.$modulelink.'/'.$errorcode.'">'.
                 get_string('moreinformation').'</a></p>');

    print_simple_box($message, '', '', '', '', 'errorbox');

    if ($showerrordebugwarning) {
        debugging('error() is a deprecated function, please call print_error() instead of error()', DEBUG_DEVELOPER);

    } else {
        if (debugging('', DEBUG_DEVELOPER)) {
            if ($debuginfo) {
                debugging($debuginfo, DEBUG_DEVELOPER, $backtrace);
            } else {
                notify('Stack trace:'.print_backtrace($backtrace, true), 'notifytiny');
            }
        }
    }

    if (!empty($link)) {
        print_continue($link);
    }

    print_footer();

    for ($i=0;$i<512;$i++) {  // Padding to help IE work with 404
        echo ' ';
    }
    die;
}

/**
 * Internal function - do not use directly!!
 * This function is used if fatal error occures before the themes are fully initialised (eg. in lib/setup.php)
 */
function _print_early_error($errorcode, $module, $a, $backtrace=null, $debuginfo=null) {
    $message = get_string($errorcode, $module, $a);
    if ($module === 'error' and strpos($message, '[[') === 0) {
        //search in moodle file if error specified - needed for backwards compatibility
        $message = get_string($errorcode, 'moodle', $a);
    }
    $message = clean_text($message);

    // In the name of protocol correctness, monitoring and performance
    // profiling, set the appropriate error headers for machine comsumption
    if (isset($_SERVER['SERVER_PROTOCOL'])) {
        // Avoid it with cron.php. Note that we assume it's HTTP/1.x
        @header($_SERVER['SERVER_PROTOCOL'] . ' 503 Service Unavailable');
    }

    // better disable any caching
    @header('Content-Type: text/html; charset=utf-8');
    @header('Cache-Control: no-store, no-cache, must-revalidate');
    @header('Cache-Control: post-check=0, pre-check=0', false);
    @header('Pragma: no-cache');
    @header('Expires: Mon, 20 Aug 1969 09:23:00 GMT');
    @header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');

    echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" '.get_html_lang().'>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>'.get_string('error').'</title>
</head><body>
<div style="margin-top: 6em; margin-left:auto; margin-right:auto; color:#990000; text-align:center; font-size:large; border-width:1px;
    border-color:black; background-color:#ffffee; border-style:solid; border-radius: 20px; border-collapse: collapse;
    width: 80%; -moz-border-radius: 20px; padding: 15px">
'.$message.'
</div>';
    if (debugging('', DEBUG_DEVELOPER)) {
        if ($debuginfo) {
            debugging($debuginfo, DEBUG_DEVELOPER, $backtrace);
        } else if ($backtrace) {
            notify('Stack trace:'.print_backtrace($backtrace, true), 'notifytiny');
        }
    }

    echo '</body></html>';
    die;
}

/**
 * Print an error to STDOUT and exit with a non-zero code. For commandline scripts.
 * Default errorcode is 1.
 *
 * Very useful for perl-like error-handling:
 *
 * do_somethting() or mdie("Something went wrong");
 *
 * @param string  $msg       Error message
 * @param integer $errorcode Error code to emit
 */
function mdie($msg='', $errorcode=1) {
    trigger_error($msg);
    exit($errorcode);
}

/**
 * Returns a string of html with an image of a help icon linked to a help page on a number of help topics.
 * Should be used only with htmleditor or textarea.
 * @param mixed $helptopics variable amount of params accepted. Each param may be a string or an array of arguments for
 *                  helpbutton.
 * @return string
 */
function editorhelpbutton(){
    global $CFG, $SESSION;
    $items = func_get_args();
    $i = 1;
    $urlparams = array();
    $titles = array();
    foreach ($items as $item){
        if (is_array($item)){
            $urlparams[] = "keyword$i=".urlencode($item[0]);
            $urlparams[] = "title$i=".urlencode($item[1]);
            if (isset($item[2])){
                $urlparams[] = "module$i=".urlencode($item[2]);
            }
            $titles[] = trim($item[1], ". \t");
        } else if (is_string($item)) {
            $urlparams[] = "button$i=".urlencode($item);
            switch ($item) {
                case 'reading' :
                    $titles[] = get_string("helpreading");
                    break;
                case 'writing' :
                    $titles[] = get_string("helpwriting");
                    break;
                case 'questions' :
                    $titles[] = get_string("helpquestions");
                    break;
                case 'emoticons2' :
                    $titles[] = get_string("helpemoticons");
                    break;
                case 'richtext2' :
                    $titles[] = get_string('helprichtext');
                    break;
                case 'text2' :
                    $titles[] = get_string('helptext');
                    break;
                default :
                    print_error('unknownhelp', '', '', $item);
            }
        }
        $i++;
    }
    if (count($titles)>1){
        //join last two items with an 'and'
        $a = new object();
        $a->one = $titles[count($titles) - 2];
        $a->two = $titles[count($titles) - 1];
        $titles[count($titles) - 2] = get_string('and', '', $a);
        unset($titles[count($titles) - 1]);
    }
    $alttag = join (', ', $titles);

    $paramstring = join('&', $urlparams);
    $linkobject = '<img alt="'.$alttag.'" class="iconhelp" src="'.$CFG->pixpath .'/help.gif" />';
    return link_to_popup_window(s('/lib/form/editorhelp.php?'.$paramstring), 'popup', $linkobject, 400, 500, $alttag, 'none', true);
}

/**
 * Print a help button.
 *
 * @uses $CFG
 * @param string $page  The keyword that defines a help page
 * @param string $title The title of links, rollover tips, alt tags etc
 *           'Help with' (or the language equivalent) will be prefixed and '...' will be stripped.
 * @param string $module Which module is the page defined in
 * @param mixed $image Use a help image for the link?  (true/false/"both")
 * @param boolean $linktext If true, display the title next to the help icon.
 * @param string $text If defined then this text is used in the page, and
 *           the $page variable is ignored.
 * @param boolean $return If true then the output is returned as a string, if false it is printed to the current page.
 * @param string $imagetext The full text for the helpbutton icon. If empty use default help.gif
 * @return string
 * @todo Finish documenting this function
 */
function helpbutton($page, $title, $module='moodle', $image=true, $linktext=false, $text='', $return=false,
                     $imagetext='') {
    global $CFG, $COURSE;

    //warning if ever $text parameter is used
    //$text option won't work properly because the text needs to be always cleaned and,
    // when cleaned... html tags always break, so it's unusable.
    if ( isset($text) && $text!='') {
        debugging('Warning: it\'s not recommended to use $text parameter in helpbutton ($page=' . $page . ', $module=' . $module . ') function', DEBUG_DEVELOPER);
    }

    // Catch references to the old text.html and emoticons.html help files that
    // were renamed in MDL-13233.
    if (in_array($page, array('text', 'emoticons', 'richtext'))) {
        $oldname = $page;
        $page .= '2';
        debugging("You are referring to the old help file '$oldname'. " .
                "This was renamed to '$page' becuase of MDL-13233. " .
                "Please update your code.", DEBUG_DEVELOPER);
    }

    if ($module == '') {
        $module = 'moodle';
    }

    if ($title == '' && $linktext == '') {
        debugging('Error in call to helpbutton function: at least one of $title and $linktext is required');
    }

    // Warn users about new window for Accessibility
    $tooltip = get_string('helpprefix2', '', trim($title, ". \t")) .' ('.get_string('newwindow').')';

    $linkobject = '';

    if ($image) {
        if ($linktext) {
            // MDL-7469 If text link is displayed with help icon, change to alt to "help with this".
            $linkobject .= $title.'&nbsp;';
            $tooltip = get_string('helpwiththis');
        }
        if ($imagetext) {
            $linkobject .= $imagetext;
        } else {
            $linkobject .= '<img class="iconhelp" alt="'.s(strip_tags($tooltip)).'" src="'.
                $CFG->pixpath .'/help.gif" />';
        }
    } else {
        $linkobject .= $tooltip;
    }

    // fix for MDL-7734
    if ($text) {
        $url = '/help.php?text='. s(urlencode($text));
    } else {
        $url = '/help.php?module='. $module .'&amp;file='. $page .'.html';
        // fix for MDL-7734
        if (!empty($COURSE->lang)) {
            $url .= '&amp;forcelang=' . $COURSE->lang;
        }
    }

    $link = '<span class="helplink">' . link_to_popup_window($url, 'popup',
            $linkobject, 400, 500, $tooltip, 'none', true) . '</span>';

    if ($return) {
        return $link;
    } else {
        echo $link;
    }
}

/**
 * Print a help button.
 *
 * Prints a special help button that is a link to the "live" emoticon popup
 * @uses $CFG
 * @uses $SESSION
 * @param string $form ?
 * @param string $field ?
 * @todo Finish documenting this function
 */
function emoticonhelpbutton($form, $field, $return = false) {

    global $CFG, $SESSION;

    $SESSION->inserttextform = $form;
    $SESSION->inserttextfield = $field;
    $imagetext = '<img src="' . $CFG->pixpath . '/s/smiley.gif" alt="" class="emoticon" style="margin-left:3px; padding-right:1px;width:15px;height:15px;" />';
    $help = helpbutton('emoticons2', get_string('helpemoticons'), 'moodle', true, true, '', true, $imagetext);
    if (!$return){
        echo $help;
    } else {
        return $help;
    }
}

/**
 * Print a help button.
 *
 * Prints a special help button for html editors (htmlarea in this case)
 * @uses $CFG
 */
function editorshortcutshelpbutton() {

    global $CFG;
    //TODO: detect current editor and print correct info
/*    $imagetext = '<img src="' . $CFG->httpswwwroot . '/lib/editor/htmlarea/images/kbhelp.gif" alt="'.
        get_string('editorshortcutkeys').'" class="iconkbhelp" />';

    return helpbutton('editorshortcuts', get_string('editorshortcutkeys'), 'moodle', true, false, '', true, $imagetext);*/
    return '';
}

/**
 * Print a message and exit.
 *
 * @uses $CFG
 * @param string $message ?
 * @param string $link ?
 * @todo Finish documenting this function
 */
function notice ($message, $link='', $course=NULL) {
    global $CFG, $SITE, $THEME, $COURSE, $PAGE;

    $message = clean_text($message);   // In case nasties are in here

    if (CLI_SCRIPT) {
        // notices in cron should be mtrace'd.
        mtrace($message);
        die;
    }

    if (!$PAGE->headerprinted) {
        //header not yet printed
        print_header(get_string('notice'));
    } else {
        print_container_end_all(false, $THEME->open_header_containers);
    }

    print_box($message, 'generalbox', 'notice');
    print_continue($link);

    if (empty($course)) {
        print_footer($COURSE);
    } else {
        print_footer($course);
    }
    exit;
}

/**
 * Print a message along with "Yes" and "No" links for the user to continue.
 *
 * @param string $message The text to display
 * @param string $linkyes The link to take the user to if they choose "Yes"
 * @param string $linkno The link to take the user to if they choose "No"
 * TODO Document remaining arguments
 */
function notice_yesno ($message, $linkyes, $linkno, $optionsyes=NULL, $optionsno=NULL, $methodyes='post', $methodno='post') {

    global $CFG;

    $message = clean_text($message);
    $linkyes = clean_text($linkyes);
    $linkno = clean_text($linkno);

    print_box_start('generalbox', 'notice');
    echo '<p>'. $message .'</p>';
    echo '<div class="buttons">';
    print_single_button($linkyes, $optionsyes, get_string('yes'), $methodyes, $CFG->framename);
    print_single_button($linkno,  $optionsno,  get_string('no'),  $methodno,  $CFG->framename);
    echo '</div>';
    print_box_end();
}

/**
 * Redirects the user to another page, after printing a notice
 *
 * @param string $url The url to take the user to
 * @param string $message The text message to display to the user about the redirect, if any
 * @param string $delay How long before refreshing to the new page at $url?
 * @todo '&' needs to be encoded into '&amp;' for XHTML compliance,
 *      however, this is not true for javascript. Therefore we
 *      first decode all entities in $url (since we cannot rely on)
 *      the correct input) and then encode for where it's needed
 *      echo "<script type='text/javascript'>alert('Redirect $url');</script>";
 */
function redirect($url, $message='', $delay=-1) {
    global $CFG, $THEME, $SESSION, $PAGE;

    if (!empty($CFG->usesid) && !isset($_COOKIE[session_name()])) {
       $url = $SESSION->sid_process_url($url);
    }

    $message = clean_text($message);

    $encodedurl = preg_replace("/\&(?![a-zA-Z0-9#]{1,8};)/", "&amp;", $url);
    $encodedurl = preg_replace('/^.*href="([^"]*)".*$/', "\\1", clean_text('<a href="'.$encodedurl.'" />'));
    $url = str_replace('&amp;', '&', $encodedurl);

/// At developer debug level. Don't redirect if errors have been printed on screen.
/// Currenly only works in PHP 5.2+; we do not want strict PHP5 errors
    $lasterror = error_get_last();
    $error = defined('DEBUGGING_PRINTED') or (!empty($lasterror) && ($lasterror['type'] & DEBUG_DEVELOPER));
    $errorprinted = debugging('', DEBUG_ALL) && $CFG->debugdisplay && $error;
    if ($errorprinted) {
        $message = "<strong>Error output, so disabling automatic redirect.</strong></p><p>" . $message;
    }

    $performanceinfo = '';
    if (defined('MDL_PERF') || (!empty($CFG->perfdebug) and $CFG->perfdebug > 7)) {
        if (defined('MDL_PERFTOLOG') && !function_exists('register_shutdown_function')) {
            $perf = get_performance_info();
            error_log("PERF: " . $perf['txt']);
        }
    }

/// when no message and header printed yet, try to redirect
    if (empty($message) and !$PAGE->headerprinted) {

        // Technically, HTTP/1.1 requires Location: header to contain
        // the absolute path. (In practice browsers accept relative
        // paths - but still, might as well do it properly.)
        // This code turns relative into absolute.
        if (!preg_match('|^[a-z]+:|', $url)) {
            // Get host name http://www.wherever.com
            $hostpart = preg_replace('|^(.*?[^:/])/.*$|', '$1', $CFG->wwwroot);
            if (preg_match('|^/|', $url)) {
                // URLs beginning with / are relative to web server root so we just add them in
                $url = $hostpart.$url;
            } else {
                // URLs not beginning with / are relative to path of current script, so add that on.
                $url = $hostpart.preg_replace('|\?.*$|','',me()).'/../'.$url;
            }
            // Replace all ..s
            while (true) {
                $newurl = preg_replace('|/(?!\.\.)[^/]*/\.\./|', '/', $url);
                if ($newurl == $url) {
                    break;
                }
                $url = $newurl;
            }
        }

        $delay = 0;
        //try header redirection first
        @header($_SERVER['SERVER_PROTOCOL'] . ' 303 See Other'); //302 might not work for POST requests, 303 is ignored by obsolete clients
        @header('Location: '.$url);
        //another way for older browsers and already sent headers (eg trailing whitespace in config.php)
        echo '<meta http-equiv="refresh" content="'. $delay .'; url='. $encodedurl .'" />';
        print_js_call('document.location.replace', array($url));
        die;
    }

    if ($delay == -1) {
        $delay = 3;  // if no delay specified wait 3 seconds
    }
    if (!$PAGE->headerprinted) {
        // this type of redirect might not be working in some browsers - such as lynx :-(
        print_header('', '', '', '', $errorprinted ? '' : ('<meta http-equiv="refresh" content="'. $delay .'; url='. $encodedurl .'" />'));
        $delay += 3; // double redirect prevention, it was sometimes breaking upgrades before 1.7
    } else {
        print_container_end_all(false, $THEME->open_header_containers);
    }
    echo '<div id="redirect">';
    echo '<div id="message">' . $message . '</div>';
    echo '<div id="continue">( <a href="'. $encodedurl .'">'. get_string('continue') .'</a> )</div>';
    echo '</div>';

    if (!$errorprinted) {
        print_delayed_js_call($delay, 'document.location.replace', array($url));
    }

    $CFG->docroot = false; // to prevent the link to moodle docs from being displayed on redirect page.
    print_footer('none');
    die;
}

/**
 * Print a bold message in an optional color.
 *
 * @param string $message The message to print out
 * @param string $style Optional style to display message text in
 * @param string $align Alignment option
 * @param bool $return whether to return an output string or echo now
 */
function notify($message, $style='notifyproblem', $align='center', $return=false) {
    global $DB;

    if ($style == 'green') {
        $style = 'notifysuccess';  // backward compatible with old color system
    }

    $message = clean_text($message);
    if (!CLI_SCRIPT) {
        $output = '<div class="'.$style.'" style="text-align:'. $align .'">'. $message .'</div>'."\n";
    } else {
        if ($style === 'notifysuccess') {
            $output = '++'.$message.'++';
        } else {
            $output = '!!'.$message.'!!';
        }
    }

    if ($return) {
        return $output;
    }

    if (!CLI_SCRIPT) {
        echo $output;
    } else {
        console_write($output."\n", '', false);
    }
}


/**
 * Given an email address, this function will return an obfuscated version of it
 *
 * @param string $email The email address to obfuscate
 * @return string
 */
 function obfuscate_email($email) {

    $i = 0;
    $length = strlen($email);
    $obfuscated = '';
    while ($i < $length) {
        if (rand(0,2)) {
            $obfuscated.='%'.dechex(ord($email{$i}));
        } else {
            $obfuscated.=$email{$i};
        }
        $i++;
    }
    return $obfuscated;
}

/**
 * This function takes some text and replaces about half of the characters
 * with HTML entity equivalents.   Return string is obviously longer.
 *
 * @param string $plaintext The text to be obfuscated
 * @return string
 */
function obfuscate_text($plaintext) {

    $i=0;
    $length = strlen($plaintext);
    $obfuscated='';
    $prev_obfuscated = false;
    while ($i < $length) {
        $c = ord($plaintext{$i});
        $numerical = ($c >= ord('0')) && ($c <= ord('9'));
        if ($prev_obfuscated and $numerical ) {
            $obfuscated.='&#'.ord($plaintext{$i}).';';
        } else if (rand(0,2)) {
            $obfuscated.='&#'.ord($plaintext{$i}).';';
            $prev_obfuscated = true;
        } else {
            $obfuscated.=$plaintext{$i};
            $prev_obfuscated = false;
        }
      $i++;
    }
    return $obfuscated;
}

/**
 * This function uses the {@link obfuscate_email()} and {@link obfuscate_text()}
 * to generate a fully obfuscated email link, ready to use.
 *
 * @param string $email The email address to display
 * @param string $label The text to dispalyed as hyperlink to $email
 * @param boolean $dimmed If true then use css class 'dimmed' for hyperlink
 * @return string
 */
function obfuscate_mailto($email, $label='', $dimmed=false) {

    if (empty($label)) {
        $label = $email;
    }
    if ($dimmed) {
        $title = get_string('emaildisable');
        $dimmed = ' class="dimmed"';
    } else {
        $title = '';
        $dimmed = '';
    }
    return sprintf("<a href=\"%s:%s\" $dimmed title=\"$title\">%s</a>",
                    obfuscate_text('mailto'), obfuscate_email($email),
                    obfuscate_text($label));
}

/**
 * Prints a single paging bar to provide access to other pages  (usually in a search)
 *
 * @param int $totalcount Thetotal number of entries available to be paged through
 * @param int $page The page you are currently viewing
 * @param int $perpage The number of entries that should be shown per page
 * @param mixed $baseurl If this  is a string then it is the url which will be appended with $pagevar, an equals sign and the page number.
 *                          If this is a moodle_url object then the pagevar param will be replaced by the page no, for each page.
 * @param string $pagevar This is the variable name that you use for the page number in your code (ie. 'tablepage', 'blogpage', etc)
 * @param bool $nocurr do not display the current page as a link
 * @param bool $return whether to return an output string or echo now
 * @return bool or string
 */
function print_paging_bar($totalcount, $page, $perpage, $baseurl, $pagevar='page',$nocurr=false, $return=false) {
    $maxdisplay = 18;
    $output = '';

    if ($totalcount > $perpage) {
        $output .= '<div class="paging">';
        $output .= get_string('page') .':';
        if ($page > 0) {
            $pagenum = $page - 1;
            if (!is_a($baseurl, 'moodle_url')){
                $output .= '&nbsp;(<a class="previous" href="'. $baseurl . $pagevar .'='. $pagenum .'">'. get_string('previous') .'</a>)&nbsp;';
            } else {
                $output .= '&nbsp;(<a class="previous" href="'. $baseurl->out(false, array($pagevar => $pagenum)).'">'. get_string('previous') .'</a>)&nbsp;';
            }
        }
        if ($perpage > 0) {
            $lastpage = ceil($totalcount / $perpage);
        } else {
            $lastpage = 1;
        }
        if ($page > 15) {
            $startpage = $page - 10;
            if (!is_a($baseurl, 'moodle_url')){
                $output .= '&nbsp;<a href="'. $baseurl . $pagevar .'=0">1</a>&nbsp;...';
            } else {
                $output .= '&nbsp;<a href="'. $baseurl->out(false, array($pagevar => 0)).'">1</a>&nbsp;...';
            }
        } else {
            $startpage = 0;
        }
        $currpage = $startpage;
        $displaycount = $displaypage = 0;
        while ($displaycount < $maxdisplay and $currpage < $lastpage) {
            $displaypage = $currpage+1;
            if ($page == $currpage && empty($nocurr)) {
                $output .= '&nbsp;&nbsp;'. $displaypage;
            } else {
                if (!is_a($baseurl, 'moodle_url')){
                    $output .= '&nbsp;&nbsp;<a href="'. $baseurl . $pagevar .'='. $currpage .'">'. $displaypage .'</a>';
                } else {
                    $output .= '&nbsp;&nbsp;<a href="'. $baseurl->out(false, array($pagevar => $currpage)).'">'. $displaypage .'</a>';
                }

            }
            $displaycount++;
            $currpage++;
        }
        if ($currpage < $lastpage) {
            $lastpageactual = $lastpage - 1;
            if (!is_a($baseurl, 'moodle_url')){
                $output .= '&nbsp;...<a href="'. $baseurl . $pagevar .'='. $lastpageactual .'">'. $lastpage .'</a>&nbsp;';
            } else {
                $output .= '&nbsp;...<a href="'. $baseurl->out(false, array($pagevar => $lastpageactual)).'">'. $lastpage .'</a>&nbsp;';
            }
        }
        $pagenum = $page + 1;
        if ($pagenum != $displaypage) {
            if (!is_a($baseurl, 'moodle_url')){
                $output .= '&nbsp;&nbsp;(<a class="next" href="'. $baseurl . $pagevar .'='. $pagenum .'">'. get_string('next') .'</a>)';
            } else {
                $output .= '&nbsp;&nbsp;(<a class="next" href="'. $baseurl->out(false, array($pagevar => $pagenum)) .'">'. get_string('next') .'</a>)';
            }
        }
        $output .= '</div>';
    }

    if ($return) {
        return $output;
    }

    echo $output;
    return true;
}

/**
 * This function is used to rebuild the <nolink> tag because some formats (PLAIN and WIKI)
 * will transform it to html entities
 *
 * @param string $text Text to search for nolink tag in
 * @return string
 */
function rebuildnolinktag($text) {

    $text = preg_replace('/&lt;(\/*nolink)&gt;/i','<$1>',$text);

    return $text;
}

/**
 * Prints a nice side block with an optional header.  The content can either
 * be a block of HTML or a list of text with optional icons.
 *
 * @param string $heading HTML for the heading. Can include full HTML or just
 *   plain text - plain text will automatically be enclosed in the appropriate
 *   heading tags.
 * @param string $content HTML for the content
 * @param array $list an alternative to $content, it you want a list of things with optional icons.
 * @param array $icons optional icons for the things in $list.
 * @param string $footer Extra HTML content that gets output at the end, inside a &lt;div class="footer">
 * @param array $attributes an array of attribute => value pairs that are put on the
 * outer div of this block. If there is a class attribute ' sideblock' gets appended to it. If there isn't
 * already a class, class='sideblock' is used.
 * @param string $title Plain text title, as embedded in the $heading.
 * @todo Finish documenting this function. Show example of various attributes, etc.
 */
function print_side_block($heading='', $content='', $list=NULL, $icons=NULL, $footer='', $attributes = array(), $title='') {

    //Accessibility: skip block link, with title-text (or $block_id) to differentiate links.
    static $block_id = 0;
    $block_id++;
    if (empty($heading)) {
        $skip_text = get_string('skipblock', 'access').' '.$block_id;
    }
    else {
        $skip_text = get_string('skipa', 'access', strip_tags($title));
    }
    $skip_link = '<a href="#sb-'.$block_id.'" class="skip-block">'.$skip_text.'</a>';
    $skip_dest = '<span id="sb-'.$block_id.'" class="skip-block-to"></span>';

    if (! empty($heading)) {
        echo $skip_link;
    }
    //ELSE: a single link on a page "Skip block 4" is too confusing - ignore.

    print_side_block_start($heading, $attributes);

    // The content.
    if ($content) {
        echo $content;
    } else {
        if ($list) {
            $row = 0;
            //Accessibility: replaced unnecessary table with list, see themes/standard/styles_layout.css
            echo "\n<ul class='list'>\n";
            foreach ($list as $key => $string) {
                echo '<li class="r'. $row .'">';
                if ($icons) {
                    echo '<div class="icon column c0">'. $icons[$key] .'</div>';
                }
                echo '<div class="column c1">'. $string .'</div>';
                echo "</li>\n";
                $row = $row ? 0:1;
            }
            echo "</ul>\n";
        }
    }

    // Footer, if any.
    if ($footer) {
        echo '<div class="footer">'. $footer .'</div>';
    }

    print_side_block_end($attributes, $title);
    echo $skip_dest;
}

/**
 * Starts a nice side block with an optional header.
 *
 * @param string $heading HTML for the heading. Can include full HTML or just
 *   plain text - plain text will automatically be enclosed in the appropriate
 *   heading tags.
 * @param array $attributes ?
 * @todo Finish documenting this function
 */
function print_side_block_start($heading='', $attributes = array()) {

    global $CFG, $THEME;

    // If there are no special attributes, give a default CSS class
    if (empty($attributes) || !is_array($attributes)) {
        $attributes = array('class' => 'sideblock');

    } else if(!isset($attributes['class'])) {
        $attributes['class'] = 'sideblock';

    } else if(!strpos($attributes['class'], 'sideblock')) {
        $attributes['class'] .= ' sideblock';
    }

    // OK, the class is surely there and in addition to anything
    // else, it's tagged as a sideblock

    /*

    // IE misery: if I do it this way, blocks which start hidden cannot be "unhidden"

    // If there is a cookie to hide this thing, start it hidden
    if (!empty($attributes['id']) && isset($_COOKIE['hide:'.$attributes['id']])) {
        $attributes['class'] = 'hidden '.$attributes['class'];
    }
    */

    $attrtext = '';
    foreach ($attributes as $attr => $val) {
        $attrtext .= ' '.$attr.'="'.$val.'"';
    }

    echo '<div '.$attrtext.'>';

    if (!empty($THEME->customcorners)) {
        echo '<div class="wrap">'."\n";
    }
    if ($heading) {
        // Some callers pass in complete html for the heading, which may include
        // complicated things such as the 'hide block' button; some just pass in
        // text. If they only pass in plain text i.e. it doesn't include a
        // <div>, then we add in standard tags that make it look like a normal
        // page block including the h2 for accessibility
        if(strpos($heading,'</div>')===false) {
            $heading='<div class="title"><h2>'.$heading.'</h2></div>';
        }

        echo '<div class="header">';
        if (!empty($THEME->customcorners)) {
            echo '<div class="bt"><div>&nbsp;</div></div>';
            echo '<div class="i1"><div class="i2">';
            echo '<div class="i3">';
        }
        echo $heading;
        if (!empty($THEME->customcorners)) {
            echo '</div></div></div>';
        }
        echo '</div>';
    } else {
        if (!empty($THEME->customcorners)) {
            echo '<div class="bt"><div>&nbsp;</div></div>';
        }
    }

    if (!empty($THEME->customcorners)) {
        echo '<div class="i1"><div class="i2">';
        echo '<div class="i3">';
    }
    echo '<div class="content">';

}


/**
 * Print table ending tags for a side block box.
 */
function print_side_block_end($attributes = array(), $title='') {
    global $CFG, $THEME;

    echo '</div>';

    if (!empty($THEME->customcorners)) {
        echo '</div></div></div><div class="bb"><div>&nbsp;</div></div></div>';
    }

    echo '</div>';

    $strshow = addslashes_js(get_string('showblocka', 'access', strip_tags($title)));
    $strhide = addslashes_js(get_string('hideblocka', 'access', strip_tags($title)));

    // IE workaround: if I do it THIS way, it works! WTF?
    if (!empty($CFG->allowuserblockhiding) && isset($attributes['id'])) {
        echo '<script type="text/javascript">'."\n//<![CDATA[\n".'elementCookieHide("'.$attributes['id'].
             '","'.$strshow.'","'.$strhide."\");\n//]]>\n".'</script>';
    }

}

/**
 * @deprecated since Moodle 2.0 - use $PAGE->pagetype instead of the .
 * @param string $getid used to return $PAGE->pagetype.
 * @param string $getclass used to return $PAGE->legacyclass.
 */
function page_id_and_class(&$getid, &$getclass) {
    global $PAGE;
    debugging('Call to deprecated function page_id_and_class. Please use $PAGE->pagetype instead.', DEBUG_DEVELOPER);
    $getid = $PAGE->pagetype;
    $getclass = $PAGE->legacyclass;
}

/**
 * Prints a maintenance message from /maintenance.html
 */
function print_maintenance_message () {
    global $CFG, $SITE;

    $PAGE->set_pagetype('maintenance-message');
    print_header(strip_tags($SITE->fullname), $SITE->fullname, 'home');
    print_box_start();
    print_heading(get_string('sitemaintenance', 'admin'));
    @include($CFG->dataroot.'/1/maintenance.html');
    print_box_end();
    print_footer();
}

/**
 * Adjust the list of allowed tags based on $CFG->allowobjectembed and user roles (admin)
 */
function adjust_allowed_tags() {

    global $CFG, $ALLOWED_TAGS;

    if (!empty($CFG->allowobjectembed)) {
        $ALLOWED_TAGS .= '<embed><object>';
    }
}

/// Some code to print tabs

/// A class for tabs
class tabobject {
    var $id;
    var $link;
    var $text;
    var $linkedwhenselected;

    /// A constructor just because I like constructors
    function tabobject ($id, $link='', $text='', $title='', $linkedwhenselected=false) {
        $this->id   = $id;
        $this->link = $link;
        $this->text = $text;
        $this->title = $title ? $title : $text;
        $this->linkedwhenselected = $linkedwhenselected;
    }
}



/**
 * Returns a string containing a nested list, suitable for formatting into tabs with CSS.
 *
 * @param array $tabrows An array of rows where each row is an array of tab objects
 * @param string $selected  The id of the selected tab (whatever row it's on)
 * @param array  $inactive  An array of ids of inactive tabs that are not selectable.
 * @param array  $activated An array of ids of other tabs that are currently activated
**/
function print_tabs($tabrows, $selected=NULL, $inactive=NULL, $activated=NULL, $return=false) {
    global $CFG;

/// $inactive must be an array
    if (!is_array($inactive)) {
        $inactive = array();
    }

/// $activated must be an array
    if (!is_array($activated)) {
        $activated = array();
    }

/// Convert the tab rows into a tree that's easier to process
    if (!$tree = convert_tabrows_to_tree($tabrows, $selected, $inactive, $activated)) {
        return false;
    }

/// Print out the current tree of tabs (this function is recursive)

    $output = convert_tree_to_html($tree);

    $output = "\n\n".'<div class="tabtree">'.$output.'</div><div class="clearer"> </div>'."\n\n";

/// We're done!

    if ($return) {
        return $output;
    }
    echo $output;
}


function convert_tree_to_html($tree, $row=0) {

    $str = "\n".'<ul class="tabrow'.$row.'">'."\n";

    $first = true;
    $count = count($tree);

    foreach ($tree as $tab) {
        $count--;   // countdown to zero

        $liclass = '';

        if ($first && ($count == 0)) {   // Just one in the row
            $liclass = 'first last';
            $first = false;
        } else if ($first) {
            $liclass = 'first';
            $first = false;
        } else if ($count == 0) {
            $liclass = 'last';
        }

        if ((empty($tab->subtree)) && (!empty($tab->selected))) {
            $liclass .= (empty($liclass)) ? 'onerow' : ' onerow';
        }

        if ($tab->inactive || $tab->active || $tab->selected) {
            if ($tab->selected) {
                $liclass .= (empty($liclass)) ? 'here selected' : ' here selected';
            } else if ($tab->active) {
                $liclass .= (empty($liclass)) ? 'here active' : ' here active';
            }
        }

        $str .= (!empty($liclass)) ? '<li class="'.$liclass.'">' : '<li>';

        if ($tab->inactive || $tab->active || ($tab->selected && !$tab->linkedwhenselected)) {
            // The a tag is used for styling
            $str .= '<a class="nolink"><span>'.$tab->text.'</span></a>';
        } else {
            $str .= '<a href="'.$tab->link.'" title="'.$tab->title.'"><span>'.$tab->text.'</span></a>';
        }

        if (!empty($tab->subtree)) {
            $str .= convert_tree_to_html($tab->subtree, $row+1);
        } else if ($tab->selected) {
            $str .= '<div class="tabrow'.($row+1).' empty">&nbsp;</div>'."\n";
        }

        $str .= ' </li>'."\n";
    }
    $str .= '</ul>'."\n";

    return $str;
}


function convert_tabrows_to_tree($tabrows, $selected, $inactive, $activated) {

/// Work backwards through the rows (bottom to top) collecting the tree as we go.

    $tabrows = array_reverse($tabrows);

    $subtree = array();

    foreach ($tabrows as $row) {
        $tree = array();

        foreach ($row as $tab) {
            $tab->inactive = in_array((string)$tab->id, $inactive);
            $tab->active = in_array((string)$tab->id, $activated);
            $tab->selected = (string)$tab->id == $selected;

            if ($tab->active || $tab->selected) {
                if ($subtree) {
                    $tab->subtree = $subtree;
                }
            }
            $tree[] = $tab;
        }
        $subtree = $tree;
    }

    return $subtree;
}


/**
 * Returns a string containing a link to the user documentation for the current
 * page. Also contains an icon by default. Shown to teachers and admin only.
 *
 * @param string $text The text to be displayed for the link
 * @param string $iconpath The path to the icon to be displayed
 */
function page_doc_link($text='', $iconpath='') {
    global $CFG, $PAGE;

    if (empty($CFG->docroot) || empty($CFG->rolesactive)) {
        return '';
    }
    if (!has_capability('moodle/site:doclinks', $PAGE->context)) {
        return '';
    }

    $path = $PAGE->docspath;
    if (!$path) {
        return '';
    }
    return doc_link($path, $text, $iconpath);
}

/**
 * @param string $path the end of the URL.
 * @return The MoodleDocs URL in the user's language. for example http://docs.moodle.org/en/$path
 */
function get_docs_url($path) {
    global $CFG;
    return $CFG->docroot . '/' . str_replace('_utf8', '', current_language()) . '/' . $path;
}

/**
 * Returns a string containing a link to the user documentation.
 * Also contains an icon by default. Shown to teachers and admin only.
 *
 * @param string $path The page link after doc root and language, no leading slash.
 * @param string $text The text to be displayed for the link
 * @param string $iconpath The path to the icon to be displayed
 */
function doc_link($path='', $text='', $iconpath='') {
    global $CFG;

    if (empty($CFG->docroot)) {
        return '';
    }

    $url = get_docs_url($path);

    $target = '';
    if (!empty($CFG->doctonewwindow)) {
        $target = " onclick=\"window.open('$url'); return false;\"";
    }

    $str = "<a href=\"$url\"$target>";

    if (empty($iconpath)) {
        $iconpath = $CFG->httpswwwroot . '/pix/docs.gif';
    }

    // alt left blank intentionally to prevent repetition in screenreaders
    $str .= '<img class="iconhelp" src="' .$iconpath. '" alt="" />' .$text. '</a>';

    return $str;
}


/**
 * Returns true if the current site debugging settings are equal or above specified level.
 * If passed a parameter it will emit a debugging notice similar to trigger_error(). The
 * routing of notices is controlled by $CFG->debugdisplay
 * eg use like this:
 *
 * 1)  debugging('a normal debug notice');
 * 2)  debugging('something really picky', DEBUG_ALL);
 * 3)  debugging('annoying debug message only for develpers', DEBUG_DEVELOPER);
 * 4)  if (debugging()) { perform extra debugging operations (do not use print or echo) }
 *
 * In code blocks controlled by debugging() (such as example 4)
 * any output should be routed via debugging() itself, or the lower-level
 * trigger_error() or error_log(). Using echo or print will break XHTML
 * JS and HTTP headers.
 *
 *
 * @param string $message a message to print
 * @param int $level the level at which this debugging statement should show
 * @param array $backtrace use different backtrace
 * @return bool
 */
function debugging($message='', $level=DEBUG_NORMAL, $backtrace=null) {

    global $CFG;

    if (empty($CFG->debug)) {
        return false;
    }

    if ($CFG->debug >= $level) {
        if ($message) {
            if (!$backtrace) {
                $backtrace = debug_backtrace();
            }
            $from = print_backtrace($backtrace, true);
            if (!isset($CFG->debugdisplay)) {
                $CFG->debugdisplay = ini_get_bool('display_errors');
            }
            if ($CFG->debugdisplay) {
                if (!defined('DEBUGGING_PRINTED')) {
                    define('DEBUGGING_PRINTED', 1); // indicates we have printed something
                }
                notify($message . $from, 'notifytiny');
            } else {
                trigger_error($message . $from, E_USER_NOTICE);
            }
        }
        return true;
    }
    return false;
}

/**
 * Prints formatted backtrace
 * @param backtrace array
 * @param return return as string or print
 * @return mixed
 */
function print_backtrace($callers, $return=false) {
    global $CFG;

    if (empty($callers)) {
        if ($return) {
            return '';
        } else {
            return;
        }
    }

    $from = '<ul style="text-align: left">';
    foreach ($callers as $caller) {
        if (!isset($caller['line'])) {
            $caller['line'] = '?'; // probably call_user_func()
        }
        if (!isset($caller['file'])) {
            $caller['file'] = $CFG->dirroot.'/unknownfile'; // probably call_user_func()
        }
        $from .= '<li>line ' . $caller['line'] . ' of ' . substr($caller['file'], strlen($CFG->dirroot) + 1);
        if (isset($caller['function'])) {
            $from .= ': call to ';
            if (isset($caller['class'])) {
                $from .= $caller['class'] . $caller['type'];
            }
            $from .= $caller['function'] . '()';
        } else if (isset($caller['exception'])) {
            $from .= ': '.$caller['exception'].' thrown';
        }
        $from .= '</li>';
    }
    $from .= '</ul>';

    if ($return) {
        return $from;
    } else {
        echo $from;
    }
}

/**
 * Disable debug messages from debugging(), while keeping PHP error reporting level as is.
 */
function disable_debugging() {
    global $CFG;
    $CFG->debug = $CFG->debug | 0x80000000; // switch the sign bit in integer number ;-)
}


/**
 *  Returns string to add a frame attribute, if required
 */
function frametarget() {
    global $CFG;

    if (empty($CFG->framename) or ($CFG->framename == '_top')) {
        return '';
    } else {
        return ' target="'.$CFG->framename.'" ';
    }
}

/**
* Outputs a HTML comment to the browser. This is used for those hard-to-debug
* pages that use bits from many different files in very confusing ways (e.g. blocks).
* @usage print_location_comment(__FILE__, __LINE__);
* @param string $file
* @param integer $line
* @param boolean $return Whether to return or print the comment
* @return mixed Void unless true given as third parameter
*/
function print_location_comment($file, $line, $return = false)
{
    if ($return) {
        return "<!-- $file at line $line -->\n";
    } else {
        echo "<!-- $file at line $line -->\n";
    }
}


/**
 * Returns an image of an up or down arrow, used for column sorting. To avoid unnecessary DB accesses, please
 * provide this function with the language strings for sortasc and sortdesc.
 * If no sort string is associated with the direction, an arrow with no alt text will be printed/returned.
 * @param string $direction 'up' or 'down'
 * @param string $strsort The language string used for the alt attribute of this image
 * @param bool $return Whether to print directly or return the html string
 * @return string HTML for the image
 *
 * TODO See if this isn't already defined somewhere. If not, move this to weblib
 */
function print_arrow($direction='up', $strsort=null, $return=false) {
    global $CFG;

    if (!in_array($direction, array('up', 'down', 'right', 'left', 'move'))) {
        return null;
    }

    $return = null;

    switch ($direction) {
        case 'up':
            $sortdir = 'asc';
            break;
        case 'down':
            $sortdir = 'desc';
            break;
        case 'move':
            $sortdir = 'asc';
            break;
        default:
            $sortdir = null;
            break;
    }

    // Prepare language string
    $strsort = '';
    if (empty($strsort) && !empty($sortdir)) {
        $strsort  = get_string('sort' . $sortdir, 'grades');
    }

    $return = ' <img src="'.$CFG->pixpath.'/t/' . $direction . '.gif" alt="'.$strsort.'" /> ';

    if ($return) {
        return $return;
    } else {
        echo $return;
    }
}

/**
 * Returns boolean true if the current language is right-to-left (Hebrew, Arabic etc)
 *
 */
function right_to_left() {
    static $result;

    if (isset($result)) {
        return $result;
    }
    return $result = (get_string('thisdirection') == 'rtl');
}


/**
 * Returns swapped left<=>right if in RTL environment.
 * part of RTL support
 *
 * @param string $align align to check
 * @return string
 */
function fix_align_rtl($align) {
    if (!right_to_left()) {
        return $align;
    }
    if ($align=='left')  { return 'right'; }
    if ($align=='right') { return 'left'; }
    return $align;
}


/**
 * Returns true if the page is displayed in a popup window.
 * Gets the information from the URL parameter inpopup.
 *
 * @return boolean
 *
 * TODO Use a central function to create the popup calls allover Moodle and
 * TODO In the moment only works with resources and probably questions.
 */
function is_in_popup() {
    $inpopup = optional_param('inpopup', '', PARAM_BOOL);

    return ($inpopup);
}

//=========================================================================//
/**
 * Write to standard out and error with exit in error.
 *
 * @param standard out/err $stream
 * @param string  $identifier
 * @param name of module $module
 */
function console_write($identifier, $module='install', $use_string_lib=true) {
    if (!isset($_SERVER['REMOTE_ADDR'])) {
        // real CLI script
        if ($use_string_lib) {
            fwrite(STDOUT, get_string($identifier, $module));
        } else {
            fwrite(STDOUT, $identifier);
        }
    } else {
        // emulated cli script - something like cron
        if ($use_string_lib) {
            echo get_string($identifier, $module);
        } else {
            echo $identifier;
        }
    }
}

//=========================================================================//
/**
 * Write to standard out and error with exit in error.
 *
 * @param standard out/err $stream
 * @param string  $identifier
 * @param name of module $module
 */
function console_write_error($identifier, $module='install', $use_string_lib=true) {
    if (!isset($_SERVER['REMOTE_ADDR'])) {
        // real CLI script
        if ($use_string_lib) {
            fwrite(STDERR, get_string($identifier, $module));
        } else {
            fwrite(STDERR, $identifier);
        }
        fwrite(STDERR, "\n\n".get_string('aborting', $module)."\n\n");
    } else {
        // emulated cli script - something like cron
        if ($use_string_lib) {
            echo get_string($identifier, $module);
        } else {
            echo $identifier;
        }
        echo "\n\n".get_string('aborting', $module)."\n\n";
    }

    die; die; die;
}

/**
 * To use this class.
 * - construct
 * - call create (or use the 3rd param to the constructor)
 * - call update or update_full repeatedly
 * - 
 */
class progress_bar {
    private $html_id;
    private $percent;
    private $width;
    private $clr;
    private $lastcall;
    private $time_start;
    private $minimum_time = 2; //min time between updates.
    function __construct($html_id = 'pid', $width = 500, $autostart = false){
        $this->html_id  = $html_id;
        $this->clr      = new stdClass;
        $this->clr->done    = 'green';
        $this->clr->process = '#FFCC66';
        $this->width = $width;
        $this->restart();
        if($autostart){
            $this->create();
        }
    }
    /**
      * set progress bar color, call before $this->create
      * Usage:
      *     $clr->done = 'red';
      *     $clr->process = 'blue';
      *     $pb->setclr($clr);
      *     $pb->create();
      *     ......
      *
      * @param $clr object
      */
    function setclr($clr){
        foreach($clr as $n=>$v) {
            $this->clr->$n = $v;
        }
    }
    /**
      * Create a new progress bar, this function will output
      * html.
      *
      */
    function create(){
            flush();
            $this->lastcall->pt = 0;
            $this->lastcall->time = microtime(true);
            $htmlcode = <<<EOT
            <script type="text/javascript">
            Number.prototype.fixed=function(n){
                with(Math)
                    return round(Number(this)*pow(10,n))/pow(10,n);
            }
            function up_{$this->html_id} (id, width, pt, msg, es){
                percent = pt*100;
                document.getElementById("status_"+id).innerHTML = msg;
                document.getElementById("pt_"+id).innerHTML =
                    percent.fixed(2) + '%';
                if(percent == 100) {
                    document.getElementById("progress_"+id).style.background
                        = "{$this->clr->done}";
                    document.getElementById("time_"+id).style.display
                            = "none";
                } else {
                    document.getElementById("progress_"+id).style.background
                        = "{$this->clr->process}";
                    if (es == Infinity){
                        document.getElementById("time_"+id).innerHTML =
                            "Initializing...";
                    }else {
                        document.getElementById("time_"+id).innerHTML =
                            es.fixed(2)+" sec";
                        document.getElementById("time_"+id).style.display
                            = "block";
                    }
                }
                document.getElementById("progress_"+id).style.width
                    = width + "px";

            }

            </script>
            <div style="text-align:center;width:{$this->width}px;clear:both;padding:0;margin:0 auto;">
                <h2 id="status_{$this->html_id}" style="text-align: center;margin:0 auto"></h2>
                <p id="time_{$this->html_id}"></p>
                <div id="bar_{$this->html_id}" style="border-style:solid;border-width:1px;width:500px;height:50px;">
                    <div id="progress_{$this->html_id}"
                    style="text-align:center;background:{$this->clr->process};width:4px;border:1px
                    solid gray;height:38px; padding-top:10px;">&nbsp;<span id="pt_{$this->html_id}"></span>
                    </div>
                </div>
            </div>
EOT;
            echo $htmlcode;
            flush();
    }
    function _update($percent, $msg, $es){
        if(empty($this->time_start)){
            $this->time_start = microtime(true);
        }
        $this->percent = $percent;
        $this->lastcall->time = microtime(true);
        $this->lastcall->pt   = $percent;
        $w = $this->percent * $this->width;
        if ($es === null){
            $es = "Infinity";
        }
        echo "<script type=\"text/javascript\">up_".$this->html_id."('$this->html_id', '$w', '$this->percent', '$msg', $es);</script>";
        flush();
    }
    /**
      * estimate time
      *
      * @param $curtime int the time call this function
      * @param $percent int
      */
    function estimate($curtime, $pt){
        $consume = $curtime - $this->time_start;
        $one = $curtime - $this->lastcall->time;
        $this->percent = $pt;
        $percent = $pt - $this->lastcall->pt;
        if ($percent != 0) {
            $left = ($one / $percent) - $consume;
        } else {
            return null;
        }
        if($left < 0) {
            return 0;
        } else {
            return $left;
        }
    }
    /**
      * Update progress bar according percent
      *
      * @param $percent int from 1-100
      * @param $msg     string the message needed to be shown
      */
    function update_full($percent, $msg){
        $percent = max(min($percent, 100), 0);
        if ($percent != 100 && ($this->lastcall->time + $this->minimum_time) > microtime(true)){
            return;
        }
        $this->_update($percent/100, $msg);
    }
    /**
      * Update progress bar according the nubmer of tasks
      *
      * @param $cur   int       current task number
      * @param $total int       total task number
      * @param $msg   string    message
      */
    function update($cur, $total, $msg){
        $cur = max($cur, 0);
        if ($cur >= $total){
            $percent = 1;
        } else {
            $percent = $cur / $total;
        }
        /**
        if ($percent != 1 && ($this->lastcall->time + $this->minimum_time) > microtime(true)){
            return;
        }
        */
        $es = $this->estimate(microtime(true), $percent);
        $this->_update($percent, $msg, $es);
    }
    /**
     * Restart the progress bar.
     */
    function restart(){
        $this->percent  = 0;
        $this->lastcall = new stdClass;
        $this->lastcall->pt = 0;
        $this->lastcall->time = microtime(true);
        $this->time_start  = 0;
    }
}

/**
 * Use this class from long operations where you want to output occasional information about
 * what is going on, but don't know if, or in what format, the output should be.
 */
abstract class moodle_progress_trace {
    /**
     * Ouput an progress message in whatever format.
     * @param string $message the message to output.
     * @param integer $depth indent depth for this message.
     */
    abstract public function output($message, $depth = 0);

    /**
     * Called when the processing is finished.
     */
    public function finished() {
        
    }
}

/**
 * This subclass of moodle_progress_trace does not ouput anything.
 */
class null_progress_trace extends moodle_progress_trace {
    public function output($message, $depth = 0) {
    }
}

/**
 * This subclass of moodle_progress_trace outputs to plain text.
 */
class text_progress_trace extends moodle_progress_trace {
    public function output($message, $depth = 0) {
        echo str_repeat('  ', $depth), $message, "\n";
        flush();
    }
}

/**
 * This subclass of moodle_progress_trace outputs as HTML.
 */
class html_progress_trace extends moodle_progress_trace {
    public function output($message, $depth = 0) {
        echo '<p>', str_repeat('&#160;&#160;', $depth), htmlspecialchars($message), "</p>\n";
        flush();
    }
}

class html_list_progress_trace extends moodle_progress_trace {
    protected $currentdepth = -1;

    public function output($message, $depth = 0) {
        $samedepth = true;
        while ($this->currentdepth > $depth) {
            echo "</li>\n</ul>\n";
            $this->currentdepth -= 1;
            if ($this->currentdepth == $depth) {
                echo '<li>';
            }
            $samedepth = false;
        }
        while ($this->currentdepth < $depth) {
            echo "<ul>\n<li>";
            $this->currentdepth += 1;
            $samedepth = false;
        }
        if ($samedepth) {
            echo "</li>\n<li>";
        }
        echo htmlspecialchars($message);
        flush();
    }

    public function finished() {
        while ($this->currentdepth >= 0) {
            echo "</li>\n</ul>\n";
            $this->currentdepth -= 1;
        }
    }
}

/**
 * Return the authentication plugin title
 * @param string $authtype plugin type
 * @return string
 */
function auth_get_plugin_title ($authtype) {
    $authtitle = get_string("auth_{$authtype}title", "auth");
    if ($authtitle == "[[auth_{$authtype}title]]") {
        $authtitle = get_string("auth_{$authtype}title", "auth_{$authtype}");
    }
    return $authtitle;
}

// vim:autoindent:expandtab:shiftwidth=4:tabstop=4:tw=140:
?>
