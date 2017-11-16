<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.4
 * bit miscellaneous functions
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
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class bitrackerFunctions
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
	 * Total active users
	 *
	 * @access	protected
	 * @var 	integer
	 */
	protected $total_active			= 0;
		
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
	}
	
	/**
	 * Return the screenshot URL.  Takes into account whether screenshots are web-accessible
	 * or need to be loaded through the PHP handler.
	 * 
	 * @param	array	Screenshot file information
	 * @param	bool	Show thumbnail?
	 * @param	bool	Whether we have already checked for the screenshot (prevents duplicate DB query)
	 * @return	string	URL to screenshot
	 * @note	When $thumb is false, we need to return full URL so the watermark/copyright stamping can be applied correctly
	 */
 	public function returnScreenshotUrl( $file, $thumb=false, $checked=false )
 	{
 		if( !is_array($file) AND intval($file) == $file )
 		{
 			$file	= array( 'file_id' => $file );
 		}

 		if( $this->settings['bit_screenshot_url'] AND $thumb AND $file['record_storagetype'] == 'disk' )
 		{
	 		if( $checked OR $file['record_id'] )
	 		{
	 			if( $file['record_type'] == 'sslink' )
	 			{
	 				return $file['record_location'];
	 			}

	 			$_fileName	= ( $thumb AND $file['record_thumb'] ) ? $file['record_thumb'] : $file['record_location'];
	 			
	 			if( !$_fileName OR !file_exists( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localsspath'] ) . '/' . $_fileName ) )
	 			{
	 				return $this->settings['public_dir'] . '/style_images/' . $this->registry->output->skin['set_image_dir'] . '/bitracker/no_screenshot.png';
	 			}
	 			
	 			return rtrim( $this->settings['bit_screenshot_url'], '/' ) . '/' . $_fileName;
	 		}
	 		else if( $file['file_id'] )
	 		{
	 			$_record	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'bitracker_files_records', 'where' => "record_file_id={$file['file_id']} AND record_type IN('ssupload','sslink') AND record_backup=0", 'order' => 'record_default DESC', 'limit' => array( 1 ) ) );
	 			
	 			if( $_record['record_id'] )
	 			{
		 			if( $_record['record_type'] == 'sslink' )
		 			{
		 				return $_record['record_location'];
		 			}
		 			$_fileName	= ( $thumb AND $_record['record_thumb'] ) ? $_record['record_thumb'] : $_record['record_location'];
		 			
		 			if( !$_fileName OR !file_exists( str_replace( '{root_path}', substr( DOC_IPS_ROOT_PATH, 0, -1 ), $this->settings['bit_localsspath'] ) . '/' . $_fileName ) )
		 			{
		 				return $this->settings['public_dir'] . '/style_images/' . $this->registry->output->skin['set_image_dir'] . '/bitracker/no_screenshot.png';
		 			}
	 			
	 				return rtrim( $this->settings['bit_screenshot_url'], '/' ) . '/' . $_fileName;
 				}
 				else
 				{
 					return $this->settings['public_dir'] . '/style_images/' . $this->registry->output->skin['set_image_dir'] . '/bitracker/no_screenshot.png';
 				}
	 		}
	 		else
	 		{
	 			return $this->settings['public_dir'] . '/style_images/' . $this->registry->output->skin['set_image_dir'] . '/bitracker/no_screenshot.png';
	 		}
 		}

 		/* If it is a remotely linked screenshot, just return the URL */
		if( $thumb AND $file['record_storagetype'] == 'disk' AND $file['record_id'] AND $file['record_type'] == 'sslink' )
		{
			return $file['record_location'];
		}

 		/* If this is an FTP-stored file and we have a remote URL, use that */
		if( $file['record_storagetype'] == 'ftp' AND $file['record_id'] AND $file['record_type'] == 'ssupload' )
		{
			if( $checked OR $file['record_id'] )
			{
				$_fileName	= ( $thumb AND $file['record_thumb'] ) ? $file['record_thumb'] : $file['record_location'];

				if( $_fileName )
				{
					return rtrim( $this->settings['bit_remotessurl'], '/' ) . '/' . $_fileName;
				}
				else
				{
					return $this->settings['public_dir'] . '/style_images/' . $this->registry->output->skin['set_image_dir'] . '/bitracker/no_screenshot.png';
				}
			}
	 		else if( $file['file_id'] )
	 		{
	 			$_record	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'bitracker_files_records', 'where' => "record_file_id={$file['file_id']} AND record_type IN('ssupload','sslink') AND record_backup=0", 'order' => 'record_default DESC', 'limit' => array( 1 ) ) );
	 			
	 			if( $_record['record_id'] )
	 			{
					$_fileName	= ( $thumb AND $_record['record_thumb'] ) ? $_record['record_thumb'] : $_record['record_location'];

					if( $_fileName )
					{
						return rtrim( $this->settings['bit_remotessurl'], '/' ) . '/' . $_fileName;
					}
					else
					{
						return $this->settings['public_dir'] . '/style_images/' . $this->registry->output->skin['set_image_dir'] . '/bitracker/no_screenshot.png';
					}
 				}
 				else
 				{
 					return $this->settings['public_dir'] . '/style_images/' . $this->registry->output->skin['set_image_dir'] . '/bitracker/no_screenshot.png';
 				}
	 		}
	 		else
	 		{
	 			return $this->settings['public_dir'] . '/style_images/' . $this->registry->output->skin['set_image_dir'] . '/bitracker/no_screenshot.png';
	 		}
		}
 		
 		if( $file['record_id'] )
 		{
 			return $this->registry->output->buildSEOUrl( "app=bitracker&amp;module=display&amp;section=screenshot&amp;record=" . $file['record_id'] . '&amp;id=' . $file['record_file_id'] . ( !$thumb ? "&amp;full=1" : '' ), 'public' );
 		}
 		else if( $file['file_id'] )
 		{
 			return $this->registry->output->buildSEOUrl( "app=bitracker&amp;module=display&amp;section=screenshot&amp;id=" . $file['file_id'] . ( !$thumb ? "&amp;full=1" : '' ), 'public' );
 		}
 		else
 		{
 			return $this->settings['public_dir'] . '/style_images/' . $this->registry->output->skin['set_image_dir'] . '/bitracker/no_screenshot.png';
 		}
 	}

	
	/**
	 * Show error message if we're offline
	 *
	 * @access	public
	 * @return	@e void
	 */
	public function checkOnline()
	{
		$groups		= array( $this->memberData['member_group_id'] );
		
		if( $this->memberData['mgroup_others'] )
		{
			foreach( explode( ',', $this->memberData['mgroup_others'] ) as $omg )
			{
				$groups[] = $omg;
			}
		}
		
		$offlineGroups	= explode( ',', $this->settings['bit_offline_groups'] );
		
		if( !$this->settings['bit_online'] )
		{
			$accessOffline	= false;
			
			foreach( $groups as $g )
			{
				if( in_array( $g, $offlineGroups ) )
				{
					$accessOffline	= true;
				}
			}
			
			if( !$accessOffline )
			{
				$this->registry->member()->finalizePublicMember();
				$this->registry->getClass('output')->showError( $this->settings['bit_offline_msg'], null, null, 403 );
			}
		}
	}

	/**
	 * Rebuild the pending comment count for a file
	 *
	 * @access	public
	 * @param	integer		File id
	 * @return 	boolean
	 */
	public function rebuildPendingComments( $file_id=0 )
    {
	    if( !$file_id )
	    {
		    return false;
	    }
	    
	    $file_id = intval($file_id);
	    
	    $comments = $this->DB->buildAndFetch( array( 'select'	=> 'COUNT(*) as coms',
	    															  'from'	=> 'bitracker_comments',
	    															  'where'	=> 'comment_fid=' . $file_id . ' AND comment_open IN (0,-1)'
	    													)		);
	    
	    $comments['coms'] = $comments['coms'] <= 0 ? 0 : $comments['coms'];
	    
	    $this->DB->update( 'bitracker_files', array( 'file_pendcomments' => $comments['coms'] ), 'file_id=' . $file_id );
	    
	    return true;
    }
    
	/**
	 * Rebuild the viewable comment count for a file
	 *
	 * @access	public
	 * @param	integer		File id
	 * @return 	boolean
	 */
	public function rebuildComments( $file_id=0 )
    {
	    if( !$file_id )
	    {
		    return false;
	    }
	    
	    $file_id = intval($file_id);
	    
	    $comments = $this->DB->buildAndFetch( array( 'select'	=> 'COUNT(*) as coms',
	    															  'from'	=> 'bitracker_comments',
	    															  'where'	=> 'comment_fid=' . $file_id . ' AND comment_open=1'
	    													)		);
	    
	    $comments['coms'] = $comments['coms'] <= 0 ? 0 : $comments['coms'];
	    
	    $this->DB->update( 'bitracker_files', array( 'file_comments' => $comments['coms'] ), 'file_id=' . $file_id );
	    
	    return true;
    }
    
	/**
	 * Check permissions to complete an action
	 *
	 * @access	public
	 * @param	array		File info
	 * @param	string		Moderator permission key to check
	 * @param	string		"User allowed" setting to check
	 * @return 	boolean		User can do action or not
	 */
	public function checkPerms( $file=array(), $modperm='modcanapp', $userperm='' )
    {
	    if( !is_array( $file ) OR !count( $file ) )
	    {
		    return false;
	    }
	    
		//-----------------------------------------
		// Got permission?
		//-----------------------------------------
		
		$moderator 	= $this->memberData['g_is_supmod'] ? true : false;
		
		$groups		= array( 'g' . $this->memberData['member_group_id'] );
		
		if( $this->memberData['mgroup_others'] )
		{
			foreach( explode( ',', $this->memberData['mgroup_others'] ) as $omg )
			{
				$groups[] = 'g' . $omg;
			}
		}

		if( !$moderator )		
		{
			if( is_array( $this->registry->getClass('categories')->cat_mods[ $file['file_cat'] ] ) )
			{
				if( count($this->registry->getClass('categories')->cat_mods[ $file['file_cat'] ]) )
				{
					foreach( $this->registry->getClass('categories')->cat_mods[ $file['file_cat'] ] as $k => $v )
					{
						if( $k == "m".$this->memberData['member_id'] )
						{
							if( $v[ $modperm ] )
							{
								$moderator = true;
							}
						}
						else if( in_array( $k, $groups ) )
						{
							if( $v[ $modperm ] )
							{
								$moderator = true;
							}
						}
					}
				}
			}
		}
		
		if( $userperm )
		{
			if( $userperm == 'bit_comment_edit' OR $userperm == 'bit_comment_delete' )
			{
				$member_id	= $file['id'] ? $file['id'] : ( $file['comment_author_id'] ? $file['comment_author_id'] : $file['comment_mid'] );
			}
			else
			{
				$member_id	= $file['file_submitter'] ? $file['file_submitter'] : $file['member_id'];
			}
			
			if( $member_id == $this->memberData['member_id'] && $this->settings[ $userperm ] )
			{
				$moderator = true;
			}
		}
		
		return $moderator;
	}
	
	/**
	 * Is a moderator?
	 *
	 * @access	public
	 * @return 	boolean		User is a moderator
	 */
	public function isModerator()
    {
		$moderator 	= $this->memberData['g_is_supmod'] ? true : false;
		
		$groups		= array( $this->memberData['member_group_id'] );
		
		if( $this->memberData['mgroup_others'] )
		{
			foreach( explode( ',', $this->memberData['mgroup_others'] ) as $omg )
			{
				$groups[] = $omg;
			}
		}

		if( !$moderator )		
		{
			foreach( $groups as $groupId )
			{
				if( is_array( $this->registry->getClass('categories')->group_mods[ $groupId ] ) )
				{
					if( count($this->registry->getClass('categories')->group_mods[ $groupId ]) )
					{
						foreach( $this->registry->getClass('categories')->group_mods[ $groupId ] as $k => $v )
						{
							if( $v['modcanapp'] OR $v['modcanbrok'] )
							{
								$moderator	= true;
								break;
							}
						}
					}
				}
			}
		}

		if( !$moderator )
		{
			if( is_array( $this->registry->getClass('categories')->mem_mods[ $this->memberData['member_id'] ] ) )
			{
				if( count($this->registry->getClass('categories')->mem_mods[ $this->memberData['member_id'] ]) )
				{
					foreach( $this->registry->getClass('categories')->mem_mods[$this->memberData['member_id'] ] as $k => $v )
					{
						if( $v['modcanapp'] OR $v['modcanbrok'] )
						{
							$moderator	= true;
							break;
						}
					}
				}
			}
		}
		
		return $moderator;
	}
	
	/**
	 * Return all moderators
	 *
	 * @access	public
	 * @return 	array 		Members who are moderators
	 */
	public function returnModerators()
    {
    	//-----------------------------------------
    	// Get supermod group ids
    	//-----------------------------------------
    	
    	$group_ids	= array();
    	$member_ids	= array();
		$members	= array();
		
		foreach( $this->cache->getCache('group_cache') as $i )
		{
			if ( $i['g_is_supmod'] )
			{
				$group_ids[ $i['g_id'] ] = $i['g_id'];
			}
			
			if ( $i['g_access_cp'] )
			{
				$group_ids[ $i['g_id'] ] = $i['g_id'];
			}
		}
		
		//-----------------------------------------
		// Get regular moderator group ids
		//-----------------------------------------
		
		if( is_array($this->registry->getClass('categories')->group_mods) AND count($this->registry->getClass('categories')->group_mods) )
		{
			foreach( $this->registry->getClass('categories')->group_mods as $groupId => $_data )
			{
				$group_ids[ $groupId ] = $groupId;
			}
		}
		
		//-----------------------------------------
		// Get members based on group id
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from' => 'members', 'where' => "member_group_id IN(" . implode( ',', $group_ids ) . ")" ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$members[ $r['member_id'] ]	= $r;
		}
    	
		//-----------------------------------------
		// Any member mods?
		//-----------------------------------------
		
		if( is_array($this->registry->getClass('categories')->mem_mods) AND count($this->registry->getClass('categories')->mem_mods) )
		{
			foreach( $this->registry->getClass('categories')->mem_mods as $memberId => $_data )
			{
				$member_ids[ $memberId ]	= $memberId;
			}
		}
		
		//-----------------------------------------
		// Get members based on member id
		//-----------------------------------------
		
		if( count($member_ids) )
		{
			$this->DB->build( array( 'select' => '*', 'from' => 'members', 'where' => "member_id IN(" . implode( ',', $member_ids ) . ")" ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$members[ $r['member_id'] ]	= $r;
			}
		}
    
		//-----------------------------------------
		// Return members
		//-----------------------------------------

		return $members;
	}
	
	/**
	 * Grab stats block and display
	 *
	 * @access	public
	 * @return	@e void
	 */	
	public function getStats()
	{
		/* Grab active users */
		$activeUsers = array();
		
		if( $this->settings['bit_displayactive'] )
		{
			$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/session/api.php', 'session_api' );
			$sessions    = new $classToLoad( $this->registry );
			
			$activeUsers = $sessions->getUsersIn('bitracker');
		}
		
		//-------------------------------------------
		// Mini-stats
		//-------------------------------------------
				
		$show['mini_active']	= $this->total_active;
		$show['mini_files']		= intval($this->caches['bit_stats']['total_files']);
		$show['mini_bitracker']	= intval($this->caches['bit_stats']['total_bitracker']);
		$latest_files			= array();

		//-------------------------------------------
		// Find the latest file you can see
		//-------------------------------------------
		
		if( count($this->registry->getClass('categories')->cat_lookup) )
		{
			foreach( $this->registry->getClass('categories')->cat_lookup as $k => $v )
			{
				if( in_array( $k, $this->registry->getClass('categories')->member_access['show'] ) )
				{
					if( $v['cfileinfo']['date'] > 0 )
					{
						$latest_files[ $v['cfileinfo']['date'] ] = $v['cfileinfo'];
					}
				}
			}
		}
		
		krsort($latest_files);

		$latest = count($latest_files) ? array_shift($latest_files) : array();

		//-------------------------------------------
		// Show random files?
		//-------------------------------------------
		
		if( $this->settings['bit_randomfiles'] AND count($this->registry->getClass('categories')->member_access['view']) )
		{
			$random			= array();
			$_randomIds		= array();
			$count			= $this->settings['bit_randomfiles'] > 0 ? $this->settings['bit_randomfiles'] : 8;

			$this->DB->build( array( 'select'	=> 'f.*',
									 'from'		=> array( 'bitracker_files' => 'f' ),
									 'where' 	=> "f.file_open=1 AND c.copen=1 AND " . $this->DB->buildRegexp( "p.perm_view", $this->member->perm_id_array ),
									 'order' 	=> $this->DB->buildRandomOrder(),
									 'limit' 	=> array( 0, $count ),
									 'add_join'	=> array(
														array(
																'select'	=> 'c.cname as file_category, c.cname_furl',
																'from'		=> array( 'bitracker_categories' => 'c' ),
																'where'		=> 'c.cid=f.file_cat',
																'type'		=> 'left'
															),
														array(
																'from'		=> array( 'permission_index' => 'p' ),
																'where'		=> "p.app='bitracker' AND p.perm_type='cat' AND p.perm_type_id=c.cid",
																'type'		=> 'left'
															),
														array(
																'select'	=> 'm.*',
																'from'		=> array( 'members' => 'm' ),
																'where'		=> "m.member_id=f.file_submitter",
																'type'		=> 'left'
															),
														array(
																'select'	=> 'pp.*',
																'from'		=> array( 'profile_portal' => 'pp' ),
																'where'		=> "pp.pp_member_id=m.member_id",
																'type'		=> 'left'
															),
														)
								)		);
			$this->DB->execute();

			while( $row = $this->DB->fetch() )
			{
				$row['members_display_name']	= $row['members_display_name'] ? $row['members_display_name'] : $this->lang->words['global_guestname'];
				$random[ $row['file_id'] ]		= $row;
				$_randomIds[]					= $row['file_id'];
			}
			
			if( count($_randomIds) )
			{
				$_recordIds	= array();
				
				$this->DB->build( array( 'select' => '*', 'from' => 'bitracker_files_records', 'where' => "record_file_id IN(" . implode( ',', $_randomIds ) . ") AND record_type IN('ssupload','sslink') AND record_backup=0" ) );
				$this->DB->execute();
				
				while( $r = $this->DB->fetch() )
				{
					if( !isset($_recordIds[ $r['record_file_id'] ]) OR $r['record_default'] )
					{
						$_recordIds[ $r['record_file_id'] ]	= $r;
					}
				}
			}
		}
		
		//-------------------------------------------
		// Show stats
		//-------------------------------------------
		
		return $this->registry->getClass('output')->getTemplate('bitracker')->pageEnd( $show, $activeUsers, $latest, $random, $_recordIds );
	}
	
	/**
	 * Get the filename without an extension
	 *
	 * @access	public
	 * @param	string		Filename
	 * @return	string		Filename, no extension
	 */	
	public function getFileName($file)
	{
		return strtolower( str_replace( ".", "", substr( $file, 0, (strrpos( $file, '.' )) ) ) );
	}
	
	/**
	 * Return the allowed mime-types for the category
	 *
	 * @access	public
	 * @param	array		Category
	 * @return	array		Allowed file/screenshot types
	 */	
	public function getAllowedTypes( $category )
	{
		$types						= array(
											'files'	=> array(),
											'nfo'	=> array(),
											'ss'	=> array() 
											);

		if( is_array($this->cache->getCache('bit_mimetypes')) AND count( $this->cache->getCache('bit_mimetypes') ) > 0 )
		{
			foreach( $this->cache->getCache('bit_mimetypes') as $k => $v )
			{
				$addfile	= explode( ",", $v['mime_file'] );
				$addnfo	    = explode( ",", $v['mime_nfo'] );
				$addss		= explode( ",", $v['mime_screenshot'] );

				if( in_array( $category['coptions']['opt_mimemask'], $addfile ) )
				{
					$types['files'][] = $v['mime_extension'];
				}

				if( in_array( $category['coptions']['opt_mimemask'], $addnfo ) )
				{
					$types['nfo'][] = $v['mime_extension'];
				}

				if( in_array( $category['coptions']['opt_mimemask'], $addss ) )
				{
					$types['ss'][] = $v['mime_extension'];
				}
			}
		}
		
		return $types;
	}
	
	/**
	 * Can member submit links?
	 *
	 * @access	public
	 * @return	boolean
	 */	
	public function canSubmitLinks()
	{
		//-----------------------------------------
		// Can we submit links?
		//-----------------------------------------
		
		if( $this->settings['bit_allow_urls'] )
		{
			$groups		= explode( ",", $this->settings['bit_groups_link'] );
			$my_groups	= array( $this->memberData['member_group_id'] );
			
			if( $this->memberData['mgroup_others'] )
			{
				$my_groups = array_merge( $my_groups, explode( ",", IPSText::cleanPermString( $this->memberData['mgroup_others'] ) ) );
			}
			
			foreach( $my_groups as $groupid )
			{
				if( in_array( $groupid, $groups ) )
				{
					return true;
				}
			}
		}
		
		return false;
	}

	/**
	 * Can member import files (submit paths)?
	 *
	 * @access	public
	 * @return	boolean
	 */	
	public function canSubmitPaths()
	{
		//-----------------------------------------
		// Can we import files?
		//-----------------------------------------
		
		if( $this->settings['bit_allow_path'] )
		{
			$groups		= explode( ",", $this->settings['bit_path_users'] );
			$my_groups	= array( $this->memberData['member_group_id'] );
			
			if( $this->memberData['mgroup_others'] )
			{
				$my_groups = array_merge( $my_groups, explode( ",", IPSText::cleanPermString( $this->memberData['mgroup_others'] ) ) );
			}

			foreach( $my_groups as $groupid )
			{
				if( in_array( $groupid, $groups ) )
				{
					return true;
				}
			}
		}
		
		return false;
	}
	
	/**
	 * (Attempt to) Retrieve the filesize of a remotely hosted file
	 *
	 * @access	public
	 * @param	string		URL to file
	 * @return	integer		File size
	 */	
	public function obtainRemoteFileSize( $url="" )
	{
		if( !$url )
		{
			return 0;
		}
		
		if( function_exists( 'curl_init' ) )
		{
			ob_start();
			
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_HEADER, 1);
			curl_setopt($ch, CURLOPT_NOBODY, 1);
			
			$ok = curl_exec($ch);
			curl_close($ch);
			
			$head = ob_get_contents();
			ob_end_clean();
			
			preg_match( '/Content-Length:\s([0-9].+?)\s/', $head, $matches );
			
			return isset($matches[1]) ? $matches[1] : 0;
		}
		else
		{
			if( !parse_url( $url ) )
			{
				return 0;
			}
			else
			{
				$url_bits = parse_url( $url );
			}
		
			if( $url_bits['scheme'] == 'https' )
			{
				$url_bits['host'] = "ssl://" . $url_bits['host'];
			}

			$socket_connection = @fsockopen( $url_bits['host'], 80 );
			
			if( !$socket_connection )
			{
				return 0;
			}
			
			$head = "HEAD $url HTTP/1.0\r\nConnection: Close\r\n\r\n";
			
			fwrite( $socket_connection, $head );
			
   			$i			= 0;
   			$results 	= "";
   			
   			while( true && $i<20 )
   			{
	   			if( $i >= 20 )
	   			{
		   			$results = "";
		   			break;
	   			}
	   			
       			$s = fgets( $socket_connection, 4096 );
       
       			$results .= $s;

       			if( strcmp( $s, "\r\n" ) == 0 || strcmp( $s, "\n" ) == 0 )
       			{
           			break;
       			}
       
       			$i++;
   			}
   
			fclose( $socket_connection );
			
			preg_match( '/Content-Length:\s([0-9].+?)\s/', $results, $matches );
			
			return isset($matches[1]) ? $matches[1] : 0;
		}
	}
	
	/**
	 * Check for monthly directory and create if necessary
	 *
	 * @access	public
	 * @param	string		Directory to check
	 * @return	string		Directory to use
	 */
	public function checkForMonthlyDirectory( $path, $time=0 )
	{
		if( @ini_get("safe_mode") OR $this->settings['safe_mode_skins'] )
		{
			return '';
		}
		
		if( $this->settings['bit_filestorage'] != 'disk' )
		{
			return '';
		}
		
		$time		= $time ? $time : time();
		$this_month	= "monthly_" . gmstrftime( "%m_%Y", $time );
		
		$_path = $path . '/' . $this_month;

		if( ! file_exists( $_path ) )
		{

			if( mkdir( $_path, IPS_FOLDER_PERMISSION ) )
			{
				file_put_contents( $_path . '/index.html', '' );
				chmod( $_path, IPS_FOLDER_PERMISSION );
			}
			
			/* Was it really made or was it lying? */
			if( ! file_exists( $_path ) )
			{
				return '';
			}
			else
			{
				return $this_month . '/';
			}
		}
		else
		{
			return $this_month . '/';
		}

		return '';
	}


	/**
	 * Sort the short: Trim test string
	 */

	public function substrwords($text, $maxchar, $end='...') {
    if (strlen($text) > $maxchar || $text == '') {
        $words = preg_split('/\s/', $text);      
        $output = '';
        $i      = 0;
        while (1) {
            $length = strlen($output)+strlen($words[$i]);
            if ($length > $maxchar) {
                break;
            } 
            else {
                $output .= " " . $words[$i];
                ++$i;
            }
        }
        $output .= $end;
    } 
    else {
        $output = $text;
    }
    return $output;
}

		/**
		* Count the peers
		*
		* @param	var	   torrent_id
		* @param	bool   seeders or leechers (seeders is default)	
		* @return	int    number of seeders
		*/
		public function countPeers( $torrent_id=0, $seeders=TRUE )
		{
		  if ( $torrent_id == 0 OR $torrent_id == '' )
          {
		    return;
          }		  
		  if ( $seeders )
          {
		  $result = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as total', 'from' => 'bitracker_torrent_peers', 'where' => 'torrent=' . $torrent_id . " AND seeder='yes'" ) );
		  }else{
          $result = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as total', 'from' => 'bitracker_torrent_peers', 'where' => 'torrent=' . $torrent_id . " AND seeder='no'" ) );		  
		  }		  
		  return $result['total'];
		}

	/**
	 * @param	array	Is the client connectable?
	 * @param	bool	Whether we have already checked for the screenshot (prevents duplicate DB query)
	 * @return	bool	TRUE or FALSE
	 */
 	public function checkifFirewalled( $ip, $port )
     {
	    $sockres = @fsockopen($ip, $port, $errno, $errstr, 5);

	      if (!$sockres){

		       return true;

	       }else{

		       	return false;
           }

        	@fclose($sockres);
            unset($sockres,$errno,$errstr);
      }

	/**
	 * On client announce error retrieves the error code and string and puts it into the log DB
	 * @param	array	Error info
	 * @param	bool	Whether we have already checked for the screenshot (prevents duplicate DB query)
	 * @note	
	 */
 	public function log_error( $error_code, $error_string )
     {

      $logEntry = $this->DB->buildAndFetch( array( 'select'	=> '*',
	    													   'from'	=> 'bitracker_announce_error_log',
	    													   'where'	=> "request_ip='{$_SERVER['REMOTE_ADDR']}' AND request_infohash='{$this->request['info_hash']}'"
	    													)		);
      if ( $logEntry || !empty($logEntry) )
           { 

               return;

           }else{



	  $newLogEntry	= array(    'request_ip'	 => $_SERVER['REMOTE_ADDR'],
								'request_client'	 => trim($_SERVER['HTTP_USER_AGENT']),
								'request_infohash'	 => trim($this->request['info_hash']),
								'request_perm_key'	 => $this->request['perm_key'],
								'request_time'	 => time(),
								'error_code'	 => trim($error_code),
								'error_string'	 => trim($error_string)

						    );

       $this->DB->insert( 'bitracker_announce_error_log', $newLogEntry );

       }
     }
	/**
	 * Creates a png from an NFO/ACSII file
	 * @param	array	Error 
	 * @param	bool	Whether the file was successfully created or not
	 * @return	bool	file as png $_nfopng
	 * @note	
	 */
     public function output_nfo_image ($path2file, $size='10') 
        {
            $font_file = IPSLib::getAppDir( 'bitracker' ) . '/cour.ttf';
            $nfo_file_lines = file($path2file);
            $width = 0;

            for($i = 0; $i < count($nfo_file_lines); $i++) 
              {
                   $box = imagettfbbox($size, 0, $font_file, $nfo_file_lines[$i]);
                   $width = max($width, $box[2]);
              }

            $image = imagecreate($width, $size * (count($nfo_file_lines) + 1));
            $background_color = imagecolorallocate($image, 0, 0, 0);
            $text_color = imagecolorallocate($image, 255, 255, 255);

            for($i = 0; $i < count($nfo_file_lines); $i++) 
              {
                 imagettftext($image, $size, 0, 0, $size * ($i + 1), $text_color, $font_file, $nfo_file_lines[$i]);
              }

           ob_start();
           imagepng($image);
           $nfo_img_png = ob_get_contents();
           imagedestroy($image);
           ob_end_clean();
                 
          return $nfo_img_png;

       }

	/**
	 * On client announce error retrieves the error code and string and puts it into the log DB
	 * @param	array	Error info
	 * @param	bool	Whether we have already checked for the screenshot (prevents duplicate DB query)
	 * @note	
	 */
     public function convertNfoforDisplay( $nfo )
       {
        $trans = array(
        "\x80" => "&#199;", "\x81" => "&#252;", "\x82" => "&#233;", "\x83" => "&#226;", "\x84" => "&#228;", "\x85" => "&#224;", "\x86" => "&#229;", "\x87" => "&#231;", "\x88" => "&#234;", "\x89" => "&#235;", "\x8a" => "&#232;", "\x8b" => "&#239;", "\x8c" => "&#238;", "\x8d" => "&#236;", "\x8e" => "&#196;", "\x8f" => "&#197;", "\x90" => "&#201;",
        "\x91" => "&#230;", "\x92" => "&#198;", "\x93" => "&#244;", "\x94" => "&#246;", "\x95" => "&#242;", "\x96" => "&#251;", "\x97" => "&#249;", "\x98" => "&#255;", "\x99" => "&#214;", "\x9a" => "&#220;", "\x9b" => "&#162;", "\x9c" => "&#163;", "\x9d" => "&#165;", "\x9e" => "&#8359;", "\x9f" => "&#402;", "\xa0" => "&#225;", "\xa1" => "&#237;",
        "\xa2" => "&#243;", "\xa3" => "&#250;", "\xa4" => "&#241;", "\xa5" => "&#209;", "\xa6" => "&#170;", "\xa7" => "&#186;", "\xa8" => "&#191;", "\xa9" => "&#8976;", "\xaa" => "&#172;", "\xab" => "&#189;", "\xac" => "&#188;", "\xad" => "&#161;", "\xae" => "&#171;", "\xaf" => "&#187;", "\xb0" => "&#9617;", "\xb1" => "&#9618;", "\xb2" => "&#9619;",
        "\xb3" => "&#9474;", "\xb4" => "&#9508;", "\xb5" => "&#9569;", "\xb6" => "&#9570;", "\xb7" => "&#9558;", "\xb8" => "&#9557;", "\xb9" => "&#9571;", "\xba" => "&#9553;", "\xbb" => "&#9559;", "\xbc" => "&#9565;", "\xbd" => "&#9564;", "\xbe" => "&#9563;", "\xbf" => "&#9488;", "\xc0" => "&#9492;", "\xc1" => "&#9524;", "\xc2" => "&#9516;", "\xc3" => "&#9500;",
        "\xc4" => "&#9472;", "\xc5" => "&#9532;", "\xc6" => "&#9566;", "\xc7" => "&#9567;", "\xc8" => "&#9562;", "\xc9" => "&#9556;", "\xca" => "&#9577;", "\xcb" => "&#9574;", "\xcc" => "&#9568;", "\xcd" => "&#9552;", "\xce" => "&#9580;", "\xcf" => "&#9575;", "\xd0" => "&#9576;", "\xd1" => "&#9572;", "\xd2" => "&#9573;", "\xd3" => "&#9561;", "\xd4" => "&#9560;",
        "\xd5" => "&#9554;", "\xd6" => "&#9555;", "\xd7" => "&#9579;", "\xd8" => "&#9578;", "\xd9" => "&#9496;", "\xda" => "&#9484;", "\xdb" => "&#9608;", "\xdc" => "&#9604;", "\xdd" => "&#9612;", "\xde" => "&#9616;", "\xdf" => "&#9600;", "\xe0" => "&#945;", "\xe1" => "&#223;", "\xe2" => "&#915;", "\xe3" => "&#960;", "\xe4" => "&#931;", "\xe5" => "&#963;",
        "\xe6" => "&#181;", "\xe7" => "&#964;", "\xe8" => "&#934;", "\xe9" => "&#920;", "\xea" => "&#937;", "\xeb" => "&#948;", "\xec" => "&#8734;", "\xed" => "&#966;", "\xee" => "&#949;", "\xef" => "&#8745;", "\xf0" => "&#8801;", "\xf1" => "&#177;", "\xf2" => "&#8805;", "\xf3" => "&#8804;", "\xf4" => "&#8992;", "\xf5" => "&#8993;", "\xf6" => "&#247;",
        "\xf7" => "&#8776;", "\xf8" => "&#176;", "\xf9" => "&#8729;", "\xfa" => "&#183;", "\xfb" => "&#8730;", "\xfc" => "&#8319;", "\xfd" => "&#178;", "\xfe" => "&#9632;", "\xff" => "&#160;",
        );
        $trans2 = array("\xe4" => "&auml;",        "\xF6" => "&ouml;",        "\xFC" => "&uuml;",        "\xC4" => "&Auml;",        "\xD6" => "&Ouml;",        "\xDC" => "&Uuml;",        "\xDF" => "&szlig;");
        $all_chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $last_was_ascii = False;
        $tmp = "";
        $nfo = $nfo . "\00";
        for ($i = 0; $i < (strlen($nfo) - 1); $i++)
        {
                $char = $nfo[$i];
                if (isset($trans2[$char]) and ($last_was_ascii or strpos($all_chars, ($nfo[$i + 1]))))
                {
                        $tmp = $tmp . $trans2[$char];
                        $last_was_ascii = True;
                }
                else
                {
                        if (isset($trans[$char]))
                        {
                                $tmp = $tmp . $trans[$char];
                        }
                        else
                        {
                            $tmp = $tmp . $char;
                        }
                        $last_was_ascii = strpos($all_chars, $char);
                }
        }

        return $tmp;

        }

        public function client_error($msg)
         {
	          benc_resp(array('failure reason' => array('type' => 'string', 'value' => $msg)));	
	          exit();
         }

        public function benc_resp($d)
         {
	         benc_resp_raw(benc(array('type' => 'dictionary', 'value' => $d)));
         }

        public function benc_resp_raw($x)
         {
	             header( "Content-Type: text/plain" );
                 header( "Pragma: no-cache" );

if (stristr($_SERVER["HTTP_ACCEPT_ENCODING"],"gzip") && extension_loaded('zlib') && ini_get("zlib.output_compression") == 0) 
 {
    if (ini_get('output_handler')!='ob_gzhandler') 
     {
        ob_start("ob_gzhandler");
        echo $x ; 
        ob_end_flush();
     } else {
        ob_start();
        echo $x ;
        ob_end_flush();
     }
 }else{
        ob_start();
        echo $x ;
        ob_end_flush();
      }
   }

        public function benc($obj) {
 
	         if (!is_array($obj) || !isset($obj["type"]) || !isset($obj["value"]))
		      return;
	         $c = $obj["value"];
	         switch ($obj["type"]) {
	                             	case "string":
			                        return benc_str($c);
		                            case "integer":
			                        return benc_int($c);
		                            case "list":
			                        return benc_list($c);
		                            case "dictionary":
			                        return benc_dict($c);
		                            default:
			return;
	                                }
          }

        public function benc_str($s) {

	            return strlen($s) . ":$s";
        }

        public function benc_int($i) {
 
	            return "i" . $i . "e";
        }

        public function benc_list($a) {

	                 $s = "l";
	                 foreach ($a as $e) {
		             $s .= benc($e);
	                 }
	        $s .= "e";
	        return $s;
        }

        public function benc_dict($d) {

	                 $s = "d";
	                 $keys = array_keys($d);
	                 sort($keys);
	                 foreach ($keys as $k) {
		             $v = $d[$k];
		             $s .= benc_str($k);
		             $s .= benc($v);
	                 }
	        $s .= "e";
	        return $s;
        }


}