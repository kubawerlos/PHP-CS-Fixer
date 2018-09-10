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

namespace PhpCsFixer\Fixer\FunctionNotation;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author Kuba Werłos <werlos@gmail.com>
 */
final class SingleLineThrowFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            '`throw` must be single line.',
            [
                new CodeSample("<?php\nthrow new Exception(\n    'Error',\n    500\n);\n"),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_THROW);
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        for ($index = 0, $count = $tokens->count(); $index < $count; ++$index) {
            if (!$tokens[$index]->isGivenKind(T_THROW)) {
                continue;
            }

            $openBraceCandidateIndex = $tokens->getNextTokenOfKind($index, [';', '(']);
            if (!$tokens[$openBraceCandidateIndex]->equals('(')) {
                continue;
            }

            $this->trimNewLines($tokens, $openBraceCandidateIndex, $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $openBraceCandidateIndex));
        }
    }

    /**
     * @param Tokens $tokens
     * @param int    $startIndex
     * @param int    $endIndex
     */
    private function trimNewLines(Tokens $tokens, $startIndex, $endIndex)
    {
        for ($index = $startIndex; $index < $endIndex; ++$index) {
            if (!$tokens[$index]->isGivenKind(T_WHITESPACE)) {
                continue;
            }

            if (false === strpbrk($tokens[$index]->getContent(), "\n")) {
                continue;
            }

            if ($index === $startIndex + 1 || $index === $endIndex - 1) {
                $tokens->clearAt($index);

                continue;
            }

            $tokens[$index] = new Token([T_WHITESPACE, ' ']);
        }
    }
}
