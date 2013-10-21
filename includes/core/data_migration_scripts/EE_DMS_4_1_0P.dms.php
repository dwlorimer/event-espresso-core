<?php
/**
 * meant to convert DBs between 3.1.26 and 4.0.0 to 4.1.0
 */
//make sure we have all the stages loaded too
//unfortunately, this needs to be done upon INCLUSION of this file,
//instead of construction, because it only gets constructed on first page load 
//(all other times it gets resurrected from a wordpress option)
$stages = glob(EE_CORE.'data_migration_scripts/4_1_0P_stages/*');
$class_to_filepath = array();
foreach($stages as $filepath){
	$matches = array();
	preg_match('~4_1_0P_stages/(.*).dmsstage.php~',$filepath,$matches);
	$class_to_filepath[$matches[1]] = $filepath;
}
EEH_Autoloader::register_autoloader($class_to_filepath);

/**
 * Organizes all the various stages of the migration from 3.1 (but only versions above 3.1.26, 
 * lower versions need to eb upgraded to 3.1.26 normally) to 4.1.0.P. 
 * It adds the database tables on some of the first migration_steps, then migrates the data within
 * each stage.
 * 
 * External Dependencies:
 * -function EEH_Activation::create_table($table_name,$table_sql,$engine)
 * -class EE_Config with attributes and function:
 * --static function instance() which returns the instance of EE_Config
 * --that the instance of EE_Config have an property named 'gateway' which is a class with properties '-'payment_settings' and 'active_gateways'
 *	 which are both arrays
 * --a function named update_espresso_config() which saves the EE_Config object to teh database
 * --...and all its subclasses... really, you're best off copying the whole thin gwhen 4.1 is released into this file and wrapping its declaration in if( ! class_exists()){...}
 */
class EE_DMS_4_1_0P extends EE_Data_Migration_Script_Base{

	
	
