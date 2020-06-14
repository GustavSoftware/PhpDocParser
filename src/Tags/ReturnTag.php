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
 * This class represents a return-tag.
 *
 * @author Chris Köcher <ckone@fieselschweif.de>
 * @link   https://gustav.fieselschweif.de
 * @since  1.0.0
 */
class ReturnTag extends ATag
{
    /**
     * The type expression to return in this method.
     *
     * @var string
     */
    private string $_type;

    /**
     * Constructor of this class.
     *
     * @param string $type
     *   The type expression
     * @param string $description
     *   The description
     * @param ATag[] $inlineTags
     *   The inline tags
     */
    public function __construct(string $type, string $description, array $inlineTags)
    {
        $this->_type = $type;
        parent::__construct($description, $inlineTags);
    }

    /**
     * Returns the type expression to return in this method.
     *
     * @return string
     *   The type expression
     */
    public function getType(): string
    {
        return $this->_type;
    }
}