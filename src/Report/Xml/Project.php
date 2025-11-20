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
final class Project extends Node
{
    private readonly string $directory;

    public function __construct(string $directory)
    {
        $dom = new DOMDocument;
        $dom->loadXML('<?xml version="1.0" ?><phpunit xmlns="https://schema.phpunit.de/coverage/1.0"><build/><project/></phpunit>');

        parent::__construct(
            $dom->getElementsByTagNameNS(
                Facade::XML_NAMESPACE,
                'project',
            )->item(0),
        );

        $this->directory = $directory;
    }

    public function projectSourceDirectory(): string
    {
        return $this->directory;
    }

    public function buildInformation(): BuildInformation
    {
        $buildNode = $this->dom()->getElementsByTagNameNS(
            Facade::XML_NAMESPACE,
            'build',
        )->item(0);

        if ($buildNode === null) {
            $buildNode = $this->dom()->documentElement->appendChild(
                $this->dom()->createElementNS(
                    Facade::XML_NAMESPACE,
                    'build',
                ),
            );
        }

        assert($buildNode instanceof DOMElement);

        return new BuildInformation($buildNode);
    }

    public function tests(): Tests
    {
        $testsNode = $this->contextNode()->appendChild(
            $this->dom()->createElementNS(
                Facade::XML_NAMESPACE,
                'tests',
            ),
        );

        assert($testsNode instanceof DOMElement);

        return new Tests($testsNode);
    }

    public function asDom(): DOMDocument
    {
        $this->contextNode()->setAttribute('source', $this->directory);

        return $this->dom();
    }
}
