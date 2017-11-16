<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.4
 * bit moderation library
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 *
 * @package		IP.bitracker
 *
 * @since		6/24/2008
 * @version		$Revision: 10721 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_bitracker_moderate_moderate extends ipsCommand
{
	/**
	 * Stored temporary output
	 *
	 * @var 	string 				Page output
	 */
	protected $output				= "";

	/**
	 * Moderator library
	 *
	 * @var 	object
	 */
	protected $moderateLibrary;

	/**
	 * Message to show on the mod CP
	 *
	 * @var 	string
	 */
	protected $message				= "";

	/**
	 * Main class entry point
	 *
	 * @param	object		ipsRegistry reference
	 * @return	@e void
	 */	
	public function doExecute( ipsRegistry $registry )
	{
		//-------------------------------------------
		// Page title and navigation bar
		//-------------------------------------------
		
		$this->registry->output->addNavigation( IPSLib::getAppTitle('bitracker'), 'app=bitracker', 'false', 'app=bitracker' );

		//-------------------------------------------
		// Do we have access?
		//-------------------------------------------
		
		if( count($this->registry->getClass('categories')->member_access['view']) == 0 )
		{
			$this->registry->output->showError( 'no_bitracker_permissions', 10875, null, null, 403 );
		}

		//-------------------------------------------
		// CSRF protection
		//-------------------------------------------

		if ( $this->request['secure_key'] != $this->member->form_hash )
		{
			$this->registry->output->showError( 'no_permission', 10799, null, null, 403 );
		}
		
		//-------------------------------------------
		// Get our moderator library
		//-------------------------------------------
		
		$classToLoad			= IPSLib::loadLibrary( IPSLib::getAppDir( 'bitracker' ) . '/sources/classes/moderate.php', 'bit_moderate', 'bitracker' );
		$this->moderateLibrary	= new $classToLoad( $this->registry );

		switch( $this->request['do'] )
		{
			case 'togglefile':
				$this->_doToggleFile( );
			break;
			
			case 'pin':
			case 'unpin':
				$this->_doPinFile( );
			break;

			case 'broken':
				$this->_reportBroken( );
			break;
				
			case 'notbroken':
				$this->_reportUnbroken( );
			break;					
			
			case 'delete':
				$this->_doDelete( );
			break;
				
			case 'modaction':
				$this->_doModAction( );
			break;
				
			case 'versions':
				$this->_versionControlGateway();
			break;

			case 'multimod':
				$this->_multiModeration();
			break;
			
			case 'updateAuthor':
				$this->_updateFileAuthor();
			break;

			case 'setFeatured':
				$this->_setFeatured();
			break;
			
			default:
				$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=core&module=modcp&fromapp=bitracker&tab=brokenfiles' );
				break;
		}
		
		//-------------------------------------------
		// Print output
		//-------------------------------------------

        $this->registry->output->setTitle( $this->lang->words['moderate_nav'] . ' - ' . $this->settings['board_name'] );
		$this->registry->output->addContent( $this->output );
		$this->registry->output->sendOutput();
	}
	
	/**
	 * Set or unset featured file
	 *
	 * @return	@e void
	 */
	protected function _setFeatured()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$id		= intval($this->request['id']);
		
		if( !$id )
		{
			$this->registry->output->showError( $this->lang->words['nofile_for_feature'], 108990.2, null, null, 403 );
		}
		
		$file	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'bitracker_files', 'where' => 'file_id=' . $id ) );
		
		if( !$file['file_id'] )
		{
			$this->registry->output->showError( $this->lang->words['nofile_for_feature'], 108989.2, null, null, 403 );
		}
		
		//-----------------------------------------
		// Check permissions
		//-----------------------------------------
		
		if( !$this->registry->getClass('bitFunctions')->checkPerms( $file, 'modusefeature' ) )
		{
			$this->registry->output->showError( $this->lang->words['noperm_for_feature'], 108988.2, null, null, 403 );
		}
		
		//-----------------------------------------
		// Check member details
		//-----------------------------------------
		
		if( !$file['file_featured'] )
		{
			$langbit	= 'featured_file_ok';
			$this->DB->update( "bitracker_files", array( 'file_featured' => 1 ), "file_id=" . $id );
		}
		else
		{
			$langbit	= 'unfeatured_file_ok';
			$this->DB->update( "bitracker_files", array( 'file_featured' => 0 ), "file_id=" . $id );
		}
		
		$this->registry->output->redirectScreen( $this->lang->words[ $langbit ], $this->settings['base_url'] . "app=bitracker&amp;showfile={$id}", $file['file_name_furl'], 'bitshowfile' );
	}
	
	/**
	 * Update owner of a file
	 *
	 * @return	@e void
	 */
	protected function _updateFileAuthor()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$id		= intval($this->request['id']);
		
		if( !$id )
		{
			$this->registry->output->showError( $this->lang->words['nofile_for_authchange'], 108990, null, null, 403 );
		}
		
		$file	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'bitracker_files', 'where' => 'file_id=' . $id ) );
		
		if( !$file['file_id'] )
		{
			$this->registry->output->showError( $this->lang->words['nofile_for_authchange'], 108989, null, null, 403 );
		}
		
		//-----------------------------------------
		// Check permissions
		//-----------------------------------------
		
		if( !$this->registry->getClass('bitFunctions')->checkPerms( $file, 'modchangeauthor' ) )
		{
			$this->registry->output->showError( $this->lang->words['changeauth_perms'], 108988, null, null, 403 );
		}
		
		//-----------------------------------------
		// Check member details
		//-----------------------------------------
		
		$name = trim( strtolower( $this->request['change_author_input'] ) );
		
		$member = $this->DB->buildAndFetch( array( 'select' => 'member_id, members_display_name', 'from' => 'members', 'where' => "members_l_display_name='{$name}'" ) );

		if( $member['member_id'] )
		{
			$this->DB->update( 'bitracker_files', array( 'file_submitter' => $member['member_id'] ), "file_id=" . $id );
			$this->DB->update( 'core_tags', array( 'tag_member_id' => $member['member_id'] ), "tag_meta_app='bitracker' AND tag_meta_area='files' AND tag_meta_id={$id}" );
			
			$this->registry->categories->rebuildFileinfo();
			$this->cache->rebuildCache( 'bit_stats', 'bitracker' );
			$this->cache->rebuildCache( 'bit_cats', 'bitracker' );
			
			//-----------------------------------------
			// Update topic too
			// @link	http://bugs.---.com/tracker/issue-26436-topic-author-is-not-the-same-of-file-when-change-author/
			// @link	http://bugs.---.com/tracker/issue-31954-author-not-updated-in-first-post-of-topic-when-changing-the-file-submitter
			//-----------------------------------------
			
			if( $file['file_topicid'] )
			{
				$file['file_submitter']			= $member['member_id'];
				$file['file_submitter_name']	= $member['members_display_name'];

				$classToLoad		= IPSLib::loadLibrary( IPSLib::getAppDir( 'bitracker' ) . '/sources/classes/topics.php', 'topicsLibrary', 'bitracker' );
				$topicsLibrary		= new $classToLoad( $this->registry );
				$topicsLibrary->sortTopic( $file, $this->registry->getClass('categories')->cat_lookup[ intval($file['file_cat']) ], 'edit' );
				
				$topic	= $this->DB->buildAndFetch( array( 'select' => 'tid, topic_firstpost', 'from' => 'topics', 'where' => 'tid=' . $file['file_topicid'] ) );

				$this->DB->update( 'posts', array( 'author_id' => $member['member_id'], 'author_name' => $member['members_display_name'] ), 'pid=' . $topic['topic_firstpost'] );

				$classToLoad		= IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . '/sources/classes/moderate.php', 'moderatorLibrary', 'forums' );
				$moderatorLibrary	= new $classToLoad( $this->registry );
				$moderatorLibrary->rebuildTopic( $topic['tid'], false );
				
				$moderatorLibrary->forumRecount( $this->registry->getClass('categories')->cat_lookup[ intval($file['file_cat']) ]['coptions']['opt_topicf'] );
			}
		}
		else
		{
			$this->registry->output->showError( $this->lang->words['noauth_for_filechange'], 108987, null, null, 403 );
		}
		
		$this->registry->output->redirectScreen( $this->lang->words['author_changed'], $this->settings['base_url'] . "app=bitracker&amp;showfile={$id}", $file['file_name_furl'], 'bitshowfile' );
	}
	
	/**
	 * Pin a file
	 *
	 * @return	@e void
	 */	
	protected function _doPinFile( )
	{
		$id = intval($this->request['id']);
		
		if( !$id )
		{
			$this->registry->output->showError( 'cannot_find_to_pin', 108760, null, null, 404 );
		}			
		
		$file = $this->DB->buildAndFetch( array( 'select'	=> '*',
														'from'		=> 'bitracker_files',
														'where'		=> 'file_id=' . $id
												)		);
		
		if( !$file['file_id'] )
		{
			$this->registry->output->showError( 'cannot_find_to_pin', 108761, null, null, 404 );
		}

		if( !$this->registry->getClass('bitFunctions')->checkPerms( $file, 'modcanpin' ) )
		{
			$this->registry->output->showError( 'no_permitted_categories', 108762, null, null, 403 );
		}
		
		$to_update = array();
		
		if( !$file['file_pinned'] )
		{
			$this->moderateLibrary->doMultiPin( array( $id => $id ) );

			$text = $this->lang->words['moderate_pinned'];
		}
		else
		{
			$this->moderateLibrary->doMultiUnpin( array( $id => $id ) );

			$text = $this->lang->words['moderate_unpinned'];
		}

		$this->registry->output->redirectScreen( $text, $this->settings['base_url'] . "app=bitracker&amp;showfile={$id}", $file['file_name_furl'], 'bitshowfile' );
	}

	/**
	 * Approve/unapprove
	 *
	 * @return	@e void
	 */	
	protected function _doToggleFile( )
	{
		$id = intval($this->request['id']);
		
		if( !$id )
		{
			$this->registry->output->showError( 'cannot_find_to_toggle', 10876, null, null, 404 );
		}			
		
		$file = $this->DB->buildAndFetch( array( 'select'	=> '*',
														'from'		=> 'bitracker_files',
														'where'		=> 'file_id=' . $id
												)		);
		
		if( !$file['file_id'] )
		{
			$this->registry->output->showError( 'cannot_find_to_toggle', 10877, null, null, 404 );
		}
		
		$cantog	= $this->registry->getClass('bitFunctions')->checkPerms( $file, 'modcanapp' );
		
		if( !$cantog )
		{
			$this->registry->output->showError( 'no_permitted_categories', 10878, null, null, 403 );
		}
		
		$to_update = array();
		
		if( $file['file_open'] == 0 )
		{
			$this->moderateLibrary->doMultiApprove( array( $id => $id ) );

			$text = $this->lang->words['moderate_approve'];
		}
		else
		{
			$this->moderateLibrary->doMultiUnapprove( array( $id => $id ) );

			$text = $this->lang->words['moderate_unapprove'];
		}
		
		$this->registry->getClass('categories')->rebuildFileinfo($file['file_cat']);
		$this->registry->getClass('categories')->rebuildStatsCache();
		
		if( $this->request['return'] == 1 )
		{
			$this->registry->output->redirectScreen( $text, $this->settings['base_url'] . "app=bitracker&amp;showfile={$id}", $file['file_name_furl'], 'bitshowfile' );
		}
		else
		{
			$this->registry->output->redirectScreen( $text, $this->settings['base_url'] . "app=core&amp;module=modcp&amp;fromapp=bitracker&amp;tab=unapprovedfiles" );
		}		
	}
	
	/**
	 * Delete a single file
	 *
	 * @return	@e void
	 */	
	protected function _doDelete( )
	{
		$count = $this->moderateLibrary->doMultiDelete( array( intval($this->request['id']) ) );

		if( !$count )
		{
			$this->registry->output->showError( 'cannot_find_to_del', 10879, null, null, 404 );
		}
		
		if( $this->request['return'] == 1 )
		{
			$this->registry->output->redirectScreen( $this->lang->words['moderate_filedeleted'], $this->settings['base_url'] . "app=bitracker&amp;showcat=" . $this->moderateLibrary->fileCat, $this->registry->getClass('categories')->cat_lookup[ $this->moderateLibrary->fileCat ]['cname_furl'], 'bitshowcat' );
		}
		else
		{
			$this->registry->output->redirectScreen( $this->lang->words['moderate_filedeleted'], $this->settings['base_url'] . "app=core&amp;module=modcp&amp;fromapp=bitracker&amp;tab=" . ( $this->request['type'] == 'broke' ? 'brokenfiles' : 'unapprovedfiles' ) );
		}
	}
	
	/**
	 * File multi-moderation
	 *
	 * @return	@e void
	 */	
	protected function _multiModeration()
	{
		$ids	= IPSLib::cleanIntArray( explode( ',', $this->request['selectedfileids'] ) );
		$cat	= intval($this->request['catid']);
		
		if( !is_array($ids) OR !count($ids) )
		{
			$this->registry->output->showError( 'error_generic', 10880, null, null, 404 );
		}
		
		if( !$cat )
		{
			$this->registry->output->showError( 'error_generic', 10881 );
		}
		
		if( $this->request['doaction'] == 'move' AND !$this->request['moveto'] )
		{
			$categories		= $this->registry->getClass('categories')->catJumpList( 1, 'view' );
			$category_opts	= '';

			if( count($categories) )
			{
				foreach( $categories as $cat )
				{
					if( !$this->registry->getClass('bitFunctions')->checkPerms( array( 'file_cat' => $cat[0] ) ) )
					{
						continue;
					}

					$category_opts .= "<option value='{$cat[0]}'>{$cat[1]}</option>\n";
				}
			}

			$this->output .= $this->registry->getClass('output')->getTemplate('bitracker_submit')->moderateSelectCategory( $category_opts );
			return;
		}
		else if( $this->request['doaction'] == 'move' )
		{
			$category = $this->registry->getClass('categories')->cat_lookup[ intval($this->request['moveto']) ];
			
			if( !$category['cid'] OR !$this->registry->getClass('bitFunctions')->checkPerms( array( 'file_cat' => $category['cid'] ) ) )
			{
				$this->registry->output->showError( 'error_generic', 10882, null, null, 403 );
			}
		}

		$fids 		= array();
		$catWhere	= array_merge( $this->registry->categories->getChildren( $cat ), array( $cat ) );
		
		$this->DB->build( array( 'select' => '*', 'from' => 'bitracker_files', 'where' => 'file_id IN(' . implode( ',', $ids ) . ') AND file_cat IN(' . implode( ',', $catWhere ) . ')' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			switch( $this->request['doaction'] )
			{
				case 'del':
					$canmod = $this->registry->getClass('bitFunctions')->checkPerms( $r, 'modcandel', 'bit_allow_delete' );
				break;
				
				case 'app':
				case 'unapp':
					$canmod = $this->registry->getClass('bitFunctions')->checkPerms( $r, 'modcanapp' );
				break;
				
				case 'move':
					$canmod = $this->registry->getClass('bitFunctions')->checkPerms( $r, 'modcanapp', 'bit_allow_edit' );
				break;
				
				case 'pin':
				case 'unpin':
					$canmod = $this->registry->getClass('bitFunctions')->checkPerms( $r, 'modcanpin' );
				break;
			}
			
			if( $canmod )
			{
				$fids[ $r['file_id'] ] = $r['file_id'];
			}
		}
		
		if( !count($fids) )
		{
			$this->registry->output->showError( 'error_generic', 10883 );
		}
		
		switch( $this->request['doaction'] )
		{
			case 'del':
				$cnt			= $this->moderateLibrary->doMultiDelete( $fids );
				$this->message	.= sprintf( $this->lang->words['modact_message_del'], $cnt );
			break;
			
			case 'app':
				$cnt			= $this->moderateLibrary->doMultiApprove( $fids );
				$this->message	.= sprintf( $this->lang->words['modact_message_app'], $cnt );
			break;
			
			case 'unapp':
				$cnt			= $this->moderateLibrary->doMultiUnapprove( $fids );
				$this->message	.= sprintf( $this->lang->words['modact_message_unapp'], $cnt );
			break;
			
			case 'move':
				$this->moderateLibrary->doMultiMove( $fids, intval($this->request['moveto']) );
				$this->registry->getClass('categories')->rebuildFileinfo( intval($this->request['moveto']) );
			break;
			
			case 'pin':
				$cnt			= $this->moderateLibrary->doMultiPin( $fids );
				$this->message	.= sprintf( $this->lang->words['modact_message_pinned'], $cnt );
			break;
			
			case 'unpin':
				$cnt			= $this->moderateLibrary->doMultiUnpin( $fids );
				$this->message	.= sprintf( $this->lang->words['modact_message_unpinned'], $cnt );
			break;
		}
		
		IPSCookie::set('modfileids', '', 0);

		$this->registry->getClass('categories')->rebuildFileinfo( $cat );
		$this->registry->getClass('categories')->rebuildStatsCache();
		
		$this->registry->output->redirectScreen( $this->lang->words['file_mmod_success'], $this->settings['base_url'] . "app=bitracker&amp;showcat={$cat}", $this->registry->getClass('categories')->cat_lookup[ $cat ]['cname_furl'], 'bitshowcat' );
	}

	/**
	 * Version control gateway
	 *
	 * @return	@e void
	 */	
	protected function _versionControlGateway()
    {
	    $id		= intval($this->request['id']);
	    $vid	= intval($this->request['rid']);

		if( !$id )
		{
			$this->registry->output->showError( 'error_generic', 10887, null, null, 404 );
		}
		
		if( !$vid )
		{
			$this->registry->output->showError( 'error_generic', 10888 );
		}	
	    
	    $file	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'bitracker_files', 'where' => 'file_id=' . $id ) );
	    $ver	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'bitracker_filebackup', 'where' => 'b_fileid=' . $id . ' AND b_id=' . $vid ) );
	    
	    if( !$file['file_id'] OR !$ver['b_id'] )
	    {
	    	$this->registry->output->showError( 'error_generic', 10889, null, null, 404 );
		}		    
		    
		$candelete = $this->registry->getClass('bitFunctions')->checkPerms( $file, 'modcandel', 'bit_allow_delete' );
		
		if( !$candelete )
		{
			$this->registry->output->showError( 'not_your_file', 10890, null, null, 403 );
		}
		
		//-------------------------------------------
		// Permissions check out, manage
		//-------------------------------------------
		
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir('bitracker') . '/sources/classes/versioning.php', 'versioningLibrary', 'bitracker' );
		$versions 		= new $classToLoad( $this->registry );
		$text			= "";
						
		switch( $this->request['process'] )
		{
			case 'restore':
				$versions->restore( $file, $vid );
				$text = 'version_restore_succesful';
			break;

			case 'hide':
				$versions->hide( $file, $vid );
				$text = 'version_hide_succesful';
			break;

			case 'unhide':
				$versions->unhide( $file, $vid );
				$text = 'version_unhide_succesful';
			break;

			case 'delete':
				$versions->remove( $file, $vid, $ver, $file );
				$text = 'version_remove_succesful';
			break;
		}
		
		if( $versions->error )
		{
			$this->registry->output->showError( $versions->error, 10891 );
		}
		else
		{
			$this->registry->output->redirectScreen( $this->lang->words[ $text ], $this->settings['base_url'] . "app=bitracker&amp;showfile=" . $id, $file['file_name_furl'], 'bitshowfile' );
		}
	}    
	
	/**
	 * Perform moderator action
	 *
	 * @return	@e void
	 */	
	protected function _doModAction( )
	{
		$this->message	= "";
		$returnAction	= $this->request['type'] == 'broke' ? 'brokenfiles' : 'unapprovedfiles';

		$ids	= IPSLib::cleanIntArray( $this->request['selectedfileids'] );

		if( !count($ids) )
		{
			$this->registry->output->showError( 'noselectedfiles', 10892.1, null, null, 403 );
		}
		
		$this->DB->build( array( 'select'	=> 'file_id, file_cat',
								 'from'		=> 'bitracker_files',
								 'where'	=> 'file_id IN(' . implode( ',', $ids ) . ')' 
						)		);
		$outer = $this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
 			$canapp		= $this->registry->getClass('bitFunctions')->checkPerms( $r );
			$canbroke	= $this->registry->getClass('bitFunctions')->checkPerms( $r, 'modcanbrok' );

	 		switch( $this->request['type'] )
	 		{
		 		case 'app':
		 			if( !$canapp )
		 			{
		 				$this->registry->output->showError( 'no_permitted_categories', 10892, null, null, 403 );
					}
		 		break;

		 		case 'broke':
		 			if( !$canbroke )
		 			{
		 				$this->registry->output->showError( 'no_permitted_categories', 10893, null, null, 403 );
					}
		 		break;
		 		
		 		default:
					$this->registry->output->redirectScreen( $this->lang->words['modact_message_huh'], $this->settings['base_url'] . "app=core&amp;module=modcp&amp;fromapp=bitracker&amp;tab=unapprovedfiles" );
			 	break;
		 	}
	 	}

 		switch( $this->request['type'] )
 		{
	 		case 'app':
	 			if( $this->request['doaction'] == 'del' )
	 			{
		 			$cnt = $this->moderateLibrary->doMultiDelete( $ids );
		 			
		 			$this->message .= sprintf( $this->lang->words['modact_message_del'], $cnt );
	 			}
	 			else if( $this->request['doaction'] == 'app' )
	 			{
		 			$cnt = $this->moderateLibrary->doMultiApprove( $ids );
 			
		 			$this->message .= sprintf( $this->lang->words['modact_message_app'], $cnt );
	 			}
	 			else if( $this->request['doaction'] == 'unapp' )
	 			{
		 			$cnt = $this->moderateLibrary->doMultiUnapprove( $ids );
 			
		 			$this->message .= sprintf( $this->lang->words['modact_message_unapp'], $cnt );
	 			}
	 			else
	 			{
	 				$this->registry->output->redirectScreen( $this->lang->words['modact_message_huh'], $this->settings['base_url'] . "app=core&amp;module=modcp&amp;fromapp=bitracker&amp;tab={$returnAction}" );
	 			}
	 		break;

	 		case 'broke':
	 			if( $this->request['doaction'] == 'del' )
	 			{
		 			$cnt = $this->moderateLibrary->doMultiDelete( $ids );
	 			
		 			$this->message .= sprintf( $this->lang->words['modact_message_del'], $cnt );
	 			}
	 			else if( $this->request['doaction'] == 'rem' )
	 			{
	 				$cnt = $this->moderateLibrary->doMultiUnbroke( $ids );

		 			$this->message .= sprintf( $this->lang->words['modact_message_br'], $cnt );
	 			}
	 			else
	 			{
	 				$this->registry->output->redirectScreen( $this->lang->words['modact_message_huh'], $this->settings['base_url'] . "app=core&amp;module=modcp&amp;fromapp=bitracker&amp;tab={$returnAction}" );
	 			}
	 		break;
	 	}
	 	
		$this->registry->getClass('categories')->rebuildFileinfo('all');
		$this->registry->getClass('categories')->rebuildStatsCache();

		$this->registry->output->redirectScreen( $this->message, $this->settings['base_url'] . "app=core&amp;module=modcp&amp;fromapp=bitracker&amp;tab={$returnAction}" );
	}	

	/**
	 * Show form to report a file broken
	 *
	 * @return	@e void
	 */	
	protected function _reportBroken()
    {
		if( !$this->memberData['bit_report_files'] )
		{
			$this->registry->output->showError( 'no_permission', 107999, null, null, 403 );
		}
		
		$id		= intval($this->request['id']);
		
		$file	= $this->DB->buildAndFetch( array(	'select'	=> 'f.*', 
													'from'		=> array( 'bitracker_files' => 'f' ), 
													'where'		=> 'f.file_id=' . $id,
													'add_join'	=> array(
																		array(
																			'select'	=> 'm.*',
																			'from'		=> array( 'members' => 'm' ),
																			'where'		=> 'm.member_id=f.file_submitter',
																			'type'		=> 'left',
																			),
													 					)
											)		);
		
		if( !$file['file_id'] )
		{
			$this->registry->output->showError( 'cannot_find_to_report', 10895, null, null, 404 );
		}

		/* Editor library */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
		$editor = new $classToLoad();
		
		$editor->setAllowBbcode( true );
		$editor->setAllowSmilies( true );
		$editor->setAllowHtml( false );
		
		//-----------------------------------------
		// Show form if not submitted
		//-----------------------------------------
		
		if ( !$this->request['do_broken'] )
		{
			/* Navigation */
			foreach( $this->registry->getClass('categories')->getNav( $file['file_cat'] ) as $navigation )
			{
				$this->registry->output->addNavigation( $navigation[0], $navigation[1], $navigation[2], 'bitshowcat' );
			}
			
			$this->registry->output->addNavigation( $file['file_name'], "app=bitracker&amp;showfile={$file['file_id']}", $file['file_name_furl'], 'bitshowfile' );
			
			/* Output */
			$this->output = $this->registry->getClass('output')->getTemplate('bitracker_submit')->submitBrokenFile( $file, $editor->show( 'Post' ) );
			return;
		}

		/* Format report for save */
		$reason = $editor->process( $_POST['Post'] );
		
		IPSText::getTextClass('bbcode')->parse_html		= 0;
		IPSText::getTextClass('bbcode')->parse_smilies	= 1;
		IPSText::getTextClass('bbcode')->parse_bbcode	= 1;

 		$reason	= IPSText::getTextClass( 'bbcode' )->preDbParse( $reason );
		
		if( !trim($reason) )
		{
			$this->registry->output->showError( 'reason_report_req', 10895.2 );
		}

		$this->DB->update( 'bitracker_files', array( 'file_broken' => 1, 'file_broken_reason' => $reason, 'file_broken_info' => $this->memberData['member_id'] . '|' . $this->memberData['members_display_name'] . '|' . time() ), "file_id=" . $id );
		
		$this->registry->getClass('categories')->rebuildFileinfo( $file['file_cat'] );
		
		//-----------------------------------------
		// Send notification...
		//-----------------------------------------
		
		$_url	= $this->registry->output->buildSEOUrl( 'app=bitracker&amp;showfile=' . $file['file_id'], 'public', $file['file_name_furl'], 'bitshowfile' );

		$classToLoad		= IPSLib::loadLibrary( IPS_ROOT_PATH . '/sources/classes/member/notifications.php', 'notifications' );
		$notifyLibrary		= new $classToLoad( $this->registry );

		$notifyLibrary->setMember( $file );
		$notifyLibrary->setFrom( $this->memberData );
		$notifyLibrary->setNotificationKey( 'file_mybroken' );
		$notifyLibrary->setNotificationUrl( $_url );
		$notifyLibrary->setNotificationText( sprintf( $this->lang->words['moderate_filebroke'], $file['members_display_name'], $file['file_name'] ) );
		$notifyLibrary->setNotificationTitle( sprintf( $this->lang->words['moderate_subjectbroke'], $_url, $file['file_name'] ) );
		try
		{
			$notifyLibrary->sendNotification();
		}
		catch( Exception $e ){}
		
		$moderators	= $this->registry->getClass('bitFunctions')->returnModerators();
		
		if( is_array($moderators) AND count($moderators) )
		{
			// Don't use &amp; here or it breaks the link in the emails
			$_modPanel	= $this->registry->output->buildSEOUrl( 'app=core&module=modcp&fromapp=bitracker&tab=brokenfiles', 'public' );
			
			foreach( $moderators as $moderator )
			{
				//-----------------------------------------
				// Don't send notification to yourself
				//-----------------------------------------
				
				if( $moderator['member_id'] == $this->memberData['member_id'] )
				{
					continue;
				}

				$notifyLibrary->setMember( $moderator );
				$notifyLibrary->setFrom( $this->memberData );
				$notifyLibrary->setNotificationKey( 'file_broken' );
				$notifyLibrary->setNotificationUrl( $_url );
				$notifyLibrary->setNotificationText( sprintf( $this->lang->words['moderate_modfilebroke'], $moderator['members_display_name'], $file['file_name'], $_modPanel ) );
				$notifyLibrary->setNotificationTitle( sprintf( $this->lang->words['moderate_modsubjectbroke'], $_url, $file['file_name'] ) );
				try
				{
					$notifyLibrary->sendNotification();
				}
				catch( Exception $e ){}
			}
		}

		
		//-----------------------------------------
		// Redirect user
		//-----------------------------------------
		
		$this->registry->output->redirectScreen( $this->lang->words['moderate_broken'], $this->settings['base_url'] . "app=bitracker&amp;showfile={$id}", $file['file_name_furl'], 'bitshowfile' );
	}
	
	/**
	 * Remove single broken file flag
	 *
	 * @return	@e void
	 */	
	protected function _reportUnbroken( )
    {
		$id		= intval($this->request['id']);
		
		$file	= $this->DB->buildAndFetch( array(	'select'	=> '*',
													'from'		=> 'bitracker_files',
													'where'		=> 'file_id=' . $id
											)		);
		
		if( !$file['file_id'] )
		{
			$this->registry->output->showError( 'cannot_find_to_unreport', 10896, null, null, 404 );
		}

		if( !$this->moderateLibrary->doMultiUnbroke( array( $id ) ) )
		{
			$this->registry->output->showError( 'cannot_find_to_unreport', 10897, null, null, 403 );
		}
		
		$this->registry->getClass('categories')->rebuildFileinfo( $file['file_cat'] );

		$this->registry->output->redirectScreen( $this->lang->words['moderate_unbroken'], $this->settings['base_url'] . "app=bitracker&amp;showfile={$id}", $file['file_name_furl'], 'bitshowfile' );
	}
}