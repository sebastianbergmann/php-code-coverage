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
use DOMDocument;
use DOMElement;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
abstract class Node
{
    protected readonly DOMDocument $dom;
    private readonly DOMElement $contextNode;

    public function __construct(DOMElement $context)
    {
        $this->dom         = $context->ownerDocument;
        $this->contextNode = $context;
    }

    public function totals(): Totals
    {
        $totalsContainer = $this->contextNode()->firstChild;

        if ($totalsContainer === null) {
            $totalsContainer = $this->contextNode()->appendChild(
                $this->dom->createElementNS(
                    Facade::XML_NAMESPACE,
                    'totals',
                ),
            );
        }

        assert($totalsContainer instanceof DOMElement);

        return new Totals($totalsContainer);
    }

    public function addDirectory(string $name): Directory
    {
        $dirNode = $this->dom->createElementNS(
            Facade::XML_NAMESPACE,
            'directory',
        );

        $dirNode->setAttribute('name', $name);
        $this->contextNode()->appendChild($dirNode);

        return new Directory($dirNode);
    }

    public function addFile(string $name, string $href, string $hash): File
    {
        $fileNode = $this->dom->createElementNS(
            Facade::XML_NAMESPACE,
            'file',
        );

        $fileNode->setAttribute('name', $name);
        $fileNode->setAttribute('href', $href);
        $fileNode->setAttribute('hash', $hash);
        $this->contextNode()->appendChild($fileNode);

        return new File($fileNode);
    }

    protected function contextNode(): DOMElement
    {
        return $this->contextNode;
    }
}
