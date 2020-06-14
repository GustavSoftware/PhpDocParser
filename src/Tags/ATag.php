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
 * An abstract class for some PHPDoc-tag.
 *
 * @author Chris Köcher <ckone@fieselschweif.de>
 * @link   https://gustav.fieselschweif.de
 * @since  1.0.0
 */
abstract class ATag
{
    /**
     * The description of this tag.
     *
     * @var string
     */
    protected string $_description;

    /**
     * A list of inline tags in the description of this tag. You can find a placeholder "{{{{key-of-element}}}}" at the
     * positions of the inline tags in the description.
     *
     * @var ATag[]
     */
    protected array $_inlineTags;

    /**
     * Constructor of this class.
     *
     * @param string $description
     *   The description
     * @param ATag[] $inlineTags
     *   The inline tags
     */
    public function __construct(string $description, array $inlineTags)
    {
        $this->_description = trim($description);
        $this->_inlineTags = $inlineTags;
    }

    /**
     * Returns the description of this tag. Note that you can find placeholders "{{{{number}}}}" at position "number"
     * in self::$_inlineTags.
     *
     * @return string
     *   The description
     */
    public function getDescription(): string
    {
        return $this->_description;
    }

    /**
     * Returns the inline tags. You can find a placeholder "{{{{key-of-element}}}}" at the positions of the inline tags
     * in the description.
     *
     * @return ATag[]
     *   The inline tags
     */
    public function getInlineTags(): array
    {
        return $this->_inlineTags;
    }
}