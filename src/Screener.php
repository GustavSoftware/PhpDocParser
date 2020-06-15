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

use Gustav\Lexer\AScreener;
use Gustav\Lexer\Token;

/**
 * This is the screener which is needed while parsing PHPDoc comments.
 *
 * @internal
 * @author Chris Köcher <ckone@fieselschweif.de>
 * @link   https://gustav.fieselschweif.de
 * @since  1.0.0
 */
class Screener extends AScreener
{
    /**
     * A map of tag names to their types.
     *
     * @var int[]
     */
    private static array $_types = [
        "api" => Lexer::T_API_TAG,
        "author" => Lexer::T_AUTHOR_TAG,
        "copyright" => Lexer::T_COPYRIGHT_TAG,
        "deprecated" => Lexer::T_DEPRECATED_TAG,
        "inheritdoc" => Lexer::T_INHERITDOC_TAG,
        "internal" => Lexer::T_INTERNAL_TAG,
        "link" => Lexer::T_LINK_TAG,
        "method" => Lexer::T_METHOD_TAG,
        "package" => Lexer::T_PACKAGE_TAG,
        "param" => Lexer::T_PARAM_TAG,
        "property" => Lexer::T_PROPERTY_TAG,
        "return" => Lexer::T_RETURN_TAG,
        "see" => Lexer::T_SEE_TAG,
        "since" => Lexer::T_SINCE_TAG,
        "throws" => Lexer::T_THROWS_TAG,
        "todo" => Lexer::T_TODO_TAG,
        "uses" => Lexer::T_USES_TAG,
        "used" => Lexer::T_USED_BY_TAG,
        "var" => Lexer::T_VAR_TAG,
        "version" => Lexer::T_VERSION_TAG
    ];

    /**
     * Array of scanned and preprocessed tokens.
     *
     * @var Token[]
     */
    private array $_tokens = [];

    public function __construct(string $string)
    {
        parent::__construct($string, new Lexer($string));
    }

    /**
     * Returns the current position of the cursor in the token stream.
     *
     * @return int
     *   The current position
     */
    public function getPosition(): int
    {
        return $this->_position;
    }

    /**
     * Sets the position of the cursor in the token stream.
     *
     * @param int $pos
     *   The new position
     * @return $this
     *   This object
     */
    public function setPosition(int $pos): self
    {
        $this->_position = $pos;
        return $this;
    }

    /**
     * Returns the peek of the cursor in the token stream.
     *
     * @return int
     *   The peek
     */
    public function getPeek(): int
    {
        return $this->_peek;
    }

    /**
     * Sets the peek of the cursor in the token stream.
     *
     * @param int $peek
     *   The new peek
     * @return $this
     *   This object
     */
    public function setPeek(int $peek): self
    {
        $this->_peek = $peek;
        return $this;
    }

    /**
     * @inheritDoc
     * @throws ParserException
     *   Unexpected token or unexpected end
     */
    protected function _fetchToken(int $pos): ?Token
    {
        while(!isset($this->_tokens[$pos])) {
            $token = $this->_moveLexer();

            if($token === null) {
                return null;
            }

            switch($token->getType()) {
                case Lexer::T_WHITESPACE:
                    $this->_mergeWhiteSpace($token);
                    break;
                case Lexer::T_NEWLINE:
                    $this->_fetchNewLine();
                    break;
                case Lexer::T_AT:
                    $this->_fetchTag($token);
                    break;
                case Lexer::T_SLASH:
                    $this->_fetchIntro($token);
                    break;
                case Lexer::T_STAR:
                    $this->_fetchOutro($token);
                    break;
                case Lexer::T_STRING:
                    $this->_mergeStrings($token);
                    break;
                default:
                    $this->_tokens[] = $token;
            }
        }

        return $this->_tokens[$pos];
    }

    /**
     * Returns the next token from token stream
     *
     * @return Token|null
     *   The next token
     */
    private function _moveLexer(): ?Token
    {
        $token = $this->_lexer->getToken();
        $this->_lexer->moveNext();
        return $token;
    }

