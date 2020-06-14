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

namespace Gustav\PhpDocParser\Tags;

/**
 * This class represents an author-tag.
 *
 * @author Chris Köcher <ckone@fieselschweif.de>
 * @link   https://gustav.fieselschweif.de
 * @since  1.0.0
 */
class AuthorTag extends ATag
{
    /**
     * The name of the author.
     *
     * @var string
     */
    private string $_name;

    /**
     * The Email-address of the author.
     * @var string
     */
    private string $_email;

    /**
     * Constructor of this class.
     *
     * @param string $name
     *   The name
     * @param string $email
     *   The Email-address
     */
    public function __construct(string $name, string $email)
    {
        $this->_name = $name;
        $this->_email = $email;
        parent::__construct("", []);
    }

    /**
     * Returns the name of the author.
     *
     * @return string
     *   The name
     */
    public function getName(): string
    {
        return $this->_name;
    }

    /**
     * Returns the Email-address of this author.
     *
     * @return string
     *   The Email-address
     */
    public function getEmail(): string
    {
        return $this->_email;
    }
}