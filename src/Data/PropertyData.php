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

namespace Gustav\PhpDocParser\Data;

use Gustav\PhpDocParser\Tags\ApiTag;
use Gustav\PhpDocParser\Tags\ATag;
use Gustav\PhpDocParser\Tags\AuthorTag;
use Gustav\PhpDocParser\Tags\CopyrightTag;
use Gustav\PhpDocParser\Tags\DeprecatedTag;
use Gustav\PhpDocParser\Tags\InheritDocTag;
use Gustav\PhpDocParser\Tags\InternalTag;
use Gustav\PhpDocParser\Tags\LinkTag;
use Gustav\PhpDocParser\Tags\SeeTag;
use Gustav\PhpDocParser\Tags\SinceTag;
use Gustav\PhpDocParser\Tags\TodoTag;
use Gustav\PhpDocParser\Tags\UsesTag;
use Gustav\PhpDocParser\Tags\VarTag;
use Gustav\PhpDocParser\Tags\VersionTag;
use ReflectionProperty;

/**
 * This class represents all the data of some class property.
 *
 * @author Chris Köcher <ckone@fieselschweif.de>
 * @link   https://gustav.fieselschweif.de
 * @since  1.0.0
 */
class PropertyData extends AData
{
    /**
     * The reflection.
     *
     * @var ReflectionProperty
     */
    private ReflectionProperty $_reflection;

    /**
     * The var-tag.
     *
     * @var VarTag|null
     */
    private ?VarTag $_var;

    /**
     * Constructor of this class.
     *
     * @param ReflectionProperty $reflection
     *   The reflection
     * @param VarTag|null $var
     *   The var-tag
     * @param string $description
     *   The description
     * @param ATag[] $inlineTags
     *   The inline tags
     * @param ApiTag|null $api
     *   The api-tag
     * @param AuthorTag[] $author
     *   The author-tags
     * @param CopyrightTag[] $copyright
     *   The copyright-tags
     * @param DeprecatedTag|null $deprecated
     *   The deprecated-tag
     * @param InheritDocTag|null $inheritDoc
     *   The inheritDoc-tag
     * @param InternalTag[] $internal
     *   The internal-tags
     * @param LinkTag[] $link
     *   The link-tags
     * @param SeeTag[] $see
     *   The see-tags
     * @param SinceTag[] $since
     *   The since-tags
     * @param TodoTag[] $todo
     *   The todo-tags
     * @param UsesTag[] $uses
     *   The uses-tags
     * @param VersionTag[] $version
     *   The version-tags
     */
    public function __construct(
        ReflectionProperty $reflection, ?VarTag $var = null, string $description = "", array $inlineTags = [],
        ?ApiTag $api = null, array $author = [], array $copyright = [], ?DeprecatedTag $deprecated = null,
        ?InheritDocTag $inheritDoc = null, array $internal = [], array $link = [], array $see = [], array $since = [],
        array $todo = [], array $uses = [], array $version = []
    ) {
        $this->_reflection = $reflection;
        $this->_var = $var;
        parent::__construct(
            $description, $inlineTags, $api, $author, $copyright, $deprecated, $inheritDoc, $internal, $link, $see,
            $since, $todo, $uses, $version
        );
    }

    /**
     * @inheritDoc
     * @return ReflectionProperty
     *   The reflection
     */
    public function getReflection(): ReflectionProperty
    {
        return $this->_reflection;
    }

    /**
     * Returns the var-tag.
     *
     * @return VarTag|null
     *   The var-tag
     */
    public function getVarTag(): ?VarTag
    {
        return $this->_var;
    }
}