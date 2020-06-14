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
use Gustav\PhpDocParser\Tags\VersionTag;
use Reflector;

/**
 * This is some abstract class for representation of all data of some structural element.
 *
 * @author Chris Köcher <ckone@fieselschweif.de>
 * @link   https://gustav.fieselschweif.de
 * @since  1.0.0
 */
abstract class AData
{
    /**
     * The description of this structural element.
     *
     * @var string
     */
    private string $_description;

    /**
     * The inline tags to be contained in the description of this structural element. You can find a placeholder
     * "{{{{key-of-element}}}}" at the positions of the inline tags in the description.
     *
     * @var array
     */
    private array $_inlineTags;

    /**
     * The api-tag.
     *
     * @var ApiTag|null
     */
    private ?ApiTag $_api;

    /**
     * The author-tags.
     *
     * @var AuthorTag[]
     */
    private array $_author;

    /**
     * The copyright-tags.
     *
     * @var CopyrightTag[]
     */
    private array $_copyright;

    /**
     * The deprecated-tag.
     *
     * @var DeprecatedTag|null
     */
    private ?DeprecatedTag $_deprecated;

    /**
     * The inheritDoc-tag.
     *
     * @var InheritDocTag|null
     */
    private ?InheritDocTag $_inheritDoc;

    /**
     * The internal tags.
     *
     * @var InternalTag[]
     */
    private array $_internal;

    /**
     * The link tags.
     *
     * @var LinkTag[]
     */
    private array $_link;

    /**
     * The see tags.
     *
     * @var SeeTag[]
     */
    private array $_see;

    /**
     * The since tags.
     *
     * @var SinceTag[]
     */
    private array $_since;

    /**
     * The todo-tags.
     *
     * @var TodoTag[]
     */
    private array $_todo;

    /**
     * The uses-tags.
     *
     * @var UsesTag[]
     */
    private array $_uses;

    /**
     * The version-tags.
     *
     * @var VersionTag[]
     */
    private array $_version;

    /**
     * Constructor of this class.
     *
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
        string $description = "", array $inlineTags = [], ?ApiTag $api = null, array $author = [], array $copyright = [],
        ?DeprecatedTag $deprecated = null, ?InheritDocTag $inheritDoc = null, array $internal = [], array $link = [],
        array $see = [], array $since = [], array $todo = [], array $uses = [], array $version = []
    ) {
        $this->_description = trim($description);
        $this->_inlineTags = $inlineTags;
        $this->_api = $api;
        $this->_author = $author;
        $this->_copyright = $copyright;
        $this->_deprecated = $deprecated;
        $this->_internal = $internal;
        $this->_inheritDoc = $inheritDoc;
        $this->_link = $link;
        $this->_see = $see;
        $this->_since = $since;
        $this->_todo = $todo;
        $this->_uses = $uses;
        $this->_version = $version;
    }

    /**
     * Returns the reflection of this structural element.
     *
     * @return Reflector
     *   The reflection
     */
    public abstract function getReflection(): Reflector;

    /**
     * Returns the description of this structural element.
     *
     * @return string
     *   The description
     */
    public function getDescription(): string
    {
        return $this->_description;
    }

    /**
     * Returns the inline tags of this structural element. You can find a placeholder "{{{{key-of-element}}}}" at the
     * positions of the inline tags in the description.
     *
     * @return ATag[]
     *   The inline tags
     */
    public function getInlineTags(): array
    {
        return $this->_inlineTags;
    }

    /**
     * Returns the api-tag.
     *
     * @return ApiTag|null
     *   The api-tag
     */
    public function getApiTag(): ?ApiTag
    {
        return $this->_api;
    }

    /**
     * Returns the author-tags.
     *
     * @return AuthorTag[]
     *   The author-tags
     */
    public function getAuthorTags(): array
    {
        return $this->_author;
    }

    /**
     * Returns the copyright-tags.
     *
     * @return CopyrightTag[]
     *   The copyright-tag
     */
    public function getCopyrightTags(): array
    {
        return $this->_copyright;
    }

    /**
     * Returns the deprecated-tag.
     *
     * @return DeprecatedTag|null
     *   The deprecated-tag
     */
    public function getDeprecatedTag(): ?DeprecatedTag
    {
        return $this->_deprecated;
    }

    /**
     * Returns the inheritDoc-tag.
     *
     * @return InheritDocTag|null
     *   The inheritDoc-tag
     */
    public function getInheritDocTag(): ?InheritDocTag
    {
        return $this->_inheritDoc;
    }

    /**
     * Returns the internal-tags.
     *
     * @return InternalTag[]
     *   The internal-tags
     */
    public function getInternalTags(): array
    {
        return $this->_internal;
    }

    /**
     * Returns the link-tags.
     *
     * @return LinkTag[]
     *   The link-tags
     */
    public function getLinkTags(): array
    {
        return $this->_link;
    }

    /**
     * Returns the see-tags.
     *
     * @return SeeTag[]
     *   The see-tags
     */
    public function getSeeTags(): array
    {
        return $this->_see;
    }

    /**
     * Returns the since-tags.
     *
     * @return SinceTag[]
     *   The since-tags
     */
    public function getSinceTags(): array
    {
        return $this->_since;
    }

    /**
     * Returns the todo-tags.
     *
     * @return TodoTag[]
     *   The todo-tags
     */
    public function getTodoTags(): array
    {
        return $this->_todo;
    }

    /**
     * Returns the uses-tags.
     *
     * @return UsesTag[]
     *   The uses-tags
     */
    public function getUsesTags(): array
    {
        return $this->_uses;
    }

    /**
     * Returns the version-tags.
     *
     * @return VersionTag[]
     */
    public function getVersionTags(): array
    {
        return $this->_version;
    }
}