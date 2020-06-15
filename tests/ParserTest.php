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

namespace Gustav\PhpDocParser\Tests;

use Gustav\PhpDocParser\Data\ClassData;
use Gustav\PhpDocParser\Data\ConstantData;
use Gustav\PhpDocParser\Data\MethodData;
use Gustav\PhpDocParser\Data\PropertyData;
use Gustav\PhpDocParser\Parser;
use Gustav\PhpDocParser\ParserException;
use Gustav\PhpDocParser\Screener;
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
use Gustav\PhpDocParser\Tags\UsedByTag;
use Gustav\PhpDocParser\Tags\UsesTag;
use Gustav\PhpDocParser\Tags\VarTag;
use Gustav\PhpDocParser\Tags\VersionTag;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    private Parser $_parser;
    private \ReflectionClass $_reflection;
    private \ReflectionProperty $_screener;
    private \ReflectionProperty $_comment;

    public function setUp(): void
    {
        parent::setUp();
        $this->_parser = new Parser;
        $this->_reflection = new \ReflectionClass($this->_parser);
        $this->_screener = $this->_reflection->getProperty("_screener");
        $this->_screener->setAccessible(true);
        $this->_comment = $this->_reflection->getProperty("_comment");
        $this->_comment->setAccessible(true);
    }

    public function testParseType(): void
    {
        $goodStrings = [
            ["(\\foo\\bar|baz)[]&\\lorem\\ipsum", "(\\foo\\bar|baz)[]&\\lorem\\ipsum", [false]],
            ["int", "int", [false]],
            [Parser::CLASS, Parser::CLASS, [false]],
            ["string[][]", "string[][]", [false]],
            ["(int|string)&((test[][]|foo[])&\\_bar)", "(int|string)&((test[][]|foo[])&\\_bar)", [false]],
            ["\$this", "\$this", [false]]
        ];
        $badStrings = [
            ["(test", [false]],
            ["123test", [false]],
            ["foo||bar", [false]],
            ["foo[", [false]],
            ["\\foo\\bar::baz()", [false]],
            ["\$test", [false]]
        ];

        $this->_testMethod("_parseType", $goodStrings, $badStrings);
    }

    public function testParseClassName(): void
    {
        $goodStrings = [
            ["int", "int"],
            ["Gustav\\PhpDocParser\\Tests\\ParserTest", "Gustav\\PhpDocParser\\Tests\\ParserTest"],
            ["\\Gustav\\PhpDocParser\\Tests\\ParserTest", "\\Gustav\\PhpDocParser\\Tests\\ParserTest"],
            ["_test", "_test"],
            ["test123", "test123"]
        ];
        $badStrings = [
            ["\\foo\\123test"],
            ["123test"],
            ["foo\\\\bar"]
        ];

        $this->_testMethod("_parseClassName", $goodStrings, $badStrings);
    }

    public function testParseVariable(): void
    {
        $goodStrings = [
            ["\$this", "\$this"],
            ["\$fooBar", "\$fooBar"],
            ["\$_foo", "\$_foo"],
            ["\$bar2", "\$bar2"]
        ];
        $badStrings = [
            ["foo"],
            ["\$1foo"],
            ["\$foo+bar"]
        ];

        $this->_testMethod("_parseVariable", $goodStrings, $badStrings);
    }

    public function testParseVersion(): void
    {
        $goodStrings = [
            ["0.0.10", "0.0.10"],
            ["3.14159265359", "3.14159265359"],
            ["1.0a123", "1.0a123"],
            ["1.0-a123", "1.0-a123"],
            ["git: \$12345abc\$", "git: \$12345abc\$"],
            ["@package_version@", "@package_version@"],
            ["v1.0.0", "v1.0.0"]
        ];
        $badStrings = [
            ["test"],
            ["git: 1.0"],
            ["git \$12345abc\$"]
        ];

        $this->_testMethod("_parseVersion", $goodStrings, $badStrings);
    }

    public function testParseLink(): void
    {
        $goodStrings = [
            ["https://www.fieselschweif.de", "https://www.fieselschweif.de"],
            ["http://www.fieselschweif.de/ueberuns/#top", "http://www.fieselschweif.de/ueberuns/#top"],
            ["https://news.fieselschweif.de/?a=CKOne", "https://news.fieselschweif.de/?a=CKOne"],
            ["ftp://my-ftp.example", "ftp://my-ftp.example"],
            ["http://localhost", "http://localhost"],
            ["https://123.0.0.1:1234", "https://123.0.0.1:1234"],
            ["https://[2001:db8:0:8d3::8a2e:70:7344]:1234", "https://[2001:db8:0:8d3::8a2e:70:7344]:1234"]
        ];
        $badStrings = [
            ["test"],
            ["http//www.fieselschweif.de"],
            ["https:www.fieselschweif.de"],
            ["https://"]
        ];

        $this->_testMethod("_parseLink", $goodStrings, $badStrings);
    }

    public function testParseElement(): void
    {
        $goodStrings = [
            ["\\foo\\bar", "\\foo\\bar"],
            ["Gustav\\PhpDocParser\\Tests\\ParserTest::METHODS_PUBLIC", "Gustav\\PhpDocParser\\Tests\\ParserTest::METHODS_PUBLIC"],
            ["Gustav\\PhpDocParser\\Tests\\ParserTest::testParseElement()", "Gustav\\PhpDocParser\\Tests\\ParserTest::testParseElement()"],
            ["\\Gustav\\PhpDocParser\\Tests\\ParserTest::\$_parser", "\\Gustav\\PhpDocParser\\Tests\\ParserTest::\$_parser"],
            ["\$this", "\$this"],
            ["self", "self"],
            ["my_function()", "my_function()"],
            ["_test", "_test"]
        ];
        $badStrings = [
            ["123foo"],
            ["\\Gustav\\\\PhpDocParser"],
            ["\$123abc"],
            ["123abc()"],
            ["\\Gustav\\123Parser::CLASS"],
            ["\\Gustav\\PhpDocParser\\Tests\\ParserTest:\$_parser"],
            ["\\Gustav\\PhpDocParser\\Tests\\ParserTest::\$_parser()"],
            ["\\Gustav\\PhpDocParser\\Tests\\ParserTest::Foo\\Bar"]
        ];

        $this->_testMethod("_parseElement", $goodStrings, $badStrings);
    }

    public function testParseApiTag(): void
    {
        $goodStrings = [
            ["@api", new ApiTag()]
        ];
        $badStrings = [
            ["test"],
            ["@author Chris Köcher <ckone@fieselschweif.de>"]
        ];

        $this->_testMethod("_parseApiTag", $goodStrings, $badStrings);
    }

    public function testParseAuthorTag(): void
    {
        $goodStrings = [
            ["@author Chris Köcher <ckone@fieselschweif.de>", new AuthorTag("Chris Köcher", "ckone@fieselschweif.de")],
            ["@author Chris Köcher", new AuthorTag("Chris Köcher", "")],
            ["@author <ckone@fieselschweif.de>", new AuthorTag("", "ckone@fieselschweif.de")]
        ];
        $badStrings = [
            ["@author Chris Köcher <no-mail>"],
            ["@api Chris Köcher"],
            ["@author Chris Köcher <ckone@fieselschweif.de"]
        ];

        $this->_testMethod("_parseAuthorTag", $goodStrings, $badStrings);
    }

    public function testParseCopyrightTag(): void
    {
        $goodStrings = [
            ["@copyright Gustav Software", new CopyrightTag("Gustav Software", [])],
            ["@copyright Gustav Software {@link https://gustav.fieselschweif.de}", new CopyrightTag(
                "Gustav Software {{{{0}}}}",
                [new LinkTag("https://gustav.fieselschweif.de", "", [])]
            )]
        ];
        $badStrings = [
            ["@api"]
        ];

        $this->_testMethod("_parseCopyrightTag", $goodStrings, $badStrings);
    }

    public function testParseDeprecatedTag(): void
    {
        $goodStrings = [
            ["@deprecated", new DeprecatedTag("", "", [])],
            ["@deprecated 1.0.0", new DeprecatedTag("1.0.0", "", [])],
            ["@deprecated Some description", new DeprecatedTag("", "Some description", [])],
            ["@deprecated 1.0.0 Some description", new DeprecatedTag("1.0.0", "Some description", [])]
        ];
        $badStrings = [
            ["@api"]
        ];

        $this->_testMethod("_parseDeprecatedTag", $goodStrings, $badStrings);
    }

    public function testParseInheritDocTag(): void
    {
        $goodStrings = [
            ["@inheritDoc", new InheritDocTag(), [false]],
            ["{@inheritDoc}", new InheritDocTag(), [true]]
        ];
        $badStrings = [
            ["@api", [false]],
            ["@api", [true]],
            ["{@inheritDoc}", [false]],
            ["@inheritDoc", [true]]
        ];

        $this->_testMethod("_parseInheritDocTag", $goodStrings, $badStrings);
    }

    public function testParseInternalTag(): void
    {
        $goodStrings = [
            ["@internal Some description", new InternalTag("Some description", []), [false]],
            ["{@internal Some description}", new InternalTag("Some description", []), [true]],
            ["{@internal Some description {@link https://www.fieselschweif.de Text}}", new InternalTag(
                "Some description {{{{0}}}}",
                [new LinkTag("https://www.fieselschweif.de", "Text", [])]
            ), [true]]
        ];
        $badStrings = [
            ["@api", [false]],
            ["@internal Some description", [true]],
            ["{@internal Some description}", [false]],
            ["{@internal Some description {@link https://www.fieselschweif.de Text}", [true]]
        ];

        $this->_testMethod("_parseInternalTag", $goodStrings, $badStrings);
    }

    public function testParseLinkTag(): void
    {
        $goodStrings = [
            ["@link https://www.fieselschweif.de", new LinkTag("https://www.fieselschweif.de", "", []), [false]],
            ["{@link https://www.fieselschweif.de}", new LinkTag("https://www.fieselschweif.de", "", []), [true]],
            ["@link https://www.fieselschweif.de Some description", new LinkTag("https://www.fieselschweif.de", "Some description", []), [false]],
            ["{@link https://www.fieselschweif.de Some description}", new LinkTag("https://www.fieselschweif.de", "Some description", []), [true]]
        ];
        $badStrings = [
            ["@api", [false]],
            ["@link Some description", [false]],
            ["{@link Some description}", [true]],
            ["@link https://www.fieselschweif.de", [true]],
            ["{@link https://www.fieselschweif.de}", [false]]
        ];

        $this->_testMethod("_parseLinkTag", $goodStrings, $badStrings);
    }

    public function testParseMethodTag(): void
    {
        $goodStrings = [
            ["@method \\foo\\bar myMethod() Some description", new MethodTag("\\foo\\bar", "myMethod", [], "Some description", [])],
            ["@method myMethod() Some description", new MethodTag("void", "myMethod", [], "Some description", [])],
            [
                "@method \\foo\\bar myMethod(int \$test, string \$test2) Some description",
                new MethodTag("\\foo\\bar", "myMethod", [
                    ['type' => "int", 'name' => "\$test"],
                    ['type' => "string", 'name' => "\$test2"]
                ], "Some description", [])
            ],
            [
                "@method \\foo\\bar myMethod(int|string[] \$test) Some description",
                new MethodTag("\\foo\\bar", "myMethod", [
                    ['type' => "int|string[]", 'name' => "\$test"]
                ], "Some description", [])
            ],
            [
                "@method \\foo\\bar myMethod(\$test, \$test2) Some description",
                new MethodTag("\\foo\\bar", "myMethod", [
                    ['type' => "mixed", 'name' => "\$test"],
                    ['type' => "mixed", 'name' => "\$test2"]
                ], "Some description", [])
            ],
            ["@method myMethod()", new MethodTag("void", "myMethod", [], "", [])],
            [
                "@method \\foo\\bar myMethod(\$test, \$test2,) Some description",
                new MethodTag("\\foo\\bar", "myMethod", [
                    ['type' => "mixed", 'name' => "\$test"],
                    ['type' => "mixed", 'name' => "\$test2"]
                ], "Some description", [])
            ]
        ];
        $badStrings = [
            ["@api"],
            ["@method \\foo\\bar"],
            ["@method \\foo\\bar myMethod"],
            ["@method \\foo\\bar myMethod(int \$test"],
            ["@method \\foo\\bar myMethod(int test)"]
        ];

        $this->_testMethod("_parseMethodTag", $goodStrings, $badStrings);
    }

    public function testParsePackageTag(): void
    {
        $goodStrings = [
            ["@package _foo", new PackageTag("_foo")],
            ["@package \\foo\\bar", new PackageTag("\\foo\\bar")],
            ["@package \\foo\\bar123", new PackageTag("\\foo\\bar123")]
        ];
        $badStrings = [
            ["@api"],
            ["@package \\foo\\123bar"],
            ["@package foo\\\\bar"]
        ];

        $this->_testMethod("_parsePackageTag", $goodStrings, $badStrings);
    }

    public function testParseParamTag(): void
    {
        $goodStrings = [
            ["@param \\foo\\bar \$baz Some description", new ParamTag("\\foo\\bar", "\$baz", "Some description", [])],
            ["@param int|string[] \$baz Some description", new ParamTag("int|string[]", "\$baz", "Some description", [])],
            ["@param \$baz Some description", new ParamTag("mixed", "\$baz", "Some description", [])],
            ["@param \$baz", new ParamTag("mixed", "\$baz", "", [])],
            ["@param \\foo\\bar Some description", new ParamTag("\\foo\\bar", "", "Some description", [])]
        ];
        $badStrings = [
            ["@api"]
        ];

        $this->_testMethod("_parseParamTag", $goodStrings, $badStrings);
    }

    public function testParsePropertyTag(): void
    {
        $goodStrings = [
            ["@property \\foo\\bar \$baz Some description", new PropertyTag(
                PropertyTag::T_READ|PropertyTag::T_WRITE, "\\foo\\bar", "\$baz", "Some description", []
            )],
            ["@property-read \\foo\\bar \$baz Some description", new PropertyTag(
                PropertyTag::T_READ, "\\foo\\bar", "\$baz", "Some description", []
            )],
            ["@property-write \\foo\\bar \$baz Some description", new PropertyTag(
                PropertyTag::T_WRITE, "\\foo\\bar", "\$baz", "Some description", []
            )],
            ["@property \$baz Some description", new PropertyTag(
                PropertyTag::T_READ|PropertyTag::T_WRITE, "mixed", "\$baz", "Some description", []
            )]
        ];
        $badStrings = [
            ["@api"],
            ["@property \\foo\\bar Some description"],
            ["@property foo\\\\bar \$baz Some description"]
        ];

        $this->_testMethod("_parsePropertyTag", $goodStrings, $badStrings);
    }

    public function testParseReturnTag(): void
    {
        $goodStrings = [
            ["@return \\foo\\bar Some description", new ReturnTag("\\foo\\bar", "Some description", [])],
            ["@return int|string[] Some description", new ReturnTag("int|string[]", "Some description", [])],
            ["@return \\foo\\bar", new ReturnTag("\\foo\\bar", "", [])]
        ];
        $badStrings = [
            ["@api"],
            ["@return 123test Some description"]
        ];

        $this->_testMethod("_parseReturnTag", $goodStrings, $badStrings);
    }

    public function testParseSeeTag(): void
    {
        $goodStrings = [
            ["@see \\foo\\bar::\$_string", new SeeTag("\\foo\\bar::\$_string", "", [])],
            ["@see \\test\\myFunction() Some description", new SeeTag("\\test\\myFunction()", "Some description", [])],
            ["@see https://www.fieselschweif.de Some description", new SeeTag("https://www.fieselschweif.de", "Some description", [])]
        ];
        $badStrings = [
            ["@api"],
            ["@see \\123test\\foo Some description"],
            ["@see https//www.fieselschweif.de"]
        ];

        $this->_testMethod("_parseSeeTag", $goodStrings, $badStrings);
    }

    public function testParseSinceTag(): void
    {
        $goodStrings = [
            ["@since 1.0.0 Some description", new SinceTag("1.0.0", "Some description", [])],
            ["@since git: $123abc$ Some description", new SinceTag("git: $123abc$", "Some description", [])],
            ["@since @package_version@ Some description", new SinceTag("@package_version@", "Some description", [])],
            ["@since v1.0.0", new SinceTag("v1.0.0", "", [])]
        ];
        $badStrings = [
            ["@api"],
            ["@since Some description"],
            ["@since git: 123 Some description"]
        ];

        $this->_testMethod("_parseSinceTag", $goodStrings, $badStrings);
    }

    public function testParseThrowsTag(): void
    {
        $goodStrings = [
            ["@throws MyException Some description", new ThrowsTag("MyException", "Some description", [])],
            ["@throws \\foo\\MyException Some description", new ThrowsTag("\\foo\\MyException", "Some description", [])],
            ["@throws MyException", new ThrowsTag("MyException", "", [])]
        ];
        $badString = [
            ["@api"],
            ["@throws 123abc Some description"]
        ];

        $this->_testMethod("_parseThrowsTag", $goodStrings, $badString);
    }

    public function testParseTodoTag(): void
    {
        $goodStrings = [
            ["@todo", new TodoTag("", [])],
            ["@todo Some description", new TodoTag("Some description", [])]
        ];
        $badStrings = [
            ["@api"]
        ];

        $this->_testMethod("_parseTodoTag", $goodStrings, $badStrings);
    }

    public function testParseUsedByTag(): void
    {
        $goodStrings = [
            ["@used-by /some/path/to/file.php Some description", new UsedByTag("/some/path/to/file.php", "Some description", [])],
            ["@used-by \\foo\\bar Some description", new UsedByTag("\\foo\\bar", "Some description", [])],
            ["@used-by foo::\$_bar Some description", new UsedByTag("foo::\$_bar", "Some description", [])],
            ["@used-by /some/path/to/file.php", new UsedByTag("/some/path/to/file.php", "", [])]
        ];
        $badStrings = [
            ["@api"],
            ["@used-by foo::\$_bar() Some description"]
        ];

        $this->_testMethod("_parseUsedByTag", $goodStrings, $badStrings);
    }

    public function testParseUsesTag(): void
    {
        $goodStrings = [
            ["@uses /some/path/to/file.php Some description", new UsesTag("/some/path/to/file.php", "Some description", [])],
            ["@uses \\foo\\bar Some description", new UsesTag("\\foo\\bar", "Some description", [])],
            ["@uses foo::\$_bar Some description", new UsesTag("foo::\$_bar", "Some description", [])],
            ["@uses /some/path/to/file.php", new UsesTag("/some/path/to/file.php", "", [])]
        ];
        $badStrings = [
            ["@api"],
            ["@uses foo::\$_bar() Some description"]
        ];

        $this->_testMethod("_parseUsesTag", $goodStrings, $badStrings);
    }

    public function testParseVarTag(): void
    {
        $goodStrings = [
            ["@var int", new VarTag("int", "", "", [])],
            ["@var int|string[]", new VarTag("int|string[]", "", "", [])],
            ["@var int|string[] Some description", new VarTag("int|string[]", "", "Some description", [])],
            ["@var int|string[] \$foo", new VarTag("int|string[]", "\$foo", "", [])],
            ["@var int|string[] \$foo Some description", new VarTag("int|string[]", "\$foo", "Some description", [])],
            ["@var \$foo Some description", new VarTag("mixed", "\$foo", "Some description", [])]
        ];
        $badStrings = [
            ["@api"]
        ];

        $this->_testMethod("_parseVarTag", $goodStrings, $badStrings);
    }

    public function testVersionTag(): void
    {
        $goodStrings = [
            ["@version 1.0.0 Some description", new VersionTag("1.0.0", "Some description", [])],
            ["@version 1.0.0", new VersionTag("1.0.0", "", [])],
            ["@version git: $123abc$ Some description", new VersionTag("git: $123abc$", "Some description", [])]
        ];
        $badStrings = [
            ["@api"],
            ["@version foo Some description"]
        ];

        $this->_testMethod("_parseVersionTag", $goodStrings, $badStrings);
    }

    public function testParseConstant(): void
    {
        $reflection = new \ReflectionClassConstant(Parser::CLASS, "METHODS_PUBLIC");
        $data = $this->_parser->parseConstant($reflection);

        $this->assertEquals(new ConstantData(
            $reflection, new VarTag("int", "", "", []), "Some constants to be used to filter methods, properties, and constants of classes and interfaces."
        ), $data);
    }

    public function testProperty(): void
    {
        $reflection = new \ReflectionProperty(Parser::CLASS, "_tags");
        $data = $this->_parser->parseProperty($reflection);

        $this->assertEquals(new PropertyData(
            $reflection, new VarTag("int[]", "", "", []), "This array contains all tag types."
        ), $data);
    }

    public function testMethod(): void
    {
        $reflection = new \ReflectionMethod(Parser::CLASS, "parseMethod");
        $data = $this->_parser->parseMethod($reflection);

        $this->assertEquals(new MethodData(
            $reflection, "Parses the PHPDoc comment of a single method.", [], [
                new ParamTag("ReflectionMethod|string|object", "\$method", "Either the reflection of a method or the containing class name or object to consider", []),
                new ParamTag("string", "\$name", "The name of the method to consider if the first argument is no reflection", [])
            ], new ReturnTag("MethodData", "The parsed data", []), [
                new ThrowsTag("ParserException", "Unexpected tokens or an invalid name was found while parsing", []),
                new ThrowsTag("ReflectionException", "Some error occurred while handling the reflection of this constant", [])
            ], new ApiTag()
        ), $data);
    }

    public function testClass(): void
    {
        $reflection = new \ReflectionClass(ATag::CLASS);
        $data = $this->_parser->parseClass($reflection);

        $this->assertEquals(new ClassData(
            $reflection, [], [
                new MethodData(
                    new \ReflectionMethod(ATag::CLASS, "__construct"),
                    "Constructor of this class.", [], [
                        new ParamTag("string", "\$description", "The description", []),
                        new ParamTag("ATag[]", "\$inlineTags", "The inline tags", [])
                    ]
                ),
                new MethodData(
                    new \ReflectionMethod(ATag::CLASS, "getDescription"),
                    "Returns the description of this tag. Note that you can find placeholders \"{{{{number}}}}\" at position \"number\"\nin self::\$_inlineTags.", [], [],
                    new ReturnTag("string", "The description", [])
                ),
                new MethodData(
                    new \ReflectionMethod(ATag::CLASS, "getInlineTags"),
                    "Returns the inline tags. You can find a placeholder \"{{{{key-of-element}}}}\" at the positions of the inline tags\nin the description.", [], [],
                    new ReturnTag("ATag[]", "The inline tags", [])
                )
            ], [], [
                new PropertyData(
                    new \ReflectionProperty(ATag::CLASS, "_description"),
                    new VarTag("string", "", "", []), "The description of this tag."
                ),
                new PropertyData(
                    new \ReflectionProperty(ATag::CLASS, "_inlineTags"),
                    new VarTag("ATag[]", "", "", []),
                    "A list of inline tags in the description of this tag. You can find a placeholder \"{{{{key-of-element}}}}\" at the\npositions of the inline tags in the description."
                ),
            ], [], null, "An abstract class for some PHPDoc-tag.", [], null, [
                new AuthorTag("Chris Köcher", "ckone@fieselschweif.de")
            ], [], null, null, [], [
                new LinkTag("https://gustav.fieselschweif.de", "", [])
            ], [], [
                new SinceTag("1.0.0", "", [])
            ]
        ), $data);
    }

    private function _testMethod(string $name, array $goodStrings, array $badStrings): void
    {
        $method = $this->_reflection->getMethod($name);
        $method->setAccessible(true);

        foreach($goodStrings as $string) {
            $this->_screener->setValue($this->_parser, new Screener($string[0]." */")); //note: the type parser stops on first whitespace or outro!
            $this->_comment->setValue($this->_parser, $string[0]." */");
            if(isset($string[2]) && is_array($string[2])) {
                $value = $method->invokeArgs($this->_parser, $string[2]);
            } else {
                $value = $method->invokeArgs($this->_parser, []);
            }
            $this->assertEquals($string[1], $value, $string[0]);
        }

        foreach($badStrings as $string) {
            $this->_screener->setValue($this->_parser, new Screener($string[0]." */")); //note: the type parser stops on first whitespace or outro!
            $this->_comment->setValue($this->_parser, $string[0]." */");
            $catched = false;
            try {
                if(isset($string[1]) && is_array($string[1])) {
                    $method->invokeArgs($this->_parser, $string[1]);
                } else {
                    $method->invokeArgs($this->_parser, []);
                }
            } catch(ParserException $e) {
                $catched = true;
            }
            $this->assertTrue($catched, $string[0]);
        }
    }
}
