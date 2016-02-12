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
 * Exception that is raised when code is unintentionally covered.
 *
 * @since Class available since Release 2.0.0
 */
class PHP_CodeCoverage_UnintentionallyCoveredCodeException extends PHP_CodeCoverage_RuntimeException
{
    /**
     * @var array
     */
    private $unintentionallyCoveredUnits = [];

    /**
     * @param array $unintentionallyCoveredUnits
     */
    public function __construct(array $unintentionallyCoveredUnits)
    {
        $this->unintentionallyCoveredUnits = $unintentionallyCoveredUnits;

        parent::__construct($this->toString());
    }

    /**
     * @return array
     */
    public function getUnintentionallyCoveredUnits()
    {
        return $this->unintentionallyCoveredUnits;
    }

    /**
     * @return string
     */
    private function toString()
    {
        $message = '';

        foreach ($this->unintentionallyCoveredUnits as $unit) {
            $message .= '- ' . $unit . "\n";
        }

        return $message;
    }
}
