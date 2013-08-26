<?php
class PHP_CodeCoverage_Report_PHPSmart
{
    /**
     * @param  PHP_CodeCoverage $coverage
     * @param  string           $target
     * @return string
     */
    public function process(PHP_CodeCoverage $coverage, $target = NULL)
    {
        $output = '<?php $filter = new PHP_CodeCoverage_Filter();'
            . '$filter->setBlacklistedFiles(' . var_export($coverage->filter()->getBlacklistedFiles(), 1) . ');'
            . '$filter->setWhitelistedFiles(' . var_export($coverage->filter()->getWhitelistedFiles(), 1) . ');'
            . '$object = new PHP_CodeCoverage(new PHP_CodeCoverage_Driver_Xdebug(), $filter); $object->setData('
            . var_export($coverage->getData(), 1) . '); $object->setTests('
            . var_export($coverage->getTests(), 1) . '); return $object;';

        if ($target !== NULL) {
            return file_put_contents($target, $output);
        } else {
            return $output;
        }
    }
}
