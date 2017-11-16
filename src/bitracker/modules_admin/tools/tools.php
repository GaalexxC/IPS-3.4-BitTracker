<?php
/**
 * @file		tools.php 	IP.download Miscellaneous Tools
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 *
 * $Author: bfarber $
 * @since		1st April 2004
 * $LastChangedDate: 2012-04-27 14:55:56 -0400 (Fri, 27 Apr 2012) $
 * @version		v2.5.4
 * $Revision: 10657 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

/**
 *
 * @class		admin_bitracker_tools_tools
 * @brief		IP.download Miscellaneous Tools
 */
class admin_bitracker_tools_tools extends ipsCommand
{
	/**
	 * Skin object shortcut
	 *
	 * @var		$html
	 */
	public $html;
	
	/**
	 * String for the screen url bit
	 *
	 * @var		$form_code
	 */
	public $form_code    = '';
	
	/**
	 * String for the JS url bit
	 *
	 * @var		$form_code_js
	 */
	public $form_code_js = '';
	
	/**
	 * Main function executed automatically by the controller
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Load HTML
		//-----------------------------------------
		
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_tools' );
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=tools&amp;section=tools';
		$this->form_code_js	= $this->html->form_code_js	= 'module=tools&section=tools';
		
		//-----------------------------------------
		// Load Language
		//-----------------------------------------
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_bitracker' ) );

		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{	
			case 'check_topics':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bit_tools_topics' );
				$this->_checkTopics();
			break;
			case 'do_topics':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bit_tools_topics' );
				$this->_doTopics();
			break;
				
			//-----------------------------------------
			case 'do_cats':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bit_tools_rebuild' );
				$this->_rebuildCategories();
			break;			
			//-----------------------------------------
			case 'recount_dlcounts':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bit_tools_rebuild' );
				$this->_recountbitracker();
			break;
			//-----------------------------------------
			case 'thumbs':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bit_tools_thumbs' );
				$this->_rebuildThumbnails();
			break;
			//-----------------------------------------		
			case 'check_orph':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bit_tools_orphan' );
				$this->_checkOrphanedFiles();
			break;
			case 'do_orph':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bit_tools_orphan' );
				$this->_removeOrphanedFiles();
			break;
			//-----------------------------------------		
			case 'check_broken':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bit_tools_orphan' );
				$this->_checkBrokenFiles();
			break;
			case 'do_broken':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'bit_tools_orphan' );
				$this->_removeBrokenFiles();
			break;

			case 'main':
			default:
				$this->_mainScreen();
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}
	
	/**
	 * Home screen - present user with tool options
	 *
	 * @return	@e void
	 */
	protected function _mainScreen()
	{											
		$this->registry->output->html .= $this->html->overviewScreen();
	}
	
