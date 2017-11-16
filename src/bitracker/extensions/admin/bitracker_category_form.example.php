<?php
/**
 *  devCU Software Development
 *  devCU Bitracker 1.0.0 Release
 *  Last Updated: $Date: 2013-07-11 09:01:45 -0500 (Thurs, 11 July 2013) $
 *
 * @author 		PM
 * @copyright	(c) 2012 devCU Software Development
 * @Web	        http://www.devcu.com
 * @support       support@devcu.com
 * @license		 DCU Public License
 *
 * DevCU Public License DCUPL Rev 21
 * The use of this license is free for all those who choose to program under its guidelines. 
 * The creation, use, and distribution of software under the terms of this license is aimed at protecting the authors work. 
 * The license terms are for the free use and distribution of open source projects. 
 * The author agrees to allow other programmers to modify and improve, while keeping it free to use, the given software with the full knowledge of the original authors copyright.
 * 
 *  The full License is available at devcu.com
 *  http://www.devcu.com/devcu-public-license-dcupl/
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @interface	admin_bitracker_category_form__APPDIRECTORY
 * @brief		bitracker group editing form
 *
 */
class admin_bitracker_category_form__bitracker implements admin_bitracker_category_form
{	
	/**
	 * Tab name (leave it blank to use the default application title)
	 *
	 * @var		$tab_name
	 */
	public $tab_name = "";

	/**
	 * Returns HTML tabs content for the page
	 *
	 * @param	integer		$tabsUsed	Number of tabs used so far (your ids should be this +1)
	 * @return	@e array Array of 'tabs' (HTML for the tabs), 'content' (HTML for the content), 'tabsUsed' (number of tabs you have used)
	 */
	public function getDisplayContent( $category=array(), $tabsUsed = 2 )
	{
		//-----------------------------------------
		// Load skin
		//-----------------------------------------
		
		$this->html = ipsRegistry::getClass('output')->loadTemplate('cp_skin_bit_track_category_form', 'bitracker');
		
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
				
		#ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'LANGUAGE_FILE' ), 'APPDIRECTORY' );

		//-----------------------------------------
		// Show...
		//-----------------------------------------

		return array( 'tabs' => $this->html->acp_bitracker_category_form_tabs( $category, ( $tabsUsed + 1 ) ), 'content' => $this->html->acp_bitracker_category_form_main( $category, ( $tabsUsed + 1 ) ), 'tabsUsed' => 1 );
	}
	
	/**
	 * Process the entries for saving and return
	 *
	 * @return	@e array Array of keys => values for saving
	 */
	public function getForSave()
	{
		return array( 'example_yesno'		=> intval( ipsRegistry::$request['example_yesno'] ),
					  'example_input'		=> trim( ipsRegistry::$request['example_input'] ),
					  'example_textarea'	=> trim( nl2br( ipsRegistry::$request['example_textarea'] ) )
					 );
	}
	
	/**
	 * Post-process the entries for saving
	 *
	 * @param	integer		$categoryId		Category ID
	 * @return	@e void
	 */
	public function postSave( $categoryId )
	{
		// Your code here if you need to run some post process code
	}
}