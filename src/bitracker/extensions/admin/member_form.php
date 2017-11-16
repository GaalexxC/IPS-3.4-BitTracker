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

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class admin_member_form__bitracker implements admin_member_form
{	
	/**
	 * Tab name
	 *
	 * @access	public
	 * @var		string		Tab name
	 */
	public $tab_name = "";

	
	/**
	 * Returns sidebar links for this tab
	 *
	 * @access	public
	 * @author	Brandon Farber
	 * @param    array 			Member data
	 * @return   array 			Array of links
	 */
	public function getSidebarLinks( $member=array() )
	{
		return array();
	}

	/**
	* Returns content for the page.
	*
	* @access	public
	* @author	Matt Mecham
	* @param    array 				Member data
	* @return   array 				Array of tabs, content
	*/
	public function getDisplayContent( $member=array(), $tabsUsed = 5 )
	{

	   /* require_once( IPSLib::getAppDir( 'bitracker' ) . '/sources/classes/tracker_functions.php' ); */ /*noLibHook*/

		/* INIT */
		
		$bitracker	= array();		
		
		/* Load skin */
		
		$html = ipsRegistry::getClass('output')->loadTemplate( 'cp_skin_membersview', 'bitracker' );

		/* Load lang */
				
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_bitracker' ), 'bitracker' );
		
		/* Get member data */
		
		$member = IPSMember::load( $member['member_id'], 'core' );
				
		/* Count uploaded files */
		
		$scnt = ipsRegistry::DB()->buildAndFetch( array( 'select' => 'COUNT(*) as total', 'from' => 'bitracker_files', 'where' => 'file_submitter = ' . $member['member_id'] ) );

		$member['tor_counter'] = $scnt['total'];

		/* Tally Ratio */
		
		$ucnt = ipsRegistry::DB()->buildAndFetch( array( 'select' => 'upload_total as total', 'from' => 'members', 'where' => 'member_id = ' . $member['member_id'] ) );

		$member['upl_counter'] = IPSLib::sizeFormat( $ucnt['total'] ? $ucnt['total'] : 0 );

		$dcnt = ipsRegistry::DB()->buildAndFetch( array( 'select' => 'download_total as total', 'from' => 'members', 'where' => 'member_id = ' . $member['member_id'] ) );

		$member['dow_counter'] = IPSLib::sizeFormat( $dcnt['total'] ? $dcnt['total'] : 0 );

        $member['rat_counter'] 	=  number_format($ucnt['total'] / $dcnt['total'], 2);

		/* Get member and torrents */
		
		$bt = ipsRegistry::DB()->build( array(
											  'select' => 'p.*',
											  'from'   => array( 'bitracker_torrent_peers' => 'p' ),
											  'where'  => 'p.mem_id = ' . $member['member_id'],
								'add_join'  => array(
													0 => array(
								                            'select'	=> 'f.file_id, f.file_name',
								                            'from'		=> array( 'bitracker_files' => 'f' ),
								                            'where'  => 'p.torrent=f.file_id',
															'type'   => 'left',
				
														),												
												),	
	
								'order'		=> 	'f.file_submitted DESC',																	
		)	);
		
		ipsRegistry::DB()->execute( $bt );

		/* Do we have any users? */
		
		if ( ipsRegistry::DB()->getTotalRows() )
		{
			while( $t = ipsRegistry::DB()->fetch( $bt ) )
			{			
				
				if( $t['seeder'] == 'yes' )
				{

				/* Show the shit */
				
				$t['t_name'] = $t['file_name'];	
				$t['t_submit_date'] = ipsRegistry::getClass('class_localization')->getDate( $t['started'], 'LONG' );						
				$t['t_uploaded'] = IPSLib::sizeFormat( $t['uploaded'] ? $t['uploaded'] : 0 );
				$t['t_downloaded'] = IPSLib::sizeFormat( $t['downloaded'] ? $t['downloaded'] : 0 );	
				$t['t_ratio'] = number_format($t['uploaded'] / $t['downloaded'], 2);

				$t['t_seeds'] = $t['uploaded'];	
				$t['t_leeches'] = $t['uploaded'];				

				/* Store it */				
				
				$bitracker[] = $t;				
			}
		}
}

		/* Show... */

		return array( 'tabs' => $html->acp_member_form_tabs( $member, ( $tabsUsed + 1 ) ), 'content' => $html->acp_member_form_main( $member, $bitracker, ( $tabsUsed + 1 ) ) );
	}

	/**
	* Process the entries for saving and return
	*
	* @access	public
	* @author	Brandon Farber
	* @return   array 				Multi-dimensional array (core, extendedProfile) for saving
	*/
	public function getForSave()
	{
		$return = array( 'core' => array(), 'extendedProfile' => array() );
		
		$return['core']['up_ban'] 	= intval( ipsRegistry::$request['up_ban'] );
		$return['core']['down_ban'] 	= intval( ipsRegistry::$request['down_ban'] );
		$return['core']['full_ban'] 	= intval( ipsRegistry::$request['full_ban'] );
		
		return $return;
	}
} // End of class