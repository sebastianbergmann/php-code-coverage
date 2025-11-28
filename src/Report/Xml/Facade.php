<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Report\Xml;

use const DIRECTORY_SEPARATOR;
use function count;
use function dirname;
use function file_get_contents;
use function is_array;
use function is_dir;
use function is_file;
use function is_writable;
use function sprintf;
use function strlen;
use function substr;
use DateTimeImmutable;
use DOMDocument;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Data\ProcessedClassType;
use SebastianBergmann\CodeCoverage\Data\ProcessedFunctionType;
use SebastianBergmann\CodeCoverage\Data\ProcessedTraitType;
use SebastianBergmann\CodeCoverage\Node\AbstractNode;
use SebastianBergmann\CodeCoverage\Node\Directory as DirectoryNode;
use SebastianBergmann\CodeCoverage\Node\File as FileNode;
use SebastianBergmann\CodeCoverage\PathExistsButIsNotDirectoryException;
use SebastianBergmann\CodeCoverage\Util\Filesystem;
use SebastianBergmann\CodeCoverage\Util\Xml;
use SebastianBergmann\CodeCoverage\Version;
use SebastianBergmann\CodeCoverage\WriteOperationFailedException;
use SebastianBergmann\CodeCoverage\XmlException;
use SebastianBergmann\Environment\Runtime;

/**
 * @phpstan-import-type TestType from CodeCoverage
 */
final class Facade
{
    public const string XML_NAMESPACE = 'https://schema.phpunit.de/coverage/1.0';
    private string $target;
    private Project $project;
    private readonly string $phpUnitVersion;
    private readonly bool $includeSource;

    public function __construct(string $version, bool $includeSource = true)
    {
        $this->phpUnitVersion = $version;
        $this->includeSource  = $includeSource;
    }

    /**
     * @throws XmlException
     */
    public function process(CodeCoverage $coverage, string $target): void
    {
        if (substr($target, -1, 1) !== DIRECTORY_SEPARATOR) {
            $target .= DIRECTORY_SEPARATOR;
        }

        $this->target = $target;
        $this->initTargetDirectory($target);

        $report = $coverage->getReport();

        $this->project = new Project(
            $coverage->getReport()->name(),
        );

        $this->setBuildInformation();
        $this->processTests($coverage->getTests());
        $this->processDirectory($report, $this->project);

        $this->saveDocument($this->project->asDom(), 'index');
    }

    private function setBuildInformation(): void
    {
        $this->project->buildInformation(
            new Runtime,
            new DateTimeImmutable,
            $this->phpUnitVersion,
            Version::id(),
        );
    }

    /**
     * @throws PathExistsButIsNotDirectoryException
     * @throws WriteOperationFailedException
     */
    private function initTargetDirectory(string $directory): void
    {
        if (is_file($directory)) {
            // @codeCoverageIgnoreStart
            if (!is_dir($directory)) {
                throw new PathExistsButIsNotDirectoryException($directory);
            }

            if (!is_writable($directory)) {
                throw new WriteOperationFailedException($directory);
            }
            // @codeCoverageIgnoreEnd
        }

        Filesystem::createDirectory($directory);
    }

    /**
     * @throws XmlException
     */
    private function processDirectory(DirectoryNode $directory, Node $context): void
    {
        $directoryName = $directory->name();

        if ($this->project->projectSourceDirectory() === $directoryName) {
            $directoryName = '/';
        }

        $directoryObject = $context->addDirectory($directoryName);

        $this->setTotals($directory, $directoryObject->totals());

        foreach ($directory->directories() as $node) {
            $this->processDirectory($node, $directoryObject);
        }

        foreach ($directory->files() as $node) {
            $this->processFile($node, $directoryObject);
        }
    }

    /**
     * @throws XmlException
     */
    private function processFile(FileNode $file, Directory $context): void
    {
        $fileObject = $context->addFile(
            $file->name(),
            $file->id() . '.xml',
            $file->sha1(),
        );

        $this->setTotals($file, $fileObject->totals());

        $path = substr(
            $file->pathAsString(),
            strlen($this->project->projectSourceDirectory()),
        );

        $fileReport = new Report($path, $file->sha1());

        $this->setTotals($file, $fileReport->totals());

        foreach ($file->classesAndTraits() as $unit) {
            $this->processUnit($unit, $fileReport);
        }

        foreach ($file->functions() as $function) {
            $this->processFunction($function, $fileReport);
        }

        foreach ($file->lineCoverageData() as $line => $tests) {
            if (!is_array($tests) || count($tests) === 0) {
                continue;
            }

            $coverage = $fileReport->lineCoverage((string) $line);
            $coverage->finalize($tests);
        }

        if ($this->includeSource) {
            $fileReport->source()->setSourceCode(
                file_get_contents($file->pathAsString()),
            );
        }

        $this->saveDocument($fileReport->asDom(), $file->id());
    }

    private function processUnit(ProcessedClassType|ProcessedTraitType $unit, Report $report): void
    {
        if ($unit instanceof ProcessedClassType) {
            $unitObject = $report->classObject(
                $unit->className,
                $unit->namespace,
                $unit->startLine,
                $unit->executableLines,
                $unit->executedLines,
                (float) $unit->crap,
            );
        } else {
            $unitObject = $report->traitObject(
                $unit->traitName,
                $unit->namespace,
                $unit->startLine,
                $unit->executableLines,
                $unit->executedLines,
                (float) $unit->crap,
            );
        }

        foreach ($unit->methods as $method) {
            $unitObject->addMethod(
                $method->methodName,
                $method->signature,
                (string) $method->startLine,
                (string) $method->endLine,
                (string) $method->executableLines,
                (string) $method->executedLines,
                (string) $method->coverage,
                $method->crap,
            );
        }
    }

    private function processFunction(ProcessedFunctionType $function, Report $report): void
    {
        $report->functionObject(
            $function->functionName,
            $function->signature,
            (string) $function->startLine,
            null,
            (string) $function->executableLines,
            (string) $function->executedLines,
            (string) $function->coverage,
            $function->crap,
        );
    }

    /**
     * @param array<string, TestType> $tests
     */
    private function processTests(array $tests): void
    {
        $testsObject = $this->project->tests();

        foreach ($tests as $test => $result) {
            $testsObject->addTest($test, $result);
        }
    }

    private function setTotals(AbstractNode $node, Totals $totals): void
    {
        $loc = $node->linesOfCode();

        $totals->setNumLines(
            $loc->linesOfCode(),
            $loc->commentLinesOfCode(),
            $loc->nonCommentLinesOfCode(),
            $node->numberOfExecutableLines(),
            $node->numberOfExecutedLines(),
        );

        $totals->setNumClasses(
            $node->numberOfClasses(),
            $node->numberOfTestedClasses(),
        );

        $totals->setNumTraits(
            $node->numberOfTraits(),
            $node->numberOfTestedTraits(),
        );

        $totals->setNumMethods(
            $node->numberOfMethods(),
            $node->numberOfTestedMethods(),
        );

        $totals->setNumFunctions(
            $node->numberOfFunctions(),
            $node->numberOfTestedFunctions(),
        );
    }

    private function targetDirectory(): string
    {
        return $this->target;
    }

    private function targetFilePath(string $name): string
    {
        $filename = sprintf('%s/%s.xml', $this->targetDirectory(), $name);

        $this->initTargetDirectory(dirname($filename));

        return $filename;
    }

    /**
     * @throws XmlException
     */
    private function saveDocument(DOMDocument $document, string $name): void
    {
        Filesystem::write($this->targetFilePath($name), Xml::asString($document));
    }
}
