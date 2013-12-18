<?php
/*
Plugin Name: Connections ExpSearch
Plugin URI: 
Description: 
Version: 0.1
Author: 
Author URI: 
*/

if (!class_exists('connectionsExpSearchLoad')) {
	class connectionsExpSearchLoad {
		
		public function __construct() {
			if ( !is_admin() ) add_action( 'plugins_loaded', array(&$this, 'start') );
			//if ( !is_admin() ) add_action( 'wp_print_scripts', array(&$this, 'loadScripts') );
		}
		
		public function start() {
			add_filter('cn_list_atts_permitted', array(__CLASS__, 'expand_atts_permitted'));
			
			
			add_action('cn_action_list_actions', array(__CLASS__, 'showList') );
			add_action('cn_action_list_before', array(__CLASS__, 'beforeList') );
		}
		
		public static function expand_atts_permitted($permittedAtts){
			$permittedAtts['mode'] = NULL;
			$permittedAtts['fields'] = NULL;
			$permittedAtts['hide_empty'] = True;
			$permittedAtts['theme_file'] = NULL;
			
			return $permittedAtts;
		}
		
		
		public static function showList($atts){
			$html = "just your friendly serach form";
			return $html;
		}
		public static function beforeList($atts){
			$html = "here at beforeList";//"just your friendly serach form";
			return $html;
		}				
		
		
		
		


	}
	
	/*
	 * Checks for PHP 5 or greater as required by Connections Pro and display an error message
	 * rather that havinh PHP thru an error.
	 */
	if (version_compare(PHP_VERSION, '5.0.0', '>')) {
		/*
		 * Initiate the plug-in.
		 */
		global $connectionsExpSearch;
		$connectionsExpSearch = new connectionsExpSearchLoad();
	} else {
		add_action( 'admin_notices', create_function('', 'echo \'<div id="message" class="error"><p><strong>Connections ROT13 requires at least PHP5. You are using version: ' . PHP_VERSION . '</strong></p></div>\';') );
	}
	
}