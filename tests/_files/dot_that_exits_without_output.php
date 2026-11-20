<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*
 * This script stands in for Graphviz' dot in ControlFlowGraphTest: it starts
 * to read the graph it is given, like dot does, but then exits without having
 * written anything, like dot does when it cannot parse the graph it is given.
 */

fgets(STDIN);
