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

namespace PhpCsFixer\Tests\Fixer\FunctionNotation;

use PhpCsFixer\Tests\Test\AbstractFixerTestCase;

/**
 * @author Kuba Werłos <werlos@gmail.com>
 *
 * @internal
 *
 * @covers \PhpCsFixer\Fixer\FunctionNotation\SingleLineThrowFixer
 */
final class SingleLineThrowFixerTest extends AbstractFixerTestCase
{
    /**
     * @param string      $expected
     * @param null|string $input
     *
     * @dataProvider provideFixCases
     */
    public function testFix($expected, $input = null)
    {
        $this->doTest($expected, $input);
    }

    public function provideFixCases()
    {
        yield ["<?php throw new Exception; var_dump(
                    'Boo'
                );"];

        yield ["<?php throw new \$exceptionName; var_dump(
                    'Boo'
                );"];

        yield ["<?php throw \$exception; var_dump(
                    'Boo'
                );"];

        yield [
            "<?php throw new Exception('Boo', 0);",
            "<?php throw new Exception(
                'Boo',
                0
            );",
        ];

        yield [
            "<?php throw new Vendor\\Exception('Boo');",
            "<?php throw new Vendor\\Exception(
                'Boo'
            );",
        ];

        yield [
            "<?php throw new \\Vendor\\Exception('Boo');",
            "<?php throw new \\Vendor\\Exception(
                'Boo'
            );",
        ];

        yield [
            "<?php throw new \$exceptionName('Boo');",
            "<?php throw new \$exceptionName(
                'Boo'
            );",
        ];

        yield [
            "<?php throw new WeirdException('Boo', -20, 'An elephant', 1, 2, 3, 4, 5, 6, 7, 8);",
            "<?php throw new WeirdException('Boo', -20, 'An elephant',
            ".'
            '.'
                1,
        2,
                                    3, 4, 5, 6, 7, 8
            );',
        ];
    }
}