	public function __construct() {
		$this->_pretty_name = __("Data Migration to Event Espresso 4.1.0P", "event_espresso");
		$this->_migration_stages = array(
			new EE_DMS_4_1_0P_org_options(),
			new EE_DMS_4_1_0P_gateways(),
			new EE_DMS_4_1_0P_events(),
			new EE_DMS_4_1_0P_prices(),
			new EE_DMS_4_1_0P_category_details(),
			new EE_DMS_4_1_0P_event_category(),
			new EE_DMS_4_1_0P_venues(),
			new EE_DMS_4_1_0P_event_venue(),
			new EE_DMS_4_1_0P_question_groups(),
			new EE_DMS_4_1_0P_questions(),
			new EE_DMS_4_1_0P_question_group_question(),
			new EE_DMS_4_1_0P_event_question_group(),
		);
		parent::__construct();
	}
	public function can_migrate_from_version($version_string) {
		if($version_string < '4.0.0' && $version_string > '3.1.26' ){
//			echo "$version_string can be mgirated fro";
			return true;
		}elseif( ! $version_string ){
//			echo "no version string provided: $version_string";
			//no version string provided... this must be pre 4.1
			//because since 4.1 we're 
			return false;//changed mind. dont want people thinking they should migrate yet because they cant
		}else{
//			echo "$version_string doesnt apply";
			return false;
		}
	}
	public function pretty_name() {
		return __("Core Data Migration to version 4.1.0", "event_espresso");
	}
	public function schema_changes_before_migration() {
		//relies on 4.1's EEH_Activation::create_table
		require_once( EE_HELPERS . 'EEH_Activation.helper.php' );
		
		$table_name='esp_answer';
		$sql=" ANS_ID INT UNSIGNED NOT NULL AUTO_INCREMENT ,
					REG_ID INT UNSIGNED NOT NULL ,
					QST_ID INT UNSIGNED NOT NULL ,
					ANS_value TEXT NOT NULL ,
					PRIMARY KEY  (ANS_ID)";
		EEH_Activation::create_table($table_name,$sql, 'ENGINE=InnoDB');
		
		$table_name = 'esp_attendee_meta';
		$sql = "ATTM_ID int(10) unsigned NOT	NULL AUTO_INCREMENT,
						ATT_ID int(10) unsigned NOT NULL,
						ATT_fname varchar(45) NOT NULL,
						ATT_lname varchar(45) NOT	NULL,
						ATT_address varchar(45) DEFAULT	NULL,
						ATT_address2 varchar(45) DEFAULT	NULL,
						ATT_city varchar(45) DEFAULT	NULL,
						STA_ID int(10) DEFAULT	NULL,
						CNT_ISO varchar(45) DEFAULT	NULL,
						ATT_zip varchar(12) DEFAULT	NULL,
						ATT_email varchar(100) NOT NULL,
						ATT_phone varchar(45) DEFAULT NULL,
						ATT_social text,
						ATT_comments mediumtext,
						ATT_notes mediumtext,
							PRIMARY KEY  (ATTM_ID),
								KEY ATT_fname (ATT_fname),
								KEY ATT_lname (ATT_lname),
								KEY ATT_email (ATT_email)";
		EEH_Activation::create_table($table_name, $sql, 'ENGINE=InnoDB ');



		$table_name = 'esp_country';
		$sql = "CNT_ISO varchar(2) COLLATE utf8_bin NOT NULL,
					  CNT_ISO3 varchar(3) COLLATE utf8_bin NOT NULL,
					  RGN_ID tinyint(3) unsigned DEFAULT NULL,
					  CNT_name varchar(45) COLLATE utf8_bin NOT NULL,
					  CNT_cur_code varchar(6) COLLATE utf8_bin DEFAULT 'USD',
					  CNT_cur_single varchar(45) COLLATE utf8_bin DEFAULT 'dollar',
					  CNT_cur_plural varchar(45) COLLATE utf8_bin DEFAULT 'dollars',
					  CNT_cur_sign varchar(45) COLLATE utf8_bin DEFAULT '$',
					  CNT_cur_sign_b4 tinyint(1) DEFAULT '1',
					  CNT_cur_dec_plc tinyint(3) unsigned NOT NULL DEFAULT '2',
					  CNT_cur_dec_mrk varchar(1) COLLATE utf8_bin NOT NULL DEFAULT '.',
					  CNT_cur_thsnds varchar(1) COLLATE utf8_bin NOT NULL DEFAULT ',',
					  CNT_tel_code varchar(12) COLLATE utf8_bin DEFAULT NULL,
					  CNT_is_EU tinyint(1) DEFAULT '0',
					  CNT_active tinyint(1) DEFAULT '0',
					  PRIMARY KEY  (CNT_ISO)";
		EEH_Activation::create_table($table_name, $sql, 'ENGINE=InnoDB' );



		$table_name = 'esp_datetime';
		$sql = "DTT_ID int(10) unsigned NOT NULL AUTO_INCREMENT,
				  EVT_ID INT UNSIGNED NOT NULL ,
				  DTT_EVT_start datetime NOT NULL default '0000-00-00 00:00:00',
				  DTT_EVT_end datetime NOT NULL default '0000-00-00 00:00:00',
				  DTT_reg_limit mediumint(8) unsigned DEFAULT NULL,
				  DTT_sold mediumint(8) unsigned DEFAULT 0,
				  DTT_is_primary tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
				  DTT_order mediumint(3) unsigned DEFAULT 0,
				  DTT_parent int(10) unsigned DEFAULT 0,
				  DTT_deleted tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
						PRIMARY KEY  (DTT_ID),
						KEY EVT_ID (EVT_ID),
						KEY DTT_is_primary (DTT_is_primary)";
		
		
		
		EEH_Activation::create_table($table_name, $sql, 'ENGINE=InnoDB' );
		$table_name = 'esp_event_meta';
		$sql = "
			EVTM_ID INT NOT NULL AUTO_INCREMENT,
			EVT_ID int(11) unsigned NOT NULL,
			EVT_display_desc TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 ,
			EVT_display_reg_form TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 ,
			EVT_visible_on datetime NOT NULL default '0000-00-00 00:00:00',
			EVT_allow_multiple TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 ,
			EVT_additional_attendee_reg_info TINYINT(2) UNSIGNED NOT NULL DEFAULT 0,
			EVT_default_registration_status VARCHAR(3),
			EVT_phone varchar(45) DEFAULT NULL,
			EVT_additional_limit TINYINT UNSIGNED NULL ,
			EVT_require_pre_approval TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 ,
			EVT_member_only TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 ,
			EVT_allow_overflow TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 ,
			EVT_timezone_string VARCHAR(45) NULL ,
			EVT_external_URL VARCHAR(200) NULL ,
			EVT_donations TINYINT(1) NULL,
			PRIMARY KEY  (EVTM_ID)";
		EEH_Activation::create_table($table_name,$sql, 'ENGINE=InnoDB');


		
		$table_name='esp_event_question_group';
		$sql="EQG_ID INT UNSIGNED NOT NULL AUTO_INCREMENT ,
					EVT_ID INT UNSIGNED NOT NULL ,
					QSG_ID INT UNSIGNED NOT NULL ,
					EQG_primary TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
					PRIMARY KEY  (EQG_ID)";
		EEH_Activation::create_table($table_name,$sql, 'ENGINE=InnoDB');



		$table_name='esp_event_venue';
		$sql="EVV_ID INT(11) NOT NULL AUTO_INCREMENT ,
				EVT_ID INT(11) NOT NULL ,
				VNU_ID INT(11) NOT NULL ,
				EVV_primary TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
				PRIMARY KEY  (EVV_ID)";
		EEH_Activation::create_table($table_name,$sql, 'ENGINE=InnoDB');


		
		$table_name='esp_extra_meta';
		$sql="EXM_ID int(11) NOT NULL AUTO_INCREMENT,
				OBJ_ID int(11) DEFAULT NULL,
				EXM_type varchar(45) DEFAULT NULL,
				EXM_key varchar(45) DEFAULT NULL,
				EXM_value text,
				PRIMARY KEY  (EXM_ID)";
		EEH_Activation::create_table($table_name,$sql, 'ENGINE=InnoDB');

		$table_name='esp_line_item';
		$sql="LIN_ID int(11) NOT NULL AUTO_INCREMENT,
				TXN_ID int(11) DEFAULT NULL,
				LIN_name varchar(245) NOT NULL DEFAULT '',
				LIN_desc varchar(245) DEFAULT NULL,
				LIN_unit_price decimal(10,3) DEFAULT NULL,
				LIN_is_percent tinyint(1) DEFAULT 0,
				LIN_order int DEFAULT 0,
				LIN_parent int DEFAULT 0,
				LIN_type varchar(10) NOT NULL,
				LIN_total decimal(10,3) DEFAULT NULL,
				LIN_quantity int(10) DEFAULT NULL,
				LIN_taxable tinyint(1) DEFAULT NULL,
				LIN_item_id varchar(10) DEFAULT NULL,
				LIN_item_type varchar(45)DEFAULT NULL,
				PRIMARY KEY  (LIN_ID)";
		EEH_Activation::create_table($table_name,$sql, 'ENGINE=InnoDB');

		$table_name = 'esp_message_template';
		$sql = "MTP_ID int(10) unsigned NOT NULL AUTO_INCREMENT,
					GRP_ID int(10) unsigned NOT NULL,
					MTP_context varchar(50) NOT NULL,
					MTP_template_field varchar(30) NOT NULL,
					MTP_content text NOT NULL,
					PRIMARY KEY  (MTP_ID),
					KEY GRP_ID (GRP_ID)";
		EEH_Activation::create_table($table_name, $sql, 'ENGINE=InnoDB');



		$table_name = 'esp_message_template_group';
		$sql = "GRP_ID int(10) unsigned NOT NULL AUTO_INCREMENT,
					EVT_ID int(10) unsigned DEFAULT NULL,
					MTP_user_id int(10) NOT NULL DEFAULT '1',
					MTP_messenger varchar(30) NOT NULL,
					MTP_message_type varchar(50) NOT NULL,
					MTP_is_global tinyint(1) NOT NULL DEFAULT '0',
					MTP_is_override tinyint(1) NOT NULL DEFAULT '0',
					MTP_deleted tinyint(1) NOT NULL DEFAULT '0',
					MTP_is_active tinyint(1) NOT NULL DEFAULT '1',
					PRIMARY KEY  (GRP_ID),
					KEY EVT_ID (EVT_ID),
					KEY MTP_user_id (MTP_user_id)";
		EEH_Activation::create_table( $table_name, $sql, 'ENGINE=InnoDB');



		$table_name = 'esp_payment';
		$sql = "PAY_ID int(10) unsigned NOT NULL AUTO_INCREMENT,
					TXN_ID int(10) unsigned DEFAULT NULL,
					STS_ID varchar(3) COLLATE utf8_bin DEFAULT NULL,
					PAY_timestamp datetime NOT NULL default '0000-00-00 00:00:00',
					PAY_method varchar(45) COLLATE utf8_bin DEFAULT NULL,
					PAY_amount decimal(10,3) DEFAULT NULL,
					PAY_gateway varchar(32) COLLATE utf8_bin DEFAULT NULL,
					PAY_gateway_response text COLLATE utf8_bin,
					PAY_txn_id_chq_nmbr varchar(32) COLLATE utf8_bin DEFAULT NULL,
					PAY_po_number varchar(32) COLLATE utf8_bin DEFAULT NULL,
					PAY_extra_accntng varchar(45) COLLATE utf8_bin DEFAULT NULL,
					PAY_via_admin tinyint(1) NOT NULL DEFAULT '0',
					PAY_details text COLLATE utf8_bin,
					PRIMARY KEY  (PAY_ID),
					KEY TXN_ID (TXN_ID),
					KEY PAY_timestamp (PAY_timestamp)";
		EEH_Activation::create_table($table_name, $sql, 'ENGINE=InnoDB ');

		$table_name = 'esp_promotion';
		$sql = "PRO_ID INT UNSIGNED NOT NULL AUTO_INCREMENT ,
					PRC_ID INT UNSIGNED NOT NULL ,
					PRO_scope VARCHAR(16) NOT NULL DEFAULT 'event' ,
					PRO_start DATETIME NULL DEFAULT NULL ,
					PRO_end DATETIME NULL DEFAULT NULL ,
					PRO_code VARCHAR(45) NULL DEFAULT NULL ,
					PRO_uses SMALLINT UNSIGNED NULL DEFAULT NULL ,
					PRO_global TINYINT(1) NOT NULL DEFAULT 0 ,
					PRO_global_uses SMALLINT UNSIGNED NOT NULL DEFAULT 0 ,
					PRO_exclusive TINYINT(1) NOT NULL DEFAULT 0 ,
					PRO_accept_msg TINYTEXT NULL DEFAULT NULL ,
					PRO_decline_msg TINYTEXT NULL DEFAULT NULL ,
					PRO_default TINYINT(1) NOT NULL DEFAULT 0 ,
					PRO_order TINYINT UNSIGNED NOT NULL DEFAULT 40 ,
					PRIMARY KEY  (PRO_ID) ,
					KEY PRC_ID (PRC_ID)";
		EEH_Activation::create_table($table_name, $sql, 'ENGINE=InnoDB ');
		
		$table_name = 'esp_promotion_object';
		$sql = "POB_ID INT UNSIGNED NOT NULL AUTO_INCREMENT,
			PRO_ID INT UNSIGNED NOT NULL,
			OBJ_ID INT UNSIGNED NOT NULL,
			POB_type VARCHAR(45) NULL,
			POB_used INT NULL,
			PRIMARY KEY  (POB_ID),
			KEY OBJ_ID (OBJ_ID),
			KEY PRO_ID (PRO_ID)";
		EEH_Activation::create_table($table_name, $sql, 'ENGINE=InnoDB ');
		
		$table_name = 'esp_promotion_applied';
		$sql = "PRA_ID INT UNSIGNED NOT NULL AUTO_INCREMENT,
			PRO_ID INT UNSIGNED NOT NULL,
			OBJ_ID INT UNSIGNED NOT NULL,
			POB_type VARCHAR(45) NULL,
			PRIMARY KEY  (PRA_ID),
			KEY OBJ_ID (OBJ_ID),
			KEY PRO_ID (PRO_ID)";
		EEH_Activation::create_table($table_name, $sql, 'ENGINE=InnoDB ');
		
		$table_name = 'esp_promotion_rule';
		$sql = "PRR_ID INT UNSIGNED NOT NULL AUTO_INCREMENT ,
					PRO_ID INT UNSIGNED NOT NULL ,
					RUL_ID INT UNSIGNED NOT NULL ,
					PRR_order TINYINT UNSIGNED NOT NULL DEFAULT 1,
					PRR_add_rule_comparison ENUM('AND','OR') NULL DEFAULT 'AND',
					PRIMARY KEY  (PRR_ID) ,
					KEY PRO_ID (PRO_ID),
					KEY RUL_ID (RUL_ID) ";
		EEH_Activation::create_table($table_name, $sql, 'ENGINE=InnoDB ');
		
		
		
		$table_name = 'esp_rule';
		$sql = "RUL_ID INT UNSIGNED NOT NULL AUTO_INCREMENT ,
					RUL_name VARCHAR(45) NOT NULL ,
					RUL_desc TEXT NULL ,
					RUL_trigger VARCHAR(45) NOT NULL ,
					RUL_trigger_type VARCHAR(45) NULL DEFAULT NULL ,
					RUL_comparison ENUM('=','!=','<','>') NOT NULL DEFAULT '=' ,
					RUL_value VARCHAR(45) NOT NULL ,
					RUL_value_type VARCHAR(45) NULL DEFAULT NULL ,
					RUL_is_active TINYINT(1) NOT NULL DEFAULT 1 ,
					RUL_archived TINYINT(1) NOT NULL DEFAULT 0 ,
					PRIMARY KEY  (RUL_ID)";
		EEH_Activation::create_table($table_name, $sql, 'ENGINE=InnoDB ');
		


		$table_name = "esp_ticket";  
		$sql = "TKT_ID int(10) unsigned NOT NULL AUTO_INCREMENT,
					  TTM_ID int(10) unsigned NOT NULL,
					  TKT_name varchar(100) NOT NULL DEFAULT '',
					  TKT_description TEXT NOT NULL DEFAULT '',
					  TKT_qty mediumint(8) DEFAULT NULL,
					  TKT_sold mediumint(8) NOT NULL DEFAULT 0,
					  TKT_uses tinyint NOT NULL DEFAULT '-1',
					  TKT_min tinyint unsigned NOT NULL DEFAULT '0',
					  TKT_max tinyint NOT NULL DEFAULT '-1',
					  TKT_price decimal(10,3) NOT NULL DEFAULT '0.00',
					  TKT_start_date datetime NOT NULL default '0000-00-00 00:00:00',
					  TKT_end_date datetime NOT NULL default '0000-00-00 00:00:00',
					  TKT_taxable tinyint(1) unsigned NOT NULL DEFAULT '0',
					  TKT_order tinyint(3) unsigned NOT NULL DEFAULT '0',
					  TKT_row tinyint(3) unsigned NOT NULL DEFAULT '0',
					  TKT_is_default tinyint(1) unsigned NOT NULL DEFAULT '0',
					  TKT_parent int(10) unsigned DEFAULT '0',
					  TKT_deleted tinyint(1) NOT NULL DEFAULT '0',
					  PRIMARY KEY  (TKT_ID)";
		EEH_Activation::create_table($table_name, $sql, 'ENGINE=InnoDB');




		$table_name = "esp_ticket_price";  
		$sql = "TKP_ID int(10) unsigned NOT NULL AUTO_INCREMENT,
					  TKT_ID int(10) unsigned NOT NULL,
					  PRC_ID int(10) unsigned NOT NULL,
					  PRIMARY KEY  (TKP_ID)";
		EEH_Activation::create_table($table_name, $sql, 'ENGINE=InnoDB');




		$table_name = "esp_datetime_ticket";  
		$sql = "DTK_ID int(10) unsigned NOT NULL AUTO_INCREMENT,
					  DTT_ID int(10) unsigned NOT NULL,
					  TKT_ID int(10) unsigned NOT NULL,
					  PRIMARY KEY  (DTK_ID)";
		EEH_Activation::create_table($table_name, $sql, 'ENGINE=InnoDB');





		$table_name = "esp_ticket_template";  
		$sql = "TTM_ID int(10) unsigned NOT NULL AUTO_INCREMENT,
					  TTM_name varchar(45) NOT NULL,
					  TTM_description text,
					  TTM_file varchar(45),
					  PRIMARY KEY  (TTM_ID)";
		EEH_Activation::create_table($table_name, $sql, 'ENGINE=InnoDB');



		$table_name = "esp_price";  
		$sql = "PRC_ID int(10) unsigned NOT NULL AUTO_INCREMENT,
					  PRT_ID tinyint(3) unsigned NOT NULL,
					  PRC_amount decimal(10,3) NOT NULL DEFAULT '0.00',
					  PRC_name varchar(45) NOT NULL,
					  PRC_desc text,
					  PRC_is_default tinyint(1) unsigned NOT NULL DEFAULT '1',
					  PRC_overrides int(10) unsigned DEFAULT NULL,
					  PRC_deleted tinyint(1) unsigned NOT NULL DEFAULT '0',
					  PRC_order tinyint(3) unsigned NOT NULL DEFAULT '0',
					  PRC_row tinyint(3) unsigned NOT NULL DEFAULT '0',
					  PRC_parent int(10) unsigned DEFAULT 0,
					  PRIMARY KEY  (PRC_ID)";
		EEH_Activation::create_table($table_name, $sql, 'ENGINE=InnoDB');



		$table_name = "esp_price_type";
		$sql = "PRT_ID tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
				  PRT_name VARCHAR(45) NOT NULL ,
				  PBT_ID tinyint(3) unsigned NOT NULL DEFAULT '1',
				  PRT_is_member tinyint(1) NOT NULL DEFAULT '0',
				  PRT_is_percent tinyint(1) NOT NULL DEFAULT '0',
				  PRT_order tinyint(1) UNSIGNED NULL,
				  PRT_deleted tinyint(1) NOT NULL DEFAULT '0',
				  UNIQUE KEY PRT_name_UNIQUE (PRT_name),
				  PRIMARY KEY  (PRT_ID)";
		EEH_Activation::create_table($table_name, $sql, 'ENGINE=InnoDB');


		
		$table_name='esp_question';
		$sql='QST_ID INT UNSIGNED NOT NULL AUTO_INCREMENT,
					QST_display_text VARCHAR(100) NOT NULL,
					QST_admin_label VARCHAR(100) NOT NULL,
					QST_system varchar(25) DEFAULT NULL,
					QST_type VARCHAR(25) NOT NULL DEFAULT "TEXT",
					QST_required TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
					QST_required_text VARCHAR(100) NULL,
					QST_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
					QST_admin_only TINYINT(1) NOT NULL DEFAULT 0,
					QST_wp_user BIGINT UNSIGNED NULL,
					QST_deleted TINYINT UNSIGNED NOT NULL DEFAULT 0,
					PRIMARY KEY  (QST_ID)';
		EEH_Activation::create_table($table_name,$sql, 'ENGINE=InnoDB');
		
		EEH_Activation::drop_index( 'esp_question_group', 'QSG_identifier_UNIQUE' );
		
		$table_name = 'esp_question_group';
		$sql='QSG_ID INT UNSIGNED NOT NULL AUTO_INCREMENT,
					QSG_name VARCHAR(100) NOT NULL,
					QSG_identifier VARCHAR(100) NOT NULL,
					QSG_desc TEXT NULL,
					QSG_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
					QSG_show_group_name TINYINT(1) NOT NULL,
					QSG_show_group_desc TINYINT(1) NOT NULL,
					QSG_system TINYINT NULL,
					QSG_deleted TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
					PRIMARY KEY  (QSG_ID),
					UNIQUE KEY QSG_identifier_UNIQUE (QSG_identifier ASC)';
		EEH_Activation::create_table($table_name,$sql, 'ENGINE=InnoDB');
		
		
		
		$table_name='esp_question_group_question';
		$sql="QGQ_ID INT UNSIGNED NOT NULL AUTO_INCREMENT ,
					QSG_ID INT UNSIGNED NOT NULL ,
					QST_ID INT UNSIGNED NOT NULL ,
					PRIMARY KEY  (QGQ_ID) ";
		EEH_Activation::create_table($table_name,$sql, 'ENGINE=InnoDB');


		
		$table_name='esp_question_option';
		$sql="QSO_ID INT UNSIGNED NOT NULL AUTO_INCREMENT ,
					QSO_name VARCHAR(100) NOT NULL ,
					QSO_value VARCHAR(100) NOT NULL ,
					QST_ID INT UNSIGNED NOT NULL ,
					QSO_deleted TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 ,
					PRIMARY KEY  (QSO_ID)";
		EEH_Activation::create_table($table_name,$sql, 'ENGINE=InnoDB');



		$table_name = 'esp_registration';
		$sql = "REG_ID int(10) unsigned NOT NULL AUTO_INCREMENT,
					  EVT_ID int(10) unsigned NOT NULL,
					  ATT_ID int(10) unsigned NOT NULL,
					  TXN_ID int(10) unsigned NOT NULL,
					  TKT_ID int(10) unsigned NOT NULL,
					  STS_ID varchar(3) COLLATE utf8_bin NOT NULL DEFAULT 'RPN',
					  REG_date datetime NOT NULL default '0000-00-00 00:00:00',
					  REG_final_price decimal(10,3) NOT NULL DEFAULT '0.00',
					  REG_session varchar(45) COLLATE utf8_bin NOT NULL,
					  REG_code varchar(45) COLLATE utf8_bin DEFAULT NULL,
					  REG_url_link varchar(64) COLLATE utf8_bin DEFAULT NULL,
					  REG_count tinyint(4) DEFAULT '1',
					  REG_group_size tinyint(4) DEFAULT '1',
					  REG_att_is_going tinyint(1) DEFAULT '0',
					  PRIMARY KEY  (REG_ID),
					  KEY EVT_ID (EVT_ID),
					  KEY ATT_ID (ATT_ID),
					  KEY TXN_ID (TXN_ID),
					  KEY TKT_ID (TKT_ID),
					  KEY STS_ID (STS_ID),
					  KEY REG_url_link (REG_url_link),
					  KEY REG_code (REG_code)";
		EEH_Activation::create_table($table_name, $sql, 'ENGINE=InnoDB ');




		$table_name='esp_checkin';
		$sql="CHK_ID INT(10) UNSIGNED NOT NULL AUTO_INCREMENT ,
					REG_ID INT(10) unsigned NOT NULL ,
					DTT_ID INT(10) unsigned NOT NULL ,
					CHK_in TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 ,
					CHK_timestamp datetime NOT NULL default '0000-00-00 00:00:00' ,
					PRIMARY KEY  (CHK_ID)";
		EEH_Activation::create_table($table_name, $sql, 'ENGINE=InnoDB');



		$table_name = 'esp_state';
		$sql = "STA_ID smallint(5) unsigned NOT NULL AUTO_INCREMENT,
					  CNT_ISO varchar(2) COLLATE utf8_bin NOT NULL,
					  STA_abbrev varchar(6) COLLATE utf8_bin NOT NULL,
					  STA_name varchar(100) COLLATE utf8_bin NOT NULL,
					  STA_active tinyint(1) DEFAULT '1',
					  PRIMARY KEY  (STA_ID)";
		EEH_Activation::create_table($table_name, $sql, 'ENGINE=InnoDB' );



		$table_name = 'esp_status';
		$sql = "STS_ID varchar(3) COLLATE utf8_bin NOT NULL,
					  STS_code varchar(45) COLLATE utf8_bin NOT NULL,
					  STS_type set('event','registration','transaction','payment','email') COLLATE utf8_bin NOT NULL,
					  STS_can_edit tinyint(1) NOT NULL DEFAULT 0,
					  STS_desc tinytext COLLATE utf8_bin,
					  STS_open tinyint(1) NOT NULL DEFAULT 1,
					  UNIQUE KEY STS_ID_UNIQUE (STS_ID),
					  KEY STS_type (STS_type)";
		EEH_Activation::create_table($table_name, $sql, 'ENGINE=InnoDB' );



		$table_name = 'esp_transaction';
		$sql = "TXN_ID int(10) unsigned NOT NULL AUTO_INCREMENT,
					  TXN_timestamp datetime NOT NULL default '0000-00-00 00:00:00',
					  TXN_total decimal(10,3) DEFAULT '0.00',
					  TXN_paid decimal(10,3) NOT NULL DEFAULT '0.00',
					  STS_ID varchar(3) NOT NULL DEFAULT 'TOP',
					  TXN_details text COLLATE utf8_bin,
					  TXN_tax_data text COLLATE utf8_bin,
					  TXN_session_data text COLLATE utf8_bin,
					  TXN_hash_salt varchar(250) COLLATE utf8_bin DEFAULT NULL,
					  PRIMARY KEY  (TXN_ID),
					  KEY TXN_timestamp (TXN_timestamp),
					  KEY STS_ID (STS_ID)";
		EEH_Activation::create_table($table_name, $sql, 'ENGINE=InnoDB');


		
		
		$table_name = 'esp_status';
		$sql = "STS_ID varchar(3) COLLATE utf8_bin NOT NULL,
					  STS_code varchar(45) COLLATE utf8_bin NOT NULL,
					  STS_type set('event','registration','transaction','payment','email') COLLATE utf8_bin NOT NULL,
					  STS_can_edit tinyint(1) NOT NULL DEFAULT 0,
					  STS_desc tinytext COLLATE utf8_bin,
					  STS_open tinyint(1) NOT NULL DEFAULT 1,
					  UNIQUE KEY STS_ID_UNIQUE (STS_ID),
					  KEY STS_type (STS_type)";
		EEH_Activation::create_table($table_name, $sql, 'ENGINE=InnoDB' );



		$table_name = 'esp_venue_meta';
		$sql = "VNUM_ID int(11) NOT NULL AUTO_INCREMENT,
			VNU_ID int(11) DEFAULT NULL,
			VNU_address varchar(100) DEFAULT NULL,
			VNU_address2 varchar(100) DEFAULT NULL,
			VNU_city varchar(100) DEFAULT NULL,
			STA_ID int(11) DEFAULT NULL,
			CNT_ISO varchar(2) DEFAULT NULL,
			VNU_zip varchar(45) DEFAULT NULL,
			VNU_phone varchar(45) DEFAULT NULL,
			VNU_capacity int(11) DEFAULT NULL,
			VNU_url varchar(255) DEFAULT NULL,
			VNU_virtual_phone varchar(45) DEFAULT NULL,
			VNU_virtual_url varchar(255) DEFAULT NULL,
			VNU_enable_for_gmap tinyint(1) DEFAULT '0',
			VNU_google_map_link varchar(255) DEFAULT NULL,
			PRIMARY KEY  (VNUM_ID),
			KEY STA_ID (STA_ID),
			KEY CNT_ISO (CNT_ISO)";
		EEH_Activation::create_table($table_name, $sql, 'ENGINE=InnoDB');	
		
		//setting up the default stats and countries is also essential for the data migrations to run
		//(because many need to convert old string states to foreign keys into the states table)
		$this->insert_default_states();
		$this->insert_default_countries();
		//setting up default prices, price types, and tickets is also essential for the price migrations
		$this->insert_default_price_types();
		$this->insert_default_prices();
		$this->insert_default_tickets();
		
		//setting up the config wp option pretty well counts as a 'schema change', or at least should happen ehre
		EE_Config::instance()->update_espresso_config(false, true);
		return true;
	}
	public function schema_changes_after_migration() {
		return true;
	}
	
