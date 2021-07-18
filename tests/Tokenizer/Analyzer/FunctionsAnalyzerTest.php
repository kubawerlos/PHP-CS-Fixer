<?php

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpCsFixer\Tests\Tokenizer\Analyzer;

use PhpCsFixer\Tests\TestCase;
use PhpCsFixer\Tokenizer\Analyzer\Analysis\ArgumentAnalysis;
use PhpCsFixer\Tokenizer\Analyzer\Analysis\TypeAnalysis;
use PhpCsFixer\Tokenizer\Analyzer\FunctionsAnalyzer;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author VeeWee <toonverwerft@gmail.com>
 *
 * @internal
 *
 * @covers \PhpCsFixer\Tokenizer\Analyzer\FunctionsAnalyzer
 */
final class FunctionsAnalyzerTest extends TestCase
{
    /**
     * @param string $code
     * @param int[]  $indices
     *
     * @dataProvider provideIsGlobalFunctionCallCases
     */
    public function testIsGlobalFunctionCall($code, $indices)
    {
        self::assertIsGlobalFunctionCall($code, $indices);
    }

    public function provideIsGlobalFunctionCallCases()
    {
        yield [
            '<?php CONSTANT;',
            [],
        ];

        yield [
            '<?php foo("bar");',
            [1],
        ];

        yield [
            '<?php \foo("bar");',
            [2],
        ];

        yield [
            '<?php foo\bar("baz");',
            [],
        ];

        yield [
            '<?php foo\bar("baz");',
            [],
        ];

        yield [
            '<?php foo::bar("baz");',
            [],
        ];

        yield [
            '<?php foo::bar("baz");',
            [],
        ];

        yield [
            '<?php $foo->bar("baz");',
            [],
        ];

        yield [
            '<?php new bar("baz");',
            [],
        ];

        yield [
            '<?php function foo() {}',
            [],
        ];

        yield [
            '<?php function & foo() {}',
            [],
        ];

        yield [
            '<?php namespace\foo("bar");',
            [],
        ];

        yield [
            '<?php
                namespace A {
                    use function A;
                }
                namespace B {
                    use function D;
                    A();
                }
            ',
            [30],
        ];

        yield [
            '<?php
                function A(){}
                A();
            ',
            [10],
        ];

        yield [
            '<?php
                function A(){}
                a();
            ',
            [10],
        ];

        yield [
            '<?php
                namespace {
                    function A(){}
                    A();
                }
            ',
            [14],
        ];

        yield [
            '<?php
                namespace Z {
                    function A(){}
                    A();
                }
            ',
            [],
        ];

        yield [
            '<?php
            namespace Z;

            function A(){}
            A();
            ',
            [],
        ];

        yield [
            '<?php
                function & A(){}
                A();
            ',
            [12],
        ];

        yield [
            '<?php
                class Foo
                {
                    public function A(){}
                }
                A();
            ',
            [20],
        ];

        yield [
            '<?php
                namespace A {
                    function A(){}
                }
                namespace B {
                    A();
                }
            ',
            [24],
        ];

        yield [
            '<?php
                use function X\a;
                A();
            ',
            [],
        ];

        yield [
            '<?php
                use A;
                A();
            ',
            [7],
        ];

        yield [
            '<?php
                use const A;
                A();
            ',
            [9],
        ];

        yield [
            '<?php
                use function A;
                str_repeat($a, $b);
            ',
            [9],
        ];

        yield [
            '<?php
                namespace {
                    function A(){}
                    A();
                    $b = function(){};
                }
            ',
            [14],
        ];

        yield [
            '<?php implode($a);implode($a);implode($a);implode($a);implode($a);implode($a);',
            [1, 6, 11, 16, 21, 26],
        ];

        if (\PHP_VERSION_ID < 80000) {
            yield [
                '<?php
                    use function \  str_repeat;
                    str_repeat($a, $b);
                ',
                [11],
            ];
        }
    }

    /**
     * @param string $code
     * @param int[]  $indices
     *
     * @dataProvider provideIsGlobalFunctionCallPhp70Cases
     * @requires PHP 7.0
     */
    public function testIsGlobalFunctionCallPhp70($code, $indices)
    {
        self::assertIsGlobalFunctionCall($code, $indices);
    }

    public function provideIsGlobalFunctionCallPhp70Cases()
    {
        yield [
            '<?php
$z = new class(
    new class(){ private function A(){} }
){
    public function A() {}
};

A();
                ',
            [46],
        ];
    }

