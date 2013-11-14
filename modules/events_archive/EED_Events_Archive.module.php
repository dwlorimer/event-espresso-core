<?php if ( ! defined('EVENT_ESPRESSO_VERSION')) exit('No direct script access allowed');
/**
 * Event Espresso
 *
 * Event Registration and Management Plugin for WordPress
 *
 * @ package			Event Espresso
 * @ author			Seth Shoultes
 * @ copyright		(c) 2008-2011 Event Espresso  All Rights Reserved.
 * @ license			http://eventespresso.com/support/terms-conditions/   * see Plugin Licensing *
 * @ link					http://www.eventespresso.com
 * @ version		 	4.0
 *
 * ------------------------------------------------------------------------
 *
 * Event List
 *
 * @package		Event Espresso
 * @subpackage	/modules/events_archive/
 * @author		Brent Christensen 
 *
 * ------------------------------------------------------------------------
 */
class EED_Events_Archive  extends EED_Module {


	/**
	 * 	Start Date
	 *	@var 	$_elf_month
	 * 	@access 	protected
	 */
	protected $_elf_month = NULL;


	/**
	 * 	Category
	 *	@var 	$_elf_category
	 * 	@access 	protected
	 */
	protected $_elf_category = NULL;


	/**
	 * 	whether to display expired events in the event list
	 *	@var 	$_show_expired
	 * 	@access 	protected
	 */
	protected $_show_expired = NULL;


	/**
	 * 	whether to display the event list as a grid or list
	 *	@var 	$_type
	 * 	@access 	protected
	 */
	protected static $_type = NULL;


	/**
	 * 	array of existing event list views
	 *	@var 	$_types
	 * 	@access 	protected
	 */
	protected static $_types = array( 'grid', 'text', 'dates' );


	public static $espresso_event_list_ID = 0;
	public static $espresso_grid_event_lists = array();

	

	/**
	 * 	set_hooks - for hooking into EE Core, other modules, etc
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public static function set_hooks() {
		EE_Config::register_route( __( 'events', 'event_espresso' ), 'Events_Archive', 'run' );
		EE_Config::register_route( 'event_list', 'Events_Archive', 'event_list' );		
		add_action( 'wp_loaded', array( 'EED_Events_Archive', 'set_definitions' ), 2 );
	}

	/**
	 * 	set_hooks_admin - for hooking into EE Admin Core, other modules, etc
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public static function set_hooks_admin() {
		add_filter('FHEE__Config__update_config__CFG', array( 'EED_Events_Archive', 'filter_config' ), 10 );
		add_filter( 'FHEE__EED_Events_Archive__template_settings_form__event_list_config', array( 'EED_Events_Archive', 'set_default_settings' ));
		add_action( 'AHEE__general_settings_admin__template_settings__before_settings_form', array( 'EED_Events_Archive', 'template_settings_form' ), 10 );
		add_action( 'wp_loaded', array( 'EED_Events_Archive', 'set_definitions' ), 2 );
		add_filter( 'FHEE__General_Settings_Admin_Page__update_template_settings__data', array( 'EED_Events_Archive', 'update_template_settings' ), 10, 2 );
	}




	/**
	 * 	set_definitions
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public static function set_definitions() {
		define( 'EVENTS_ARCHIVE_ASSETS_URL', plugin_dir_url( __FILE__ ) . 'assets' . DS );
		define( 'EVENTS_ARCHIVE_TEMPLATES_PATH', str_replace( '\\', DS, plugin_dir_path( __FILE__ )) . 'templates' . DS );
	}



	/**
	 * 	run - initial module setup
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public function run( $WP ) {
		do_action( 'AHEE__EED_Events_Archive__before_run' );
		// grid, text or dates ?
		EED_Events_Archive::set_type();
		// grab POST data
		$this->get_post_data();		
		// filter the WP posts_join, posts_where, and posts_orderby SQL clauses
		$this->_filter_query_parts();		
		// load other required components
		$this->_load_assests();
		// load template
		EE_Config::register_view( 'events', 0, $this->_get_template('full') );
		// ad event list filters
		add_action( 'AHEE__archive_event_list_template__after_header', array( $this, 'event_list_template_filters' ));
		remove_all_filters( 'excerpt_length' );
		add_filter( 'excerpt_length', array( $this, 'excerpt_length' ), 10 );
		add_filter( 'excerpt_more', array( $this, 'excerpt_more' ), 10 );
	}



	/**
	 * 	event_list
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public function event_list() {	
		// load other required components
		$this->_load_assests();
	}







	/**
	 * 	_filter_query_parts
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	private function _filter_query_parts() {
		// make sure CPT is set correctly
		//add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ), 1 );
		// build event list query
		add_filter( 'posts_join', array( $this, 'posts_join' ), 1, 1 );
		add_filter( 'posts_where', array( $this, 'posts_where' ), 1, 1 );
		add_filter( 'posts_orderby', array( $this, 'posts_orderby' ), 1, 1 );
	}

	/**
	 * 	_type - the type of event list : grid, text, dates
	 *
	 *  @access 	public
	 *  @return 	string
	 */
	public static function set_type() {
		do_action( 'AHEE__EED_Events_Archive__before_set_type' );
		EED_Events_Archive::$_types = apply_filters( 'EED_Events_Archive__set_type__types', EED_Events_Archive::$_types );
		$view = isset( EE_Registry::instance()->CFG->EED_Events_Archive['default_type'] ) ? EE_Registry::instance()->CFG->EED_Events_Archive['default_type'] : 'grid';
		$view = EE_Registry::instance()->REQ->is_set( 'elf_type' ) ? sanitize_text_field( EE_Registry::instance()->REQ->get( 'elf_type' )) : $view;
		$view = apply_filters( 'EED_Events_Archive__set_type__type', $view );
		if ( ! empty( $view ) && in_array( $view, EED_Events_Archive::$_types )) {
//			echo '<h4>$view : ' . $view . '  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h4>';
//			printr( EE_Registry::instance()->REQ, 'EE_Registry::instance()->REQ  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );
			self::$_type = $view;
		} 
	}

