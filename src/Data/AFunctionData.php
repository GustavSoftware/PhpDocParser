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
use Gustav\PhpDocParser\Tags\ParamTag;
use Gustav\PhpDocParser\Tags\ReturnTag;
use Gustav\PhpDocParser\Tags\SeeTag;
use Gustav\PhpDocParser\Tags\SinceTag;
use Gustav\PhpDocParser\Tags\ThrowsTag;
use Gustav\PhpDocParser\Tags\TodoTag;
use Gustav\PhpDocParser\Tags\UsedByTag;
use Gustav\PhpDocParser\Tags\UsesTag;
use Gustav\PhpDocParser\Tags\VersionTag;
use ReflectionFunctionAbstract;

/**
 * This is an abstract class for representation of all data of some function or method.
 *
 * @author Chris Köcher <ckone@fieselschweif.de>
 * @link   https://gustav.fieselschweif.de
 * @since  1.0.0
 */
abstract class AFunctionData extends AData
{
    /**
     * The param-tags.
     *
     * @var ParamTag[]
     */
    private array $_param;

    /**
     * The return-tag.
     *
     * @var ReturnTag|null
     */
    private ?ReturnTag $_return;

    /**
     * The throws-tags.
     *
     * @var ThrowsTag[]
     */
    private array $_throws;

    /**
     * Constructor of this class.
     *
     * @param string $description
     *   The description
     * @param ATag[] $inlineTags
     *   The inline-tags
     * @param ParamTag[] $param
     *   The param-tags
     * @param ReturnTag|null $return
     *   The return-tags
     * @param ThrowsTag[] $throws
     *   The throws-tags
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
     * @param UsedByTag[] $usedBy
     *   The used-by-tags
     * @param UsesTag[] $uses
     *   The uses-tags
     * @param VersionTag[] $version
     *   The version-tags
     */
    public function __construct(
        string $description = "", array $inlineTags = [], array $param = [], ?ReturnTag $return = null,
        array $throws = [], ?ApiTag $api = null, array $author = [], array $copyright = [],
        ?DeprecatedTag $deprecated = null, ?InheritDocTag $inheritDoc = null, array $internal = [], array $link = [],
        array $see = [], array $since = [], array $todo = [], array $usedBy = [], array $uses = [], array $version = []
    ) {
        $this->_param = $param;
        $this->_return = $return;
        $this->_throws = $throws;
        parent::__construct(
            $description, $inlineTags, $api, $author, $copyright, $deprecated, $inheritDoc, $internal, $link, $see,
            $since, $todo, $usedBy, $uses, $version
        );
    }

    /**
     * @inheritDoc
     *
     * @return ReflectionFunctionAbstract
     *   The reflection
     */
    public abstract function getReflection(): ReflectionFunctionAbstract;

    /**
     * Returns the param-tags.
     *
     * @return ParamTag[]
     *   The param-tags
     */
    public function getParams(): array
    {
        return $this->_param;
    }

    /**
     * Returns the return-tag.
     *
     * @return ReturnTag
     *   The return-tag
     */
    public function getReturn(): ?ReturnTag
    {
        return $this->_return;
    }

    /**
     * Returns the throws-tags.
     *
     * @return ThrowsTag[]
     *   The throws-tags
     */
    public function getThrows(): array
    {
        return $this->_throws;
    }
}