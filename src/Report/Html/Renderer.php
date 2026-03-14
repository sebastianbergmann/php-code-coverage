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
use const T_CONST;
use const T_CONTINUE;
use const T_DECLARE;
use const T_DEFAULT;
use const T_DO;
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
use const T_ENUM;
use const T_EVAL;
use const T_EXIT;
use const T_EXTENDS;
use const T_FINAL;
use const T_FINALLY;
use const T_FN;
use const T_FOR;
use const T_FOREACH;
use const T_FUNCTION;
use const T_GLOBAL;
use const T_GOTO;
use const T_HALT_COMPILER;
use const T_IF;
use const T_IMPLEMENTS;
use const T_INCLUDE;
use const T_INCLUDE_ONCE;
use const T_INLINE_HTML;
use const T_INSTANCEOF;
use const T_INSTEADOF;
use const T_INTERFACE;
use const T_ISSET;
use const T_LIST;
use const T_MATCH;
use const T_NAMESPACE;
use const T_NEW;
use const T_PRINT;
use const T_PRIVATE;
use const T_PROTECTED;
use const T_PUBLIC;
use const T_READONLY;
use const T_REQUIRE;
use const T_REQUIRE_ONCE;
use const T_RETURN;
use const T_STATIC;
use const T_SWITCH;
use const T_THROW;
use const T_TRAIT;
use const T_TRY;
use const T_UNSET;
use const T_USE;
use const T_VAR;
use const T_WHILE;
use const T_YIELD;
use const T_YIELD_FROM;
use const TOKEN_PARSE;
use function array_pop;
use function count;
use function explode;
use function file_get_contents;
use function htmlspecialchars;
use function is_string;
use function sprintf;
use function str_ends_with;
use function str_repeat;
use function str_replace;
use function substr_count;
use function token_get_all;
use function trim;
use SebastianBergmann\CodeCoverage\Node\AbstractNode;
use SebastianBergmann\CodeCoverage\Node\Directory as DirectoryNode;
use SebastianBergmann\CodeCoverage\Node\File as FileNode;
use SebastianBergmann\CodeCoverage\Report\Thresholds;
use SebastianBergmann\CodeCoverage\Version;
use SebastianBergmann\Environment\Runtime;
use SebastianBergmann\Template\Template;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
abstract class Renderer
{
    /**
     * @var array<int,true>
     */
    protected const array KEYWORD_TOKENS = [
        T_ABSTRACT      => true,
        T_ARRAY         => true,
        T_AS            => true,
        T_BREAK         => true,
        T_CALLABLE      => true,
        T_CASE          => true,
        T_CATCH         => true,
        T_CLASS         => true,
        T_CLONE         => true,
        T_CONST         => true,
        T_CONTINUE      => true,
        T_DECLARE       => true,
        T_DEFAULT       => true,
        T_DO            => true,
        T_ECHO          => true,
        T_ELSE          => true,
        T_ELSEIF        => true,
        T_EMPTY         => true,
        T_ENDDECLARE    => true,
        T_ENDFOR        => true,
        T_ENDFOREACH    => true,
        T_ENDIF         => true,
        T_ENDSWITCH     => true,
        T_ENDWHILE      => true,
        T_ENUM          => true,
        T_EVAL          => true,
        T_EXIT          => true,
        T_EXTENDS       => true,
        T_FINAL         => true,
        T_FINALLY       => true,
        T_FN            => true,
        T_FOR           => true,
        T_FOREACH       => true,
        T_FUNCTION      => true,
        T_GLOBAL        => true,
        T_GOTO          => true,
        T_HALT_COMPILER => true,
        T_IF            => true,
        T_IMPLEMENTS    => true,
        T_INCLUDE       => true,
        T_INCLUDE_ONCE  => true,
        T_INSTANCEOF    => true,
        T_INSTEADOF     => true,
        T_INTERFACE     => true,
        T_ISSET         => true,
        T_LIST          => true,
        T_MATCH         => true,
        T_NAMESPACE     => true,
        T_NEW           => true,
        T_PRINT         => true,
        T_PRIVATE       => true,
        T_PROTECTED     => true,
        T_PUBLIC        => true,
        T_READONLY      => true,
        T_REQUIRE       => true,
        T_REQUIRE_ONCE  => true,
        T_RETURN        => true,
        T_STATIC        => true,
        T_SWITCH        => true,
        T_THROW         => true,
        T_TRAIT         => true,
        T_TRY           => true,
        T_UNSET         => true,
        T_USE           => true,
        T_VAR           => true,
        T_WHILE         => true,
        T_YIELD         => true,
        T_YIELD_FROM    => true,
    ];

