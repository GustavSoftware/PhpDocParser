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

namespace Gustav\PhpDocParser;

use Gustav\Lexer\Token;
use Gustav\PhpDocParser\Data\ClassData;
use Gustav\PhpDocParser\Data\ConstantData;
use Gustav\PhpDocParser\Data\FunctionData;
use Gustav\PhpDocParser\Data\InterfaceData;
use Gustav\PhpDocParser\Data\MethodData;
use Gustav\PhpDocParser\Data\PropertyData;
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
use Gustav\PhpDocParser\Tags\ParamTag;
use Gustav\PhpDocParser\Tags\PropertyTag;
use Gustav\PhpDocParser\Tags\ReturnTag;
use Gustav\PhpDocParser\Tags\SeeTag;
use Gustav\PhpDocParser\Tags\SinceTag;
use Gustav\PhpDocParser\Tags\ThrowsTag;
use Gustav\PhpDocParser\Tags\TodoTag;
use Gustav\PhpDocParser\Tags\UsesTag;
use Gustav\PhpDocParser\Tags\VarTag;
use Gustav\PhpDocParser\Tags\VersionTag;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionProperty;

/**
 * The PHPDoc-Parser class.
 *
 * @api
 * @author Chris Köcher <ckone@fieselschweif.de>
 * @link   https://gustav.fieselschweif.de
 * @since  1.0.0
 */
class Parser
{
    /**
     * Some constants to be used to filter methods, properties, and constants of classes and interfaces.
     *
     * @var int
     */
    public const METHODS_PUBLIC = 1;
    public const METHODS_PROTECTED = 2;
    public const METHODS_PRIVATE = 4;
    public const METHODS_ALL = 7;
    public const PROPERTIES_PUBLIC = 8;
    public const PROPERTIES_PROTECTED = 16;
    public const PROPERTIES_PRIVATE = 32;
    public const PROPERTIES_ALL = 56;
    public const CONSTANTS_PUBLIC = 64;
    public const CONSTANTS_PROTECTED = 128;
    public const CONSTANTS_PRIVATE = 256;
    public const CONSTANTS_ALL = 448;
    public const ALL = 511;

    /**
     * This array contains all tag types.
     *
     * @var int[]
     */
    private static array $_tags = [
        Lexer::T_API_TAG, Lexer::T_AUTHOR_TAG, Lexer::T_COPYRIGHT_TAG, Lexer::T_DEPRECATED_TAG, Lexer::T_INHERITDOC_TAG,
        Lexer::T_INTERNAL_TAG, Lexer::T_LINK_TAG, Lexer::T_METHOD_TAG, Lexer::T_PACKAGE_TAG, Lexer::T_PARAM_TAG,
        Lexer::T_PROPERTY_TAG, Lexer::T_PROPERTY_READ_TAG, Lexer::T_PROPERTY_WRITE_TAG, Lexer::T_RETURN_TAG,
        Lexer::T_SEE_TAG, Lexer::T_SINCE_TAG, Lexer::T_THROWS_TAG, Lexer::T_TODO_TAG, Lexer::T_USES_TAG,
        Lexer::T_VAR_TAG, Lexer::T_VERSION_TAG
    ];

    /**
     * The screener to be used while parsing.
     *
     * @var Screener
     */
    private Screener $_screener;

    /**
     * The PHPDoc comment to be parsed here.
     *
     * @var string
     */
    private string $_comment;

    /**
     * Parses the PHPDoc comments of a class or interface and all of its methods, properties, and constants. Using the
     * second argument one can filter the methods, properties, and constants by their visibility. By default this
     * methods parses all of them.
     *
     * @api
     * @param ReflectionClass|string|object $class
     *   The name, and object, or the reflection of the class to parse
     * @param int $filter
     *   The filters of methods, properties, and constants.
     * @return InterfaceData
     *   The parsed data
     * @throws ParserException
     *   Unexpected tokens or an invalid name was found while parsing
     * @throws ReflectionException
     *   Some error occurred while handling the reflection of this constant
     */
    public function parseClass($class, int $filter = self::ALL): InterfaceData
    {
        if(!$class instanceof ReflectionClass) {
            $class = new ReflectionClass($class);
        }
        if($class->isInternal()) {
            throw ParserException::internalElement($class->getName());
        }

        $description = "";
        $inlineTags = $authorTags = $copyrightTags = $internalTags = $linkTags = $methodTags = $paramTags
            = $propertyTags = $seeTags = $sinceTags = $throwsTags = $todoTags = $usesTags = $versionTags = [];

        $this->_parseComment(
            $class->getDocComment(), $description, $inlineTags, $apiTag, $authorTags, $copyrightTags,
            $deprecatedTag, $inheritDocTag, $internalTags, $linkTags, $methodTags, $packageTag,
            $paramTags, $propertyTags, $returnTag, $seeTags, $sinceTags, $throwsTags, $todoTags,
            $usesTags, $varTags, $versionTags
        );

        $constants = $properties = $methods = [];
        foreach($class->getConstants() as $constant) { /** @var ReflectionClassConstant $constant */
            if(
                !$constant->getDeclaringClass()->isInternal() &&
                (($constant->isPublic() && ($filter & self::CONSTANTS_PUBLIC))
                || ($constant->isProtected() && ($filter & self::CONSTANTS_PROTECTED))
                || ($constant->isPrivate() && ($filter & self::CONSTANTS_PRIVATE)))
            ) {
                $constants[] = $this->parseConstant($constant);
            }
        }
        foreach($class->getProperties() as $property) { /** @var ReflectionProperty $property */
            if(
                !$property->getDeclaringClass()->isInternal() &&
                (($property->isPublic() && ($filter & self::PROPERTIES_PUBLIC))
                || ($property->isProtected() && ($filter & self::PROPERTIES_PROTECTED))
                || ($property->isPrivate() && ($filter & self::PROPERTIES_PRIVATE)))
            ) {
                $properties[] = $this->parseProperty($property);
            }
        }
        foreach($class->getMethods() as $method) { /** @var ReflectionMethod $method */
            if(
                !$method->isInternal() &&
                (($method->isPublic() && ($filter & self::METHODS_PUBLIC))
                || ($method->isProtected() && ($filter & self::METHODS_PROTECTED))
                || ($method->isPrivate() && ($filter & self::METHODS_PRIVATE)))
            ) {
                $methods[] = $this->parseMethod($method);
            }
        }

        if($class->isInterface()) {
            return new InterfaceData(
                $class, $constants, $methods, $methodTags, $packageTag, $description, $inlineTags, $apiTag, $authorTags,
                $copyrightTags, $deprecatedTag, $inheritDocTag, $internalTags, $linkTags, $seeTags, $sinceTags,
                $todoTags, $usesTags, $versionTags
            );
        } else {
            return new ClassData(
                $class, $constants, $methods, $methodTags, $properties, $propertyTags, $packageTag, $description,
                $inlineTags, $apiTag, $authorTags, $copyrightTags, $deprecatedTag, $inheritDocTag, $internalTags,
                $linkTags, $seeTags, $sinceTags, $todoTags, $usesTags, $versionTags
            );
        }
    }