	/**
	 * 	_show_expired
	 *
	 *  @access 	private
	 *  @param	boolean	$req_only if TRUE, then ignore defaults and only return $_POST value
	 *  @return 	boolean
	 */
	private static function _show_expired( $req_only = FALSE ) {	
		// get default value for "display_expired_events" as set in the EE General Settings > Templates > Event Listings 
		$show_expired = ! $req_only && isset( EE_Registry::instance()->CFG->EED_Events_Archive['display_expired_events'] ) ? EE_Registry::instance()->CFG->EED_Events_Archive['display_expired_events'] : FALSE;
		// override default expired option if set via filter
		$show_expired = EE_Registry::instance()->REQ->is_set( 'elf_expired_chk' ) ? absint( EE_Registry::instance()->REQ->get( 'elf_expired_chk' )) : $show_expired;
		return $show_expired ? TRUE : FALSE;
	}

	/**
	 * 	_event_category_slug
	 *
	 *  @access 	private
	 *  @return 	string
	 */
	private static function _event_category_slug() {			
		return EE_Registry::instance()->REQ->is_set( 'elf_category_dd' ) ? sanitize_text_field( EE_Registry::instance()->REQ->get( 'elf_category_dd' )) : '';
	}

	/**
	 * 	_display_month - what month should the event list display events for?
	 *
	 *  @access 	private
	 *  @return 	string
	 */
	private static function _display_month() {			
		return EE_Registry::instance()->REQ->is_set( 'elf_month_dd' ) ? sanitize_text_field( EE_Registry::instance()->REQ->get( 'elf_month_dd' )) : '';
	}



	/**
	 * 	get_post_data
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public function get_post_data() {
		$this->_elf_month = EED_Events_Archive::_display_month();
		$this->_elf_category = EED_Events_Archive::_event_category_slug();
		$this->_show_expired = EED_Events_Archive::_show_expired( TRUE );
//		printr( $this->EE->REQ, '$this->EE->REQ  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );
//		echo '<h4>$this->_elf_month : ' . $this->_elf_month . '  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h4>';
//		echo '<h4>$this->_elf_category : ' . $this->_elf_category . '  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h4>';
//		printr( $this->_elf_category, '$this->_elf_category  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );
//		echo '<h4>$this->_show_expired : ' . $this->_show_expired . '  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h4>';
//		echo '<h4>$this->_type : ' . $this->_type . '  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h4>';
	}


	/**
	 * 	pre_get_posts
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public function pre_get_posts( $wp_query ) {
//		d( $wp_query );
		//$wp_query->query_vars['post_type'] = 'espresso_events';
	}


	/**
	 * 	posts_join
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public function posts_join( $SQL ) {
		global $wp_query;
//		d( $wp_query );		
		if ( isset( $wp_query->query_vars ) && isset( $wp_query->query_vars['post_type'] ) && $wp_query->query_vars['post_type'] == 'espresso_events' ) {
			// Category
//			$elf_category = EE_Registry::instance()->REQ->is_set( 'elf_category_dd' ) ? sanitize_text_field( EE_Registry::instance()->REQ->get( 'elf_category_dd' )) : '';
			$SQL .= EED_Events_Archive::posts_join_sql_for_terms( EED_Events_Archive::_event_category_slug() );
		}
		return $SQL;
	}


	/**
	 * 	posts_join_sql_for_terms
	 *
	 *  @access 	public
	 *  @param	mixed boolean|string	$join_terms pass TRUE or term string, doesn't really matter since this value doesn't really get used for anything yet
	 *  @return 	string
	 */
	public static function posts_join_sql_for_terms( $join_terms = NULL ) {
		$SQL= '';
		if ( ! empty( $join_terms )) {
			global $wpdb;
			$SQL .= " LEFT JOIN $wpdb->term_relationships ON ($wpdb->posts.ID = $wpdb->term_relationships.object_id)";
			$SQL .= " LEFT JOIN $wpdb->term_taxonomy ON ($wpdb->term_relationships.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id)";
			$SQL .= " LEFT JOIN $wpdb->terms ON ($wpdb->terms.term_id = $wpdb->term_taxonomy.term_id) ";
		}
		return  $SQL;
	}


