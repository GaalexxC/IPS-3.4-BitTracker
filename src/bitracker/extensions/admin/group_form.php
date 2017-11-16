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
 * @interface	admin_group_form__bitracker
 *
 */
class admin_group_form__bitracker implements admin_group_form
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
	 * @param	array		$group		Group data
	 * @param	integer		$tabsUsed	Number of tabs used so far (your ids should be this +1)
	 * @return	@e array Array of 'tabs' (HTML for the tabs), 'content' (HTML for the content), 'tabsUsed' (number of tabs you have used)
	 */
	public function getDisplayContent( $group=array(), $tabsUsed = 2 )
	{
		//-----------------------------------------
		// Load skin
		//-----------------------------------------
		
		$this->html = ipsRegistry::getClass('output')->loadTemplate('cp_skin_bit_group_form', 'bitracker');
		
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
				
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_bitracker' ), 'bitracker' );

		//-----------------------------------------
		// Show...
		//-----------------------------------------

		return array( 'tabs' => $this->html->acp_group_form_tabs( $group, ( $tabsUsed + 1 ) ), 'content' => $this->html->acp_group_form_main( $group, ( $tabsUsed + 1 ) ), 'tabsUsed' => 1 );
	}
	
	/**
	 * Process the entries for saving and return
	 *
	 * @return	@e array Array of keys => values for saving
	 */
	public function getForSave()
	{
		$return = array(
						'bit_restrictions'	=> serialize( 
							array(
								'enabled'		=> intval( ipsRegistry::$request['enabled'] ),
								'limit_sim'		=> intval( ipsRegistry::$request['limit_sim'] ),
								'min_posts'		=> intval( ipsRegistry::$request['min_posts'] ),
								'posts_per_dl'	=> intval( ipsRegistry::$request['posts_per_dl'] ),
								'daily_bw'		=> intval( ipsRegistry::$request['daily_bw'] ),
								'weekly_bw'		=> intval( ipsRegistry::$request['weekly_bw'] ),
								'monthly_bw'	=> intval( ipsRegistry::$request['monthly_bw'] ),
								'daily_dl'		=> intval( ipsRegistry::$request['daily_dl'] ),
								'weekly_dl'		=> intval( ipsRegistry::$request['weekly_dl'] ),
								'monthly_dl'	=> intval( ipsRegistry::$request['monthly_dl'] ),
							)	
						 ),
						 'bit_add_paid'			=> intval( ipsRegistry::$request['bit_add_paid'] ),
						 'bit_bypass_paid'		=> intval( ipsRegistry::$request['bit_bypass_paid'] ),
						 'bit_report_files'		=> intval( ipsRegistry::$request['bit_report_files'] ),
						 'bit_view_bitracker'	=> intval( ipsRegistry::$request['bit_view_bitracker'] ),
						 'bit_bypass_revision'	=> intval( ipsRegistry::$request['bit_bypass_revision'] ),
						 'bit_throttling'		=> intval( ipsRegistry::$request['bit_throttling'] ) > 0 ? intval( ipsRegistry::$request['bit_throttling'] ) : 0,
						 'bit_wait_period'		=> intval( ipsRegistry::$request['bit_wait_period'] ) > 0 ? intval( ipsRegistry::$request['bit_wait_period'] ) : 0,
						);

		return $return;
	}
}