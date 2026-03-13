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
use SebastianBergmann\CodeCoverage\Data\ProcessedTraitType;
use SebastianBergmann\CodeCoverage\Node\Directory;
use SebastianBergmann\CodeCoverage\Node\File;
use SebastianBergmann\CodeCoverage\StaticAnalysis\LinesOfCode;

#[CoversClass(TraitSection::class)]
#[Small]
final class TraitSectionTest extends TestCase
{
    public function testProperties(): void
    {
        $root     = new Directory('root');
        $fileNode = new File('trait.php', $root, 'abc123', [], [], [], [], [], [], new LinesOfCode(0, 0, 0));

        $method = new ProcessedMethodType('traitMethod', 'public', 'public function traitMethod(): void', 1, 5, 3, 2, 0, 0, 0, 0, 1, 66, 1, '');
        $trait  = new ProcessedTraitType('App\\MyTrait', 'App', ['traitMethod' => $method], 1, 3, 2, 0, 0, 0, 0, 1, 66, 1, '');

        $section = new TraitSection('App\\MyTrait', '/path/to/trait.php', 1, 10, $trait, $fileNode);

        $this->assertSame('App\\MyTrait', $section->traitName);
        $this->assertSame('/path/to/trait.php', $section->filePath);
        $this->assertSame(1, $section->startLine);
        $this->assertSame(10, $section->endLine);
        $this->assertSame($trait, $section->trait);
        $this->assertSame($fileNode, $section->fileNode);
    }
}
