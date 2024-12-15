<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\StaticAnalysis;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;

#[CoversClass(ParsingFileAnalyser::class)]
#[CoversClass(CodeUnitFindingVisitor::class)]
#[CoversClass(IgnoredLinesFindingVisitor::class)]
#[Small]
final class ParsingFileAnalyserTest extends FileAnalyserTestCase
{
    protected function analyser(): FileAnalyser
    {
        return new ParsingFileAnalyser(true, true);
    }
}
