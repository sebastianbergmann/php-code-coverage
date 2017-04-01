<?php
/*
 * This file is part of the php-code-coverage package.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SebastianBergmann\CodeCoverage\Report\Xml;

use SebastianBergmann\Environment\Runtime;

class BuildInformation
{
    /**
     * @var \DOMElement
     */
    private $contextNode;

    /**
     * @param \DOMElement $contextNode
     */
    public function __construct(\DOMElement $contextNode)
    {
        $this->contextNode = $contextNode;
    }

    /**
     * @param Runtime $runtime
     */
    public function setRuntimeInformation(Runtime $runtime)
    {
        $runtimeNode = $this->getNodeByName('runtime');

        $runtimeNode->setAttribute('name', $runtime->getName());
        $runtimeNode->setAttribute('version', $runtime->getVersion());
        $runtimeNode->setAttribute('url', $runtime->getVendorUrl());

        if ($runtime->isHHVM()) {
            $runtimeNode->setAttribute('hhvm', constant('HHVM_VERSION'));

            return;
        }

        if ($runtime->hasPHPDBGCodeCoverage()) {
            $runtimeNode->setAttribute('phpdbg', constant('PHPDBG_VERSION'));
        }

        if ($runtime->hasXdebug()) {
            $runtimeNode->setAttribute('xdebug', phpversion('xdebug'));
        }
    }

    /**
     * @param $name
     *
     * @return \DOMElement
     */
    private function getNodeByName($name)
    {
        $node = $this->contextNode->getElementsByTagNameNS(
            'http://schema.phpunit.de/coverage/1.0',
            $name
        )->item(0);

        if (!$node) {
            $node = $this->contextNode->appendChild(
                $this->contextNode->ownerDocument->createElementNS(
                    'http://schema.phpunit.de/coverage/1.0',
                    $name
                )
            );
        }

        return $node;
    }

    /**
     * @param \DateTime $date
     */
    public function setBuildTime(\DateTime $date)
    {
        $this->contextNode->setAttribute('time', $date->format('D M j G:i:s T Y'));
    }

    /**
     * @param string $phpUnitVersion
     * @param string $coverageVersion
     */
    public function setGeneratorVersions($phpUnitVersion, $coverageVersion)
    {
        $this->contextNode->setAttribute('phpunit', $phpUnitVersion);
        $this->contextNode->setAttribute('coverage', $coverageVersion);
    }
}
