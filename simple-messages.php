<?php

/*
	Plugin Name: Simple Moderated Messageboard
	Plugin URI:
	Description: A simple plugin to create a moderated message system
	Version: 0.3
	Author: Ash Whiting
	Author URI:
	License: MIT
*/

ob_start();

/**
* Preliminary stuff - Install some tables, extend the class
*/

/** Do all the scripts
* =============================================================================== */

function sm_scripts_backend() {
	wp_enqueue_script( 'sm_script', plugins_url( 'lib/script.js' , __FILE__ ), array('jquery'), "1.0.0", true );
	wp_register_style( 'sm_styles', plugins_url( 'css/admin-style.css' , __FILE__ ), false, '1.0.0' );
	wp_enqueue_style( 'sm_styles' );
}

function sm_scripts_frontend() {
	wp_enqueue_script( 'sm_validator', plugins_url( 'lib/validator.min.js' , __FILE__ ), array('jquery'), "1.0.0", true );
	wp_enqueue_script( 'bootstrap-js', plugins_url( 'bootstrap/js/bootstrap.min.js' , __FILE__ ), array('jquery'), "1.0.0", true );
	wp_register_style( 'bootstrap', plugins_url( 'bootstrap/css/bootstrap.min.css' , __FILE__ ), false, '1.0.0' );
	wp_enqueue_style( 'bootstrap' );
}

add_action( 'wp_enqueue_scripts', 'sm_scripts_frontend' );
add_action( 'admin_enqueue_scripts', 'sm_scripts_backend');
register_activation_hook( __FILE__, 'create_plugin_database_table' );

/** Create database on plugin install
* =============================================================================== */

function create_plugin_database_table() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'messages';
	$sql = "CREATE TABLE $table_name (id mediumint(9) unsigned NOT NULL AUTO_INCREMENT,
	time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
	sm_email longtext NOT NULL,
	sm_to longtext NOT NULL,
	sm_from longtext NOT NULL,
	sm_message longtext NOT NULL,
	sm_moderated longtext NOT NULL,
	sm_location longtext NOT NULL,
	PRIMARY KEY  (id));";
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
}

if ( ! class_exists( 'WP_List_Table' ) ) :
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
endif;

class Messages_List extends WP_List_Table {

	/** Class constructor
	* =============================================================================== */

	public function __construct() {

		parent::__construct( [
			'singular' => __( 'message', 'sm' ), //singular name of the listed records
			'plural'   => __( 'messages', 'sm' ), //plural name of the listed records
			'ajax'     => false //does this table support ajax?
		] );
	}

	/**
	* Retrieve data
	*
	* @param int $per_page
	* @param int $page_number
	*
	* @return mixed
	* =============================================================================== */

	public static function get_messages( $per_page = 5, $page_number = 1 ) {

		global $wpdb;

		$sql = "SELECT * FROM {$wpdb->prefix}messages";

		if ( ! empty( $_REQUEST['orderby'] ) ) :
			$sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
			$sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
		endif;

		$sql .= " LIMIT $per_page";
		$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;
		$result = $wpdb->get_results( $sql, 'ARRAY_A' );

		return $result;
	}

	/**
	* Returns the count of records in the database.
	*
	* @return null|string
	* =============================================================================== */

	public static function record_count() {
		global $wpdb;
		$sql = "SELECT COUNT(*) FROM {$wpdb->prefix}messages";
		return $wpdb->get_var( $sql );
	}

	/** Text displayed when no customer data is available */
	public function no_items() {
		_e( 'No messages avaliable.', 'sm' );
	}

	/**
	* Render a column when no column specific method exists.
	*
	* @param array $item
	* @param string $column_name
	*
	* @return mixed
	* =============================================================================== */

