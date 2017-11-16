<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.4
 * IP.download Manager moderation library
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 *
 * @package		IP.bitracker
 *
 * @since		1st April 2004
 * @version		$Revision: 10721 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class bit_moderate
{
	/**
	 * Registry object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $registry;
	
	/**
	 * Database object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $DB;
	
	/**
	 * Settings object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $settings;
	
	/**
	 * Request object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $request;
	
	/**
	 * Language object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $lang;
	
	/**
	 * Member object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $member;
	protected $memberData;
	
	/**
	 * Cache object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $cache;
	protected $caches;
	
	/**
	 * Last category (for use in parent mod lib)
	 *
	 * @access	protected
	 * @var		integer
	 */	
	public $fileCat;
		
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make object */
		$this->registry = $registry;
		$this->DB       = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->lang     = $this->registry->getClass('class_localization');
		$this->member   = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache    = $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();

		//-----------------------------------------
		// Tagging
		//-----------------------------------------
		
		if ( ! $this->registry->isClassLoaded('bitrackerTags') )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
			$this->registry->setClass( 'bitrackerTags', classes_tags_bootstrap::run( 'bitracker', 'files' ) );
		}
	}
	
	/**
	 * Unpin files
	 *
	 * @access	public
	 * @param	array 		File ids to change
	 * @return	integer		Number of files changed
	 */
	public function doMultiUnpin( $ids=array() )
	{
		if( !is_array($ids) OR count($ids) < 1 )
		{
			return 0;
		}

		$num = 0;
		
		$this->DB->build( array( 'select' => '*', 'from' => 'bitracker_files', 'where' => 'file_id IN(' . implode( ',', $ids ) . ')' ) );
		$outer	= $this->DB->execute();
		
		while( $file = $this->DB->fetch( $outer ) )
		{
			if( !$this->registry->getClass('bitFunctions')->checkPerms( $file, 'modcanpin' ) )
			{
				continue;
			}

			$this->DB->update( "bitracker_files", array( 'file_pinned' => 0 ), "file_id=" . $file['file_id'] );
			
			$num++;
		}

		return $num;
	}
	
	/**
	 * Pin files
	 *
	 * @access	public
	 * @param	array 		File ids to change
	 * @return	integer		Number of files changed
	 */
	public function doMultiPin( $ids=array() )
	{
		if( !is_array($ids) OR count($ids) < 1 )
		{
			return 0;
		}

		$num = 0;
		
		$this->DB->build( array( 'select' => '*', 'from' => 'bitracker_files', 'where' => 'file_id IN(' . implode( ',', $ids ) . ')' ) );
		$outer	= $this->DB->execute();
		
		while( $file = $this->DB->fetch( $outer ) )
		{
			if( !$this->registry->getClass('bitFunctions')->checkPerms( $file, 'modcanpin' ) )
			{
				continue;
			}

			$this->DB->update( "bitracker_files", array( 'file_pinned' => 1 ), "file_id=" . $file['file_id'] );
			
			$num++;
		}

		return $num;
	}
	
	/**
	 * Change broken file status to unbroken
	 *
	 * @access	public
	 * @param	array 		File ids to change
	 * @return	integer		Number of files changed
	 */
	public function doMultiUnbroke( $ids=array() )
	{
		if( !is_array($ids) OR count($ids) < 1 )
		{
			return 0;
		}

		$num = 0;
		
		$this->DB->build( array( 'select' => '*', 'from' => 'bitracker_files', 'where' => 'file_id IN(' . implode( ',', $ids ) . ')' ) );
		$outer	= $this->DB->execute();
		
		while( $file = $this->DB->fetch( $outer ) )
		{
			if( !$this->registry->getClass('bitFunctions')->checkPerms( $file, 'modcanbrok' ) )
			{
				continue;
			}

			$this->DB->update( "bitracker_files", array( 'file_broken' => 0 ), "file_id=" . $file['file_id'] );
			
			$num++;
		}

		return $num;
	}
	
	/**
	 * Move files
	 *
	 * @access	public
	 * @param	array 		File ids to move
	 * @param	integer		Category id to move files to
	 * @return	integer		Number of files moved
	 */
	public function doMultiMove( $files=array(), $newcatid=0 )
	{
		if( !count($files) OR !$newcatid )
		{
			return 0;
		}

		$this->DB->update( 'bitracker_files', array( 'file_cat' => $newcatid ), 'file_id IN(' . implode( ',', array_keys( $files ) ) . ')' );
		$num	= intval($this->DB->getAffectedRows());

		$this->registry->bitrackerTags->moveTagsToParentId( array_keys( $files ), $newcatid );
		
		return $num;
	}
	
	/**
	 * Un-Approve files
	 *
	 * @access	public
	 * @param	array 		File ids to unapprove
	 * @return	integer		Number of files unapproved
	 */
	public function doMultiUnapprove( $files=array() )
	{
		if( !count($files) )
		{
			return 0;
		}
		
		$num = 0;
		
		$this->DB->build( array( 'select' => '*', 'from' => 'bitracker_files', 'where' => 'file_id IN(' . implode( ',', $files ) . ')' ) );
		$outer	= $this->DB->execute();
		
		while( $file = $this->DB->fetch( $outer ) )
		{
			if( !$file['file_id'] OR !$file['file_open'] )
			{
				continue;
			}
			
			if( !$this->registry->getClass('bitFunctions')->checkPerms( $file ) )
			{
				continue;
			}

			$this->DB->update( 'bitracker_files', array( 'file_open' => 0 ), 'file_id=' . $file['file_id'] );
			$this->registry->bitrackerTags->updateVisibilityByMetaId( $file['file_id'], 0 );
			
			$num++;
		}

		//-----------------------------------------
		// Hide likes
		//-----------------------------------------
		
		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
		$_like	= classes_like::bootstrap( 'bitracker', 'files' );
		$_like->toggleVisibility( $files, false );

		return $num;
	}
		
	/**
	 * Approve files
	 *
	 * @access	public
	 * @param	array 		File ids to approve
	 * @return	integer		Number of files approved
	 */
	public function doMultiApprove( $files=array() )
	{
		if( !count($files) )
		{
			return 0;
		}
		
		$num = 0;
		
		$this->DB->build( array( 'select'	=> 'f.*', 
								 'from'		=> array( 'bitracker_files' => 'f' ), 
								 'where'	=> 'f.file_id IN(' . implode( ',', $files ) . ')',
								 'add_join'	=> array(
													array(
														'select'	=> 'm.*',
														'from'		=> array( 'members' => 'm' ),
														'where'		=> 'm.member_id=f.file_submitter',
														'type'		=> 'left',
														),
								 					)
						)		);
		$outer	= $this->DB->execute();
		
		while( $file = $this->DB->fetch( $outer ) )
		{
			$category = $this->registry->getClass('categories')->cat_lookup[ $file['file_cat'] ];
			
			if( !$file['file_id'] OR $file['file_open'] )
			{
				continue;
			}

			if( !$this->registry->getClass('bitFunctions')->checkPerms( $file ) )
			{
				continue;
			}

			//-----------------------------------------
			// Update
			//-----------------------------------------
			
			$to_update = array(
								'file_open'			=> 1,
								'file_approver'		=> $this->memberData['member_id'],
								'file_approvedon'	=> time(),
								'file_new'			=> 0
							  );
			
			$this->DB->update( "bitracker_files", $to_update, "file_id=" . $file['file_id'] );
			
			$this->registry->bitrackerTags->updateVisibilityByMetaId( $file['file_id'], 1 );

			//-----------------------------------------
			// Need to post a topic?
			//-----------------------------------------

			$classToLoad		= IPSLib::loadLibrary( IPSLib::getAppDir('bitracker') . '/sources/classes/topics.php', 'topicsLibrary', 'bitracker' );
			$lib_topics			= new $classToLoad( $this->registry );

			$file['file_submitter_name']	= $file['members_display_name'];
			$file['file_open']				= 1;
	
			$lib_topics->sortTopic( $file, $category, $file['file_topicid'] ? 'edit' : 'new', true );

			//-----------------------------------------
			// Send subscription notifications
			//-----------------------------------------
			
			if( $file['file_updated'] > $file['file_submitted'] )
			{
				$_url	= $this->registry->output->buildSEOUrl( 'app=bitracker&amp;showfile=' . $file['file_id'], 'public', $file['file_name_furl'], 'bitshowfile' );
				
				//-----------------------------------------
				// Like class
				//-----------------------------------------
		
				require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
				$this->_like = classes_like::bootstrap( 'bitracker', 'files' );
				$this->_like->sendNotifications( $file['file_id'], array( 'immediate', 'offline' ), array(
																										'notification_key'		=> 'updated_file',
																										'notification_url'		=> $_url,
																										'email_template'		=> 'subsription_notifications',
																										'email_subject'			=> sprintf( $this->lang->words['sub_notice_subject'], $_url, $file['file_name'] ),
																										'build_message_array'	=> array(
																																		'NAME'  		=> '-member:members_display_name-',
																																		'AUTHOR'		=> $file['members_display_name'],
																																		'TITLE' 		=> $file['file_name'],
																																		'URL'			=> $_url,
																																		),
																										'from'					=> $file
																								) 		);
			}
			
			if( $file['file_new'] )
			{
				$_url	= $this->registry->output->buildSEOUrl( 'app=bitracker&amp;showfile=' . $file['file_id'], 'public', $file['file_name_furl'], 'bitshowfile' );
				
				//-----------------------------------------
				// Like class
				//-----------------------------------------
		
				require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
				$this->_like = classes_like::bootstrap( 'bitracker', 'categories' );
				$this->_like->sendNotifications( $file['file_cat'], array( 'immediate', 'offline' ), array(
																										'notification_key'		=> 'new_file',
																										'notification_url'		=> $_url,
																										'email_template'		=> 'subsription_notifications_new',
																										'email_subject'			=> sprintf( $this->lang->words['sub_notice_subject_new'], $_url, $file['file_name'] ),
																										'build_message_array'	=> array(
																																		'NAME'  		=> '-member:members_display_name-',
																																		'AUTHOR'		=> $file['members_display_name'],
																																		'TITLE' 		=> $file['file_name'],
																																		'URL'			=> $_url,
																																		),
																										'from'					=> $file
																								) 		);
			}

			//-----------------------------------------
			// Notify upon new file approval
			//-----------------------------------------
			
			if( $file['file_new'] )
			{
				$_url	= $this->registry->output->buildSEOUrl( 'app=bitracker&amp;showfile=' . $file['file_id'], 'public', $file['file_name_furl'], 'bitshowfile' );
				
				//-----------------------------------------
				// Notifications library
				//-----------------------------------------
				
				$classToLoad		= IPSLib::loadLibrary( IPS_ROOT_PATH . '/sources/classes/member/notifications.php', 'notifications' );
				$notifyLibrary		= new $classToLoad( $this->registry );

				$notifyLibrary->setMember( $file );
				$notifyLibrary->setFrom( $file );
				$notifyLibrary->setNotificationKey( 'file_approved' );
				$notifyLibrary->setNotificationUrl( $_url );
				$notifyLibrary->setNotificationText( sprintf( $this->lang->words['moderate_appnotify'], $file['members_display_name'], $file['file_name'] ) );
				$notifyLibrary->setNotificationTitle( sprintf( $this->lang->words['moderate_subject'], $_url, $file['file_name'], $this->lang->words['moderate_app_lang'] ) );
				try
				{
					$notifyLibrary->sendNotification();
				}
				catch( Exception $e ){}
			}

			$num++;
		}

		//-----------------------------------------
		// Hide likes
		//-----------------------------------------
		
		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
		$_like	= classes_like::bootstrap( 'bitracker', 'files' );
		$_like->toggleVisibility( $files, true );
		
		return $num;
	}
	
	
	/**
	 * Delete files
	 *
	 * @access	public
	 * @param	array 		File ids to delete
	 * @return	integer		Number of files deleted
	 */
	public function doMultiDelete( $files=array() )
	{
		if( !count($files) )
		{
			return 0;
		}
		
		$num		= 0;
		$categories	= array();
		
		//-----------------------------------------
		// Forum mod library to delete topics
		//-----------------------------------------	

		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) . '/sources/classes/moderate.php', 'moderatorLibrary', 'forums' );
		$modFunctions	=  new $classToLoad( $this->registry );
        
		//-----------------------------------------
		// Versions library
		//-----------------------------------------	
		
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppdir('bitracker') . '/sources/classes/versioning.php', 'versioningLibrary', 'bitracker' );
		$versions 		= new $classToLoad( $this->registry );
		
		//-----------------------------------------
		// Grab the actual files...
		//-----------------------------------------
		
		$_files	= array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'bitracker_files_records', 'where' => "record_file_id IN(" . implode( ",", $files ) . ")" )	);
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$_files[ $r['record_file_id'] ][]	= $r;
		}
		
		//-----------------------------------------
		// Grab main file records
		//-----------------------------------------	

		$this->DB->build( array( 'select'	=> 'f.*', 
								 'from'		=> array( 'bitracker_files' => 'f' ), 
								 'where'	=> "f.file_id IN(" . implode( ",", $files ) . ")",
								 'add_join'	=> array(
								 					array(
								 						'select'	=> 'm.*',
								 						'from'		=> array( 'members' => 'm' ),
								 						'where'		=> 'm.member_id=f.file_submitter',
								 						'type'		=> 'left',
								 						)
								 					)
						)		);
		$outer = $this->DB->execute();
		
		while( $row = $this->DB->fetch($outer) )
		{
			if( !$this->registry->getClass('bitFunctions')->checkPerms( $row, 'modcandel', 'bit_allow_delete' ) )
			{
				continue;
			}

			if( count($_files[ $row['file_id'] ]) )
			{
				foreach( $_files[ $row['file_id'] ] as $record )
				{
					//-----------------------------------------
					// Delete the files
					//-----------------------------------------	
					
					if( ( $record['record_type'] == 'upload' OR $record['record_type'] == 'ssupload' OR $record['record_type'] == 'nfoupload') AND ( $record['record_storagetype'] == 'disk' ) )
					{
						if( $record['record_type'] == 'upload' )
						{
							@unlink( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localfilepath'] ) . "/" . $record['record_location'] );

						}elseif( $record['record_type'] == 'nfoupload' ){

							@unlink( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localnfopath'] ) . "/" . $record['record_location'] );
                        }
						else
						{
							@unlink( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localsspath'] ) . "/" . $record['record_location'] );
						}

						if( $record['record_thumb'] )
						{
							@unlink( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localsspath'] ) . "/" . $record['record_thumb'] );
						}
					}
					else if( $record['record_storagetype'] == 'ftp' )
					{
						if( $this->settings['bit_remoteurl'] AND
							$this->settings['bit_remoteport'] AND
							$this->settings['bit_remoteuser'] AND
							$this->settings['bit_remotepass'] AND
							$this->settings['bit_remotefilepath'] )
						{
							$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH . '/classFtp.php', 'classFtp' );
							
							try
							{
								classFtp::$transferMode	= FTP_BINARY;
								
								$_ftpClass		= new $classToLoad( $this->settings['bit_remoteurl'], $this->settings['bit_remoteuser'], $this->settings['bit_remotepass'], $this->settings['bit_remoteport'], '/', true, 999999 );
			
								if( $record['record_type'] == 'upload' )
								{
									$_ftpClass->chdir( $this->settings['bit_remotefilepath'] );
								}
								else
								{
									$_ftpClass->chdir( $this->settings['bit_remotesspath'] );
								}

								$_ftpClass->file( $record['record_location'] )->delete();

								if( $this->settings['bit_remotesspath'] AND $record['record_thumb'] )
								{
									$_ftpClass->file( $record['record_thumb'] )->delete();
								}
							}
							catch( Exception $e ) {}
						}
					}
					else if( $record['record_storagetype'] == 'db' )
					{
						$this->DB->delete( 'bitracker_filestorage', "storage_id=" . $record['record_db_id'] );
					}
				}
			}
			
			//-----------------------------------------
			// Delete the topic if appropriate
			//-----------------------------------------		
		
			if( $this->registry->getClass('categories')->cat_lookup[ $row['file_cat'] ]['coptions']['opt_topice'] )
			{
				if( $this->registry->getClass('categories')->cat_lookup[ $row['file_cat'] ]['coptions']['opt_topicf'] )
				{
					if( $this->registry->getClass('categories')->cat_lookup[ $row['file_cat'] ]['coptions']['opt_topicd'] )
					{
						$tid = $row['file_topicid'];
						
						if( $tid > 0 )
						{
					        $modFunctions->init( $this->registry->class_forums->forum_by_id[ $this->registry->getClass('categories')->cat_lookup[ $row['file_cat'] ]['coptions']['opt_topicf'] ] );
					        
							$this->DB->build( array( 'select' => 'tid, forum_id', 'from' => 'topics', 'where' => "state='link' AND moved_to='" . $tid . '&' . $this->registry->getClass('categories')->cat_lookup[ $row['file_cat'] ]['coptions']['opt_topicf'] . "'" ) );
							$this->DB->execute();
							
							if ( $linked_topic = $this->DB->fetch() )
							{
								$this->DB->delete( 'topics', "tid=" . $linked_topic['tid'] );
								
								$modFunctions->forumRecount($linked_topic['forum_id']);
							}
							
							$modFunctions->topicDelete( $tid );
 							$modFunctions->addModerateLog( $this->registry->getClass('categories')->cat_lookup[ $row['file_cat'] ]['coptions']['opt_topicf'], $tid, '', $row['file_name'], $this->lang->words['log_topic_del'] );
						}
					}
				}
			}
			
			//-----------------------------------------
			// Delete old versions
			//-----------------------------------------	
			
			$this->DB->build( array( 'select' => '*', 'from' => 'bitracker_filebackup', 'where' => "b_fileid=" . $row['file_id'] ) );
			$inner = $this->DB->execute();
			
			if( $this->DB->getTotalRows($inner) )
			{
				while( $r = $this->DB->fetch($inner) )
				{
					$versions->remove( $row, $r['b_id'], $r );
				}
			}
			
			//-----------------------------------------
			// Remove logged bitracker
			//-----------------------------------------
			
			$this->DB->delete( 'bitracker_bitracker', 'dfid ='.$row['file_id'] );
			
			//-----------------------------------------
			// Send notifications
			//-----------------------------------------
			
			if( $row['file_new'] )
			{
				$_url	= $this->registry->output->buildSEOUrl( 'app=bitracker&amp;showfile=' . $row['file_id'], 'public', $row['file_name_furl'], 'bitshowfile' );
				
				//-----------------------------------------
				// Notifications library
				//-----------------------------------------
				
				$classToLoad		= IPSLib::loadLibrary( IPS_ROOT_PATH . '/sources/classes/member/notifications.php', 'notifications' );
				$notifyLibrary		= new $classToLoad( $this->registry );

				$notifyLibrary->setMember( $row );
				$notifyLibrary->setFrom( $row );
				$notifyLibrary->setNotificationKey( 'file_approved' );
				$notifyLibrary->setNotificationUrl( $_url );
				$notifyLibrary->setNotificationText( sprintf( $this->lang->words['moderate_dennotify'], $row['members_display_name'], $row['file_name'] ) );
				$notifyLibrary->setNotificationTitle( sprintf( $this->lang->words['moderate_subject'], $_url, $row['file_name'], $this->lang->words['moderate_del_lang'] ) );
				try
				{
					$notifyLibrary->sendNotification();
				}
				catch( Exception $e ){}
			}

			//-----------------------------------------
			// Remove the rest of the data
			//-----------------------------------------
			
			$this->DB->delete( 'bitracker_ccontent', "file_id=" . $row['file_id'] );
			$this->DB->delete( 'bitracker_comments', "comment_fid=" . $row['file_id'] );
			$this->DB->delete( 'bitracker_files', "file_id=" . $row['file_id'] );
			$this->DB->delete( 'bitracker_files_records', "record_file_id=" . $row['file_id'] );
			$this->DB->delete( 'bitracker_torrent_data', "torrent_id=" . $row['file_id'] );
			$this->DB->delete( 'bitracker_fileviews', "view_fid=" . $row['file_id'] );
			$this->DB->delete( 'core_like', "like_app='bitracker' AND like_area='files' AND like_rel_id=" . $row['file_id'] );
			$this->DB->delete( 'core_like_cache', "like_cache_app='bitracker' AND like_cache_area='files' AND like_cache_rel_id=" . $row['file_id'] );

			$categories[ $row['file_cat'] ]	= $row['file_cat'];
			$num++;
		}
		
		//-----------------------------------------
		// Remove tags
		//-----------------------------------------
		
		$this->registry->bitrackerTags->deleteByMetaId( $files );

		//-----------------------------------------
		// Hide likes
		//-----------------------------------------
		
		require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
		$_like	= classes_like::bootstrap( 'bitracker', 'files' );
		$_like->remove( $files );
		
		foreach( $categories as $cat )
		{
			$this->registry->getClass('categories')->rebuildFileinfo( $cat );
			
			$this->fileCat	= $cat;
		}
		
		$this->registry->getClass('categories')->rebuildStatsCache();
		
		return $num;
	}
	
	/**
	 * Rebuild an image thumbnail
	 *
	 * @access	public
	 * @param	array 		File information
	 * @return	boolean		Rebuild successfully
	 */
	public function buildThumbnail( $file=array() )
	{
		//-----------------------------------------
		// Check data
		//-----------------------------------------

		if( !is_array($file) OR !count($file) )
		{
			return false;
		}

		if( $file['record_type'] != 'ssupload' )
		{
			return false;
		}

		$category	= $this->registry->getClass('categories')->cat_lookup[ $file['file_cat'] ];
		$_default	= intval($this->settings['bit_default_dimensions']);
		
		if( !$category['coptions']['opt_thumb_x'] )
		{
			$category['coptions']['opt_thumb_x']	= $_default;
		}

		if( !$category['coptions']['opt_thumb_x'] )
		{
			return false;
		}

		//-----------------------------------------
		// Grab image library
		//-----------------------------------------
		
		$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classImage.php', 'classImage' );
		$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classImageGd.php', 'classImageGd' );
		$image			= new $classToLoad();
		
		//-----------------------------------------
		// Remove old thumbnails
		//-----------------------------------------
		
		$path_additional	= '';

		switch( $file['record_storagetype'] )
		{
			case 'disk':

				if( $file['record_thumb'] )
				{
					@unlink( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localsspath'] ) . $file['record_thumb'] );
				}
				
				$path_additional	= $this->registry->getClass('bitFunctions')->checkForMonthlyDirectory( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localsspath'] ), $file['file_updated'] );
				$path				= str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localsspath'] );
				$filename			= $file['record_location'];
			break;
				
			case 'db':
				$storage	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'bitracker_filestorage', 'where' => 'storage_id=' . $file['record_db_id'] ) );
				
				$fh = fopen( $this->settings['upload_dir'] . '/' . $file['record_location'], 'wb' );
				fputs( $fh, base64_decode( $storage['storage_ss'] ) );
				fclose( $fh );
				
				$path		= $this->settings['upload_dir'];
				$filename	= $file['record_location'];
			break;
				
			case 'ftp':
				if( $this->settings['bit_remoteurl'] AND
					$this->settings['bit_remoteport'] AND
					$this->settings['bit_remoteuser'] AND
					$this->settings['bit_remotepass'] AND
					$this->settings['bit_remotefilepath'] )
				{
					$classToLoad	= IPSLib::loadLibrary( IPS_KERNEL_PATH . '/classFtp.php', 'classFtp' );
					
					try
					{
						classFtp::$transferMode	= FTP_BINARY;

						$_ftpClass		= new $classToLoad( $this->settings['bit_remoteurl'], $this->settings['bit_remoteuser'], $this->settings['bit_remotepass'], $this->settings['bit_remoteport'], '/', true, 999999 );

						$_ftpClass->chdir( $this->settings['bit_remotesspath'] );
						$_ftpClass->file( $file['record_location'] )->download( $this->settings['upload_dir'] . '/' . $file['record_location'] );

					}
					catch( Exception $e )
					{
						return false;
					}

					$path		= $this->settings['upload_dir'];
					$filename	= $file['record_location'];
				}
			break;
		}
		
		if( !$path OR !$filename )
		{
			return false;
		}
		
		if( !is_file( $path . '/' . $filename ) )
		{
			return false;
		}

		//-----------------------------------------
		// Initialize library
		//-----------------------------------------
		
		if( !$image->init( array( 'image_path' => $path, 'image_file' => $filename ) ) )
		{
			return false;
		}
		
		//-----------------------------------------
		// Watermark/copyright text
		//-----------------------------------------
		
		if( $this->settings['bit_addwatermark'] )
		{
			$image->addWatermark( $this->settings['bit_watermarkpath'] );
		}
		else if( $this->settings['bit_addcopyright'] )
		{
			$image->addCopyrightText( ( strpos( $this->settings['bit_copyrighttext'], '%s' ) !== false ) ? sprintf( $this->settings['bit_copyrighttext'], date('Y') ) : $this->settings['bit_copyrighttext'] );
		}

		//-----------------------------------------
		// Resize and write
		//-----------------------------------------
		
		$return = $image->croppedResize( $category['coptions']['opt_thumb_x'], $category['coptions']['opt_thumb_x'] );
		
		if( !$return['newWidth'] )
		{
			return false;
		}

		//-----------------------------------------
		// Monthly folder stuff
		//-----------------------------------------
		
		$_thumbBits	= explode( '/', $filename );
		
		if( is_array($_thumbBits) AND count($_thumbBits) > 1 AND $file['record_storagetype'] == 'disk' )
		{
			$filename		= array_pop($_thumbBits);
		}

		$thumb	= 'thumb-' . $filename;

		//-----------------------------------------
		// Write to disk
		//-----------------------------------------
		
		if( !$image->writeImage( $path . '/' . $path_additional . $thumb ) )
		{
			return false;
		}

		//-----------------------------------------
		// And update database
		//-----------------------------------------
		
		switch( $file['record_storagetype'] )
		{
			case 'disk':
				if( $file['record_id'] )
				{
					$this->DB->update( "bitracker_files_records", array( 'record_thumb' => $path_additional . $thumb ), "record_id=" . $file['record_id'] );
				}
			break;
				
			case 'ftp':
				if( $this->settings['bit_remoteurl'] AND
					$this->settings['bit_remoteport'] AND
					$this->settings['bit_remoteuser'] AND
					$this->settings['bit_remotepass'] AND
					$this->settings['bit_remotefilepath'] )
				{
					try
					{
						$_ftpClass->file( $file['record_thumb'] )->delete();

					}
					catch( Exception $e )
					{
						// File may not exist..that's fine
					}

					try
					{
						$_ftpClass->upload( $this->settings['upload_dir'] . "/" . $thumb, $thumb );

					}
					catch( Exception $e )
					{
						return false;
					}

					@unlink( $this->settings['upload_dir'] . "/" . $thumb );
					
					if( $file['record_id'] )
					{
						$this->DB->update( "bitracker_files_records", array( 'record_thumb' => $thumb ), "record_id=" . $file['record_id'] );
					}
				}
			break;
				
			case 'db':
				// Get file data first
				$filedata = base64_encode( file_get_contents( $path . "/" . $thumb ) );
				
				if( !$filedata )
				{
					return false;
				}

				if( $file['record_id'] )
				{
					$this->DB->update( "bitracker_filestorage", array( 'storage_thumb'	=> $filedata ), "storage_id=" . $file['record_db_id'] );
				}

				@unlink( $path . '/' . $thumb );
				@unlink( $path . '/' . $filename );
			break;
		}
		
		return true;
	}
}