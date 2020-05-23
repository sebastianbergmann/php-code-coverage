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

use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Node\File;
use SebastianBergmann\CodeCoverage\Percentage;

/**
 * Generates human readable output from a code coverage object.
 *
 * The output gets put into a text file our written to the CLI.
 */
final class Text
{
    /**
     * @var string
     */
    private const COLOR_GREEN = "\x1b[30;42m";

    /**
     * @var string
     */
    private const COLOR_YELLOW = "\x1b[30;43m";

    /**
     * @var string
     */
    private const COLOR_RED = "\x1b[37;41m";

    /**
     * @var string
     */
    private const COLOR_HEADER = "\x1b[1;37;40m";

    /**
     * @var string
     */
    private const COLOR_RESET = "\x1b[0m";

    /**
     * @var string
     */
    private const COLOR_EOL = "\x1b[2K";

    /**
     * @var int
     */
    private $lowUpperBound;

    /**
     * @var int
     */
    private $highLowerBound;

    /**
     * @var bool
     */
    private $showUncoveredFiles;

    /**
     * @var bool
     */
    private $showOnlySummary;

    public function __construct(int $lowUpperBound = 50, int $highLowerBound = 90, bool $showUncoveredFiles = false, bool $showOnlySummary = false)
    {
        $this->lowUpperBound      = $lowUpperBound;
        $this->highLowerBound     = $highLowerBound;
        $this->showUncoveredFiles = $showUncoveredFiles;
        $this->showOnlySummary    = $showOnlySummary;
    }

