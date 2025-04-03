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
use function array_merge_recursive;
use function array_slice;
use function array_unique;
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
            foreach ($analyser->traitsIn($file) as $trait) {
                if ($trait->isNamespaced()) {
                    $this->processNamespace($namespaces, $trait->namespace(), $file, $trait->startLine(), $trait->endLine());
                }

                $this->process($traits, $trait->namespacedName(), $file, $trait->startLine(), $trait->endLine());
                $this->processMethods($trait, $file, $methods, $reverseLookup);
            }
        }

        foreach ($filter->files() as $file) {
            foreach ($analyser->traitsIn($file) as $trait) {
                foreach ($trait->traits() as $traitName) {
                    if (!isset($traits[$traitName])) {
                        continue;
                    }

                    $file = array_keys($traits[$traitName])[0];

                    if (!isset($traits[$trait->namespacedName()][$file])) {
                        $traits[$trait->namespacedName()][$file] = $traits[$traitName][$file];

                        continue;
                    }

                    $traits[$trait->namespacedName()][$file] = array_unique(
                        array_merge(
                            $traits[$trait->namespacedName()][$file],
                            $traits[$traitName][$file],
                        ),
                    );
                }
            }
        }

        foreach ($filter->files() as $file) {
            foreach ($analyser->interfacesIn($file) as $interface) {
                $classesThatImplementInterface[$interface->namespacedName()] = [];
            }

            foreach ($analyser->classesIn($file) as $class) {
                if ($class->isNamespaced()) {
                    $this->processNamespace($namespaces, $class->namespace(), $file, $class->startLine(), $class->endLine());
                }

                $this->process($classes, $class->namespacedName(), $file, $class->startLine(), $class->endLine());

                foreach ($class->traits() as $traitName) {
                    if (!isset($traits[$traitName])) {
                        continue;
                    }

                    foreach ($traits[$traitName] as $traitFile => $lines) {
                        if (!isset($classes[$class->namespacedName()][$traitFile])) {
                            $classes[$class->namespacedName()][$traitFile] = $lines;

                            continue;
                        }

                        $classes[$class->namespacedName()][$traitFile] = array_unique(
                            array_merge(
                                $classes[$class->namespacedName()][$traitFile],
                                $lines,
                            ),
                        );
                    }
                }

                $this->processMethods($class, $file, $methods, $reverseLookup);

                $classesThatExtendClass[$class->namespacedName()] = [];
                $classDetails[$class->namespacedName()]           = $class;
            }

            foreach ($analyser->functionsIn($file) as $function) {
                if ($function->isNamespaced()) {
                    $this->processNamespace($namespaces, $function->namespace(), $file, $function->startLine(), $function->endLine());
                }

                $this->process($functions, $function->namespacedName(), $file, $function->startLine(), $function->endLine());

                foreach (range($function->startLine(), $function->endLine()) as $line) {
                    $reverseLookup[$file . ':' . $line] = $function->namespacedName();
                }
            }
        }

        foreach (array_keys($namespaces) as $namespace) {
            foreach (array_keys($namespaces[$namespace]) as $file) {
                $namespaces[$namespace][$file] = array_unique($namespaces[$namespace][$file]);
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
                $classes[$class->namespacedName()] = array_merge_recursive(
                    $classes[$class->namespacedName()],
                    $classes[$parentClass->namespacedName()],
                );

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

        /**
         * @todo Avoid duplication and remove this loop
         */
        foreach (array_keys($classes) as $className) {
            foreach (array_keys($classes[$className]) as $file) {
                $classes[$className][$file] = array_unique($classes[$className][$file]);
            }
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
        if (!$class->hasParent()) {
            return [];
        }

        if (!isset($classDetails[$class->parentClass()])) {
            return [];
        }

        return array_merge(
            [$classDetails[$class->parentClass()]],
            $this->parentClasses($classDetails, $classDetails[$class->parentClass()]),
        );
    }
}
