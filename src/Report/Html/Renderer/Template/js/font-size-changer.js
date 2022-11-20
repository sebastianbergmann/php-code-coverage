  $(function() {
   var $body = $('body');

   $(document).on('click', '.font-size-changer > button', function(){
     let $this = $(this);
     let size = $this.attr('data-size');
     $body.attr('data-size', size);
     localStorage.setItem('phpunit-coverage-fontsize', size);
   });

   let currentSize = localStorage.getItem('phpunit-coverage-fontsize');
   if (currentSize) {
     $body.attr('data-size', currentSize);
   }
});
