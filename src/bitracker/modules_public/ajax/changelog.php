<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.4
 * AJAX view file full changelog
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

class public_bitracker_ajax_changelog extends ipsAjaxCommand
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
		
		$id	= intval( $this->request['file'] );

		if( !$id )
		{
			$this->returnNull();
		}
		
		//-----------------------------------------
		// Get file info
		//-----------------------------------------

		$file	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'bitracker_files', 'where' => 'file_id=' . $id ) );
		
		if( !$file['file_id'] )
		{
			$this->returnNull();
		}
		
		//-----------------------------------------
		// Verify we can access
		//-----------------------------------------

		$category = $this->registry->getClass('categories')->cat_lookup[ $file['file_cat'] ];
		
		if( ! in_array( $file['file_cat'], $this->registry->getClass('categories')->member_access['view'] ) )
		{
			if( $category['coptions']['opt_noperm_view'] )
			{
				$this->returnNull();
			}
			else
			{
				$this->returnNull();
			}
		}

		$versions	= array();
		
		//-----------------------------------------
		// Get changelogs from previous versions
		//-----------------------------------------

		$this->DB->build( array( 'select' => '*', 'from' => 'bitracker_filebackup', 'where' => 'b_fileid=' . $id, 'order' => 'b_backup DESC' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$versions[]	= $r;
		}
		
		$this->returnHtml( $this->registry->output->getTemplate('bitracker_external')->fileChanges( $file, $versions ) );
	}
}