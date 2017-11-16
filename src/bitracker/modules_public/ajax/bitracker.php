<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.4
 * AJAX view file trackers
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 *
 * @package		IP.bitracker
 *
 * @version		$Revision: 10721 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_bitracker_ajax_trackers extends ipsAjaxCommand
{
	/**
	 * IPS command execution
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$id = intval( $this->request['id'] );
		
		if( !$id )
		{
			$this->returnString( $this->lang->words['cannot_find_bitracker'] );
		}
		
		//-----------------------------------------
		// Get file info
		//-----------------------------------------
		
		$file = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'bitracker_files', 'where' => 'file_id=' . $id ) );
		
		if( !$file['file_id'] )
		{
			$this->returnString( $this->lang->words['cannot_find_bitracker'] );
		}
		
		//-----------------------------------------
		// Make sure we can view
		//-----------------------------------------
		
		if( !$this->settings['bit_logallbitracker'] OR ( !$this->memberData['bit_view_bitracker'] && ! ( $this->settings['submitter_view_dl'] && $this->memberData['member_id'] == $file['file_submitter']  ) ) )
		{
			$this->returnString( $this->lang->words['cannot_view_bitracker'] );
		}
		
		//-----------------------------------------
		// Verify we can access
		//-----------------------------------------

		$category = $this->registry->getClass('categories')->cat_lookup[ $file['file_cat'] ];
		
		if( ! in_array( $file['file_cat'], $this->registry->getClass('categories')->member_access['view'] ) )
		{
			if( $category['coptions']['opt_noperm_view'] )
			{
				$this->returnString( $category['coptions']['opt_noperm_view'] );
			}
			else
			{
				$this->returnString( $this->lang->words['no_permitted_categories'] );
			}
		}

		//-----------------------------------------
		// Get distinct member ids who have tracked
		//-----------------------------------------
		
		$member_ids	= array();
		
		$this->DB->build( array( 'select' => 'dmid as member_id, dtime', 'from' => 'bitracker_bitracker', 'group' => 'dmid', 'where' => 'dfid=' . $id, 'order' => 'MAX(dtime) DESC', 'limit' => array( 0, 20 ) ) );
		
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$member_dls[ $r['member_id'] ]	= $r;
		}
		
		if( count($member_dls) )
		{
			$finalMembers	= array();
			$members		= IPSMember::load( array_keys( $member_dls ), 'all' );
			
			foreach( $members as $mid => $member )
			{
				$member	= IPSMember::buildDisplayData( $member );
				$member['_last_track'] = $member_dls[ $mid ]['dtime'];
				
				$finalMembers[ $mid ]	= $member;
			}
		}
		
		$this->returnHtml( $this->registry->output->getTemplate('bitracker_external')->filetrackers( $file, $finalMembers, true ) );
	}
}