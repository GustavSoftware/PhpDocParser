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

use Gustav\Lexer\Token;

/**
 * An exception to be thrown while parsing the PHPDoc comments.
 *
 * @author Chris Köcher <ckone@fieselschweif.de>
 * @link   https://gustav.fieselschweif.de
 * @since  1.0.0
 */
class ParserException extends \Exception
{
    /**
     * Some possible error codes
     *
     * @var int
     */
    public const UNEXPECTED_TOKEN = 1;
    public const UNEXPECTED_END = 2;
    public const INVALID_EMAIL = 3;
    public const INVALID_LINK = 4;
    public const INVALID_TYPE = 5;
    public const INVALID_VAR = 6;
    public const INVALID_VERSION = 7;
    public const INVALID_METHOD = 8;
    public const INVALID_ELEMENT = 9;
    public const INVALID_FILE = 10;
    public const INTERNAL_ELEMENT = 11;

    /**
     * Creates an exception if the parser detects a token which was not expected at this position.
     *
     * @param \Gustav\Lexer\Token $token
     *   The token detected by the parser
     * @param string $string
     *   The PHPDoc comment
     * @param \Exception|null $previous
     *   Previous exception
     * @return ParserException
     *   The exception
     */
    public static function unexpectedToken(Token $token, string $string, \Exception $previous = null): self
    {
        return new self(
            "unexpected token \"{$token->getValue()}\" in \"{$string}\" on position {$token->getPosition()}",
            self::UNEXPECTED_TOKEN,
            $previous
        );
    }

    /**
     * Creates an exception if the parser detects an unexpected end of the PHPDoc comment.
     *
     * @param string $string
     *   The PHPDoc comment
     * @param \Exception|null $previous
     *   Previous exception
     * @return ParserException
     *   The exception
     */
    public static function unexpectedEnd(string $string, \Exception $previous = null): self
    {
        return new self("unexpected end in \"{$string}\"", self::UNEXPECTED_END, $previous);
    }

    /**
     * Creates an exception if the parser detects an invalid email address in the author-tag.
     *
     * @param string $email
     *   The Email string
     * @param string $string
     *   The PHPDoc comment
     * @param \Exception|null $previous
     *   Previous exception
     * @return ParserException
     *   The exception
     */
    public static function invalidEmail(string $email, string $string, \Exception $previous = null): self
    {
        return new self("invalid email \"{$email}\" in \"{$string}\"", self::INVALID_EMAIL, $previous);
    }

    /**
     * Creates an exception if the parser detects an invalid link in some tag.
     *
     * @param string $link
     *   The link string
     * @param string $string
     *   The PHPDoc comment
     * @param \Exception|null $previous
     *   Previous exception
     * @return ParserException
     *   The exception
     */
    public static function invalidLink(string $link, string $string, \Exception $previous = null): self
    {
        return new self("invalid link \"{$link}\" in \"{$string}\"", self::INVALID_LINK, $previous);
    }

    /**
     * Creates an exception if the parser detects an invalid type in  some tag.
     *
     * @param string $type
     *   The type string
     * @param string $string
     *   The PHPDoc comment
     * @param \Exception|null $previous
     *   Previous exception
     * @return ParserException
     *   The exception
     */
    public static function invalidType(string $type, string $string, \Exception $previous = null): self
    {
        return new self("invalid type \"{$type}\" in \"{$string}\"", self::INVALID_TYPE, $previous);
    }

    /**
     * Creates an exception if the parser detects an invalid variable name in some tag.
     *
     * @param string $var
     *   The variable name
     * @param string $string
     *   The PHPDoc comment
     * @param \Exception|null $previous
     *   Previous exception
     * @return ParserException
     *   The exception
     */
    public static function invalidVariable(string $var, string $string, \Exception $previous = null): self
    {
        return new self("invalid variable \"{$var}\" in \"{$string}\"", self::INVALID_VAR, $previous);
    }

    /**
     * Creates an exception if the parser detects an invalid version in some tag.
     *
     * @param string $version
     *   The version name
     * @param string $string
     *   The PHPDoc comment
     * @param \Exception|null $previous
     *   Previous exception
     * @return ParserException
     *   The exception
     */
    public static function invalidVersion(string $version, string $string, \Exception $previous = null): self
    {
        return new self(
            "invalid version \"{$version}\" in \"{$string}\"", self::INVALID_VERSION, $previous
        );
    }

    /**
     * Creates an exception if the parser detects an invalid method name in method-tag.
     *
     * @param string $method
     *   The method name
     * @param string $string
     *   The PHPDoc comment
     * @param \Exception|null $previous
     *   Previous exception
     * @return ParserException
     *   The exception
     */
    public static function invalidMethod(string $method, string $string, \Exception $previous = null): self
    {
        return new self(
            "invalid method name \"{$method}\" in \"{$string}\"", self::INVALID_METHOD, $previous
        );
    }

    /**
     * Creates an exception if the parser detects an invalid element name in some tag.
     *
     * @param string $name
     *   The element name
     * @param string $string
     *   The PHPDoc comment
     * @param \Exception|null $previous
     *   Previous exception
     * @return ParserException
     *   The exception
     */
    public static function invalidElement(string $name, string $string, \Exception $previous = null): self
    {
        return new self(
            "invalid element name \"{$name}\" in \"{$string}\"", self::INVALID_ELEMENT, $previous
        );
    }

    /**
     * Creates an exception if the parser detects an invalid file name in uses-tag.
     *
     * @param string $file
     *   The file name
     * @param string $string
     *   The PHPDoc comment
     * @param \Exception|null $previous
     *   Previous exception
     * @return ParserException
     *   The exception
     */
    public static function invalidFile(string $file, string $string, \Exception $previous = null): self
    {
        return new self(
            "invalid file name \"{$file}\" in \"{$string}\"", self::INVALID_FILE, $previous
        );
    }

    /**
     * Creates an exception if the parser should parse an internal element.
     *
     * @param string $name
     *   The name of the element
     * @param \Exception|null $previous
     *   Previous exception
     * @return ParserException
     *   The exception
     */
    public static function internalElement(string $name, \Exception $previous = null): self
    {
        return new self(
            "cannot parse internal element \"{$name}\"", self::INTERNAL_ELEMENT, $previous
        );
    }
}