    /**
     * @param string $code
     * @param int[]  $indices
     *
     * @dataProvider provideIsGlobalFunctionCallPhp74Cases
     * @requires PHP 7.4
     */
    public function testIsGlobalFunctionCallPhp74($code, $indices)
    {
        self::assertIsGlobalFunctionCall($code, $indices);
    }

    public function provideIsGlobalFunctionCallPhp74Cases()
    {
        yield [
            '<?php $foo = fn() => false;',
            [],
        ];
    }

    /**
     * @param string $code
     * @param int[]  $indices
     *
     * @dataProvider provideIsGlobalFunctionCallPhp80Cases
     * @requires PHP 8.0
     */
    public function testIsGlobalFunctionCallPhp80($code, $indices)
    {
        self::assertIsGlobalFunctionCall($code, $indices);
    }

    public function provideIsGlobalFunctionCallPhp80Cases()
    {
        yield [
            '<?php $a = new (foo());',
            [8],
        ];

        yield [
            '<?php $b = $foo instanceof (foo());',
            [10],
        ];

        yield [
            '<?php
#[\Attribute(\Attribute::TARGET_CLASS)]
class Foo {}
',
            [],
        ];

        yield [
            '<?php $x?->count();',
            [],
        ];
    }

    /**
     * @param string $code
     * @param int    $methodIndex
     * @param array  $expected
     *
     * @dataProvider provideFunctionsWithArgumentsCases
     */
    public function testFunctionArgumentInfo($code, $methodIndex, $expected)
    {
        $tokens = Tokens::fromCode($code);
        $analyzer = new FunctionsAnalyzer();

        static::assertSame(serialize($expected), serialize($analyzer->getFunctionArguments($tokens, $methodIndex)));
    }

    /**
     * @param string $code
     * @param int    $methodIndex
     * @param array  $expected
     *
     * @dataProvider provideFunctionsWithReturnTypeCases
     */
    public function testFunctionReturnTypeInfo($code, $methodIndex, $expected)
    {
        $tokens = Tokens::fromCode($code);
        $analyzer = new FunctionsAnalyzer();

        $actual = $analyzer->getFunctionReturnType($tokens, $methodIndex);
        static::assertSame(serialize($expected), serialize($actual));
    }

    /**
     * @param string $code
     * @param int    $methodIndex
     * @param array  $expected
     *
     * @dataProvider provideFunctionsWithReturnTypePhp70Cases
     * @requires PHP 7.0
     */
    public function testFunctionReturnTypeInfoPhp70($code, $methodIndex, $expected)
    {
        $tokens = Tokens::fromCode($code);
        $analyzer = new FunctionsAnalyzer();

        $actual = $analyzer->getFunctionReturnType($tokens, $methodIndex);
        static::assertSame(serialize($expected), serialize($actual));
    }

    public function provideFunctionsWithArgumentsCases()
    {
        $tests = [
            ['<?php function(){};', 1, []],
            ['<?php function($a){};', 1, [
                '$a' => new ArgumentAnalysis(
                    '$a',
                    3,
                    null,
                    null
                ),
            ]],
            ['<?php function($a, $b){};', 1, [
                '$a' => new ArgumentAnalysis(
                    '$a',
                    3,
                    null,
                    null
                ),
                '$b' => new ArgumentAnalysis(
                    '$b',
                    6,
                    null,
                    null
                ),
            ]],
            ['<?php function($a, $b = array(1,2), $c = 3){};', 1, [
                '$a' => new ArgumentAnalysis(
                    '$a',
                    3,
                    null,
                    null
                ),
                '$b' => new ArgumentAnalysis(
                    '$b',
                    6,
                    'array(1,2)',
                    null
                ),
                '$c' => new ArgumentAnalysis(
                    '$c',
                    18,
                    '3',
                    null
                ),
            ]],
            ['<?php function(array $a = array()){};', 1, [
                '$a' => new ArgumentAnalysis(
                    '$a',
                    5,
                    'array()',
                    new TypeAnalysis(
                        'array',
                        3,
                        3
                    )
                ),
            ]],
            ['<?php function(array ... $a){};', 1, [
                '$a' => new ArgumentAnalysis(
                    '$a',
                    7,
                    null,
                    new TypeAnalysis(
                        'array',
                        3,
                        3
                    )
                ),
            ]],
            ['<?php function(\Foo\Bar $a){};', 1, [
                '$a' => new ArgumentAnalysis(
                    '$a',
                    8,
                    null,
                    new TypeAnalysis(
                        '\Foo\Bar',
                        3,
                        6
                    )
                ),
            ]],
        ];

        foreach ($tests as $index => $test) {
            yield $index => $test;
        }

        if (\PHP_VERSION_ID < 80000) {
            yield ['<?php function(\Foo/** TODO: change to something else */\Bar $a){};', 1, [
                '$a' => new ArgumentAnalysis(
                    '$a',
                    9,
                    null,
                    new TypeAnalysis(
                        '\Foo\Bar',
                        3,
                        7
                    )
                ),
            ]];
        }
    }

