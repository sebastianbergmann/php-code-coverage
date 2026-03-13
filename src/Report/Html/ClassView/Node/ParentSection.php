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

use SebastianBergmann\CodeCoverage\Data\ProcessedMethodType;
use SebastianBergmann\CodeCoverage\Node\File as FileNode;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final readonly class ParentSection
{
    /**
     * @var non-empty-string
     */
    public string $className;

    /**
     * @var non-empty-string
     */
    public string $filePath;

    /**
     * @var array<string, ProcessedMethodType>
     */
    public array $methods;
    public FileNode $fileNode;

    /**
     * @param non-empty-string                   $className
     * @param non-empty-string                   $filePath
     * @param array<string, ProcessedMethodType> $methods
     */
    public function __construct(string $className, string $filePath, array $methods, FileNode $fileNode)
    {
        $this->className = $className;
        $this->filePath  = $filePath;
        $this->methods   = $methods;
        $this->fileNode  = $fileNode;
    }
}
