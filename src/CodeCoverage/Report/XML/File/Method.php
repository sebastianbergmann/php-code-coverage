<?php
/*
 * This file is part of the PHP_CodeCoverage package.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @since Class available since Release 2.0.0
 */
class PHP_CodeCoverage_Report_XML_File_Method
{
    /**
     * @var DOMElement
     */
    private $contextNode;

    public function __construct(DOMElement $context, $name)
    {
        $this->contextNode = $context;

        $this->setName($name);
    }

    private function setName($name)
    {
        $this->contextNode->setAttribute('name', $name);
    }

    public function setSignature($signature)
    {
        $this->contextNode->setAttribute('signature', $signature);
    }

    public function setLines($start, $end = null)
    {
        $this->contextNode->setAttribute('start', $start);

        if ($end !== null) {
            $this->contextNode->setAttribute('end', $end);
        }
    }

    public function setTotals($executable, $executed, $coverage, $executablePaths, $executedPaths)
    {
        $this->contextNode->setAttribute('executable', $executable);
        $this->contextNode->setAttribute('executed', $executed);
        $this->contextNode->setAttribute('coverage', $coverage);
        $this->contextNode->setAttribute('executablePaths', $executablePaths);
        $this->contextNode->setAttribute('executedPaths', $executedPaths);
    }

    public function setCrap($crap)
    {
        $this->contextNode->setAttribute('crap', $crap);
    }
}
