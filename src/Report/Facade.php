<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Report;

use DateTimeImmutable;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Node\Builder;
use SebastianBergmann\CodeCoverage\Node\Directory;
use SebastianBergmann\CodeCoverage\Report\Html\Colors;
use SebastianBergmann\CodeCoverage\Report\Html\CustomCssFile;
use SebastianBergmann\CodeCoverage\Report\Html\Facade as HtmlFacade;
use SebastianBergmann\CodeCoverage\Report\Xml\Facade as XmlFacade;
use SebastianBergmann\CodeCoverage\Serialization\Serializer;
use SebastianBergmann\CodeCoverage\StaticAnalysis\FileAnalyser;
use SebastianBergmann\CodeCoverage\StaticAnalysis\ParsingSourceAnalyser;
use SebastianBergmann\CodeCoverage\Util\Filesystem;
use SebastianBergmann\Environment\Runtime;

/**
 * @phpstan-import-type SerializedCoverage from Serializer
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final readonly class Facade
{
    private Directory $report;

    /**
     * @var array<string, array{size: string, status: string, time: float}>
     */
    private array $testResults;

    public static function fromObject(CodeCoverage $codeCoverage): self
    {
        return new self(
            $codeCoverage->getReport(),
            $codeCoverage->getTests(),
        );
    }

    /**
     * @param SerializedCoverage $serializedCoverage
     */
    public static function fromSerializedData(array $serializedCoverage): self
    {
        return new self(
            self::buildReport($serializedCoverage),
            $serializedCoverage['testResults'],
        );
    }

    private function __construct(Directory $report, array $testResults)
    {
        $this->report      = $report;
        $this->testResults = $testResults;
    }

    /**
     * @param non-empty-string $target
     */
    public function renderHtml(string $target, string $generator = '', ?Colors $colors = null, ?Thresholds $thresholds = null, ?CustomCssFile $customCssFile = null): void
    {
        new HtmlFacade($generator, $colors, $thresholds, $customCssFile)->process(
            $this->report,
            $target,
        );
    }

    /**
     * @param non-empty-string $target
     */
    public function renderXml(string $target, bool $includeSource = true, ?Runtime $runtime = null, ?DateTimeImmutable $buildDate = null, ?string $phpUnitVersion = null, ?string $coverageVersion = null, ?string $driverExtensionName = null, ?string $driverExtensionVersion = null): void
    {
        new XmlFacade($includeSource)->process(
            $target,
            $this->report,
            $this->testResults,
            $runtime,
            $buildDate,
            $phpUnitVersion,
            $coverageVersion,
            $driverExtensionName,
            $driverExtensionVersion,
        );
    }

    /**
     * @param non-empty-string      $target
     * @param null|non-empty-string $name
     */
    public function renderClover(string $target, ?string $name = null): void
    {
        (new Clover)->process(
            $this->report,
            $target,
            $name,
        );
    }

    /**
     * @param non-empty-string      $target
     * @param null|non-empty-string $name
     */
    public function renderOpenClover(string $target, ?string $name = null): void
    {
        (new OpenClover)->process(
            $this->report,
            $target,
            $name,
        );
    }

    /**
     * @param non-empty-string $target
     */
    public function renderCobertura(string $target): void
    {
        (new Cobertura)->process(
            $this->report,
            $target,
        );
    }

    /**
     * @param non-empty-string      $target
     * @param null|non-empty-string $name
     */
    public function renderCrap4j(string $target, int $threshold = 30, ?string $name = null): void
    {
        new Crap4j($threshold)->process(
            $this->report,
            $target,
            $name,
        );
    }

    public function summary(): Summary
    {
        return new Summary(
            $this->report->numberOfExecutableLines(),
            $this->report->numberOfExecutedLines(),
            $this->report->numberOfExecutableBranches(),
            $this->report->numberOfExecutedBranches(),
            $this->report->numberOfExecutablePaths(),
            $this->report->numberOfExecutedPaths(),
        );
    }

    /**
     * @param ?non-empty-string $target
     */
    public function renderText(?string $target, ?Thresholds $thresholds = null, bool $showUncoveredFiles = false, bool $showOnlySummary = false, bool $showColors = false): string
    {
        if ($thresholds === null) {
            $thresholds = Thresholds::default();
        }

        $buffer = new Text($thresholds, $showUncoveredFiles, $showOnlySummary)->process(
            $this->report,
            $showColors,
        );

        if ($target !== null) {
            Filesystem::write(
                $target,
                new Text($thresholds, $showUncoveredFiles, $showOnlySummary)->process(
                    $this->report,
                    $showColors,
                ),
            );
        }

        return $buffer;
    }

    /**
     * @param SerializedCoverage $serializedCoverage
     */
    private static function buildReport(array $serializedCoverage): Directory
    {
        return new Builder(new FileAnalyser(new ParsingSourceAnalyser, false, false))->build(
            $serializedCoverage['codeCoverage'],
            $serializedCoverage['testResults'],
            $serializedCoverage['basePath'],
        );
    }
}
