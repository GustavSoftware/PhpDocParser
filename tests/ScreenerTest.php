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
use Gustav\PhpDocParser\Screener;
use PHPUnit\Framework\TestCase;

class ScreenerTest extends TestCase
{
    private string $_string = '
    /**
     * This is no lorem ipsum. {@link http://www.fieselschweif.de Some text here!}
     *
     * @param \foo\bar $baz
     *   Some text
     */
    ';

    private array $_token = [
        [Lexer::T_INTRO, "/**"],
        [Lexer::T_WHITESPACE, "\n"],
        [Lexer::T_STRING, "This"],
        [Lexer::T_WHITESPACE, " "],
        [Lexer::T_STRING, "is"],
        [Lexer::T_WHITESPACE, " "],
        [Lexer::T_STRING, "no"],
        [Lexer::T_WHITESPACE, " "],
        [Lexer::T_STRING, "lorem"],
        [Lexer::T_WHITESPACE, " "],
        [Lexer::T_STRING, "ipsum"],
        [Lexer::T_DOT, "."],
        [Lexer::T_WHITESPACE, " "],
        [Lexer::T_LCBRACKET, "{"],
        [Lexer::T_LINK_TAG, "@link"],
        [Lexer::T_WHITESPACE, " "],
        [Lexer::T_STRING, "http"],
        [Lexer::T_COLON, ":"],
        [Lexer::T_STRING, "/"],
        [Lexer::T_STRING, "/"],
        [Lexer::T_STRING, "www"],
        [Lexer::T_DOT, "."],
        [Lexer::T_STRING, "fieselschweif"],
        [Lexer::T_DOT, "."],
        [Lexer::T_STRING, "de"],
        [Lexer::T_WHITESPACE, " "],
        [Lexer::T_STRING, "Some"],
        [Lexer::T_WHITESPACE, " "],
        [Lexer::T_STRING, "text"],
        [Lexer::T_WHITESPACE, " "],
        [Lexer::T_STRING, "here!"],
        [Lexer::T_RCBRACKET, "}"],
        [Lexer::T_WHITESPACE, "\n"],
        [Lexer::T_WHITESPACE, "\n"],
        [Lexer::T_PARAM_TAG, "@param"],
        [Lexer::T_WHITESPACE, " "],
        [Lexer::T_BACKSLASH, "\\"],
        [Lexer::T_STRING, "foo"],
        [Lexer::T_BACKSLASH, "\\"],
        [Lexer::T_STRING, "bar"],
        [Lexer::T_WHITESPACE, " "],
        [Lexer::T_DOLLAR, "$"],
        [Lexer::T_STRING, "baz"],
        [Lexer::T_WHITESPACE, "\n"],
        [Lexer::T_WHITESPACE, "  "],
        [Lexer::T_STRING, "Some"],
        [Lexer::T_WHITESPACE, " "],
        [Lexer::T_STRING, "text"],
        [Lexer::T_OUTRO, "*/"],
        [Lexer::END_OF_STRING, ""]
    ];

    public function testScreener(): void
    {
        $screener = new Screener($this->_string);

        $i = 1;
        while($token = $screener->getNextToken()) {
            $this->assertEquals($this->_token[$i][0], $token->getType());
            $this->assertEquals($this->_token[$i][1], $token->getValue());
            $i++;
            $screener->peek();
        }
        $this->assertEquals(count($this->_token), $i);
        $screener->resetPeek();

        $i = 0;
        while($token = $screener->getToken()) {
            $this->assertEquals($this->_token[$i][0], $token->getType());
            $this->assertEquals($this->_token[$i][1], $token->getValue());
            $i++;
            $screener->moveNext();
        }
        $this->assertEquals(count($this->_token), $i);

        $screener->setPosition(1);
        $screener->skipUntil(Lexer::T_LCBRACKET);
        $this->assertEquals($this->_token[13][0], $screener->getToken()->getType());
        $this->assertEquals($this->_token[13][1], $screener->getToken()->getValue());

        $token = $screener->peekUntilAny([Lexer::T_RCBRACKET]);
        $this->assertEquals($this->_token[31][0], $token->getType());
        $this->assertEquals($this->_token[31][1], $token->getValue());
    }
}
