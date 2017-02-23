<?php

/*
	Plugin Name: Simple Moderated Messageboard
	Plugin URI:
	Description: A simple plugin to create a moderated message system
	Version: 0.2
	Author: Ash Whiting
	Author URI:
	License: MIT
*/

ob_start();

if(is_admin()):
	require_once(plugin_dir_path( __FILE__ ) . 'admin.php');
else:
	require_once(plugin_dir_path( __FILE__ ) . 'frontend.php');
endif;