    /**
     * Parses the PHPDoc comment of a single constant.
     *
     * @api
     * @param ReflectionClassConstant|string|object $constant
     *   Either the reflection of a constant or the containing class name or object to consider.
     * @param string $name
     *   The name of the constant to consider if the first argument is no reflection
     * @return ConstantData
     *   The parsed data
     * @throws ParserException
     *   Unexpected tokens or an invalid name was found while parsing
     * @throws ReflectionException
     *   Some error occurred while handling the reflection of this constant
     */
    public function parseConstant($constant, string $name = ""): ConstantData
    {
        if(!$constant instanceof ReflectionClassConstant) {
            $constant = new ReflectionClassConstant($constant, $name);
        }
        if($constant->getDeclaringClass()->isInternal()) {
            throw ParserException::internalElement($constant->getName());
        }

        $description = "";
        $inlineTags = $authorTags = $copyrightTags = $internalTags = $linkTags = $methodTags = $paramTags
            = $propertyTags = $seeTags = $sinceTags = $throwsTags = $todoTags = $usesTags = $versionTags = [];

        $this->_parseComment(
            $constant->getDocComment(), $description, $inlineTags, $apiTag, $authorTags, $copyrightTags,
            $deprecatedTag, $inheritDocTag, $internalTags, $linkTags, $methodTags, $packageTag,
            $paramTags, $propertyTags, $returnTag, $seeTags, $sinceTags, $throwsTags, $todoTags,
            $usesTags, $varTag, $versionTags
        );

        return new ConstantData(
            $constant, $varTag, $description, $inlineTags, $apiTag, $authorTags, $copyrightTags, $deprecatedTag,
            $inheritDocTag, $internalTags, $linkTags, $seeTags, $sinceTags, $todoTags, $usesTags, $versionTags
        );
    }

    /**
     * Parses the PHPDoc comment of a single property.
     *
     * @api
     * @param ReflectionProperty|string|object $property
     *   Either the reflection of a property or the containing class name or object to consider.
     * @param string $name
     *   The name of the property to consider if the first argument is no reflection
     * @return PropertyData
     *   The parsed data
     * @throws ParserException
     *   Unexpected tokens or an invalid name was found while parsing
     * @throws ReflectionException
     *   Some error occurred while handling the reflection of this constant
     */
    public function parseProperty($property, string $name = ""): PropertyData
    {
        if(!$property instanceof ReflectionProperty) {
            $property = new ReflectionProperty($property, $name);
        }
        if($property->getDeclaringClass()->isInternal()) {
            throw ParserException::internalElement($property->getName());
        }

        $description = "";
        $inlineTags = $authorTags = $copyrightTags = $internalTags = $linkTags = $methodTags = $paramTags
            = $propertyTags = $seeTags = $sinceTags = $throwsTags = $todoTags = $usesTags = $versionTags = [];

        $this->_parseComment(
            $property->getDocComment(), $description, $inlineTags, $apiTag, $authorTags, $copyrightTags,
            $deprecatedTag, $inheritDocTag, $internalTags, $linkTags, $methodTags, $packageTag,
            $paramTags, $propertyTags, $returnTag, $seeTags, $sinceTags, $throwsTags, $todoTags,
            $usesTags, $varTag, $versionTags
        );

        return new PropertyData(
            $property, $varTag, $description, $inlineTags, $apiTag, $authorTags, $copyrightTags, $deprecatedTag,
            $inheritDocTag, $internalTags, $linkTags, $seeTags, $sinceTags, $todoTags, $usesTags, $versionTags
        );
    }

    /**
     * Parses the PHPDoc comment of a single method.
     *
     * @api
     * @param ReflectionMethod|string|object $method
     *   Either the reflection of a method or the containing class name or object to consider
     * @param string $name
     *   The name of the method to consider if the first argument is no reflection
     * @return MethodData
     *   The parsed data
     * @throws ParserException
     *   Unexpected tokens or an invalid name was found while parsing
     * @throws ReflectionException
     *   Some error occurred while handling the reflection of this constant
     */
    public function parseMethod($method, string $name = ""): MethodData
    {
        if(!$method instanceof ReflectionMethod) {
            $method = new ReflectionMethod($method, $name);
        }
        if($method->isInternal()) {
            throw ParserException::internalElement($method->getName());
        }

        $description = "";
        $inlineTags = $authorTags = $copyrightTags = $internalTags = $linkTags = $methodTags = $paramTags
            = $propertyTags = $seeTags = $sinceTags = $throwsTags = $todoTags = $usesTags = $versionTags = [];

        $this->_parseComment(
            $method->getDocComment(), $description, $inlineTags, $apiTag, $authorTags, $copyrightTags,
            $deprecatedTag, $inheritDocTag, $internalTags, $linkTags, $methodTags, $packageTag,
            $paramTags, $propertyTags, $returnTag, $seeTags, $sinceTags, $throwsTags, $todoTags,
            $usesTags, $varTag, $versionTags
        );

        return new MethodData(
            $method, $description, $inlineTags, $paramTags, $returnTag, $throwsTags, $apiTag, $authorTags,
            $copyrightTags, $deprecatedTag, $inheritDocTag, $internalTags, $linkTags, $seeTags, $sinceTags, $todoTags,
            $usesTags, $versionTags
        );
    }