	/**
	 * insert_default_states
	 *
	 * 	@access public
	 * 	@static
	 * 	@return void
	 */
	private function insert_default_states() {
		
		global $wpdb;
		$state_table = $wpdb->prefix."esp_state";
		if ( $wpdb->get_var( "SHOW TABLES LIKE '" . $state_table . "'") == $state_table ) {
			
			$SQL = "SELECT COUNT('STA_ID') FROM " . $state_table;
			$states = $wpdb->get_var($SQL);
			if ( ! $states ) {
				$SQL = "INSERT INTO " . $state_table . " 
				(STA_ID, CNT_ISO, STA_abbrev, STA_name, STA_active) VALUES
				(1, 'US', 'AK', 'Alaska', 1),
				(2, 'US', 'AL', 'Alabama', 1),
				(3, 'US', 'AS', 'American Samoa', 1),
				(4, 'US', 'AZ', 'Arizona', 1),
				(5, 'US', 'AR', 'Arkansas', 1),
				(6, 'US', 'CA', 'California', 1),
				(7, 'US', 'CO', 'Colorado', 1),
				(8, 'US', 'CT', 'Connecticut', 1),
				(9, 'US', 'DE', 'Delaware', 1),
				(10, 'US', 'DC', 'District of Columbia', 1),
				(11, 'US', 'FM', 'Federated States of Micronesia', 1),
				(12, 'US', 'FL', 'Florida', 1),
				(13, 'US', 'GA', 'Georgia', 1),
				(14, 'US', 'GU', 'Guam', 1),
				(15, 'US', 'HI', 'Hawaii', 1),
				(16, 'US', 'ID', 'Idaho', 1),
				(17, 'US', 'IL', 'Illinois', 1),
				(18, 'US', 'IN', 'Indiana', 1),
				(19, 'US', 'IA', 'Iowa', 1),
				(20, 'US', 'KS', 'Kansas', 1),
				(21, 'US', 'KY', 'Kentucky', 1),
				(22, 'US', 'LA', 'Louisiana', 1),
				(23, 'US', 'ME', 'Maine', 1),
				(24, 'US', 'MH', 'Marshall Islands', 1),
				(25, 'US', 'MD', 'Maryland', 1),
				(26, 'US', 'MA', 'Massachusetts', 1),
				(27, 'US', 'MI', 'Michigan', 1),
				(28, 'US', 'MN', 'Minnesota', 1),
				(29, 'US', 'MS', 'Mississippi', 1),
				(30, 'US', 'MO', 'Missouri', 1),
				(31, 'US', 'MT', 'Montana', 1),
				(32, 'US', 'NE', 'Nebraska', 1),
				(33, 'US', 'NV', 'Nevada', 1),
				(34, 'US', 'NH', 'New Hampshire', 1),
				(35, 'US', 'NJ', 'New Jersey', 1),
				(36, 'US', 'NM', 'New Mexico', 1),
				(37, 'US', 'NY', 'New York', 1),
				(38, 'US', 'NC', 'North Carolina', 1),
				(39, 'US', 'ND', 'North Dakota', 1),
				(40, 'US', 'MP', 'Northern Mariana Islands', 1),
				(41, 'US', 'OH', 'Ohio', 1),
				(42, 'US', 'OK', 'Oklahoma', 1),
				(43, 'US', 'OR', 'Oregon', 1),
				(44, 'US', 'PW', 'Palau', 1),
				(45, 'US', 'PA', 'Pennsylvania', 1),
				(46, 'US', 'PR', 'Puerto Rico', 1),
				(47, 'US', 'RI', 'Rhode Island', 1),
				(48, 'US', 'SC', 'South Carolina', 1),
				(49, 'US', 'SD', 'South Dakota', 1),
				(50, 'US', 'TN', 'Tennessee', 1),
				(51, 'US', 'TX', 'Texas', 1),
				(52, 'US', 'UT', 'Utah', 1),
				(53, 'US', 'VT', 'Vermont', 1),
				(54, 'US', 'VI', 'Virgin Islands', 1),
				(55, 'US', 'VA', 'Virginia', 1),
				(56, 'US', 'WA', 'Washington', 1),
				(57, 'US', 'WV', 'West Virginia', 1),
				(58, 'US', 'WI', 'Wisconsin', 1),
				(59, 'US', 'WY', 'Wyoming', 1),
				(60, 'CA', 'AB', 'Alberta', 1),
				(61, 'CA', 'BC', 'British Columbia', 1),
				(62, 'CA', 'MB', 'Manitoba', 1),
				(63, 'CA', 'NB', 'New Brunswick', 1),
				(64, 'CA', 'NL', 'Newfoundland and Labrador', 1),
				(65, 'CA', 'NS', 'Nova Scotia', 1),
				(66, 'CA', 'ON', 'Ontario', 1),
				(67, 'CA', 'PE', 'Prince Edward Island', 1),
				(68, 'CA', 'QC', 'Quebec', 1),
				(69, 'CA', 'SK', 'Saskatchewan', 1);";
				$wpdb->query($SQL);		
			}
		}
	}
	