    /**
     * Merges consecutive whitespace-tokens.
     *
     * @param Token $token
     *   The current token
     */
    private function _mergeWhiteSpace(Token $token): void
    {
        $value = $token->getValue();
        $nextToken = $this->_moveLexer();
        while($nextToken && $nextToken->getType() == Lexer::T_WHITESPACE) {
            $value .= $nextToken->getValue();
            $nextToken = $this->_moveLexer();
        }
        $this->_tokens[] = new Token(Lexer::T_WHITESPACE, $value, $token->getPosition());
        if($token) {
            $this->_lexer->resetPosition($this->_lexer->getPosition()-1);
        }
    }

    /**
     * Merges consecutive string-tokens.
     *
     * @param Token $token
     *   The current token
     */
    private function _mergeStrings(Token $token): void
    {
        $value = $token->getValue();
        $next = $this->_moveLexer();
        while($next && $next->getType() == Lexer::T_STRING) {
            $value .= $next->getValue();
            $next = $this->_moveLexer();
        }
        if($next) {
            $this->_lexer->resetPosition($this->_lexer->getPosition()-1);
        }
        $this->_tokens[] = new Token(Lexer::T_STRING, $value, $token->getPosition());
    }

    /**
     * Fetches a new line in the token stream. Additionally, this method removes leading whitespace and the first star
     * from the new line. Alternatively, this method merges the outro-token if we are on the last line.
     *
     * @throws ParserException
     *   Unexpected token or unexpected end
     */
    private function _fetchNewLine(): void
    {
        do {
            $nextToken = $this->_moveLexer();
        } while($nextToken && $nextToken->getType() == Lexer::T_WHITESPACE);

        if(!$nextToken) {
            return;
        }

        switch($nextToken->getType()) {
            case Lexer::T_SLASH:
                $next2 = $this->_moveLexer();
                $next3 = $this->_moveLexer();
                $this->_matchToken($next2, Lexer::T_STAR);
                $this->_matchToken($next3, Lexer::T_STAR);
                $this->_tokens[] = new Token(Lexer::T_INTRO, "/**", $nextToken->getPosition());
                return;
            case Lexer::T_STAR:
                $next2 = $this->_moveLexer();
                if(!$next2) {
                    throw ParserException::unexpectedEnd($this->_string);
                }
                switch($next2->getType()) {
                    case Lexer::T_WHITESPACE:
                        $this->_tokens[] = new Token(Lexer::T_WHITESPACE, "\n", $nextToken->getPosition());
                        return;
                    case Lexer::T_SLASH:
                    case Lexer::T_STAR:
                        while($next2 && $next2->getType() == Lexer::T_STAR) {
                            $next2 = $this->_moveLexer();
                        }
                        $this->_matchToken($next2, Lexer::T_SLASH);
                        $this->_tokens[] = new Token(Lexer::T_OUTRO, "*/", $nextToken->getPosition());
                        return;
                    default:
                        $this->_tokens[] = new Token(Lexer::T_WHITESPACE, "\n", $nextToken->getPosition());
                        $this->_lexer->resetPosition($this->_lexer->getPosition()-1);
                        return;
                }
            case Lexer::END_OF_STRING:
                $this->_tokens[] = $nextToken;
                break;
            default:
                throw ParserException::unexpectedToken($nextToken, $this->_string);
        }
    }

    /**
     * Fetches the intro-token (i.e., one slash and two or more stars).
     *
     * @param Token $token
     *   The current token
     */
    private function _fetchIntro(Token $token): void
    {
        $next1 = $this->_moveLexer();
        $next2 = $this->_moveLexer();
        if($next1 && $next2 && $next1->getType() == Lexer::T_STAR && $next2->getType() == Lexer::T_STAR) {
            do {
                $next3 = $this->_moveLexer();
            } while($next3 && $next3->getType() == Lexer::T_STAR);
            if($next3) {
                $this->_lexer->resetPosition($this->_lexer->getPosition()-1);
            }
            $this->_tokens[] = new Token(Lexer::T_INTRO, "/**", $token->getPosition());
            return;
        }
        $this->_tokens[] = $this->_convertToStringToken($token);
        if($next2) {
            $this->_lexer->resetPosition($this->_lexer->getPosition()-2);
        } elseif($next1) {
            $this->_lexer->resetPosition($this->_lexer->getPosition()-1);
        }
    }

