<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage;

use function array_flip;
use function array_keys;
use function array_merge;
use function array_unique;
use function count;
use function explode;
use function sort;
use ReflectionClass;
use SebastianBergmann\CodeCoverage\Data\RawCodeCoverageData;
use SebastianBergmann\CodeCoverage\Test\Target\Mapper;
use SebastianBergmann\CodeCoverage\Test\Target\Method;
use SebastianBergmann\CodeCoverage\Test\Target\TargetCollection;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @phpstan-import-type TargetedLines from CodeCoverage
 */
final readonly class UnintentionallyCoveredCodeChecker
{
    /**
     * @param TargetedLines      $linesToBeCovered
     * @param TargetedLines      $linesToBeUsed
     * @param list<class-string> $parentClassesExcludedFromCheck
     *
     * @throws ReflectionException
     * @throws UnintentionallyCoveredCodeException
     */
    public function check(RawCodeCoverageData $data, array $linesToBeCovered, array $linesToBeUsed, Mapper $targetMapper, array $parentClassesExcludedFromCheck, TargetCollection $covers, TargetCollection $uses): true
    {
        $allowedLines = $this->allowedLines(
            $linesToBeCovered,
            $linesToBeUsed,
        );

        $unintentionallyCoveredUnits = [];

        foreach ($data->lineCoverage() as $file => $_data) {
            foreach ($_data as $line => $flag) {
                if ($flag === 1 && !isset($allowedLines[$file][$line])) {
                    $unintentionallyCoveredUnits[] = $targetMapper->lookup($file, $line);
                }
            }
        }

        $unintentionallyCoveredUnits = $this->process(
            $unintentionallyCoveredUnits,
            $parentClassesExcludedFromCheck,
            $this->hasMethodLevelTargets($covers, $uses),
        );

        if ($unintentionallyCoveredUnits !== []) {
            throw new UnintentionallyCoveredCodeException(
                $unintentionallyCoveredUnits,
            );
        }

        return true;
    }

    /**
     * @param list<string>       $unintentionallyCoveredUnits
     * @param list<class-string> $parentClassesExcludedFromCheck
     *
     * @throws ReflectionException
     *
     * @return list<string>
     */
    public function process(array $unintentionallyCoveredUnits, array $parentClassesExcludedFromCheck, bool $methodLevelReporting = false): array
    {
        $unintentionallyCoveredUnits = array_unique($unintentionallyCoveredUnits);
        $processed                   = [];

        foreach ($unintentionallyCoveredUnits as $unintentionallyCoveredUnit) {
            $tmp = explode('::', $unintentionallyCoveredUnit);

            if (count($tmp) !== 2) {
                $processed[] = $unintentionallyCoveredUnit;

                continue;
            }

            try {
                $class = new ReflectionClass($tmp[0]);

                foreach ($parentClassesExcludedFromCheck as $parentClass) {
                    if ($class->isSubclassOf($parentClass)) {
                        continue 2;
                    }
                }
            } catch (\ReflectionException $e) {
                throw new ReflectionException(
                    $e->getMessage(),
                    $e->getCode(),
                    $e,
                );
            }

            if ($methodLevelReporting) {
                $processed[] = $unintentionallyCoveredUnit;
            } else {
                $processed[] = $tmp[0];
            }
        }

        $processed = array_unique($processed);

        sort($processed);

        return $processed;
    }

    /**
     * @param TargetedLines $linesToBeCovered
     * @param TargetedLines $linesToBeUsed
     *
     * @return TargetedLines
     */
    public function allowedLines(array $linesToBeCovered, array $linesToBeUsed): array
    {
        $allowedLines = [];

        foreach (array_keys($linesToBeCovered) as $file) {
            if (!isset($allowedLines[$file])) {
                $allowedLines[$file] = [];
            }

            $allowedLines[$file] = array_merge(
                $allowedLines[$file],
                $linesToBeCovered[$file],
            );
        }

        foreach (array_keys($linesToBeUsed) as $file) {
            if (!isset($allowedLines[$file])) {
                $allowedLines[$file] = [];
            }

            $allowedLines[$file] = array_merge(
                $allowedLines[$file],
                $linesToBeUsed[$file],
            );
        }

        foreach (array_keys($allowedLines) as $file) {
            $allowedLines[$file] = array_flip(
                array_unique($allowedLines[$file]),
            );
        }

        return $allowedLines;
    }

    private function hasMethodLevelTargets(TargetCollection $covers, TargetCollection $uses): bool
    {
        foreach ([$covers, $uses] as $targets) {
            foreach ($targets as $target) {
                if ($target instanceof Method) {
                    return true;
                }
            }
        }

        return false;
    }
}
