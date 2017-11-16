<?php
/**
 *  devCU Software Development
 *  devCU biTracker 1.0.0 Release
 *  Last Updated: $Date: 2014-08-07 09:01:45 -0500 (Thursday, 07 August 2014) $
 *
 * @author 		TG / PM
 * @copyright	(c) 2014 devCU Software Development
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

$_PERM_CONFIG = array( 'Cat' );

class bitrackerPermMappingCat
{
	/**
	 * Mapping of keys to columns
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $mapping = array(
								'view'		=> 'perm_view',
								'show'		=> 'perm_2',
								'add'		=> 'perm_3',
								'download'	=> 'perm_4',
								'comment'	=> 'perm_5',
								'rate'		=> 'perm_6',
								'auto'		=> 'perm_7',
							);

	/**
	 * Mapping of keys to names
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $perm_names = array(
								'view'		=> 'View Files',
								'show'		=> 'Show Files',
								'add'		=> 'Add Files',
								'download'	=> 'download',
								'comment'	=> 'Add Comments',
								'rate'		=> 'Rate Files',
								'auto'		=> 'Bypass Moderation',
							);

	/**
	 * Mapping of keys to background colors for the form
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $perm_colors = array(
								'view'		=> '#fff0f2',
								'show'		=> '#effff6',
								'add'		=> '#edfaff',
								'download'	=> '#f0f1ff',
								'comment'	=> '#fffaee',
								'rate'		=> '#ffeef9',
								'auto'		=> '#fff5ec',
							);

	/**
	 * Method to pull the key/column mapping
	 *
	 * @access	public
	 * @return	array
	 */
	public function getMapping()
	{
		return $this->mapping;
	}

	/**
	 * Method to pull the key/name mapping
	 *
	 * @access	public
	 * @return	array
	 */
	public function getPermNames()
	{
		return $this->perm_names;
	}

	/**
	 * Method to pull the key/color mapping
	 *
	 * @access	public
	 * @return	array
	 */
	public function getPermColors()
	{
		return $this->perm_colors;
	}

	/**
	 * Retrieve the items that support permission mapping
	 *
	 * @access	public
	 * @return	array
	 */
	public function getPermItems()
	{
		/* Category Library */
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'bitracker' ) . "/sources/classes/categories.php", 'class_bitcategories', 'bitracker' );
		$category		= new $classToLoad( ipsRegistry::instance() );
		$category->fullInit();
		
		$cats = $category->catJumpList();
		
		$_return_arr = array();
		foreach( $cats as $r )
		{
			$return_arr[$r[0]] = array(
										'title'     => $r[1],
										'perm_view' => $category->cat_lookup[$r[0]]['perm_view'],
										'perm_2'    => $category->cat_lookup[$r[0]]['perm_2'],
										'perm_3'    => $category->cat_lookup[$r[0]]['perm_3'],
										'perm_4'    => $category->cat_lookup[$r[0]]['perm_4'],
										'perm_5'    => $category->cat_lookup[$r[0]]['perm_5'],
										'perm_6'    => $category->cat_lookup[$r[0]]['perm_6'],
										'perm_7'    => $category->cat_lookup[$r[0]]['perm_7'],										
									);
		}
		
		return $return_arr;
	}	
}

/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.4
 * Item Marking
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 *
 * @package		IP.bitracker
 *
 * @version		$Rev: 10721 $
 *
 */

class itemMarking__bitracker
{
	/**
	 * Field Convert Data Remap Array
	 *
	 * This is where you can map your app_key_# numbers to application savvy fields
	 * 
	 * @access	protected
	 * @var		array
	 */
	protected $_convertData = array( 'forumID' => 'item_app_key_1' );
	
	/**#@+
	 * Registry Object Shortcuts
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $memberData;
	protected $cache;
	protected $caches;
	/**#@-*/
	