	/**
	 * 	posts_join_for_orderby
	 * 	usage:  $SQL .= EED_Events_Archive::posts_join_for_orderby( $orderby_params );
	 *
	 *  @access 	public
	 *  @param	array	$orderby_params 
	 *  @return 	string
	 */
	public static function posts_join_for_orderby( $orderby_params = array() ) {
		$SQL= '';
		$orderby_params = is_array( $orderby_params ) ? $orderby_params : array( $orderby_params );
		foreach( $orderby_params as $orderby ) {
			switch ( $orderby ) {
				
				case 'ticket_start' :
				case 'ticket_end' :
					$SQL .= ' LEFT JOIN ' . EEM_Datetime_Ticket::instance()->table() . ' ON (' . EEM_Datetime::instance()->table() . '.DTT_ID = ' . EEM_Datetime_Ticket::instance()->table() . '.DTT_ID )';
					$SQL .= ' LEFT JOIN ' . EEM_Ticket::instance()->table() . ' ON (' . EEM_Datetime_Ticket::instance()->table() . '.TKT_ID = ' . EEM_Ticket::instance()->table() . '.TKT_ID )';
					break;
				
				case 'venue_title' :
				case 'city' :
					$SQL .= ' LEFT JOIN ' . EEM_Event_Venue::instance()->table() . ' ON (' . $wpdb->posts . '.ID = ' . EEM_Event_Venue::instance()->table() . '.EVT_ID )';
					$SQL .= ' LEFT JOIN ' . EEM_Venue::instance()->table() . ' ON (' . EEM_Event_Venue::instance()->table() . '.VNU_ID = ' . EEM_Venue::instance()->table() . '.VNU_ID )';
					break;
				
				case 'state' :
					$SQL .= ' LEFT JOIN ' . EEM_Event_Venue::instance()->table() . ' ON (' . $wpdb->posts . '.ID = ' . EEM_Event_Venue::instance()->table() . '.EVT_ID )';
					$SQL .= ' LEFT JOIN ' . EEM_Event_Venue::instance()->second_table() . ' ON (' . EEM_Event_Venue::instance()->table() . '.VNU_ID = ' . EEM_Event_Venue::instance()->second_table() . '.VNU_ID )';
					break;
				
				break;
				
			}
		}
		return  $SQL;
	}


	/**
	 * 	posts_where
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public function posts_where( $SQL ) {
		global $wp_query;
		if ( isset( $wp_query->query_vars ) && isset( $wp_query->query_vars['post_type'] ) && $wp_query->query_vars['post_type'] == 'espresso_events'  ) {			
			// Show Expired ?
			$SQL .= EED_Events_Archive::posts_where_sql_for_show_expired( EED_Events_Archive::_show_expired() );
			// Category
			//$elf_category = EED_Events_Archive::_event_category_slug();
			$SQL .=  EED_Events_Archive::posts_where_sql_for_event_category_slug( EED_Events_Archive::_event_category_slug() );
			// Start Date
			//$elf_month = EED_Events_Archive::_display_month();
			$SQL .= EED_Events_Archive::posts_where_sql_for_event_list_month( EED_Events_Archive::_display_month() );
		}
		return $SQL;
	}


	/**
	 * 	posts_where_sql_for_show_expired
	 *
	 *  @access 	public
	 *  @param	boolean	$show_expired if TRUE, then displayed past events
	 *  @return 	string
	 */
	public static function posts_where_sql_for_show_expired( $show_expired = FALSE ) {
		return  $show_expired != FALSE ? ' AND ' . EEM_Datetime::instance()->table() . '.DTT_EVT_end > "' . date('Y-m-d H:s:i') . '" ' : '';
	}


	/**
	 * 	posts_where_sql_for_event_category_slug
	 *
	 *  @access 	public
	 *  @param	boolean	$event_category_slug
	 *  @return 	string
	 */
	public static function posts_where_sql_for_event_category_slug( $event_category_slug = NULL ) {
		global $wpdb;
		return  ! empty( $event_category_slug ) ? ' AND ' . $wpdb->terms . '.slug = "' . $event_category_slug . '" ' : '';
	}