	/**
	 * insert_default_countries
	 *
	 * 	@access public
	 * 	@static
	 * 	@return void
	 */
	private function insert_default_countries() {

		global $wpdb;
		$country_table = $wpdb->prefix."esp_country";
		if ( $wpdb->get_var( "SHOW TABLES LIKE '" . $country_table . "'") == $country_table ) {
			
			$SQL = "SELECT COUNT('CNT_ISO') FROM " . $country_table;
			$countries = $wpdb->get_var($SQL);
			if ( ! $countries ) {
				$SQL = "INSERT INTO " . $country_table . " 
				(CNT_ISO, CNT_ISO3, RGN_ID, CNT_name, CNT_cur_code, CNT_cur_single, CNT_cur_plural, CNT_cur_sign, CNT_cur_sign_b4, CNT_cur_dec_plc, CNT_tel_code, CNT_is_EU, CNT_active) VALUES
				('AD', 'AND', 0, 'Andorra', 'EUR', 'Euro', 'Euros', '&#8364;', 1, 2, '+376', 0, 0),
				('AE', 'ARE', 0, 'United Arab Emirates', 'AED', 'Dirham', 'Dirhams', '&#1583;.&#1573;', 1, 2, '+971', 0, 0),
				('AF', 'AFG', 0, 'Afghanistan', 'AFN', 'Afghani', 'Afghanis', '&#1547;', 1, 2, '+93', 0, 0),
				('AG', 'ATG', 0, 'Antigua and Barbuda', 'XCD', 'Dollar', 'Dollars', '&#36;', 1, 2, '+1-268', 0, 0),
				('AI', 'AIA', 0, 'Anguilla', 'XCD', 'Dollar', 'Dollars', '&#36;', 1, 2, '+1-264', 0, 0),
				('AL', 'ALB', 0, 'Albania', 'ALL', 'Lek', 'Leks', '&#76;&#101;&#107;', 1, 2, '+355', 0, 0),
				('AM', 'ARM', 0, 'Armenia', 'AMD', 'Dram', 'Dram', '&#1332;&#1408;&#1377;&#1396;', 1, 2, '+374', 0, 0),
				('AN', 'ANT', 0, 'Netherlands Antilles', 'ANG', 'Guilder', 'Guilders', '&#402;', 1, 2, '+599', 0, 0),
				('AO', 'AGO', 0, 'Angola', 'AOA', 'Kwanza', 'Kwanzas', '', 1, 2, '+244', 0, 0),
				('AR', 'ARG', 0, 'Argentina', 'ARS', 'Peso', 'Pesos', '&#36;', 1, 2, '+54', 0, 0),
				('AS', 'ASM', 0, 'American Samoa', 'USD', 'Dollar', 'Dollars', '&#36;', 1, 2, '+1-684', 0, 0),
				('AT', 'AUT', 0, 'Austria', 'EUR', 'Euro', 'Euros', '&#8364;', 1, 2, '+43', 1, 0),
				('AU', 'AUS', 0, 'Australia', 'AUD', 'Dollar', 'Dollars', '&#36;', 1, 2, '+61', 0, 0),
				('AW', 'ABW', 0, 'Aruba', 'AWG', 'Guilder', 'Guilders', '&#402;', 1, 2, '+297', 0, 0),
				('AZ', 'AZE', 0, 'Azerbaijan', 'AMD', 'Dram', 'Dram', '&#1332;&#1408;&#1377;&#1396;', 1, 2, '+374-97', 0, 0),
				('BA', 'BIH', 0, 'Bosnia and Herzegovina', 'BAM', 'Marka', 'Markas', '&#75;&#77;', 1, 2, '+387', 0, 0),
				('BB', 'BRB', 0, 'Barbados', 'BBD', 'Dollar', 'Dollars', '&#36;', 1, 2, '+1-246', 0, 0),
				('BD', 'BGD', 0, 'Bangladesh', 'BDT', 'Taka', 'Takas', '&#2547;', 1, 2, '+880', 0, 0),
				('BE', 'BEL', 0, 'Belgium', 'EUR', 'Euro', 'Euros', '&#8364;', 1, 2, '+32', 1, 0),
				('BF', 'BFA', 0, 'Burkina Faso', 'XOF', 'Franc', 'Francs', '&#8355;', 1, 0, '+226', 0, 0),
				('BG', 'BGR', 0, 'Bulgaria', 'BGN', 'Lev', 'Levs', '&#1083;&#1074;', 1, 2, '+359', 1, 0),
				('BH', 'BHR', 0, 'Bahrain', 'BHD', 'Dinar', 'Dinars', '', 1, 3, '+973', 0, 0),
				('BI', 'BDI', 0, 'Burundi', 'BIF', 'Franc', 'Francs', '&#8355;', 1, 0, '+257', 0, 0),
				('BJ', 'BEN', 0, 'Benin', 'XOF', 'Franc', 'Francs', '&#8355;', 1, 0, '+229', 0, 0),
				('BM', 'BMU', 0, 'Bermuda', 'BMD', 'Dollar', 'Dollars', '&#36;', 1, 2, '+1-441', 0, 0),
				('BN', 'BRN', 0, 'Brunei Darussalam', 'BND', 'Dollar', 'Dollars', '&#36;', 1, 2, '+673', 0, 0),
				('BO', 'BOL', 0, 'Bolivia', 'BOB', 'Boliviano', 'Bolivianos', '&#36;&#98;', 1, 2, '+591', 0, 0),
				('BR', 'BRA', 0, 'Brazil', 'BRL', 'Real', 'Reals', '&#82;&#36;', 1, 2, '+55', 0, 0),
				('BS', 'BHS', 0, 'Bahamas', 'BSD', 'Dollar', 'Dollars', '&#36;', 1, 2, '+1-242', 0, 0),
				('BT', 'BTN', 0, 'Bhutan', 'BTN', 'Ngultrum', 'Ngultrums', '', 1, 2, '+975', 0, 0),
				('BW', 'BWA', 0, 'Botswana', 'BWP', 'Pula', 'Pulas', '&#80;', 1, 2, '+267', 0, 0),
				('BY', 'BLR', 0, 'Belarus', 'BYR', 'Ruble', 'Rubles', '&#112;&#46;', 1, 0, '+375', 0, 0),
				('BZ', 'BLZ', 0, 'Belize', 'BZD', 'Dollar', 'Dollars', '&#66;&#90;&#36;', 1, 2, '+501', 0, 0),
				('CA', 'CAN', 0, 'Canada', 'CAD', 'Dollar', 'Dollars', '&#36;', 1, 2, '+1', 0, 1),
				('CD', 'COD', 0, 'Congo, the Democratic Republic of the', 'CDF', 'Franc', 'Francs', '&#8355;', 1, 2, '+243', 0, 0),
				('CF', 'CAF', 0, 'Central African Republic', 'XAF', 'Franc', 'Francs', '&#8355;', 1, 0, '+236', 0, 0),
				('CG', 'COG', 0, 'Congo', 'XAF', 'Franc', 'Francs', '&#8355;', 1, 0, '+242', 0, 0),
				('CH', 'CHE', 0, 'Switzerland', 'CHF', 'Franc', 'Francs', '&#8355;', 1, 2, '+41', 0, 0),
				('CI', 'CIV', 0, 'Cote D''Ivoire', 'XOF', 'Franc', 'Francs', '&#8355;', 1, 0, '+225', 0, 0),
				('CK', 'COK', 0, 'Cook Islands', 'NZD', 'Dollar', 'Dollars', '&#36;', 1, 2, '+682', 0, 0),
				('CL', 'CHL', 0, 'Chile', 'CLP', 'Peso', 'Pesos', '&#36;', 1, 0, '+56', 0, 0),
				('CM', 'CMR', 0, 'Cameroon', 'XAF', 'Franc', 'Francs', '&#8355;', 1, 0, '+237', 0, 0),
				('CN', 'CHN', 0, 'China', 'CNY', 'Yuan Renminbi', 'Yuan Renminbis', '&#165;', 1, 2, '+86', 0, 0),
				('CO', 'COL', 0, 'Colombia', 'COP', 'Peso', 'Pesos', '&#36;', 1, 2, '+57', 0, 0),
				('CR', 'CRI', 0, 'Costa Rica', 'CRC', 'Colon', 'Colons', '&#8353;', 1, 2, '+506', 0, 0),
				('CU', 'CUB', 0, 'Cuba', 'CUP', 'Peso', 'Pesos', '&#8369;', 1, 2, '+53', 0, 0),
				('CV', 'CPV', 0, 'Cape Verde', 'CVE', 'Escudo', 'Escudos', '', 1, 2, '+238', 0, 0),
				('CY', 'CYP', 0, 'Cyprus', 'CYP', 'Pound', 'Pounds', '&#163;', 1, 2, '+357', 1, 0),
				('CZ', 'CZE', 0, 'Czech Republic', 'CZK', 'Koruna', 'Korunas', '&#75;&#269;', 1, 2, '+420', 1, 0),
				('DE', 'DEU', 0, 'Germany', 'EUR', 'Euro', 'Euros', '&#8364;', 1, 2, '+49', 1, 0),
				('DJ', 'DJI', 0, 'Djibouti', 'DJF', 'Franc', 'Francs', '&#8355;', 1, 0, '+253', 0, 0),
				('DK', 'DNK', 0, 'Denmark', 'DKK', 'Krone', 'Kroner', '&#107;&#114;', 1, 2, '+45', 1, 0),
				('DM', 'DMA', 0, 'Dominica', 'XCD', 'Dollar', 'Dollars', '&#36;', 1, 2, '+1-767', 0, 0),
				('DO', 'DOM', 0, 'Dominican Republic', 'DOP', 'Peso', 'Pesos', '&#82;&#68;&#36;', 1, 2, '+849', 0, 0),
				('DZ', 'DZA', 0, 'Algeria', 'DZD', 'Dinar', 'Dinars', '', 1, 3, '+213', 0, 0),
				('EC', 'ECU', 0, 'Ecuador', 'USD', 'Dollar', 'Dollars', '&#36;', 1, 2, '+593', 0, 0),
				('EE', 'EST', 0, 'Estonia', 'EEK', 'Kroon', 'Kroons', '&#107;&#114;', 1, 2, '+372', 1, 0),
				('EG', 'EGY', 0, 'Egypt', 'EGP', 'Pound', 'Pounds', '&#163;', 1, 2, '+20', 0, 0),
				('EH', 'ESH', 0, 'Western Sahara', 'MAD', 'Dirham', 'Dirhams', '', 1, 2, '+212', 0, 0),
				('ER', 'ERI', 0, 'Eritrea', 'ERN', 'Nakfa', 'Nakfas', '', 1, 2, '+291', 0, 0),
				('ES', 'ESP', 0, 'Spain', 'EUR', 'Euro', 'Euros', '&#8364;', 1, 2, '+34', 1, 0),
				('ET', 'ETH', 0, 'Ethiopia', 'ETB', 'Birr', 'Birrs', '', 1, 2, '+251', 0, 0),
				('FI', 'FIN', 0, 'Finland', 'EUR', 'Euro', 'Euros', '&#8364;', 1, 2, '+358', 1, 0),
				('FJ', 'FJI', 0, 'Fiji', 'FJD', 'Dollar', 'Dollars', '&#36;', 1, 2, '+679', 0, 0),
				('FK', 'FLK', 0, 'Falkland Islands (Malvinas)', 'FKP', 'Pound', 'Pounds', '&#163;', 1, 2, '+500', 0, 0),
				('FM', 'FSM', 0, 'Micronesia, Federated States of', 'USD', 'Dollar', 'Dollars', '&#36;', 1, 2, '+691', 0, 0),
				('FO', 'FRO', 0, 'Faroe Islands', 'DKK', 'Krone', 'Krones', '&#107;&#114;', 1, 2, '+298', 0, 0),
				('FR', 'FRA', 0, 'France', 'EUR', 'Euro', 'Euros', '&#8364;', 1, 2, '+33', 1, 0),
				('GA', 'GAB', 0, 'Gabon', 'XAF', 'Franc', 'Francs', '&#8355;', 1, 0, '+241', 0, 0),
				('GB', 'GBR', 0, 'United Kingdom', 'GBP', 'Pound', 'Pounds', '&#163;', 1, 2, '+44', 1, 0),
				('GD', 'GRD', 0, 'Grenada', 'XCD', 'Dollar', 'Dollars', '&#36;', 1, 2, '+1-473', 0, 0),
				('GE', 'GEO', 0, 'Georgia', 'RUB', 'Ruble', 'Rubles', '&#1088;&#1091;&#1073;', 1, 2, '+995', 0, 0),
				('GF', 'GUF', 0, 'French Guiana', 'EUR', 'Euro', 'Euros', '&#8364;', 1, 2, '+594', 0, 0),
				('GH', 'GHA', 0, 'Ghana', 'GHS', 'Cedi', 'Cedis', '', 1, 2, '+233', 0, 0),
				('GI', 'GIB', 0, 'Gibraltar', 'GIP', 'Pound', 'Pounds', '&#163;', 1, 2, '+350', 0, 0),
				('GL', 'GRL', 0, 'Greenland', 'DKK', 'Krone', 'Krones', '&#107;&#114;', 1, 2, '+299', 0, 0),
				('GM', 'GMB', 0, 'Gambia', 'GMD', 'Dalasi', 'Dalasis', '', 1, 2, '+220', 0, 0),
				('GN', 'GIN', 0, 'Guinea', 'GNF', 'Franc', 'Francs', '&#8355;', 1, 0, '+224', 0, 0),
				('GP', 'GLP', 0, 'Guadeloupe', 'EUR', 'Euro', 'Euros', '&#8364;', 1, 2, '+590', 0, 0),
				('GQ', 'GNQ', 0, 'Equatorial Guinea', 'XAF', 'Franc', 'Francs', '&#8355;', 1, 0, '+240', 0, 0),
				('GR', 'GRC', 0, 'Greece', 'EUR', 'Euro', 'Euros', '&#8364;', 1, 2, '+30', 1, 0),
				('GT', 'GTM', 0, 'Guatemala', 'GTQ', 'Quetzal', 'Quetzals', '&#81;', 1, 2, '+502', 0, 0),
				('GU', 'GUM', 0, 'Guam', 'USD', 'Dollar', 'Dollars', '&#36;', 1, 2, '+1-671', 0, 0),
				('GW', 'GNB', 0, 'Guinea-Bissau', 'XOF', 'Franc', 'Francs', '&#8355;', 1, 0, '+245', 0, 0),
				('GY', 'GUY', 0, 'Guyana', 'GYD', 'Dollar', 'Dollars', '&#36;', 1, 2, '+592', 0, 0),
				('HK', 'HKG', 0, 'Hong Kong', 'HKD', 'Dollar', 'Dollars', '&#36;', 1, 2, '+852', 0, 0),
				('HN', 'HND', 0, 'Honduras', 'HNL', 'Lempira', 'Lempiras', '&#76;', 1, 2, '+504', 0, 0),
				('HR', 'HRV', 0, 'Croatia', 'HRK', 'Kuna', 'Kunas', '&#107;&#110;', 1, 2, '+385', 0, 0),
				('HT', 'HTI', 0, 'Haiti', 'HTG', 'Gourde', 'Gourdes', '', 1, 2, '+509', 0, 0),
				('HU', 'HUN', 0, 'Hungary', 'HUF', 'Forint', 'Forints', '&#70;&#116;', 1, 2, '+36', 1, 0),
				('ID', 'IDN', 0, 'Indonesia', 'IDR', 'Rupiah', 'Rupiahs', '&#82;&#112;', 1, 2, '+62', 0, 0),
				('IE', 'IRL', 0, 'Ireland', 'EUR', 'Euro', 'Euros', '&#8364;', 1, 2, '+353', 1, 0),
				('IL', 'ISR', 0, 'Israel', 'ILS', 'Shekel', 'Shekels', '&#8362;', 1, 2, '+972', 0, 0),
				('IN', 'IND', 0, 'India', 'INR', 'Rupee', 'Rupees', '&#36;', 1, 2, '+91', 0, 0),
				('IQ', 'IRQ', 0, 'Iraq', 'IQD', 'Dinar', 'Dinars', '&#1583;.&#1593;', 1, 3, '+964', 0, 0),
				('IR', 'IRN', 0, 'Iran, Islamic Republic of', 'IRR', 'Rial', 'Rials', '&#65020;', 1, 2, '+98', 0, 0),
				('IS', 'ISL', 0, 'Iceland', 'ISK', 'Króna', 'krónur', '&#107;&#114;', 1, 0, '+354', 0, 0),
				('IT', 'ITA', 0, 'Italy', 'EUR', 'Euro', 'Euros', '&#8364;', 1, 2, '+39', 1, 0),
				('JM', 'JAM', 0, 'Jamaica', 'JMD', 'Dollar', 'Dollars', '&#74;&#36;', 1, 2, '+1-876', 0, 0),
				('JO', 'JOR', 0, 'Jordan', 'JOD', 'Dinar', 'Dinars', '', 1, 3, '+962', 0, 0),
				('JP', 'JPN', 0, 'Japan', 'JPY', 'Yen', 'Yens', '&#165;', 1, 0, '+81', 0, 0),
				('KE', 'KEN', 0, 'Kenya', 'KES', 'Shilling', 'Shillings', '&#83;', 1, 2, '+254', 0, 0),
				('KG', 'KGZ', 0, 'Kyrgyzstan', 'KGS', 'Som', 'Soms', '&#1083;&#1074;', 1, 2, '+996', 0, 0),
				('KH', 'KHM', 0, 'Cambodia', 'KHR', 'Riels', 'Rielss', '&#6107;', 1, 2, '+855', 0, 0),
				('KI', 'KIR', 0, 'Kiribati', 'AUD', 'Dollar', 'Dollars', '&#36;', 1, 2, '+686', 0, 0),
				('KM', 'COM', 0, 'Comoros', 'KMF', 'Franc', 'Francs', '&#8355;', 1, 0, '+269', 0, 0),
				('KN', 'KNA', 0, 'Saint Kitts and Nevis', 'XCD', 'Dollar', 'Dollars', '&#36;', 1, 2, '+1-869', 0, 0),
				('KP', 'PRK', 0, 'Korea, Democratic People''s Republic of', 'KPW', 'Won', 'Wons', '&#8361;', 1, 2, '+850', 0, 0),
				('KR', 'KOR', 0, 'Korea, Republic of', 'KRW', 'Won', 'Wons', '&#8361;', 1, 0, '+82', 0, 0),
				('KW', 'KWT', 0, 'Kuwait', 'KWD', 'Dinar', 'Dinars', '', 1, 3, '+965', 0, 0),
				('KY', 'CYM', 0, 'Cayman Islands', 'KYD', 'Dollar', 'Dollars', '&#36;', 1, 2, '+1-345', 0, 0),
				('KZ', 'KAZ', 0, 'Kazakhstan', 'KZT', 'Tenge', 'Tenges', '&#1083;&#1074;', 1, 2, '+7', 0, 0),
				('LA', 'LAO', 0, 'Lao People''s Democratic Republic', 'LAK', 'Kip', 'Kips', '&#8365;', 1, 2, '+856', 0, 0),
				('LB', 'LBN', 0, 'Lebanon', 'LBP', 'Pound', 'Pounds', '&#163;', 1, 2, '+961', 0, 0),
				('LC', 'LCA', 0, 'Saint Lucia', 'XCD', 'Dollar', 'Dollars', '&#36;', 1, 2, '+1-758', 0, 0),
				('LI', 'LIE', 0, 'Liechtenstein', 'CHF', 'Franc', 'Francs', '&#8355;', 1, 2, '+423', 0, 0),
				('LK', 'LKA', 0, 'Sri Lanka', 'LKR', 'Rupee', 'Rupees', '&#8360;', 1, 2, '+94', 0, 0),
				('LR', 'LBR', 0, 'Liberia', 'LRD', 'Dollar', 'Dollars', '&#36;', 1, 2, '+231', 0, 0),
				('LS', 'LSO', 0, 'Lesotho', 'LSL', 'Loti', 'Lotis', '', 1, 2, '+266', 0, 0),
				('LT', 'LTU', 0, 'Lithuania', 'LTL', 'Litas', 'Litass', '&#76;&#116;', 1, 2, '+370', 1, 0),
				('LU', 'LUX', 0, 'Luxembourg', 'EUR', 'Euro', 'Euros', '&#8364;', 1, 2, '+352', 1, 0),
				('LV', 'LVA', 0, 'Latvia', 'LVL', 'Lat', 'Lats', '&#76;&#115;', 1, 2, '+371', 1, 0),
				('LY', 'LBY', 0, 'Libyan Arab Jamahiriya', 'LYD', 'Dinar', 'Dinars', '', 1, 3, '+218', 0, 0),
				('MA', 'MAR', 0, 'Morocco', 'MAD', 'Dirham', 'Dirhams', '', 1, 2, '+212', 0, 0),
				('MC', 'MCO', 0, 'Monaco', 'EUR', 'Euro', 'Euros', '&#8364;', 1, 2, '+377', 0, 0),
				('MD', 'MDA', 0, 'Moldova, Republic of', 'MDL', 'Leu', 'Leus', '', 1, 2, '+373', 0, 0),
				('MG', 'MDG', 0, 'Madagascar', 'MGA', 'Ariary', 'Ariarys', '', 1, 2, '+261', 0, 0),
				('MH', 'MHL', 0, 'Marshall Islands', 'USD', 'Dollar', 'Dollars', '&#36;', 1, 2, '+692', 0, 0),
				('MK', 'MKD', 0, 'Macedonia, the Former Yugoslav Republic of', 'MKD', 'Denar', 'Denars', '&#1076;&#1077;&#1085;', 1, 2, '+389', 0, 0),
				('ML', 'MLI', 0, 'Mali', 'XOF', 'Franc', 'Francs', '&#8355;', 1, 0, '+223', 0, 0),
				('MM', 'MMR', 0, 'Myanmar', 'MMK', 'Kyat', 'Kyats', '', 1, 2, '+95', 0, 0),
				('MN', 'MNG', 0, 'Mongolia', 'MNT', 'Tugrik', 'Tugriks', '&#8366;', 1, 2, '+976', 0, 0),
				('MO', 'MAC', 0, 'Macao', 'MOP', 'Pataca', 'Patacas', '', 1, 2, '+853', 0, 0),
				('MP', 'MNP', 0, 'Northern Mariana Islands', 'USD', 'Dollar', 'Dollars', '&#36;', 1, 2, '+1-670', 0, 0),
				('MQ', 'MTQ', 0, 'Martinique', 'EUR', 'Euro', 'Euros', '&#8364;', 1, 2, '+596', 0, 0),
				('MR', 'MRT', 0, 'Mauritania', 'MRO', 'Ouguiya', 'Ouguiyas', '', 1, 2, '+222', 0, 0),
				('MS', 'MSR', 0, 'Montserrat', 'XCD', 'Dollar', 'Dollars', '&#36;', 1, 2, '+1-664', 0, 0),
				('MT', 'MLT', 0, 'Malta', 'MTL', 'Lira', 'Liras', '', 1, 2, '+356', 1, 0),
				('MU', 'MUS', 0, 'Mauritius', 'MUR', 'Rupee', 'Rupees', '&#8360;', 1, 2, '+230', 0, 0),
				('MV', 'MDV', 0, 'Maldives', 'MVR', 'Rufiyaa', 'Rufiyaas', '', 1, 2, '+960', 0, 0),
				('MW', 'MWI', 0, 'Malawi', 'MWK', 'Kwacha', 'Kwachas', '', 1, 2, '+265', 0, 0),
				('MX', 'MEX', 0, 'Mexico', 'MXN', 'Peso', 'Pesos', '&#36;', 1, 2, '+52', 0, 0),
				('MY', 'MYS', 0, 'Malaysia', 'MYR', 'Ringgit', 'Ringgits', '&#82;&#77;', 1, 2, '+60', 0, 0),
				('MZ', 'MOZ', 0, 'Mozambique', 'MZM', 'Meticail', 'Meticails', '', 1, 2, '+258', 0, 0),
				('NA', 'NAM', 0, 'Namibia', 'NAD', 'Dollar', 'Dollars', '&#36;', 1, 2, '+264', 0, 0),
				('NC', 'NCL', 0, 'New Caledonia', 'XPF', 'Franc', 'Francs', '&#8355;', 1, 0, '+687', 0, 0),
				('NE', 'NER', 0, 'Niger', 'XOF', 'Franc', 'Francs', '&#8355;', 1, 0, '+227', 0, 0),
				('NF', 'NFK', 0, 'Norfolk Island', 'AUD', 'Dollar', 'Dollars', '&#36;', 1, 2, '+672', 0, 0),
				('NG', 'NGA', 0, 'Nigeria', 'NGN', 'Naira', 'Nairas', '&#8358;', 1, 2, '+234', 0, 0),
				('NI', 'NIC', 0, 'Nicaragua', 'NIO', 'Cordoba', 'Cordobas', '&#67;&#36;', 1, 2, '+505', 0, 0),
				('NL', 'NLD', 0, 'Netherlands', 'EUR', 'Euro', 'Euros', '&#8364;', 1, 2, '+31', 1, 0),
				('NO', 'NOR', 0, 'Norway', 'NOK', 'Krone', 'Krones', '&#107;&#114;', 1, 2, '+47', 0, 0),
				('NP', 'NPL', 0, 'Nepal', 'NPR', 'Rupee', 'Rupees', '&#8360;', 1, 2, '+977', 0, 0),
				('NR', 'NRU', 0, 'Nauru', 'AUD', 'Dollar', 'Dollars', '&#36;', 1, 2, '+674', 0, 0),
				('NU', 'NIU', 0, 'Niue', 'NZD', 'Dollar', 'Dollars', '&#36;', 1, 2, '+683', 0, 0),
				('NZ', 'NZL', 0, 'New Zealand', 'NZD', 'Dollar', 'Dollars', '&#36;', 1, 2, '+64', 0, 0),
				('OM', 'OMN', 0, 'Oman', 'OMR', 'Rial', 'Rials', '&#65020;', 1, 3, '+968', 0, 0),
				('PA', 'PAN', 0, 'Panama', 'PAB', 'Balboa', 'Balboas', '&#66;&#47;&#46;', 1, 2, '+507', 0, 0),
				('PE', 'PER', 0, 'Peru', 'PEN', 'Sol', 'Sols', '&#83;&#47;&#46;', 1, 2, '+51', 0, 0),
				('PF', 'PYF', 0, 'French Polynesia', 'XPF', 'Franc', 'Francs', '&#8355;', 1, 0, '+689', 0, 0),
				('PG', 'PNG', 0, 'Papua New Guinea', 'PGK', 'Kina', 'Kinas', '', 1, 2, '+675', 0, 0),
				('PH', 'PHL', 0, 'Philippines', 'PHP', 'Peso', 'Pesos', '&#8369;', 1, 2, '+63', 0, 0),
				('PK', 'PAK', 0, 'Pakistan', 'PKR', 'Rupee', 'Rupees', '&#8360;', 1, 2, '+92', 0, 0),
				('PL', 'POL', 0, 'Poland', 'PLN', 'Zloty', 'Zlotys', '&#122;&#322;', 1, 2, '+48', 1, 0),
				('PM', 'SPM', 0, 'Saint Pierre and Miquelon', 'EUR', 'Euro', 'Euros', '&#8364;', 1, 2, '+508', 0, 0),
				('PN', 'PCN', 0, 'Pitcairn', 'NZD', 'Dollar', 'Dollars', '&#36;', 1, 2, '', 0, 0),
				('PR', 'PRI', 0, 'Puerto Rico', 'USD', 'Dollar', 'Dollars', '&#36;', 1, 2, '+1', 0, 0),
				('PT', 'PRT', 0, 'Portugal', 'EUR', 'Euro', 'Euros', '&#8364;', 1, 2, '+351', 1, 0),
				('PW', 'PLW', 0, 'Palau', 'USD', 'Dollar', 'Dollars', '&#36;', 1, 2, '+680', 0, 0),
				('PY', 'PRY', 0, 'Paraguay', 'PYG', 'Guarani', 'Guaranis', '&#71;&#115;', 1, 0, '+595', 0, 0),
				('QA', 'QAT', 0, 'Qatar', 'QAR', 'Rial', 'Rials', '&#65020;', 1, 2, '+974', 0, 0),
				('RE', 'REU', 0, 'Reunion', 'EUR', 'Euro', 'Euros', '&#8364;', 1, 2, '+262', 0, 0),
				('RO', 'ROM', 0, 'Romania', 'RON', 'Leu', 'Leus', '&#108;&#101;&#105;', 1, 2, '+40', 1, 0),
				('RU', 'RUS', 0, 'Russian Federation', 'RUB', 'Ruble', 'Rubles', '&#1088;&#1091;&#1073;', 1, 2, '+7', 0, 0),
				('RW', 'RWA', 0, 'Rwanda', 'RWF', 'Franc', 'Francs', '&#8355;', 1, 0, '+250', 0, 0),
				('SA', 'SAU', 0, 'Saudi Arabia', 'SAR', 'Rial', 'Rials', '&#65020;', 1, 2, '+966', 0, 0),
				('SB', 'SLB', 0, 'Solomon Islands', 'SBD', 'Dollar', 'Dollars', '&#36;', 1, 2, '+677', 0, 0),
				('SC', 'SYC', 0, 'Seychelles', 'SCR', 'Rupee', 'Rupees', '&#8360;', 1, 2, '+248', 0, 0),
				('SD', 'SDN', 0, 'Sudan', 'SDG', 'Pound', 'Pounds', '', 1, 2, '+249', 0, 0),
				('SE', 'SWE', 0, 'Sweden', 'SEK', 'Krona', 'Kronor', '&#107;&#114;', 1, 2, '+46', 1, 0),
				('SG', 'SGP', 0, 'Singapore', 'SGD', 'Dollar', 'Dollars', '&#36;', 1, 2, '+65', 0, 0),
				('SH', 'SHN', 0, 'Saint Helena', 'SHP', 'Pound', 'Pounds', '&#163;', 1, 2, '+290', 0, 0),
				('SI', 'SVN', 0, 'Slovenia', 'EUR', 'Euro', 'Euros', '&#8364;', 1, 2, '+386', 1, 0),
				('SJ', 'SJM', 0, 'Svalbard and Jan Mayen', 'NOK', 'Krone', 'Krones', '&#107;&#114;', 1, 2, '+47', 0, 0),
				('SK', 'SVK', 0, 'Slovakia', 'SKK', 'Koruna', 'Korunas', '', 1, 2, '+421', 1, 0),
				('SL', 'SLE', 0, 'Sierra Leone', 'SLL', 'Leone', 'Leones', '', 1, 2, '+232', 0, 0),
				('SM', 'SMR', 0, 'San Marino', 'EUR', 'Euro', 'Euros', '&#8364;', 1, 2, '+378', 0, 0),
				('SN', 'SEN', 0, 'Senegal', 'XOF', 'Franc', 'Francs', '&#8355;', 1, 0, '+221', 0, 0),
				('SO', 'SOM', 0, 'Somalia', 'SOS', 'Shilling', 'Shillings', '&#83;', 1, 2, '+252', 0, 0),
				('SR', 'SUR', 0, 'Suriname', 'SRD', 'Dollar', 'Dollars', '&#36;', 1, 2, '+597', 0, 0),
				('ST', 'STP', 0, 'Sao Tome and Principe', 'STD', 'Dobra', 'Dobras', '', 1, 2, '+239', 0, 0),
				('SV', 'SLV', 0, 'El Salvador', 'USD', 'Dollar', 'Dollars', '&#36;', 1, 2, '+503', 0, 0),
				('SY', 'SYR', 0, 'Syrian Arab Republic', 'SYP', 'Pound', 'Pounds', '&#163;', 1, 2, '+963', 0, 0),
				('SZ', 'SWZ', 0, 'Swaziland', 'SZL', 'Lilangeni', 'Lilangenis', '', 1, 2, '+268', 0, 0),
				('TC', 'TCA', 0, 'Turks and Caicos Islands', 'USD', 'Dollar', 'Dollars', '&#36;', 1, 2, '+1-649', 0, 0),
				('TD', 'TCD', 0, 'Chad', 'XAF', 'Franc', 'Francs', '&#8355;', 1, 0, '+235', 0, 0),
				('TG', 'TGO', 0, 'Togo', 'XOF', 'Franc', 'Francs', '&#8355;', 1, 0, '+228', 0, 0),
				('TH', 'THA', 0, 'Thailand', 'THB', 'Baht', 'Bahts', '&#3647;', 1, 2, '+66', 0, 0),
				('TJ', 'TJK', 0, 'Tajikistan', 'TJS', 'Somoni', 'Somonis', '', 1, 2, '+992', 0, 0),
				('TK', 'TKL', 0, 'Tokelau', 'NZD', 'Dollar', 'Dollars', '&#36;', 1, 2, '+690', 0, 0),
				('TM', 'TKM', 0, 'Turkmenistan', 'TMM', 'Manat', 'Manats', '', 1, 2, '+993', 0, 0),
				('TN', 'TUN', 0, 'Tunisia', 'TND', 'Dinar', 'Dinars', '', 1, 3, '+216', 0, 0),
				('TO', 'TON', 0, 'Tonga', 'TOP', 'Pa''anga', 'Pa''angas', '', 1, 2, '+676', 0, 0),
				('TR', 'TUR', 0, 'Turkey', 'TRY', 'Lira', 'Liras', '&#36;', 1, 2, '+90', 0, 0),
				('TT', 'TTO', 0, 'Trinidad and Tobago', 'TTD', 'Dollar', 'Dollars', '&#84;&#84;&#36;', 1, 2, '+1-868', 0, 0),
				('TV', 'TUV', 0, 'Tuvalu', 'AUD', 'Dollar', 'Dollars', '&#36;', 1, 2, '+688', 0, 0),
				('TW', 'TWN', 0, 'Taiwan, Province of China', 'TWD', 'Dollar', 'Dollars', '&#78;&#84;&#36;', 1, 2, '+886', 0, 0),
				('TZ', 'TZA', 0, 'Tanzania, United Republic of', 'TZS', 'Shilling', 'Shillings', '&#83;', 1, 2, '+255', 0, 0),
				('UA', 'UKR', 0, 'Ukraine', 'UAH', 'Hryvnia', 'Hryvnias', '&#8372;', 1, 2, '+380', 0, 0),
				('UG', 'UGA', 0, 'Uganda', 'UGX', 'Shilling', 'Shillings', '&#83;', 1, 2, '+256', 0, 0),
				('US', 'USA', 0, 'United States', 'USD', 'Dollar', 'Dollars', '&#36;', 1, 2, '+1', 0, 1),
				('UY', 'URY', 0, 'Uruguay', 'UYU', 'Peso', 'Pesos', '&#36;&#85;', 1, 2, '+598', 0, 0),
				('UZ', 'UZB', 0, 'Uzbekistan', 'UZS', 'Som', 'Soms', '&#1083;&#1074;', 1, 2, '+998', 0, 0),
				('VA', 'VAT', 0, 'Holy See (Vatican City State)', 'EUR', 'Euro', 'Euros', '&#8364;', 1, 2, '+379', 0, 0),
				('VC', 'VCT', 0, 'Saint Vincent and the Grenadines', 'XCD', 'Dollar', 'Dollars', '&#36;', 1, 2, '+1-784', 0, 0),
				('VE', 'VEN', 0, 'Venezuela', 'VEB', 'Bolivar', 'Bolivars', '', 1, 2, '+58', 0, 0),
				('VG', 'VGB', 0, 'Virgin Islands, British', 'USD', 'Dollar', 'Dollars', '&#36;', 1, 2, '+1-284', 0, 0),
				('VI', 'VIR', 0, 'Virgin Islands, US', 'USD', 'Dollar', 'Dollars', '&#36;', 1, 2, '+1-340', 0, 0),
				('VN', 'VNM', 0, 'Viet Nam', 'VND', 'Dong', 'Dongs', '&#8363;', 1, 2, '+84', 0, 0),
				('VU', 'VUT', 0, 'Vanuatu', 'VUV', 'Vatu', 'Vatus', '', 1, 0, '+678', 0, 0),
				('WF', 'WLF', 0, 'Wallis and Futuna', 'XPF', 'Franc', 'Francs', '&#8355;', 1, 0, '+681', 0, 0),
				('WS', 'WSM', 0, 'Samoa', 'WST', 'Tala', 'Talas', '', 1, 2, '+685', 0, 0),
				('YE', 'YEM', 0, 'Yemen', 'YER', 'Rial', 'Rials', '&#65020;', 1, 2, '+967', 0, 0),
				('ZA', 'ZAF', 0, 'South Africa', 'ZAR', 'Rand', 'Rands', '&#82;', 1, 2, '+27', 0, 0),
				('ZM', 'ZMB', 0, 'Zambia', 'ZMK', 'Kwacha', 'Kwachas', '', 1, 2, '+260', 0, 0),
				('ZW', 'ZWE', 0, 'Zimbabwe', 'ZWD', 'Dollar', 'Dollars', '&#90;&#36;', 1, 2, '+263', 0, 0);";		
				$wpdb->query($SQL);			
			}
		
		}
		
	}
	