	 public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'id':
			case 'time':
				return $item[ $column_name ];
			case 'sm_to':
				return $item[ $column_name ];
			case 'sm_from':
				return $item[ $column_name ];
			case 'sm_message':
				return $item[ $column_name ];
			case 'sm_moderated':
				if($item['sm_moderated'] == '1'):
					return "Yes";
				else:
					return "<span style='font-weight:bold;color:red;'>No</span>";
				endif;
			case 'sm_location':
				return $item[ $column_name ];
			default:
				return print_r( $item, true ); //Show the whole array for troubleshooting purposes
		}
	}

	/**
	* Render the bulk edit checkbox - This is dynamically switched using javascript
	*
	* @param array $item
	*
	* @return string
	* =============================================================================== */

	function column_cb( $item ) {
		return sprintf(
			'<input class="sm-check" type="checkbox" name="bulk-delete[]" value="%s" />', $item['id']
		);
	}


	/**
	*  Associative array of columns
	*
	* @return array
	* =============================================================================== */

	function get_columns() {
		$columns = [
			'cb'      			=> '<input type="checkbox" />',
			'id'    			=> __( 'ID', 'sm' ),
			'time' 				=> __( 'Date Added', 'sm' ),
			'sm_to'   			=> __( 'To', 'sm' ),
			'sm_from'    		=> __( 'From', 'sm' ),
			'sm_message'    	=> __( 'Message', 'sm' ),
			'sm_moderated'    	=> __( 'Approved', 'sm' ),
			'sm_location'    	=> __( 'Location', 'sm' ),
		];

		return $columns;
	}


	/**
	* Columns to make sortable.
	*
	* @return array
	* =============================================================================== */

	public function get_sortable_columns() {
		$sortable_columns = array(
			'time'     		=> array('time',false),
            'sm_to'    		=> array('sm_to',false),
			'sm_moderated' 	=> array('sm_moderated', false)
		);

		return $sortable_columns;
	}

	/**
	* Returns an associative array containing the bulk action
	*
	* @return array
	* =============================================================================== */

	public function get_bulk_actions() {
		$actions = [
			'bulk-delete' 		=> 'Delete',
			'bulk-approve' 		=> 'Approve',
			'bulk-unapprove' 	=> 'Unapprove'
		];

		return $actions;
	}


	/**
	* Handles data query and filter, sorting, and pagination.
	* =============================================================================== */

	public function prepare_items() {

		$this->_column_headers 	= $this->get_column_info();
		$this->process_bulk_action();
		$per_page     			= $this->get_items_per_page( 'messages_per_page', 20 );
		$current_page			= $this->get_pagenum();
		$total_items  			= self::record_count();

		$this->set_pagination_args( [
			'total_items' => $total_items,
			'per_page'    => $per_page
		] );

		$this->items = self::get_messages( $per_page, $current_page );
	}

	/**
	* Delete a message record.
	*
	* @param int $id message id
	* =============================================================================== */

	public function delete_message( $id ) {
		global $wpdb;
		$wpdb->delete("{$wpdb->prefix}messages", [ 'id' => $id ], [ '%d' ]);
	}

	/**
	* Approve a message record.
	*
	* @param int $id message id
	* =============================================================================== */

	public function approve_message( $id ) {
		global $wpdb;
		$wpdb->update( "{$wpdb->prefix}messages", ['sm_moderated' => '1'], [ 'id' => $id ]);
	}

	/**
	* Unapprove a message record.
	*
	* @param int $id message id
	* =============================================================================== */

	public function unapprove_message($id ) {
		global $wpdb;
		$wpdb->update( "{$wpdb->prefix}messages", ['sm_moderated' => '0'], [ 'id' => $id ]);
	}

	/**
	* Do bulk actions
	*
	* =============================================================================== */

	public function process_bulk_action() {

		// DELETE
		// -------------------------------------------------

		if (( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-delete' ) || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-delete' )) :
			$d_ids = esc_sql( $_POST['bulk-delete'] );

			foreach ( $d_ids as $id ) :
				self::delete_message($id);
			endforeach;

		    wp_redirect( esc_url_raw(add_query_arg()) );
			exit;
		endif;

		// APPROVE
		// -------------------------------------------------

		if (( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-approve' ) || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-approve' )) :
			$a_ids = esc_sql( $_POST['bulk-approve'] );

			foreach ( $a_ids as $id ) :
				self::approve_message($id);
			endforeach;

		    wp_redirect(esc_url_raw(add_query_arg()));
			exit;
		endif;

		// UNAPPROVE
		// -------------------------------------------------

		if (( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-unapprove' ) || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-unapprove' )) :
			$u_ids = esc_sql( $_POST['bulk-unapprove'] );

			foreach ( $u_ids as $id ) :
				self::unapprove_message($id);
			endforeach;

		    wp_redirect(esc_url_raw(add_query_arg()));
			exit;
		endif;
	}

}


class SM_Plugin {

	// class instance
	static $instance;

	// customer WP_List_Table object
	public $messages_obj;

	// class constructor
	public function __construct() {
		add_filter( 'set-screen-option', [ __CLASS__, 'set_screen' ], 10, 3 );
		add_action( 'admin_menu', [ $this, 'plugin_menu' ] );
	}

	public static function set_screen( $status, $option, $value ) {
		return $value;
	}

	public function plugin_menu() {

		$hook = add_menu_page(
			'Messages',
			'Messages',
			'manage_options',
			'wp_list_messages',
			[ $this, 'plugin_settings_page' ],
			'',
			22
		);

		add_action( "load-$hook", [ $this, 'screen_option' ] );

	}


	/**
	 * Plugin settings page
	 */
	public function plugin_settings_page() {
?>

			<h1>Message Moderation</h1>
			<p>You can delete, approve or unapprove messages here. As soon as a message is approved it will appear on the website.</p>

			<div id="poststuff" class="messages-container">
				<div id="post-body" class="metabox-holder">
					<div id="post-body-content">
						<div class="meta-box-sortables ui-sortable">
							<form method="post">
								<?php
								$this->messages_obj->prepare_items();
								$this->messages_obj->display(); ?>
							</form>
						</div>
					</div>
				</div>
				<br class="clear">
			</div>

<?php
	}

	/**
	 * Screen options
	 */
	public function screen_option() {

		$option = 'per_page';
		$args   = [
			'label'   => 'Messages',
			'default' => 20,
			'option'  => 'messages_per_page'
		];

		add_screen_option( $option, $args );

		$this->messages_obj = new Messages_List();
	}

	/** Singleton instance */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) :
			self::$instance = new self();
		endif;

		return self::$instance;
	}

}

add_action( 'plugins_loaded', function () {
	SM_Plugin::get_instance();
} );

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
