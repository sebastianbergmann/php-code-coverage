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
use DOMElement;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final readonly class Unit
{
    private DOMElement $contextNode;

    public function __construct(
        DOMElement $context,
        string $name,
        string $namespace,
        int $start,
        int $executable,
        int $executed,
        float $crap
    ) {
        $this->contextNode = $context;

        $this->contextNode->setAttribute('name', $name);
        $this->contextNode->setAttribute('start', (string) $start);
        $this->contextNode->setAttribute('executable', (string) $executable);
        $this->contextNode->setAttribute('executed', (string) $executed);
        $this->contextNode->setAttribute('crap', (string) $crap);

        $node = $this->contextNode->appendChild(
            $this->contextNode->ownerDocument->createElementNS(
                Facade::XML_NAMESPACE,
                'namespace',
            ),
        );
        assert($node instanceof DOMElement);

        $node->setAttribute('name', $namespace);
    }

    public function addMethod(
        string $name,
        string $signature,
        string $start,
        ?string $end,
        string $executable,
        string $executed,
        string $coverage,
        string $crap
    ): void {
        $node = $this->contextNode->appendChild(
            $this->contextNode->ownerDocument->createElementNS(
                Facade::XML_NAMESPACE,
                'method',
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
}