	/**
	 * 	posts_where_sql_for_event_list_month
	 *
	 *  @access 	public
	 *  @param	boolean	$month
	 *  @return 	string
	 */
	public static function posts_where_sql_for_event_list_month( $month = NULL ) {
		$SQL= '';
		if ( ! empty( $month )) {
			// event start date is LESS than the end of the month ( so nothing that doesn't start until next month )
			$SQL = ' AND ' . EEM_Datetime::instance()->table() . '.DTT_EVT_start <= "' . date('Y-m-t 23:59:59', strtotime( $month )) . '"';
			// event end date is GREATER than the start of the month ( so nothing that ended before this month )
			$SQL .= ' AND ' . EEM_Datetime::instance()->table() . '.DTT_EVT_end >= "' . date('Y-m-d 0:0:00', strtotime( $month )) . '" ';
		}
		return $SQL;
	}


	/**
	 * 	posts_orderby
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public function posts_orderby( $SQL ) {
		global $wp_query;
		if ( isset( $wp_query->query_vars ) && isset( $wp_query->query_vars['post_type'] ) && $wp_query->query_vars['post_type'] == 'espresso_events' ) {			
			$SQL = EED_Events_Archive::posts_orderby_sql( array( 'start_date' ));
		}
		return $SQL;
	}


	/**
	 * 	posts_orderby_sql
	 * 
	 * 	possible parameters:
	 * 	ID
	 * 	start_date
	 * 	end_date
	 * 	event_name
	 * 	category_slug
	 * 	ticket_start
	 * 	ticket_end
	 * 	venue_title 
	 * 	city
	 * 	state
	 * 
	 * 	**IMPORTANT**  
	 * 	make sure to also send the $orderby_params array to the posts_join_for_orderby() method
	 * 	or else some of the table references below will result in MySQL errors
	 *
	 *  @access 	public
	 *  @param	boolean	$orderby_params
	 *  @return 	string
	 */
	public static function posts_orderby_sql( $orderby_params = array(), $sort = 'ASC' ) {
		global $wpdb;
		$SQL = '';
		$cntr = 1;
		$orderby_params = is_array( $orderby_params ) ? $orderby_params : array( $orderby_params );
		foreach( $orderby_params as $orderby ) {
			$glue = $cntr == 1 || $cntr == count( $orderby_params ) ? ' ' : ', ';
			switch ( $orderby ) {
				
				case 'id' :
				case 'ID' :
					$SQL .= $glue . $wpdb->posts . '.ID ' . $sort;
					break;
				
				case 'start_date' :
					$SQL .= $glue . EEM_Datetime::instance()->table() . '.DTT_EVT_start ' . $sort;
					break;
				
				case 'end_date' :
					$SQL .= $glue . EEM_Datetime::instance()->table() . '.DTT_EVT_end ' . $sort;
					break;
				
				case 'event_name' :
					$SQL .= $glue . $wpdb->posts . '.post_title ' . $sort;
					break;
				
				case 'category_slug' :
					$SQL .= $glue . $wpdb->terms . '.slug ' . $sort;
					break;
				
				case 'ticket_start' :
					$SQL .= $glue . EEM_Ticket::instance()->table() . '.TKT_start_date ' . $sort;
					break;
				
				case 'ticket_end' :
					$SQL .= $glue . EEM_Ticket::instance()->table() . '.TKT_end_date ' . $sort;
					break;
				
				case 'venue_title' :
					$SQL .= $glue . 'venue_title ' . $sort;
					break;
				
				case 'city' :
					$SQL .= $glue . EEM_Venue::instance()->second_table() . '.VNU_city ' . $sort;
				break;
				
				case 'state' :
					$SQL .= $glue . EEM_State::instance()->table() . '.STA_name ' . $sort;
				break;
				
			}
			$cntr++;
		}
		//echo '<h4>$SQL : ' . $SQL . '  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span></h4>';
		return  $SQL;
	}



	/**
	 * 	_initial_setup
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	private function _load_assests() {
		do_action( 'AHEE__EED_Events_Archive__before_load_assests' );
		add_filter( 'FHEE_load_css', '__return_true' );
		add_filter( 'FHEE_load_EE_Session', '__return_true' );
		add_action('wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ), 10 );
		if ( EE_Registry::instance()->CFG->map_settings->use_google_maps ) {
			EE_Registry::instance()->load_helper( 'Maps' );
			add_action('wp_enqueue_scripts', array( 'EEH_Maps', 'espresso_google_map_js' ), 11 );
		}
		//add_filter( 'the_excerpt', array( $this, 'the_excerpt' ), 999 );
		$this->EE->load_helper( 'Event_View' );
	}





	/**
	 * 	_get_template
	 *
	 *  @access 	private
	 *  @return 	string
	 */
	private function _get_template( $which = 'part' ) {
		return EVENT_ESPRESSO_TEMPLATES . 'espresso_events' . DS . 'archive-espresso_events.php';		
	}



