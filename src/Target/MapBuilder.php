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
use function array_unique;
use function range;
use function sort;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\StaticAnalysis\FileAnalyser;

/**
 * @phpstan-import-type TargetMap from Mapper
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
        $namespaces                    = [];
        $classes                       = [];
        $classDetails                  = [];
        $classesThatExtendClass        = [];
        $classesThatImplementInterface = [];
        $traits                        = [];
        $methods                       = [];
        $functions                     = [];
        $reverseLookup                 = [];

        foreach ($filter->files() as $file) {
            foreach ($analyser->interfacesIn($file) as $interface) {
                $classesThatImplementInterface[$interface->namespacedName()] = [];
            }

            foreach ($analyser->classesIn($file) as $class) {
                if ($class->isNamespaced()) {
                    $this->process($namespaces, $class->namespace(), $file, $class->startLine(), $class->endLine());
                }

                $this->process($classes, $class->namespacedName(), $file, $class->startLine(), $class->endLine());

                foreach ($class->methods() as $method) {
                    $methodName = $class->namespacedName() . '::' . $method->name();

                    $this->process($methods, $methodName, $file, $method->startLine(), $method->endLine());

                    foreach (range($method->startLine(), $method->endLine()) as $line) {
                        $reverseLookup[$file . ':' . $line] = $methodName;
                    }
                }

                $classesThatExtendClass[$class->namespacedName()] = [];
                $classDetails[]                                   = $class;
            }

            foreach ($analyser->traitsIn($file) as $trait) {
                if ($trait->isNamespaced()) {
                    $this->process($namespaces, $trait->namespace(), $file, $trait->startLine(), $trait->endLine());
                }

                $this->process($traits, $trait->namespacedName(), $file, $trait->startLine(), $trait->endLine());

                foreach ($trait->methods() as $method) {
                    $methodName = $trait->namespacedName() . '::' . $method->name();

                    $this->process($methods, $methodName, $file, $method->startLine(), $method->endLine());

                    foreach (range($method->startLine(), $method->endLine()) as $line) {
                        $reverseLookup[$file . ':' . $line] = $methodName;
                    }
                }
            }

            foreach ($analyser->functionsIn($file) as $function) {
                if ($function->isNamespaced()) {
                    $this->process($namespaces, $function->namespace(), $file, $function->startLine(), $function->endLine());
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

                sort($namespaces[$namespace][$file]);
            }
        }

        foreach ($classDetails as $class) {
            foreach ($class->interfaces() as $interfaceName) {
                if (!isset($classesThatImplementInterface[$interfaceName])) {
                    continue;
                }

                $this->process($classesThatImplementInterface, $interfaceName, $class->file(), $class->startLine(), $class->endLine());
            }

            if (!$class->hasParent()) {
                continue;
            }

            if (!isset($classesThatExtendClass[$class->parentClass()])) {
                continue;
            }

            $this->process($classesThatExtendClass, $class->parentClass(), $class->file(), $class->startLine(), $class->endLine());
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
            'classes'                       => $classes,
            'classesThatExtendClass'        => $classesThatExtendClass,
            'classesThatImplementInterface' => $classesThatImplementInterface,
            'traits'                        => $traits,
            'methods'                       => $methods,
            'functions'                     => $functions,
            'reverseLookup'                 => $reverseLookup,
        ];
    }

    /**
     * @param-out array $data
     *
     * @param non-empty-string $unit
     * @param non-empty-string $file
     * @param positive-int     $startLine
     * @param positive-int     $endLine
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
}
