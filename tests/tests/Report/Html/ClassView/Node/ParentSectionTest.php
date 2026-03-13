<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Report\Html\ClassView\Node;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\CodeCoverage\Data\ProcessedMethodType;
use SebastianBergmann\CodeCoverage\Node\Directory;
use SebastianBergmann\CodeCoverage\Node\File;
use SebastianBergmann\CodeCoverage\StaticAnalysis\LinesOfCode;

#[CoversClass(ParentSection::class)]
#[Small]
final class ParentSectionTest extends TestCase
{
    public function testProperties(): void
    {
        $root     = new Directory('root');
        $fileNode = new File('parent.php', $root, 'abc123', [], [], [], [], [], [], new LinesOfCode(0, 0, 0));

        $method = new ProcessedMethodType('parentMethod', 'public', 'public function parentMethod(): void', 1, 5, 3, 2, 0, 0, 0, 0, 1, 66, 1, '');

        $section = new ParentSection('App\\BaseClass', '/path/to/base.php', ['parentMethod' => $method], $fileNode);

        $this->assertSame('App\\BaseClass', $section->className);
        $this->assertSame('/path/to/base.php', $section->filePath);
        $this->assertArrayHasKey('parentMethod', $section->methods);
        $this->assertSame($fileNode, $section->fileNode);
    }
}