    public function provideFunctionsWithReturnTypeCases()
    {
        yield ['<?php function(){};', 1, null];
    }

    public function provideFunctionsWithReturnTypePhp70Cases()
    {
        yield ['<?php function($a): array {};', 1, new TypeAnalysis('array', 7, 7)];
        yield ['<?php function($a): \Foo\Bar {};', 1, new TypeAnalysis('\Foo\Bar', 7, 10)];
        yield ['<?php function($a): /* not sure if really an array */array {};', 1, new TypeAnalysis('array', 8, 8)];

        if (\PHP_VERSION_ID < 80000) {
            yield ['<?php function($a): \Foo/** TODO: change to something else */\Bar {};', 1, new TypeAnalysis('\Foo\Bar', 7, 11)];
        }
    }

    /**
     * @param string $code
     * @param int    $methodIndex
     * @param array  $expected
     *
     * @dataProvider provideFunctionsWithArgumentsPhp74Cases
     * @requires PHP 7.4
     */
    public function testFunctionArgumentInfoPhp74($code, $methodIndex, $expected)
    {
        $tokens = Tokens::fromCode($code);
        $analyzer = new FunctionsAnalyzer();

        static::assertSame(serialize($expected), serialize($analyzer->getFunctionArguments($tokens, $methodIndex)));
    }

    public function provideFunctionsWithArgumentsPhp74Cases()
    {
        $tests = [
            ['<?php fn() => null;', 1, []],
            ['<?php fn($a) => null;', 1, [
                '$a' => new ArgumentAnalysis(
                    '$a',
                    3,
                    null,
                    null
                ),
            ]],
            ['<?php fn($a, $b) => null;', 1, [
                '$a' => new ArgumentAnalysis(
                    '$a',
                    3,
                    null,
                    null
                ),
                '$b' => new ArgumentAnalysis(
                    '$b',
                    6,
                    null,
                    null
                ),
            ]],
            ['<?php fn($a, $b = array(1,2), $c = 3) => null;', 1, [
                '$a' => new ArgumentAnalysis(
                    '$a',
                    3,
                    null,
                    null
                ),
                '$b' => new ArgumentAnalysis(
                    '$b',
                    6,
                    'array(1,2)',
                    null
                ),
                '$c' => new ArgumentAnalysis(
                    '$c',
                    18,
                    '3',
                    null
                ),
            ]],
            ['<?php fn(array $a = array()) => null;', 1, [
                '$a' => new ArgumentAnalysis(
                    '$a',
                    5,
                    'array()',
                    new TypeAnalysis(
                        'array',
                        3,
                        3
                    )
                ),
            ]],
            ['<?php fn(array ... $a) => null;', 1, [
                '$a' => new ArgumentAnalysis(
                    '$a',
                    7,
                    null,
                    new TypeAnalysis(
                        'array',
                        3,
                        3
                    )
                ),
            ]],
            ['<?php fn(\Foo\Bar $a) => null;', 1, [
                '$a' => new ArgumentAnalysis(
                    '$a',
                    8,
                    null,
                    new TypeAnalysis(
                        '\Foo\Bar',
                        3,
                        6
                    )
                ),
            ]],
        ];

        foreach ($tests as $index => $test) {
            yield $index => $test;
        }

        if (\PHP_VERSION_ID < 80000) {
            yield ['<?php fn(\Foo/** TODO: change to something else */\Bar $a) => null;', 1, [
                '$a' => new ArgumentAnalysis(
                    '$a',
                    9,
                    null,
                    new TypeAnalysis(
                        '\Foo\Bar',
                        3,
                        7
                    )
                ),
            ]];
        }
    }

    /**
     * @param string $code
     * @param int    $methodIndex
     * @param array  $expected
     *
     * @dataProvider provideFunctionsWithReturnTypePhp74Cases
     * @requires PHP 7.4
     */
    public function testFunctionReturnTypeInfoPhp74($code, $methodIndex, $expected)
    {
        $tokens = Tokens::fromCode($code);
        $analyzer = new FunctionsAnalyzer();

        $actual = $analyzer->getFunctionReturnType($tokens, $methodIndex);
        static::assertSame(serialize($expected), serialize($actual));
    }

