<?php

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
		$this->process_bulk_action(); // This is causing a problem on form submit
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
