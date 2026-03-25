$(function () {
  var $window = $(window)
    , $top_link = $('#toplink')
    , $body = $('body, html')
    , offset = $('#code').offset().top;

  $top_link.hide().click(function (event) {
    event.preventDefault();
    $body.animate({scrollTop: 0}, 800);
  });

  $window.scroll(function () {
    if ($window.scrollTop() > offset) {
      $top_link.fadeIn();
    } else {
      $top_link.fadeOut();
    }
  });

  var $linePopovers = $('tr.popin > :first-child');
  var $spanPopovers = $('span.popin[data-bs-content]');
  var $allPopovers = $linePopovers.add($spanPopovers);

  function hideAllExcept($except) {
    $allPopovers.each(function () {
      var $current = $(this);
      if (!$except || !$current.is($except)) {
        $current.popover('hide');
      }
    });
  }

  $('tr.popin').on({
    'click.popover': function (event) {
      event.stopPropagation();

      var $container = $(this).children().first();

      hideAllExcept($container);
      $container.popover('toggle');
    },
  });

  $spanPopovers.on({
    'click.popover': function (event) {
      event.stopPropagation();

      var $span = $(this);

      hideAllExcept($span);
      $span.popover('toggle');
    },
  });

  //Hide all popovers on outside click:
  $(document).click(function (event) {
    if ($(event.target).closest($('.popover')).length === 0) {
      hideAllExcept(null);
    }
  });

  //Hide all popovers on escape:
  $(document).keyup(function (event) {
    if (event.key === 'Escape') {
      hideAllExcept(null);
    }
  });

  // Path highlighting in control flow graphs
  $(document).on('click', '.path-row', function () {
    var $row = $(this);
    var pathIndex = $row.data('path-index');
    var $table = $row.closest('table');
    var $graph = $table.nextAll('.cfg-graph').first();

    if (!$graph.length) return;

    var pathsData = $graph.data('paths');

    // Reset all highlights
    $graph.find('.edge, .node').removeClass('highlighted');

    // Toggle: if already selected, deselect
    if ($row.hasClass('path-selected')) {
      $('.path-row').removeClass('path-selected');
      return;
    }

    $('.path-row').removeClass('path-selected');
    $row.addClass('path-selected');

    if (!pathsData || !pathsData[pathIndex]) return;

    // Highlight edges for this path
    var edges = pathsData[pathIndex];
    for (var i = 0; i < edges.length; i++) {
      $graph.find('#edge-' + edges[i]).addClass('highlighted');
    }
  });
});