	/**
	 * insert_default_price_types
	 *
	 * 	@access public
	 * 	@static
	 * 	@return void
	 */
	public function insert_default_price_types() {

		global $wpdb;
		$price_type_table = $wpdb->prefix."esp_price_type";

		if ($wpdb->get_var("SHOW TABLES LIKE '$price_type_table'") == $price_type_table) {

			$SQL = 'SELECT COUNT(PRT_ID) FROM ' . $price_type_table;
			$price_types_exist = $wpdb->get_var( $SQL );
			
			if ( ! $price_types_exist ) {
				$SQL = "INSERT INTO $price_type_table ( PRT_ID, PRT_name, PBT_ID, PRT_is_member, PRT_is_percent, PRT_order, PRT_deleted ) VALUES
							(1, '" . __('Base Price', 'event_espresso') . "', 1, 0, 0, 0, 0),
							(2, '" . __('Member % Discount', 'event_espresso') . "', 2, 1, 1, 10, 0),
							(3, '" . __('Member Dollar Discount', 'event_espresso') . "', 2, 1, 0, 10, 0),
							(4, '" . __('Percent Discount', 'event_espresso') . "', 2, 0, 1, 20, 0),
							(5, '" . __('Dollar Discount', 'event_espresso') . "', 2, 0, 0, 30, 0),
							(6, '" . __('Percent Surcharge', 'event_espresso') . "', 3, 0, 1, 40, 0),
							(7, '" . __('Dollar Surcharge', 'event_espresso') . "', 3, 0, 0, 50, 0),
							(8, '" . __('Regional Tax', 'event_espresso') . "', 4, 0, 1, 60, 0),
							(9, '" . __('Federal Tax', 'event_espresso') . "', 4, 0, 1, 70, 0);";
				$SQL = apply_filters( 'FHEE_default_price_types_activation_sql', $SQL );
				$wpdb->query( $SQL );	
			}
		}
	}
	