	/**
	 * 	excerpt_length
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public function excerpt_length( $length ) {
		
		if ( self::$_type == 'grid' ) {
			return 36;
		}
		
		switch ( EE_Registry::instance()->CFG->template_settings->EED_Events_Archive->event_list_grid_size ) {
			case 'tiny' :
				return 12;
				break;
			case 'small' :
				return 24;
				break;
			case 'large' :
				return 48;
				break;
			case 'medium' :
			default :
				return 36;
		}		
	}



	/**
	 * 	excerpt_more
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public function excerpt_more( $more ) {
		return '&hellip;';
	}




	/**
	 * 	the_excerpt
	 *
	 *  @access 	public
	 *  @return 	void
	 */
//	public function the_excerpt( $the_excerpt ) {
//		$display_address = isset( $this->EE->CFG->template_settings->EED_Events_Archive['display_description'] ) ? $this->EE->CFG->template_settings->EED_Events_Archive['display_description'] : TRUE;
//		return $display_address ? $the_excerpt : '';			
//	}





	/**
	 * 	wp_enqueue_scripts
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public function wp_enqueue_scripts() {
		// get some style
		if ( apply_filters( 'FHEE_enable_default_espresso_css', FALSE )) {
			// first check uploads folder
			if ( file_exists( get_stylesheet_directory() . 'espresso_events/archive-espresso_events.css' )) {
				wp_register_style( 'archive-espresso_events', get_stylesheet_directory_uri() . 'espresso_events/archive-espresso_events.css', array() );
			} else {
				wp_register_style( 'archive-espresso_events', EE_TEMPLATES_URL . 'espresso_events/archive-espresso_events.css', array() );
			}
			if ( file_exists( get_stylesheet_directory() . 'espresso_events/archive-espresso_events.js' )) {
				wp_register_script( 'archive-espresso_events', get_stylesheet_directory_uri() . 'espresso_events/archive-espresso_events.js', array( 'jquery-masonry' ), '1.0', TRUE  );
			} else {
				wp_register_script( 'archive-espresso_events', EVENTS_ARCHIVE_ASSETS_URL . 'archive-espresso_events.js', array( 'jquery-masonry' ), '1.0', TRUE );
			}
			wp_enqueue_style( 'archive-espresso_events' );
			wp_enqueue_script( 'jquery-masonry' );
			wp_enqueue_script( 'archive-espresso_events' );
			wp_localize_script( 'archive-espresso_events', 'espresso_grid_event_lists', EED_Events_Archive::$espresso_grid_event_lists );
		}
	}




	/**
	 * 	template_settings_form
	 *
	 *  @access 	public
	 *  @static
	 *  @return 	void
	 */
	public static function template_settings_form() {
		$EE = EE_Registry::instance();
		$EE->CFG->template_settings->EED_Events_Archive = isset( $EE->CFG->template_settings->EED_Events_Archive ) ? $EE->CFG->template_settings->EED_Events_Archive : new EE_Events_Archive_Config();
		$EE->CFG->template_settings->EED_Events_Archive = apply_filters( 'FHEE__Event_List__template_settings_form__event_list_config', $EE->CFG->template_settings->EED_Events_Archive );
		EEH_Template::display_template( EVENTS_ARCHIVE_TEMPLATES_PATH . 'admin-event-list-settings.template.php', $EE->CFG->template_settings->EED_Events_Archive );
	}





	/**
	 * 	set_default_settings
	 *
	 *  @access 	public
	 *  @static
	 *  @return 	void
	 */
	public static function set_default_settings( $CFG ) {
		//printr( $CFG, '$CFG  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );
		$CFG->display_description = isset( $CFG->display_description ) && ! empty( $CFG->display_description ) ? $CFG->display_description : 1;
		$CFG->display_address = isset( $CFG->display_address ) && ! empty( $CFG->display_address ) ? $CFG->display_address : FALSE;
		$CFG->display_venue = isset( $CFG->display_venue ) && ! empty( $CFG->display_venue ) ? $CFG->display_venue : FALSE;
		$CFG->display_expired_events = isset( $CFG->display_expired_events ) && ! empty( $CFG->display_expired_events ) ? $CFG->display_expired_events : FALSE;
		$CFG->default_type = isset( $CFG->default_type ) && ! empty( $CFG->default_type ) ? $CFG->default_type : 'grid';
		$CFG->event_list_grid_size = isset( $CFG->event_list_grid_size ) && ! empty( $CFG->event_list_grid_size ) ? $CFG->event_list_grid_size : 'medium';
		$CFG->templates['full'] = isset( $CFG->templates['full'] ) && ! empty( $CFG->templates['full'] ) ? $CFG->templates['full'] : EVENT_ESPRESSO_TEMPLATES . 'espresso_events' . DS . 'archive-espresso_events.php';
		$CFG->templates['part'] = isset( $CFG->templates['part'] ) && ! empty( $CFG->templates['part'] ) ? $CFG->templates['part'] : EVENT_ESPRESSO_TEMPLATES . 'espresso_events' . DS . 'archive-espresso_events-grid-view.php';
		return $CFG;
	}



