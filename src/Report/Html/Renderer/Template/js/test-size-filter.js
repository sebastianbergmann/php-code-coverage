$(function () {
  var $btnGroup = $('[data-test-size-filter], [data-test-size-filter-all]').first().parent();
  var lowBound = parseFloat($btnGroup.data('low-upper-bound')) || 50;
  var highBound = parseFloat($btnGroup.data('high-lower-bound')) || 90;

  function colorLevel(percent) {
    if (percent <= lowBound) {
      return 'danger';
    }

    if (percent < highBound) {
      return 'warning';
    }

    return 'success';
  }

  function coverageBar(percent) {
    var level = colorLevel(percent);
    var p = percent.toFixed(2);

    return '<div class="progress">' +
      '<div class="progress-bar bg-' + level + '" role="progressbar" ' +
      'aria-valuenow="' + p + '" aria-valuemin="0" aria-valuemax="100" ' +
      'style="width: ' + p + '%">' +
      '<span class="visually-hidden">' + p + '% covered (' + level + ')</span>' +
      '</div></div>';
  }

  function filterKey(sizes) {
    if (sizes.length === 0) {
      return 'all';
    }

    var has = {small: false, medium: false, large: false};

    sizes.forEach(function (size) {
      has[size] = true;
    });

    if (has.small && has.medium && has.large) {
      return 'small+medium+large';
    }

    if (has.small && has.medium) {
      return 'small+medium';
    }

    if (has.small && has.large) {
      return 'small+large';
    }

    if (has.medium && has.large) {
      return 'medium+large';
    }

    return sizes[0];
  }

  function dataKeySuffix(filter) {
    switch (filter) {
      case 'small':              return 'Small';
      case 'medium':             return 'Medium';
      case 'large':              return 'Large';
      case 'small+medium':       return 'SM';
      case 'small+large':        return 'SL';
      case 'medium+large':       return 'ML';
      case 'small+medium+large': return 'SML';
      default:                   return 'All';
    }
  }

  function updateMetric($tr, metric, tested, total) {
    var $bar = $tr.find('td[data-metric="' + metric + '-bar"]');

    if (!$bar.length) {
      return '';
    }

    var percent = 0;
    var percentAsString = 'n/a';
    var level = '';
    var bar = '';

    if (total > 0) {
      percent = (tested / total) * 100;
      percentAsString = percent.toFixed(2) + '%';
      level = colorLevel(percent);
      bar = coverageBar(percent);
    }

    $bar.attr('class', level + ' big').html(bar);

    $tr.find('td[data-metric="' + metric + '-percent"]')
      .attr('class', level + ' small')
      .html('<div align="right">' + percentAsString + '</div>');

    $tr.find('td[data-metric="' + metric + '-number"]')
      .attr('class', level + ' small')
      .html('<div align="right">' + tested + '&nbsp;/&nbsp;' + total + '</div>');

    return level;
  }

  function applyFilter(filter) {
    var suffix = dataKeySuffix(filter);

    $('tr[data-coverage]').each(function () {
      var $tr = $(this);
      var raw = $tr.attr('data-coverage');

      if (!raw || raw === '{}') {
        return;
      }

      var data;

      try {
        data = JSON.parse(raw);
      } catch (e) {
        return;
      }

      var linesLevel = updateMetric($tr, 'lines', data['lines' + suffix] || 0, data.linesTotal || 0);

      updateMetric($tr, 'methods', data['methods' + suffix] || 0, data.methodsTotal || 0);
      updateMetric($tr, 'classes', data['classes' + suffix] || 0, data.classesTotal || 0);

      $tr.children('td').first().attr('class', linesLevel);
    });
  }

  function currentSizes() {
    return $btnGroup.find('input[type=checkbox][data-test-size-filter]:checked').map(function () {
      return $(this).data('test-size-filter');
    }).get();
  }

  function refresh() {
    var sizes = currentSizes();
    var $allBtn = $btnGroup.find('[data-test-size-filter-all]');

    if (sizes.length === 0) {
      $allBtn.addClass('active');
    } else {
      $allBtn.removeClass('active');
    }

    applyFilter(filterKey(sizes));
  }

  $btnGroup.on('change', 'input[type=checkbox][data-test-size-filter]', refresh);

  $btnGroup.on('click', '[data-test-size-filter-all]', function () {
    $btnGroup.find('input[type=checkbox][data-test-size-filter]:checked').prop('checked', false);
    refresh();
  });
});
