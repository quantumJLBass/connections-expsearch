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
			$this->loadConstants();
			if ( !is_admin() ) add_action( 'plugins_loaded', array(&$this, 'start') );
			//if ( !is_admin() ) add_action( 'wp_print_scripts', array(&$this, 'loadScripts') );
		}
		
		public function start() {
			add_filter('cn_list_atts_permitted', array(__CLASS__, 'expand_atts_permitted'));
			
			
			add_action('cn_action_list_actions', array(__CLASS__, 'showList') );
			
			add_action('cn_action_list_before', array(__CLASS__, 'beforeList') );
			
			add_shortcode( 'connections_search', array( $this, 'shortcode') );
			require_once(dirname( __FILE__ ) . '/includes/class.template-parts-extended.php');//temp correct later
			
			add_action( 'wp_print_styles', array( $this, 'loadStyles' ) );
			
			if (isset($_POST)) {
				if (isset($_POST['start_search'])) {// Check if option save is performed
					add_filter('the_content', array( $this, 'doSearch' ));
				}
			}	
		
			
		}
		private function loadConstants() {

			define( 'CNEXSCH_CURRENT_VERSION', '1.0.2' );

			define( 'CNEXSCH_DIR_NAME', plugin_basename( dirname( __FILE__ ) ) );
			define( 'CNEXSCH_BASE_NAME', plugin_basename( __FILE__ ) );
			define( 'CNEXSCH_BASE_PATH', plugin_dir_path( __FILE__ ) );
			define( 'CNEXSCH_BASE_URL', plugin_dir_url( __FILE__ ) );
		}

		/**
		 * Called when running the wp_print_styles action.
		 *
		 * @return null
		 */
		public function loadStyles() {
			if ( ! is_admin() ) wp_enqueue_style('cn-expsearch', CNEXSCH_BASE_URL . '/css/cn-expsearch.css', array(), CNEXSCH_CURRENT_VERSION);
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
		public function doSearch() {
			global $post,$connections;
			var_dump($_POST);
			$out='<p>the results</p>';
			return $out;
		}
		
		
		
				
		/**
		 * @todo: Add honeypot fields for bots.
		 */
		public function shortcode( $atts , $content = NULL ) {
			global $connections;

			$date = new cnDate();
			$form = new cnFormObjects();
			$convert = new cnFormatting();
			$format =& $convert;
			$entry = new cnEntry();
			$out = '';

			$atts = shortcode_atts(
				array(
					'default_type'     => 'individual',
					'show_label'		=> TRUE,
					'select_type'      => TRUE,
					'photo'            => FALSE,
					'logo'             => FALSE,
					'address'          => TRUE,
					'phone'            => TRUE,
					'email'            => TRUE,
					'messenger'        => TRUE,
					'social'           => TRUE,
					'link'             => TRUE,
					'anniversary'      => FALSE,
					'birthday'         => FALSE,
					'category'         => TRUE,
					'rte'              => TRUE,
					'bio'              => TRUE,
					'notes'            => FALSE,
					'str_contact_name' => __( 'Entry Name' , 'connections_form' ),
					'str_bio'          => __( 'Biography' , 'connections_form' ),
					'str_notes'        => __( 'Notes' , 'connections_form' )
				), $atts );


			/*
			 * Convert some of the $atts values in the array to boolean.
			 */
			$convert->toBoolean($atts['select_type']);
			$convert->toBoolean($atts['photo']);
			$convert->toBoolean($atts['logo']);
			$convert->toBoolean($atts['address']);
			$convert->toBoolean($atts['phone']);
			$convert->toBoolean($atts['email']);
			$convert->toBoolean($atts['messenger']);
			$convert->toBoolean($atts['social']);
			$convert->toBoolean($atts['link']);
			$convert->toBoolean($atts['anniversary']);
			$convert->toBoolean($atts['birthday']);
			$convert->toBoolean($atts['category']);
			$convert->toBoolean($atts['rte']);
			$convert->toBoolean($atts['bio']);
			$convert->toBoolean($atts['notes']);
			//$out .= var_dump($atts);

			// switch out for a template that can be changed. ie: {$category_select}, {$state_dropdown} etc.
			$out .= '<div id="cn-form-container">' . "\n";
				$out .= '<div id="cn-form-ajax-response"><ul></ul></div>' . "\n";
				$out .= '<form id="cn-search-form" method="POST" enctype="multipart/form-data">' . "\n";
	
					$defaults = array(
						'show_label' => TRUE
					);
			
					$atts = wp_parse_args( $atts, $defaults );	
					$searchValue = ( get_query_var('cn-s') ) ? get_query_var('cn-s') : '';

					$out .= '<div>';
					$out .= cnTemplatePartExended::flexSelect($connections->retrieve->categories(),array(
						'type'            => 'select',
						'group'           => FALSE,
						'default'         => __('Select state', 'connections'),
						'label'           => __('Search by category', 'connections'),
						'show_select_all' => TRUE,
						'select_all'      => __('Any', 'connections'),
						'show_empty'      => TRUE,
						'show_count'      => FALSE,
						'depth'           => 0,
						'parent_id'       => array(),
						'exclude'         => array(),
						'return'          => TRUE,
						'class'				=>'search-select'
					));
					$out .= '<hr/></div>';

					$out .= '<div>';

					$out 			.= '<label class="search-select"><strong>Search by state:</strong></label><br/>';
					$display_code 	= $connections->settings->get('connections_form', 'connections_form_preferences', 'form_preference_regions_display_code');
					$out          	.= '<select name="cn-state">';
					$out 			.= '<option value="" selected >Any</option>';
					foreach (cnDefaultValues::getRegions() as $code => $regions) {
						$lable = $display_code ? $code : $regions;
						$out .= '<option value="' . $code . '" >' . $lable . '</option>';
					}
					$out .= '</select>';
					$out .= '<hr/></div>';

					
					$out .= '<label for="cn-s"><strong>Keywords:</strong></label><br/>';
					$out .= '<span class="cn-search" style="width:50%; display:inline-block">';
						$out .= '<input type="text" id="cn-search-input" name="cn-keyword" value="' . esc_attr( $searchValue ) . '" placeholder="' . __('Search', 'connections') . '"/>';
					$out .= '</span>';

					$out .=  '<hr/><br/><p class="cn-add"><input class="cn-button-shell cn-button red" id="cn-form-search" type="submit" name="start_search" value="' . __('Submit' , 'connections_form' ) . '" /></p><br/>' . "\n";
	
				$out .= '</form>';
			$out .= '</div>';

			// Output the the search input.
			return $out;
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