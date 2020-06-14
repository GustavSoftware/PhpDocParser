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
use Gustav\PhpDocParser\Tags\MethodTag;
use Gustav\PhpDocParser\Tags\PackageTag;
use Gustav\PhpDocParser\Tags\SeeTag;
use Gustav\PhpDocParser\Tags\SinceTag;
use Gustav\PhpDocParser\Tags\TodoTag;
use Gustav\PhpDocParser\Tags\UsesTag;
use Gustav\PhpDocParser\Tags\VersionTag;
use ReflectionClass;

/**
 * This class represents all the data of some interface.
 *
 * @author Chris Köcher <ckone@fieselschweif.de>
 * @link   https://gustav.fieselschweif.de
 * @since  1.0.0
 */
class InterfaceData extends AData
{
    /**
     * The reflection.
     *
     * @var ReflectionClass
     */
    private ReflectionClass $_reflection;

    /**
     * The constants of this interface or class.
     *
     * @var ConstantData[]
     */
    private array $_constants;

    /**
     * The methods of this interface or class.
     *
     * @var MethodData[]
     */
    private array $_methods;

    /**
     * The method-tags.
     *
     * @var MethodTag[]
     */
    private array $_methodTags;

    /**
     * The package-tag.
     *
     * @var PackageTag|null
     */
    private ?PackageTag $_package;

    /**
     * Constructor of this class.
     *
     * @param ReflectionClass $reflection
     *   The reflection
     * @param ConstantData[] $constants
     *   The constants
     * @param MethodData[] $methods
     *   The methods
     * @param MethodTag[] $methodTags
     *   The method-tags
     * @param PackageTag|null $package
     *   The package-tag
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
        ReflectionClass $reflection, array $constants = [], array $methods = [], array $methodTags = [],
        ?PackageTag $package = null, string $description = "", array $inlineTags = [], ?ApiTag $api = null,
        array $author = [], array $copyright = [], ?DeprecatedTag $deprecated = null, ?InheritDocTag $inheritDoc = null,
        array $internal = [], array $link = [], array $see = [], array $since = [], array $todo = [], array $uses = [],
        array $version = []
    ) {
        $this->_reflection = $reflection;
        $this->_constants = $constants;
        $this->_methods = $methods;
        $this->_methodTags = $methodTags;
        $this->_package = $package;
        parent::__construct(
            $description, $inlineTags, $api, $author, $copyright, $deprecated, $inheritDoc, $internal, $link, $see,
            $since, $todo, $uses, $version
        );
    }

    /**
     * @inheritDoc
     * @return ReflectionClass
     *   The reflection
     */
    public function getReflection(): ReflectionClass
    {
        return $this->_reflection;
    }

    /**
     * Returns the constants of this interface or class.
     *
     * @return ConstantData[]
     *   The constants
     */
    public function getConstants(): array
    {
        return $this->_constants;
    }

    /**
     * Returns the methods of this interface or class.
     *
     * @return MethodData[]
     *   The methods
     */
    public function getMethods(): array
    {
        return $this->_methods;
    }

    /**
     * Returns the method-tags.
     *
     * @return MethodTag[]
     *   The method-tags
     */
    public function getMethodTags(): array
    {
        return $this->_methodTags;
    }

    /**
     * Returns the package-tag.
     *
     * @return PackageTag|null
     *   The package-tag
     */
    public function getPackageTag(): ?PackageTag
    {
        return $this->_package;
    }
}