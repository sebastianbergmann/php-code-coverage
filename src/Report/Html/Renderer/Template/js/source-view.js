/*
 * Interactions of the source code views: popovers that list the tests that
 * cover a line, highlighting of a path in a control flow graph, and the
 * "back to the top" button.
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

        edges.forEach(function (edge) {
            var element = graph.querySelector('#edge-' + CSS.escape(edge));

            if (element !== null) {
                element.classList.add('highlighted');
            }
        });
    });

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
