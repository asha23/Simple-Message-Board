

(function( $ ) {

    "use strict";
	$( document ).ready(function() {

		function sm_process(e){

			var data = {
				action: "sm_add_record",
				to:e["to"].value,
				from:e["from"].value,
				email:e["email"].value,
				location:e["location"].value,
				message:e["message"].value
			};

			path = $('#sm_form').data('path');
			$.post(path, data, function(response) {
				jQuery("#sm_form").html(response);
			});
		}
	});

})(jQuery);