	/**
	 * Check for broken files
	 *
	 * @return	@e void
	 */
	protected function _checkBrokenFiles()
	{
		if( $this->settings['bit_filestorage'] == 'ftp' )
		{
			$this->registry->output->global_message = $this->lang->words['t_noftp'];
			$this->_mainScreen();
			return;
		}
		
		//-----------------------------------------
		// Could take some time..
		//-----------------------------------------
		
		set_time_limit(0);
		
		$thefiles		= '';
		$images			= '';
		$files			= array();
		$screenshots	= array();
		$thumbs			= array();
		
		//-----------------------------------------
		// Database storage?
		//-----------------------------------------
		
		$this->DB->build( array(
								'select'	=> 'r.*',
								'from'		=> array( 'bitracker_files_records' => 'r' ),
								'where'		=> "r.record_type IN ('upload','ssupload')",
								'add_join'	=> array(
													array(
														'select'	=> 'f.*',
														'from'		=> array( 'bitracker_files' => 'f' ),
														'where'		=> 'f.file_id=r.record_file_id',
														'type'		=> 'left',
														),
													array(
														'select'	=> 's.*',
														'from'		=> array( 'bitracker_filestorage' => 's' ),
														'where'		=> 's.storage_id=r.record_db_id',
														'type'		=> 'left',
														),
													)
						)		);
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			if( $r['record_db_id'] )
			{
				if( !$r['storage_file'] )
				{
					$files[] = $r;
				}
				if( !$r['storage_ss'] )
				{
					$screenshots[] = $r;
				}
				if( !$r['storage_thumb'] )
				{
					$thumbs[] = $r;
				}
			}
			else
			{
				if( $r['record_type'] == 'upload' )
				{
					if( !is_file( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localfilepath'] ) . '/' . $r['record_location'] ) )
					{
						$files[ $r['record_location'] ] = $r;
					}
				}
				
				if( $r['record_type'] == 'ssupload' )
				{
					if( !is_file( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localsspath'] ) . '/' . $r['record_location'] ) )
					{
						$screenshots[ $r['record_location'] ] = $r;
					}
					
					if( $r['record_thumb'] )
					{
						if( !is_file( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localsspath'] ) . '/' . $r['record_thumb'] ) )
						{
							$thumbs[ $r['record_thumb'] ] = $r;
						}
					}
				}
			}
		}

		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		if( count($files) )
		{
			foreach( $files as $row )
			{
				$thefiles .= $this->html->brokenFileRow( $row, 'file' );
			}
		}

		if( count($screenshots) )
		{
			foreach( $screenshots as $row )
			{
				$images .= $this->html->brokenFileRow( $row, 'ss' );
			}
		}
		
		if( count($thumbs) )
		{
			foreach( $thumbs as $row )
			{
				$images .= $this->html->brokenFileRow( $row, 'thumb' );
			}
		}
		
		$this->registry->output->html .= $this->html->brokenFileListing( $thefiles, $images );
	}
	
	/**
	 * Remove the broken file records
	 *
	 * @return	@e void
	 */
	protected function _removeBrokenFiles( )
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$thumbs	= array();
		$files	= array();
		$ss		= array();
		$cnt	= 0;
		
 		foreach ( $_POST as $key => $value)
 		{
 			if ( preg_match( "/^file_(\d+)$/", $key, $match ) )
 			{
 				if( $value )
 				{
 					$files[] = $match[1];
 				}
 			}
 			if ( preg_match( "/^ss_(\d+)$/", $key, $match ) )
 			{
 				if( $value )
 				{
 					$ss[] = $match[1];
 				}
 			} 			
 			if ( preg_match( "/^thumb_(\d+)$/", $key, $match ) )
 			{
 				if( $value )
 				{
 					$thumbs[] = $match[1];
 				}
 			} 			
 		}

		//-----------------------------------------
		// Deleting files?
		//-----------------------------------------
		
 		if( $this->request['type'] == 'file' )
 		{
	 		if( count($files) )
	 		{
		 		$cnt		= count($files);
				$_fileIds	= array();
				$revisions	= array();
				
				$this->DB->build( array( 'select' => '*', 'from' => 'bitracker_files_records', 'where' => "record_id IN(" . implode( ',', $files ) . ")" ) );
				$this->DB->execute();
				
				while( $_r = $this->DB->fetch() )
				{
					if( $_r['record_backup'] )
					{
						$revisions[ $_r['record_id'] ]	= $_r['record_id'];
					}
					else
					{
						$_fileIds[ $_r['record_file_id'] ]	= $_r['record_file_id'];
					}
				}

		 		switch( $this->request['action'] )
		 		{
			 		case 'del':
			 			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'bitracker' ) . '/sources/classes/moderate.php', 'bit_moderate', 'bitracker' );
						$mod			= new $classToLoad( $this->registry );
						$cnt			= $mod->doMultiDelete( $_fileIds );
						
						if( !$cnt AND count($files) )
						{
							if( count($revisions) )
							{
								$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'bitracker' ) . '/sources/classes/versioning.php', 'versioningLibrary', 'bitracker' );
								$rev			= new $classToLoad( $this->registry );

								foreach( $revisions as $revision )
								{
									$this->DB->build( array( 'select' => '*', 'from' => 'bitracker_filebackup', 'where' => $this->DB->buildWherePermission( array( $revision ), 'b_records' ) ) );
									$revq = $this->DB->execute();

									while( $_k = $this->DB->fetch($revq) )
									{
										$rev->remove( $_k['b_fileid'], $_k['b_id'], $_k );
									}
								}
							}

							$this->DB->delete( "bitracker_files_records", "record_id IN (" . implode( ',', $files ) . ")" );
							
							$cnt	= $this->DB->getAffectedRows();
						}
					break;
						
					case 'hide':
						$this->DB->update( "bitracker_files", array( 'file_open' => 0 ), "file_id IN (" . implode( ',', $_fileIds ) . ")" );
					break;
						
					default:
						$this->registry->output->global_message = $this->lang->words['t_noaction'];
						$this->_checkBrokenFiles();
						return;
					break;
				}
				
				$this->registry->getClass('categories')->rebuildFileinfo( 'all' );
				$this->registry->getClass('categories')->rebuildStatsCache();
			}
			else
			{
				$this->registry->output->global_message = $this->lang->words['t_nofiles'];
				$this->_checkBrokenFiles();
				return;
			}
		}
		else
		{
	 		if( count($ss) )
	 		{
		 		$cnt		= count($ss);
				$_fileIds	= array();
				
				$this->DB->build( array( 'select' => 'record_file_id', 'from' => 'bitracker_files_records', 'where' => "record_id IN(" . implode( ',', $ss ) . ")" ) );
				$this->DB->execute();
				
				while( $_r = $this->DB->fetch() )
				{
					$_fileIds[ $_r['record_file_id'] ]	= $_r['record_file_id'];
				}
				
		 		switch( $this->request['action'] )
		 		{
			 		case 'del':
			 			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'bitracker' ) . '/sources/classes/moderate.php', 'bit_moderate', 'bitracker' );
						$mod			= new $classToLoad( $this->registry );
						$cnt	= $mod->doMultiDelete( $_fileIds );
						
						if( !$cnt AND count($ss) )
						{
							$this->DB->delete( "bitracker_files_records", "record_id IN (" . implode( ',', $ss ) . ")" );
							
							$cnt	= $this->DB->getAffectedRows();
						}
					break;
						
					case 'hide':
						$this->DB->update( "bitracker_files", array( 'file_open' => 0 ), "file_id IN (" . implode( ',', $_fileIds ) . ")" );
					break;
						
					case 'rem':
						$this->DB->delete( "bitracker_files_records", "record_id IN (" . implode( ',', $ss ) . ")" );
					break;
						
					default:
						$this->registry->output->global_message = $this->lang->words['t_noaction_img'];
						$this->_checkBrokenFiles();
						return;
					break;
				}
			}
						