    protected const int HTML_SPECIAL_CHARS_FLAGS = ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE;

    /**
     * @var array<non-empty-string, list<string>>
     */
    protected static array $formattedSourceCache = [];
    protected string $templatePath;
    protected string $generator;
    protected string $date;
    protected Thresholds $thresholds;
    protected bool $hasBranchCoverage;
    protected string $version;

    /**
     * @var array<string, string>
     */
    private array $fileToClassMap = [];

    public function __construct(string $templatePath, string $generator, string $date, Thresholds $thresholds, bool $hasBranchCoverage)
    {
        $this->templatePath      = $templatePath;
        $this->generator         = $generator;
        $this->date              = $date;
        $this->thresholds        = $thresholds;
        $this->version           = Version::id();
        $this->hasBranchCoverage = $hasBranchCoverage;
    }

    /**
     * @param array<string, string> $map
     */
    public function setFileToClassMap(array $map): void
    {
        $this->fileToClassMap = $map;
    }

    /**
     * @param array<non-empty-string, float|int|string> $data
     */
    protected function renderItemTemplate(Template $template, array $data): string
    {
        $numSeparator = '&nbsp;/&nbsp;';

        if (isset($data['numClasses']) && $data['numClasses'] > 0) {
            $classesLevel = $this->colorLevel($data['testedClassesPercent']);

            $classesNumber = $data['numTestedClasses'] . $numSeparator .
                $data['numClasses'];

            $classesBar = $this->coverageBar(
                $data['testedClassesPercent'],
            );
        } else {
            $classesLevel                         = '';
            $classesNumber                        = '0' . $numSeparator . '0';
            $classesBar                           = '';
            $data['testedClassesPercentAsString'] = 'n/a';
        }

        if ($data['numMethods'] > 0) {
            $methodsLevel = $this->colorLevel($data['testedMethodsPercent']);

            $methodsNumber = $data['numTestedMethods'] . $numSeparator .
                $data['numMethods'];

            $methodsBar = $this->coverageBar(
                $data['testedMethodsPercent'],
            );
        } else {
            $methodsLevel                         = '';
            $methodsNumber                        = '0' . $numSeparator . '0';
            $methodsBar                           = '';
            $data['testedMethodsPercentAsString'] = 'n/a';
        }

        if ($data['numExecutableLines'] > 0) {
            $linesLevel = $this->colorLevel($data['linesExecutedPercent']);

            $linesNumber = $data['numExecutedLines'] . $numSeparator .
                $data['numExecutableLines'];

            $linesBar = $this->coverageBar(
                $data['linesExecutedPercent'],
            );
        } else {
            $linesLevel                           = '';
            $linesNumber                          = '0' . $numSeparator . '0';
            $linesBar                             = '';
            $data['linesExecutedPercentAsString'] = 'n/a';
        }

        if ($data['numExecutablePaths'] > 0) {
            $pathsLevel = $this->colorLevel($data['pathsExecutedPercent']);

            $pathsNumber = $data['numExecutedPaths'] . $numSeparator .
                $data['numExecutablePaths'];

            $pathsBar = $this->coverageBar(
                $data['pathsExecutedPercent'],
            );
        } else {
            $pathsLevel                           = '';
            $pathsNumber                          = '0' . $numSeparator . '0';
            $pathsBar                             = '';
            $data['pathsExecutedPercentAsString'] = 'n/a';
        }

        if ($data['numExecutableBranches'] > 0) {
            $branchesLevel = $this->colorLevel($data['branchesExecutedPercent']);

            $branchesNumber = $data['numExecutedBranches'] . $numSeparator .
                $data['numExecutableBranches'];

            $branchesBar = $this->coverageBar(
                $data['branchesExecutedPercent'],
            );
        } else {
            $branchesLevel                           = '';
            $branchesNumber                          = '0' . $numSeparator . '0';
            $branchesBar                             = '';
            $data['branchesExecutedPercentAsString'] = 'n/a';
        }

        $template->setVar(
            [
                'icon'                      => $data['icon'] ?? '',
                'crap'                      => $data['crap'] ?? '',
                'name'                      => $data['name'],
                'lines_bar'                 => $linesBar,
                'lines_executed_percent'    => $data['linesExecutedPercentAsString'],
                'lines_level'               => $linesLevel,
                'lines_number'              => $linesNumber,
                'paths_bar'                 => $pathsBar,
                'paths_executed_percent'    => $data['pathsExecutedPercentAsString'],
                'paths_level'               => $pathsLevel,
                'paths_number'              => $pathsNumber,
                'branches_bar'              => $branchesBar,
                'branches_executed_percent' => $data['branchesExecutedPercentAsString'],
                'branches_level'            => $branchesLevel,
                'branches_number'           => $branchesNumber,
                'methods_bar'               => $methodsBar,
                'methods_tested_percent'    => $data['testedMethodsPercentAsString'],
                'methods_level'             => $methodsLevel,
                'methods_number'            => $methodsNumber,
                'classes_bar'               => $classesBar,
                'classes_tested_percent'    => $data['testedClassesPercentAsString'] ?? '',
                'classes_level'             => $classesLevel,
                'classes_number'            => $classesNumber,
            ],
        );

        return $template->render();
    }