	/**
	 * 	filter_config
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public function filter_config( $CFG ) {
		return $CFG;
	}




	/**
	 * 	filter_config
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public function update_template_settings( $CFG, $REQ ) {
//		printr( $REQ, '$REQ  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );
//		printr( $CFG, '$CFG  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );
		//$CFG->template_settings->EED_Events_Archive = new stdClass();
		$CFG->EED_Events_Archive->display_description = isset( $REQ['display_description_in_event_list'] ) ? absint( $REQ['display_description_in_event_list'] ) : 1;
		$CFG->EED_Events_Archive->display_address = isset( $REQ['display_address_in_event_list'] ) ? absint( $REQ['display_address_in_event_list'] ) : FALSE;
		$CFG->EED_Events_Archive->display_venue = isset( $REQ['display_venue_in_event_list'] ) ? absint( $REQ['display_venue_in_event_list'] ) : FALSE;
		$CFG->EED_Events_Archive->display_expired_events = isset( $REQ['display_expired_events'] ) ? absint( $REQ['display_expired_events'] ) : FALSE;
		$CFG->EED_Events_Archive->default_type = isset( $REQ['default_type'] ) ? sanitize_text_field( $REQ['default_type'] ) : 'grid';
		$CFG->EED_Events_Archive->event_list_grid_size = isset( $REQ['event_list_grid_size'] ) ? sanitize_text_field( $REQ['event_list_grid_size'] ) : 'medium';
		$CFG->EED_Events_Archive->templates = array(
				'full'  => EVENT_ESPRESSO_TEMPLATES . 'espresso_events' . DS . 'archive-espresso_events.php'
			);
		
		switch ( $CFG->EED_Events_Archive->default_type ) {
			case 'dates' :
					$CFG->EED_Events_Archive->templates['part'] = EVENT_ESPRESSO_TEMPLATES . 'espresso_events' . DS . 'archive-espresso_events-dates-view.php';
				break;
			case 'text' :
					$CFG->EED_Events_Archive->templates['part'] = EVENT_ESPRESSO_TEMPLATES . 'espresso_events' . DS . 'archive-espresso_events-text-view.php';
				break;
			default :
					$CFG->EED_Events_Archive->templates['part'] = EVENT_ESPRESSO_TEMPLATES . 'espresso_events' . DS . 'archive-espresso_events-grid-view.php';
		}
		
		return $CFG;
	}





	/**
	 * 	get_template_part
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public static function get_template_part() {
		switch ( self::$_type ) {
			case 'dates' :
					return 'archive-espresso_events-dates-view.php';
				break;
			case 'text' :
					return 'archive-espresso_events-text-view.php';
				break;
			default :
					return 'archive-espresso_events-grid-view.php';
		}
		
//		return EE_Registry::instance()->CFG->EED_Events_Archive['templates']['part'];
	}



	/**
	 * 	event_list_template_filters
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public function event_list_template_filters() {
		$args = array(
			'form_url' => add_query_arg( array( ), home_url( __( 'events', 'event_espresso' )) ),
			'elf_month' => $this->_elf_month,
			'elf_category' => $this->_elf_category,
			'elf_show_expired' => $this->_show_expired,
			'elf_type' => $this->_type
		);
		EEH_Template::display_template( EVENT_ESPRESSO_TEMPLATES . 'espresso_events' . DS . 'archive-espresso_events-filters.php', $args );		
	}






	/**
	 * 	event_list_css
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public static function event_list_css() {
		$EE = EE_Registry::instance();
		$event_list_css = array( 'espresso-event-list-event' );
		if ( self::$_type == 'grid' ) {
			$event_list_grid_size = isset( $EE->CFG->template_settings->EED_Events_Archive->event_list_grid_size ) ? $EE->CFG->template_settings->EED_Events_Archive->event_list_grid_size : 'medium';
			$event_list_css[] = $event_list_grid_size . '-event-list-grid';
		}
		$event_list_css = apply_filters( 'EED_Events_Archive__event_list_css__event_list_css_array', $event_list_css );
		return implode( ' ', $event_list_css );
	}





	/**
	 * 	event_categories
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public static function event_categories() {
		$event_categories = EE_Registry::instance()->load_model('Term')->get_all_ee_categories();
//		printr( $event_categories, '$event_categories  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );
		return $event_categories;
	}



	/**
	 * 	display_description
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public static function display_description( $value ) {
		$EE = EE_Registry::instance();
		$display_description= isset( $EE->CFG->template_settings->EED_Events_Archive->display_description ) ? $EE->CFG->template_settings->EED_Events_Archive->display_description : 0;
		return $display_description === $value ? TRUE : FALSE;
	}



	/**
	 * 	display_venue
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public static function display_venue_details() {
		$EE = EE_Registry::instance();
		$EE->load_helper( 'Venue_View' );
		$display_venue= isset( $EE->CFG->template_settings->EED_Events_Archive->display_venue ) ? $EE->CFG->template_settings->EED_Events_Archive->display_venue : FALSE;
		$venue_name = EEH_Venue_View::venue_name();
		return $display_venue && ! empty( $venue_name ) ? TRUE : FALSE;
	}


	/**
	 * 	display_address
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public static function display_address() {
		$EE = EE_Registry::instance();
		$EE->load_helper( 'Venue_View' );
		$display_address= isset( $EE->CFG->template_settings->EED_Events_Archive->display_address ) ? $EE->CFG->template_settings->EED_Events_Archive->display_address : FALSE;
		$venue_name = EEH_Venue_View::venue_name();
		return $display_address && ! empty( $venue_name ) ? TRUE : FALSE;
	}





	/**
	 * 	event_list_title
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public static function event_list_title() {
		return apply_filters( 'EED_Events_Archive__event_list_title__event_list_title', __( 'Upcoming Events', 'event_espresso' ));
	}	


}



function espresso_get_event_list_ID() {
	EED_Events_Archive::$espresso_event_list_ID++;
	return EED_Events_Archive::$espresso_event_list_ID;
}


function espresso_grid_event_list( $ID ) {
	EED_Events_Archive::$espresso_grid_event_lists[] = $ID;	
	return $ID;
}


function espresso_event_list_title() {
	return EED_Events_Archive::event_list_title();
}

function espresso_event_list_css() {
	return EED_Events_Archive::event_list_css();
}
 
function espresso_event_categories() {
	return EED_Events_Archive::event_categories();
}
 
function espresso_display_full_description_in_event_list() {
	return EED_Events_Archive::display_description( 2 );
}

function espresso_display_excerpt_in_event_list() {
	return EED_Events_Archive::display_description( 1 );
}

function espresso_event_list_template_part() {
	return EED_Events_Archive::get_template_part();
}

function espresso_display_details_venue_in_event_list() {
	return EED_Events_Archive::display_venue_details();
}

function espresso_display_venue_address_in_event_list() {
	return EED_Events_Archive::display_address();
}



class EE_Event_List_Query extends WP_Query {

	private $_title = NULL;
	private $_limit = 10;
	private $_css_class = NULL;
	private $_show_expired = FALSE;
	private $_month = NULL;
	private $_category_slug = NULL;
	private $_order_by = NULL;
	private $_sort = NULL;
	private $_list_type ='text';	

	function __construct( $args = array() ) {
		//printr( $args, '$args  <br /><span style="font-size:10px;font-weight:normal;">' . __FILE__ . '<br />line no: ' . __LINE__ . '</span>', 'auto' );
		// incoming args could be a mix of WP query args + EE shortcode args
		foreach ( $args as $key =>$value ) {
			$property = '_' . $key;
			// if the arg is a property of this class, then it's an EE shortcode arg
			if ( property_exists( $this, $property )) {
				// set the property value
				$this->$property = $value;
				// then remove it from the array of args that will later be passed to WP_Query() 
				unset( $args[ $key ] );
			}
		}
		// parse orderby attribute
		if ( $this->_order_by !== NULL ) {
			$this->_order_by = explode( ',', $this->_order_by );
			$this->_order_by = array_map('trim', $this->_order_by);
		}
		$this->_sort = in_array( $this->_sort, array( 'ASC', 'asc', 'DESC', 'desc' )) ? strtoupper( $this->_sort ) : 'ASC';
		
		// first off, let's remove any filters from previous queries
		remove_filter( 'EED_Events_Archive__set_type__type', array( $this, 'event_list_type' ));
		remove_filter( 'EED_Events_Archive__event_list_title__event_list_title', array( $this, 'event_list_title' )); 
		remove_all_filters( 'EED_Events_Archive__event_list_css__event_list_css_array' );
//		remove_all_filters( 'EED_Events_Archive__event_list_css__event_list_css_array', array( $this, 'event_list_css' ));

		//  set view
		add_filter( 'EED_Events_Archive__set_type__type', array( $this, 'event_list_type' ), 10, 1 );
		// have to call this in order to get the above filter applied
		EED_Events_Archive::set_type();
		// Event List Title ?
		add_filter( 'EED_Events_Archive__event_list_title__event_list_title', array( $this, 'event_list_title' ), 10, 1 ); 
		// add the css class
		add_filter( 'EED_Events_Archive__event_list_css__event_list_css_array', array( $this, 'event_list_css' ), 10, 1 );

		// Force these args
		$args = array_merge( $args, array(
			'post_type' => 'espresso_events',
			'posts_per_page' => $this->_limit,
			'update_post_term_cache' => FALSE,
			'update_post_meta_cache' => FALSE
		));
		// filter the query parts
		add_filter( 'posts_join', array( $this, 'posts_join' ), 10, 1 );
		add_filter( 'posts_where', array( $this, 'posts_where' ), 10, 1 );
		add_filter( 'posts_orderby', array( $this, 'posts_orderby' ), 10, 1 );
		
		// run the query
		parent::__construct( $args );
	}



	/**
	 * 	posts_join
	 *
	 *  @access 	public
	 *  @return 	string
	 */
	public function posts_join( $SQL ) {
		// first off, let's remove any filters from previous queries
		remove_filter( 'posts_join', array( $this, 'posts_join' ));
		// generate the SQL
		if ( $this->_category_slug !== NULL ) {
			$SQL .= EED_Events_Archive::posts_join_sql_for_terms( TRUE );
		}
		if ( $this->_order_by !== NULL ) {
			$SQL .= EED_Events_Archive::posts_join_for_orderby( $this->_order_by );
		}
		return $SQL;
	}