	 		if( count($thumbs) )
	 		{
		 		$cnt		+= count($thumbs);
				$_fileIds	= array();
				
				$this->DB->build( array( 'select' => 'record_file_id', 'from' => 'bitracker_files_records', 'where' => "record_id IN(" . implode( ',', $thumbs ) . ")" ) );
				$this->DB->execute();
				
				while( $_r = $this->DB->fetch() )
				{
					$_fileIds[ $_r['record_file_id'] ]	= $_r['record_file_id'];
				}
				
		 		switch( $this->request['action'] )
		 		{
			 		case 'del':
			 			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'bitracker' ) . '/sources/classes/moderate.php', 'bit_moderate', 'bitracker' );
						$mod			= new $classToLoad( $this->registry );
						$cnt	= $mod->doMultiDelete( $_fileIds );
						
						if( !$cnt AND count($thumbs) )
						{
							$this->DB->delete( "bitracker_files_records", "record_id IN (" . implode( ',', $thumbs ) . ")" );
							
							$cnt	= $this->DB->getAffectedRows();
						}
					break;
						
					case 'hide':
						$this->DB->update( "bitracker_files", array( 'file_open' => 0 ), "file_id IN (" . implode( ',', $_fileIds ) . ")" );
					break;
						
					case 'rem':
						$this->DB->update( "bitracker_files_records", array( 'record_thumb' => '' ), "record_id IN (" . implode( ',', $thumbs ) . ")" );
					break;						
						
