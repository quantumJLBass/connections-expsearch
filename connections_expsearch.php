<?php
/*
Plugin Name: Connections ExpSearch
Plugin URI: 
Description: 
Version: 0.1
Author: 
Author URI: 
*/
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
if (!class_exists('connectionsExpSearchLoad')) {
	class connectionsExpSearchLoad {
		public $options;
		public $settings;
		public function __construct() {
			
			self::defineConstants();
			
			add_filter('cn_list_atts_permitted', array(__CLASS__, 'expand_atts_permitted'));
			/*
			 * Register the settings tabs shown on the Settings admin page tabs, sections and fields.
			 * Init the registered settings.
			 * NOTE: The init method must be run after registering the tabs, sections and fields.
			 */
			$this->settings = cnSettingsAPI::getInstance();
			add_filter( 'cn_register_settings_sections' , array( $this, 'registerSettingsSections' ) );
			add_filter( 'cn_register_settings_fields' , array( $this, 'registerSettingsFields' ) );
			
			$this->settings->init();
			
			
			add_shortcode( 'connections_search', array( $this, 'shortcode') );
			require_once(dirname( __FILE__ ) . '/includes/class.template-parts-extended.php');//temp correct later

			add_action( 'wp_print_styles', array( $this, 'loadStyles' ) );
			add_action( 'init', array($this, 'loadJs') );
			add_filter('wp_head', array($this, 'add_cnexpsh_data'));
			if (isset($_POST['start_search'])) {// Check if option save is performed
				add_filter('the_content', array( $this, 'doSearch' ));
			}
		}
		private function defineConstants() {
			define( 'CNEXSCH_CURRENT_VERSION', '1.0.2' );
			define( 'CNEXSCH_DIR_NAME', plugin_basename( dirname( __FILE__ ) ) );
			define( 'CNEXSCH_BASE_NAME', plugin_basename( __FILE__ ) );
			define( 'CNEXSCH_BASE_PATH', plugin_dir_path( __FILE__ ) );
			define( 'CNEXSCH_BASE_URL', plugin_dir_url( __FILE__ ) );
		}
		
		public function init() { }
		/**
		 * Called when running the wp_print_styles action.
		 *
		 * @return null
		 */
		public function loadStyles() {
			if ( ! is_admin() ) wp_enqueue_style('cn-expsearch', CNEXSCH_BASE_URL . 'css/cn-expsearch.css', array(), CNEXSCH_CURRENT_VERSION);
			
		}		
		public static function expand_atts_permitted($permittedAtts){
			$permittedAtts['mode'] = NULL;
			$permittedAtts['fields'] = NULL;
			$permittedAtts['hide_empty'] = True;
			$permittedAtts['theme_file'] = NULL;
			
			return $permittedAtts;
		}

		public function loadJs(){
			if ( ! is_admin() ){ 
				wp_enqueue_script( 'jquery-chosen-min' );
				wp_enqueue_script( 'cn-expsearch' , CNEXSCH_BASE_URL . 'js/cn-expsearch.js', array('jquery') , CNEXSCH_CURRENT_VERSION , TRUE );
			}
			
		}
		// Add items to the footer
		function add_cnexpsh_data() {
			global $connections;
			$homeID = $connections->settings->get( 'connections', 'connections_home_page', 'page_id' );
			if ( in_the_loop() && is_page() ) {
				$permalink = trailingslashit ( get_permalink() );
			} else {
				$permalink = trailingslashit ( get_permalink( $homeID ) );
			}
			echo '<script type="text/javascript">var cn_search_form_url = "'.$permalink.'";</script>';
		}

		/**
		 * Register the settings sections.
		 *
		 * @author Steven A. Zahm
		 * @since 0.4
		 * @param array $sections
		 * @return array
		 */
		public function registerSettingsSections( $sections ) {
			global $connections;

			$settings = 'connections_page_connections_settings';

			// Register the core setting sections.
			$sections[] = array(
				'tab'       => 'search' ,
				'id'        => 'connections_expsearch_defaults' ,
				'position'  => 20 ,
				'title'     => __( 'Search defaults' , 'connections_expsearch' ) ,
				'callback'  => '' ,
				'page_hook' => $settings );
			return $sections;
		}

		public function registerSettingsFields( $fields ) {
			$current_user = wp_get_current_user();

			$settings = 'connections_page_connections_settings';

			$fields[] = array(
				'plugin_id' => 'connections_expsearch',
				'id'        => 'use_geolocation',
				'position'  => 10,
				'page_hook' => $settings,
				'tab'       => 'search',
				'section'   => 'connections_expsearch_defaults',
				'title'     => __('Add geo location to the search', 'connections_expsearch'),
				'desc'      => __('', 'connections_expsearch'),
				'help'      => __('', 'connections_expsearch'),
				'type'      => 'checkbox',
				'default'   => 1
			);
			$fields[] = array(
				'plugin_id' => 'connections_expsearch',
				'id'        => 'visiable_search_fields',
				'position'  => 50,
				'page_hook' => $settings,
				'tab'       => 'search',
				'section'   => 'connections_expsearch_defaults',
				'title'     => __('Choose the visible on search form fields', 'connections_form'),
				'desc'      => '',
				'help'      => '',
				'type'      => 'multiselect',
				'options'   => $this->getSearchFields(),
				'default'   => array('region','category','keyword')
			);
			return $fields;
		}


		//Note this is hard coded for the tmp need to finish a site
		public function getSearchFields(){
			
			$fields = array(
				'region'=>'Region',
				'country'=>'Country',
				'category'=>'Category',
				'keywords'=>'Keywords'
			);
			
			return $fields;
		}











		public function doSearch() {
			global $post,$connections;
			$permittedAtts = array(
				'id'                    => NULL,
				'slug'                  => NULL,
				'category'              => isset($_POST['cn-cat'])&& !empty($_POST['cn-cat']) ?$_POST['cn-cat']:NULL,
				'enable_category_select'	=>false,
				'enable_search'			=> false,
				'cards_only'			=> true,
				/*'category_in'           => NULL,
				'exclude_category'      => NULL,
				'category_name'         => NULL,
				'category_slug'         => NULL,
				'wp_current_category'   => 'false',
				'allow_public_override' => 'false',
				'private_override'      => 'false',
				'show_alphaindex'       => cnSettingsAPI::get( 'connections', 'connections_display_results', 'index' ),
				'repeat_alphaindex'     => cnSettingsAPI::get( 'connections', 'connections_display_results', 'index_repeat' ),
				'show_alphahead'        => cnSettingsAPI::get( 'connections', 'connections_display_results', 'show_current_character' ),
				'list_type'             => NULL,
				'order_by'              => NULL,
				'limit'                 => NULL,
				'offset'                => NULL,
				'family_name'           => NULL,
				'last_name'             => NULL,
				'title'                 => NULL,*/
				'show_alphaindex'       => false,
				'repeat_alphaindex'     => false,
				'show_alphahead'       	=> false,
				'organization'          => isset($_POST['cn-keyword']) && !empty($_POST['cn-keyword'])?$_POST['cn-keyword']:NULL,
				'department'            => NULL,
				'city'                  => NULL,
				'state'                 => isset($_POST['cn-state']) && !empty($_POST['cn-state'])?$_POST['cn-state']:NULL,
				/*'zip_code'              => NULL,*/
				'country'               => isset($_POST['cn-country']) && !empty($_POST['cn-country'])?$_POST['cn-country']:NULL,
				'template'              => NULL, /* @since version 0.7.1.0 */
				'template_name'         => NULL, /* @deprecated since version 0.7.0.4 */
				'width'                 => NULL,
				'lock'                  => FALSE,
				'force_home'            => FALSE,
				'search_terms'  		=> isset($_POST['cn-keyword']) && !empty($_POST['cn-keyword'])?explode(' ',$_POST['cn-keyword']):array(),
				'home_id'               => in_the_loop() && is_page() ? get_the_id() : cnSettingsAPI::get( 'connections', 'connections_home_page', 'page_id' ),
			);
			
			if( (isset($_POST['cn-latitude']) && isset($_POST['cn-longitude'])) || (isset($_POST['cn-near_addr'])) ){
				$locationalPermittedAtts = array(
					'near_addr'		=> isset($_POST['cn-near_addr']) && !empty($_POST['cn-near_addr'])?"":NULL,
					'latitude'		=> isset($_POST['cn-latitude']) && !empty($_POST['cn-latitude'])?"":NULL,
					'longitude'		=> isset($_POST['cn-longitude']) && !empty($_POST['cn-longitude'])?"":NULL,
					'radius'		=> isset($_POST['cn-near_addr']) && !empty($_POST['cn-near_addr'])?"":10,
					'unit'			=> isset($_POST['cn-near_addr']) && !empty($_POST['cn-near_addr'])?"":'mi',
				);
				$permittedAtts = array_merge($permittedAtts,$locationalPermittedAtts);
			}
			
			$out = '';
			$categories = $connections->retrieve->categories();
			$opSortbyCat=true;//would be an option
			
			//var_dump($categories);
			//die();

			$results = $connections->retrieve->entries( $permittedAtts );
			
			if(!empty($results)){
			
			
				$markers = new stdClass();
				$markers->markers=array();
				foreach($results as $entry){
					$entryObj=new stdClass();
					$entryObj->id=$entry->id;
					$entryObj->title= $entry->organization;
					$entryObj->position=new stdClass();
					$addy = unserialize ($entry->addresses);
					$array = (array) $addy;
					$addy = array_pop($addy);
					if(!empty($addy['latitude']) && !empty($addy['longitude'])){
						$entryObj->position->latitude=$addy['latitude'];
						$entryObj->position->longitude=$addy['longitude'];
						$markers->markers[]= $entryObj;
					}
				}
				$markerJson=json_encode($markers);
				$location_posted=isset($_POST['location_alert']) ? $_POST['location_alert'] : false;
	
				
				$out .= '
				<div id="tabs" class="ui-tabs ui-widget ui-widget-content ui-corner-all" rel="'.($location_posted?"location_posted":"").'">
					<ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all">
						
						<li class="ui-state-default ui-corner-top ui-tabs-selected ui-state-active"><a href="#tabs-2">Listings</a></li>
						<li class="ui-state-default ui-corner-top"><a href="#tabs-1">Map</a></li>
					</ul>
				
					<div id="tabs-2" class="ui-tabs-panel ui-widget-content ui-corner-bottom">
					';
					if($permittedAtts['category']==NULL){
						$state = isset($_POST['cn-state']) && !empty($_POST['cn-state'])?$_POST['cn-state'].' and ':'';
						foreach($categories as $cat){
							$permittedAtts['category']=$cat->term_id;
							$catblock = connectionsList( $permittedAtts,NULL,'connections' );
							//var_dump($catblock);
							if(!empty($catblock) && strpos($catblock,'No results')===false){
								$out .= '<h2>'.$state.$cat->name.'</h2>';
								$out .= '<div class="accordion">';
								$out .= $catblock;
								$out .= '</div>';
							}
						}
					}else{
						$state = isset($_POST['cn-state']) && !empty($_POST['cn-state'])?$_POST['cn-state'].' and ':'';
						$category = $connections->retrieve->category($permittedAtts['category']);
						$out .= '<h2>'.$state.$category->name.'</h2>';
						$out .= '<div class="accordion">';
						$out .= connectionsList( $permittedAtts,NULL,'connections' );
						$out .= '</div>';
					}
		
					$out .='
					</div>
					<div id="tabs-1" class="ui-tabs-panel ui-widget-content ui-corner-bottom ">
						<h2>Hover on a point to find a business and click for more information</h2>
						<div id="mapJson">'.$markerJson.'</div>
						<div id="front_cbn_map" class="byState " rel="'.$_POST['cn-state'].'" style="width:100%;height:450px;"></div>
						<div class="ui-widget-content ui-corner-bottom" style="padding:5px 15px;">
							<div id="data_display"></div>
							<div style="clear:both;"></div>
						</div>
					</div>
				</div>';
			}else{
				$out = "No results";	
			}
			
			
			return $out;
		}
		
		
		
				
		/**
		 * @todo: Add honeypot fields for bots.
		 */
		public function shortcode( $atts , $content = NULL ) {
			global $connections;
			
			//$date = new cnDate();
			//$form = new cnFormObjects();
			$convert = new cnFormatting();
			$format =& $convert;
			//$entry = new cnEntry();


			$formObject = array();
			
			
			$atts = shortcode_atts(
				array(
					'default_type'		=> 'individual',
					'show_label'		=> TRUE,
					'select_type'		=> TRUE,
					'photo'				=> FALSE,
					'logo'				=> FALSE,
					'address'			=> TRUE,
					'phone'				=> TRUE,
					'email'				=> TRUE,
					'messenger'			=> TRUE,
					'social'			=> TRUE,
					'link'				=> TRUE,
					'anniversary'		=> FALSE,
					'birthday'			=> FALSE,
					'category'			=> TRUE,
					'rte'				=> TRUE,
					'bio'				=> TRUE,
					'notes'				=> FALSE,
					'str_contact_name'	=> __( 'Entry Name' , 'connections_form' ),
					'str_bio'			=> __( 'Biography' , 'connections_form' ),
					'str_notes'			=> __( 'Notes' , 'connections_form' )
				), $atts );

			$defaults = array(
				'show_label' => TRUE
			);
		
			$atts = wp_parse_args( $atts, $defaults );

			$formObject = $atts;
			set_transient( "formObject", $formObject, 0 );	
			ob_start();
				if ( $overridden_template = locate_template( 'searchForm.php' ) ) {
					// locate_template() returns path to file
					// if either the child theme or the parent theme have overridden the template
					load_template( $overridden_template );
				} else {
					// If neither the child nor parent theme have overridden the template,
					// we load the template from the 'templates' sub-directory of the directory this file is in
					load_template( dirname( __FILE__ ) . '/templates/searchForm.php' );
				}
				$out .= ob_get_contents();
			ob_end_clean();				

			// Output the the search input.
			return $out;
		}
	}



	/**
	 * Start up the extension.
	 *
	 * @access public
	 * @since 1.0
	 *
	 * @return mixed (object)|(bool)
	 */
	function connectionsExpSearchLoad() {
			if ( class_exists('connectionsLoad') ) {
					return new connectionsExpSearchLoad();
			} else {
					add_action(
							'admin_notices',
							 create_function(
									 '',
									'echo \'<div id="message" class="error"><p><strong>ERROR:</strong> Connections must be installed and active in order use Connections Extended Search.</p></div>\';'
									)
					);
					return FALSE;
			}
	}


	/**
	 * Since Connections loads at default priority 10, and this extension is dependent on Connections,
	 * we'll load with priority 11 so we know Connections will be loaded and ready first.
	 */
	add_action( 'plugins_loaded', 'connectionsExpSearchLoad', 11 );
}