	/**
	 * 	posts_where
	 *
	 *  @access 	public
	 *  @return 	string
	 */
	public function posts_where( $SQL ) {
		// first off, let's remove any filters from previous queries
		remove_filter( 'posts_where', array( $this, 'posts_where' ));
		// Show Expired ?
		$this->_show_expired = $this->_show_expired ? TRUE : FALSE;
		$SQL .= EED_Events_Archive::posts_where_sql_for_show_expired( $this->_show_expired );
		// Category
		$SQL .=  EED_Events_Archive::posts_where_sql_for_event_category_slug( $this->_category_slug );
		// Start Date
		$SQL .= EED_Events_Archive::posts_where_sql_for_event_list_month( $this->_month );
		return $SQL;
	}


	/**
	 * 	posts_orderby
	 *
	 *  @access 	public
	 *  @return 	string
	 */
	public function posts_orderby( $SQL ) {
		// first off, let's remove any filters from previous queries
		remove_filter( 'posts_orderby', array( $this, 'posts_orderby' ) );
		// generate the SQL
		$SQL =  EED_Events_Archive::posts_orderby_sql( $this->_order_by, $this->_sort );
		return $SQL;
	}


	/**
	 * 	event_list_title
	 *
	 *  @access 	public
	 *  @return 	string
	 */
	public function event_list_type( $event_list_type ) {
		if ( ! empty( $this->_list_type )) {
			return $this->_list_type;
		}
		return $event_list_type;
	}


