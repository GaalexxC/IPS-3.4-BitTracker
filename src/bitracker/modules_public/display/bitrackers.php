<?php
/**
 *  devCU Software Development
 *  devCU Btracker 1.0.0 Release
 *  Last Updated: $Date: 2012-06-30 09:01:45 -0500 (Sat, 30 June 2012) $
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

class public_bitracker_display_bitrackers extends ipsCommand
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
		
		$id			= intval( $this->request['id'] );
		
		if( !$id )
		{
			$this->registry->output->showError( $this->lang->words['cannot_find_bitracker'], 108997 );
		}
		
		//-----------------------------------------
		// Get file info
		//-----------------------------------------
		
		$file	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'bitracker_files', 'where' => 'file_id=' . $id ) );
		
		if( !$file['file_id'] )
		{
			$this->registry->output->showError( $this->lang->words['cannot_find_bitracker'], 108996 );
		}
		
		//-----------------------------------------
		// Make sure we can view
		//-----------------------------------------
		
		if( !$this->settings['bit_logallbitracker'] OR ( !$this->memberData['bit_view_bitracker'] && ! ( $this->settings['submitter_view_dl'] && $this->memberData['member_id'] == $file['file_submitter']  ) ) )
		{
			$this->registry->output->showError( $this->lang->words['cannot_view_bitracker'], 108998 );
		}

		//-----------------------------------------
		// Verify we can access
		//-----------------------------------------

		$category = $this->registry->getClass('categories')->cat_lookup[ $file['file_cat'] ];
		
		if( ! in_array( $file['file_cat'], $this->registry->getClass('categories')->member_access['view'] ) )
		{
			if( $category['coptions']['opt_noperm_view'] )
			{
				$this->registry->output->showError( $category['coptions']['opt_noperm_view'], 108995 );
			}
			else
			{
				$this->registry->output->showError( 'no_permitted_categories', 108994 );
			}
		}

		//-----------------------------------------
		// Get data for pagelinks
		//-----------------------------------------

		$st			= intval($this->request['st']);
		$perpage	= 20;
		$count		= $this->DB->buildAndFetch( array( 'select' => 'COUNT(' . $this->DB->buildDistinct('dmid') . ') as total', 'from' => 'bitracker_bitracker', 'where' => 'dfid=' . $id ) );
		
		$pagelinks	= $this->registry->output->generatePagination( array(
																			'totalItems'		=> $count['total'],
																			'itemsPerPage'		=> $perpage,
																			'currentStartValue'	=> $st,
																			'baseUrl'			=> "app=bitracker&amp;module=display&amp;section=bitrackers&amp;id=" . $file['file_id'],
																	)		);
		
		//-----------------------------------------
		// Get distinct member ids who have downloaded
		//-----------------------------------------
		
		$member_ids	= array();
		
		$this->DB->build( array( 'select' => $this->DB->buildDistinct('dmid') . ' as member_id, dtime', 'from' => 'bitracker_bitracker', 'where' => 'dfid=' . $id, 'order' => 'dtime DESC', 'limit' => array( $st, $perpage ) ) );
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
				$member['_last_download'] = $member_dls[ $mid ]['dtime'];
				
				$finalMembers[ $mid ]	= $member;
			}
		}
		
		foreach( $this->registry->getClass('categories')->getNav( $file['file_cat'] ) as $navigation )
		{
			$this->registry->output->addNavigation( $navigation[0], $navigation[1], $navigation[2], 'bitshowcat' );
		}
		
		$this->registry->output->addNavigation( $file['file_name'], 'app=bitracker&amp;showfile='.$file['file_id'], $file['file_name_furl'], 'bitshowfile' );
		$this->registry->output->addNavigation( $this->lang->words['view_all_downloaders'] );

        $this->registry->output->setTitle(  sprintf( $this->lang->words['downloaders_pt'], $file['file_name'] ) . ' - ' . $this->settings['board_name'] );
		$this->registry->output->addContent( $this->registry->output->getTemplate('bitracker_external')->fileDownloaders( $file, $finalMembers, false, $pagelinks ) );
		$this->registry->output->sendOutput();
	}
}