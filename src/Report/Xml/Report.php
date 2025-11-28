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

use function assert;
use function basename;
use function dirname;
use DOMDocument;
use DOMElement;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final class Report extends File
{
    private readonly string $name;
    private readonly string $sha1;

    public function __construct(string $name, string $sha1)
    {
        $dom = new DOMDocument;
        $dom->loadXML('<?xml version="1.0" ?><phpunit xmlns="https://schema.phpunit.de/coverage/1.0"><file /></phpunit>');

        $contextNode = $dom->getElementsByTagNameNS(
            Facade::XML_NAMESPACE,
            'file',
        )->item(0);

        parent::__construct($contextNode);

        $this->name = $name;
        $this->sha1 = $sha1;
    }

    public function asDom(): DOMDocument
    {
        $this->contextNode()->setAttribute('name', basename($this->name));
        $this->contextNode()->setAttribute('path', dirname($this->name));
        $this->contextNode()->setAttribute('hash', $this->sha1);

        return $this->dom;
    }

    public function functionObject(
        string $name,
        string $signature,
        string $start,
        ?string $end,
        string $executable,
        string $executed,
        string $coverage,
        string $crap
    ): void {
        $node = $this->contextNode()->appendChild(
            $this->dom->createElementNS(
                Facade::XML_NAMESPACE,
                'function',
            ),
        );

        assert($node instanceof DOMElement);

        new Method(
            $node,
            $name,
            $signature,
            $start,
            $end,
            $executable,
            $executed,
            $coverage,
            $crap,
        );
    }

    public function classObject(
        string $name,
        string $namespace,
        int $start,
        int $executable,
        int $executed,
        float $crap
    ): Unit {
        $node = $this->contextNode()->appendChild(
            $this->dom->createElementNS(
                Facade::XML_NAMESPACE,
                'class',
            ),
        );

        assert($node instanceof DOMElement);

        return new Unit($node, $name, $namespace, $start, $executable, $executed, $crap);
    }

    public function traitObject(
        string $name,
        string $namespace,
        int $start,
        int $executable,
        int $executed,
        float $crap
    ): Unit {
        $node = $this->contextNode()->appendChild(
            $this->dom->createElementNS(
                Facade::XML_NAMESPACE,
                'trait',
            ),
        );

        assert($node instanceof DOMElement);

        return new Unit($node, $name, $namespace, $start, $executable, $executed, $crap);
    }

    public function source(): Source
    {
        $source = $this->contextNode()->appendChild(
            $this->dom->createElementNS(
                Facade::XML_NAMESPACE,
                'source',
            ),
        );

        assert($source instanceof DOMElement);

        return new Source($source);
    }
}
