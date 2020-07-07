<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Report\Html;

use const ENT_COMPAT;
use const ENT_HTML401;
use const ENT_SUBSTITUTE;
use const T_ABSTRACT;
use const T_ARRAY;
use const T_AS;
use const T_BREAK;
use const T_CALLABLE;
use const T_CASE;
use const T_CATCH;
use const T_CLASS;
use const T_CLONE;
use const T_COMMENT;
use const T_CONTINUE;
use const T_DEFAULT;
use const T_DOC_COMMENT;
use const T_ECHO;
use const T_ELSE;
use const T_ELSEIF;
use const T_EMPTY;
use const T_ENDDECLARE;
use const T_ENDFOR;
use const T_ENDFOREACH;
use const T_ENDIF;
use const T_ENDSWITCH;
use const T_ENDWHILE;
use const T_EXIT;
use const T_EXTENDS;
use const T_FINAL;
use const T_FINALLY;
use const T_FOREACH;
use const T_FUNCTION;
use const T_GLOBAL;
use const T_IF;
use const T_IMPLEMENTS;
use const T_INCLUDE;
use const T_INCLUDE_ONCE;
use const T_INLINE_HTML;
use const T_INSTANCEOF;
use const T_INSTEADOF;
use const T_INTERFACE;
use const T_ISSET;
use const T_LOGICAL_AND;
use const T_LOGICAL_OR;
use const T_LOGICAL_XOR;
use const T_NAMESPACE;
use const T_NEW;
use const T_PRIVATE;
use const T_PROTECTED;
use const T_PUBLIC;
use const T_REQUIRE;
use const T_REQUIRE_ONCE;
use const T_RETURN;
use const T_STATIC;
use const T_THROW;
use const T_TRAIT;
use const T_TRY;
use const T_UNSET;
use const T_USE;
use const T_VAR;
use const T_WHILE;
use const T_YIELD;
use function array_key_exists;
use function array_pop;
use function count;
use function explode;
use function file_get_contents;
use function htmlspecialchars;
use function is_string;
use function sprintf;
use function str_replace;
use function substr;
use function token_get_all;
use function trim;
use SebastianBergmann\CodeCoverage\Node\File as FileNode;
use SebastianBergmann\CodeCoverage\Percentage;
use SebastianBergmann\Template\Template;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final class File extends Renderer
{
    /**
     * @var int
     */
    private $htmlSpecialCharsFlags = ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE;

    public function render(FileNode $node, string $file): void
    {
        $templateName = $this->templatePath . ($this->hasBranchCoverage ? 'file_branch.html' : 'file.html');
        $template     = new Template($templateName, '{{', '}}');
        $this->setCommonTemplateVariables($template, $node);

        $template->setVar(
            [
                'items'  => $this->renderItems($node),
                'lines'  => $this->renderSourceByLine($node),
                'legend' => '<p><span class="success"><strong>Executed</strong></span><span class="danger"><strong>Not Executed</strong></span><span class="warning"><strong>Dead Code</strong></span></p>',
            ]
        );

        $template->renderTo($file . '.html');
    }

    private function renderItems(FileNode $node): string
    {
        $templateName = $this->templatePath . ($this->hasBranchCoverage ? 'file_item_branch.html' : 'file_item.html');
        $template     = new Template($templateName, '{{', '}}');

        $methodTemplateName = $this->templatePath . ($this->hasBranchCoverage ? 'method_item_branch.html' : 'method_item.html');
        $methodItemTemplate = new Template(
            $methodTemplateName,
            '{{',
            '}}'
        );

        $items = $this->renderItemTemplate(
            $template,
            [
                'name'                            => 'Total',
                'numClasses'                      => $node->numberOfClassesAndTraits(),
                'numTestedClasses'                => $node->numberOfTestedClassesAndTraits(),
                'numMethods'                      => $node->numberOfFunctionsAndMethods(),
                'numTestedMethods'                => $node->numberOfTestedFunctionsAndMethods(),
                'linesExecutedPercent'            => $node->percentageOfExecutedLines()->asFloat(),
                'linesExecutedPercentAsString'    => $node->percentageOfExecutedLines()->asString(),
                'numExecutedLines'                => $node->numberOfExecutedLines(),
                'numExecutableLines'              => $node->numberOfExecutableLines(),
                'branchesExecutedPercent'         => $node->percentageOfExecutedBranches()->asFloat(),
                'branchesExecutedPercentAsString' => $node->percentageOfExecutedBranches()->asString(),
                'numExecutedBranches'             => $node->numberOfExecutedBranches(),
                'numExecutableBranches'           => $node->numberOfExecutableBranches(),
                'pathsExecutedPercent'            => $node->percentageOfExecutedPaths()->asFloat(),
                'pathsExecutedPercentAsString'    => $node->percentageOfExecutedPaths()->asString(),
                'numExecutedPaths'                => $node->numberOfExecutedPaths(),
                'numExecutablePaths'              => $node->numberOfExecutablePaths(),
                'testedMethodsPercent'            => $node->percentageOfTestedFunctionsAndMethods()->asFloat(),
                'testedMethodsPercentAsString'    => $node->percentageOfTestedFunctionsAndMethods()->asString(),
                'testedClassesPercent'            => $node->percentageOfTestedClassesAndTraits()->asFloat(),
                'testedClassesPercentAsString'    => $node->percentageOfTestedClassesAndTraits()->asString(),
                'crap'                            => '<abbr title="Change Risk Anti-Patterns (CRAP) Index">CRAP</abbr>',
            ]
        );

        $items .= $this->renderFunctionItems(
            $node->functions(),
            $methodItemTemplate
        );

        $items .= $this->renderTraitOrClassItems(
            $node->traits(),
            $template,
            $methodItemTemplate
        );

        $items .= $this->renderTraitOrClassItems(
            $node->classes(),
            $template,
            $methodItemTemplate
        );

        return $items;
    }

    private function renderTraitOrClassItems(array $items, Template $template, Template $methodItemTemplate): string
    {
        $buffer = '';

        if (empty($items)) {
            return $buffer;
        }

        foreach ($items as $name => $item) {
            $numMethods       = 0;
            $numTestedMethods = 0;

            foreach ($item['methods'] as $method) {
                if ($method['executableLines'] > 0) {
                    $numMethods++;

                    if ($method['executedLines'] === $method['executableLines']) {
                        $numTestedMethods++;
                    }
                }
            }

            if ($item['executableLines'] > 0) {
                $numClasses                   = 1;
                $numTestedClasses             = $numTestedMethods === $numMethods ? 1 : 0;
                $linesExecutedPercentAsString = Percentage::fromFractionAndTotal(
                    $item['executedLines'],
                    $item['executableLines']
                )->asString();
                $branchesExecutedPercentAsString = Percentage::fromFractionAndTotal(
                    $item['executedBranches'],
                    $item['executableBranches']
                )->asString();
                $pathsExecutedPercentAsString = Percentage::fromFractionAndTotal(
                    $item['executedPaths'],
                    $item['executablePaths']
                )->asString();
            } else {
                $numClasses                      = 'n/a';
                $numTestedClasses                = 'n/a';
                $linesExecutedPercentAsString    = 'n/a';
                $branchesExecutedPercentAsString = 'n/a';
                $pathsExecutedPercentAsString    = 'n/a';
            }

            $testedMethodsPercentage = Percentage::fromFractionAndTotal(
                $numTestedMethods,
                $numMethods
            );

            $testedClassesPercentage = Percentage::fromFractionAndTotal(
                $numTestedMethods === $numMethods ? 1 : 0,
                1
            );

            $buffer .= $this->renderItemTemplate(
                $template,
                [
                    'name'                         => $this->abbreviateClassName($name),
                    'numClasses'                   => $numClasses,
                    'numTestedClasses'             => $numTestedClasses,
                    'numMethods'                   => $numMethods,
                    'numTestedMethods'             => $numTestedMethods,
                    'linesExecutedPercent'         => Percentage::fromFractionAndTotal(
                        $item['executedLines'],
                        $item['executableLines'],
                    )->asFloat(),
                    'linesExecutedPercentAsString'    => $linesExecutedPercentAsString,
                    'numExecutedLines'                => $item['executedLines'],
                    'numExecutableLines'              => $item['executableLines'],
                    'branchesExecutedPercent'         => Percentage::fromFractionAndTotal(
                        $item['executedBranches'],
                        $item['executableBranches'],
                    )->asFloat(),
                    'branchesExecutedPercentAsString' => $branchesExecutedPercentAsString,
                    'numExecutedBranches'             => $item['executedBranches'],
                    'numExecutableBranches'           => $item['executableBranches'],
                    'pathsExecutedPercent'            => Percentage::fromFractionAndTotal(
                        $item['executedPaths'],
                        $item['executablePaths']
                    )->asFloat(),
                    'pathsExecutedPercentAsString' => $pathsExecutedPercentAsString,
                    'numExecutedPaths'             => $item['executedPaths'],
                    'numExecutablePaths'           => $item['executablePaths'],
                    'testedMethodsPercent'         => $testedMethodsPercentage->asFloat(),
                    'testedMethodsPercentAsString' => $testedMethodsPercentage->asString(),
                    'testedClassesPercent'         => $testedClassesPercentage->asFloat(),
                    'testedClassesPercentAsString' => $testedClassesPercentage->asString(),
                    'crap'                         => $item['crap'],
                ]
            );

            foreach ($item['methods'] as $method) {
                $buffer .= $this->renderFunctionOrMethodItem(
                    $methodItemTemplate,
                    $method,
                    '&nbsp;'
                );
            }
        }

        return $buffer;
    }

    private function renderFunctionItems(array $functions, Template $template): string
    {
        if (empty($functions)) {
            return '';
        }

        $buffer = '';

        foreach ($functions as $function) {
            $buffer .= $this->renderFunctionOrMethodItem(
                $template,
                $function
            );
        }

        return $buffer;
    }

    private function renderFunctionOrMethodItem(Template $template, array $item, string $indent = ''): string
    {
        $numMethods       = 0;
        $numTestedMethods = 0;

        if ($item['executableLines'] > 0) {
            $numMethods = 1;

            if ($item['executedLines'] === $item['executableLines']) {
                $numTestedMethods = 1;
            }
        }

        $executedLinesPercentage = Percentage::fromFractionAndTotal(
            $item['executedLines'],
            $item['executableLines']
        );

        $executedBranchesPercentage = Percentage::fromFractionAndTotal(
            $item['executedBranches'],
            $item['executableBranches']
        );

        $executedPathsPercentage = Percentage::fromFractionAndTotal(
            $item['executedPaths'],
            $item['executablePaths']
        );

        $testedMethodsPercentage = Percentage::fromFractionAndTotal(
            $numTestedMethods,
            1
        );

        return $this->renderItemTemplate(
            $template,
            [
                'name'                         => sprintf(
                    '%s<a href="#%d"><abbr title="%s">%s</abbr></a>',
                    $indent,
                    $item['startLine'],
                    htmlspecialchars($item['signature'], $this->htmlSpecialCharsFlags),
                    $item['functionName'] ?? $item['methodName']
                ),
                'numMethods'                      => $numMethods,
                'numTestedMethods'                => $numTestedMethods,
                'linesExecutedPercent'            => $executedLinesPercentage->asFloat(),
                'linesExecutedPercentAsString'    => $executedLinesPercentage->asString(),
                'numExecutedLines'                => $item['executedLines'],
                'numExecutableLines'              => $item['executableLines'],
                'branchesExecutedPercent'         => $executedBranchesPercentage->asFloat(),
                'branchesExecutedPercentAsString' => $executedBranchesPercentage->asString(),
                'numExecutedBranches'             => $item['executedBranches'],
                'numExecutableBranches'           => $item['executableBranches'],
                'pathsExecutedPercent'            => $executedPathsPercentage->asFloat(),
                'pathsExecutedPercentAsString'    => $executedPathsPercentage->asString(),
                'numExecutedPaths'                => $item['executedPaths'],
                'numExecutablePaths'              => $item['executablePaths'],
                'testedMethodsPercent'            => $testedMethodsPercentage->asFloat(),
                'testedMethodsPercentAsString'    => $testedMethodsPercentage->asString(),
                'crap'                            => $item['crap'],
            ]
        );
    }

    private function renderSourceByLine(FileNode $node): string
    {
        $coverageData = $node->lineCoverageData();
        $testData     = $node->testData();
        $codeLines    = $this->loadFile($node->pathAsString());
        $lines        = '';
        $i            = 1;

        foreach ($codeLines as $line) {
            $trClass        = '';
            $popoverContent = '';
            $popoverTitle   = '';

            if (array_key_exists($i, $coverageData)) {
                $numTests = ($coverageData[$i] ? count($coverageData[$i]) : 0);

                if ($coverageData[$i] === null) {
                    $trClass = ' class="warning"';
                } elseif ($numTests === 0) {
                    $trClass = ' class="danger"';
                } else {
                    $lineCss        = 'covered-by-large-tests';
                    $popoverContent = '<ul>';

                    if ($numTests > 1) {
                        $popoverTitle = $numTests . ' tests cover line ' . $i;
                    } else {
                        $popoverTitle = '1 test covers line ' . $i;
                    }

                    foreach ($coverageData[$i] as $test) {
                        if ($lineCss === 'covered-by-large-tests' && $testData[$test]['size'] === 'medium') {
                            $lineCss = 'covered-by-medium-tests';
                        } elseif ($testData[$test]['size'] === 'small') {
                            $lineCss = 'covered-by-small-tests';
                        }

                        switch ($testData[$test]['status']) {
                            case 0:
                                switch ($testData[$test]['size']) {
                                    case 'small':
                                        $testCSS = ' class="covered-by-small-tests"';

                                        break;

                                    case 'medium':
                                        $testCSS = ' class="covered-by-medium-tests"';

                                        break;

                                    default:
                                        $testCSS = ' class="covered-by-large-tests"';

                                        break;
                                }

                                break;

                            case 1:
                            case 2:
                                $testCSS = ' class="warning"';

                                break;

                            case 3:
                            case 4:
                                $testCSS = ' class="danger"';

                                break;

                            default:
                                $testCSS = '';
                        }

                        $popoverContent .= sprintf(
                            '<li%s>%s</li>',
                            $testCSS,
                            htmlspecialchars($test, $this->htmlSpecialCharsFlags)
                        );
                    }

                    $popoverContent .= '</ul>';
                    $trClass         = ' class="' . $lineCss . ' popin"';
                }
            }

            $popover = '';

            if (!empty($popoverTitle)) {
                $popover = sprintf(
                    ' data-title="%s" data-content="%s" data-placement="top" data-html="true"',
                    $popoverTitle,
                    htmlspecialchars($popoverContent, $this->htmlSpecialCharsFlags)
                );
            }

            $lines .= sprintf(
                '     <tr%s><td%s><div align="right"><a name="%d"></a><a href="#%d">%d</a></div></td><td class="codeLine">%s</td></tr>' . "\n",
                $trClass,
                $popover,
                $i,
                $i,
                $i,
                $line
            );

            $i++;
        }

        return $lines;
    }

    /**
     * @param string $file
     */
    private function loadFile($file): array
    {
        $buffer              = file_get_contents($file);
        $tokens              = token_get_all($buffer);
        $result              = [''];
        $i                   = 0;
        $stringFlag          = false;
        $fileEndsWithNewLine = substr($buffer, -1) === "\n";

        unset($buffer);

        foreach ($tokens as $j => $token) {
            if (is_string($token)) {
                if ($token === '"' && $tokens[$j - 1] !== '\\') {
                    $result[$i] .= sprintf(
                        '<span class="string">%s</span>',
                        htmlspecialchars($token, $this->htmlSpecialCharsFlags)
                    );

                    $stringFlag = !$stringFlag;
                } else {
                    $result[$i] .= sprintf(
                        '<span class="keyword">%s</span>',
                        htmlspecialchars($token, $this->htmlSpecialCharsFlags)
                    );
                }

                continue;
            }

            [$token, $value] = $token;

            $value = str_replace(
                ["\t", ' '],
                ['&nbsp;&nbsp;&nbsp;&nbsp;', '&nbsp;'],
                htmlspecialchars($value, $this->htmlSpecialCharsFlags)
            );

            if ($value === "\n") {
                $result[++$i] = '';
            } else {
                $lines = explode("\n", $value);

                foreach ($lines as $jj => $line) {
                    $line = trim($line);

                    if ($line !== '') {
                        if ($stringFlag) {
                            $colour = 'string';
                        } else {
                            switch ($token) {
                                case T_INLINE_HTML:
                                    $colour = 'html';

                                    break;

                                case T_COMMENT:
                                case T_DOC_COMMENT:
                                    $colour = 'comment';

                                    break;

                                case T_ABSTRACT:
                                case T_ARRAY:
                                case T_AS:
                                case T_BREAK:
                                case T_CALLABLE:
                                case T_CASE:
                                case T_CATCH:
                                case T_CLASS:
                                case T_CLONE:
                                case T_CONTINUE:
                                case T_DEFAULT:
                                case T_ECHO:
                                case T_ELSE:
                                case T_ELSEIF:
                                case T_EMPTY:
                                case T_ENDDECLARE:
                                case T_ENDFOR:
                                case T_ENDFOREACH:
                                case T_ENDIF:
                                case T_ENDSWITCH:
                                case T_ENDWHILE:
                                case T_EXIT:
                                case T_EXTENDS:
                                case T_FINAL:
                                case T_FINALLY:
                                case T_FOREACH:
                                case T_FUNCTION:
                                case T_GLOBAL:
                                case T_IF:
                                case T_IMPLEMENTS:
                                case T_INCLUDE:
                                case T_INCLUDE_ONCE:
                                case T_INSTANCEOF:
                                case T_INSTEADOF:
                                case T_INTERFACE:
                                case T_ISSET:
                                case T_LOGICAL_AND:
                                case T_LOGICAL_OR:
                                case T_LOGICAL_XOR:
                                case T_NAMESPACE:
                                case T_NEW:
                                case T_PRIVATE:
                                case T_PROTECTED:
                                case T_PUBLIC:
                                case T_REQUIRE:
                                case T_REQUIRE_ONCE:
                                case T_RETURN:
                                case T_STATIC:
                                case T_THROW:
                                case T_TRAIT:
                                case T_TRY:
                                case T_UNSET:
                                case T_USE:
                                case T_VAR:
                                case T_WHILE:
                                case T_YIELD:
                                    $colour = 'keyword';

                                    break;

                                default:
                                    $colour = 'default';
                            }
                        }

                        $result[$i] .= sprintf(
                            '<span class="%s">%s</span>',
                            $colour,
                            $line
                        );
                    }

                    if (isset($lines[$jj + 1])) {
                        $result[++$i] = '';
                    }
                }
            }
        }

        if ($fileEndsWithNewLine) {
            unset($result[count($result) - 1]);
        }

        return $result;
    }

    private function abbreviateClassName(string $className): string
    {
        $tmp = explode('\\', $className);

        if (count($tmp) > 1) {
            $className = sprintf(
                '<abbr title="%s">%s</abbr>',
                $className,
                array_pop($tmp)
            );
        }

        return $className;
    }
}
