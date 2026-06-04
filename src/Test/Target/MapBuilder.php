<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Test\Target;

use function array_keys;
use function array_merge;
use function array_slice;
use function array_unique;
use function array_values;
use function count;
use function explode;
use function implode;
use function range;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\StaticAnalysis\Class_;
use SebastianBergmann\CodeCoverage\StaticAnalysis\FileAnalyser;
use SebastianBergmann\CodeCoverage\StaticAnalysis\Trait_;

/**
 * @phpstan-import-type TargetMap from Mapper
 * @phpstan-import-type TargetMapPart from Mapper
 * @phpstan-import-type ReverseLookup from Mapper
 *
 * @immutable
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final readonly class MapBuilder
{
    /**
     * @return TargetMap
     */
    public function build(Filter $filter, FileAnalyser $analyser): array
    {
        /**
         * @var array<non-empty-string, Class_> $classDetails
         */
        $classDetails = [];

        $namespaces                    = [];
        $classes                       = [];
        $classesThatExtendClass        = [];
        $classesThatImplementInterface = [];
        $traits                        = [];
        $methods                       = [];
        $functions                     = [];
        $reverseLookup                 = [];

        foreach ($filter->files() as $file) {
            foreach ($analyser->analyse($file)->traits() as $trait) {
                $namespace = $trait->namespace();

                if ($namespace !== '') {
                    $this->processNamespace($namespaces, $namespace, $file, $trait->startLine(), $trait->endLine());
                }

                $this->process($traits, $trait->namespacedName(), $file, $trait->startLine(), $trait->endLine());
                $this->processMethods($trait, $file, $methods, $reverseLookup);
            }
        }

        foreach ($filter->files() as $file) {
            foreach ($analyser->analyse($file)->traits() as $trait) {
                foreach ($trait->traits() as $traitName) {
                    if (!isset($traits[$traitName])) {
                        continue;
                    }

                    $this->mergeLines($trait->namespacedName(), $traits[$traitName], $traits);
                }
            }
        }

        foreach ($filter->files() as $file) {
            $analysisResult = $analyser->analyse($file);

            foreach ($analysisResult->interfaces() as $interface) {
                $classesThatImplementInterface[$interface->namespacedName()] = [];
            }

            foreach ($analysisResult->classes() as $class) {
                $namespace = $class->namespace();

                if ($namespace !== '') {
                    $this->processNamespace($namespaces, $namespace, $file, $class->startLine(), $class->endLine());
                }

                $this->process($classes, $class->namespacedName(), $file, $class->startLine(), $class->endLine());

                foreach ($class->traits() as $traitName) {
                    if (!isset($traits[$traitName])) {
                        continue;
                    }

                    $this->mergeLines($class->namespacedName(), $traits[$traitName], $classes);
                }

                $this->processMethods($class, $file, $methods, $reverseLookup);

                $classesThatExtendClass[$class->namespacedName()] = [];
                $classDetails[$class->namespacedName()]           = $class;
            }

            foreach ($analysisResult->functions() as $function) {
                $namespace = $function->namespace();

                if ($namespace !== '') {
                    $this->processNamespace($namespaces, $namespace, $file, $function->startLine(), $function->endLine());
                }

                $this->process($functions, $function->namespacedName(), $file, $function->startLine(), $function->endLine());

                foreach (range($function->startLine(), $function->endLine()) as $line) {
                    $reverseLookup[$file . ':' . $line] = $function->namespacedName();
                }
            }
        }

        foreach ($namespaces as $namespace => $files) {
            foreach ($files as $file => $lines) {
                $namespaces[$namespace][$file] = array_values(array_unique($lines));
            }
        }

        foreach ($classDetails as $class) {
            foreach ($class->interfaces() as $interfaceName) {
                if (!isset($classesThatImplementInterface[$interfaceName])) {
                    continue;
                }

                $this->process($classesThatImplementInterface, $interfaceName, $class->file(), $class->startLine(), $class->endLine());
            }

            foreach ($this->parentClasses($classDetails, $class) as $parentClass) {
                $this->mergeLines($class->namespacedName(), $classes[$parentClass->namespacedName()] ?? [], $classes);

                if (isset($classesThatExtendClass[$parentClass->namespacedName()])) {
                    $this->process($classesThatExtendClass, $parentClass->namespacedName(), $class->file(), $class->startLine(), $class->endLine());
                }
            }
        }

        foreach (array_keys($classesThatImplementInterface) as $className) {
            if ($classesThatImplementInterface[$className] !== []) {
                continue;
            }

            unset($classesThatImplementInterface[$className]);
        }

        foreach (array_keys($classesThatExtendClass) as $className) {
            if ($classesThatExtendClass[$className] !== []) {
                continue;
            }

            unset($classesThatExtendClass[$className]);
        }

        return [
            'namespaces'                    => $namespaces,
            'traits'                        => $traits,
            'classes'                       => $classes,
            'classesThatExtendClass'        => $classesThatExtendClass,
            'classesThatImplementInterface' => $classesThatImplementInterface,
            'methods'                       => $methods,
            'functions'                     => $functions,
            'reverseLookup'                 => $reverseLookup,
        ];
    }

    /**
     * @param non-empty-string                            $targetClass
     * @param array<non-empty-string, list<positive-int>> $sourceData
     * @param TargetMapPart                               $data
     *
     * @param-out TargetMapPart $data
     */
    private function mergeLines(string $targetClass, array $sourceData, array &$data): void
    {
        /**
         * In large inheritance trees we might handle a lot of data.
         * This loop needs to prevent unnecessary work whenever possible.
         */
        foreach ($sourceData as $file => $lines) {
            if (!isset($data[$targetClass][$file])) {
                $data[$targetClass][$file] = $lines;

                continue;
            }

            if ($data[$targetClass][$file] === $lines) {
                continue;
            }

            $data[$targetClass][$file] = array_values(
                array_unique(
                    array_merge(
                        $data[$targetClass][$file],
                        $lines,
                    ),
                ),
            );
        }
    }

    /**
     * @param non-empty-string $file
     * @param TargetMapPart    $methods
     * @param ReverseLookup    $reverseLookup
     *
     * @param-out TargetMapPart $methods
     * @param-out ReverseLookup $reverseLookup
     */
    private function processMethods(Class_|Trait_ $classOrTrait, string $file, array &$methods, array &$reverseLookup): void
    {
        foreach ($classOrTrait->methods() as $method) {
            $methodName = $classOrTrait->namespacedName() . '::' . $method->name();

            $this->process($methods, $methodName, $file, $method->startLine(), $method->endLine());

            foreach (range($method->startLine(), $method->endLine()) as $line) {
                $reverseLookup[$file . ':' . $line] = $methodName;
            }
        }
    }

    /**
     * @param TargetMapPart    $data
     * @param non-empty-string $namespace
     * @param non-empty-string $file
     * @param positive-int     $startLine
     * @param positive-int     $endLine
     *
     * @param-out TargetMapPart $data
     */
    private function processNamespace(array &$data, string $namespace, string $file, int $startLine, int $endLine): void
    {
        $parts = explode('\\', $namespace);

        foreach (range(1, count($parts)) as $i) {
            $this->process($data, implode('\\', array_slice($parts, 0, $i)), $file, $startLine, $endLine);
        }
    }

    /**
     * @param TargetMapPart    $data
     * @param non-empty-string $unit
     * @param non-empty-string $file
     * @param positive-int     $startLine
     * @param positive-int     $endLine
     *
     * @param-out TargetMapPart $data
     */
    private function process(array &$data, string $unit, string $file, int $startLine, int $endLine): void
    {
        if (!isset($data[$unit])) {
            $data[$unit] = [];
        }

        if (!isset($data[$unit][$file])) {
            $data[$unit][$file] = [];
        }

        $data[$unit][$file] = array_merge(
            $data[$unit][$file],
            range($startLine, $endLine),
        );
    }

    /**
     * @param array<non-empty-string, Class_> $classDetails
     *
     * @return array<Class_>
     */
    private function parentClasses(array $classDetails, Class_ $class): array
    {
        $parentClass = $class->parentClass();

        if ($parentClass === null) {
            return [];
        }

        if (!isset($classDetails[$parentClass])) {
            return [];
        }

        return array_merge(
            [$classDetails[$parentClass]],
            $this->parentClasses($classDetails, $classDetails[$parentClass]),
        );
    }
}