					default:
						$this->registry->output->global_message = $this->lang->words['t_noaction_img'];
						$this->_checkBrokenFiles();
						return;
					break;
				}
			}
			
			$this->registry->getClass('categories')->rebuildFileinfo( 'all' );
			$this->registry->getClass('categories')->rebuildStatsCache();
		}
			
		$this->registry->getClass('adminFunctions')->saveAdminLog( sprintf( $this->lang->words['t_numfixed'], $cnt ) );
		$this->registry->output->global_message = sprintf( $this->lang->words['t_numfixed'], $cnt );
		$this->_mainScreen();
	}	
	
	/**
	 * Check for orphaned files
	 *
	 * @return	@e void
	 */
	protected function _checkOrphanedFiles()
	{
		if( $this->settings['bit_filestorage'] == 'ftp' )
		{
			$this->registry->output->global_message = $this->lang->words['t_noftp'];
			$this->_mainScreen();
			return;
		}
		
		//-----------------------------------------
		// Could take some time..
		//-----------------------------------------
		
		set_time_limit(0);
		
		$rows	= '';

		//-----------------------------------------
		// Now get em
		//-----------------------------------------
		
		if( $this->settings['bit_filestorage'] == 'db' )
		{
			$this->DB->build( array( 'select' 	=> 's.*',
									 'from' 	=> array( 'bitracker_filestorage' => 's' ),
									 'add_join' => array( 0 => array( 'select' => 'r.record_id',
									 								  'from' => array( 'bitracker_files_records' => 'r' ),
									 								  'where' => 'r.record_db_id=s.storage_id',
									 								  'type' => 'left' )	),
									 'where'	=> "r.record_id='' OR r.record_id IS NULL"
								)		);
			$this->DB->execute();
			
			if( $this->DB->getTotalRows() )
			{
				while( $row = $this->DB->fetch() )
				{
					$rows .= $this->html->orphanedFileRow( $row['storage_id'], 'id' );
				}		
			}
		}
		else
		{
			$count		= 0;
			$the_files	= array();
			$the_images	= array();
			$real_files	= array();
			$real_imgs	= array();
			
			//-----------------------------------------
			// Get the actual files and screenshots
			//-----------------------------------------

			if( is_dir( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localfilepath'] ) ) )
			{
				if( $dh = opendir( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localfilepath'] ) ) )
				{
					while (($file = readdir($dh)) !== false)
					{
						if( !is_dir( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localfilepath'] ) . '/' . $file ) AND $file != "index.html" )
						{
							$the_files[] = $file;
						}
						else if( is_dir( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localfilepath'] ) . '/' . $file ) AND strpos( $file, 'monthly_' ) === 0 )
						{
							if( $sdh = opendir( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localfilepath'] ) . '/' . $file ) )
							{
								while (($_file = readdir($sdh)) !== false)
								{
									if( !is_dir( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localfilepath'] ) . '/' . $file . '/' . $_file) && $_file != "index.html" )
									{
										$the_files[] = $file . '/' . $_file;
									}
								}
								
								closedir( $sdh );
							}
						}
					}
					
					closedir( $dh );
				}
			}
			
			if( is_dir( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localsspath'] ) ) )
			{
				if( $dh = opendir( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localsspath'] ) ) )
				{
					while (($file = readdir($dh)) !== false)
					{
						if( !is_dir( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localsspath'] ) . '/' . $file) && $file != "index.html" )
						{
							$the_images[] = $file;
						}
						else if( is_dir( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localsspath'] ) . '/' . $file) AND strpos( $file, 'monthly_' ) === 0 )
						{
							if( $sdh = opendir( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localsspath'] ) . '/' . $file ) )
							{
								while (($_file = readdir($sdh)) !== false)
								{
									if( !is_dir( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localsspath'] ) . '/' . $file . '/' . $_file) && $_file != "index.html" )
									{
										$the_images[] = $file . '/' . $_file;
									}
								}
								
								closedir( $sdh );
							}
						}
					}
					
					closedir( $dh );
				}
			}
			
			//-----------------------------------------
			// Get the database files and screenshots
			//-----------------------------------------
			
			$this->DB->build( array( 'select' => '*', 'from' => 'bitracker_files_records' ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				if( $r['record_type'] == 'ssupload' )
				{
					$real_imgs[]	= $r['record_location'];
					$real_imgs[]	= $r['record_thumb'];
				}
				else
				{
					$real_files[]	= $r['record_location'];
				}
			}
			
			$this->DB->build( array( 'select' => '*', 'from' => 'bitracker_temp_records' ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				if( $r['record_type'] == 'ss' )
				{
					$real_imgs[]	= $r['record_location'];
				}
				else
				{
					$real_files[]	= $r['record_location'];
				}
			}
			
			//-----------------------------------------
			// And compare
			//-----------------------------------------
			
			if( count($the_files) )
			{
				foreach( $the_files as $file )
				{
					if( !in_array( $file, $real_files ) )
					{
						$rows .= $this->html->orphanedFileRow( $file, 'file' );
					}
				}
			}

			if( count($the_images) )
			{
				foreach( $the_images as $file )
				{
					if( !in_array( $file, $real_imgs ) )
					{
						$rows .= $this->html->orphanedFileRow( $file, 'ss' );
					}
				}
			}
		}
		
		$this->registry->output->html .= $this->html->orphanedFileListing( $rows );
	}
	
	/**
	 * Remove the selected orphaned files
	 *
	 * @return	@e void
	 */
	protected function _removeOrphanedFiles( )
	{
		$ids	= array();
		$files	= array();
		$ss		= array();
		$cnt	= 0;
		
 		foreach ( $_POST as $key => $value)
 		{
 			if ( preg_match( "/^file_(.+)$/", $key, $match ) )
 			{
 				if( $value )
 				{
 					$files[] = urldecode($value);
 				}
 			}

 			if ( preg_match( "/^ss_(.+)$/", $key, $match ) )
 			{
 				if( $value )
 				{
 					$ss[] = urldecode($value);
 				}
 			}

 			if ( preg_match( "/^id_(\d+)$/", $key, $match ) )
 			{
 				if( $value )
 				{
 					$ids[] = urldecode($value);
 				}
 			} 			
 		}
 		
 		if( count($ids) )
 		{
	 		$cnt	+= count($ids);

	 		$this->DB->delete( 'bitracker_filestorage', 'storage_id IN(' . implode( ",", $ids ) . ')' );
 		}
 		
 		if( count($files) )
 		{
	 		$path = str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localfilepath'] ). "/";
	 		
	 		foreach( $files as $file )
	 		{
		 		$cnt++;
		 		
		 		unlink( $path . $file );
	 		}
 		}
 		
 		if( count($ss) )
 		{
	 		$path = str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localsspath'] ) . "/";
	 		
	 		foreach( $ss as $file )
	 		{
		 		$cnt++;
		 		
		 		unlink( $path . $file );
	 		}
 		} 		
 		
		$this->registry->getClass('adminFunctions')->saveAdminLog( sprintf( $this->lang->words['t_numorph'], $cnt ) );
		$this->registry->output->global_message = sprintf( $this->lang->words['t_numorph'], $cnt );
		$this->_mainScreen();
	}
		
	/**
	 * Check for missing topics
	 *
	 * @return	@e void
	 */
	protected function _checkTopics()
	{
		
		$topics		= '';
		$missing	= '';

		$this->DB->build( array( 'select'	=> 'f.*',
								 'from'		=> array( 'bitracker_files' => 'f' ),
								 'where'	=> 'f.file_open=1 AND f.file_topicid <> 0',
								 'add_join'	=> array(
								 				array(
								 					'type'		=> 'left',
								 					'select'	=> 't.tid, t.forum_id',
								 					'where'		=> 't.tid=f.file_topicid AND ' . $this->registry->getClass('class_forums')->fetchTopicHiddenQuery( array( 'approved', 'queued' ), 't.' ),
								 					'from'		=> array( 'topics' =>'t' )
								 					),
								 				array(
								 					'type'		=> 'left',
								 					'select'	=> 'm.members_display_name',
								 					'where'		=> 'm.member_id=f.file_submitter',
								 					'from'		=> array( 'members' =>'m' )
								 					)
								 				)
						)		);
		$this->DB->execute();

		if( $this->DB->getTotalRows() )
		{
			while( $row = $this->DB->fetch() )
			{
				if( !$row['tid'] )
				{
					$topics .= $this->html->topicsRow( $row );
				}
			}
		}
		
		$categories = array();
		
		if( count($this->registry->getClass('categories')->cat_lookup) > 0 )
		{
			foreach( $this->registry->getClass('categories')->cat_lookup as $k => $v )
			{
				if( $v['coptions']['opt_topice'] == 1 )
				{
					$categories[] = $k;
				}
			}
		}
		
		if( count($categories) )
		{
			$this->DB->build( array( 'select' => 'file_name, file_topicid, file_id', 'from' => 'bitracker_files', 'where' => "file_open=1 AND file_topicid=0 AND file_cat IN(" . implode( ",", $categories ) . ")" ) );
			$this->DB->execute();
			
			if( $this->DB->getTotalRows() )
			{
				while( $row = $this->DB->fetch() )
				{
					$row['file_topicid'] = '-';
					
					$missing .= $this->html->topicsRow( $row );
				}
			}
		}
		
		$this->registry->output->html .= $this->html->topicsListing( $topics, $missing );
	}
	
	/**
	 * Rebuild category latest info
	 *
	 * @return	@e void
	 */
	protected function _rebuildCategories()
	{
		/* Rebuild and log */
		$this->registry->getClass('categories')->rebuildFileinfo('all');
		$this->registry->getClass('adminFunctions')->saveAdminLog($this->lang->words['t_latest']);
		
		/* Get back to main screen */
		$this->registry->output->global_message = $this->lang->words['t_latest'];
		$this->_mainScreen();
	}

	/**
	 * Recount download counts
	 *
	 * @return	@e void
	 */
	protected function _recountbitracker()
	{
		if( $this->settings['bit_logallbitracker'] == 0 )
		{
			$this->registry->output->global_message = $this->lang->words['t_logall'];
			$this->_mainScreen();
			return;
		}
		
		$ids = array();
		
		$this->DB->build( array( 'select'	=> 'COUNT(*) as cnt, dfid',
								 'from'		=> 'bitracker_bitracker',
								 'group'	=> 'dfid'
							)		);
		$outer = $this->DB->execute();
		
		while( $row = $this->DB->fetch($outer) )
		{
			if( !$row['dfid'] )
			{
				continue;
			}
			
			$ids[] = $row['dfid'];
			
			$this->DB->update( "bitracker_files", array( 'file_bitracker' => $row['cnt'] ), "file_id=" . $row['dfid'] );
		}
		
		if( count($ids) )
		{
			$this->DB->update( "bitracker_files", array( 'file_bitracker' => 0 ), "file_id NOT IN(" . implode( ",", $ids ) . ")" );
		}
		
		$this->registry->getClass('categories')->rebuildFileinfo('all');
		$this->registry->getClass('adminFunctions')->saveAdminLog($this->lang->words['t_recounted']);
		$this->registry->output->global_message = $this->lang->words['t_recounted'];
		$this->_mainScreen();
	}	

	/**
	 * Rebuild all necessary topics
	 *
	 * @return	@e void
	 */
	protected function _doTopics()
	{
		// Set limit to do at a time...
		$this->request['limit'] = $this->request['limit'] ? intval( $this->request['limit'] ) : 20;
		
		$this->registry->class_localization->loadLanguageFile( array( 'public_bitracker' ) );
		
		//-----------------------------------------
		// And finally, the topic lib
		//-----------------------------------------

		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppdir( 'bitracker' ) ."/sources/classes/topics.php", 'topicsLibrary', 'bitracker' );
		$lib_topics		= new $classToLoad( $this->registry );
		
		//-----------------------------------------
		// Let's begin...
		//-----------------------------------------
				
		$tids 		= array();
		$fixed 		= $this->request['fixed'] ? intval($this->request['fixed']) : 0;
		$fix_dis 	= $this->request['fixdis'] ? intval($this->request['fixdis']) : 0;
		$cnt		= 0;
		
		if( $this->request['all'] == 1 )
		{
			$this->DB->build( array( 'select'		=> 'f.*',
									 'from'			=> array( 'bitracker_files' => 'f' ),
									 'where'		=> 'f.file_open=1 AND f.file_topicid <> 0',
									 'limit'		=> array( $fixed, $this->request['limit'] ),
									 'add_join'		=> array(
										 				array(
										 					'type'		=> 'left',
										 					'select'	=> 't.tid, t.forum_id',
										 					'where'		=> 't.tid=f.file_topicid',
										 					'from'		=> array( 'topics' =>'t' )
										 					),
										 				array(
										 					'type'		=> 'left',
										 					'select'	=> 'm.members_display_name',
										 					'where'		=> 'm.member_id=f.file_submitter',
										 					'from'		=> array( 'members' =>'m' )
										 					)
									 				)
							)		);
			$outer = $this->DB->execute();
			
			if( $this->DB->getTotalRows($outer) )
			{
				while( $row = $this->DB->fetch($outer) )
				{
					$forum		= array();
					$fixed++;
					
					$category = $this->registry->getClass('categories')->cat_lookup[ $row['file_cat'] ];

					if( !$row['tid'] )
					{					
						if( !$category['coptions']['opt_topicf'] )
						{
							continue;
						}
						
						$row['file_submitter_name'] = $row['members_display_name'];
						
						$_POST['ipsTags']	= null;

						$lib_topics->sortTopic( $row, $category, 'new', 1 );

						$fix_dis++;
					}
				}

				$this->registry->output->redirect( $this->settings['base_url'] . "{$this->form_code}&do=do_topics&all=1&limit={$this->request['limit']}&fixed={$fixed}&fixdis={$fix_dis}", sprintf( $this->lang->words['t_topcreated'], $fix_dis ), 2, true, true );
			}
			else
			{
				$categories = array();
			
				if( count($this->registry->getClass('categories')->cat_lookup) > 0 )
				{
					foreach( $this->registry->getClass('categories')->cat_lookup as $k => $v )
					{
						if( $v['coptions']['opt_topice'] == 1 )
						{
							$categories[] = $k;
						}
					}
				}
			
				if( count($categories) )
				{
					$this->DB->build( array( 'select' 	=> 'f.*', 
											 'from' 	=> array( 'bitracker_files' => 'f' ),
											 'where' 	=> "f.file_open=1 AND f.file_topicid < 1 AND f.file_cat IN(" . implode( ",", $categories) . ")",
											 'add_join'	=> array(
											 					array(
											 						'type'		=> 'left',
											 						'select'	=> 'm.members_display_name',
											 						'from'		=> array( 'members' => 'm' ),
											 						'where'		=> 'm.member_id=f.file_submitter'
											 						)
											 					)
											)		);
					$outer = $this->DB->execute();
					
					if( $this->DB->getTotalRows($outer) )
					{
						while( $row = $this->DB->fetch($outer) )
						{
							$category = $this->registry->getClass('categories')->cat_lookup[ $row['file_cat'] ];
							
							if( !$category['coptions']['opt_topicf'] )
							{
								continue;
							}
							
							$row['file_submitter_name'] = $row['members_display_name'];

							$_POST['ipsTags']	= null;

							$lib_topics->sortTopic( $row, $category, 'new', 1 );
													
							$fixed++;						
							$cnt++;

							if( $cnt >= $this->request['limit'] )
							{
								$this->registry->output->redirect( $this->settings['base_url'] . "{$this->form_code}&do=do_topics&all=1&limit=20&fixed={$fixed}", sprintf( $this->lang->words['t_topcreated'], $fixed ), 2, true, true );
							}
	
						}
					}
				}
				
				if( $cnt == 0 )
				{
					$this->registry->getClass('categories')->rebuildFileinfo( 'all' );
					$this->registry->getClass('adminFunctions')->saveAdminLog( sprintf( $this->lang->words['t_createdlog'], $fix_dis ) );

					$this->registry->output->redirect( $this->settings['base_url'] . $this->form_code, sprintf( $this->lang->words['t_createdlog'], $fix_dis ) );
				}
			}
		}
		else
		{
			$query_string	= "";
			$completed_ids	= array();
			
	 		foreach ( $_POST as $key => $value)
	 		{
	 			if ( preg_match( "/^file_(\d+)$/", $key, $match ) )
	 			{
	 				if( $value )
	 				{
	 					$ids[ $match[1] ] = $value;
	 				}
	 			}
	 		}
	 		
	 		if( count($ids) )
	 		{
		 		foreach( $ids as $key => $value )
		 		{
			 		if( !$value )
			 		{
				 		continue;
			 		}
			 		
					$this->DB->build( array( 'select' 	=> 'f.*', 
											 'from' 	=> array( 'bitracker_files' => 'f' ),
											 'where' 	=> "f.file_open=1 AND f.file_id={$key}",
											 'add_join'	=> array(
											 					array(
											 						'type'		=> 'left',
											 						'select'	=> 'm.members_display_name',
											 						'from'		=> array( 'members' => 'm' ),
											 						'where'		=> 'm.member_id=f.file_submitter'
											 						)
											 					)
											)		);
					$outer = $this->DB->execute();
					
					if( $this->DB->getTotalRows($outer) )
					{
						while( $row = $this->DB->fetch($outer) )
						{
							$category = $this->registry->getClass('categories')->cat_lookup[ $row['file_cat'] ];
							
							if( !$category['coptions']['opt_topicf'] )
							{
								continue;
							}
							
							$row['file_submitter_name'] = $row['members_display_name'];

							$_POST['ipsTags']	= null;

							$lib_topics->sortTopic( $row, $category, 'new', 1 );
									
							$fixed++;
							$cnt++;
							$completed_ids[] = $key;

							if( $cnt >= $this->request['limit'] )
							{
								$to_querys = array();

								foreach( $ids as $k => $v )
								{
									if( !in_array( $k, $completed_ids ) )
									{
										if( $v == 1 )
										{
											$to_querys[] = $k;
										}
									}
								}
								
								if( count($to_querys) )
								{
									$this->DB->update( "bitracker_files", array( 'file_topicid' => -1 ), "file_id IN (" . implode( ",", $to_querys ) . ")" );
								}
								
								$this->registry->output->redirect( $this->settings['base_url'] . "{$this->form_code}&do=do_topics&limit={$this->request['limit']}&fixed={$fixed}", sprintf( $this->lang->words['t_topcreated'], $fixed ), 2, true, true );
							}
						}
					}
				}
			}
			else
			{
				$this->DB->build( array( 'select' 	=> 'f.*', 
										 'from' 	=> array( 'bitracker_files' => 'f' ),
										 'where' 	=> "f.file_open=1 AND f.file_topicid=-1",
										 'limit'	=> array( $this->request['limit'] ),
										 'add_join'	=> array(
										 					array(
										 						'type'		=> 'left',
										 						'select'	=> 'm.members_display_name',
										 						'from'		=> array( 'members' => 'm' ),
										 						'where'		=> 'm.member_id=f.file_submitter'
										 						)
										 					)
										)		);
				$outer = $this->DB->execute();
				
				if( $this->DB->getTotalRows($outer) )
				{
					while( $row = $this->DB->fetch($outer) )
					{
						$category = $this->registry->getClass('categories')->cat_lookup[ $row['file_cat'] ];
						
						if( !$category['coptions']['opt_topicf'] )
						{
							continue;
						}
						
						$row['file_submitter_name'] = $row['members_display_name'];

						$_POST['ipsTags']	= null;

						$lib_topics->sortTopic( $row, $category, 'new', 1 );
												
						$fixed++;
						$cnt++;
						$completed_ids[] = $key;

						if( $cnt >= $this->request['limit'] )
						{
							$this->registry->output->redirect( $this->settings['base_url'] . "{$this->form_code}&do=do_topics&limit={$this->request['limit']}&fixed={$fixed}", sprintf( $this->lang->words['t_topcreated'], $fixed ), 2, true, true );
						}
					}
				}
			}				
		}	
		
		$this->registry->getClass('categories')->rebuildFileinfo( 'all' );
		$this->registry->getClass('adminFunctions')->saveAdminLog( sprintf( $this->lang->words['t_createdlog'], $fixed ) );

		$this->registry->output->redirect( $this->settings['base_url'] . $this->form_code, sprintf( $this->lang->words['t_createdlog'], $fixed ) );
	}
	
	/**
	 * Rebuild bit thumbnails
	 *
	 * @return	@e void
	 */
	protected function _rebuildThumbnails()
	{
		//-----------------------------------------
		// Init?
		//-----------------------------------------
		
		$limit = 20;
		
		$classToLoad		= IPSLib::loadLibrary( IPSLib::getAppDir( 'bitracker' ) . '/sources/classes/moderate.php', 'bit_moderate', 'bitracker' );
		$moderateFunctions	= new $classToLoad( $this->registry );

		//-----------------------------------------
		// Let's begin...
		//-----------------------------------------
				
		$fixed = $this->request['fixed'] ? intval($this->request['fixed']) : 0;
		
		$this->DB->build( array(
								'select'	=> 'r.*',
								'from'		=> array( 'bitracker_files_records' => 'r' ),
								'where'		=> "r.record_type='ssupload'",
								'limit'		=> array( $fixed, $limit ),
								'add_join'	=> array(
													array(
															'select'	=> 'f.*',
															'from'		=> array( 'bitracker_files' => 'f' ),
															'where'		=> 'f.file_id=r.record_file_id',
															'type'		=> 'left',
														),
													array(
															'select'	=> 's.*',
															'from'		=> array( 'bitracker_filestorage' => 's' ),
															'where'		=> 's.storage_id=r.record_db_id',
															'type'		=> 'left',
														),
													)
						)		);
		$outer = $this->DB->execute();
			
		if( $this->DB->getTotalRows($outer) )
		{
			while( $row = $this->DB->fetch($outer) )
			{
				$moderateFunctions->buildThumbnail( $row );

				$fixed++;
			}

			$this->registry->output->html	.= $this->registry->output->global_template->temporaryRedirect( $this->settings['base_url'] . "{$this->form_code}&do=thumbs&all=1&limit=20&fixed={$fixed}", sprintf( $this->lang->words['t_thumbsfixed'], $fixed ) );
		}
		else
		{
			$this->registry->getClass('adminFunctions')->saveAdminLog( $this->lang->words['t_allthumbs'] );
			$this->registry->output->redirect( $this->settings['base_url'] . "{$this->form_code}", $this->lang->words['t_allthumbs'], 2, true, true );
		}
	}
}