	/**
	 * insert_default_prices
	 *
	 * 	@access public
	 * 	@static
	 * 	@return void
	 */
	public function insert_default_prices() {

		global $wpdb;
		$price_table = $wpdb->prefix."esp_price";
		
		if ($wpdb->get_var("SHOW TABLES LIKE '$price_table'") == $price_table) {
			
			$SQL = 'SELECT COUNT(PRC_ID) FROM ' .$price_table;
			$prices_exist = $wpdb->get_var( $SQL );
			
			if ( ! $prices_exist ) {
				$SQL = "INSERT INTO $price_table
							(PRC_ID, PRT_ID, PRC_amount, PRC_name, PRC_desc,  PRC_is_default, PRC_overrides, PRC_order, PRC_deleted, PRC_row, PRC_parent ) VALUES
							(1, 1, '0.00', 'Free Admission', 'Default Price for all NEW tickets created. Example content - delete if you want to', 1, NULL, 0, 0, 1, 0),
							(2, 3, '20', 'Members Discount', 'Members receive a 20% discount off of the regular price. Example content - delete if you want to', 1, NULL, 10, 0, 2, 0),
							(3, 4, '10', 'Early Bird Discount', 'Sign up early and receive an additional 10% discount off of the regular price. Example content - delete if you want to', 1, NULL, 20, 0, 3, 0),
							(4, 5, '7.50', 'Service Fee', 'Covers administrative expenses. Example content - delete if you want to', 1, NULL, 30, 0, 4, 0),
							(5, 7, '7.00', 'Local Sales Tax', 'Locally imposed tax. Example content - delete if you want to', 1, NULL, 40, 0, 5, 0),
							(6, 8, '15.00', 'Sales Tax', 'Federally imposed tax. Example content - delete if you want to', 1, NULL, 50, 0, 6, 0);";			
				$SQL = apply_filters( 'FHEE_default_prices_activation_sql', $SQL );
				$wpdb->query($SQL);			
			}
		}	
	}
	
