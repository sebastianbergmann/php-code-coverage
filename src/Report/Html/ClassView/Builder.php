<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Report\Html\ClassView;

use function array_key_exists;
use function array_keys;
use function count;
use function explode;
use function in_array;
use SebastianBergmann\CodeCoverage\Data\ProcessedClassType;
use SebastianBergmann\CodeCoverage\Data\ProcessedTraitType;
use SebastianBergmann\CodeCoverage\Node\Directory as DirectoryNode;
use SebastianBergmann\CodeCoverage\Node\File as FileNode;
use SebastianBergmann\CodeCoverage\Report\Html\ClassView\Node\ClassNode;
use SebastianBergmann\CodeCoverage\Report\Html\ClassView\Node\NamespaceNode;
use SebastianBergmann\CodeCoverage\Report\Html\ClassView\Node\ParentSection;
use SebastianBergmann\CodeCoverage\Report\Html\ClassView\Node\TraitSection;
use SebastianBergmann\CodeCoverage\StaticAnalysis\Class_;
use SebastianBergmann\CodeCoverage\StaticAnalysis\Trait_;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final class Builder
{
    /**
     * @var array<string, array{file: FileNode, raw: Class_, processed: ProcessedClassType}>
     */
    private array $classRegistry = [];

    /**
     * @var array<string, array{file: FileNode, raw: Trait_, processed: ProcessedTraitType}>
     */
    private array $traitRegistry = [];

    public function build(DirectoryNode $root): NamespaceNode
    {
        $this->classRegistry = [];
        $this->traitRegistry = [];

        $this->collectRegistries($root);

        $rootNamespace = new NamespaceNode('(Global)', '');

        /** @var array<string, NamespaceNode> $namespaceMap */
        $namespaceMap = ['' => $rootNamespace];

        foreach ($this->classRegistry as $fqcn => $entry) {
            $raw       = $entry['raw'];
            $namespace = $raw->namespace();

            $parentNs = $this->ensureNamespaceExists($namespace, $namespaceMap, $rootNamespace);

            $traitSections  = $this->resolveTraits($raw);
            $parentSections = $this->resolveParents($raw);

            $classNode = new ClassNode(
                $fqcn,
                $namespace,
                $entry['file']->pathAsString(),
                $raw->startLine(),
                $raw->endLine(),
                $entry['processed'],
                $entry['file'],
                $traitSections,
                $parentSections,
                $parentNs,
            );

            $parentNs->addClass($classNode);
        }

        return $this->reduceRoot($rootNamespace);
    }

    private function reduceRoot(NamespaceNode $root): NamespaceNode
    {
        while (count($root->childNamespaces()) === 1 && count($root->classes()) === 0) {
            $root = $root->childNamespaces()[0];
        }

        $root->promoteToRoot();

        return $root;
    }

    private function collectRegistries(DirectoryNode $directory): void
    {
        foreach ($directory as $node) {
            if ($node instanceof DirectoryNode) {
                continue;
            }

            if (!$node instanceof FileNode) {
                continue;
            }

            foreach ($node->rawClasses() as $className => $rawClass) {
                if (array_key_exists($className, $node->classes())) {
                    $this->classRegistry[$className] = [
                        'file'      => $node,
                        'raw'       => $rawClass,
                        'processed' => $node->classes()[$className],
                    ];
                }
            }

            foreach ($node->rawTraits() as $traitName => $rawTrait) {
                if (array_key_exists($traitName, $node->traits())) {
                    $this->traitRegistry[$traitName] = [
                        'file'      => $node,
                        'raw'       => $rawTrait,
                        'processed' => $node->traits()[$traitName],
                    ];
                }
            }
        }
    }

    /**
     * @param array<string, NamespaceNode> $namespaceMap
     */
    private function ensureNamespaceExists(string $namespace, array &$namespaceMap, NamespaceNode $rootNamespace): NamespaceNode
    {
        if (isset($namespaceMap[$namespace])) {
            return $namespaceMap[$namespace];
        }

        $parts   = explode('\\', $namespace);
        $current = '';

        $parentNode = $rootNamespace;

        foreach ($parts as $part) {
            $current = $current === '' ? $part : $current . '\\' . $part;

            if (!isset($namespaceMap[$current])) {
                $node = new NamespaceNode($part, $current, $parentNode);
                $parentNode->addNamespace($node);
                $namespaceMap[$current] = $node;
            }

            $parentNode = $namespaceMap[$current];
        }

        return $parentNode;
    }

    /**
     * @return list<TraitSection>
     */
    private function resolveTraits(Class_ $class): array
    {
        $sections = [];

        foreach ($class->traits() as $traitName) {
            if (!isset($this->traitRegistry[$traitName])) {
                continue;
            }

            $entry = $this->traitRegistry[$traitName];

            $sections[] = new TraitSection(
                $traitName,
                $entry['file']->pathAsString(),
                $entry['raw']->startLine(),
                $entry['raw']->endLine(),
                $entry['processed'],
                $entry['file'],
            );
        }

        return $sections;
    }

    /**
     * @return list<ParentSection>
     */
    private function resolveParents(Class_ $class): array
    {
        $sections     = [];
        $ownMethods   = array_keys($class->methods());
        $seenMethods  = $ownMethods;
        $currentClass = $class;

        while ($currentClass->hasParent()) {
            $parentName = $currentClass->parentClass();

            if ($parentName === null || !isset($this->classRegistry[$parentName])) {
                break;
            }

            $parentEntry     = $this->classRegistry[$parentName];
            $parentRaw       = $parentEntry['raw'];
            $parentProcessed = $parentEntry['processed'];

            $inheritedMethods = [];

            foreach ($parentProcessed->methods as $methodName => $method) {
                if (!in_array($methodName, $seenMethods, true)) {
                    $inheritedMethods[$methodName] = $method;
                    $seenMethods[]                 = $methodName;
                }
            }

            if ($inheritedMethods !== []) {
                $sections[] = new ParentSection(
                    $parentName,
                    $parentEntry['file']->pathAsString(),
                    $inheritedMethods,
                    $parentEntry['file'],
                );
            }

            $currentClass = $parentRaw;
        }

        return $sections;
    }
}
