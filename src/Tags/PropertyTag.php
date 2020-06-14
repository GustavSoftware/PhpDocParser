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
 * This class represents a property-, property-read-, or property-write-tag.
 *
 * @author Chris Köcher <ckone@fieselschweif.de>
 * @link   https://gustav.fieselschweif.de
 * @since  1.0.0
 */
class PropertyTag extends ATag
{
    /**
     * These constants can be used to indicate whether this property cn be read or written. Note that a bitwise OR
     * can be used to indicate bidirectional access.
     *
     * @var int
     */
    public const T_READ = 1;
    public const T_WRITE = 2;

    /**
     * The bitmap representing the direction of access to this property:
     * 1 -> read-access,
     * 2 -> write-access,
     * 3 -> read- and write-access
     *
     * @var int
     */
    private int $_direction;

    /**
     * The type expression of this property.
     *
     * @var string
     */
    private string $_type;

    /**
     * The name of this property.
     *
     * @var string
     */
    private string $_name;

    /**
     * Constructor of this class.
     *
     * @param int $direction
     *   The direction of access
     * @param string $type
     *   The type expression
     * @param string $name
     *   The name
     * @param string $description
     *   The description
     * @param ATag[] $inlineTags
     *   The inline tags
     */
    public function __construct(int $direction, string $type, string $name, string $description, array $inlineTags)
    {
        $this->_direction = $direction;
        $this->_type = $type;
        $this->_name = $name;
        parent::__construct($description, $inlineTags);
    }

    /**
     * Indicates whether this property is readable.
     *
     * @return bool
     *   true if readable, false otherwise
     */
    public function isReadable(): bool
    {
        return ($this->_direction & self::T_READ) == true;
    }

    /**
     * Indicates whether this property is writable.
     *
     * @return bool
     *   true if writable, false otherwise
     */
    public function isWritable(): bool
    {
        return ($this->_direction & self::T_WRITE) == true;
    }

    /**
     * Returns the type expression of this property.
     *
     * @return string
     *   The type expression
     */
    public function getType(): string
    {
        return $this->_type;
    }

    /**
     * Returns the name of this property.
     *
     * @return string
     *   The name
     */
    public function getName(): string
    {
        return $this->_name;
    }
}