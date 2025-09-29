<?php

/**
 * Plain HTML (with some tags stripped)
 */
define('FORMAT_HTML',     '1');   // Plain HTML (with some tags stripped)

/**
 * Plain text (even tags are printed in full)
 */
define('FORMAT_PLAIN',    '2');   // Plain text (even tags are printed in full)

/**
 * Is it necessary to use HTMLPurifier?
 * @private
 * @param string $text
 * @return bool false means html is safe and valid, true means use HTMLPurifier
 */
function is_purify_html_necessary($text) {
    if ($text === '') {
        return false;
    }

    if ($text === (string)((int)$text)) {
        return false;
    }

    if (strpos($text, '&') !== false or preg_match('|<[^pesb/]|', $text)) {
        // we need to normalise entities or other tags except p, em, strong and br present
        return true;
    }

    $altered = htmlspecialchars($text, ENT_NOQUOTES, 'UTF-8', true);
    if ($altered === $text) {
        // no < > or other special chars means this must be safe
        return false;
    }

    // let's try to convert back some safe html tags
    $altered = preg_replace('|&lt;p&gt;(.*?)&lt;/p&gt;|m', '<p>$1</p>', $altered);
    if ($altered === $text) {
        return false;
    }
    $altered = preg_replace('|&lt;em&gt;([^<>]+?)&lt;/em&gt;|m', '<em>$1</em>', $altered);
    if ($altered === $text) {
        return false;
    }
    $altered = preg_replace('|&lt;strong&gt;([^<>]+?)&lt;/strong&gt;|m', '<strong>$1</strong>', $altered);
    if ($altered === $text) {
        return false;
    }
    $altered = str_replace('&lt;br /&gt;', '<br />', $altered);
    if ($altered === $text) {
        return false;
    }

    return true;
}


/**
 * Given raw text (eg typed in by a user), this function cleans it up
 * and removes any nasty tags that could mess up Moodle pages through XSS attacks.
 *
 * The result must be used as a HTML text fragment, this function can not cleanup random
 * parts of html tags such as url or src attributes.
 *
 * NOTE: the format parameter was deprecated because we can safely clean only HTML.
 *
 * @param string $text The text to be cleaned
 * @param int|string $format deprecated parameter, should always contain FORMAT_HTML or FORMAT_MOODLE
 * @param array $options Array of options; currently only option supported is 'allowid' (if true,
 *   does not remove id attributes when cleaning)
 * @return string The cleaned up text
 */
function clean_text($text, $format = FORMAT_HTML, $options = array()) {
    $text = (string)$text;

    if ($format != FORMAT_HTML and $format != FORMAT_HTML) {
        // TODO: we need to standardise cleanup of text when loading it into editor first
        //debugging('clean_text() is designed to work only with html');
    }

    if ($format == FORMAT_PLAIN) {
        return $text;
    }

    if (is_purify_html_necessary($text)) {
        $text = purify_html($text, $options);
    }

    // Originally we tried to neutralise some script events here, it was a wrong approach because
    // it was trivial to work around that (for example using style based XSS exploits).
    // We must not give false sense of security here - all developers MUST understand how to use
    // rawurlencode(), htmlentities(), htmlspecialchars(), p(), s(), moodle_url, html_writer and friends!!!

    return $text;
}


/**
 * Validates an email to make sure it makes sense.
 *
 * @param string $address The email address to validate.
 * @return boolean
 */
function validate_email($address) {

    return (preg_match('#^[-!\#$%&\'*+\\/0-9=?A-Z^_`a-z{|}~]+'.
                 '(\.[-!\#$%&\'*+\\/0-9=?A-Z^_`a-z{|}~]+)*'.
                  '@'.
                  '[-!\#$%&\'*+\\/0-9=?A-Z^_`a-z{|}~]+\.'.
                  '[-!\#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+$#',
                  $address));
}


function required_parameter($parname, $type=PARAM_CLEAN) {

    if (!empty($_POST[$parname])) {       // POST has precedence
        $param = $_POST[$parname];
    } else if (!empty($_GET[$parname])) {
        $param = $_GET[$parname];
    } else {
        error('A required parameter ('.$parname.') was missing');
    }

    return clean_param($param, $type);
}

function error($msg = NULL) {

    echo "<div style='padding: 5px; margin: 5px; background-color: #CCC;'>".$msg."</div>";
    exit();

}

?>