	/**
	 * I'm a constructor, twisted constructor
	 *
	 * @access	public
	 * @param	object	ipsRegistry reference
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make objects */
		$this->registry = $registry;
		$this->DB	    = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->lang	    = $this->registry->getClass('class_localization');
		$this->member   = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache	= $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
	}

	/**
	 * Convert Data
	 * Takes an array of app specific data and remaps it to the DB table fields
	 *
	 * @access	public
	 * @param	array
	 * @return	array
	 */
	public function convertData( $data )
	{
		$_data = array();
		
		if( !empty($data) )
		{
			foreach( $data as $k => $v )
			{
				if ( isset($this->_convertData[$k]) )
				{
					$_data[ $this->_convertData[ $k ] ] = intval( $v );
				}
				else
				{
					$_data[ $k ] = $v;
				}
			}
		}
		
		return $_data;
	}
	
	/**
	 * Fetch unread count
	 *
	 * Grab the number of items truly unread
	 * This is called upon by 'markRead' when the number of items
	 * left hits zero (or less).
	 * 
	 *
	 * @access	public
	 * @param	array 	Array of data
	 * @param	array 	Array of read itemIDs
	 * @param	int 	Last global reset
	 * @return	integer	Last unread count
	 */
	public function fetchUnreadCount( $data, $readItems, $lastReset )
	{
		//-----------------------------------------
		// Make sure the functions and cats are there
		//-----------------------------------------

		/* Make sure categories is setup */
		if( ! ipsRegistry::isClassLoaded( 'class_bitcategories' ) )
		{
			$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'bitracker' ) . "/sources/classes/categories.php", 'class_bitcategories', 'bitracker' );
			ipsRegistry::setClass( 'categories', new $classToLoad( ipsRegistry::instance() ) );
			ipsRegistry::getClass( 'categories' )->normalInit();
			ipsRegistry::getClass( 'categories' )->setMemberPermissions();
		}
		
		$lastItem  = 0;
		$count     = 0;
		$approved  = $this->registry->getClass('bitFunctions')->checkPerms( array( 'file_cat' => $data['forumID'] ) ) ? '' : ' AND file_open=1 ';
		$readItems = is_array( $readItems ) ? $readItems : array( 0 );

		if ( $data['forumID'] )
		{
			$_count = $this->DB->buildAndFetch( array( 
															'select' => 'COUNT(*) as cnt, MIN(file_updated) AS lastItem',
															'from'   => 'bitracker_files',
															'where'  => "file_cat=" . intval( $data['forumID'] ) . " {$approved} AND file_id NOT IN(".implode(",",array_keys($readItems)).") AND file_updated > ".intval($lastReset)
													)	);
													
			$count    = intval( $_count['cnt'] );
			$lastItem = intval( $_count['lastItem'] );
		}

		return array( 'count'    => $count,
					  'lastItem' => $lastItem );
	}

	/**
	 * Determines whether to load all markers for this view or not
	 * 
	 * @return	bool
	 */
	public function loadAllMarkers()
	{
		/* We will load our markers ourselves */
		if( !empty($this->request['showfile']) )
		{
			return false;
		}

		return true;
	}
}


class publicSessions__bitracker
{
	/**
	 * Return session variables for this application
	 *
	 * current_appcomponent, current_module and current_section are automatically
	 * stored. This function allows you to add specific variables in.
	 *
	 * @access	public
	 * @author	Matt Mecham
	 * @return	array
	 */
	public function getSessionVariables()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$array = array( 'location_1_type'	=> '',
						'location_1_id'		=> 0,
						'location_2_type'	=> '',
						'location_2_id'		=> 0 );
						
		//-----------------------------------------
		// Store...
		//-----------------------------------------
		
		if( ipsRegistry::$request['section'] == 'screenshot' OR ipsRegistry::$request['section'] == 'nfo' OR ( ipsRegistry::$request['module'] == 'post' AND ipsRegistry::$request['section'] == 'files' ) )
		{
			define( 'NO_SESSION_UPDATE', true );
		}
		
		if ( ipsRegistry::$request['module'] == 'display' )
		{
			$array = array(
							'location_1_type'	=> ipsRegistry::$request['section'],
							'location_1_id'		=> intval(ipsRegistry::$request['id']),
							'location_2_type'	=> substr( ipsRegistry::$request['do'], 0, 10 ),
						);
		}