    public function process(CodeCoverage $coverage, bool $showColors = false): string
    {
        $output = \PHP_EOL . \PHP_EOL;
        $report = $coverage->getReport();

        $colors = [
            'header'  => '',
            'classes' => '',
            'methods' => '',
            'lines'   => '',
            'reset'   => '',
            'eol'     => '',
        ];

        if ($showColors) {
            $colors['classes'] = $this->getCoverageColor(
                $report->getNumTestedClassesAndTraits(),
                $report->getNumClassesAndTraits()
            );

            $colors['methods'] = $this->getCoverageColor(
                $report->getNumTestedMethods(),
                $report->getNumMethods()
            );

            $colors['lines']   = $this->getCoverageColor(
                $report->getNumExecutedLines(),
                $report->getNumExecutableLines()
            );

            $colors['reset']  = self::COLOR_RESET;
            $colors['header'] = self::COLOR_HEADER;
            $colors['eol']    = self::COLOR_EOL;
        }

        $classes = \sprintf(
            '  Classes: %6s (%d/%d)',
            Percentage::fromFractionAndTotal(
                $report->getNumTestedClassesAndTraits(),
                $report->getNumClassesAndTraits()
            )->asString(),
            $report->getNumTestedClassesAndTraits(),
            $report->getNumClassesAndTraits()
        );

        $methods = \sprintf(
            '  Methods: %6s (%d/%d)',
            Percentage::fromFractionAndTotal(
                $report->getNumTestedMethods(),
                $report->getNumMethods(),
            )->asString(),
            $report->getNumTestedMethods(),
            $report->getNumMethods()
        );

        $lines = \sprintf(
            '  Lines:   %6s (%d/%d)',
            Percentage::fromFractionAndTotal(
                $report->getNumExecutedLines(),
                $report->getNumExecutableLines(),
            )->asString(),
            $report->getNumExecutedLines(),
            $report->getNumExecutableLines()
        );

        $padding = \max(\array_map('strlen', [$classes, $methods, $lines]));

        if ($this->showOnlySummary) {
            $title   = 'Code Coverage Report Summary:';
            $padding = \max($padding, \strlen($title));

            $output .= $this->format($colors['header'], $padding, $title);
        } else {
            $date  = \date('  Y-m-d H:i:s');
            $title = 'Code Coverage Report:';

            $output .= $this->format($colors['header'], $padding, $title);
            $output .= $this->format($colors['header'], $padding, $date);
            $output .= $this->format($colors['header'], $padding, '');
            $output .= $this->format($colors['header'], $padding, ' Summary:');
        }

        $output .= $this->format($colors['classes'], $padding, $classes);
        $output .= $this->format($colors['methods'], $padding, $methods);
        $output .= $this->format($colors['lines'], $padding, $lines);

        if ($this->showOnlySummary) {
            return $output . \PHP_EOL;
        }

        $classCoverage = [];

        foreach ($report as $item) {
            if (!$item instanceof File) {
                continue;
            }

            $classes = $item->getClassesAndTraits();

            foreach ($classes as $className => $class) {
                $classStatements        = 0;
                $coveredClassStatements = 0;
                $coveredMethods         = 0;
                $classMethods           = 0;

                foreach ($class['methods'] as $method) {
                    if ($method['executableLines'] == 0) {
                        continue;
                    }

                    $classMethods++;
                    $classStatements += $method['executableLines'];
                    $coveredClassStatements += $method['executedLines'];

                    if ($method['coverage'] == 100) {
                        $coveredMethods++;
                    }
                }

                $package = '';

                if (!empty($class['package']['fullPackage'])) {
                    $package = '@' . $class['package']['fullPackage'] . '::';
                }

                $classCoverage[$package . $className] = [
                    'namespace'         => $class['package']['namespace'],
                    'className '        => $className,
                    'methodsCovered'    => $coveredMethods,
                    'methodCount'       => $classMethods,
                    'statementsCovered' => $coveredClassStatements,
                    'statementCount'    => $classStatements,
                ];
            }
        }

        \ksort($classCoverage);

        $methodColor = '';
        $linesColor  = '';
        $resetColor  = '';

        foreach ($classCoverage as $fullQualifiedPath => $classInfo) {
            if ($this->showUncoveredFiles || $classInfo['statementsCovered'] != 0) {
                if ($showColors) {
                    $methodColor = $this->getCoverageColor($classInfo['methodsCovered'], $classInfo['methodCount']);
                    $linesColor  = $this->getCoverageColor($classInfo['statementsCovered'], $classInfo['statementCount']);
                    $resetColor  = $colors['reset'];
                }

                $output .= \PHP_EOL . $fullQualifiedPath . \PHP_EOL
                    . '  ' . $methodColor . 'Methods: ' . $this->printCoverageCounts($classInfo['methodsCovered'], $classInfo['methodCount'], 2) . $resetColor . ' '
                    . '  ' . $linesColor . 'Lines: ' . $this->printCoverageCounts($classInfo['statementsCovered'], $classInfo['statementCount'], 3) . $resetColor;
            }
        }

        return $output . \PHP_EOL;
    }

    private function getCoverageColor(int $numberOfCoveredElements, int $totalNumberOfElements): string
    {
        $coverage = Percentage::fromFractionAndTotal(
            $numberOfCoveredElements,
            $totalNumberOfElements
        );

        if ($coverage->asFloat() >= $this->highLowerBound) {
            return self::COLOR_GREEN;
        }

        if ($coverage->asFloat() > $this->lowUpperBound) {
            return self::COLOR_YELLOW;
        }

        return self::COLOR_RED;
    }

    private function printCoverageCounts(int $numberOfCoveredElements, int $totalNumberOfElements, int $precision): string
    {
        $format = '%' . $precision . 's';

        return Percentage::fromFractionAndTotal(
            $numberOfCoveredElements,
            $totalNumberOfElements
        )->asFixedWidthString() .
            ' (' . \sprintf($format, $numberOfCoveredElements) . '/' .
        \sprintf($format, $totalNumberOfElements) . ')';
    }

    /**
     * @param false|string $string
     */
    private function format(string $color, int $padding, $string): string
    {
        $reset = $color ? self::COLOR_RESET : '';

        return $color . \str_pad((string) $string, $padding) . $reset . \PHP_EOL;
    }
}
