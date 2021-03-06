<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 * moodle_sniffs_functions_functioncallargumentspacingsniff.
 *
 * @package   lib-pear-php-codesniffer-standards-moodle-sniffs-functions
 * @copyright 2008 Nicolas Connault
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * moodle_sniffs_functions_functioncallargumentspacingsniff.
 *
 * Checks that calls to methods and functions are spaced correctly.
 *
 * @copyright 2008 Nicolas Connault
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class moodle_sniffs_functions_functioncallargumentspacingsniff implements php_codesniffer_sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(T_STRING);

    }


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsfile The file being scanned.
     * @param int                  $stackptr  The position of the current token in the
     *                                        stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsfile, $stackptr)
    {
        $tokens = $phpcsfile->gettokens();

        // Skip tokens that are the names of functions or classes
        // within their definitions. For example:
        // function myFunction...
        // "myFunction" is T_STRING but we should skip because it is not a
        // function or method *call*.
        $functionname    = $stackptr;
        $functionKeyword = $phpcsfile->findPrevious(PHP_CodeSniffer_tokens::$emptyTokens, ($stackptr - 1), null, true);
        if ($tokens[$functionKeyword]['code'] === T_FUNCTION || $tokens[$functionKeyword]['code'] === T_CLASS) {
            return;
        }

        // If the next non-whitespace token after the function or method call
        // is not an opening parenthesis then it cant really be a *call*.
        $openBracket = $phpcsfile->findNext(PHP_CodeSniffer_tokens::$emptyTokens, ($functionname + 1), null, true);
        if ($tokens[$openBracket]['code'] !== T_OPEN_PARENTHESIS) {
            return;
        }

        $closeBracket = $tokens[$openBracket]['parenthesis_closer'];

        $nextSeperator = $openBracket;
        while (($nextSeperator = $phpcsfile->findNext(array(T_COMMA, T_VARIABLE), ($nextSeperator + 1), $closeBracket)) !== false) {
            // Make sure the comma or variable belongs directly to this function call,
            // and is not inside a nested function call or array.
            $brackets    = $tokens[$nextSeperator]['nested_parenthesis'];
            $lastBracket = array_pop($brackets);
            if ($lastBracket !== $closeBracket) {
                continue;
            }

            if ($tokens[$nextSeperator]['code'] === T_COMMA) {
                if ($tokens[($nextSeperator - 1)]['code'] === T_WHITESPACE) {
                    $error = 'Space found before comma in function call';
                    $phpcsfile->addwarning($error, $stackptr);
                }

                if ($tokens[($nextSeperator + 1)]['code'] !== T_WHITESPACE) {
                    $error = 'No space found after comma in function call';
                    $phpcsfile->addwarning($error, $stackptr);
                } else {
                    // If there is a newline in the space, then the must be formatting
                    // each argument on a newline, which is valid, so ignore it.
                    if (strpos($tokens[($nextSeperator + 1)]['content'], $phpcsfile->eolChar) === false) {
                        $space = strlen($tokens[($nextSeperator + 1)]['content']);
                        if ($space > 1) {
                            $error  = 'Expected 1 space after comma in function call; ';
                            $error .= $space.' found';
                            $phpcsfile->addwarning($error, $stackptr);
                        }
                    }
                }
            } else {
                // token is a variable.
                $nexttoken = $phpcsfile->findNext(PHP_CodeSniffer_tokens::$emptyTokens, ($nextSeperator + 1), $closeBracket, true);
                if ($nexttoken !== false) {
                    if ($tokens[$nexttoken]['code'] === T_EQUAL) {
                        if (($tokens[($nexttoken - 1)]['code']) !== T_WHITESPACE) {
                            $error = 'Expected 1 space before = sign of default value';
                            $phpcsfile->addwarning($error, $stackptr);
                        }

                        if ($tokens[($nexttoken + 1)]['code'] !== T_WHITESPACE) {
                            $error = 'Expected 1 space after = sign of default value';
                            $phpcsfile->addwarning($error, $stackptr);
                        }
                    }
                }
            }
        }

    }


}

?>
