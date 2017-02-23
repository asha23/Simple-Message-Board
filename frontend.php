<?php

function sm_scripts_frontend() {
	wp_enqueue_script( 'sm_validator', plugins_url( 'lib/validator.min.js' , __FILE__ ), array('jquery'), "1.0.0", true );
	wp_enqueue_script( 'bootstrap-js', plugins_url( 'bower_components/bootstrap/dist/js/bootstrap.min.js' , __FILE__ ), array('jquery'), "1.0.0", true );
	wp_enqueue_script( 'cycle-2', plugins_url('bower_components/jquery-cycle2/build/jquery.cycle2.min.js', __FILE__), array('jquery'), 2.1.6, true);
	wp_enqueue_script( 'isotope', plugins_url('bower_components/isotope/dist/isotope.pkgd.min.js', __FILE__), array('jquery'), 3.0.2, true);
	wp_register_style( 'bootstrap', plugins_url( 'bower_components/bootstrap/dist/css/bootstrap.min.css' , __FILE__ ), false, '1.0.0' );
	wp_enqueue_style( 'bootstrap' );
}

add_action( 'wp_enqueue_scripts', 'sm_scripts_frontend' );

/** Frontend display shortcode
* This is a self contained item
* the theme scripts file.
* Shortcode [messages]
* =============================================================================== */

function messages_shortcode() {
	global $wpdb;
	$row = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}messages WHERE sm_moderated='1'");

		foreach($row as $rows):
?>

			<div class="message-block col-md-4 col-sm-6 col-xs-12">
				<div class="panel panel-default">

					<div class="panel-heading">
						<div class="message-to"><strong>To:</strong> <?php echo $rows->sm_to; ?></div>
						<div class="message-date"><i class="fa fa-clock-o"></i>
<?php
							echo date("H:i | l dS M Y", strtotime($rows->time));
?>
						</div>
					</div>

					<div class="panel-body">

						<div class="message-text">

							<div class="original-lang">
								<div class="inner">
									<?php echo stripslashes($rows->sm_message); ?>
								</div>
							</div>

						</div>
					</div>
					<div class="panel-footer">
						<div class="message-from"><strong>From</strong>, <?php echo $rows->sm_from; ?></div>
						<div class="message-location"> <?php echo $rows->sm_location; ?></div>
					</div>
				</div>

		<?php
		endforeach;
}

add_shortcode('messages', 'messages_shortcode');

function messages_form_shortcode() {
	$html = '

		<form class="form-horizontal" id="sm_form" action="" method="post" onsubmit="sm_process(this);return false;" data-path="' . admin_url("admin-ajax.php") . '">

			<div class="row">

				<div class="form-half">
					<div class="">
						<input type="text" class="form-control" name="from" required placeholder="Your name*"/>
					</div>
				</div>

				<div class="form-half">
					<div class="">
						<input type="email" class="form-control" name="email" required placeholder="Your email address*"/>
					</div>
				</div>

				<div class="form-half">
					<div class="">
						<input type="text" class="form-control" name="to" placeholder="To"/>
					</div>
				</div>

				<div class="form-half">
					<div class="">
						<input type="text" class="form-control" name="location" required placeholder="Your location*"/>
					</div>
				</div>

			</div>

			<div class="row">

				<div class="form-full">
					<div class="message-field">
						<textarea name="message" class="message-control" required placeholder="Your message*"></textarea>
					</div>
				</div>

				<div class="form-half">
					<div class="captcha-form">
						<div class="g-recaptcha" data-sitekey="6LePJRIUAAAAABtB2o0gZOjdINiNv8qyDw79cwbg"></div>
					</div>
					<div class="captcha-message">
						<span class="small">Please check the box above to prove you are human.</span>
					</div>
				</div>

				<div class="form-half">
					<div class="align-right">
						<p><span class="small">*Required</span></p>
						<button type="submit" class="btn btn-default">Send message</button>
					</div>
				</div>

			</div>

		</form>
		<script>
		function sm_process(e){
			var data = {
				action: "sm_add_record",
				to:e["to"].value,
				from:e["from"].value,
				email:e["email"].value,
				location:e["location"].value,
				message:e["message"].value
			};

			response = grecaptcha.getResponse();

			if(response.length == 0) {
				$(".captcha-message").show();
			} else {
				$(".captcha-message").hide();
				jQuery.post("' . admin_url("admin-ajax.php") . '", data, function(response) {
					jQuery("#sm_form").html(response);
				});
			}
		}
		</script>
	';
	return $html;
}

