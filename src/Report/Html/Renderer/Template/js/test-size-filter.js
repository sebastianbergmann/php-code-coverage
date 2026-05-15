$(function () {
  var $btnGroup = $('[data-test-size-filter], [data-test-size-filter-all]').first().parent();
  var lowBound = parseFloat($btnGroup.data('low-upper-bound')) || 50;
  var highBound = parseFloat($btnGroup.data('high-lower-bound')) || 90;

  function colorLevel(percent) {
    if (percent <= lowBound) return 'danger';
    if (percent < highBound) return 'warning';
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

  function pct(n, total) {
    return total > 0 ? (n / total) * 100 : 0;
  }

  function filterKey(sizes) {
    if (sizes.length === 0) return 'all';
    if (sizes.length === 1) return sizes[0];
    if (sizes.length === 3) return 'small+medium+large';
    var has = {small: false, medium: false, large: false};
    sizes.forEach(function (s) { has[s] = true; });
    if (has.small && has.medium) return 'small+medium';
    if (has.small && has.large) return 'small+large';
    if (has.medium && has.large) return 'medium+large';
    return 'all';
  }

  function dataKeys(filter) {
    switch (filter) {
      case 'small':              return {lines: 'linesSmall',  methods: 'methodsSmall',  classes: 'classesSmall'};
      case 'medium':             return {lines: 'linesMedium', methods: 'methodsMedium', classes: 'classesMedium'};
      case 'large':              return {lines: 'linesLarge',  methods: 'methodsLarge',  classes: 'classesLarge'};
      case 'small+medium':       return {lines: 'linesSM',     methods: 'methodsSM',     classes: 'classesSM'};
      case 'small+large':        return {lines: 'linesSL',     methods: 'methodsSL',     classes: 'classesSL'};
      case 'medium+large':       return {lines: 'linesML',     methods: 'methodsML',     classes: 'classesML'};
      case 'small+medium+large': return {lines: 'linesSML',    methods: 'methodsSML',    classes: 'classesSML'};
      default:                   return {lines: 'linesAll',    methods: 'methodsAll',    classes: 'classesAll'};
    }
  }

  function applyFilter(filter) {
    var keys = dataKeys(filter);

    $('tr[data-coverage]').each(function () {
      var $tr = $(this);
      var raw = $tr.attr('data-coverage');

      if (!raw || raw === '{}') return;

      var d;
      try { d = JSON.parse(raw); } catch (e) { return; }

      var linesTotal = d.linesTotal || 0;
      var methodsTotal = d.methodsTotal || 0;
      var classesTotal = d.classesTotal || 0;

      var linesExec = d[keys.lines] || 0;
      var methodsTested = d[keys.methods] || 0;
      var classesTested = d[keys.classes] || 0;

      var linesPct = pct(linesExec, linesTotal);
      var methodsPct = pct(methodsTested, methodsTotal);
      var classesPct = pct(classesTested, classesTotal);

      var cells = $tr.children('td');
      var nameCell = cells.eq(0);
      var idx = 1;

      var linesLevel = linesTotal > 0 ? colorLevel(linesPct) : '';

      cells.eq(idx).attr('class', linesLevel + ' big').html(linesTotal > 0 ? coverageBar(linesPct) : '');
      cells.eq(idx + 1).attr('class', linesLevel + ' small').html(
        '<div align="right">' + (linesTotal > 0 ? linesPct.toFixed(2) + '%' : 'n/a') + '</div>'
      );
      cells.eq(idx + 2).attr('class', linesLevel + ' small').html(
        '<div align="right">' + linesExec + '&nbsp;/&nbsp;' + linesTotal + '</div>'
      );
      idx += 3;

      var totalCells = cells.length;
      var methodsIdx, classesIdx, hasClasses;

      if (totalCells >= 16) {
        methodsIdx = 10;
        classesIdx = 14;
        hasClasses = totalCells >= 17;
      } else if (totalCells >= 11) {
        methodsIdx = 4;
        classesIdx = 8;
        hasClasses = true;
      } else if (totalCells >= 10) {
        methodsIdx = 4;
        classesIdx = 7;
        hasClasses = true;
      } else {
        return;
      }

      var methodsLevel = methodsTotal > 0 ? colorLevel(methodsPct) : '';

      cells.eq(methodsIdx).attr('class', methodsLevel + ' big').html(methodsTotal > 0 ? coverageBar(methodsPct) : '');
      cells.eq(methodsIdx + 1).attr('class', methodsLevel + ' small').html(
        '<div align="right">' + (methodsTotal > 0 ? methodsPct.toFixed(2) + '%' : 'n/a') + '</div>'
      );
      cells.eq(methodsIdx + 2).attr('class', methodsLevel + ' small').html(
        '<div align="right">' + methodsTested + '&nbsp;/&nbsp;' + methodsTotal + '</div>'
      );

      if (hasClasses && cells.eq(classesIdx).length) {
        var classesLevel = classesTotal > 0 ? colorLevel(classesPct) : '';

        cells.eq(classesIdx).attr('class', classesLevel + ' big').html(classesTotal > 0 ? coverageBar(classesPct) : '');
        cells.eq(classesIdx + 1).attr('class', classesLevel + ' small').html(
          '<div align="right">' + (classesTotal > 0 ? classesPct.toFixed(2) + '%' : 'n/a') + '</div>'
        );
        cells.eq(classesIdx + 2).attr('class', classesLevel + ' small').html(
          '<div align="right">' + classesTested + '&nbsp;/&nbsp;' + classesTotal + '</div>'
        );
      }

      nameCell.attr('class', linesLevel);
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