	/**
	 * insert default ticket
	 *
	 * @access public
	 * @static
	 * @return void
	 */
	public function insert_default_tickets() {

		global $wpdb;
		$ticket_table = $wpdb->prefix."esp_ticket";
		if ( $wpdb->get_var("SHOW TABLES LIKE'$ticket_table'") == $ticket_table ) {

			$SQL = 'SELECT COUNT(TKT_ID) FROM ' . $ticket_table;
			$tickets_exist = $wpdb->get_var($SQL);

			if ( ! $tickets_exist ) {
				$SQL = "INSERT INTO $ticket_table
					( TKT_ID, TTM_ID, TKT_name, TKT_description, TKT_qty, TKT_sold, TKT_uses, TKT_min, TKT_max, TKT_price, TKT_start_date, TKT_end_date, TKT_taxable, TKT_order, TKT_row, TKT_is_default, TKT_parent, TKT_deleted ) VALUES
					( 1, 1, '" . __("Free Ticket", "event_espresso") . "', '" . __('You can modify this description', 'event_espresso') . "', 100, 0, 0, 0, -1, 0.00, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, 0, 1, 1, 0, 0);";
				$SQL = apply_filters( 'FHEE_default_tickets_activation_sql', $SQL);
				$wpdb->query($SQL);
			}
		}
		$ticket_price_table = $wpdb->prefix."esp_ticket_price";

		if ( $wpdb->get_var("SHOW TABLES LIKE'$ticket_price_table'") == $ticket_price_table ) {

			$SQL = 'SELECT COUNT(TKP_ID) FROM ' . $ticket_price_table;
			$ticket_prc_exist = $wpdb->get_var($SQL);

			if ( ! $ticket_prc_exist ) {

				$SQL = "INSERT INTO $ticket_price_table
				( TKP_ID, TKT_ID, PRC_ID ) VALUES 
				( 1, 1, 1 )
				";

				$SQL = apply_filters( 'FHEE_default_ticket_price_activation_sql', $SQL);
				$wpdb->query($SQL);
			}
		}
	}
	
