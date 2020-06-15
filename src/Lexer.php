<?php
/**
 * Gustav PhpDocParser - A simple parser for PHPDoc comments.
 * Copyright (C) since 2020  Gustav Software
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Gustav\PhpDocParser;

use Gustav\Lexer\ALexer;

/**
 * This is the lexer needed for parsing PHPDoc comments.
 *
 * @internal
 * @author Chris Köcher <ckone@fieselschweif.de>
 * @link   https://gustav.fieselschweif.de
 * @since  1.0.0
 */
class Lexer extends ALexer
{
    /**
     * The possible types of strings to be found in the parsed text.
     *
     * @var int
     */
    public const T_STRING = 1;
    public const T_NEWLINE = 2;
    public const T_WHITESPACE = 3;
    public const T_STAR = 4;
    public const T_SLASH = 5;
    public const T_AT = 6;
    public const T_DOLLAR = 7;
    public const T_LCBRACKET = 8; //curly brackets
    public const T_RCBRACKET = 9;
    public const T_LABRACKET = 10; //angle brackets
    public const T_RABRACKET = 11;
    public const T_LRBRACKET = 12; //round brackets
    public const T_RRBRACKET = 13;
    public const T_LSBRACKET = 14; //square brackets
    public const T_RSBRACKET = 15;
    public const T_DOT = 16;
    public const T_MINUS = 17;
    public const T_PIPE = 18;
    public const T_BACKSLASH = 19;
    public const T_COLON = 20;
    public const T_AMP = 21;
    public const T_COMMA = 22;

    public const T_INTRO = 25;
    public const T_OUTRO = 26;

    public const T_API_TAG = 30;
    public const T_AUTHOR_TAG = 31;
    public const T_COPYRIGHT_TAG = 32;
    public const T_DEPRECATED_TAG = 33;
    public const T_INHERITDOC_TAG = 34;
    public const T_INTERNAL_TAG = 35;
    public const T_LINK_TAG = 36;
    public const T_METHOD_TAG = 37;
    public const T_PACKAGE_TAG = 38;
    public const T_PARAM_TAG = 39;
    public const T_PROPERTY_TAG = 40;
    public const T_PROPERTY_READ_TAG = 41;
    public const T_PROPERTY_WRITE_TAG = 42;
    public const T_RETURN_TAG = 43;
    public const T_SEE_TAG = 44;
    public const T_SINCE_TAG = 45;
    public const T_THROWS_TAG = 46;
    public const T_TODO_TAG = 47;
    public const T_USES_TAG = 48;
    public const T_USED_BY_TAG = 49;
    public const T_VAR_TAG = 50;
    public const T_VERSION_TAG = 51;

    /**
     * @inheritDoc
     */
    protected function _getCatchablePatterns(): array
    {
        return [
            '\p{L}+' //do not split strings
        ];
    }

    /**
     * @inheritDoc
     */
    protected function _getNonCatchablePatterns(): array
    {
        return ['(.)'];
    }

    /**
     * @inheritDoc
     */
    protected function _getType(&$value): int
    {
        if(in_array($value, ["\n", "\r\n", "\n\r"])) { //we ignore a single \r here to avoid double linebreaks
            return self::T_NEWLINE;
        }
        if(ctype_space($value)) {
            return self::T_WHITESPACE;
        }
        switch($value) {
            case "*":
                return self::T_STAR;
            case "/":
                return self::T_SLASH;
            case "@":
                return self::T_AT;
            case "$":
                return self::T_DOLLAR;
            case "{":
                return self::T_LCBRACKET;
            case "}":
                return self::T_RCBRACKET;
            case "<":
                return self::T_LABRACKET;
            case ">":
                return self::T_RABRACKET;
            case "(":
                return self::T_LRBRACKET;
            case ")":
                return self::T_RRBRACKET;
            case "[":
                return self::T_LSBRACKET;
            case "]":
                return self::T_RSBRACKET;
            case ".":
                return self::T_DOT;
            case "-":
                return self::T_MINUS;
            case "|":
                return self::T_PIPE;
            case "\\":
                return self::T_BACKSLASH;
            case ":":
                return self::T_COLON;
            case "&":
                return self::T_AMP;
            case ",":
                return self::T_COMMA;
            default:
                return self::T_STRING;
        }
    }

    /**
     * @inheritDoc
     */
    protected function _getModifiers(): string
    {
        return "iu";
    }
}