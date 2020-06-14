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
 * This class represents a uses-tag.
 *
 * @author Chris Köcher <ckone@fieselschweif.de>
 * @link   https://gustav.fieselschweif.de
 * @since  1.0.0
 */
class UsesTag extends ATag
{
    /**
     * An structural element or file name to be used at this position.
     *
     * @var string
     */
    private string $_link;

    /**
     * Constructor of this class.
     *
     * @param string $link
     *   The structural element or file name
     * @param string $description
     *   The description
     * @param ATag[] $inlineTags
     *   The inline tags
     */
    public function __construct(string $link, string $description, array $inlineTags)
    {
        $this->_link = $link;
        parent::__construct($description, $inlineTags);
    }

    /**
     * Returns the structural element or file name to be used at this position.
     *
     * @return string
     *   The structural element or file name
     */
    public function getLink(): string
    {
        return $this->_link;
    }
}