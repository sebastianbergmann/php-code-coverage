#!/usr/bin/env php
<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Regenerates the expected HTML report files in tests/_files/Report/HTML.
 *
 * The generated files are compared using assertStringMatchesFormatFile(),
 * therefore all values that vary between runs (version, runtime, date, paths)
 * are replaced with %s placeholders; directory separators that follow the
 * fixture path are replaced with %e so that the expectations also match on
 * Windows. The contents of control flow graph containers are replaced with
 * %A: the SVG markup varies between Graphviz versions and is omitted entirely
 * when the dot tool is not available. In large file views, everything after
 * the coverage summary table (source listing, footer, scripts) is collapsed
 * to %a: PCRE cannot compile the format description of a fully literal large
 * file view into a regular expression ("regular expression is too large").
 * The collapse is only applied when the fully literal format description
 * cannot be compiled and matched.
 */
require __DIR__ . '/../../tests/bootstrap.php';

use SebastianBergmann\CodeCoverage\CoverageFixtureProvider;
use SebastianBergmann\CodeCoverage\Report\Html\Facade;
use SebastianBergmann\CodeCoverage\Version;
use SebastianBergmann\Environment\Runtime;

function removeDirectory(string $path): void
{
    if (!is_dir($path)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($iterator as $fileInfo) {
        if ($fileInfo->isDir()) {
            rmdir($fileInfo->getPathname());
        } else {
            unlink($fileInfo->getPathname());
        }
    }

    rmdir($path);
}

function withPlaceholders(string $content): string
{
    $runtime = new Runtime;

    $runtimeString = sprintf(
        '<a href="%s" target="_top">%s %s</a>',
        $runtime->getVendorUrl(),
        $runtime->getName(),
        $runtime->getVersion(),
    );

    $content = str_replace($runtimeString, '<a href="%s" target="_top">%s</a>', $content);
    $content = preg_replace('#( at )[^<]+(\.</small>)#', '${1}%s${2}', $content);
    $content = str_replace('php-code-coverage ' . Version::id() . '</a>', 'php-code-coverage %s</a>', $content);
    $content = str_replace('?v=' . Version::id(), '?v=%s', $content);
    $content = preg_replace('#(<div class="cfg-graph"[^>]*>).*?(</div>)#s', '${1}%A${2}', $content);

    return preg_replace_callback(
        '/' . preg_quote(rtrim(TEST_FILES_PATH, DIRECTORY_SEPARATOR), '/') . '(' . preg_quote(DIRECTORY_SEPARATOR, '/') . '[^<"]*)?/',
        static function (array $matches): string
        {
            if (!isset($matches[1])) {
                return '%s';
            }

            return '%s' . str_replace(DIRECTORY_SEPARATOR, '%e', substr($matches[1], 1));
        },
        $content,
    );
}

function collapseSourceListing(string $content): string
{
    $marker = "    </table>\n   </div>\n";
    $start  = strpos($content, $marker);
    $body   = strpos($content, ' </body>');

    if ($start === false || $body === false) {
        return $content;
    }

    $start += strlen($marker);

    return substr($content, 0, $start) . '%a' . "\n" . substr($content, $body);
}

/**
 * Replicates how assertStringMatchesFormatFile() turns a format description
 * into a regular expression and checks that the format both compiles and
 * matches the given subject.
 */
function formatMatches(string $format, string $subject): bool
{
    $regex = strtr(
        preg_quote($format, '/'),
        [
            '%%' => '%',
            '%e' => preg_quote(DIRECTORY_SEPARATOR, '/'),
            '%s' => '[^\r\n]+',
            '%S' => '[^\r\n]*',
            '%a' => '.+',
            '%A' => '.*',
            '%w' => '\s*',
            '%i' => '[+-]?\d+',
            '%d' => '\d+',
            '%x' => '[0-9a-fA-F]+',
            '%f' => '[+-]?\.?\d+\.?\d*(?:[eE][+-]?\d+)?',
            '%c' => '.',
        ],
    );

    return @preg_match('/^' . $regex . '$/s', $subject) === 1;
}

$provider = new CoverageFixtureProvider;

$scenarios = [
    'CoverageForBankAccount'                     => $provider->lineCoverageForBankAccount(...),
    'CoverageForBankAccountWithTestSizes'        => $provider->coverageForBankAccountWithVariousTestSizesAndStatuses(...),
    'PathCoverageForBankAccount'                 => $provider->pathCoverageForBankAccount(...),
    'PathCoverageForSourceWithoutNamespace'      => $provider->pathCoverageForSourceWithoutNamespace(...),
    'CoverageForFileWithIgnoredLines'            => $provider->coverageForFileWithIgnoredLines(...),
    'CoverageForClassWithAnonymousFunction'      => $provider->coverageForClassWithAnonymousFunction(...),
    'CoverageForClassesWithTraitsAndInheritance' => $provider->coverageForClassesWithTraitsAndInheritance(...),
];

$expectationPath = TEST_FILES_PATH . 'Report' . DIRECTORY_SEPARATOR . 'HTML' . DIRECTORY_SEPARATOR;
$temporaryPath   = TEST_FILES_PATH . 'tmp';

foreach ($scenarios as $expectationDirectoryName => $coverage) {
    removeDirectory($temporaryPath);

    (new Facade)->process($coverage()->getReport(), $temporaryPath);

    $expectationDirectory = $expectationPath . $expectationDirectoryName;

    removeDirectory($expectationDirectory);
    mkdir($expectationDirectory, 0o777, true);

    $generatedFiles = new RegexIterator(
        new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($temporaryPath, RecursiveDirectoryIterator::SKIP_DOTS),
        ),
        '/\.html$/',
    );

    $numberOfFiles = 0;

    foreach ($generatedFiles as $generatedFile) {
        $relativePath = substr($generatedFile->getPathname(), strlen($temporaryPath) + 1);
        $generated    = str_replace(PHP_EOL, "\n", file_get_contents($generatedFile->getPathname()));
        $content      = withPlaceholders($generated);

        if (!formatMatches($content, $generated)) {
            $content = collapseSourceListing($content);
        }

        if (!formatMatches($content, $generated)) {
            print 'Format description for ' . $expectationDirectoryName . '/' . $relativePath . ' does not match the generated file' . PHP_EOL;

            exit(1);
        }

        $targetFile      = $expectationDirectory . DIRECTORY_SEPARATOR . $relativePath;
        $targetDirectory = dirname($targetFile);

        if (!is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0o777, true);
        }

        file_put_contents($targetFile, $content);

        $numberOfFiles++;
    }

    print $expectationDirectoryName . ': ' . $numberOfFiles . ' files' . PHP_EOL;
}

removeDirectory($temporaryPath);