    protected function setCommonTemplateVariables(Template $template, AbstractNode $node): void
    {
        $pathToRoot    = $this->pathToRoot($node);
        $classesTarget = '_classes/index.html';

        if ($node instanceof FileNode && isset($this->fileToClassMap[$node->id()])) {
            $classesTarget = $this->fileToClassMap[$node->id()];
        }

        $template->setVar(
            [
                'id'               => $node->id(),
                'full_path'        => $node->pathAsString(),
                'path_to_root'     => $pathToRoot,
                'breadcrumbs'      => $this->breadcrumbs($node),
                'date'             => $this->date,
                'version'          => $this->version,
                'runtime'          => $this->runtimeString(),
                'generator'        => $this->generator,
                'low_upper_bound'  => (string) $this->thresholds->lowUpperBound(),
                'high_lower_bound' => (string) $this->thresholds->highLowerBound(),
                'view_switcher'    => $this->viewSwitcher($pathToRoot, 'files', 'index.html', $classesTarget),
            ],
        );
    }

    protected function viewSwitcher(string $pathToRoot, string $activeView, string $filesTarget = 'index.html', string $classesTarget = '_classes/index.html'): string
    {
        if ($activeView === 'files') {
            return sprintf(
                '      <ul class="nav nav-tabs mt-2">' . "\n" .
                '       <li class="nav-item"><a class="nav-link active" href="%sindex.html">Files</a></li>' . "\n" .
                '       <li class="nav-item"><a class="nav-link" href="%s%s">Classes</a></li>' . "\n" .
                '      </ul>' . "\n",
                $pathToRoot,
                $pathToRoot,
                $classesTarget,
            );
        }

        return sprintf(
            '      <ul class="nav nav-tabs mt-2">' . "\n" .
            '       <li class="nav-item"><a class="nav-link" href="%s%s">Files</a></li>' . "\n" .
            '       <li class="nav-item"><a class="nav-link active" href="%s_classes/index.html">Classes</a></li>' . "\n" .
            '      </ul>' . "\n",
            $pathToRoot,
            $filesTarget,
            $pathToRoot,
        );
    }

    protected function breadcrumbs(AbstractNode $node): string
    {
        $breadcrumbs = '';
        $path        = $node->pathAsArray();
        $pathToRoot  = [];
        $max         = count($path);

        if ($node instanceof FileNode) {
            $max--;
        }

        for ($i = 0; $i < $max; $i++) {
            $pathToRoot[] = str_repeat('../', $i);
        }

        foreach ($path as $step) {
            if ($step !== $node) {
                $breadcrumbs .= $this->inactiveBreadcrumb(
                    $step,
                    array_pop($pathToRoot),
                );
            } else {
                $breadcrumbs .= $this->activeBreadcrumb($step);
            }
        }

        return $breadcrumbs;
    }

    protected function activeBreadcrumb(AbstractNode $node): string
    {
        $buffer = sprintf(
            '         <li class="breadcrumb-item active">%s</li>' . "\n",
            $node->name(),
        );

        if ($node instanceof DirectoryNode) {
            $buffer .= '         <li class="breadcrumb-item">(<a href="dashboard.html">Dashboard</a>)</li>' . "\n";
        }

        return $buffer;
    }

    protected function inactiveBreadcrumb(AbstractNode $node, string $pathToRoot): string
    {
        return sprintf(
            '         <li class="breadcrumb-item"><a href="%sindex.html">%s</a></li>' . "\n",
            $pathToRoot,
            $node->name(),
        );
    }