	/**
	 * 	event_list_title
	 *
	 *  @access 	public
	 *  @return 	string
	 */
	public function event_list_title( $event_list_title ) {
		if ( ! empty( $this->_title )) {
			return $this->_title;
		}
		return $event_list_title;
	}



	/**
	 * 	event_list_css
	 *
	 *  @access 	public
	 *  @return 	array
	 */
	public function event_list_css( $event_list_css ) {
		if ( ! empty( $this->_css_class )) {
			$event_list_css[] = $this->_css_class;
		}
		if ( ! empty( $this->_category_slug )) {
			$event_list_css[] = $this->_category_slug;
		}
		return $event_list_css;
	}






}



/**
 * stores Events_Archive settings
 */
class EE_Events_Archive_Config extends EE_Config_Base{

	public $display_description;
	public $display_addresss;
	public $display_venue;
	public $display_expired_events;
	public $default_type;
	public $event_list_grid_size;
	public $templates;
	
	public function __construct(){
		$this->display_description = 1;
		$this->display_address = FALSE;
		$this->display_venue = FALSE;
		$this->display_expired_events = FALSE;
		$this->default_type = 'grid';
		$this->event_list_grid_size = 'medium';
		$this->templates = array( 'full'  => EVENT_ESPRESSO_TEMPLATES . 'espresso_events' . DS . 'archive-espresso_events.php' );
	}
}



// End of file EED_Events_Archive.module.php
// Location: /modules/events_archive/EED_Events_Archive.module.php