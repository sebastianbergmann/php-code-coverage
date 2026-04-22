$(function () {
  var $btnGroup = $('[data-test-size-filter]').first().parent();
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

  function applyFilter(filter) {
    $('tr[data-coverage]').each(function () {
      var $tr = $(this);
      var raw = $tr.attr('data-coverage');

      if (!raw || raw === '{}') return;

      var d;
      try { d = JSON.parse(raw); } catch (e) { return; }

      var linesTotal = d.linesTotal || 0;
      var methodsTotal = d.methodsTotal || 0;
      var classesTotal = d.classesTotal || 0;

      var linesExec, methodsTested, classesTested;

      if (filter === 'small') {
        linesExec = d.linesSmall || 0;
        methodsTested = d.methodsSmall || 0;
        classesTested = d.classesSmall || 0;
      } else if (filter === 'medium') {
        linesExec = d.linesMedium || 0;
        methodsTested = d.methodsMedium || 0;
        classesTested = d.classesMedium || 0;
      } else if (filter === 'large') {
        linesExec = d.linesLarge || 0;
        methodsTested = d.methodsLarge || 0;
        classesTested = d.classesLarge || 0;
      } else if (filter === 'small+medium') {
        linesExec = d.linesSM || 0;
        methodsTested = d.methodsSM || 0;
        classesTested = d.classesSM || 0;
      } else if (filter === 'small+medium+large') {
        linesExec = d.linesSML || 0;
        methodsTested = d.methodsSML || 0;
        classesTested = d.classesSML || 0;
      } else {
        linesExec = d.linesAll || 0;
        methodsTested = d.methodsAll || 0;
        classesTested = d.classesAll || 0;
      }

      var linesPct = pct(linesExec, linesTotal);
      var methodsPct = pct(methodsTested, methodsTotal);
      var classesPct = pct(classesTested, classesTotal);

      var cells = $tr.children('td');
      var nameCell = cells.eq(0);
      var idx = 1;

      // Lines: bar, percent, number (3 cells)
      var linesLevel = linesTotal > 0 ? colorLevel(linesPct) : '';

      cells.eq(idx).attr('class', linesLevel + ' big').html(linesTotal > 0 ? coverageBar(linesPct) : '');
      cells.eq(idx + 1).attr('class', linesLevel + ' small').html(
        '<div align="right">' + (linesTotal > 0 ? linesPct.toFixed(2) + '%' : 'n/a') + '</div>'
      );
      cells.eq(idx + 2).attr('class', linesLevel + ' small').html(
        '<div align="right">' + linesExec + '&nbsp;/&nbsp;' + linesTotal + '</div>'
      );
      idx += 3;

      // Branches (if present): bar, percent, number (3 cells) - skip, don't modify
      // Paths (if present): bar, percent, number (3 cells) - skip, don't modify
      // We need to find where methods start. Methods are identified by having the methods data.
      // For branch views: lines(3) + branches(3) + paths(3) + methods(3+crap) + classes(3)
      // For non-branch: lines(3) + methods(3+crap?) + classes(3)

      // Detect if this is a branch view by checking total cell count
      var totalCells = cells.length;
      var methodsIdx, classesIdx, hasClasses;

      if (totalCells >= 16) {
        // Branch view: name(1) + lines(3) + branches(3) + paths(3) + methods(3+crap) + classes(3)
        methodsIdx = 10;
        classesIdx = 14;
        hasClasses = totalCells >= 17;
      } else if (totalCells >= 11) {
        // Non-branch file view: name(1) + lines(3) + methods(3+crap) + classes(3)
        methodsIdx = 4;
        classesIdx = 8;
        hasClasses = true;
      } else if (totalCells >= 10) {
        // Directory view: name(1) + lines(3) + methods(3) + classes(3)
        methodsIdx = 4;
        classesIdx = 7;
        hasClasses = true;
      } else {
        return;
      }

      // Methods: bar, percent, number
      var methodsLevel = methodsTotal > 0 ? colorLevel(methodsPct) : '';

      cells.eq(methodsIdx).attr('class', methodsLevel + ' big').html(methodsTotal > 0 ? coverageBar(methodsPct) : '');
      cells.eq(methodsIdx + 1).attr('class', methodsLevel + ' small').html(
        '<div align="right">' + (methodsTotal > 0 ? methodsPct.toFixed(2) + '%' : 'n/a') + '</div>'
      );
      cells.eq(methodsIdx + 2).attr('class', methodsLevel + ' small').html(
        '<div align="right">' + methodsTested + '&nbsp;/&nbsp;' + methodsTotal + '</div>'
      );

      // Classes: bar, percent, number
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

      // Update name cell background
      nameCell.attr('class', linesLevel);
    });
  }

  $('[data-test-size-filter]').on('click', function () {
    var $btn = $(this);

    $btn.siblings().removeClass('active');
    $btn.addClass('active');

    applyFilter($btn.data('test-size-filter'));
  });
});