    /**
     * Parses the PHPDoc comment of a single function.
     *
     * @api
     * @param ReflectionFunction|string $function
     *   Either the reflection of a function or the name of the function
     * @return FunctionData
     *   The parsed data
     * @throws ParserException
     *   Unexpected tokens or an invalid name was found while parsing
     * @throws ReflectionException
     *   Some error occurred while handling the reflection of this constant
     */
    public function parseFunction($function): FunctionData
    {
        if(!$function instanceof ReflectionFunction) {
            $function = new ReflectionFunction($function);
        }
        if($function->isInternal()) {
            throw ParserException::internalElement($function->getName());
        }

        $this->_parseComment(
            $function->getDocComment(), $description, $inlineTags, $apiTag, $authorTags, $copyrightTags,
            $deprecatedTag, $inheritDocTag, $internalTags, $linkTags, $methodTags, $packageTag,
            $paramTags, $propertyTags, $returnTag, $seeTags, $sinceTags, $throwsTags, $todoTags,
            $usesTags, $varTag, $versionTags
        );

        return new FunctionData(
            $function, $description, $inlineTags, $paramTags, $returnTag, $throwsTags, $apiTag, $authorTags,
            $copyrightTags, $deprecatedTag, $internalTags, $linkTags, $seeTags, $sinceTags, $todoTags, $usesTags,
            $versionTags
        );
    }

    /**
     * Parses some PHPDoc-comment. Note that all arguments except of the first one are passed by reference.
     *
     * @param string $comment
     *   The PHPDoc comment we have to parse
     * @param string $description
     *   The parsed description
     * @param ATag[] $inlineTags
     *   The inline-tags in the description
     * @param ApiTag|null $apiTag
     *   The api-tag
     * @param AuthorTag[] $authorTags
     *   The author-tags
     * @param CopyrightTag[] $copyrightTags
     *   The copyright-tags
     * @param DeprecatedTag|null $deprecatedTag
     *   The deprecated-tag
     * @param InheritDocTag|null $inheritDocTag
     *   The inheritDoc-tag
     * @param InternalTag[] $internalTags
     *   The internal-tags
     * @param LinkTag[] $linkTags
     *   The link-tags
     * @param MethodTag[] $methodTags
     *   The method-tags
     * @param PackageTag|null $packageTag
     *   The package-tag
     * @param ParamTag[] $paramTags
     *   The param-tags
     * @param PropertyTag[] $propertyTags
     *   The property-, property-read-, and property-write-tags
     * @param ReturnTag|null $returnTag
     *   The return-tag
     * @param SeeTag[] $seeTags
     *   The see-tags
     * @param SinceTag[] $sinceTags
     *   The since-tags
     * @param ThrowsTag[] $throwsTags
     *   The throws-tags
     * @param TodoTag[] $todoTags
     *   The todo-tags
     * @param UsesTag[] $usesTags
     *   The uses-tags
     * @param VarTag|null $varTag
     *   The var-tag
     * @param VersionTag[] $versionTags
     *   The version-tags
     * @throws ParserException
     *   Unexpected token, unexpected end, or some invalid name
     */
    private function _parseComment(
        string $comment, string &$description, array &$inlineTags, ?ApiTag &$apiTag, array &$authorTags,
        array &$copyrightTags, ?DeprecatedTag &$deprecatedTag, ?InheritDocTag &$inheritDocTag, array &$internalTags,
        array &$linkTags, array &$methodTags, ?PackageTag &$packageTag, array &$paramTags, array &$propertyTags,
        ?ReturnTag &$returnTag, array &$seeTags, array &$sinceTags, array &$throwsTags, array &$todoTags,
        array &$usesTags, ?VarTag &$varTag, array &$versionTags
    ): void {
        $this->_comment = trim($comment);
        if($this->_comment) {
            $this->_screener = new Screener($this->_comment);
            $this->_screener->skipWhile(Lexer::T_WHITESPACE);
            $this->_match(Lexer::T_INTRO);
            $this->_screener->skipWhile(Lexer::T_WHITESPACE);

            $description = $this->_parseDescription($inlineTags, false);

            $this->_parseTags(
                $apiTag, $authorTags, $copyrightTags, $deprecatedTag, $inheritDocTag,
                $internalTags, $linkTags, $methodTags, $packageTag, $paramTags, $propertyTags,
                $returnTag, $seeTags, $sinceTags, $throwsTags, $todoTags, $usesTags, $varTag,
                $versionTags
            );

            $this->_match(Lexer::T_OUTRO);
            $this->_screener->skipWhile(Lexer::T_WHITESPACE);
            $this->_match(Lexer::END_OF_STRING);
        }
    }

