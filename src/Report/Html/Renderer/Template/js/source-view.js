/*
 * Interactions of the source code views: popovers that list the tests that
 * cover a line, highlighting of a path in a control flow graph, navigation
 * between code that is not covered, and the "back to the top" button.
 */
'use strict';

(function () {
    /* ---------------------------------------------------------------- popover */

    var popover = document.createElement('div');

    popover.className = 'popover';
    popover.setAttribute('popover', 'auto');

    document.body.appendChild(popover);

    var currentTrigger = null;

    function position(trigger) {
        var anchor = trigger.getBoundingClientRect();
        var box = popover.getBoundingClientRect();
        var margin = 8;

        var left = Math.min(
            Math.max(margin, anchor.left),
            Math.max(margin, window.innerWidth - box.width - margin)
        );

        var top = anchor.top - box.height - margin;

        if (top < margin) {
            top = Math.min(
                anchor.bottom + margin,
                Math.max(margin, window.innerHeight - box.height - margin)
            );
        }

        popover.style.left = left + 'px';
        popover.style.top = top + 'px';
    }

    function open(trigger) {
        var title = document.createElement('p');
        var body = document.createElement('div');

        title.className = 'popover-title';
        title.textContent = trigger.dataset.popoverTitle;
        body.innerHTML = trigger.dataset.popoverContent || '';

        popover.replaceChildren(title, body);
        popover.showPopover();

        currentTrigger = trigger;

        position(trigger);
    }

    popover.addEventListener('toggle', function (event) {
        if (event.newState === 'closed') {
            currentTrigger = null;
        }
    });

    document.addEventListener('click', function (event) {
        // A link in the popover leads to a line of the source code
        if (event.target.closest('.popover a') !== null) {
            popover.hidePopover();

            return;
        }

        var trigger = event.target.closest('[data-popover-title]');

        if (trigger === null) {
            return;
        }

        if (trigger === currentTrigger) {
            popover.hidePopover();

            return;
        }

        open(trigger);
    });

    /* --------------------------------------------------- control flow graphs */

    function graphFor(row) {
        var element = row.closest('table');

        while (element !== null) {
            element = element.nextElementSibling;

            if (element !== null && element.classList.contains('cfg-graph')) {
                return element;
            }
        }

        return null;
    }

    document.addEventListener('click', function (event) {
        if (event.target.closest('[data-popover-title]') !== null) {
            return;
        }

        var row = event.target.closest('.path-row');

        if (row === null) {
            return;
        }

        var graph = graphFor(row);

        if (graph === null) {
            return;
        }

        graph.querySelectorAll('.edge.highlighted, .node.highlighted').forEach(function (element) {
            element.classList.remove('highlighted');
        });

        var selected = row.classList.contains('path-selected');

        document.querySelectorAll('.path-row.path-selected').forEach(function (element) {
            element.classList.remove('path-selected');
        });

        if (selected) {
            return;
        }

        row.classList.add('path-selected');

        var paths;

        try {
            paths = JSON.parse(graph.dataset.paths);
        } catch (error) {
            return;
        }

        var edges = paths[row.dataset.pathIndex];

        if (!edges) {
            return;
        }

        var svgGraph = graph.querySelector('g.graph');

        if (svgGraph === null) {
            return;
        }

        edges.forEach(function (edge) {
            var element = graph.querySelector('#' + CSS.escape(svgGraph.id + '_edge-' + edge));

            if (element !== null) {
                element.classList.add('highlighted');
            }
        });
    });

    /* ----------------------------------- navigation between code that is not covered */

    /*
     * A line that is not covered is where to go. In the views for branch and
     * path coverage, so is a line that is only partially covered and a line
     * that ends with a decision of which an outcome was not taken. In the view
     * for line coverage, "warning" marks code that cannot be covered.
     */
    function isNotCovered(row, view) {
        if (row.classList.contains('danger')) {
            return true;
        }

        if (view !== 'branch' && view !== 'path') {
            return false;
        }

        return row.classList.contains('warning') || row.querySelector('.branch-miss') !== null;
    }

    /*
     * Consecutive lines that are not covered are one stop. Lines that are not
     * executable, blank lines and comments for instance, do not separate them.
     */
    var stops = [];
    var partial = false;

    document.querySelectorAll('table.source').forEach(function (table) {
        var view = table.dataset.view;
        var inStop = false;

        if (view === 'branch' || view === 'path') {
            partial = true;
        }

        Array.prototype.forEach.call(table.rows, function (row) {
            if (isNotCovered(row, view)) {
                if (!inStop) {
                    stops.push(row);
                }

                inStop = true;
            } else if (row.className !== '') {
                inStop = false;
            }
        });
    });

    if (stops.length > 0) {
        var what = partial ? 'code that is not fully covered' : 'code that is not covered';
        var arrow = '<svg viewBox="0 0 16 16" width="12" height="12" aria-hidden="true"><path d="M8 3.5 14 11H2z"/></svg>';
        var current = -1;

        var navigation = document.createElement('nav');
        var counter = document.createElement('span');

        var button = function (className, label, key) {
            var element = document.createElement('button');

            element.type = 'button';
            element.className = className;
            element.title = label + ' (' + key + ')';
            element.innerHTML = arrow + '<span class="visually-hidden">' + label + '</span>';

            return element;
        };

        var previous = button('uncovered-nav-previous', 'Previous ' + what, 'p');
        var next = button('uncovered-nav-next', 'Next ' + what, 'n');

        navigation.className = 'uncovered-nav';
        navigation.setAttribute('aria-label', 'Navigation between ' + what);

        counter.className = 'uncovered-nav-counter';
        counter.setAttribute('aria-live', 'polite');
        counter.textContent = '\u2013 / ' + stops.length;

        navigation.append(previous, counter, next);
        document.body.appendChild(navigation);

        var isVisible = function (row) {
            var box = row.getBoundingClientRect();

            return box.bottom > 0 && box.top < window.innerHeight;
        };

        /*
         * Without a current stop, or when the current stop was scrolled out
         * of view, where to go depends on what is shown: forward to the first
         * stop that is not above what is shown, backward to the last stop
         * that is above it.
         */
        var stopFromScrollPosition = function (direction) {
            var index;

            if (direction > 0) {
                for (index = 0; index < stops.length; index++) {
                    if (stops[index].getBoundingClientRect().top >= 0) {
                        return index;
                    }
                }

                return 0;
            }

            for (index = stops.length - 1; index >= 0; index--) {
                if (stops[index].getBoundingClientRect().top < 0) {
                    return index;
                }
            }

            return stops.length - 1;
        };

        var go = function (direction) {
            var from = current;

            if (from !== -1) {
                stops[from].classList.remove('nav-current');
            }

            if (from === -1 || !isVisible(stops[from])) {
                current = stopFromScrollPosition(direction);

                if (current === from) {
                    current = (from + direction + stops.length) % stops.length;
                }
            } else {
                current = (from + direction + stops.length) % stops.length;
            }

            stops[current].classList.add('nav-current');
            stops[current].scrollIntoView({block: 'center'});

            counter.textContent = (current + 1) + ' / ' + stops.length;
        };

        previous.addEventListener('click', function () {
            go(-1);
        });

        next.addEventListener('click', function () {
            go(1);
        });

        document.addEventListener('keydown', function (event) {
            if (event.defaultPrevented || event.ctrlKey || event.altKey || event.metaKey) {
                return;
            }

            if (event.target instanceof Element && event.target.closest('textarea, select, [contenteditable], input:not([type="checkbox"]):not([type="radio"])') !== null) {
                return;
            }

            if (event.key === 'n') {
                go(1);
            } else if (event.key === 'p' || event.key === 'N') {
                go(-1);
            }
        });
    }

    /* --------------------------------------------------------- back to the top */

    var toTop = document.querySelector('.to-top');

    if (toTop === null) {
        return;
    }

    var update = function () {
        toTop.hidden = window.scrollY < window.innerHeight / 2;
    };

    window.addEventListener('scroll', update, {passive: true});

    toTop.addEventListener('click', function (event) {
        event.preventDefault();

        window.scrollTo({top: 0});
    });

    update();
}());
