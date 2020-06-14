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
 * This class represents the method-tag.
 *
 * @author Chris Köcher <ckone@fieselschweif.de>
 * @link   https://gustav.fieselschweif.de
 * @since  1.0.0
 */
class MethodTag extends ATag
{
    /**
     * The type expression this method returns.
     *
     * @var string
     */
    private string $_return;

    /**
     * The name of this method.
     *
     * @var string
     */
    private string $_name;

    /**
     * The arguments of this method. The elements of this array are associative arrays as follows:
     * array(
     *     'type' => 'SomeType',
     *     'name' => '$theName'
     * )
     *
     * @var string[][]
     */
    private array $_args;

    /**
     * Constructor of this class.
     *
     * @param string $return
     *   The return type
     * @param string $name
     *   The name
     * @param string[][] $args
     *   The arguments
     * @param string $description
     *   The description
     * @param ATag[] $inlineTags
     *   The inline tags
     */
    public function __construct(string $return, string $name, array $args, string $description, array $inlineTags)
    {
        $this->_return = $return;
        $this->_name = $name;
        $this->_args = $args;
        parent::__construct($description, $inlineTags);
    }

    /**
     * Returns the type expression this method returns.
     *
     * @return string
     *   The return type
     */
    public function getReturn(): string
    {
        return $this->_return;
    }

    /**
     * Returns the name of this method.
     *
     * @return string
     *   The name
     */
    public function getName(): string
    {
        return $this->_name;
    }

    /**
     * Returns the arguments of this method. The elements of this array are associative arrays as follows:
     * array(
     *     'type' => 'SomeType',
     *     'name' => '$theName'
     * )
     *
     * @return string[][]
     *   The arguments
     */
    public function getArgs(): array
    {
        return $this->_args;
    }
}