add_shortcode('messages_form', 'messages_form_shortcode');

add_action( 'wp_ajax_sm_add_record', 'sm_add_record_callback' );
add_action( 'wp_ajax_nopriv_sm_add_record', 'sm_add_record_callback' );

function sm_add_record_callback() {
	global $wpdb;
	$table_name = $wpdb->prefix . "messages";
	$time_now = date("Y-m-d H:i:s");

	$email 		= $_POST["email"];
	$from 		= $_POST["from"];
	$to 		= $_POST["to"];
	$message 	= stripslashes($_POST["message"]);
	$location 	= $_POST["location"];

	$rows_affected = $wpdb->insert( $table_name, array(
		'id' 			=> null,
		'time'			=> current_time('mysql'),
		'sm_email' 		=> $email,
		'sm_to' 		=> $to,
		'sm_from' 		=> $from,
		'sm_message' 	=> $message,
		'sm_moderated' 	=> "0",
		'sm_location'	=> $location
  	));

	if ($rows_affected == 1) :
		echo "<div class='success-message'>Thanks, we have received your message. It has been added to our submission queue and we will approve it shortly.</div>";

		// Send email to us

		$to      = "'" . get_option("admin_email") . "'";
		$subject = $from .' created a new message.';
		$the_message = 'A new message has been created and is in the queue for moderation.';
		$headers = 'From: donotreply@nowhere.com' . "\r\n" .
		    'Reply-To: donotreply@nowhere.com' . "\r\n" .
		    'X-Mailer: PHP/' . phpversion();

		mail($to, $subject, $the_message, $headers);

	else:
		echo "<div class='success-message'>Error, something has gone wrong. Please try again later.</div>";
	endif;

	die();
}

function limit_text($text, $limit) {
      if (str_word_count($text, 0) > $limit):
          $words = str_word_count($text, 2);
          $pos = array_keys($words);
          $text = substr($text, 0, $pos[$limit]) . ' ...';
      endif;
      return $text;
}

function message_teaser_shortcode() {
	global $wpdb;
	$table_name = $wpdb->prefix . "messages";

	$results = $wpdb->get_results('SELECT * FROM mk_messages WHERE sm_moderated = "1" LIMIT 5', OBJECT);

	// Construct the output
	$the_message = "";

	foreach ($results as $result) :
		$the_message .= '<div class="message-slide matcher">';
		$the_message .= '<p class="tiny">' . $result->sm_from . ' said...</p><hr>“';
		$the_message .= $result->sm_message;
		$the_message .= '”';
		$the_message .= '</div>';
	endforeach;

	$img_path = get_template_directory_uri() . "/build/images/globals/mail.png";
	$html = '
		<article class="message-teaser">
			<div class="inner clearfix">
				<div class="icon">
					<img src="'. $img_path .'" class="img-respond">
				</div>

				<div class="message cycle-slideshow"
					data-cycle-fx="scrollVert"
					data-cycle-timeout="4000"
					data-cycle-slides="> .message-slide"
					data-cycle-pause-on-hover="true"
				>
					' . stripslashes($the_message) . '
				</div>

				<div class="message-links">
					<a href="" class="btn btn-circular home-form-trigger">Leave a message</a>
					<a href="" class="btn btn-circular home-form-close" style="display:none;"><i class="fa fa-times"></i> Close</a>
					<a href="/messages" class="btn btn-circular-opaque">View all</a>
				</div>
			</div>
		</article>
	';
	return $html;
}

add_shortcode('messages_teaser', 'message_teaser_shortcode');