	/**
	 * Gets a country entry as an array, or creates one if none is found. Much like EEM_Country::instance()->get_one(), but is independent of
	 * outside code which can change in future versions of EE. Also, $country_name CAN be a 3.1 country ID (int), a 2-letter ISO, 3-letter ISO, or name
	 * @global type $wpdb
	 * @param string $country_name
	 * @return array where keys are columns, values are column values
	 */
	public function get_or_create_country($country_name){
		if( ! $country_name ){
			throw new EE_Error(__("Could not get a country because country name is blank", "event_espresso"));
		}
		global $wpdb;
		$country_table = $wpdb->prefix."esp_country";
		if(is_int($country_name)){
			$country_name = $this->get_iso_from_3_1_country_id($country_name);
		}
		$country = $wpdb->get_row($wpdb->prepare("SELECT * FROM $country_table WHERE 
			CNT_ISO LIKE %s OR
			CNT_ISO3 LIKE %s OR 
			CNT_name LIKE %s LIMIT 1",$country_name,$country_name,$country_name),ARRAY_A);
		if( ! $country ){
			//insert a new one then
			$cols_n_values = array(
				'CNT_ISO'=> $this->_find_available_country_iso(2) ,
				'CNT_ISO3'=> $this->_find_available_country_iso(3),
				'RGN_ID'=>0,
				'CNT_name'=>$country_name,
				'CNT_cur_code'=>'USD',
				'CNT_cur_single'=>'Dollar',
				'CNT_cur_plural'=>'Dollars',
				'CNT_cur_sign'=>'&#36;',
				'CNT_cur_sign_b4'=>true,
				'CNT_cur_dec_plc'=>2,
				'CNT_cur_dec_mrk'=>'.',
				'CNT_cur_thsnds'=>',',
				'CNT_tel_code'=>'+1',
				'CNT_is_EU'=>false,
				'CNT_active'=>true
			);
			$data_types = array(
				'%s',//CNT_ISO
				'%s',//CNT_ISO3
				'%d',//RGN_ID
				'%s',//CNT_name
				'%s',//CNT_cur_code
				'%s',//CNT_cur_single
				'%s',//CNT_cur_plural
				'%s',//CNT_cur_sign
				'%d',//CNT_cur_sign_b4
				'%d',//CNT_cur_dec_plc
				'%s',//CNT_cur_dec_mrk
				'%s',//CNT_cur_thsnds
				'%s',//CNT_tel_code
				'%d',//CNT_is_EU
				'%d',//CNT_active
			);
			$success = $wpdb->insert($country_table,
					$cols_n_values,
					$data_types);
			if( ! $success){
				throw new EE_Error($this->_create_error_message_for_db_insertion('N/A', array('country_id'=>$country_name), $country_table, $cols_n_values, $data_types)); 
			}
			$country = $cols_n_values;
		}
		return $country;
	}
	/**
	 * finds a country iso which hasnt been used yet
	 * @global type $wpdb
	 * @return string
	 */
	private function _find_available_country_iso($num_letters = 2){
		global $wpdb;
		$country_table = $wpdb->prefix."esp_country";
		do{
			$current_iso = strtoupper(wp_generate_password($num_letters, false));
			$country_with_that_iso = $wpdb->get_var($wpdb->prepare("SELECT count(CNT_ISO) FROM ".$country_table." WHERE CNT_ISO=%s",$current_iso));
		}while(intval($country_with_that_iso));
		return $current_iso;
	}
	
	/**
	 * Gets a state entry as an array, or creates one if none is found. Much like EEM_State::instance()->get_one(), but is independent of
	 * outside code which can change in future versions of EE
	 * @global type $wpdb
	 * @param string $state_name
	 * @return array where keys are columns, values are column values
	 */
	public function get_or_create_state($state_name,$country_name){
		if( ! $state_name ){
			throw new EE_Error(__("Could not get-or-create state because no state name was provided", "event_espresso"));
		}
		try{
			$country = $this->get_or_create_country($country_name);
			$country_iso = $country['CNT_ISO'];
		}catch(EE_Error $e){
			$country_iso = $this->get_default_country_iso();
		}
		global $wpdb;
		$state_table = $wpdb->prefix."esp_state";
		$state = $wpdb->get_row($wpdb->prepare("SELECT * FROM $state_table WHERE 
			(STA_abbrev LIKE %s OR
			STA_name LIKE %s) AND
			CNT_ISO LIKE %s LIMIT 1",$state_name,$state_name,$country_iso),ARRAY_A);
		if ( ! $state){
			//insert a new one then
			$cols_n_values = array(
				'CNT_ISO'=>$country_iso,
				'STA_abbrev'=>substr($state_name,0,6),
				'STA_name'=>$state_name,
				'STA_active'=>true
			);
			$data_types = array(
				'%s',//CNT_ISO
				'%s',//STA_abbrev
				'%s',//STA_name
				'%d',//STA_active
			);
			$success = $wpdb->insert($state_table,$cols_n_values,$data_types);		
			if ( ! $success ){
				throw new EE_Error($this->_create_error_message_for_db_insertion('N/A', array('state'=>$state_name,'country_id'=>$country_name), $state_table, $cols_n_values, $data_types));
			}
			$state = $cols_n_values;
			$state['STA_ID'] = $wpdb->insert_id;
		}
		return $state;
	}
	/**
	 * Fixes times like "5:00 PM" into the expected 24-hour format "17:00".
	 * THis is actually just copied from the 3.1 JSON API because it needed to do the exact same thing
	 * @param type $timeString
	 * @return string in the php datetime format: "G:i" (24-hour format hour with leading zeros, a colon, and minutes with leading zeros)
	 */
	public function convertTimeFromAMPM($timeString){
		$matches = array();
		preg_match("~(\\d*):(\\d*)~",$timeString,$matches);
		if( ! $matches || count($matches)<3){
			$hour = '00';
			$minutes = '00';
		}else{
			$hour = intval($matches[1]);
			$minutes = $matches[2];
		}
		if(strpos($timeString, 'PM') || strpos($timeString, 'pm')){
			$hour = intval($hour) + 12;
		}
		$hour = str_pad( "$hour", 2, '0',STR_PAD_LEFT);
		$minutes = str_pad( "$minutes", 2, '0',STR_PAD_LEFT);
		return "$hour:$minutes";
	}
	
	/**
	 * Gets teh ISO3 fora country given its 3.1 country ID.
	 * @param int $country_id
	 * @return string the country's ISO3 code
	 */
	public function get_iso_from_3_1_country_id($country_id){
		$old_countries = array(
			array(64, 'United States', 'US', 'USA', 1),
			array(15, 'Australia', 'AU', 'AUS', 1),
			array(39, 'Canada', 'CA', 'CAN', 1),
			array(171, 'United Kingdom', 'GB', 'GBR', 1),
			array(70, 'France', 'FR', 'FRA', 2),
			array(111, 'Italy', 'IT', 'ITA', 2),
			array(63, 'Spain', 'ES', 'ESP', 2),
			array(1, 'Afghanistan', 'AF', 'AFG', 1),
			array(2, 'Albania', 'AL', 'ALB', 1),
			array(3, 'Germany', 'DE', 'DEU', 2),
			array(198, 'Switzerland', 'CH', 'CHE', 1),
			array(87, 'Netherlands', 'NL', 'NLD', 2),
			array(197, 'Sweden', 'SE', 'SWE', 1),
			array(230, 'Akrotiri and Dhekelia', 'CY', 'CYP', 2),
			array(4, 'Andorra', 'AD', 'AND', 2),
			array(5, 'Angola', 'AO', 'AGO', 1),
			array(6, 'Anguilla', 'AI', 'AIA', 1),
			array(7, 'Antarctica', 'AQ', 'ATA', 1),
			array(8, 'Antigua and Barbuda', 'AG', 'ATG', 1),
			array(10, 'Saudi Arabia', 'SA', 'SAU', 1),
			array(11, 'Algeria', 'DZ', 'DZA', 1),
			array(12, 'Argentina', 'AR', 'ARG', 1),
			array(13, 'Armenia', 'AM', 'ARM', 1),
			array(14, 'Aruba', 'AW', 'ABW', 1),
			array(16, 'Austria', 'AT', 'AUT', 2),
			array(17, 'Azerbaijan', 'AZ', 'AZE', 1),
			array(18, 'Bahamas', 'BS', 'BHS', 1),
			array(19, 'Bahrain', 'BH', 'BHR', 1),
			array(20, 'Bangladesh', 'BD', 'BGD', 1),
			array(21, 'Barbados', 'BB', 'BRB', 1),
			array(22, 'Belgium ', 'BE', 'BEL', 2),
			array(23, 'Belize', 'BZ', 'BLZ', 1),
			array(24, 'Benin', 'BJ', 'BEN', 1),
			array(25, 'Bermudas', 'BM', 'BMU', 1),
			array(26, 'Belarus', 'BY', 'BLR', 1),
			array(27, 'Bolivia', 'BO', 'BOL', 1),
			array(28, 'Bosnia and Herzegovina', 'BA', 'BIH', 1),
			array(29, 'Botswana', 'BW', 'BWA', 1),
			array(96, 'Bouvet Island', 'BV', 'BVT', 1),
			array(30, 'Brazil', 'BR', 'BRA', 1),
			array(31, 'Brunei', 'BN', 'BRN', 1),
			array(32, 'Bulgaria', 'BG', 'BGR', 1),
			array(33, 'Burkina Faso', 'BF', 'BFA', 1),
			array(34, 'Burundi', 'BI', 'BDI', 1),
			array(35, 'Bhutan', 'BT', 'BTN', 1),
			array(36, 'Cape Verde', 'CV', 'CPV', 1),
			array(37, 'Cambodia', 'KH', 'KHM', 1),
			array(38, 'Cameroon', 'CM', 'CMR', 1),
			array(98, 'Cayman Islands', 'KY', 'CYM', 1),
			array(172, 'Central African Republic', 'CF', 'CAF', 1),
			array(40, 'Chad', 'TD', 'TCD', 1),
			array(41, 'Chile', 'CL', 'CHL', 1),
			array(42, 'China', 'CN', 'CHN', 1),
			array(105, 'Christmas Island', 'CX', 'CXR', 1),
			array(43, 'Cyprus', 'CY', 'CYP', 2),
			array(99, 'Cocos Island', 'CC', 'CCK', 1),
			array(100, 'Cook Islands', 'CK', 'COK', 1),
			array(44, 'Colombia', 'CO', 'COL', 1),
			array(45, 'Comoros', 'KM', 'COM', 1),
			array(46, 'Congo', 'CG', 'COG', 1),
			array(47, 'North Korea', 'KP', 'PRK', 1),
			array(50, 'Costa Rica', 'CR', 'CRI', 1),
			array(51, 'Croatia', 'HR', 'HRV', 1),
			array(52, 'Cuba', 'CU', 'CUB', 1),
			array(173, 'Czech Republic', 'CZ', 'CZE', 1),
			array(53, 'Denmark', 'DK', 'DNK', 1),
			array(54, 'Djibouti', 'DJ', 'DJI', 1),
			array(55, 'Dominica', 'DM', 'DMA', 1),
			array(174, 'Dominican Republic', 'DO', 'DOM', 1),
			array(56, 'Ecuador', 'EC', 'ECU', 1),
			array(57, 'Egypt', 'EG', 'EGY', 1),
			array(58, 'El Salvador', 'SV', 'SLV', 1),
			array(60, 'Eritrea', 'ER', 'ERI', 1),
			array(61, 'Slovakia', 'SK', 'SVK', 2),
			array(62, 'Slovenia', 'SI', 'SVN', 2),
			array(65, 'Estonia', 'EE', 'EST', 2),
			array(66, 'Ethiopia', 'ET', 'ETH', 1),
			array(102, 'Faroe islands', 'FO', 'FRO', 1),
			array(103, 'Falkland Islands', 'FK', 'FLK', 1),
			array(67, 'Fiji', 'FJ', 'FJI', 1),
			array(69, 'Finland', 'FI', 'FIN', 2),
			array(71, 'Gabon', 'GA', 'GAB', 1),
			array(72, 'Gambia', 'GM', 'GMB', 1),
			array(73, 'Georgia', 'GE', 'GEO', 1),
			array(74, 'Ghana', 'GH', 'GHA', 1),
			array(75, 'Gibraltar', 'GI', 'GIB', 1),
			array(76, 'Greece', 'GR', 'GRC', 2),
			array(77, 'Grenada', 'GD', 'GRD', 1),
			array(78, 'Greenland', 'GL', 'GRL', 1),
			array(79, 'Guadeloupe', 'GP', 'GLP', 1),
			array(80, 'Guam', 'GU', 'GUM', 1),
			array(81, 'Guatemala', 'GT', 'GTM', 1),
			array(82, 'Guinea', 'GN', 'GIN', 1),
			array(83, 'Equatorial Guinea', 'GQ', 'GNQ', 1),
			array(84, 'Guinea-Bissau', 'GW', 'GNB', 1),
			array(85, 'Guyana', 'GY', 'GUY', 1),
			array(86, 'Haiti', 'HT', 'HTI', 1),
			array(88, 'Honduras', 'HN', 'HND', 1),
			array(89, 'Hong Kong', 'HK', 'HKG', 1),
			array(90, 'Hungary', 'HU', 'HUN', 1),
			array(91, 'India', 'IN', 'IND', 1),
			array(205, 'British Indian Ocean Territory', 'IO', 'IOT', 1),
			array(92, 'Indonesia', 'ID', 'IDN', 1),
			array(93, 'Iraq', 'IQ', 'IRQ', 1),
			array(94, 'Iran', 'IR', 'IRN', 1),
			array(95, 'Ireland', 'IE', 'IRL', 2),
			array(97, 'Iceland', 'IS', 'ISL', 1),
			array(110, 'Israel', 'IL', 'ISR', 1),
			array(49, 'Ivory Coast ', 'CI', 'CIV', 1),
			array(112, 'Jamaica', 'JM', 'JAM', 1),
			array(113, 'Japan', 'JP', 'JPN', 1),
			array(114, 'Jordan', 'JO', 'JOR', 1),
			array(115, 'Kazakhstan', 'KZ', 'KAZ', 1),
			array(116, 'Kenya', 'KE', 'KEN', 1),
			array(117, 'Kyrgyzstan', 'KG', 'KGZ', 1),
			array(118, 'Kiribati', 'KI', 'KIR', 1),
			array(48, 'South Korea', 'KR', 'KOR', 1),
			array(228, 'Kosovo', 'XK', 'XKV', 2), // there is no official ISO code for Kosovo yet (http://geonames.wordpress.com/2010/03/08/xk-country-code-for-kosovo/) so using a temporary country code and a modified 3 character code for ISO code -- this should be updated if/when Kosovo gets its own ISO code
			array(119, 'Kuwait', 'KW', 'KWT', 1),
			array(120, 'Laos', 'LA', 'LAO', 1),
			array(121, 'Latvia', 'LV', 'LVA', 2),
			array(122, 'Lesotho', 'LS', 'LSO', 1),
			array(123, 'Lebanon', 'LB', 'LBN', 1),
			array(124, 'Liberia', 'LR', 'LBR', 1),
			array(125, 'Libya', 'LY', 'LBY', 1),
			array(126, 'Liechtenstein', 'LI', 'LIE', 1),
			array(127, 'Lithuania', 'LT', 'LTU', 2),
			array(128, 'Luxemburg', 'LU', 'LUX', 2),
			array(129, 'Macao', 'MO', 'MAC', 1),
			array(130, 'Macedonia', 'MK', 'MKD', 1),
			array(131, 'Madagascar', 'MG', 'MDG', 1),
			array(132, 'Malaysia', 'MY', 'MYS', 1),
			array(133, 'Malawi', 'MW', 'MWI', 1),
			array(134, 'Maldivas', 'MV', 'MDV', 1),
			array(135, 'Mali', 'ML', 'MLI', 1),
			array(136, 'Malta', 'MT', 'MLT', 2),
			array(101, 'Northern Marianas', 'MP', 'MNP', 1),
			array(137, 'Morocco', 'MA', 'MAR', 1),
			array(104, 'Marshall islands', 'MH', 'MHL', 1),
			array(138, 'Martinique', 'MQ', 'MTQ', 1),
			array(139, 'Mauritius', 'MU', 'MUS', 1),
			array(140, 'Mauritania', 'MR', 'MRT', 1),
			array(141, 'Mayote', 'YT', 'MYT', 2),
			array(142, 'Mexico', 'MX', 'MEX', 1),
			array(143, 'Micronesia', 'FM', 'FSM', 1),
			array(144, 'Moldova', 'MD', 'MDA', 1),
			array(145, 'Monaco', 'MC', 'MCO', 2),
			array(146, 'Mongolia', 'MN', 'MNG', 1),
			array(147, 'Montserrat', 'MS', 'MSR', 1),
			array(227, 'Montenegro', 'ME', 'MNE', 2),
			array(148, 'Mozambique', 'MZ', 'MOZ', 1),
			array(149, 'Myanmar', 'MM', 'MMR', 1),
			array(150, 'Namibia', 'NA', 'NAM', 1),
			array(151, 'Nauru', 'NR', 'NRU', 1),
			array(152, 'Nepal', 'NP', 'NPL', 1),
			array(9, 'Netherlands Antilles', 'AN', 'ANT', 1),
			array(153, 'Nicaragua', 'NI', 'NIC', 1),
			array(154, 'Niger', 'NE', 'NER', 1),
			array(155, 'Nigeria', 'NG', 'NGA', 1),
			array(156, 'Niue', 'NU', 'NIU', 1),
			array(157, 'Norway', 'NO', 'NOR', 1),
			array(158, 'New Caledonia', 'NC', 'NCL', 1),
			array(159, 'New Zealand', 'NZ', 'NZL', 1),
			array(160, 'Oman', 'OM', 'OMN', 1),
			array(161, 'Pakistan', 'PK', 'PAK', 1),
			array(162, 'Palau', 'PW', 'PLW', 1),
			array(163, 'Panama', 'PA', 'PAN', 1),
			array(164, 'Papua New Guinea', 'PG', 'PNG', 1),
			array(165, 'Paraguay', 'PY', 'PRY', 1),
			array(166, 'Peru', 'PE', 'PER', 1),
			array(68, 'Philippines', 'PH', 'PHL', 1),
			array(167, 'Poland', 'PL', 'POL', 1),
			array(168, 'Portugal', 'PT', 'PRT', 2),
			array(169, 'Puerto Rico', 'PR', 'PRI', 1),
			array(170, 'Qatar', 'QA', 'QAT', 1),
			array(176, 'Rwanda', 'RW', 'RWA', 1),
			array(177, 'Romania', 'RO', 'ROM', 2),
			array(178, 'Russia', 'RU', 'RUS', 1),
			array(229, 'Saint Pierre and Miquelon', 'PM', 'SPM', 2),
			array(180, 'Samoa', 'WS', 'WSM', 1),
			array(181, 'American Samoa', 'AS', 'ASM', 1),
			array(183, 'San Marino', 'SM', 'SMR', 2),
			array(184, 'Saint Vincent and the Grenadines', 'VC', 'VCT', 1),
			array(185, 'Saint Helena', 'SH', 'SHN', 1),
			array(186, 'Saint Lucia', 'LC', 'LCA', 1),
			array(188, 'Senegal', 'SN', 'SEN', 1),
			array(189, 'Seychelles', 'SC', 'SYC', 1),
			array(190, 'Sierra Leona', 'SL', 'SLE', 1),
			array(191, 'Singapore', 'SG', 'SGP', 1),
			array(192, 'Syria', 'SY', 'SYR', 1),
			array(193, 'Somalia', 'SO', 'SOM', 1),
			array(194, 'Sri Lanka', 'LK', 'LKA', 1),
			array(195, 'South Africa', 'ZA', 'ZAF', 1),
			array(196, 'Sudan', 'SD', 'SDN', 1),
			array(199, 'Suriname', 'SR', 'SUR', 1),
			array(200, 'Swaziland', 'SZ', 'SWZ', 1),
			array(201, 'Thailand', 'TH', 'THA', 1),
			array(202, 'Taiwan', 'TW', 'TWN', 1),
			array(203, 'Tanzania', 'TZ', 'TZA', 1),
			array(204, 'Tajikistan', 'TJ', 'TJK', 1),
			array(206, 'Timor Oriental', 'TP', 'TMP', 1),
			array(207, 'Togo', 'TG', 'TGO', 1),
			array(208, 'Tokelau', 'TK', 'TKL', 1),
			array(209, 'Tonga', 'TO', 'TON', 1),
			array(210, 'Trinidad and Tobago', 'TT', 'TTO', 1),
			array(211, 'Tunisia', 'TN', 'TUN', 1),
			array(212, 'Turkmenistan', 'TM', 'TKM', 1),
			array(213, 'Turkey', 'TR', 'TUR', 1),
			array(214, 'Tuvalu', 'TV', 'TUV', 1),
			array(215, 'Ukraine', 'UA', 'UKR', 1),
			array(216, 'Uganda', 'UG', 'UGA', 1),
			array(59, 'United Arab Emirates', 'AE', 'ARE', 1),
			array(217, 'Uruguay', 'UY', 'URY', 1),
			array(218, 'Uzbekistan', 'UZ', 'UZB', 1),
			array(219, 'Vanuatu', 'VU', 'VUT', 1),
			array(220, 'Vatican City', 'VA', 'VAT', 2),
			array(221, 'Venezuela', 'VE', 'VEN', 1),
			array(222, 'Vietnam', 'VN', 'VNM', 1),
			array(108, 'Virgin Islands', 'VI', 'VIR', 1),
			array(223, 'Yemen', 'YE', 'YEM', 1),
			array(225, 'Zambia', 'ZM', 'ZMB', 1),
			array(226, 'Zimbabwe', 'ZW', 'ZWE', 1));
		
		$country_iso = 'US';
		foreach($old_countries as $country_array){
			//note: index 0 is the 3.1 country ID
			if($country_array[0] == $country_id){
				//note: index 2 is the ISO
				$country_iso = $country_array[2];
				break;
			}
		}
		return $country_iso;
	}
	
	/**
	 * Gets the ISO3 for the 
	 * @return string
	 */
	public function get_default_country_iso(){
		$old_org_options= get_option('events_organization_settings');
		$iso = $this->get_iso_from_3_1_country_id($old_org_options['organization_country']);
		return $iso;
	}
	
	/**
	 * Converst a 3.1 payment status to its equivalent 4.1 regisration status
	 * @param string $payment_status possible value for 3.1's evens_attendee.payment_statsu
	 * @return string STS_ID for use in 4.1
	 */
	public function convert_3_1_payment_status_to_4_1_STS_ID($payment_status){
		$mapping = $default_reg_stati_conversions=array(
		'Completed'=>'RAP',
		''=>'RNA',
		'Incomplete'=>'RNA',
		'Pending'=>'RPN');
		return isset($mapping[$payment_status]) ? $mapping[$payment_status] : 'RNA';
	}
	
	
	
}










