<?php
/**
 * PHP_CodeCoverage
 *
 * Copyright (c) 2009-2014, Sebastian Bergmann <sebastian@phpunit.de>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Sebastian Bergmann nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @category   PHP
 * @package    CodeCoverage
 * @author     Arne Blankerts <arne@blankerts.de>
 * @copyright  2009-2014 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://github.com/sebastianbergmann/php-code-coverage
 * @since      File available since Release 2.0.0
 */

/**
 * @category   PHP
 * @package    CodeCoverage
 * @author     Arne Blankerts <arne@blankerts.de>
 * @copyright  2009-2014 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://github.com/sebastianbergmann/php-code-coverage
 * @since      Class available since Release 2.0.0
 */
class PHP_CodeCoverage_Report_XML_Project extends PHP_CodeCoverage_Report_XML_Node
{
    public function __construct($name)
    {
        $this->init();
        $this->setProjectName($name);
    }

    private function init()
    {
        $dom = new DOMDocument;
        $dom->loadXML('<?xml version="1.0" ?><phpunit xmlns="http://schema.phpunit.de/coverage/1.0"><project/></phpunit>');

        $this->setContextNode(
            $dom->getElementsByTagNameNS(
                'http://schema.phpunit.de/coverage/1.0', 'project'
            )->item(0)
        );
    }

    private function setProjectName($name)
    {
        $this->getContextNode()->setAttribute('name', $name);
    }

    public function getTests()
    {
        $testsNode = $this->getContextNode()->getElementsByTagNameNS(
            'http://schema.phpunit.de/coverage/1.0', 'tests'
        )->item(0);

        if (!$testsNode) {
            $testsNode = $this->getContextNode()->appendChild(
                $this->getDom()->createElementNS(
                    'http://schema.phpunit.de/coverage/1.0', 'tests'
                )
            );
        }

        return new PHP_CodeCoverage_Report_XML_Tests($testsNode);
    }

    public function asDom()
    {
        return $this->getDom();
    }
}
