<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Data;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProcessedFunctionType::class)]
#[Small]
final class ProcessedFunctionTypeTest extends TestCase
{
    public function testCanBeConstructed(): void
    {
        $function = new ProcessedFunctionType(
            'someFunction',
            'SomeNamespace',
            'someFunction(): void',
            5,
            20,
            10,
            8,
            4,
            3,
            2,
            1,
            2,
            80.0,
            '1.02',
            'functions.php.html#5',
        );

        $this->assertSame('someFunction', $function->functionName);
        $this->assertSame('SomeNamespace', $function->namespace);
        $this->assertSame('someFunction(): void', $function->signature);
        $this->assertSame(5, $function->startLine);
        $this->assertSame(20, $function->endLine);
        $this->assertSame(10, $function->executableLines);
        $this->assertSame(8, $function->executedLines);
        $this->assertSame(4, $function->executableBranches);
        $this->assertSame(3, $function->executedBranches);
        $this->assertSame(2, $function->executablePaths);
        $this->assertSame(1, $function->executedPaths);
        $this->assertSame(2, $function->ccn);
        $this->assertSame(80.0, $function->coverage);
        $this->assertSame('1.02', $function->crap);
        $this->assertSame('functions.php.html#5', $function->link);
    }
}