    /**
     * Fetches the outro-token (i.e., one or more stars and a slash).
     *
     * @param Token $token
     *   The current token
     */
    private function _fetchOutro(Token $token): void
    {
        $next = $this->_moveLexer();
        $number = 1;
        while($next && $next->getType() == Lexer::T_STAR) {
            $next = $this->_moveLexer();
            $number++;
        }
        if($next && $next->getType() == Lexer::T_SLASH) {
            $this->_tokens[] = new Token(Lexer::T_OUTRO, "*/", $token->getPosition());
        } elseif($next) {
            $this->_lexer->resetPosition($this->_lexer->getPosition()-1);
            $this->_tokens[] = new Token(Lexer::T_STRING, str_repeat("*", $number), $token->getPosition());
        }
    }

    /**
     * Tries to fetch a tag name if the current token contains an "@". If the following token is no valid tag name
     * this method converts the current token to a string token.
     *
     * @param Token $token
     *   The current token
     */
    private function _fetchTag(Token $token): void
    {
        $nextToken = $this->_moveLexer();
        if(!$nextToken) {
            $this->_tokens[] = $this->_convertToStringToken($token);
            return;
        }

        $value = trim(strtolower($nextToken->getValue()));
        if($nextToken->getType() != Lexer::T_STRING || !isset(self::$_types[$value])) {
            $this->_tokens[] = $this->_convertToStringToken($token);
            $this->_lexer->resetPosition($this->_lexer->getPosition()-1);
            return;
        }

        $type = self::$_types[$value];
        if($type == Lexer::T_PROPERTY_TAG) {
            $next2 = $this->_moveLexer();
            if($next2 && $next2->getType() == Lexer::T_MINUS) {
                $next3 = $this->_moveLexer();
                if($next3 && trim(strtolower($next3->getValue())) == "read") {
                    $type = Lexer::T_PROPERTY_READ_TAG;
                    $value .= "-read";
                } elseif($next3 && trim(strtolower($next3->getValue())) == "write") {
                    $type = Lexer::T_PROPERTY_WRITE_TAG;
                    $value .= "-write";
                } elseif($next3) {
                    $this->_lexer->resetPosition($this->_lexer->getPosition()-2);
                } else {
                    $this->_lexer->resetPosition($this->_lexer->getPosition()-1);
                }
            } elseif($next2) {
                $this->_lexer->resetPosition($this->_lexer->getPosition()-1);
            }
        } elseif($type == Lexer::T_USED_BY_TAG) {
            $next2 = $this->_moveLexer();
            if($next2 && $next2->getType() == Lexer::T_MINUS) {
                $next3 = $this->_moveLexer();
                if($next3) {
                    if(trim(strtolower($next3->getValue())) != "by") {
                        $type = Lexer::T_STRING;
                        $value .= $next2->getValue();
                        $this->_lexer->resetPosition($this->_lexer->getPosition()-1);
                    } else {
                        $value .= "-by";
                    }
                } else {
                    $type = Lexer::T_STRING;
                    $value .= $next2->getValue();
                }
            } else {
                $type = Lexer::T_STRING;
                if($next2) {
                    $this->_lexer->resetPosition($this->_lexer->getPosition()-1);
                }
            }
        }

        $this->_tokens[] = new Token($type, "@".$value, $token->getPosition());
    }

    /**
     * Converts the given token to a string-token.
     *
     * @param Token $token
     *   The token to convert
     * @return Token
     *   The converted token
     */
    private function _convertToStringToken(Token $token): Token
    {
        return new Token(Lexer::T_STRING, $token->getValue(), $token->getPosition());
    }

    /**
     * Checks whether the given token matches to the given type. The method throws an exception if the token is NULL
     * or has invalid type.
     *
     * @param Token|null $token
     *   The token to check
     * @param int $type
     *   The type to check
     * @throws ParserException
     *   Unexpected token or unexpected end
     */
    private function _matchToken(?Token $token, int $type): void
    {
        if(!$token) {
            throw ParserException::unexpectedEnd($this->_string);
        } elseif($token->getType() != $type) {
            throw ParserException::unexpectedToken($token, $this->_string);
        }
    }
}