    /**
     * Parses the tags of some PHPDoc comment. Note that all of the arguments of this method are passed by reference.
     *
     * @param ApiTag|null $apiTag
     *   The api-tag
     * @param AuthorTag[] $authorTags
     *   The author-tags
     * @param CopyrightTag[] $copyrightTags
     *   The copyright-tags
     * @param DeprecatedTag|null $deprecatedTag
     *   The deprecated-tag
     * @param InheritDocTag|null $inheritDocTag
     *   The inheritDoc-tag
     * @param InternalTag[] $internalTags
     *   The internal-tags
     * @param LinkTag[] $linkTags
     *   The link-tags
     * @param MethodTag[] $methodTags
     *   The method-tags
     * @param PackageTag|null $packageTag
     *   The package-tag
     * @param ParamTag[] $paramTags
     *   The param-tags
     * @param PropertyTag[] $propertyTags
     *   The property-, property-read-, and property-write-tags
     * @param ReturnTag|null $returnTag
     *   The return-tag
     * @param SeeTag[] $seeTags
     *   The see-tags
     * @param SinceTag[] $sinceTags
     *   The since-tags
     * @param ThrowsTag[] $throwsTags
     *   The throws-tags
     * @param TodoTag[] $todoTags
     *   The todo-tags
     * @param UsesTag[] $usesTags
     *   The uses-tags
     * @param VarTag|null $varTag
     *   The var-tag
     * @param VersionTag[] $versionTags
     *   The version-tags
     * @throws ParserException
     *   Unexpected token, unexpected end, or some invalid name
     */
    private function _parseTags(
        ?ApiTag &$apiTag, array &$authorTags, array &$copyrightTags, ?DeprecatedTag &$deprecatedTag,
        ?InheritDocTag &$inheritDocTag, array &$internalTags, array &$linkTags, array &$methodTags,
        ?PackageTag &$packageTag, array &$paramTags, array &$propertyTags, ?ReturnTag &$returnTag, array &$seeTags,
        array &$sinceTags, array &$throwsTags, array &$todoTags, array &$usesTags, ?VarTag &$varTag, array &$versionTags
    ): void {
        while($this->_screener->isTokenAny(self::$_tags)) {
            switch($this->_screener->getToken()->getType()) {
                case Lexer::T_API_TAG:
                    $apiTag = $this->_parseApiTag();
                    break;
                case Lexer::T_AUTHOR_TAG:
                    $authorTags[] = $this->_parseAuthorTag();
                    break;
                case Lexer::T_COPYRIGHT_TAG:
                    $copyrightTags[] = $this->_parseCopyrightTag();
                    break;
                case Lexer::T_DEPRECATED_TAG:
                    $deprecatedTag = $this->_parseDeprecatedTag();
                    break;
                case Lexer::T_INHERITDOC_TAG:
                    $inheritDocTag = $this->_parseInheritDocTag(false);
                    break;
                case Lexer::T_INTERNAL_TAG:
                    $internalTags[] = $this->_parseInternalTag(false);
                    break;
                case Lexer::T_LINK_TAG:
                    $linkTags[] = $this->_parseLinkTag(false);
                    break;
                case Lexer::T_METHOD_TAG:
                    $methodTags[] = $this->_parseMethodTag();
                    break;
                case Lexer::T_PACKAGE_TAG:
                    $packageTag = $this->_parsePackageTag();
                    break;
                case Lexer::T_PARAM_TAG:
                    $paramTags[] = $this->_parseParamTag();
                    break;
                case Lexer::T_PROPERTY_TAG:
                case Lexer::T_PROPERTY_READ_TAG:
                case Lexer::T_PROPERTY_WRITE_TAG:
                    $propertyTags[] = $this->_parsePropertyTag();
                    break;
                case Lexer::T_RETURN_TAG:
                    $returnTag = $this->_parseReturnTag();
                    break;
                case Lexer::T_SEE_TAG:
                    $seeTags[] = $this->_parseSeeTag();
                    break;
                case Lexer::T_SINCE_TAG:
                    $sinceTags[] = $this->_parseSinceTag();
                    break;
                case Lexer::T_THROWS_TAG:
                    $throwsTags[] = $this->_parseThrowsTag();
                    break;
                case Lexer::T_TODO_TAG:
                    $todoTags[] = $this->_parseTodoTag();
                    break;
                case Lexer::T_USES_TAG:
                    $usesTags[] = $this->_parseUsesTag();
                    break;
                case Lexer::T_VAR_TAG:
                    $varTag = $this->_parseVarTag();
                    break;
                case Lexer::T_VERSION_TAG:
                    $versionTags[] = $this->_parseVersionTag();
                    break;
                default:
                    throw ParserException::unexpectedToken($this->_screener->getToken(), $this->_comment);
            }
            $this->_screener->skipWhile(Lexer::T_WHITESPACE);
        }
    }

    /**
     * Parses the description of some tag. This method collects all of the inline tags in the first argument which is
     * passed by reference. Inline tags are replaced in the description by "{{{{index-in-array}}}}". This method
     * finishes parsing when the comment ends or the first tag's token was found.
     *
     * @param ATag[] $inlineTags
     *   The inline tags found in the description. Note that this param is passed by reference.
     * @param bool $isInline
     *   Indicates whether we are already parsing an inline-tag's description.
     * @return string
     *   The parsed description
     * @throws ParserException
     *   Unexpected token, unexpected end, or some invalid name
     */
    private function _parseDescription(array &$inlineTags, bool $isInline): string
    {
        $value = "";
        $i = count($inlineTags);
        while($token = $this->_screener->getToken()) {
            $type = $token->getType();
            if(in_array($type, self::$_tags) || $type == Lexer::T_OUTRO || ($isInline && $type == Lexer::T_RCBRACKET)) {
                return $value; //start parsing the (next) tags
            }
            if($type == Lexer::T_LCBRACKET) {
                $this->_screener->resetPeek();
                $next = $this->_screener->peekWhileAny([Lexer::T_WHITESPACE]);
                if($next) {
                    switch($next->getType()) {
                        case Lexer::T_INHERITDOC_TAG:
                            $inlineTags[$i] = $this->_parseInheritDocTag(true);
                            $value .= "{{{{".$i++."}}}}";
                            continue 2;
                        case Lexer::T_INTERNAL_TAG:
                            $inlineTags[$i] = $this->_parseInternalTag(true);
                            $value .= "{{{{".$i++."}}}}";
                            continue 2;
                        case Lexer::T_LINK_TAG:
                            $inlineTags[$i] = $this->_parseLinkTag(true);
                            $value .= "{{{{".$i++."}}}}";
                            continue 2;
                    }
                }
            }
            $value .= $token->getValue();
            $this->_screener->moveNext();
        }
        throw ParserException::unexpectedEnd($this->_comment);
    }

    /**
     * Parses an api-tag.
     *
     * @return ApiTag
     *   The parsed token
     * @throws ParserException
     *   Unexpected token or unexpected end
     */
    private function _parseApiTag(): ApiTag
    {
        $this->_match(Lexer::T_API_TAG);
        return new ApiTag();
    }

