<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.4
 * Regular file full changelog
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

class public_bitracker_display_changelog extends ipsCommand
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
		
		$id			= intval( $this->request['file'] );

		if( !$id )
		{
			$this->registry->output->showError( 'file_not_found', 108788, null, null, 404 );
		}
		
		//-----------------------------------------
		// Get file info
		//-----------------------------------------

		$file	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'bitracker_files', 'where' => 'file_id=' . $id ) );
		
		if( !$file['file_id'] )
		{
			$this->registry->output->showError( 'file_not_found', 108789, null, null, 404 );
		}
		
		//-----------------------------------------
		// Verify we can access
		//-----------------------------------------

		$category = $this->registry->getClass('categories')->cat_lookup[ $file['file_cat'] ];
		
		if( ! in_array( $file['file_cat'], $this->registry->getClass('categories')->member_access['view'] ) )
		{
			if( $category['coptions']['opt_noperm_view'] )
			{
				$this->registry->output->showError( 'file_not_found', 108790, null, null, 403 );
			}
			else
			{
				$this->registry->output->showError( 'file_not_found', 108791, null, null, 403 );
			}
		}
		
		$canapp		= $this->registry->getClass('bitFunctions')->checkPerms( $file );
		
		if( !$file['file_open'] )
		{
			if( !$canapp AND $this->memberData['member_id'] != $file['file_submitter'] )
			{
				$this->registry->output->showError( 'file_not_found', 108791.2, null, null, 403 );
			}
		}

		//-----------------------------------------
		// Get changelogs from previous versions
		//-----------------------------------------
		
		$versions	= array();

		$this->DB->build( array( 'select' => '*', 'from' => 'bitracker_filebackup', 'where' => 'b_fileid=' . $id, 'order' => 'b_backup DESC' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$versions[]	= $r;
		}
		
		foreach( $this->registry->getClass('categories')->getNav( $file['file_cat'] ) as $navigation )
		{
			$this->registry->output->addNavigation( $navigation[0], $navigation[1], $navigation[2], 'bitshowcat' );
		}
		
		$this->registry->output->addNavigation( $file['file_name'], 'app=bitracker&amp;showfile='.$file['file_id'], $file['file_name_furl'], 'bitshowfile' );
		$this->registry->output->addNavigation( $this->lang->words['changelog_ucfirst'] );

        $this->registry->output->setTitle(  sprintf( $this->lang->words['changelog_pt'], $file['file_name'] ) . ' - ' . $this->settings['board_name'] );
		$this->registry->output->addContent( $this->registry->output->getTemplate('bitracker_external')->fileChanges( $file, $versions ) );
		$this->registry->output->sendOutput();
	}
}