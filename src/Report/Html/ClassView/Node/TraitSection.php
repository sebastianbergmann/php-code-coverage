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

use SebastianBergmann\CodeCoverage\Data\ProcessedTraitType;
use SebastianBergmann\CodeCoverage\Node\File as FileNode;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final readonly class TraitSection
{
    /**
     * @var non-empty-string
     */
    public string $traitName;

    /**
     * @var non-empty-string
     */
    public string $filePath;
    public int $startLine;
    public int $endLine;
    public ProcessedTraitType $trait;
    public FileNode $fileNode;

    /**
     * @param non-empty-string $traitName
     * @param non-empty-string $filePath
     */
    public function __construct(string $traitName, string $filePath, int $startLine, int $endLine, ProcessedTraitType $trait, FileNode $fileNode)
    {
        $this->traitName = $traitName;
        $this->filePath  = $filePath;
        $this->startLine = $startLine;
        $this->endLine   = $endLine;
        $this->trait     = $trait;
        $this->fileNode  = $fileNode;
    }
}
