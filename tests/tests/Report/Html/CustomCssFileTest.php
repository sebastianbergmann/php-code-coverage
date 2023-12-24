<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Report\Html;

use function realpath;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\CodeCoverage\InvalidArgumentException;

#[CoversClass(CustomCssFile::class)]
#[Small]
final class CustomCssFileTest extends TestCase
{
    public function testCanBeCreatedFromDefaults(): void
    {
        $file = CustomCssFile::default();

        $this->assertSame(
            realpath(__DIR__ . '/../../../../src/Report/Html/Renderer/Template/css/custom.css'),
            realpath($file->path()),
        );
    }

    public function testCanBeCreatedFromValidPath(): void
    {
        $file = CustomCssFile::from(__FILE__);

        $this->assertSame(__FILE__, $file->path());
    }

    public function testCannotBeCreatedFromInvalidPath(): void
    {
        $this->expectException(InvalidArgumentException::class);

        CustomCssFile::from('does-not-exist');
    }
}
