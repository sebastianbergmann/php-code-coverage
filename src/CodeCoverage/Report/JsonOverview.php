<?php
/*
 * This file is part of the PHP_CodeCoverage package.
 *
 * (c) Jonathan Block <block.jon@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Uses json_encode to write a small json overview containing coverage stats.
 *
 * @category   PHP
 * @package    CodeCoverage
 * @author     Jonathan Block <block.jon@gmail.com>
 * @copyright  Jonathan Block <block.jon@gmail.com>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://github.com/sebastianbergmann/php-code-coverage
 */
class PHP_CodeCoverage_Report_JsonOverview
{
    /**
     * @param  PHP_CodeCoverage $coverage
     * @param  string           $target
     * @return string
     */
    public function process(PHP_CodeCoverage $coverage, $target = null)
    {
        $num_executable_lines = $coverage->getReport()->getNumExecutableLines();
        $num_executed_lines = $coverage->getReport()->getNumExecutedLines();
        $num_classes = $coverage->getReport()->getNumClasses();
        $num_tested_classes = $coverage->getReport()->getNumTestedClasses();
        $num_traits = $coverage->getReport()->getNumTraits();
        $num_tested_traits = $coverage->getReport()->getNumTestedTraits();
        $num_methods = $coverage->getReport()->getNumMethods();
        $num_tested_methods = $coverage->getReport()->getNumTestedMethods();
        $num_functions = $coverage->getReport()->getNumFunctions();
        $num_tested_functions = $coverage->getReport()->getNumTestedFunctions();
        $num_classes_and_traits = $coverage->getReport()->getNumClassesAndTraits();
        $num_tested_classes_and_traits = $coverage->getReport()->getNumTestedClassesAndTraits();
        $num_lines_of_code = $coverage->getReport()->getLinesOfCode();

        $result = array(
            'num_executable_lines' => $num_executable_lines,
            'num_executed_lines' => $num_executed_lines,
            'percentage_tested_lines' => number_format($num_executed_lines/$num_executable_lines*100, 2),
            'num_classes' => $num_classes,
            'num_tested_classes' => $num_tested_classes,
            'percentage_tested_classes' => number_format($num_tested_classes/$num_classes*100, 2),
            'num_traits' => $num_traits,
            'num_tested_traits' => $num_tested_traits,
            'percentage_tested_traits' => number_format($num_tested_traits/$num_traits*100, 2),
            'num_methods' => $num_methods,
            'num_tested_methods' => $coverage->getReport()->getNumTestedMethods(),
            'percentage_tested_methods' => number_format($num_tested_methods / $num_methods * 100, 2),
            'num_functions' => $num_functions,
            'num_tested_functions' => $num_tested_functions,
            'percentage_tested_functions' => number_format($num_tested_functions/$num_functions*100, 2),
            'num_classes_and_traits' => $num_classes_and_traits,
            'num_tested_classes_and_traits' => $num_tested_classes_and_traits,
            'percentage_tested_classes_and_traits' => number_format($num_tested_classes_and_traits/$num_classes_and_traits*100, 2),
            'num_lines_of_code' => $num_lines_of_code,
        );

        $output = json_encode($result, JSON_PRETTY_PRINT);

        if ($target !== null) {
            return file_put_contents($target, $output);
        } else {
            return $output;
        }
    }
}
