

(function( $ ) {

    "use strict";
	$( document ).ready(function() {
		$('#bulk-action-selector-top').change(function(){
			var type_val = $(this).val() + "[]";
			$('.sm-check').attr('name', type_val);
		});



	});

})(jQuery);
