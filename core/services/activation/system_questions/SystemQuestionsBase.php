<?php
namespace EventEspresso\core\services\activation\system_questions;

if ( ! defined( 'EVENT_ESPRESSO_VERSION' ) ) {
	exit( 'No direct script access allowed' );
}



/**
 * Class SystemQuestionsBase
 *
 * Abstract parent class for the classes that provide actual table data for EE System Questions
 * These classes simply pass their data to SystemQuestionTableDataGenerator as required
 *
 * @package       Event Espresso
 * @subpackage    core
 * @author        Brent Christensen
 * @since         $VID:$
 *
 */
abstract class SystemQuestionsBase {

	/**
	 * WP User ID for the table creator
	 *
	 * @var int $wp_user_id
	 */
	private $wp_user_id = 0;

	/**
	 * db table primary key value for this System Question Group
	 *
	 * @var int $qsgID
	 */
	private $qsgID = 0;



	/**
	 * TableDataGenerator constructor.
	 *
	 * @param int $wp_user_id
	 * @throws \Exception
	 */
	public function __construct( $wp_user_id ) {
		$this->setWpUserId( $wp_user_id );
		if ( empty( $this->wp_user_id ) ) {
			throw new \Exception(
				__( 'A valid WP User ID is required in order to generate tables and default data', 'event_espresso' )
			);
		}
	}



	/**
	 * @return int
	 */
	public function wpUserId() {
		return $this->wp_user_id;
	}



	/**
	 * @param int $wp_user_id
	 */
	public function setWpUserId( $wp_user_id ) {
		$this->wp_user_id = absint( $wp_user_id );
	}



	/**
	 * @return int
	 */
	public function qsgID() {
		return $this->qsgID;
	}



	/**
	 * @param int $qsgID
	 */
	public function setQsgID( $qsgID ) {
		$this->qsgID = absint( $qsgID );
	}



	/**
	 * @return int
	 */
	abstract public function tableInsertOrder();



	/**
	 * represents the value for one of the constants on EEM_Question_Group for this System Question Group
	 *
	 * @return int
	 */
	abstract public function getQSGConstant();



	/**
	 * returns an array where the keys are the fields names from the esp_question_group table,
	 * and the values set this question group's details
	 *
	 * @return array
	 */
	abstract public function getQuestionGroupData();



	/**
	 * returns a multi-dimensional array listing all of the system questions that belong to this group
	 * where the outer keys correspond to the QST_system field value for the question
	 * and the inner keys correspond to the QST_system field names from the esp_question table,
	 * and the values set each question's details
	 *
	 * @return array
	 */
	abstract public function getSystemQuestions();



}
// End of file SystemQuestionsBase.php
// Location: /SystemQuestionsBase.php