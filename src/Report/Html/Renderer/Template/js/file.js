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
});
