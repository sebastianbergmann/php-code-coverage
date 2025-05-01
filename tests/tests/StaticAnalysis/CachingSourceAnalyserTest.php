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

use const DIRECTORY_SEPARATOR;
use function sys_get_temp_dir;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(CachingSourceAnalyser::class)]
#[UsesClass(ParsingSourceAnalyser::class)]
#[UsesClass(CodeUnitFindingVisitor::class)]
#[UsesClass(IgnoredLinesFindingVisitor::class)]
#[Small]
final class CachingSourceAnalyserTest extends SourceAnalyserTestCase
{
    protected function analyser(): SourceAnalyser
    {
        return new CachingSourceAnalyser(
            sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'php-code-coverage-tests-for-static-analysis',
            new ParsingSourceAnalyser,
        );
    }
}