    public function provideFunctionsWithReturnTypePhp74Cases()
    {
        yield ['<?php fn() => null;', 1, null];
        yield ['<?php fn(array $a) => null;', 1, null];
        yield ['<?php fn($a): array => null;', 1, new TypeAnalysis('array', 7, 7)];
        yield ['<?php fn($a): \Foo\Bar => null;', 1, new TypeAnalysis('\Foo\Bar', 7, 10)];
        yield ['<?php fn($a): /* not sure if really an array */array => null;', 1, new TypeAnalysis('array', 8, 8)];

        if (\PHP_VERSION_ID < 80000) {
            yield ['<?php fn($a): \Foo/** TODO: change to something else */\Bar => null;', 1, new TypeAnalysis('\Foo\Bar', 7, 11)];
        }
    }

    /**
     * @param bool   $isTheSameClassCall
     * @param string $code
     * @param int    $index
     *
     * @dataProvider provideIsTheSameClassCallCases
     */
    public function testIsTheSameClassCall($isTheSameClassCall, $code, $index)
    {
        $tokens = Tokens::fromCode($code);
        $analyzer = new FunctionsAnalyzer();

        static::assertSame($isTheSameClassCall, $analyzer->isTheSameClassCall($tokens, $index));
    }

    public function provideIsTheSameClassCallCases()
    {
        $template = '<?php
            class Foo {
                public function methodOne() {
                    $x = %sotherMethod(1, 2, 3);
                }
            }
        ';

        yield [
            false,
            sprintf($template, '$this->'),
            -1,
        ];

        // 24 is index of "otherMethod" token

        for ($i = 0; $i < 40; ++$i) {
            yield [
                24 === $i,
                sprintf($template, '$this->'),
                $i,
            ];
            yield [
                24 === $i,
                sprintf($template, 'self::'),
                $i,
            ];
            yield [
                24 === $i,
                sprintf($template, 'static::'),
                $i,
            ];
        }

        yield [
            true,
            sprintf($template, '$THIS->'),
            24,
        ];

        yield [
            false,
            sprintf($template, '$notThis->'),
            24,
        ];

        yield [
            false,
            sprintf($template, 'Bar::'),
            24,
        ];

        if (\PHP_VERSION_ID >= 80000) {
            yield [
                true,
                sprintf($template, '$this?->'),
                24,
            ];
        }

        yield [
            true,
            sprintf($template, '$this::'),
            24,
        ];
    }

    /**
     * @param string $code
     * @param int    $methodIndex
     * @param array  $expected
     *
     * @dataProvider provideFunctionsWithArgumentsPhp80Cases
     * @requires PHP 8.0
     */
    public function testFunctionArgumentInfoPhp80($code, $methodIndex, $expected)
    {
        $tokens = Tokens::fromCode($code);
        $analyzer = new FunctionsAnalyzer();

        static::assertSame(serialize($expected), serialize($analyzer->getFunctionArguments($tokens, $methodIndex)));
    }

    public function provideFunctionsWithArgumentsPhp80Cases()
    {
        yield ['<?php function($aa,){};', 1, [
            '$aa' => new ArgumentAnalysis(
                '$aa',
                3,
                null,
                null
            ),
        ]];

        yield ['<?php fn($a,    $bc  ,) => null;', 1, [
            '$a' => new ArgumentAnalysis(
                '$a',
                3,
                null,
                null
            ),
            '$bc' => new ArgumentAnalysis(
                '$bc',
                6,
                null,
                null
            ),
        ]];
    }

    /**
     * @param string $code
     * @param int[]  $expectedIndices
     */
    private static function assertIsGlobalFunctionCall($code, $expectedIndices)
    {
        $tokens = Tokens::fromCode($code);
        $analyzer = new FunctionsAnalyzer();

        $actualIndices = [];
        foreach ($tokens as $index => $token) {
            if ($analyzer->isGlobalFunctionCall($tokens, $index)) {
                $actualIndices[] = $index;
            }
        }

        static::assertSame(
            $expectedIndices,
            $actualIndices,
            sprintf(
                'Global function calls found at positions: [%s], expected at [%s].',
                implode(', ', $actualIndices),
                implode(', ', $expectedIndices)
            )
        );
    }
}