    /**
     * Parses an author-tag.
     *
     * @return AuthorTag
     *   The parsed tag
     * @throws ParserException
     *   Unexpected token, unexpected end, or invalid email address
     */
    private function _parseAuthorTag(): AuthorTag
    {
        $this->_match(Lexer::T_AUTHOR_TAG);
        $this->_screener->skipWhile(Lexer::T_WHITESPACE);
        $author = $email = "";
        while($token = $this->_screener->getToken()) {
            $type = $token->getType();
            if(in_array($type, self::$_tags) || $type == Lexer::T_LABRACKET || $type == Lexer::T_OUTRO) {
                break;
            }
            $author .= $token->getValue();
            $this->_screener->moveNext();
        }
        $author = trim($author);

        if($token && $token->getType() == Lexer::T_LABRACKET) {
            $this->_match(Lexer::T_LABRACKET);
            while($token = $this->_screener->getToken()) {
                if($token->getType() == Lexer::T_RABRACKET) {
                    break;
                }
                $email .= $token->getValue();
                $this->_screener->moveNext();
            }
            $email = trim($email);
            if(!$token) {
                throw ParserException::unexpectedEnd($this->_comment);
            } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw ParserException::invalidEmail($email, $this->_comment);
            }
            $this->_match(Lexer::T_RABRACKET);
        }
        return new AuthorTag($author, $email);
    }

    /**
     * Parses a copyright-tag.
     *
     * @return CopyrightTag
     *   The parsed tag
     * @throws ParserException
     *   Unexpected token or unexpected end
     */
    private function _parseCopyrightTag(): CopyrightTag
    {
        $this->_match(Lexer::T_COPYRIGHT_TAG);
        $this->_screener->skipWhile(Lexer::T_WHITESPACE);
        $inlineTags = [];
        $description = $this->_parseDescription($inlineTags, false);
        return new CopyrightTag($description, $inlineTags);
    }

    /**
     * Parses a deprecated-tag.
     *
     * @return DeprecatedTag
     *   The parsed tag
     * @throws ParserException
     *   Unexpected token or unexpected end
     */
    private function _parseDeprecatedTag(): DeprecatedTag
    {
        $this->_match(Lexer::T_DEPRECATED_TAG);
        $this->_screener->skipWhile(Lexer::T_WHITESPACE);
        $position = $this->_screener->getPosition();

        try {
            $version = $this->_parseVersion();
        } catch(ParserException $e) {
            $this->_screener->setPosition($position); //resets the cursor
            $version = "";
        }

        $this->_screener->skipWhile(Lexer::T_WHITESPACE);
        $inlineTags = [];
        $description = $this->_parseDescription($inlineTags, false);

        return new DeprecatedTag($version, $description, $inlineTags);
    }

    /**
     * Parses an inheritDoc-tag.
     *
     * @param bool $isInline
     *   Indicates whether this tag is used inline
     * @return InheritDocTag
     *   The parsed tag
     * @throws ParserException
     *   Unexpected token or unexpected end
     */
    private function _parseInheritDocTag(bool $isInline): InheritDocTag
    {
        if($isInline) {
            $this->_match(Lexer::T_LCBRACKET);
        }
        $this->_screener->skipWhile(Lexer::T_WHITESPACE);
        $this->_match(Lexer::T_INHERITDOC_TAG);
        $this->_screener->skipWhile(Lexer::T_WHITESPACE);
        if($isInline) {
            $this->_match(Lexer::T_RCBRACKET);
        }
        return new InheritDocTag();
    }

    /**
     * Parses an internal-tag.
     *
     * @param bool $isInline
     *   Indicates whether this tag is used inline
     * @return InternalTag
     *   The parsed tag
     * @throws ParserException
     *   Unexpected token or unexpected end
     */
    private function _parseInternalTag(bool $isInline): InternalTag
    {
        if($isInline) {
            $this->_match(Lexer::T_LCBRACKET);
        }
        $this->_screener->skipWhile(Lexer::T_WHITESPACE);
        $this->_match(Lexer::T_INTERNAL_TAG);
        $this->_screener->skipWhile(Lexer::T_WHITESPACE);
        $inlineTags = [];
        $description = $this->_parseDescription($inlineTags, $isInline);
        if($isInline) {
            $this->_match(Lexer::T_RCBRACKET);
        }
        return new InternalTag($description, $inlineTags);
    }

    /**
     * Parses a link-tag.
     *
     * @param bool $isInline
     *   Indicates whether this tag is used inline
     * @return LinkTag
     *   The parsed tag
     * @throws ParserException
     *   Unexpected token, unexpected end, or invalid URI
     */
    private function _parseLinkTag(bool $isInline): LinkTag
    {
        if($isInline) {
            $this->_match(Lexer::T_LCBRACKET);
        }
        $this->_match(Lexer::T_LINK_TAG);
        $this->_screener->skipWhile(Lexer::T_WHITESPACE);
        $link = $this->_parseLink();
        $this->_screener->skipWhile(Lexer::T_WHITESPACE);
        $inlineTags = [];
        $description = $this->_parseDescription($inlineTags, $isInline);
        if($isInline) {
            $this->_match(Lexer::T_RCBRACKET);
        }
        return new LinkTag($link, $description, $inlineTags);
    }

    /**
     * Parses a method-tag.
     *
     * @return MethodTag
     *   The parsed tag
     * @throws ParserException
     *   Unexpected token, unexpected end, invalid method name, or invalid variable name
     */
    private function _parseMethodTag(): MethodTag
    {
        $this->_match(Lexer::T_METHOD_TAG);
        $this->_screener->skipWhile(Lexer::T_WHITESPACE);
        $position = $this->_screener->getPosition();

        try {
            $return = $this->_parseType(false);
            $this->_screener->skipWhile(Lexer::T_WHITESPACE);
        } catch(ParserException $e) {
            $this->_screener->setPosition($position);
            $return = "void";
        }

        //parse method name
        $name = "";
        while($token = $this->_screener->getToken()) {
            if($token->getType() == Lexer::T_LRBRACKET) {
                $this->_match(Lexer::T_LRBRACKET);
                break;
            }
            $name .= $token->getValue();
            $this->_screener->moveNext();
        }
        if(!preg_match('#^[a-z_][a-z0-9_]*$#i', $name)) {
            throw ParserException::invalidMethod($name, $this->_comment);
        }

        //parse arguments
        $this->_screener->skipWhile(Lexer::T_WHITESPACE);
        $args = [];
        while($token = $this->_screener->getToken()) {
            if(!$token || $token->getType() == Lexer::T_RRBRACKET) {
                break;
            }
            $position = $this->_screener->getPosition();
            try {
                $type = $this->_parseType(false);
            } catch(ParserException $e) {
                $this->_screener->setPosition($position);
                $type = "mixed";
            }
            $this->_screener->skipWhile(Lexer::T_WHITESPACE);
            $var = $this->_parseVariable();
            $args[] = [
                'type' => $type,
                'name' => $var
            ];
            $this->_screener->skipWhile(Lexer::T_WHITESPACE);

            $token = $this->_screener->getToken();
            if($token && $token->getType() == Lexer::T_COMMA) {
                $this->_match(Lexer::T_COMMA);
                $this->_screener->skipWhile(Lexer::T_WHITESPACE);
            } else {
                break;
            }
        }
        $this->_match(Lexer::T_RRBRACKET);

        $inlineTags = [];
        $description = $this->_parseDescription($inlineTags, false);

        return new MethodTag($return, $name, $args, $description, $inlineTags);
    }

    /**
     * Parses a package-tag.
     *
     * @return PackageTag
     *   The parsed token
     * @throws ParserException
     *   Unexpected end, unexpected end, or invalid class name
     */
    private function _parsePackageTag(): PackageTag
    {
        $this->_match(Lexer::T_PACKAGE_TAG);
        $this->_screener->skipWhile(Lexer::T_WHITESPACE);
        $name = $this->_parseClassName(); //note that class names and namespaces have similar syntax

        return new PackageTag($name);
    }

    /**
     * Parses a param-tag.
     *
     * @return ParamTag
     *   The parsed token
     * @throws ParserException
     *   Unexpected token, unexpected end, or invalid variable name
     */
    private function _parseParamTag(): ParamTag
    {
        $this->_match(Lexer::T_PARAM_TAG);
        $this->_screener->skipWhile(Lexer::T_WHITESPACE);
        $position = $this->_screener->getPosition();

        try {
            $type = $this->_parseType(false);
            $this->_screener->skipWhile(Lexer::T_WHITESPACE);
        } catch(ParserException $e) {
            $type = "mixed";
            $this->_screener->setPosition($position);
        }

        $inlineTags = [];
        $var = "";
        if($this->_screener->isToken(Lexer::T_DOLLAR)) {
            $var = $this->_parseVariable();
            $this->_screener->skipWhile(Lexer::T_WHITESPACE);
        }
        $description = $this->_parseDescription($inlineTags, false);

        return new ParamTag($type, $var, $description, $inlineTags);
    }

    /**
     * Parses a property-, property-read-, or property-write tag.
     *
     * @return PropertyTag
     *   The parsed tag
     * @throws ParserException
     *   Unexpected token, unexpected name, or invalid variable name
     */
    private function _parsePropertyTag(): PropertyTag
    {
        $token = $this->_screener->getToken();
        if(!$token) {
            throw ParserException::unexpectedEnd($this->_comment);
        }
        switch($token->getType()) {
            case Lexer::T_PROPERTY_TAG:
                $dir = PropertyTag::T_READ | PropertyTag::T_WRITE;
                break;
            case Lexer::T_PROPERTY_READ_TAG:
                $dir = PropertyTag::T_READ;
                break;
            case Lexer::T_PROPERTY_WRITE_TAG:
                $dir = PropertyTag::T_WRITE;
                break;
            default:
                throw ParserException::unexpectedToken($this->_screener->getToken(), $this->_comment);
        }
        $this->_screener->moveNext();

        $this->_screener->skipWhile(Lexer::T_WHITESPACE);
        $position = $this->_screener->getPosition();

        try {
            $type = $this->_parseType(false);
            $this->_screener->skipWhile(Lexer::T_WHITESPACE);
        } catch(ParserException $e) {
            $type = "mixed";
            $this->_screener->setPosition($position);
        }

        $var = $this->_parseVariable();
        $this->_screener->skipWhile(Lexer::T_WHITESPACE);
        $inlineTags = [];
        $description = $this->_parseDescription($inlineTags, false);

        return new PropertyTag($dir, $type, $var, $description, $inlineTags);
    }

    /**
     * Parses a return-tag.
     *
     * @return ReturnTag
     *   The parsed tag
     * @throws ParserException
     *   Unexpected token or unexpected end
     */
    private function _parseReturnTag(): ReturnTag
    {
        $this->_match(Lexer::T_RETURN_TAG);
        $this->_screener->skipWhile(Lexer::T_WHITESPACE);
        $type = $this->_parseType(false);
        $this->_screener->skipWhile(Lexer::T_WHITESPACE);
        $inlineTags = [];
        $description = $this->_parseDescription($inlineTags, false);
        return new ReturnTag($type, $description, $inlineTags);
    }

    /**
     * Parses a see-tag.
     *
     * @return SeeTag
     *   The parsed tag
     * @throws ParserException
     *   Unexpected token, unexpected end, or invalid element name
     */
    private function _parseSeeTag(): SeeTag
    {
        $this->_match(Lexer::T_SEE_TAG);
        $this->_screener->skipWhile(Lexer::T_WHITESPACE);
        $position = $this->_screener->getPosition();

        try {
            $link = $this->_parseLink();
        } catch(ParserException $e) {
            $this->_screener->setPosition($position);
            $link = $this->_parseElement();
        }

        $this->_screener->skipWhile(Lexer::T_WHITESPACE);
        $inlineTags = [];
        $description = $this->_parseDescription($inlineTags, false);

        return new SeeTag($link, $description, $inlineTags);
    }

    /**
     * Parses a since-tag.
     *
     * @return SinceTag
     *   The parsed tag
     * @throws ParserException
     *   Unexpected token, unexpected end, or invalid version name
     */
    private function _parseSinceTag(): SinceTag
    {
        $this->_match(Lexer::T_SINCE_TAG);
        $this->_screener->skipWhile(Lexer::T_WHITESPACE);
        $version = $this->_parseVersion();
        $this->_screener->skipWhile(Lexer::T_WHITESPACE);
        $inlineTags = [];
        $description = $this->_parseDescription($inlineTags, false);
        return new SinceTag($version, $description, $inlineTags);
    }

    /**
     * Parses a throws-tag.
     *
     * @return ThrowsTag
     *   The parsed tag
     * @throws ParserException
     *   Unexpected token or unexpected end
     */
    private function _parseThrowsTag(): ThrowsTag
    {
        $this->_match(Lexer::T_THROWS_TAG);
        $this->_screener->skipWhile(Lexer::T_WHITESPACE);
        $type = $this->_parseClassName();
        $this->_screener->skipWhile(Lexer::T_WHITESPACE);
        $inlineTags = [];
        $description = $this->_parseDescription($inlineTags, false);
        return new ThrowsTag($type, $description, $inlineTags);
    }

    /**
     * Parses a todo-tag.
     *
     * @return TodoTag
     *   The parsed tag
     * @throws ParserException
     *   Unexpected token or unexpected end
     */
    private function _parseTodoTag(): TodoTag
    {
        $this->_match(Lexer::T_TODO_TAG);
        $this->_screener->skipWhile(Lexer::T_WHITESPACE);
        $inlineTags = [];
        $description = $this->_parseDescription($inlineTags, false);
        return new TodoTag($description, $inlineTags);
    }

    /**
     * Parses a uses-tag.
     *
     * @return UsesTag
     *   The parsed tag
     * @throws ParserException
     *   Unexpected token, unexpected end, or invalid file name
     */
    private function _parseUsesTag(): UsesTag
    {
        $this->_match(Lexer::T_USES_TAG);
        $this->_screener->skipWhile(Lexer::T_WHITESPACE);
        $position = $this->_screener->getPosition();

        try {
            $link = $this->_parseElement();
        } catch(ParserException $e) {
            $this->_screener->setPosition($position);
            $link = "";
            while($token = $this->_screener->getToken()) {
                if($token->getType() == Lexer::T_WHITESPACE) {
                    break;
                }
                $link .= $token->getValue();
                $this->_screener->moveNext();
            }

            if(!preg_match('#^[a-z0-9./_\\-]*$#i', $link)) {
                throw ParserException::invalidFile($link, $this->_comment);
            }
        }

        $this->_screener->skipWhile(Lexer::T_WHITESPACE);
        $inlineTags = [];
        $description = $this->_parseDescription($inlineTags, false);
        return new UsesTag($link, $description, $inlineTags);
    }

    /**
     * Parses a var-tag.
     *
     * @return VarTag
     *   The parsed tag
     * @throws ParserException
     *   Unexpected token, unexpected end, or invalid variable name
     */
    private function _parseVarTag(): VarTag
    {
        $this->_match(Lexer::T_VAR_TAG);
        $this->_screener->skipWhile(Lexer::T_WHITESPACE);
        $position = $this->_screener->getPosition();

        try {
            $type = $this->_parseType(false);
            $this->_screener->skipWhile(Lexer::T_WHITESPACE);
        } catch(ParserException $e) {
            $type = "mixed";
            $this->_screener->setPosition($position);
        }

        $inlineTags = [];
        $var = "";
        if($this->_screener->isToken(Lexer::T_DOLLAR)) {
            $var = $this->_parseVariable();
            $this->_screener->skipWhile(Lexer::T_WHITESPACE);
        }
        $description = $this->_parseDescription($inlineTags, false);

        return new VarTag($type, $var, $description, $inlineTags);
    }

    /**
     * Parses a version-tag.
     *
     * @return VersionTag
     *   The parsed tag
     * @throws ParserException
     *   Unexpected token, unexpected end, or invalid version name
     */
    private function _parseVersionTag(): VersionTag
    {
        $this->_match(Lexer::T_VERSION_TAG);
        $this->_screener->skipWhile(Lexer::T_WHITESPACE);
        $version = $this->_parseVersion();
        $this->_screener->skipWhile(Lexer::T_WHITESPACE);
        $inlineTags = [];
        $description = $this->_parseDescription($inlineTags, false);

        return new VersionTag($version, $description, $inlineTags);
    }

    /**
     * Parses a type expression {@link https://github.com/php-fig/fig-standards/blob/master/proposed/phpdoc.md#appendix-a-types}.
     *
     * @param bool $recursive
     *   true if this is a recursive call of this method, false otherwise
     * @return string
     *   The parsed type expression
     * @throws ParserException
     *   Unexpected token or unexpected end
     */
    private function _parseType(bool $recursive): string
    {
        if($recursive) {
            $this->_match(Lexer::T_LRBRACKET);
        }

        $string = "";
        while($token = $this->_screener->getToken()) {
            switch($token->getType()) {
                case Lexer::T_WHITESPACE:
                case Lexer::T_OUTRO:
                    if($recursive || in_array(substr($string, -1), ["&", "|"])) {
                        throw ParserException::unexpectedToken($token, $this->_comment);
                    }
                    break 2;
                case Lexer::T_LRBRACKET:
                    $string .= $this->_parseType(true);
                    break;
                case Lexer::T_DOLLAR:
                    $this->_match(Lexer::T_DOLLAR);
                    $next = $this->_screener->getToken();
                    if(!$next || trim(strtolower($next->getValue())) != "this") {
                        throw ParserException::unexpectedToken($next, $this->_comment);
                    }
                    $string .= '$this';
                    $this->_screener->moveNext();
                    break;
                //parse class name
                case Lexer::T_BACKSLASH:
                case Lexer::T_STRING:
                    $string .= $this->_parseClassName();
                    break;
                default:
                    throw ParserException::unexpectedToken($token, $this->_comment);
            }

            $next = $this->_screener->getToken();
            while($next && $next->getType() == Lexer::T_LSBRACKET) {
                $this->_match(Lexer::T_LSBRACKET);
                $this->_match(Lexer::T_RSBRACKET);
                $string .= "[]";
                $next = $this->_screener->getToken();
            }
            if(!$next || in_array($next->getType(), [Lexer::T_WHITESPACE, Lexer::T_OUTRO]) || ($recursive && $next->getType() == Lexer::T_RRBRACKET)) {
                break;
            } elseif(!in_array($next->getType(), [Lexer::T_AMP, Lexer::T_PIPE])) {
                throw ParserException::unexpectedToken($next, $this->_comment);
            }
            $string .= $next->getValue();
            $this->_screener->moveNext();
        }

        if($recursive) {
            $this->_match(Lexer::T_RRBRACKET);
            $string = "({$string})";
        }
        return $string;
    }

    /**
     * Parses a class name.
     *
     * @return string
     *   The parsed class name
     * @throws ParserException
     *   The parsed string was no class name
     */
    private function _parseClassName(): string
    {
        $className = "";
        do {
            $token = $this->_screener->getToken();
            if(!in_array($token->getType(), [Lexer::T_STRING, Lexer::T_BACKSLASH])) {
                break;
            }
            $className .= $token->getValue();
        } while($this->_screener->moveNext());

        if(!preg_match('#^((\\\\|)[a-z_][a-z0-9_]*)+$#i', $className)) {
            throw ParserException::invalidType($className, $this->_comment);
        }
        return $className;
    }

    /**
     * Parses a variable's name.
     *
     * @return string
     *   The parsed variable
     * @throws ParserException
     *   The parsed string was no variable
     */
    private function _parseVariable(): string
    {
        $this->_match(Lexer::T_DOLLAR);
        $name = "$";
        $token = $this->_screener->getToken();
        while($token && $token->getType() == Lexer::T_STRING) {
            $name .= $token->getValue();
            $this->_screener->moveNext();
            $token = $this->_screener->getToken();
        }

        if(!preg_match('#^\$[a-z_][a-z0-9_]*$#i', $name)) {
            throw ParserException::invalidVariable($name, $this->_comment);
        }

        return $name;
    }

    /**
     * Parses a semantic version name. See the definition given in PSR-19
     * {@link https://github.com/php-fig/fig-standards/blob/master/proposed/phpdoc-tags.md#518-version}.
     *
     * @return string
     *   The parsed version name
     * @throws ParserException
     *   The parsed string was no version name
     */
    private function _parseVersion(): string
    {
        $version = "";
        while($token = $this->_screener->getToken()) {
            if(in_array($token->getType(), [Lexer::T_WHITESPACE, Lexer::T_OUTRO])) {
                break;
            }
            $version .= $token->getValue();
            $this->_screener->moveNext();
        }

        if(!preg_match('#^((v|)[0-9]+[a-z0-9.\-_]+|@[a-z0-9.\-_]+@|\$[a-z0-9.\-_]+\$|[a-z0-9.\-_]+:)$#i', $version)) {
            throw ParserException::invalidVersion($version, $this->_comment);
        }
        if(substr($version, -1) == ":") {
            $this->_screener->skipWhile(Lexer::T_WHITESPACE);
            $this->_match(Lexer::T_DOLLAR);
            $version .= " $";
            while($token = $this->_screener->getToken()) {
                $version .= $token->getValue();
                if($token->getType() == Lexer::T_DOLLAR) {
                    $this->_match(Lexer::T_DOLLAR);
                    break;
                }
                $this->_screener->moveNext();
            }
            if(!$token) {
                throw ParserException::unexpectedEnd($this->_comment);
            }
        }
        return $version;
    }

    /**
     * Parses an URI.
     *
     * @return string
     *   The returns URI
     * @throws ParserException
     *   The parsed string was no URI
     */
    private function _parseLink(): string
    {
        $link = "";
        while($token = $this->_screener->getToken()) {
            if(in_array($token->getType(), [Lexer::T_WHITESPACE, Lexer::T_OUTRO, Lexer::T_RCBRACKET])) {
                break;
            }
            $link .= $token->getValue();
            $this->_screener->moveNext();
        }

        if(!filter_var($link, FILTER_VALIDATE_URL)) {
            throw ParserException::invalidLink($link, $this->_comment);
        }
        return $link;
    }

    /**
     * Parses the name of some element, i.e., a variable, class name, namespace, function, method, property, or
     * constant.
     *
     * @return string
     *   The parsed element's name
     * @throws ParserException
     *   The parsed string is no valid name of some element
     */
    private function _parseElement(): string
    {
        $name = "";
        while($token = $this->_screener->getToken()) {
            if(in_array($token->getType(), [Lexer::T_WHITESPACE, Lexer::T_OUTRO])) {
                break;
            }
            $name .= $token->getValue();
            $this->_screener->moveNext();
        }

        if(!preg_match('#^((((\\\\|)[a-z_][a-z0-9_]*)+::|)((\\$|)[a-z_][a-z0-9_]*|[a-z_][a-z0-9_]*\\(\\))|((\\\\|)[a-z_][a-z0-9_]*)+(\\(\\)|))$#i', $name)) {
            throw ParserException::invalidElement($name, $this->_comment);
        }
        return $name;
    }

    /**
     * Checks whether the current token from the screener matches the given token type, moves the pointer ahead, and
     * returns the matched token.
     *
     * @param int $type
     *   The type of the token given in Lexer
     * @return Token
     *   The matched token
     * @throws ParserException
     *   There is no token to match or the tokens do not match
     */
    private function _match(int $type): Token
    {
        $token = $this->_screener->getToken();
        if($token === null) {
            throw ParserException::unexpectedEnd($this->_comment);
        }
        if($token->getType() != $type) {
            throw ParserException::unexpectedToken($token, $this->_comment);
        }
        $this->_screener->moveNext();
        return $token;
    }
}