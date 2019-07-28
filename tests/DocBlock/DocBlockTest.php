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

namespace PhpCsFixer\Tests\DocBlock;

use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\Tests\TestCase;

/**
 * @author Graham Campbell <graham@alt-three.com>
 *
 * @internal
 *
 * @covers \PhpCsFixer\DocBlock\DocBlock
 */
final class DocBlockTest extends TestCase
{
    /**
     * This represents the content an entire docblock.
     *
     * @var string
     */
    private static $sample = '/**
     * Test docblock.
     *
     * @param string $hello
     * @param bool $test Description
     *        extends over many lines
     *
     * @param adkjbadjasbdand $asdnjkasd
     *
     * @throws \Exception asdnjkasd
     * asdasdasdasdasdasdasdasd
     * kasdkasdkbasdasdasdjhbasdhbasjdbjasbdjhb
     *
     * @return void
     */';

    public function testContent()
    {
        $doc = new DocBlock(self::$sample);

        static::assertSame(self::$sample, $doc->getContent());
        static::assertSame(self::$sample, (string) $doc);
    }

    public function testEmptyContent()
    {
        $doc = new DocBlock('');

        static::assertSame('', $doc->getContent());
    }

    public function testGetLines()
    {
        $doc = new DocBlock(self::$sample);

        static::assertInternalType('array', $doc->getLines());
        static::assertCount(15, $doc->getLines());

        foreach ($doc->getLines() as $index => $line) {
            static::assertInstanceOf(\PhpCsFixer\DocBlock\Line::class, $line);
            static::assertSame($doc->getLine($index), $line);
        }

        static::assertEmpty($doc->getLine(15));
    }

    public function testGetAnnotations()
    {
        $doc = new DocBlock(self::$sample);

        static::assertInternalType('array', $doc->getAnnotations());
        static::assertCount(5, $doc->getAnnotations());

        foreach ($doc->getAnnotations() as $index => $annotations) {
            static::assertInstanceOf(\PhpCsFixer\DocBlock\Annotation::class, $annotations);
            static::assertSame($doc->getAnnotation($index), $annotations);
        }

        static::assertEmpty($doc->getAnnotation(5));
    }

    public function testGetAnnotationsOfTypeParam()
    {
        $doc = new DocBlock(self::$sample);

        $annotations = $doc->getAnnotationsOfType('param');

        static::assertInternalType('array', $annotations);
        static::assertCount(3, $annotations);

        $first = '     * @param string $hello
';
        $second = '     * @param bool $test Description
     *        extends over many lines
';
        $third = '     * @param adkjbadjasbdand $asdnjkasd
';

        static::assertSame($first, $annotations[0]->getContent());
        static::assertSame($second, $annotations[1]->getContent());
        static::assertSame($third, $annotations[2]->getContent());
    }

    public function testGetAnnotationsOfTypeThrows()
    {
        $doc = new DocBlock(self::$sample);

        $annotations = $doc->getAnnotationsOfType('throws');

        static::assertInternalType('array', $annotations);
        static::assertCount(1, $annotations);

        $content = '     * @throws \Exception asdnjkasd
     * asdasdasdasdasdasdasdasd
     * kasdkasdkbasdasdasdjhbasdhbasjdbjasbdjhb
';

        static::assertSame($content, $annotations[0]->getContent());
    }

    public function testGetAnnotationsOfTypeReturn()
    {
        $doc = new DocBlock(self::$sample);

        $annotations = $doc->getAnnotationsOfType('return');

        static::assertInternalType('array', $annotations);
        static::assertCount(1, $annotations);

        $content = '     * @return void
';

        static::assertSame($content, $annotations[0]->getContent());
    }

    public function testGetAnnotationsOfTypeFoo()
    {
        $doc = new DocBlock(self::$sample);

        $annotations = $doc->getAnnotationsOfType('foo');

        static::assertInternalType('array', $annotations);
        static::assertCount(0, $annotations);
    }

    /**
     * @param string $expected
     * @param string $input
     * @param int    $line
     *
     * @dataProvider provideRemovingLineCases
     */
    public function testRemovingLine($expected, $input, $line)
    {
        $doc = new DocBlock($input);
        $doc->getLine($line)->remove();

        $this->assertSame($expected, $doc->getContent());
    }

    public function provideRemovingLineCases()
    {
        yield [
            '/**
              * Comment
              */',
            '/**
              * Comment
              */',
            0,
        ];
        yield [
            '',
            '/** Comment */',
            0,
        ];

        yield [
            "/**\r              * Comment\r              */",
            "/**\r              * Comment\r              */",
            0,
        ];

        yield [
            "/**\r\n              * Comment\r\n              */",
            "/**\r\n              * Comment\r\n              */",
            0,
        ];

        yield [
            '/**
              * Comment
              */',
            '/**
              * Comment
              */',
            2,
        ];

        yield [
            "/**\r              * Comment\r              */",
            "/**\r              * Comment\r              */",
            2,
        ];

        yield [
            "/**\n\t\t\t * Comment\n\t\t\t */",
            "/**\n\t\t\t * Comment\n\t\t\t */",
            2,
        ];

        yield [
            '/**
              * Foo
              */',
            '/** Comment
              * Foo
              */',
            0,
        ];

        yield [
            '/**
              * Foo
              */',
            '/**
              * Foo
              * Comment */',
            2,
        ];

        yield [
            '/**
              * Foo
              */',
            '/**
              * Foo
              * Comment */',
            2,
        ];
    }
}