    protected function pathToRoot(AbstractNode $node): string
    {
        $id    = $node->id();
        $depth = substr_count($id, '/');

        if ($id !== 'index' &&
            $node instanceof DirectoryNode) {
            $depth++;
        }

        return str_repeat('../', $depth);
    }

    protected function coverageBar(float $percent): string
    {
        $level = $this->colorLevel($percent);

        $templateName = $this->templatePath . ($this->hasBranchCoverage ? 'coverage_bar_branch.html' : 'coverage_bar.html');
        $template     = new Template(
            $templateName,
            '{{',
            '}}',
        );

        $template->setVar(['level' => $level, 'percent' => sprintf('%.2F', $percent)]);

        return $template->render();
    }

    protected function colorLevel(float $percent): string
    {
        if ($percent <= $this->thresholds->lowUpperBound()) {
            return 'danger';
        }

        if ($percent > $this->thresholds->lowUpperBound() &&
            $percent < $this->thresholds->highLowerBound()) {
            return 'warning';
        }

        return 'success';
    }

    protected function renderLine(Template $template, int $lineNumber, string $lineContent, string $class, string $popover): string
    {
        $template->setVar(
            [
                'lineNumber'  => (string) $lineNumber,
                'lineContent' => $lineContent,
                'class'       => $class,
                'popover'     => $popover,
            ],
        );

        return $template->render();
    }

    protected function createPopoverContentForTest(string $test, array $testData): string
    {
        $testCSS = '';

        switch ($testData['status']) {
            case 'success':
                $testCSS = match ($testData['size']) {
                    'small'  => ' class="covered-by-small-tests"',
                    'medium' => ' class="covered-by-medium-tests"',
                    // no break
                    default => ' class="covered-by-large-tests"',
                };

                break;

            case 'failure':
                $testCSS = ' class="danger"';

                break;
        }

        return sprintf(
            '<li%s>%s</li>',
            $testCSS,
            htmlspecialchars($test, self::HTML_SPECIAL_CHARS_FLAGS),
        );
    }

    /**
     * @param non-empty-string $file
     *
     * @return list<string>
     */
    protected function loadFile(string $file): array
    {
        if (isset(self::$formattedSourceCache[$file])) {
            return self::$formattedSourceCache[$file];
        }

        $buffer              = file_get_contents($file);
        $tokens              = token_get_all($buffer, TOKEN_PARSE);
        $result              = [''];
        $i                   = 0;
        $stringFlag          = false;
        $fileEndsWithNewLine = str_ends_with($buffer, "\n");

        unset($buffer);

        foreach ($tokens as $j => $token) {
            if (is_string($token)) {
                if ($token === '"' && $tokens[$j - 1] !== '\\') {
                    $result[$i] .= sprintf(
                        '<span class="string">%s</span>',
                        htmlspecialchars($token, self::HTML_SPECIAL_CHARS_FLAGS),
                    );

                    $stringFlag = !$stringFlag;
                } else {
                    $result[$i] .= sprintf(
                        '<span class="keyword">%s</span>',
                        htmlspecialchars($token, self::HTML_SPECIAL_CHARS_FLAGS),
                    );
                }

                continue;
            }

            [$token, $value] = $token;

            $value = str_replace(
                ["\t", ' '],
                ['&nbsp;&nbsp;&nbsp;&nbsp;', '&nbsp;'],
                htmlspecialchars($value, self::HTML_SPECIAL_CHARS_FLAGS),
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
                            $colour = 'default';

                            if ($this->isInlineHtml($token)) {
                                $colour = 'html';
                            } elseif ($this->isComment($token)) {
                                $colour = 'comment';
                            } elseif ($this->isKeyword($token)) {
                                $colour = 'keyword';
                            }
                        }

                        $result[$i] .= sprintf(
                            '<span class="%s">%s</span>',
                            $colour,
                            $line,
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

        self::$formattedSourceCache[$file] = $result;

        return $result;
    }

    protected function runtimeString(): string
    {
        $runtime = new Runtime;

        return sprintf(
            '<a href="%s" target="_top">%s %s</a>',
            $runtime->getVendorUrl(),
            $runtime->getName(),
            $runtime->getVersion(),
        );
    }

    private function isComment(int $token): bool
    {
        return $token === T_COMMENT || $token === T_DOC_COMMENT;
    }

    private function isInlineHtml(int $token): bool
    {
        return $token === T_INLINE_HTML;
    }

    private function isKeyword(int $token): bool
    {
        return isset(self::KEYWORD_TOKENS[$token]);
    }
}
