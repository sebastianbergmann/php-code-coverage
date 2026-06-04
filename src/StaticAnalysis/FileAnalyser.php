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

use function file_get_contents;
use SebastianBergmann\CodeCoverage\Serialization\FileCouldNotBeReadException;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final class FileAnalyser
{
    private readonly SourceAnalyser $sourceAnalyser;
    private readonly bool $useAnnotationsForIgnoringCode;
    private readonly bool $ignoreDeprecatedCode;

    /**
     * @var array<non-empty-string, AnalysisResult>
     */
    private array $cache = [];

    public function __construct(SourceAnalyser $sourceAnalyser, bool $useAnnotationsForIgnoringCode, bool $ignoreDeprecatedCode)
    {
        $this->sourceAnalyser                = $sourceAnalyser;
        $this->useAnnotationsForIgnoringCode = $useAnnotationsForIgnoringCode;
        $this->ignoreDeprecatedCode          = $ignoreDeprecatedCode;
    }

    /**
     * @param non-empty-string $sourceCodeFile
     *
     * @throws FileCouldNotBeReadException
     */
    public function analyse(string $sourceCodeFile): AnalysisResult
    {
        if (isset($this->cache[$sourceCodeFile])) {
            return $this->cache[$sourceCodeFile];
        }

        $sourceCode = file_get_contents($sourceCodeFile);

        if ($sourceCode === false) {
            // @codeCoverageIgnoreStart
            throw new FileCouldNotBeReadException($sourceCodeFile);
            // @codeCoverageIgnoreEnd
        }

        $this->cache[$sourceCodeFile] = $this->sourceAnalyser->analyse(
            $sourceCodeFile,
            $sourceCode,
            $this->useAnnotationsForIgnoringCode,
            $this->ignoreDeprecatedCode,
        );

        return $this->cache[$sourceCodeFile];
    }
}
