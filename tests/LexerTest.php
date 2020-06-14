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

namespace Gustav\PhpDocParser\Tests;

use Gustav\PhpDocParser\Lexer;
use PHPUnit\Framework\TestCase;

class LexerTest extends TestCase
{
    private string $_string = '  /**
 * This is some crazy text.
 * Test-test {@foo bar::baz()}
 */';

    private array $_tokens = [
        [Lexer::T_WHITESPACE, " "],
        [Lexer::T_WHITESPACE, " "],
        [Lexer::T_SLASH, "/"],
        [Lexer::T_STAR, "*"],
        [Lexer::T_STAR, "*"],
        [Lexer::T_NEWLINE, "\n"],
        [Lexer::T_WHITESPACE, " "],
        [Lexer::T_STAR, "*"],
        [Lexer::T_WHITESPACE, " "],
        [Lexer::T_STRING, "This"],
        [Lexer::T_WHITESPACE, " "],
        [Lexer::T_STRING, "is"],
        [Lexer::T_WHITESPACE, " "],
        [Lexer::T_STRING, "some"],
        [Lexer::T_WHITESPACE, " "],
        [Lexer::T_STRING, "crazy"],
        [Lexer::T_WHITESPACE, " "],
        [Lexer::T_STRING, "text"],
        [Lexer::T_DOT, "."],
        [Lexer::T_NEWLINE, "\n"],
        [Lexer::T_WHITESPACE, " "],
        [Lexer::T_STAR, "*"],
        [Lexer::T_WHITESPACE, " "],
        [Lexer::T_STRING, "Test"],
        [Lexer::T_MINUS, "-"],
        [Lexer::T_STRING, "test"],
        [Lexer::T_WHITESPACE, " "],
        [Lexer::T_LCBRACKET, "{"],
        [Lexer::T_AT, "@"],
        [Lexer::T_STRING, "foo"],
        [Lexer::T_WHITESPACE, " "],
        [Lexer::T_STRING, "bar"],
        [Lexer::T_COLON, ":"],
        [Lexer::T_COLON, ":"],
        [Lexer::T_STRING, "baz"],
        [Lexer::T_LRBRACKET, "("],
        [Lexer::T_RRBRACKET, ")"],
        [Lexer::T_RCBRACKET, "}"],
        [Lexer::T_NEWLINE, "\n"],
        [Lexer::T_WHITESPACE, " "],
        [Lexer::T_STAR, "*"],
        [Lexer::T_SLASH, "/"],
        [Lexer::END_OF_STRING, ""],
    ];

    public function testLexing(): void
    {
        $lexer = new Lexer($this->_string);

        $i = 0;
        while($token = $lexer->getToken()) {
            $this->assertEquals($this->_tokens[$i][0], $token->getType());
            $this->assertEquals($this->_tokens[$i][1], $token->getValue());
            $i++;
            $lexer->moveNext();
        }
        $this->assertEquals(count($this->_tokens), $i);
        $lexer->resetPosition(5);
        $token = $lexer->getToken();
        $this->assertEquals($this->_tokens[5][0], $token->getType());
        $this->assertEquals($this->_tokens[5][1], $token->getValue());
        $lexer->reset();
        $this->assertEquals(0, $lexer->getPosition());
    }
}