		return $array;
	}
	
	
	/**
	 * Parse/format the online list data for the records
	 *
	 * @access	public
	 * @author	Brandon Farber
	 * @param	array 			Online list rows to check against
	 * @return	array 			Online list rows parsed
	 */
	public function parseOnlineEntries( $rows )
	{
		if( !is_array($rows) OR !count($rows) )
		{
			return $rows;
		}
		
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$cats_raw	= array();
		$cats		= array();
		$files_raw	= array();
		$files		= array();
		$final		= array();
		
		//-----------------------------------------
		// Extract the topic/forum data
		//-----------------------------------------
		
		foreach( $rows as $row )
		{
			if( $row['current_appcomponent'] != 'bitracker' OR !$row['current_module'] )
			{
				continue;
			}
			
			if( $row['current_module'] == 'display' )
			{
				if( $row['current_section'] == 'category' )
				{
					$cats_raw[ $row['location_1_id'] ]	= $row['location_1_id'];
				}
				else if( $row['current_section'] == 'file' )
				{
					$files_raw[ $row['location_1_id'] ]	= $row['location_1_id'];
				}
				else if( $row['current_section'] == 'screenshot' )
				{
					$files_raw[ $row['location_1_id'] ]	= $row['location_1_id'];
				}
				else if( $row['current_section'] == 'nfo' )
				{
					$files_raw[ $row['location_1_id'] ]	= $row['location_1_id'];
				}
			}
			else if( $row['current_module'] == 'download' )
			{
				$files_raw[ $row['location_1_id'] ]	= $row['location_1_id'];
			}
		}

		//-----------------------------------------
		// Get the categories
		//-----------------------------------------
		
		ipsRegistry::getAppClass( 'bitracker' );
		
		ipsRegistry::getClass('categories')->setMemberPermissions();

		if( count($cats_raw) )
		{
			foreach( ipsRegistry::getClass('categories')->cat_lookup as $cid => $category )
			{
				if( isset($cats_raw[ $cid ]) )
				{
					if( in_array( $cid, ipsRegistry::getClass('categories')->member_access['view'] ) )
					{
						$cats[ $cid ] = $category;
					}
				}
			}
		}

		//-----------------------------------------
		// And the files
		//-----------------------------------------
		
		if( count($files_raw) )
		{
			ipsRegistry::DB()->build( array( 'select' => 'file_id, file_name, file_cat, file_name_furl', 'from' => 'bitracker_files', 'where' => 'file_open=1 AND file_id IN(' . implode( ',', $files_raw ) . ')' ) );
			$tr = ipsRegistry::DB()->execute();
			
			while( $r = ipsRegistry::DB()->fetch($tr) )
			{
				if( count( ipsRegistry::getClass('categories')->cat_lookup[ $r['file_cat'] ] ) )
				{
					if( in_array( $r['file_cat'], ipsRegistry::getClass('categories')->member_access['view'] ) )
					{
						$files[ $r['file_id'] ]	= $r;
					}
				}
			}
		}
		
		//-----------------------------------------
		// Put humpty dumpty together again
		//-----------------------------------------

		foreach( $rows as $row )
		{
			if( $row['current_appcomponent'] != 'bitracker' )
			{
				$final[ $row['id'] ]	= $row;
				
				continue;
			}
		
			if( !$row['current_module'] )
			{
				$row['where_link']		= 'app=bitracker';
				$row['where_line']		= ipsRegistry::getClass( 'class_localization' )->words['bit_loc_idx'];
				$row['_whereLinkSeo']	= ipsRegistry::getClass('output')->buildSEOUrl( $row['where_link'], 'public', 'false', 'app=bitracker' );
				$final[ $row['id'] ]	= $row;
				
				continue;
			}
			
			if( $row['current_module'] == 'display' )
			{
				if( $row['current_section'] == 'category' )
				{
					if( isset($cats[ $row['location_1_id'] ]) )
					{
						$row['where_line']		= ipsRegistry::getClass( 'class_localization' )->words['bit_loc_cat'];
						$row['where_line_more']	= $cats[ $row['location_1_id'] ]['cname'];
						$row['where_link']		= 'app=bitracker&amp;showcat=' . $row['location_1_id'];
						$row['_whereLinkSeo']	= ipsRegistry::getClass('output')->buildSEOUrl( $row['where_link'], 'public', $cats[ $row['location_1_id'] ]['cname_furl'], 'bitshowcat' );
					}
				}
				else if( $row['current_section'] == 'file' )
				{
					if( isset($files[ $row['location_1_id'] ]) )
					{
						$row['where_line']		= ipsRegistry::getClass( 'class_localization' )->words['bit_loc_file'];
						$row['where_line_more']	= $files[ $row['location_1_id'] ]['file_name'];
						$row['where_link']		= 'app=bitracker&amp;showfile=' . $row['location_1_id'];
						$row['_whereLinkSeo']	= ipsRegistry::getClass('output')->buildSEOUrl( $row['where_link'], 'public', $files[ $row['location_1_id'] ]['file_name_furl'], 'bitshowfile' );
					}
				}
				else if( $row['current_section'] == 'screenshot' )
				{
					if( isset($files[ $row['location_1_id'] ]) )
					{
						$row['where_line']		= ipsRegistry::getClass( 'class_localization' )->words['bit_loc_file'];
						$row['where_line_more']	= $files[ $row['location_1_id'] ]['file_name'];
						$row['where_link']		= 'app=bitracker&amp;showfile=' . $row['location_1_id'];
						$row['_whereLinkSeo']	= ipsRegistry::getClass('output')->buildSEOUrl( $row['where_link'], 'public', $files[ $row['location_1_id'] ]['file_name_furl'], 'bitshowfile' );
					}
				}
				else if( $row['current_section'] == 'nfo' )
				{
					if( isset($files[ $row['location_1_id'] ]) )
					{
						$row['where_line']		= ipsRegistry::getClass( 'class_localization' )->words['bit_loc_file'];
						$row['where_line_more']	= $files[ $row['location_1_id'] ]['file_name'];
						$row['where_link']		= 'app=bitracker&amp;showfile=' . $row['location_1_id'];
						$row['_whereLinkSeo']	= ipsRegistry::getClass('output')->buildSEOUrl( $row['where_link'], 'public', $files[ $row['location_1_id'] ]['file_name_furl'], 'bitshowfile' );
					}
				}
			}
			else if( $row['current_module'] == 'download' )
			{
				if( isset($files[ $row['location_1_id'] ]) )
				{
					$row['where_line']		= ipsRegistry::getClass( 'class_localization' )->words['bit_loc_file'];
					$row['where_line_more']	= $files[ $row['location_1_id'] ]['file_name'];
					$row['where_link']		= 'app=bitracker&amp;showfile=' . $row['location_1_id'];
					$row['_whereLinkSeo']	= ipsRegistry::getClass('output')->buildSEOUrl( $row['where_link'], 'public', $files[ $row['location_1_id'] ]['file_name_furl'], 'bitshowfile' );
				}
			}
			else if( $row['current_module'] == 'client' )
			{
				if( $row['current_section'] == 'announce' )
				{
					$row['where_line']		= ipsRegistry::getClass( 'class_localization' )->words['bit_con_ann'];
					$row['where_line_more']	= '';
					$row['where_link']		= '';
					$row['_whereLinkSeo']	= '';
				}
			}
			else
			{
				$row['where_link']		= 'app=bitracker';
				$row['where_line']		= ipsRegistry::getClass( 'class_localization' )->words['bit_loc_idx'];
				$row['_whereLinkSeo']	= ipsRegistry::getClass('output')->buildSEOUrl( $row['where_link'], 'public', 'false', 'app=bitracker' );
			}

			$final[ $row['id'] ]	= $row;
		}
		
		return $final;
	}
}


/**
 * Find ip address extension
 *
 */
class bitracker_findIpAddress
{
	/**
	 * Return ip address lookup tables
	 *
	 * @access	public
	 * @return	array 	Table lookups
	 */
	public function getTables()
	{
		return array(
					'bitracker_files'		=> array( 'file_submitter', 'file_ipaddress', 'file_submitted' ),
					'bitracker_comments'	=> array( 'comment_mid', 'ip_address', 'comment_date' ),
					'bitracker_bitracker'	=> array( 'dmid', 'dip', 'dtime' ),
					);
	}
}
