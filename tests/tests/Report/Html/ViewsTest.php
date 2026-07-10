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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[CoversClass(Views::class)]
#[Small]
final class ViewsTest extends TestCase
{
    public function testCanBeFileViewAndClassView(): void
    {
        $views = Views::FileViewAndClassView;

        $this->assertTrue($views->fileView());
        $this->assertTrue($views->classView());
    }

    public function testCanBeOnlyFileView(): void
    {
        $views = Views::OnlyFileView;

        $this->assertTrue($views->fileView());
        $this->assertFalse($views->classView());
    }

    public function testCanBeOnlyClassView(): void
    {
        $views = Views::OnlyClassView;

        $this->assertFalse($views->fileView());
        $this->assertTrue($views->classView());
    }
}
