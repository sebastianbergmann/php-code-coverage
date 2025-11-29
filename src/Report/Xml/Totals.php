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

use function sprintf;
use DOMElement;
use SebastianBergmann\CodeCoverage\Util\Percentage;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final readonly class Totals
{
    private DOMElement $linesNode;
    private DOMElement $branchesNode;
    private DOMElement $pathsNode;
    private DOMElement $methodsNode;
    private DOMElement $functionsNode;
    private DOMElement $classesNode;
    private DOMElement $traitsNode;

    public function __construct(DOMElement $container)
    {
        $dom = $container->ownerDocument;

        $this->linesNode = $dom->createElementNS(
            Facade::XML_NAMESPACE,
            'lines',
        );

        $this->branchesNode = $dom->createElementNS(
            'https://schema.phpunit.de/coverage/1.0',
            'branches',
        );

        $this->pathsNode = $dom->createElementNS(
            'https://schema.phpunit.de/coverage/1.0',
            'paths',
        );

        $this->methodsNode = $dom->createElementNS(
            Facade::XML_NAMESPACE,
            'methods',
        );

        $this->functionsNode = $dom->createElementNS(
            Facade::XML_NAMESPACE,
            'functions',
        );

        $this->classesNode = $dom->createElementNS(
            Facade::XML_NAMESPACE,
            'classes',
        );

        $this->traitsNode = $dom->createElementNS(
            Facade::XML_NAMESPACE,
            'traits',
        );

        $container->appendChild($this->linesNode);
        $container->appendChild($this->branchesNode);
        $container->appendChild($this->pathsNode);
        $container->appendChild($this->methodsNode);
        $container->appendChild($this->functionsNode);
        $container->appendChild($this->classesNode);
        $container->appendChild($this->traitsNode);
    }

    public function setNumLines(int $loc, int $cloc, int $ncloc, int $executable, int $executed): void
    {
        $this->linesNode->setAttribute('total', (string) $loc);
        $this->linesNode->setAttribute('comments', (string) $cloc);
        $this->linesNode->setAttribute('code', (string) $ncloc);
        $this->linesNode->setAttribute('executable', (string) $executable);
        $this->linesNode->setAttribute('executed', (string) $executed);
        $this->linesNode->setAttribute(
            'percent',
            $executable === 0 ? '0' : sprintf('%01.2F', Percentage::fromFractionAndTotal($executed, $executable)->asFloat()),
        );
    }

    public function setNumBranches(int $count, int $tested): void
    {
        $this->branchesNode->setAttribute('count', (string) $count);
        $this->branchesNode->setAttribute('tested', (string) $tested);
        $this->branchesNode->setAttribute(
            'percent',
            $count === 0 ? '0' : sprintf('%01.2F', Percentage::fromFractionAndTotal($tested, $count)->asFloat()),
        );
    }

    public function setNumPaths(int $count, int $tested): void
    {
        $this->pathsNode->setAttribute('count', (string) $count);
        $this->pathsNode->setAttribute('tested', (string) $tested);
        $this->pathsNode->setAttribute(
            'percent',
            $count === 0 ? '0' : sprintf('%01.2F', Percentage::fromFractionAndTotal($tested, $count)->asFloat()),
        );
    }

    public function setNumClasses(int $count, int $tested): void
    {
        $this->classesNode->setAttribute('count', (string) $count);
        $this->classesNode->setAttribute('tested', (string) $tested);
        $this->classesNode->setAttribute(
            'percent',
            $count === 0 ? '0' : sprintf('%01.2F', Percentage::fromFractionAndTotal($tested, $count)->asFloat()),
        );
    }

    public function setNumTraits(int $count, int $tested): void
    {
        $this->traitsNode->setAttribute('count', (string) $count);
        $this->traitsNode->setAttribute('tested', (string) $tested);
        $this->traitsNode->setAttribute(
            'percent',
            $count === 0 ? '0' : sprintf('%01.2F', Percentage::fromFractionAndTotal($tested, $count)->asFloat()),
        );
    }

    public function setNumMethods(int $count, int $tested): void
    {
        $this->methodsNode->setAttribute('count', (string) $count);
        $this->methodsNode->setAttribute('tested', (string) $tested);
        $this->methodsNode->setAttribute(
            'percent',
            $count === 0 ? '0' : sprintf('%01.2F', Percentage::fromFractionAndTotal($tested, $count)->asFloat()),
        );
    }

    public function setNumFunctions(int $count, int $tested): void
    {
        $this->functionsNode->setAttribute('count', (string) $count);
        $this->functionsNode->setAttribute('tested', (string) $tested);
        $this->functionsNode->setAttribute(
            'percent',
            $count === 0 ? '0' : sprintf('%01.2F', Percentage::fromFractionAndTotal($tested, $count)->asFloat()),
        );
    }
}
