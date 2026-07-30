/*
 * Recalculates the coverage tables for a subset of the test sizes.
 */
'use strict';

(function () {
    var filter = document.querySelector('.test-size-filter');

    var lowUpperBound = filter ? parseFloat(filter.dataset.lowUpperBound) : 50;
    var highLowerBound = filter ? parseFloat(filter.dataset.highLowerBound) : 90;

    if (isNaN(lowUpperBound)) {
        lowUpperBound = 50;
    }

    if (isNaN(highLowerBound)) {
        highLowerBound = 90;
    }

    /*
     * Metrics that can be recalculated for a subset of the test sizes. Branch
     * and path coverage are not collected per test size.
     */
    var METRICS = ['lines', 'methods', 'classes'];

    /* Maps a combination of test sizes to the suffix used in the coverage data. */
    var SUFFIXES = {
        '': 'All',
        'small': 'Small',
        'medium': 'Medium',
        'large': 'Large',
        'medium+small': 'SM',
        'large+small': 'SL',
        'large+medium': 'ML',
        'large+medium+small': 'SML'
    };

    function level(percent) {
        if (percent <= lowUpperBound) {
            return 'danger';
        }

        if (percent < highLowerBound) {
            return 'warning';
        }

        return 'success';
    }

    function coverageBar(percent) {
        return '<span class="bar" aria-hidden="true"><span style="width: ' + percent.toFixed(2) + '%"></span></span>';
    }

    function slot(container, metric, part) {
        return container.querySelector('[data-metric="' + metric + '-' + part + '"]');
    }

    function updateMetric(container, metric, tested, total) {
        var bar = slot(container, metric, 'bar');

        if (bar === null) {
            return;
        }

        var percentAsString = 'n/a';
        var levelName = '';

        bar.innerHTML = '';

        if (total > 0) {
            var percent = (tested / total) * 100;

            percentAsString = percent.toFixed(2) + '%';
            levelName = level(percent);
            bar.innerHTML = coverageBar(percent);
        }

        var percentage = slot(container, metric, 'percent');
        var count = slot(container, metric, 'number');

        if (percentage !== null) {
            percentage.textContent = percentAsString;
        }

        if (count !== null) {
            count.textContent = tested + ' / ' + total;
        }

        container.querySelectorAll('[data-metric^="' + metric + '-"]').forEach(function (element) {
            element.dataset.level = levelName;
        });
    }

    function applyTestSizeFilter(sizes) {
        var suffix = SUFFIXES[sizes.slice().sort().join('+')] || 'All';

        document.querySelectorAll('[data-coverage]').forEach(function (container) {
            var data;

            try {
                data = JSON.parse(container.dataset.coverage);
            } catch (error) {
                return;
            }

            METRICS.forEach(function (metric) {
                updateMetric(container, metric, data[metric + suffix] || 0, data[metric + 'Total'] || 0);
            });
        });
    }

    if (filter !== null) {
        var checkboxes = Array.prototype.slice.call(filter.querySelectorAll('input[type="checkbox"][data-test-size-filter]'));
        var anyButton = filter.querySelector('[data-test-size-filter-all]');

        var refresh = function () {
            var sizes = checkboxes.filter(function (checkbox) {
                return checkbox.checked;
            }).map(function (checkbox) {
                return checkbox.dataset.testSizeFilter;
            });

            if (anyButton !== null) {
                anyButton.setAttribute('aria-pressed', sizes.length === 0 ? 'true' : 'false');
            }

            applyTestSizeFilter(sizes);
        };

        checkboxes.forEach(function (checkbox) {
            checkbox.addEventListener('change', refresh);
        });

        if (anyButton !== null) {
            anyButton.addEventListener('click', function () {
                checkboxes.forEach(function (checkbox) {
                    checkbox.checked = false;
                });

                refresh();
            });
        }
    }
}());
