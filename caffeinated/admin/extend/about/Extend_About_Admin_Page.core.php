<?php
if (!defined('EVENT_ESPRESSO_VERSION') )
	exit('NO direct script access allowed');

/**
 * Event Espresso
 *
 * Event Registration and Management Plugin for Wordpress
 *
 * @package		Event Espresso
 * @author		Seth Shoultes
 * @copyright	(c)2009-2012 Event Espresso All Rights Reserved.
 * @license		http://eventespresso.com/support/terms-conditions/  ** see Plugin Licensing **
 * @link		http://www.eventespresso.com
 * @version		4.0
 *
 * ------------------------------------------------------------------------
 *
 * Extend_About_Admin_Page
 *
 * This contains the logic for setting up the caffeinated EE About related pages.  Any methods without phpdoc comments have inline docs with parent class. 
 *
 * This is the extended (caf) general settings class
 *
 * @package		Extend_About_Admin_Page
 * @subpackage	caffeinated/admin/extend/about/Extend_About_Admin_Page.core.php
 * @author 		Darren Ethier
 *
 * ------------------------------------------------------------------------
 */
class Extend_About_Admin_Page extends About_Admin_Page {



	public function __construct( $routing = TRUE ) {
		parent::__construct( $routing );
		define( 'EE_ABOUT_CAF_TEMPLATE_PATH', EE_CORE_CAF_ADMIN_EXTEND . 'about/templates/' );
	}



	protected function _extend_page_config() {
		$this->_admin_base_path = EE_CORE_CAF_ADMIN_EXTEND . 'about';
	}


	protected function _whats_new() {
		$steps .= '<h3>'.__('Getting Started').'</h3>';
		$steps .= '<p>'.sprintf( __('Step 1: Visit your %sOrganization Settings%s and add/update your details', 'event_espresso'), '<a href="admin.php?page=espresso_general_settings">', '</a>') .'</strong></p>';
		$steps .= '<p>'.sprintf( __('Step 2:  Setup your %sPayment Methods%s', 'event_espresso'), '<a href="admin.php?page=espresso_payment_settings">', '</a>') .'</strong></p>';
		$steps .= '<p>'.sprintf( __('Step 3: Create your %sFirst Event%s', 'event_espresso'), '<a href="admin.php?page=espresso_events&action=create_new">', '</a>') .'</strong></p>';
		$this->_template_args['admin_page_title'] = sprintf( __('Welcome to Event Espresso %s', 'event_espresso'), EVENT_ESPRESSO_VERSION );
		$settings_message = EE_Registry::instance()->CFG->organization->address_1 == '123 Onna Road' && EE_Maintenance_Mode::instance()->level() != EE_Maintenance_Mode::level_2_complete_maintenance ? $steps : '';
		$this->_template_args['admin_page_subtitle'] = sprintf( __('Thank you for choosing Event Espresso, the most powerful WordPress plugin for Event Management.%s', 'event_espresso'), $settings_message );
		$template = EE_ABOUT_CAF_TEMPLATE_PATH . 'whats_new.template.php';
		$this->_template_args['about_admin_page_content'] = EEH_Template::display_template( $template, $this->_template_args, TRUE );
		$this->display_about_admin_page();
	}

	protected function _credits() {
	//	$this->_template_args['admin_page_title'] = sprintf( __('Welcome to Event Espresso %s', 'event_espresso'), EVENT_ESPRESSO_VERSION );
		$this->_template_args['admin_page_subtitle'] = __('Thank you for using Event Espresso, the most powerful WordPress plugin for Event Management.', 'event_espresso');
		$template = EE_ABOUT_TEMPLATE_PATH . 'credits.template.php';
		$this->_template_args['about_admin_page_content'] = EEH_Template::display_template( $template, $this->_template_args, TRUE );
		$this->display_about_admin_page();
	}
}